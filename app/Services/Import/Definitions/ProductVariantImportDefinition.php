<?php

declare(strict_types=1);

namespace App\Services\Import\Definitions;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Import\ImportColumn;
use App\Services\Import\ImportDefinition;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

/**
 * ProductVariantImportDefinition — "products.variants" registry entry:
 * SKU-bearing variants resolved against existing product families by
 * Family ID or Family Name. Existing variants match by SKU and update
 * only after explicit confirmation; new SKUs create. Row classification
 * enforces family resolution, required cells, in-file SKU and barcode
 * rules, and reorder-point/attributes validation with translation-keyed
 * errors (issue #219).
 */
class ProductVariantImportDefinition extends ImportDefinition
{
    public function key(): string
    {
        return 'products.variants';
    }

    public function labelKey(): string
    {
        return 'resources.products.import.mode_variants';
    }

    /**
     * @return array<int, ImportColumn>
     */
    public function columns(): array
    {
        return [
            new ImportColumn(
                key: 'family_id',
                labelKey: 'resources.products.import.map_family_id',
                guessKeywords: ['family id', 'product_id', 'product id'],
                sample: '',
                descriptionKey: 'resources.products.import.desc_family_id',
            ),
            new ImportColumn(
                key: 'family_name',
                labelKey: 'resources.products.import.map_family_name',
                guessKeywords: ['family name', 'product family'],
                sample: 'Arabica Specialty Coffee',
                descriptionKey: 'resources.products.import.desc_family_name',
            ),
            new ImportColumn(
                key: 'sku',
                labelKey: 'resources.products.import.map_sku',
                required: true,
                guessKeywords: ['sku', 'product code', 'item code'],
                sample: 'SKU-0001-AA (Sample)',
                descriptionKey: 'resources.products.import.desc_sku',
            ),
            new ImportColumn(
                key: 'name',
                labelKey: 'resources.products.import.map_variant_name',
                required: true,
                guessKeywords: ['variant name', 'product name'],
                sample: 'Arabica Medium Roast 250g',
                descriptionKey: 'resources.products.import.desc_variant_name',
            ),
            new ImportColumn(
                key: 'base_unit_name',
                labelKey: 'resources.products.import.map_base_unit',
                required: true,
                guessKeywords: ['base unit name', 'base unit', 'unit'],
                sample: 'Bag',
                descriptionKey: 'resources.products.import.desc_base_unit',
            ),
            new ImportColumn(
                key: 'barcode',
                labelKey: 'resources.products.import.map_barcode',
                guessKeywords: ['barcode', 'ean', 'upc'],
                sample: '1234567890123',
                descriptionKey: 'resources.products.import.desc_barcode',
            ),
            new ImportColumn(
                key: 'reorder_point',
                labelKey: 'resources.products.import.map_reorder_point',
                guessKeywords: ['reorder point', 'reorder'],
                sample: '10',
                descriptionKey: 'resources.products.import.desc_reorder_point',
            ),
            new ImportColumn(
                key: 'attributes',
                labelKey: 'resources.products.import.map_attributes',
                guessKeywords: ['attributes'],
                sample: '{"Roast":"Medium"}',
                descriptionKey: 'resources.products.import.desc_attributes',
            ),
            new ImportColumn(
                key: 'is_active',
                labelKey: 'resources.products.import.map_is_active',
                guessKeywords: ['active', 'is_active', 'status'],
                sample: '1',
                descriptionKey: 'resources.products.import.desc_is_active',
            ),
        ];
    }

    /**
     * Catalog create authority, identical to the action's original
     * gate: a user may import variants when they may create a
     * ProductVariant or a Product.
     */
    public function authorize(User $user): void
    {
        if (! $user->can('create', ProductVariant::class) && ! $user->can('create', Product::class)) {
            throw new AuthorizationException;
        }
    }

    /**
     * Required-column gate plus the "at least one of Family ID /
     * Family Name" mapping requirement.
     *
     * @param  array<string, string|int|null>  $columnMap
     */
    public function assertMappingsSatisfied(array $columnMap): void
    {
        parent::assertMappingsSatisfied($columnMap);

        if (blank($columnMap['family_id'] ?? null) && blank($columnMap['family_name'] ?? null)) {
            throw ValidationException::withMessages([
                'columnMap.family_id' => __('resources.products.import.errors.family_identifier_mapping_required'),
            ]);
        }
    }

