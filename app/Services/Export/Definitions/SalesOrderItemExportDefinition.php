<?php

declare(strict_types=1);

namespace App\Services\Export\Definitions;

use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Services\Export\ExportColumn;
use App\Services\Export\ExportDefinition;
use Illuminate\Database\Eloquent\Builder;

/**
 * SalesOrderItemExportDefinition — "sales_orders.items" (#226): the
 * line items of the sales orders the user is looking at.
 *
 * The unit sale price is the snapshot stored on the line, never a live
 * price lookup, so the file keeps the value the order was placed at.
 */
class SalesOrderItemExportDefinition extends ExportDefinition
{
    public function key(): string
    {
        return 'sales_orders.items';
    }

    public function labelKey(): string
    {
        return 'exports.types.sales_orders.items';
    }

    public function modelClass(): string
    {
        return SalesOrder::class;
    }

    /**
     * @return array<int, ExportColumn>
     */
    public function columns(): array
    {
        return [
            new ExportColumn(key: 'sales_order_id', labelKey: 'exports.columns.sales_order_id', cast: ExportColumn::CAST_INT),
            new ExportColumn(key: 'sales_order_reference_code', labelKey: 'resources.sales_orders.fields.reference_code'),
            new ExportColumn(key: 'product_variant_id', labelKey: 'exports.columns.product_variant_id', cast: ExportColumn::CAST_INT),
            new ExportColumn(key: 'sku', labelKey: 'resources.sales_orders.fields.sku'),
            new ExportColumn(key: 'product_name', labelKey: 'resources.sales_orders.fields.product'),
            new ExportColumn(key: 'base_qty', labelKey: 'resources.sales_orders.fields.ordered_base', cast: ExportColumn::CAST_FLOAT),
            new ExportColumn(key: 'dispatched_base_qty', labelKey: 'resources.sales_orders.fields.dispatched_base', cast: ExportColumn::CAST_FLOAT),
            new ExportColumn(key: 'unit_sale_price_snapshot', labelKey: 'resources.sales_orders.fields.snapshot_price', cast: ExportColumn::CAST_FLOAT),
            new ExportColumn(key: 'sales_order_item_id', labelKey: 'exports.columns.item_id', cast: ExportColumn::CAST_INT),
        ];
    }

    public function query(Builder $tableQuery): Builder
    {
        return SalesOrderItem::query()
            ->select([
                'sales_order_items.sales_order_id',
                'so.reference_code as sales_order_reference_code',
                'sales_order_items.product_variant_id',
                'pv.sku',
                'pv.name as product_name',
                'sales_order_items.base_qty',
                'sales_order_items.dispatched_base_qty',
                'sales_order_items.unit_sale_price_snapshot',
                'sales_order_items.id as sales_order_item_id',
            ])
            ->join('sales_orders as so', 'so.id', '=', 'sales_order_items.sales_order_id')
            ->join('product_variants as pv', 'pv.id', '=', 'sales_order_items.product_variant_id')
            ->whereIn('sales_order_items.sales_order_id', $this->parentIdsQuery($tableQuery));
    }

    /** An item inherits the one warehouse of its sales order. */
    public function warehouseScope(): array
    {
        return [['so.warehouse_id']];
    }
}
