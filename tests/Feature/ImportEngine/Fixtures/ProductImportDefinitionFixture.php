<?php

declare(strict_types=1);

namespace Tests\Feature\ImportEngine\Fixtures;

use App\Models\Product;
use App\Models\User;
use App\Services\Import\ImportColumn;
use App\Services\Import\ImportDefinition;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use PDOException;

/**
 * Engine fixture: a minimal definition over products (name + category)
 * used to exercise the shared import engine end to end without
 * depending on any Wave 1 feature definition. Rejection reasons are
 * plain strings on purpose — production definitions use translation
 * keys, but the preview blade accepts both.
 */
class ProductImportDefinitionFixture extends ImportDefinition
{
    public const KEY = 'fixture.products';

    /**
     * Test hook: when true, persist() throws a MySQL-style duplicate-key
     * QueryException, as a concurrent writer would.
     */
    public static bool $failPersistWithDuplicateKey = false;

    /**
     * Test hook: when true, persist() reports a skipped row after
     * writing — an update target that vanished between the confirmation
     * and the write.
     */
    public static bool $failPersistWithSkip = false;
    public static ?\Illuminate\Database\QueryException $customPersistException = null;

    public function key(): string
    {
        return self::KEY;
    }

    public function labelKey(): string
    {
        return 'resources.products.actions.import';
    }

    /** @return array<int, ImportColumn> */
    public function columns(): array
    {
        return [
            new ImportColumn(
                key: 'name',
                labelKey: 'resources.products.import.map_family_name',
                required: true,
                guessKeywords: ['name', 'product name'],
                sample: 'Sample Product (Sample)',
                descriptionKey: 'resources.products.import.desc_name',
            ),
            new ImportColumn(
                key: 'category',
                labelKey: 'resources.products.import.map_family_category',
                guessKeywords: ['category'],
                sample: 'Beverages',
            ),
        ];
    }

    public function authorize(User $user): void
    {
        Gate::authorize('create', Product::class);
    }

    public function validate(array $rows): array
    {
        $result = [
            'rows' => [],
            'counts' => ['create' => 0, 'update' => 0, 'reject' => 0, 'total' => count($rows)],
        ];

        $seen = [];

        foreach ($rows as $row) {
            $name = trim((string) ($row['data']['name'] ?? ''));

            if ($name === '') {
                $result['rows'][] = $this->makeRow($row['row_number'], 'reject', '-', ['Name is required.'], $row['data']);
                $result['counts']['reject']++;

                continue;
            }

            if (isset($seen[mb_strtolower($name)])) {
                $result['rows'][] = $this->makeRow($row['row_number'], 'reject', $name, ["Duplicate name [{$name}] in uploaded file."], $row['data']);
                $result['counts']['reject']++;

                continue;
            }

            $seen[mb_strtolower($name)] = true;

            $existing = Product::query()->where('name', $name)->first();

            if ($existing !== null) {
                $result['rows'][] = $this->makeRow($row['row_number'], 'update', "id:{$existing->getKey()} | {$name}", [], $row['data'], $existing->getKey());
                $result['counts']['update']++;

                continue;
            }

            $result['rows'][] = $this->makeRow($row['row_number'], 'create', $name, [], $row['data']);
            $result['counts']['create']++;
        }

        return $result;
    }

    public function persist(array $rows): array
    {
        if (self::$customPersistException !== null) {
            throw self::$customPersistException;
        }

        if (self::$failPersistWithDuplicateKey) {
            $previous = new PDOException("SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'Robusta' for key 'products_name_unique'");
            $previous->errorInfo = ['23000', 1062, "Duplicate entry 'Robusta' for key 'products_name_unique'"];

            throw new QueryException('mysql', 'insert into `products` (`name`) values (?)', ['Robusta'], $previous);
        }

        $created = 0;
        $updated = 0;

        foreach ($rows as $row) {
            if ($row['status'] === 'create') {
                Product::query()->create([
                    'name' => $row['data']['name'],
                    'category' => $row['data']['category'] ?? null,
                ]);
                $created++;

                continue;
            }

            $product = Product::query()->find($row['existing_id'] ?? null);

            if ($product !== null && array_key_exists('category', $row['data']) && filled($row['data']['category'])) {
                $product->update(['category' => $row['data']['category']]);
            }

            $updated++;
        }

        if (self::$failPersistWithSkip) {
            return ['created' => $created, 'updated' => $updated, 'skipped' => 1];
        }

        return ['created' => $created, 'updated' => $updated, 'skipped' => 0];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function makeRow(int $number, string $status, string $identifier, array $errors, array $data, ?int $existingId = null): array
    {
        return [
            'row_number' => $number,
            'status' => $status,
            'identifier' => $identifier,
            'errors' => $errors,
            'data' => $data,
            'existing_id' => $existingId,
        ];
    }
}
