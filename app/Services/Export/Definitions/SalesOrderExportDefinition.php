<?php

declare(strict_types=1);

namespace App\Services\Export\Definitions;

use App\Models\SalesOrder;
use App\Services\Export\ExportColumn;
use App\Services\Export\ExportDefinition;
use Illuminate\Database\Eloquent\Builder;

/**
 * SalesOrderExportDefinition — "sales_orders" (#226): sales order
 * headers, exported as the List page scopes them (dispatching
 * warehouse inside the user's assignments).
 *
 * `status` exports its backing value; `ordered_at` and `cancelled_at`
 * are internal workflow stamps and stay out, with `ordered_by` and
 * `confirmed_at` carrying the same story. Actors export as names.
 */
class SalesOrderExportDefinition extends ExportDefinition
{
    public function key(): string
    {
        return 'sales_orders';
    }

    public function labelKey(): string
    {
        return 'exports.types.sales_orders.label';
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
            new ExportColumn(key: 'reference_code', labelKey: 'resources.sales_orders.fields.reference_code'),
            new ExportColumn(key: 'status', labelKey: 'resources.sales_orders.fields.status'),
            new ExportColumn(key: 'customer_id', labelKey: 'exports.columns.customer_id', cast: ExportColumn::CAST_INT),
            new ExportColumn(key: 'customer', labelKey: 'resources.sales_orders.fields.customer'),
            new ExportColumn(key: 'warehouse_id', labelKey: 'exports.columns.warehouse_id', cast: ExportColumn::CAST_INT),
            new ExportColumn(key: 'dispatching_warehouse', labelKey: 'resources.sales_orders.fields.dispatching_warehouse'),
            new ExportColumn(key: 'ordered_by', labelKey: 'resources.sales_orders.fields.ordered_by'),
            new ExportColumn(key: 'dispatched_by', labelKey: 'resources.sales_orders.fields.dispatched_by'),
            new ExportColumn(key: 'confirmed_at', labelKey: 'resources.sales_orders.fields.confirmed_at', cast: ExportColumn::CAST_DATETIME),
            new ExportColumn(key: 'dispatched_at', labelKey: 'resources.sales_orders.fields.dispatched_at', cast: ExportColumn::CAST_DATETIME),
            new ExportColumn(key: 'notes', labelKey: 'resources.sales_orders.fields.notes'),
            new ExportColumn(key: 'items_count', labelKey: 'resources.sales_orders.table.items', cast: ExportColumn::CAST_INT),
        ];
    }

    public function query(Builder $tableQuery): Builder
    {
        return $tableQuery
            ->select([
                'sales_orders.id as sales_order_id',
                'sales_orders.reference_code',
                'sales_orders.status',
                'sales_orders.customer_id',
                'c.name as customer',
                'sales_orders.warehouse_id',
                'w.name as dispatching_warehouse',
                'uo.name as ordered_by',
                'ud.name as dispatched_by',
                'sales_orders.confirmed_at',
                'sales_orders.dispatched_at',
                'sales_orders.notes',
            ])
            ->join('customers as c', 'c.id', '=', 'sales_orders.customer_id')
            ->join('warehouses as w', 'w.id', '=', 'sales_orders.warehouse_id')
            ->leftJoin('users as uo', 'uo.id', '=', 'sales_orders.ordered_by')
            ->leftJoin('users as ud', 'ud.id', '=', 'sales_orders.dispatched_by')
            ->withCount('items');
    }

    /** One sales order is raised against exactly one warehouse. */
    public function warehouseScope(): array
    {
        return [['sales_orders.warehouse_id']];
    }
}
