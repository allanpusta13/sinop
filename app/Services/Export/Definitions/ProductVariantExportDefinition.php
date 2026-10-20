<?php

declare(strict_types=1);

namespace App\Services\Export\Definitions;

use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Export\ExportColumn;
use App\Services\Export\ExportDefinition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

/**
 * ProductVariantExportDefinition — "products.variants" (#225): the
 * Products List page's own rows, exported as they appear on screen.
 *
 * The family name comes through an inner join on the parent Product;
 * variant `images` and the migration-orphan `Product.is_active` are
 * never selected.
 */
class ProductVariantExportDefinition extends ExportDefinition
{
    public function key(): string
    {
        return 'products.variants';
    }

    public function labelKey(): string
    {
        return 'exports.types.products.variants';
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
            new ExportColumn(key: 'sku', labelKey: 'resources.products.table.sku'),
            new ExportColumn(key: 'name', labelKey: 'resources.products.table.name'),
            new ExportColumn(key: 'family_name', labelKey: 'resources.products.table.family'),
            new ExportColumn(key: 'base_unit_name', labelKey: 'resources.products.table.base_unit'),
            new ExportColumn(key: 'reorder_point', labelKey: 'resources.products.table.reorder_point', cast: ExportColumn::CAST_INT),
            new ExportColumn(key: 'is_active', labelKey: 'resources.products.table.status', cast: ExportColumn::CAST_INT),
            new ExportColumn(key: 'barcode', labelKey: 'resources.products.fields.barcode'),
            new ExportColumn(key: 'attributes', labelKey: 'resources.products.fields.attributes'),
        ];
    }

    public function query(Builder $tableQuery): Builder
    {
        return $tableQuery
            ->select([
                'product_variants.sku',
                'product_variants.name',
                'pf.name as family_name',
                'product_variants.base_unit_name',
                'product_variants.reorder_point',
                'product_variants.is_active',
                'product_variants.barcode',
                'product_variants.attributes',
            ])
            ->join('products as pf', 'pf.id', '=', 'product_variants.product_id');
    }
}
