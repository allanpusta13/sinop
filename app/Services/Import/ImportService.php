<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Exceptions\DomainRuleViolationException;
use App\Jobs\ProcessImportJob;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * ImportService — orchestrates the shared import flow for every
 * definition: definition registry, preview generation, confirmation
 * gating, queueing above the inline threshold, and transactional
 * execution that re-parses and re-validates every row against the
 * current database. The preview is never trusted at execution time.
 */
class ImportService
{
    /** Imports above this many rows (creates + updates) are queued. */
    public const QUEUE_THRESHOLD = 100;

    public function __construct(
        protected ImportParser $parser,
        protected ImportFileStore $fileStore,
    ) {}

    /**
     * @return array<string, class-string<ImportDefinition>>
     */
    public function definitions(): array
    {
        return config('imports.definitions', []);
    }

    public function resolveDefinition(string $key): ImportDefinition
    {
        $class = $this->definitions()[$key] ?? null;

        if (! is_string($class) || ! is_a($class, ImportDefinition::class, true)) {
            throw new InvalidArgumentException(__('imports.errors.unknown_definition', ['key' => $key]));
        }

        return app($class);
    }

    /**
     * Build the row-level preview: parse, map, classify.
     *
     * @param  array<string, string|int|null>  $columnMap
     * @return array{rows: array<int, array<string, mixed>>, counts: array{create: int, update: int, reject: int, total: int}, has_updates: bool}
     */
    public function preview(string $filePath, ?string $originalName, string $definitionKey, array $columnMap, User $user): array
    {
        $definition = $this->resolveDefinition($definitionKey);

        $definition->authorize($user);
        $definition->assertMappingsSatisfied($columnMap);

        $parsed = $this->parser->parse($filePath, $originalName);

        $result = $definition->validate(
            $this->extractRows($definition, $parsed['headers'], $parsed['rows'], $columnMap),
        );

        $result['has_updates'] = $result['counts']['update'] > 0;

        return $result;
    }

    /**
     * Execute an import: queued above the inline threshold, inline
     * below. Nothing is persisted before the user confirms.
     *
     * @param  array<string, string|int|null>  $columnMap
     * @param  array{create: int, update: int, reject: int, total?: int}  $expectedCounts
     * @return array{status: 'queued'|'completed', counts: array<string, int>}
     */
    public function execute(string $filePath, ?string $originalName, string $definitionKey, array $columnMap, array $expectedCounts, bool $confirmUpdates, User $user): array
    {
        $definition = $this->resolveDefinition($definitionKey);

        $definition->authorize($user);
        $this->assertUpdatesConfirmed($expectedCounts, $confirmUpdates);

        if (($expectedCounts['create'] ?? 0) + ($expectedCounts['update'] ?? 0) > self::QUEUE_THRESHOLD) {
            ProcessImportJob::dispatch(
                $filePath,
                $originalName,
                $definitionKey,
                $columnMap,
                $expectedCounts,
                $confirmUpdates,
                (int) $user->getKey(),
            );

            return ['status' => 'queued', 'counts' => $expectedCounts];
        }

        $counts = $this->executeTransactional(
            $filePath,
            $originalName,
            $definitionKey,
            $columnMap,
            $expectedCounts,
            $confirmUpdates,
            $user,
        );

        return ['status' => 'completed', 'counts' => $counts];
    }

    /**
     * Re-parse and re-validate every row against the current database,
     * compare with the confirmed counts, then persist inside a
     * transaction. Used by both the inline path and the queued job.
     *
     * @param  array<string, string|int|null>  $columnMap
     * @param  array{create: int, update: int, reject: int, total?: int}  $expectedCounts
     * @return array{created: int, updated: int, rejected: int, skipped: int}
     */
    public function executeTransactional(string $filePath, ?string $originalName, string $definitionKey, array $columnMap, array $expectedCounts, bool $confirmUpdates, User $user): array
    {
        $definition = $this->resolveDefinition($definitionKey);

        $definition->authorize($user);
        $definition->assertMappingsSatisfied($columnMap);
        $this->assertUpdatesConfirmed($expectedCounts, $confirmUpdates);

        $parsed = $this->parser->parse($filePath, $originalName);

        $validated = $definition->validate(
            $this->extractRows($definition, $parsed['headers'], $parsed['rows'], $columnMap),
        );

        $this->assertCountsUnchanged($expectedCounts, $validated['counts']);

        $applicable = array_values(array_filter(
            $validated['rows'],
            static fn (array $row): bool => ($row['status'] ?? null) !== 'reject',
        ));

        return DB::transaction(static function () use ($definition, $applicable, $validated): array {
            $persisted = $definition->persist($applicable);

            return [
                'created' => $persisted['created'],
                'updated' => $persisted['updated'],
                'skipped' => $persisted['skipped'],
                'rejected' => $validated['counts']['reject'],
            ];
        });
    }

