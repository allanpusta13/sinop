<?php

declare(strict_types=1);

namespace App\Services\Export;

use App\Jobs\ProcessExportJob;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Throwable;

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

    /**
     * Prefix of the hidden snapshot columns that place a row inside a
     * warehouse. They are selected alongside the real ones so the queued
     * job can re-narrow the snapshot, and the generator ignores them
     * because it only ever writes declared column keys.
     */
    public const SCOPE_ALIAS_PREFIX = '__scope_wh_';

    /**
     * Alias the narrowed snapshot is wrapped in when the queued job
     * intersects it with the user's live warehouse scope.
     */
    public const SCOPE_ALIAS = '__export_scope';

    public function __construct(
        private readonly ExportGenerator $generator,
        private readonly ExportFileStore $fileStore,
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
        [$sql, $bindings] = $this->snapshot($definition, $exportQuery);

        $userId = (int) $user->getKey();
        $this->fileStore->ensureUserDirectory($userId);
        $this->fileStore->sweep($userId);

        $filename = $this->fileStore->allocateFilename($userId, $definitionKey, $format);

        if ($rows > self::QUEUE_THRESHOLD) {
            ProcessExportJob::dispatch($sql, $bindings, $definitionKey, $format, $filename, $user)
                ->onConnection((string) config('queue.export_connection', config('queue.default')));

            return ['status' => 'queued', 'rows' => $rows, 'filename' => $filename];
        }

        try {
            $rows = $this->generator->generate(
                $definition,
                $sql,
                $bindings,
                $format,
                $this->fileStore->absolutePath($userId, $filename),
            );
        } catch (Throwable $exception) {
            // The header row is already on disk by now, and a
            // half-written file would still be served by its own
            // download link until the next sweep — mirrors
            // ProcessExportJob's discard on failure.
            $this->fileStore->discard($userId, $filename);

            throw $exception;
        }

        // Provenance for the download route: which definition and
        // which warehouse scope this file was written with, so a scope
        // that shrinks before the download is caught there.
        $this->fileStore->writeScopeMeta($userId, $filename, $definitionKey, $this->currentWarehouseScope($definition, $user));

        return ['status' => 'completed', 'rows' => $rows, 'filename' => $filename];
    }

    /**
     * Compile a definition query into a SQL snapshot plus bindings.
     * `toBase()` applies global scopes, so soft-deleted rows stay out
     * of the file exactly as they stay out of the table.
     *
     * The definition travels with the query because a snapshot is not
     * just the columns: it also carries the hidden warehouse columns a
     * queued job re-narrows against, and those have to be part of the
     * compiled SQL rather than something the job adds afterwards.
     *
     * @return array{0: string, 1: array<int, mixed>}
     */
    public function snapshot(ExportDefinition $definition, Builder $query): array
    {
        $this->exposeWarehouseScope($definition, $query);
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
            if ($this->isBareColumn($order['column'] ?? null)
                && in_array($order['column'], $columns, true)) {
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
     * The warehouses the user holds *right now* — re-derived live, never
     * carried in the snapshot, because the point is that they may have
     * changed while a job waited.
     *
     * Returns `null` when there is nothing to narrow: an export with no
     * warehouse scope of its own (master data), or an unrestricted user,
     * whose file the dispatch snapshot already bounds. Returns the id
     * list otherwise, which is empty exactly when the user has lost
     * every warehouse and must not be handed a file at all.
     *
     * @return array<int, int>|null
     */
    public function currentWarehouseScope(ExportDefinition $definition, User $user): ?array
    {
        if ($definition->warehouseScope() === []) {
            return null;
        }

        // Admin and auditor reach every warehouse in every resource, so
        // there is no scope to apply — and applying one would only risk
        // handing back a narrower file than they are entitled to.
        if ($user->isAdmin() || $user->isAuditor()) {
            return null;
        }

        return $user->warehouses()
            ->pluck('warehouses.id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * Intersect a snapshot with the warehouses the user holds now.
     *
     * The snapshot is wrapped, not rewritten: it already carries the
     * dispatch-time scope, every filter the table applied and the user's
     * chosen sort, and an intersection can only ever remove rows — so
     * warehouses granted while the job waited cannot widen the file.
     * Paging still lands on the wrapper, because the engine appends its
     * `limit`/`offset` to whatever SQL it is given.
     *
     * @param  array<int, mixed>  $bindings
     * @param  array<int, int|string>  $warehouseIds
     * @return array{0: string, 1: array<int, mixed>}
     */
    public function narrowToScope(string $sql, array $bindings, ExportDefinition $definition, array $warehouseIds): array
    {
        $grammar = DB::getQueryGrammar();
        $wrapped = $grammar->wrap(self::SCOPE_ALIAS);

        // An empty id list would compile to an empty `in ()`, which no
        // engine accepts; a snapshot matching nothing is the honest
        // result anyway.
        if ($warehouseIds === []) {
            return ['select * from ('.$sql.') as '.$wrapped.' where 1 = 0', $bindings];
        }

        $placeholders = implode(',', array_fill(0, count($warehouseIds), '?'));
        $columns = $this->scopeColumnNames($definition);
        $groups = [];
        $extra = [];

        foreach ($definition->warehouseScope() as $group) {
            $terms = [];

            foreach ($group as $column) {
                $terms[] = $grammar->wrap(self::SCOPE_ALIAS.'.'.self::SCOPE_ALIAS_PREFIX.$columns[$column])
                    .' in ('.$placeholders.')';

                $extra = array_merge($extra, $warehouseIds);
            }

            // A group is "either of these columns"; the groups are
            // "and"ed, which is what separates a requisition's either
            // endpoint from a direct transfer's both.
            $groups[] = '('.implode(' or ', $terms).')';
        }

        return [
            'select * from ('.$sql.') as '.$wrapped.' where '.implode(' and ', $groups),
            array_merge($bindings, $extra),
        ];
    }

    /**
     * Select the warehouse columns a queued export needs to be
     * re-narrowed, aliased out of the way of the declared ones.
     *
     * Always selected, queued or not, so that one snapshot serves both
     * paths; the inline path simply never reads them.
     */
    protected function exposeWarehouseScope(ExportDefinition $definition, Builder $query): void
    {
        foreach ($this->scopeColumns($definition) as $index => $column) {
            $query->addSelect($column.' as '.self::SCOPE_ALIAS_PREFIX.$index);
        }
    }

    /**
     * Every scope column of a definition, in selection order — the one
     * ordering the alias index generation and the name lookup share.
     *
     * @return array<int, string>
     */
    protected function scopeColumns(ExportDefinition $definition): array
    {
        $columns = [];

        foreach ($definition->warehouseScope() as $group) {
            foreach ($group as $column) {
                $columns[] = $column;
            }
        }

        return $columns;
    }

    /**
     * Every scope column of a definition keyed by the alias index it
     * was given, so a column can be looked up by the name the scope
     * declaration uses.
     *
     * @return array<string, int>
     */
    protected function scopeColumnNames(ExportDefinition $definition): array
    {
        return array_flip($this->scopeColumns($definition));
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
