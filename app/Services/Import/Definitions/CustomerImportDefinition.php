<?php

declare(strict_types=1);

namespace App\Services\Import\Definitions;

use App\Models\Customer;
use App\Models\User;
use App\Services\Import\ImportColumn;
use App\Services\Import\ImportDefinition;
use Illuminate\Support\Facades\Gate;

/**
 * CustomerImportDefinition — create-only import of customers on the
 * shared engine (issue #223).
 *
 * Mirror of `SupplierImportDefinition` (§2.15/§3.13): customers are
 * identified by `name` only (owner decision), names are not unique and
 * carry no unique validation, so this import NEVER updates or merges.
 * Rows whose normalized name already exists (including soft-deleted
 * records) are rejected with a `possible_match` reason so a human
 * reviews the flagged row before anything is written.
 */
class CustomerImportDefinition extends ImportDefinition
{
    public function key(): string
    {
        return 'customers';
    }

    public function labelKey(): string
    {
        return 'resources.customers.model.singular';
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
                labelKey: 'resources.customers.fields.name',
                required: true,
                guessKeywords: ['name', 'company'],
                sample: 'Acme Retail',
                descriptionKey: 'resources.customers.import.desc_name',
            ),
            new ImportColumn(
                key: 'contact_person',
                labelKey: 'resources.customers.fields.contact_person',
                sample: 'Jane Doe',
                descriptionKey: 'resources.customers.import.desc_contact_person',
            ),
            new ImportColumn(
                key: 'phone',
                labelKey: 'resources.customers.fields.phone',
                sample: '+1 555 0100',
                descriptionKey: 'resources.customers.import.desc_phone',
            ),
            new ImportColumn(
                key: 'email',
                labelKey: 'resources.customers.fields.email',
                sample: 'sales@acme.example',
                descriptionKey: 'resources.customers.import.desc_email',
            ),
            new ImportColumn(
                key: 'address',
                labelKey: 'resources.customers.fields.address',
                sample: '123 Main St',
                descriptionKey: 'resources.customers.import.desc_address',
            ),
        ];
    }

    public function authorize(User $user): void
    {
        Gate::forUser($user)->authorize('create', Customer::class);
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
                    ['key' => 'resources.customers.import.errors.name_required', 'params' => []],
                ]);
                $result['counts']['reject']++;

                continue;
            }

            if (isset($seen[$normalized])) {
                $result['rows'][] = $this->makeRow($row['row_number'], 'reject', $name, $data, [
                    ['key' => 'resources.customers.import.errors.duplicate_name_in_file', 'params' => [':name' => $name]],
                ]);
                $result['counts']['reject']++;

                continue;
            }

            $seen[$normalized] = true;

            $existingId = $existingIds[$normalized] ?? null;

            if ($existingId !== null) {
                $result['rows'][] = $this->makeRow($row['row_number'], 'reject', "existing id:{$existingId}", $data, [
                    ['key' => 'resources.customers.import.errors.possible_match', 'params' => [':name' => $name, ':id' => $existingId]],
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

            Customer::query()->create($payload);
            $created++;
        }

        return ['created' => $created, 'updated' => 0, 'skipped' => $skipped];
    }

    /**
     * Normalized name => id for every customer, soft-deleted included:
     * a soft-deleted name still means a human must review before a
     * namesake is created.
     *
     * @return array<string, int>
     */
    protected function existingIdsByNormalizedName(): array
    {
        /** @var array<string, int> $map */
        $map = [];

        Customer::query()->withTrashed()->get(['id', 'name'])->each(function (Customer $customer) use (&$map): void {
            $map[$this->normalizeName((string) $customer->name)] = (int) $customer->getKey();
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