    /**
     * Classify variant rows: family resolution, required fields,
     * in-file duplicates, barcode conflicts and SKU matching.
     *
     * @param  array<int, array{row_number: int, data: array<string, mixed>}>  $rows
     * @return array{rows: array<int, array<string, mixed>>, counts: array{create: int, update: int, reject: int, total: int}}
     */
    public function validate(array $rows): array
    {
        $skuCounts = [];
        $barcodeSkuSets = [];

        foreach ($rows as $row) {
            $data = $row['data'];
            $sku = strtoupper(trim((string) ($data['sku'] ?? '')));

            if ($sku !== '') {
                $skuCounts[$sku] = ($skuCounts[$sku] ?? 0) + 1;
            }

            $barcode = trim((string) ($data['barcode'] ?? ''));

            if ($barcode !== '') {
                $barcodeSkuSets[$barcode][$sku] = true;
            }
        }

        $allFamilies = Product::withTrashed()->get();
        $familiesById = $allFamilies->keyBy('id')->all();
        $familiesByName = [];

        foreach ($allFamilies as $family) {
            $familiesByName[mb_strtolower(trim($family->name))][] = $family;
        }

        $existingVariants = ProductVariant::withTrashed()->get();
        $variantsBySku = $existingVariants->keyBy(
            static fn (ProductVariant $variant): string => strtoupper(trim((string) $variant->sku))
        );
        $variantsByBarcode = [];

        foreach ($existingVariants as $variant) {
            $storedBarcode = $variant->barcode;

            if (filled($storedBarcode)) {
                $variantsByBarcode[trim((string) $storedBarcode)] = $variant;
            }
        }

        $result = [
            'rows' => [],
            'counts' => ['create' => 0, 'update' => 0, 'reject' => 0, 'total' => 0],
        ];

        foreach ($rows as $row) {
            $data = $row['data'];
            $sku = strtoupper(trim((string) ($data['sku'] ?? '')));
            $name = trim((string) ($data['name'] ?? ''));
            $baseUnit = trim((string) ($data['base_unit_name'] ?? ''));
            $barcode = trim((string) ($data['barcode'] ?? ''));
            $familyIdRaw = trim((string) ($data['family_id'] ?? ''));
            $familyNameRaw = trim((string) ($data['family_name'] ?? ''));

            [$resolvedFamilyId, $familyErrors] = $this->resolveFamilyId($familyIdRaw, $familyNameRaw, $familiesById, $familiesByName);
            $errors = $familyErrors;

            if ($sku === '') {
                $errors[] = $this->rowError('resources.products.import.errors.sku_required');
            }

            if ($name === '') {
                $errors[] = $this->rowError('resources.products.import.errors.variant_name_required');
            }

            if ($baseUnit === '') {
                $errors[] = $this->rowError('resources.products.import.errors.base_unit_required');
            }

            if ($sku !== '' && ($skuCounts[$sku] ?? 0) > 1) {
                $errors[] = $this->rowError('resources.products.import.errors.sku_duplicate_in_file', ['sku' => $sku]);
            }

            $errors = [...$errors, ...$this->barcodeErrors($barcode, $sku, $barcodeSkuSets, $variantsByBarcode)];

            $reorderPoint = 0;
            $reorderRaw = trim((string) ($data['reorder_point'] ?? ''));

            if ($reorderRaw !== '') {
                if (preg_match('/^\d+$/', $reorderRaw) !== 1) {
                    $errors[] = $this->rowError('resources.products.import.errors.reorder_point_invalid');
                } else {
                    $reorderPoint = (int) $reorderRaw;
                }
            }

            $attributes = null;
            $attributesRaw = $data['attributes'] ?? null;

            if (is_string($attributesRaw) && $attributesRaw !== '') {
                $decoded = json_decode($attributesRaw, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $errors[] = $this->rowError('resources.products.import.errors.attributes_invalid_json');
                } elseif (is_array($decoded)) {
                    $attributes = $decoded;
                }
            }

            $isActive = true;
            $isActiveRaw = trim((string) ($data['is_active'] ?? ''));

            if ($isActiveRaw !== '') {
                $filtered = filter_var($isActiveRaw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                $isActive = $filtered ?? true;
            }

            $existingVariantId = null;

            if ($errors === []) {
                $existing = $variantsBySku[$sku] ?? null;

                if ($existing !== null) {
                    $status = 'update';
                    $existingVariantId = $existing->id;
                } else {
                    $status = 'create';
                }
            } else {
                $status = 'reject';
            }

            $payload = $this->payloadFor($data, $sku, $name, $baseUnit, $barcode, $reorderPoint, $attributes, $isActive);

            $result['counts'][$status]++;
            $result['counts']['total']++;

            $result['rows'][] = [
                'row_number' => $row['row_number'],
                'status' => $status,
                'identifier' => $this->identifierFor($sku, $status, $existingVariantId, $row['row_number']),
                'errors' => $errors,
                'data' => $payload,
                'resolved_family_id' => $resolvedFamilyId,
                'existing_id' => $existingVariantId,
            ];
        }

        return $result;
    }

    /**
     * Create or update the accepted variant rows. Reject rows never
     * reach this step — the engine filters them before calling
     * persist(). Only Product and ProductVariant rows are written.
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
            $status = $row['status'] ?? null;
            $data = $row['data'];

            if ($status === 'create') {
                ProductVariant::query()->create([
                    'product_id' => $row['resolved_family_id'],
                    'sku' => $data['sku'],
                    'barcode' => $data['barcode'] ?? null,
                    'name' => $data['name'],
                    'base_unit_name' => $data['base_unit_name'],
                    'reorder_point' => (int) ($data['reorder_point'] ?? 0),
                    'attributes' => $data['attributes'] ?? null,
                    'is_active' => (bool) ($data['is_active'] ?? true),
                ]);

                $created++;

                continue;
            }

            if ($status === 'update') {
                $variant = ProductVariant::withTrashed()->find($row['existing_id'] ?? null);

                if ($variant === null) {
                    $skipped++;

                    continue;
                }

                $attributes = ['product_id' => $row['resolved_family_id']];

                foreach (['barcode', 'name', 'base_unit_name', 'reorder_point', 'attributes', 'is_active'] as $field) {
                    if (array_key_exists($field, $data)) {
                        $attributes[$field] = $data[$field];
                    }
                }

                $variant->update($attributes);

                $updated++;

                continue;
            }

            $skipped++;
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

    /**
     * Preview identifier: "SKU" for creates/rejects, "SKU | id" for
     * updates, "[Row N]" when the row carries no SKU.
     */
    protected function identifierFor(string $sku, string $status, ?int $existingVariantId, int $rowNumber): string
    {
        if ($sku === '') {
            return "[Row {$rowNumber}]";
        }

        if ($status === 'update' && $existingVariantId !== null) {
            return "{$sku} | {$existingVariantId}";
        }

        return $sku;
    }

    /**
     * Resolve the family reference for a row: the family id when
     * exactly one family matches, otherwise the accumulated errors.
     *
     * @param  array<string, Product>  $familiesById
     * @param  array<string, array<int, Product>>  $familiesByName
     * @return array{0: int|string|null, 1: array<int, array{key: string, params: array<string, mixed>}>}
     */
    protected function resolveFamilyId(string $familyIdRaw, string $familyNameRaw, array $familiesById, array $familiesByName): array
    {
        if ($familyIdRaw !== '') {
            if (! ctype_digit($familyIdRaw) || ! isset($familiesById[(int) $familyIdRaw])) {
                return [null, [$this->rowError('resources.products.import.errors.family_id_not_found', ['family_id' => $familyIdRaw])]];
            }

            return [(int) $familyIdRaw, []];
        }

        if ($familyNameRaw === '') {
            return [null, [$this->rowError('resources.products.import.errors.family_reference_missing')]];
        }

        $matches = $familiesByName[mb_strtolower($familyNameRaw)] ?? [];

        if ($matches === []) {
            return [null, [$this->rowError('resources.products.import.errors.family_name_no_match', ['name' => $familyNameRaw])]];
        }

        if (count($matches) > 1) {
            return [null, [$this->rowError('resources.products.import.errors.family_name_ambiguous', [
                'name' => $familyNameRaw,
                'count' => count($matches),
            ])]];
        }

        return [$matches[0]->id, []];
    }

    /**
     * Barcode rule errors: a barcode carried by more than one SKU in
     * the file, or already owned by a different existing SKU.
     *
     * @param  array<string, array<string, true>>  $barcodeSkuSets
     * @param  array<string, ProductVariant>  $variantsByBarcode
     * @return array<int, array{key: string, params: array<string, mixed>}>
     */
    protected function barcodeErrors(string $barcode, string $sku, array $barcodeSkuSets, array $variantsByBarcode): array
    {
        if ($barcode === '') {
            return [];
        }

        $errors = [];

        if (isset($barcodeSkuSets[$barcode]) && count($barcodeSkuSets[$barcode]) > 1) {
            $errors[] = $this->rowError('resources.products.import.errors.barcode_multiple_in_file', ['barcode' => $barcode]);
        }

        $conflicting = $variantsByBarcode[$barcode] ?? null;

        if ($conflicting !== null && strtoupper(trim((string) $conflicting->sku)) !== $sku) {
            $errors[] = $this->rowError('resources.products.import.errors.barcode_conflict', [
                'barcode' => $barcode,
                'sku' => (string) $conflicting->sku,
            ]);
        }

        return $errors;
    }

    /**
     * Build the persist payload for a row. Only mapped columns carry
     * normalized values into the payload — an unmapped column stays
     * absent, so persist() can tell "leave alone" from an explicit
     * blank value.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>|null  $attributes
     * @return array<string, mixed>
     */
    protected function payloadFor(array $data, string $sku, string $name, string $baseUnit, string $barcode, int $reorderPoint, ?array $attributes, bool $isActive): array
    {
        $payload = [
            'sku' => $sku,
            'name' => $name,
            'base_unit_name' => $baseUnit,
        ];

        if (array_key_exists('barcode', $data)) {
            $payload['barcode'] = filled($barcode) ? $barcode : null;
        }

        if (array_key_exists('reorder_point', $data)) {
            $payload['reorder_point'] = $reorderPoint;
        }

        if (array_key_exists('attributes', $data)) {
            $payload['attributes'] = $attributes;
        }

        if (array_key_exists('is_active', $data)) {
            $payload['is_active'] = $isActive;
        }

        return $payload;
    }
}
