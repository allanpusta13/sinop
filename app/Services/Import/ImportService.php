<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Exceptions\DomainRuleViolationException;
use App\Jobs\ProcessImportJob;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PDOException;

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
        private readonly ImportParser $parser,
        private readonly ImportFileStore $fileStore,
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
            )->onConnection((string) config('queue.import_connection', config('queue.default')));

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
     * Re-parse every row, then validate, compare against the confirmed
     * counts and persist all inside one transaction, so the counts the
     * user confirmed are checked against the same state the write
     * lands on. Used by both the inline path and the queued job.
     *
     * A duplicate-key violation means a concurrent write won a unique
     * key between the confirmation and this run; the transaction rolls
     * the whole run back with it — including rows persist() already
     * wrote — and it is reported through the standard counts-mismatch
     * flow with freshly read counts, so the user re-reviews against
     * reality instead of hitting an error page.
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
        $rows = $this->extractRows($definition, $parsed['headers'], $parsed['rows'], $columnMap);

        try {
            return DB::transaction(function () use ($definition, $rows, $expectedCounts): array {
                $validated = $definition->validate($rows);

                $this->assertCountsUnchanged($expectedCounts, $validated['counts']);

                $applicable = array_values(array_filter(
                    $validated['rows'],
                    static fn (array $row): bool => ($row['status'] ?? null) !== 'reject',
                ));

                $persisted = $definition->persist($applicable);

                // A skip only happens when an update target vanished
                // between the confirmation and the write: the confirmed
                // counts did not materialise, so nothing is committed and
                // the user is sent through the standard mismatch flow
                // (which refreshes the preview against reality).
                if ($persisted['skipped'] > 0) {
                    throw new DomainRuleViolationException('errors.import_counts_mismatch', [
                        'expected_create' => $expectedCounts['create'] ?? 0,
                        'expected_update' => $expectedCounts['update'] ?? 0,
                        'expected_reject' => $expectedCounts['reject'] ?? 0,
                        'actual_create' => $validated['counts']['create'],
                        'actual_update' => $validated['counts']['update'],
                        'actual_reject' => $validated['counts']['reject'],
                    ]);
                }

                return [
                    'created' => $persisted['created'],
                    'updated' => $persisted['updated'],
                    'skipped' => $persisted['skipped'],
                    'rejected' => $validated['counts']['reject'],
                ];
            });
        } catch (QueryException $exception) {
            if (! $this->isDuplicateKeyViolation($exception)) {
                throw $exception;
            }

            $fresh = $definition->validate($rows);

            throw new DomainRuleViolationException('errors.import_counts_mismatch', [
                'expected_create' => $expectedCounts['create'] ?? 0,
                'expected_update' => $expectedCounts['update'] ?? 0,
                'expected_reject' => $expectedCounts['reject'] ?? 0,
                'actual_create' => $fresh['counts']['create'],
                'actual_update' => $fresh['counts']['update'],
                'actual_reject' => $fresh['counts']['reject'],
            ]);
        }
    }

    /**
     * A unique-key violation (MySQL 1062, SQLite/Postgres UNIQUE
     * constraint messages) — the race signature of a row inserted
     * between the confirmation and this transaction's write. Any other
     * constraint or query failure is a real error and propagates.
     */
    protected function isDuplicateKeyViolation(QueryException $exception): bool
    {
        $sqlState = $exception->getCode();
        if (is_string($sqlState) && $sqlState === '23505') {
            return true;
        }

        $previous = $exception->getPrevious();
        if ($previous instanceof PDOException && (string) $previous->getCode() === '23505') {
            return true;
        }

        $errorInfo = $exception->errorInfo
            ?? ($previous instanceof PDOException ? $previous->errorInfo : null);

        $sqlStateFromInfo = is_array($errorInfo) ? (string) ($errorInfo[0] ?? '') : '';
        $driverCode = is_array($errorInfo) ? (int) ($errorInfo[1] ?? 0) : 0;
        $message = is_array($errorInfo) ? (string) ($errorInfo[2] ?? '') : $exception->getMessage();

        return $sqlStateFromInfo === '23505'
            || $driverCode === 1062
            || str_contains($message, 'Duplicate entry')
            || str_contains($message, 'UNIQUE constraint')
            || str_contains($message, 'unique constraint');
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
     * An integer mapping is a header index and must fall inside the
     * header row; out-of-range integers read as unmapped, never as a
     * blank-value column.
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

            $index = is_int($mapped)
                ? (array_key_exists($mapped, $headers) ? $mapped : null)
                : $this->resolveColumnIndex($mapped, $headers);

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
                'updates' => $expectedCounts['update'],
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
                    'expected_create' => $expected['create'] ?? 0,
                    'expected_update' => $expected['update'] ?? 0,
                    'expected_reject' => $expected['reject'] ?? 0,
                    'actual_create' => $actual['create'],
                    'actual_update' => $actual['update'],
                    'actual_reject' => $actual['reject'],
                ]);
            }
        }
    }
}
