<?php

declare(strict_types=1);

namespace App\Services\Export\Definitions;

use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use App\Models\User;
use App\Services\Export\ExportColumn;
use App\Services\Export\ExportDefinition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

/**
 * CurrentPriceExportDefinition — "products.current_prices" (#225): the
 * live cost/sale price of the variants the user is looking at.
 *
 * Only rows flagged `is_current` are exported. Historical price rows
 * and their audit columns (`set_by`, `notes`, the `is_current` flag
 * itself) stay out of the file — they are ledger history, not the
 * current price list.
 */
class CurrentPriceExportDefinition extends ExportDefinition
{
    public function key(): string
    {
        return 'products.current_prices';
    }

    public function labelKey(): string
    {
        return 'exports.types.products.current_prices';
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
            new ExportColumn(key: 'cost_price', labelKey: 'resources.products.fields.cost_price', cast: ExportColumn::CAST_FLOAT),
            new ExportColumn(key: 'sale_price', labelKey: 'resources.products.fields.sale_price', cast: ExportColumn::CAST_FLOAT),
            new ExportColumn(key: 'effective_from', labelKey: 'exports.columns.effective_from'),
        ];
    }

    public function query(Builder $tableQuery): Builder
    {
        return ProductVariantPrice::query()
            ->select([
                'pv.sku',
                'product_variant_prices.cost_price',
                'product_variant_prices.sale_price',
                'product_variant_prices.effective_from',
            ])
            ->join('product_variants as pv', 'pv.id', '=', 'product_variant_prices.product_variant_id')
            ->where('product_variant_prices.is_current', true)
            ->whereIn('product_variant_prices.product_variant_id', $this->parentIdsQuery($tableQuery));
    }
}
