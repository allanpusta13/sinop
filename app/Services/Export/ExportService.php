<?php

declare(strict_types=1);

namespace App\Services\Export;

use App\Jobs\ProcessExportJob;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

/**
 * ExportService — registry resolution and export orchestration.
 *
 * Mirrors ImportService: definitions are resolved from
 * `config('exports.definitions')`, authorization is server-side, and
 * exports above the inline row threshold are handed to the queue
 * instead of running inside the request.
 *
 * `export()` accepts the List page's table query — already scoped to
 * the user's warehouses, filtered, searched and sorted by Filament —
 * so what lands in the file is exactly what the user was looking at.
 * That query is compiled once into a SQL snapshot plus bindings, which
 * both the inline path and the queued job share.
 */
class ExportService
{
    /**
     * Rows at or below this count are generated inside the request;
     * anything above is queued.
     */
    public const QUEUE_THRESHOLD = 100;

    /**
     * The only formats the generator can write. Validated here, before a
     * filename is allocated or a job is dispatched, so an unsupported
     * format never reaches the queue — the generator checks again as the
     * second gate.
     */
    public const SUPPORTED_FORMATS = ['csv', 'xlsx'];

    public function __construct(
        protected ExportGenerator $generator,
        protected ExportFileStore $fileStore,
    ) {}

    /** @return array<string, class-string<ExportDefinition>> */
    public function definitions(): array
    {
        $definitions = config('exports.definitions', []);

        return is_array($definitions) ? $definitions : [];
    }

    public function resolveDefinition(string $key): ExportDefinition
    {
        $class = $this->definitions()[$key] ?? null;

        if (! is_string($class) || ! is_a($class, ExportDefinition::class, true)) {
            throw new InvalidArgumentException(__('exports.errors.unknown_definition', ['key' => $key]));
        }

        return app($class);
    }

    /**
     * Run an export against the given table query.
     *
     * @return array{status: 'completed'|'queued', rows: int, filename: string}
     *
     * @throws InvalidArgumentException when the definition key or format is not supported
     */
    public function export(Builder $tableQuery, string $definitionKey, string $format, User $user): array
    {
        $definition = $this->resolveDefinition($definitionKey);
        $definition->authorize($user);

        $format = strtolower($format);

        if (! in_array($format, self::SUPPORTED_FORMATS, true)) {
            throw new InvalidArgumentException(__('exports.errors.unsupported_format'));
        }

        $exportQuery = $definition->query($tableQuery);
        $rows = $this->rowCount($exportQuery);
        [$sql, $bindings] = $this->snapshot($exportQuery);

        $userId = (int) $user->getKey();
        $this->fileStore->ensureUserDirectory($userId);
        $this->fileStore->sweep($userId);

        $filename = $this->fileStore->allocateFilename($userId, $definitionKey, $format);

        if ($rows > self::QUEUE_THRESHOLD) {
            ProcessExportJob::dispatch($sql, $bindings, $definitionKey, $format, $filename, $user)
                ->onConnection((string) config('queue.export_connection', config('queue.default')));

            return ['status' => 'queued', 'rows' => $rows, 'filename' => $filename];
        }

        $rows = $this->generator->generate(
            $definition,
            $sql,
            $bindings,
            $format,
            $this->fileStore->absolutePath($userId, $filename),
        );

        return ['status' => 'completed', 'rows' => $rows, 'filename' => $filename];
    }

    /**
     * Compile a definition query into a SQL snapshot plus bindings.
     * `toBase()` applies global scopes, so soft-deleted rows stay out
     * of the file exactly as they stay out of the table.
     *
     * @return array{0: string, 1: array<int, mixed>}
     */
    public function snapshot(Builder $query): array
    {
        $this->qualifyAmbiguousColumns($query);

        $base = $query->toBase();

        // Paged generation appends limit/offset, which needs a total order
        // to stay stable across pages. The incoming sort may be absent
        // (child definitions build fresh builders) or carry ties (many rows
        // sharing a created_at), both of which shuffle rows between pages.
        // The primary key is unique and always present, and appending it
        // leaves a sort the user already chose in the lead — it is skipped
        // where the sort already carries it, since a unique column
        // anywhere in the order makes the whole order total.
        $primaryKey = $query->getModel()->getQualifiedKeyName();

        $sortIsTotal = collect($base->orders ?? [])
            ->contains(static fn (array $order): bool => ($order['column'] ?? null) === $primaryKey);

        if (! $sortIsTotal) {
            $base->orderBy($primaryKey);
        }

        return [$base->toSql(), $base->getBindings()];
    }

