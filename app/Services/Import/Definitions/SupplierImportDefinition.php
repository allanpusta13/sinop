<?php

declare(strict_types=1);

namespace App\Services\Import\Definitions;

use App\Models\Supplier;
use App\Models\User;
use App\Services\Import\ImportColumn;
use App\Services\Import\ImportDefinition;
use Illuminate\Support\Facades\Gate;

/**
 * SupplierImportDefinition — create-only import of suppliers on the
 * shared engine (issue #223).
 *
 * Suppliers are minimal master data identified by `name` only (owner
 * decision, §2.14); names are not unique in the schema and carry no
 * unique validation, so this import NEVER updates or merges. Rows
 * whose normalized name already exists (including soft-deleted
 * records) are rejected with a `possible_match` reason so a human
 * reviews the flagged row before anything is written.
 *
 * CustomerImportDefinition is a line-for-line mirror of this class
 * (only the model, policy gate, and translation prefix differ); keep
 * any rule change in sync across both.
 */
class SupplierImportDefinition extends ImportDefinition
{
    public function key(): string
    {
        return 'suppliers';
    }

    public function labelKey(): string
    {
        return 'resources.suppliers.model.singular';
    }

    /**
     * The importable columns, in template order.
     *
     * @return array<int, ImportColumn>
     */
    public function columns(): array
    {
        return [
            new ImportColumn(
                key: 'name',
                labelKey: 'resources.suppliers.fields.name',
                required: true,
                guessKeywords: ['name', 'company'],
                sample: 'Acme Trading',
                descriptionKey: 'resources.suppliers.import.desc_name',
            ),
            new ImportColumn(
                key: 'contact_person',
                labelKey: 'resources.suppliers.fields.contact_person',
                sample: 'Jane Doe',
                descriptionKey: 'resources.suppliers.import.desc_contact_person',
            ),
            new ImportColumn(
                key: 'phone',
                labelKey: 'resources.suppliers.fields.phone',
                sample: '+1 555 0100',
                descriptionKey: 'resources.suppliers.import.desc_phone',
            ),
            new ImportColumn(
                key: 'email',
                labelKey: 'resources.suppliers.fields.email',
                sample: 'sales@acme.example',
                descriptionKey: 'resources.suppliers.import.desc_email',
            ),
            new ImportColumn(
                key: 'address',
                labelKey: 'resources.suppliers.fields.address',
                sample: '123 Main St',
                descriptionKey: 'resources.suppliers.import.desc_address',
            ),
        ];
    }

    public function authorize(User $user): void
    {
        Gate::forUser($user)->authorize('create', Supplier::class);
    }

    /**
     * Classify every row: blank name → reject, in-file duplicate →
     * reject, existing name (incl. soft-deleted) → reject flagged for
     * review, otherwise create. Updates never occur.
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

        /** @var array<string, true> $seen */
        $seen = [];

        /** @var array<string, int> $existingIds */
        $existingIds = $this->existingIdsByNormalizedName();

        foreach ($rows as $row) {
            $data = $row['data'];
            $name = trim((string) ($data['name'] ?? ''));
            $normalized = $this->normalizeName($name);

            if ($name === '') {
                $result['rows'][] = $this->makeRow($row['row_number'], 'reject', '-', $data, [
                    ['key' => 'resources.suppliers.import.errors.name_required', 'params' => []],
                ]);
                $result['counts']['reject']++;

                continue;
            }

            if (isset($seen[$normalized])) {
                $result['rows'][] = $this->makeRow($row['row_number'], 'reject', $name, $data, [
                    ['key' => 'resources.suppliers.import.errors.duplicate_name_in_file', 'params' => [':name' => $name]],
                ]);
                $result['counts']['reject']++;

                continue;
            }

            $seen[$normalized] = true;

            $existingId = $existingIds[$normalized] ?? null;

            if ($existingId !== null) {
                $result['rows'][] = $this->makeRow($row['row_number'], 'reject', "existing id:{$existingId}", $data, [
                    ['key' => 'resources.suppliers.import.errors.possible_match', 'params' => [':name' => $name, ':id' => $existingId]],
                ]);
                $result['counts']['reject']++;

                continue;
            }

            $result['rows'][] = $this->makeRow($row['row_number'], 'create', $name, $data);
            $result['counts']['create']++;
        }

        return $result;
    }

    /**
     * Create every `create` row with only the mapped columns. Unmapped
     * columns are absent from `data` and are omitted from the payload;
     * `is_active` is never written.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{created: int, updated: int, skipped: int}
     */
    public function persist(array $rows): array
    {
        $created = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            if (($row['status'] ?? null) !== 'create') {
                $skipped++;

                continue;
            }

            $data = (array) $row['data'];

            /** @var array<string, mixed> $payload */
            $payload = ['name' => $data['name']];

            foreach (['contact_person', 'phone', 'email', 'address'] as $field) {
                if (array_key_exists($field, $data)) {
                    $payload[$field] = $data[$field];
                }
            }

            Supplier::query()->create($payload);
            $created++;
        }

        return ['created' => $created, 'updated' => 0, 'skipped' => $skipped];
    }

    /**
     * Normalized name => id for every supplier, soft-deleted included:
     * a soft-deleted name still means a human must review before a
     * namesake is created.
     *
     * @return array<string, int>
     */
    protected function existingIdsByNormalizedName(): array
    {
        /** @var array<string, int> $map */
        $map = [];

        Supplier::query()->withTrashed()->get(['id', 'name'])->each(function (Supplier $supplier) use (&$map): void {
            $map[$this->normalizeName((string) $supplier->name)] = (int) $supplier->getKey();
        });

        return $map;
    }

    /** Trim, collapse whitespace and lowercase for case-insensitive comparison. */
    protected function normalizeName(string $name): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', trim($name)) ?? trim($name));
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array{key: string, params: array<string, mixed>}>  $errors
     * @return array<string, mixed>
     */
    protected function makeRow(int $rowNumber, string $status, string $identifier, array $data, array $errors = []): array
    {
        return [
            'row_number' => $rowNumber,
            'status' => $status,
            'identifier' => $identifier,
            'errors' => $errors,
            'data' => $data,
        ];
    }
}
