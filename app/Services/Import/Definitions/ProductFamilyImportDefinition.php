<?php

declare(strict_types=1);

namespace App\Services\Import\Definitions;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Import\ImportColumn;
use App\Services\Import\ImportDefinition;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * ProductFamilyImportDefinition — "products.families" registry entry:
 * a create-only import of parent product families (name + optional
 * category). Families are matched by normalized name; an existing
 * family or a duplicate name inside the file rejects the row — the
 * import never updates or merges families by name (issue #219).
 */
class ProductFamilyImportDefinition extends ImportDefinition
{
    public function key(): string
    {
        return 'products.families';
    }

    public function labelKey(): string
    {
        return 'resources.products.import.mode_families';
    }

    /**
     * @return array<int, ImportColumn>
     */
    public function columns(): array
    {
        return [
            new ImportColumn(
                key: 'name',
                labelKey: 'resources.products.import.map_family_name',
                required: true,
                guessKeywords: ['family name', 'name', 'family'],
                sample: 'Arabica Specialty Coffee (Sample)',
                descriptionKey: 'resources.products.import.desc_family_name',
            ),
            new ImportColumn(
                key: 'category',
                labelKey: 'resources.products.import.map_family_category',
                guessKeywords: ['category', 'family category'],
                sample: 'Beverages',
                descriptionKey: 'resources.products.import.desc_family_category',
            ),
        ];
    }

    /**
     * Catalog create authority, identical to the action's original
     * gate: a user may import families when they may create a
     * ProductVariant or a Product.
     */
    public function authorize(User $user): void
    {
        if (! $user->can('create', ProductVariant::class) && ! $user->can('create', Product::class)) {
            throw new AuthorizationException;
        }
    }

    /**
     * Classify family rows — create-only, matched by normalized name.
     *
     * @param  array<int, array{row_number: int, data: array<string, mixed>}>  $rows
     * @return array{rows: array<int, array<string, mixed>>, counts: array{create: int, update: int, reject: int, total: int}}
     */
    public function validate(array $rows): array
    {
        $nameCounts = [];

        foreach ($rows as $row) {
            $name = trim((string) ($row['data']['name'] ?? ''));

            if ($name !== '') {
                $lower = mb_strtolower($name);
                $nameCounts[$lower] = ($nameCounts[$lower] ?? 0) + 1;
            }
        }

        $existingNames = [];

        foreach (Product::withTrashed()->get() as $family) {
            $existingNames[mb_strtolower(trim($family->name))] = true;
        }

        $result = [
            'rows' => [],
            'counts' => ['create' => 0, 'update' => 0, 'reject' => 0, 'total' => 0],
        ];

        foreach ($rows as $row) {
            $name = trim((string) ($row['data']['name'] ?? ''));
            $categoryRaw = $row['data']['category'] ?? null;
            $category = filled($categoryRaw) ? trim((string) $categoryRaw) : null;
            $lower = mb_strtolower($name);

            $errors = [];

            if ($name === '') {
                $errors[] = $this->rowError('resources.products.import.errors.family_name_required');
            } else {
                if (($nameCounts[$lower] ?? 0) > 1) {
                    $errors[] = $this->rowError('resources.products.import.errors.family_duplicate_in_file', [':name' => $name]);
                }

                if (isset($existingNames[$lower])) {
                    $errors[] = $this->rowError('resources.products.import.errors.family_name_exists', [':name' => $name]);
                }
            }

            $status = $errors === [] ? 'create' : 'reject';

            $result['counts'][$status]++;
            $result['counts']['total']++;

            $result['rows'][] = [
                'row_number' => $row['row_number'],
                'status' => $status,
                'identifier' => $name !== '' ? $name : "[Row {$row['row_number']}]",
                'errors' => $errors,
                'data' => [
                    'name' => $name,
                    'category' => $category,
                ],
            ];
        }

        return $result;
    }

    /**
     * Create the accepted family rows. Reject rows never reach this
     * step — the engine filters them before calling persist().
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
            if (($row['status'] ?? null) !== 'create') {
                $skipped++;

                continue;
            }

            $data = $row['data'];

            Product::query()->create([
                'name' => $data['name'],
                'category' => $data['category'] ?? null,
            ]);

            $created++;
        }

        return ['created' => $created, 'updated' => $updated, 'skipped' => $skipped];
    }

    /**
     * Build one translation-keyed row error for the preview blade.
     *
     * @param  array<string, mixed>  $params
     * @return array{key: string, params: array<string, mixed>}
     */
    protected function rowError(string $key, array $params = []): array
    {
        return ['key' => $key, 'params' => $params];
    }
}