    /**
     * Filament hands the export a query whose sorts and filters name
     * columns bare ("created_at desc", "name like ?"). Every definition
     * joins at least one table carrying the same names — `users`,
     * `warehouses` and `product_variants` all have `created_at`, `name`
     * and `updated_at` — so an unqualified reference is ambiguous on
     * MySQL and SQLite alike. Qualifying against the definition's own
     * table keeps the user's chosen sort and filters and still compiles.
     *
     * A sort or filter that targets a relationship arrives as an
     * `Expression` (already-compiled SQL) or as a dotted column; both are
     * left alone, and a bare name is only rewritten when it really exists
     * on the base table so a reference to a joined column is never
     * repointed at the wrong table.
     */
    protected function qualifyAmbiguousColumns(Builder $query): void
    {
        $base = $query->getQuery();
        $table = $query->getModel()->getTable();

        $columns = Schema::connection($query->getConnection()->getName())
            ->getColumnListing($table);

        foreach ($base->orders ?? [] as $index => $order) {
            if ($this->isBareColumn($order['column'] ?? null)) {
                $base->orders[$index]['column'] = $table.'.'.$order['column'];
            }
        }

        $base->wheres = $this->qualifyWheres($base, $base->wheres ?? [], $table, $columns);
    }

    /**
     * Qualify the column references in a where tree. Filament wraps a
     * search term in a nested `or` group, so a top-level pass alone would
     * leave `name like ?` bare inside the group it created.
     *
     * A nested group holds another query builder rather than a plain
     * array, so it is walked in place.
     *
     * A search term is worse than a bare column: Filament compiles it
     * itself into `whereRaw`, embedding the quoted column name in the SQL
     * text, so there is no `column` key to rewrite. Those are matched by
     * the wrapped name they start with and repointed the same way — the
     * quote character the grammar adds makes a prefix such as `sku`
     * safe against a joined `sku_code`.
     *
     * `Exists` wheres carry a query of their own whose columns belong to
     * the joined table they search, and are left alone, as is any
     * reference that exists only on a joined table.
     *
     * @param  array<int, array<string, mixed>>  $wheres
     * @param  array<int, string>  $columns
     * @return array<int, array<string, mixed>>
     */
    protected function qualifyWheres(QueryBuilder $base, array $wheres, string $table, array $columns): array
    {
        foreach ($wheres as $index => $where) {
            if (($where['type'] ?? null) === 'Nested') {
                $nested = $where['query'] ?? null;

                if ($nested instanceof QueryBuilder) {
                    $nested->wheres = $this->qualifyWheres($base, $nested->wheres ?? [], $table, $columns);
                }

                continue;
            }

            if (($where['type'] ?? null) === 'raw' && is_string($where['sql'] ?? null)) {
                $wheres[$index]['sql'] = $this->qualifyRawWhere($base, $where['sql'], $table, $columns);

                continue;
            }

            if ($this->isBareColumn($where['column'] ?? null)
                && in_array($where['column'], $columns, true)) {
                $wheres[$index]['column'] = $table.'.'.$where['column'];
            }
        }

        return $wheres;
    }

    /**
     * Repoint the leading column of a Filament-compiled search clause at
     * the definition's own table. Anything that does not begin with a
     * quoted base-table column is returned untouched, so a hand-written
     * raw clause can never be rewritten by accident.
     */
    protected function qualifyRawWhere(QueryBuilder $base, string $sql, string $table, array $columns): string
    {
        $grammar = $base->getGrammar();

        foreach ($columns as $column) {
            $wrapped = $grammar->wrap($column);

            if (str_starts_with($sql, $wrapped.' ')) {
                return $grammar->wrap($table.'.'.$column).substr($sql, strlen($wrapped));
            }
        }

        return $sql;
    }

    /**
     * A plain, unqualified column reference — never an `Expression`
     * (which strict_types would reject) and never already dotted.
     */
    protected function isBareColumn(mixed $column): bool
    {
        return is_string($column)
            && $column !== ''
            && ! str_contains($column, '.');
    }

    /**
     * Row count for a definition query, used for the inline/queued
     * decision. Counting through the builder (not the snapshot) lets
     * the grammar drop the ORDER BY, which a wrapped sub-select could
     * not carry safely.
     */
    public function rowCount(Builder $query): int
    {
        return (int) $query->toBase()->count();
    }
}
