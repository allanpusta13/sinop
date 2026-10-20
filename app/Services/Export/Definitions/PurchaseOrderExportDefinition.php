<?php

declare(strict_types=1);

namespace App\Services\Export\Definitions;

use App\Models\PurchaseOrder;
use App\Models\User;
use App\Services\Export\ExportColumn;
use App\Services\Export\ExportDefinition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

/**
 * PurchaseOrderExportDefinition — "purchase_orders" (#226): purchase
 * order headers, exported as the List page scopes them (receiving
 * warehouse inside the user's assignments).
 *
 * `status` exports its backing value, `cancelled_at` is an internal
 * workflow stamp and stays out, and the actors export as names.
 */
class PurchaseOrderExportDefinition extends ExportDefinition
{
    public function key(): string
    {
        return 'purchase_orders';
    }

    public function labelKey(): string
    {
        return 'exports.types.purchase_orders.label';
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
            new ExportColumn(key: 'reference_code', labelKey: 'resources.purchase_orders.fields.reference_code'),
            new ExportColumn(key: 'status', labelKey: 'resources.purchase_orders.fields.status'),
            new ExportColumn(key: 'supplier_id', labelKey: 'exports.columns.supplier_id', cast: ExportColumn::CAST_INT),
            new ExportColumn(key: 'supplier', labelKey: 'resources.purchase_orders.fields.supplier'),
            new ExportColumn(key: 'warehouse_id', labelKey: 'exports.columns.warehouse_id', cast: ExportColumn::CAST_INT),
            new ExportColumn(key: 'receiving_warehouse', labelKey: 'resources.purchase_orders.fields.receiving_warehouse'),
            new ExportColumn(key: 'update_cost_price', labelKey: 'resources.purchase_orders.fields.update_cost_price', cast: ExportColumn::CAST_INT),
            new ExportColumn(key: 'ordered_by', labelKey: 'resources.purchase_orders.fields.ordered_by'),
            new ExportColumn(key: 'received_by', labelKey: 'resources.purchase_orders.fields.received_by'),
            new ExportColumn(key: 'ordered_at', labelKey: 'resources.purchase_orders.fields.ordered_at', cast: ExportColumn::CAST_DATETIME),
            new ExportColumn(key: 'received_at', labelKey: 'resources.purchase_orders.fields.received_at', cast: ExportColumn::CAST_DATETIME),
            new ExportColumn(key: 'notes', labelKey: 'resources.purchase_orders.fields.notes'),
            new ExportColumn(key: 'items_count', labelKey: 'resources.purchase_orders.table.items', cast: ExportColumn::CAST_INT),
        ];
    }

    public function query(Builder $tableQuery): Builder
    {
        return $tableQuery
            ->select([
                'purchase_orders.id as purchase_order_id',
                'purchase_orders.reference_code',
                'purchase_orders.status',
                'purchase_orders.supplier_id',
                's.name as supplier',
                'purchase_orders.warehouse_id',
                'w.name as receiving_warehouse',
                'purchase_orders.update_cost_price',
                'uo.name as ordered_by',
                'ur.name as received_by',
                'purchase_orders.ordered_at',
                'purchase_orders.received_at',
                'purchase_orders.notes',
            ])
            ->join('suppliers as s', 's.id', '=', 'purchase_orders.supplier_id')
            ->join('warehouses as w', 'w.id', '=', 'purchase_orders.warehouse_id')
            ->leftJoin('users as uo', 'uo.id', '=', 'purchase_orders.ordered_by')
            ->leftJoin('users as ur', 'ur.id', '=', 'purchase_orders.received_by')
            ->withCount('items');
    }

    /** One purchase order is raised against exactly one warehouse. */
    public function warehouseScope(): array
    {
        return [['purchase_orders.warehouse_id']];
    }
}