    /**
     * Build the mapping suggestions for the mapping step: exact header
     * matches first, then substring matches, per column.
     *
     * @param  array<int, string>  $headers
     * @return array<string, string>
     */
    public function guessMappings(ImportDefinition $definition, array $headers): array
    {
        $guesses = [];

        foreach ($definition->columns() as $column) {
            $candidates = array_filter(array_merge(
                [$column->label()],
                $column->guessKeywords,
            ));

            foreach ($headers as $header) {
                $normalized = mb_strtolower(trim((string) $header));
                $matched = false;

                foreach ($candidates as $candidate) {
                    $candidateNormalized = mb_strtolower(trim($candidate));

                    if ($candidateNormalized === '') {
                        continue;
                    }

                    if ($normalized === $candidateNormalized || str_contains($normalized, $candidateNormalized)) {
                        $guesses[$column->key] = trim((string) $header);
                        $matched = true;

                        break;
                    }
                }

                if ($matched) {
                    break;
                }
            }
        }

        return $guesses;
    }

    /**
     * Map raw parsed rows onto definition fields via the column map.
     * Unmapped columns are absent from `data` (distinct from an empty
     * cell), so definitions can tell "leave alone" from "blank value".
     *
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, mixed>>  $rawRows
     * @param  array<string, string|int|null>  $columnMap
     * @return array<int, array{row_number: int, data: array<string, mixed>}>
     */
    protected function extractRows(ImportDefinition $definition, array $headers, array $rawRows, array $columnMap): array
    {
        $indexes = [];

        foreach ($definition->columns() as $column) {
            $mapped = $columnMap[$column->key] ?? null;

            if (blank($mapped)) {
                continue;
            }

            $index = is_int($mapped) ? $mapped : $this->resolveColumnIndex($mapped, $headers);

            if ($index !== null) {
                $indexes[$column->key] = $index;
            }
        }

        $rows = [];

        foreach ($rawRows as $rowNumber => $row) {
            $data = [];

            foreach ($indexes as $field => $index) {
                $value = $row[$index] ?? null;

                $data[$field] = is_scalar($value) ? trim((string) $value) : '';
            }

            $rows[] = ['row_number' => $rowNumber, 'data' => $data];
        }

        return $rows;
    }

    /**
     * @param  array<int, string>  $headers
     */
    protected function resolveColumnIndex(string $header, array $headers): ?int
    {
        foreach ($headers as $index => $candidate) {
            if (mb_strtolower(trim((string) $candidate)) === mb_strtolower(trim($header))) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @param  array{update?: int}  $expectedCounts
     */
    protected function assertUpdatesConfirmed(array $expectedCounts, bool $confirmUpdates): void
    {
        if (($expectedCounts['update'] ?? 0) > 0 && ! $confirmUpdates) {
            throw new DomainRuleViolationException('errors.import_update_confirmation_required', [
                ':updates' => $expectedCounts['update'],
            ]);
        }
    }

    /**
     * @param  array{create?: int, update?: int, reject?: int}  $expected
     * @param  array{create: int, update: int, reject: int}  $actual
     */
    protected function assertCountsUnchanged(array $expected, array $actual): void
    {
        foreach (['create', 'update', 'reject'] as $bucket) {
            if ((int) ($expected[$bucket] ?? 0) !== (int) $actual[$bucket]) {
                throw new DomainRuleViolationException('errors.import_counts_mismatch', [
                    ':expected_create' => $expected['create'] ?? 0,
                    ':expected_update' => $expected['update'] ?? 0,
                    ':expected_reject' => $expected['reject'] ?? 0,
                    ':actual_create' => $actual['create'],
                    ':actual_update' => $actual['update'],
                    ':actual_reject' => $actual['reject'],
                ]);
            }
        }
    }
}
