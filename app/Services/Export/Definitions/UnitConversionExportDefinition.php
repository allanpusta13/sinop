<?php

declare(strict_types=1);

namespace App\Services\Export\Definitions;

use App\Models\ProductVariant;
use App\Models\ProductVariantUnitConversion;
use App\Models\User;
use App\Services\Export\ExportColumn;
use App\Services\Export\ExportDefinition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

/**
 * UnitConversionExportDefinition — "products.unit_conversions" (#225):
 * the unit-conversion rows of the variants the user is looking at.
 *
 * The conversions have no List page of their own, so the variant set
 * is derived from the Products table query — the export covers exactly
 * the variants in the current view.
 */
class UnitConversionExportDefinition extends ExportDefinition
{
    public function key(): string
    {
        return 'products.unit_conversions';
    }

    public function labelKey(): string
    {
        return 'exports.types.products.unit_conversions';
    }

    public function modelClass(): string
    {
        return ProductVariant::class;
    }

    public function authorize(User $user): void
    {
        Gate::forUser($user)->authorize('viewAny', ProductVariant::class);
    }

    /**
     * @return array<int, ExportColumn>
     */
    public function columns(): array
    {
        return [
            new ExportColumn(key: 'sku', labelKey: 'resources.products.fields.sku'),
            new ExportColumn(key: 'unit_name', labelKey: 'resources.products.fields.unit_name'),
            new ExportColumn(key: 'base_unit_ratio', labelKey: 'resources.products.fields.base_unit_ratio', cast: ExportColumn::CAST_FLOAT),
            new ExportColumn(key: 'is_default_purchase', labelKey: 'resources.products.fields.is_default_purchase', cast: ExportColumn::CAST_INT),
            new ExportColumn(key: 'is_default_transfer', labelKey: 'resources.products.fields.is_default_transfer', cast: ExportColumn::CAST_INT),
        ];
    }

    public function query(Builder $tableQuery): Builder
    {
        return ProductVariantUnitConversion::query()
            ->select([
                'pv.sku',
                'product_variant_unit_conversions.unit_name',
                'product_variant_unit_conversions.base_unit_ratio',
                'product_variant_unit_conversions.is_default_purchase',
                'product_variant_unit_conversions.is_default_transfer',
            ])
            ->join('product_variants as pv', 'pv.id', '=', 'product_variant_unit_conversions.product_variant_id')
            ->whereIn('product_variant_unit_conversions.product_variant_id', $this->parentIdsQuery($tableQuery));
    }
}
