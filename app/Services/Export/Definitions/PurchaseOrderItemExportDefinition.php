<?php

declare(strict_types=1);

namespace App\Services\Export\Definitions;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\User;
use App\Services\Export\ExportColumn;
use App\Services\Export\ExportDefinition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

/**
 * PurchaseOrderItemExportDefinition — "purchase_orders.items" (#226):
 * the line items of the purchase orders the user is looking at.
 *
 * Scope is the purchase orders table query narrowed to its ids, so
 * filtering the purchase orders page filters the exported items with
 * it.
 */
class PurchaseOrderItemExportDefinition extends ExportDefinition
{
    public function key(): string
    {
        return 'purchase_orders.items';
    }

    public function labelKey(): string
    {
        return 'exports.types.purchase_orders.items';
    }

    public function modelClass(): string
    {
        return PurchaseOrder::class;
    }

    public function authorize(User $user): void
    {
        Gate::forUser($user)->authorize('viewAny', PurchaseOrder::class);
    }

    /**
     * @return array<int, ExportColumn>
     */
    public function columns(): array
    {
        return [
            new ExportColumn(key: 'purchase_order_id', labelKey: 'exports.columns.purchase_order_id', cast: ExportColumn::CAST_INT),
            new ExportColumn(key: 'purchase_order_reference_code', labelKey: 'resources.purchase_orders.fields.reference_code'),
            new ExportColumn(key: 'product_variant_id', labelKey: 'exports.columns.product_variant_id', cast: ExportColumn::CAST_INT),
            new ExportColumn(key: 'sku', labelKey: 'resources.purchase_orders.fields.sku'),
            new ExportColumn(key: 'product_name', labelKey: 'resources.purchase_orders.fields.product'),
            new ExportColumn(key: 'ordered_base_qty', labelKey: 'resources.purchase_orders.fields.ordered_base', cast: ExportColumn::CAST_FLOAT),
            new ExportColumn(key: 'received_base_qty', labelKey: 'resources.purchase_orders.fields.received_base', cast: ExportColumn::CAST_FLOAT),
            new ExportColumn(key: 'unit_cost_price', labelKey: 'resources.purchase_orders.fields.unit_cost', cast: ExportColumn::CAST_FLOAT),
        ];
    }

    public function query(Builder $tableQuery): Builder
    {
        return PurchaseOrderItem::query()
            ->select([
                'purchase_order_items.purchase_order_id',
                'po.reference_code as purchase_order_reference_code',
                'purchase_order_items.product_variant_id',
                'pv.sku',
                'pv.name as product_name',
                'purchase_order_items.ordered_base_qty',
                'purchase_order_items.received_base_qty',
                'purchase_order_items.unit_cost_price',
            ])
            ->join('purchase_orders as po', 'po.id', '=', 'purchase_order_items.purchase_order_id')
            ->join('product_variants as pv', 'pv.id', '=', 'purchase_order_items.product_variant_id')
            ->whereIn('purchase_order_items.purchase_order_id', $this->parentIdsQuery($tableQuery));
    }
}
