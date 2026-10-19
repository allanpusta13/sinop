<?php

declare(strict_types=1);

namespace App\Services\Import\Definitions;

use App\Models\User;
use App\Models\Warehouse;
use App\Services\Import\ImportColumn;
use App\Services\Import\ImportDefinition;
use Illuminate\Support\Facades\Gate;

/**
 * WarehouseImportDefinition — controlled warehouse import (issue #220).
 *
 * The code is the only identity axis: a row whose code matches an
 * existing warehouse is classified `update` (confirmation required),
 * every other valid row creates. Imports never merge or match by name
 * (owner decision) — a create row whose normalized name already exists
 * under any other code is rejected as `name_conflict`. `is_active` is
 * not an importable column and is never written on either path.
 */
class WarehouseImportDefinition extends ImportDefinition
{
    public function key(): string
    {
        return 'warehouses';
    }

    public function labelKey(): string
    {
        return 'resources.warehouses.model.singular';
    }

    /**
     * The importable columns, in template order (§7K / issue #220).
     *
     * @return array<int, ImportColumn>
     */
    public function columns(): array
    {
        return [
            new ImportColumn(
                key: 'code',
                labelKey: 'resources.warehouses.fields.code',
                required: true,
                guessKeywords: ['code', 'warehouse code', 'wh'],
                sample: 'WH-0001',
                descriptionKey: 'resources.warehouses.import.desc_code',
            ),
            new ImportColumn(
                key: 'name',
                labelKey: 'resources.warehouses.fields.name',
                required: true,
                guessKeywords: ['name', 'warehouse'],
                sample: 'North Warehouse',
                descriptionKey: 'resources.warehouses.import.desc_name',
            ),
            new ImportColumn(
                key: 'location',
                labelKey: 'resources.warehouses.fields.location',
                guessKeywords: ['location'],
                sample: 'Main St. 123',
                descriptionKey: 'resources.warehouses.import.desc_location',
            ),
        ];
    }

    /**
     * Mirrors the resource gate: warehouse creation (and therefore
     * import) is admin-only (§8.11 `WarehousePolicy::create`). Bound to
     * the given user rather than the session so the queued job path
     * re-authorizes correctly outside an HTTP request.
     */
    public function authorize(User $user): void
    {
        Gate::forUser($user)->authorize('create', Warehouse::class);
    }

    /**
     * Row classification shared by preview and execution.
     *
     * @param  array<int, array{row_number: int, data: array<string, mixed>}>  $rows
     * @return array{rows: array<int, array<string, mixed>>, counts: array{create: int, update: int, reject: int, total: int}}
     */
    public function validate(array $rows): array
    {
        $result = [
            'rows' => [],
            'counts' => ['create' => 0, 'update' => 0, 'reject' => 0, 'total' => count($rows)],
        ];

        /** @var array<string, true> $seenCodes */
        $seenCodes = [];

        /** @var array<string, int> $existingIdsByCode */
        $existingIdsByCode = [];

        /** @var array<string, int> $existingNameCounts */
        $existingNameCounts = [];

        Warehouse::query()->get(['id', 'code', 'name'])->each(function (Warehouse $warehouse) use (&$existingIdsByCode, &$existingNameCounts): void {
            $existingIdsByCode[$this->normalizeCode((string) $warehouse->code)] = (int) $warehouse->getKey();

            $normalizedName = $this->normalizeName((string) $warehouse->name);
            $existingNameCounts[$normalizedName] = ($existingNameCounts[$normalizedName] ?? 0) + 1;
        });

        foreach ($rows as $row) {
            $data = $row['data'];
            $code = trim((string) ($data['code'] ?? ''));
            $name = trim((string) ($data['name'] ?? ''));

            if ($code === '') {
                $result['rows'][] = $this->makeRow($row['row_number'], 'reject', '-', [
                    $this->error('resources.warehouses.import.errors.code_required'),
                ], $data);
                $result['counts']['reject']++;

                continue;
            }

            if ($name === '') {
                $result['rows'][] = $this->makeRow($row['row_number'], 'reject', $code, [
                    $this->error('resources.warehouses.import.errors.name_required'),
                ], $data);
                $result['counts']['reject']++;

                continue;
            }

            $normalizedCode = $this->normalizeCode($code);

            if (isset($seenCodes[$normalizedCode])) {
                $result['rows'][] = $this->makeRow($row['row_number'], 'reject', $code, [
                    $this->error('resources.warehouses.import.errors.duplicate_code_in_file', ['code' => $code]),
                ], $data);
                $result['counts']['reject']++;

                continue;
            }

            $seenCodes[$normalizedCode] = true;

            $existingId = $existingIdsByCode[$normalizedCode] ?? null;

            if ($existingId !== null) {
                $result['rows'][] = $this->makeRow(
                    $row['row_number'],
                    'update',
                    sprintf('%s | id %s', $code, $existingId),
                    [],
                    $data,
                    $existingId,
                );
                $result['counts']['update']++;

                continue;
            }

            $nameMatches = $existingNameCounts[$this->normalizeName($name)] ?? 0;

            if ($nameMatches > 0) {
                $result['rows'][] = $this->makeRow($row['row_number'], 'reject', $code, [
                    $this->error('resources.warehouses.import.errors.name_conflict', [
                        'code' => $code,
                        'name' => $name,
                        'count' => $nameMatches,
                    ]),
                ], $data);
                $result['counts']['reject']++;

                continue;
            }

            $result['rows'][] = $this->makeRow($row['row_number'], 'create', $code, [], $data);
            $result['counts']['create']++;
        }

        return $result;
    }

    /**
     * Apply the classified rows inside the engine transaction. Creates
     * write code + name (+ location only when the column was mapped);
     * updates change name + location on `existing_id` only — the code
     * matched, and `is_active` is not importable.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{created: int, updated: int, skipped: int}
     */
    public function persist(array $rows): array
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            /** @var array<string, mixed> $data */
            $data = $row['data'];

            if (($row['status'] ?? null) === 'create') {
                $payload = ['code' => $data['code'], 'name' => $data['name']];

                if (array_key_exists('location', $data)) {
                    $payload['location'] = $data['location'];
                }

                Warehouse::query()->create($payload);
                $created++;

                continue;
            }

            $warehouse = Warehouse::query()->find($row['existing_id'] ?? null);

            if ($warehouse === null) {
                $skipped++;

                continue;
            }

            $payload = ['name' => $data['name']];

            if (array_key_exists('location', $data)) {
                $payload['location'] = $data['location'];
            }

            $warehouse->update($payload);
            $updated++;
        }

        return ['created' => $created, 'updated' => $updated, 'skipped' => $skipped];
    }

    /**
     * @return array{key: string, params: array<string, mixed>}
     */
    protected function error(string $key, array $params = []): array
    {
        return ['key' => $key, 'params' => $params];
    }

    /** Trim and lowercase for case-insensitive code comparison. */
    protected function normalizeCode(string $code): string
    {
        return mb_strtolower(trim($code));
    }

    /** Trim, collapse whitespace and lowercase for case-insensitive comparison. */
    protected function normalizeName(string $name): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', trim($name)) ?? trim($name));
    }

    /**
     * @param  array<int, array{key: string, params: array<string, mixed>}>  $errors
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function makeRow(int $rowNumber, string $status, string $identifier, array $errors, array $data, ?int $existingId = null): array
    {
        return [
            'row_number' => $rowNumber,
            'status' => $status,
            'identifier' => $identifier,
            'errors' => $errors,
            'data' => $data,
            'existing_id' => $existingId,
        ];
    }
}
