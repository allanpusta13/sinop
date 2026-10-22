<?php

declare(strict_types=1);

namespace App\Services\Export\Definitions;

use App\Models\TransferRequisition;
use App\Services\Export\ExportColumn;
use App\Services\Export\ExportDefinition;
use Illuminate\Database\Eloquent\Builder;

/**
 * TransferRequisitionExportDefinition — "transfer_requisitions"
 * (#226): requisition headers, exported as the List page scopes them
 * (from- or to-warehouse inside the user's assignments).
 *
 * Both warehouse ids and names travel with the row, and the actors
 * export as names rather than user ids. `status` exports its backing
 * value; `approved_at`/`dispatched_at`/`completed_at` are internal
 * workflow stamps and stay out — `approved_by`/`dispatched_by` carry
 * the same story without them.
 */
class TransferRequisitionExportDefinition extends ExportDefinition
{
    public function key(): string
    {
        return 'transfer_requisitions';
    }

    public function labelKey(): string
    {
        return 'exports.types.transfer_requisitions.label';
    }

    public function modelClass(): string
    {
        return TransferRequisition::class;
    }

    /**
     * @return array<int, ExportColumn>
     */
    public function columns(): array
    {
        return [
            new ExportColumn(key: 'transfer_requisition_id', labelKey: 'exports.columns.transfer_requisition_id', cast: ExportColumn::CAST_INT),
            new ExportColumn(key: 'reference_code', labelKey: 'resources.transfer_requisitions.fields.reference_code'),
            new ExportColumn(key: 'status', labelKey: 'resources.transfer_requisitions.fields.status'),
            new ExportColumn(key: 'from_warehouse_id', labelKey: 'exports.columns.from_warehouse_id', cast: ExportColumn::CAST_INT),
            new ExportColumn(key: 'from_warehouse', labelKey: 'resources.transfer_requisitions.fields.from_warehouse'),
            new ExportColumn(key: 'to_warehouse_id', labelKey: 'exports.columns.to_warehouse_id', cast: ExportColumn::CAST_INT),
            new ExportColumn(key: 'to_warehouse', labelKey: 'resources.transfer_requisitions.fields.to_warehouse'),
            new ExportColumn(key: 'requested_by', labelKey: 'resources.transfer_requisitions.fields.requested_by'),
            new ExportColumn(key: 'requested_at', labelKey: 'resources.transfer_requisitions.table.requested', cast: ExportColumn::CAST_DATETIME),
            new ExportColumn(key: 'approved_by', labelKey: 'resources.transfer_requisitions.fields.approved_by'),
            new ExportColumn(key: 'dispatched_by', labelKey: 'resources.transfer_requisitions.fields.dispatched_by'),
            new ExportColumn(key: 'received_by', labelKey: 'resources.transfer_requisitions.fields.received_by'),
            new ExportColumn(key: 'notes', labelKey: 'resources.transfer_requisitions.fields.notes'),
            new ExportColumn(key: 'items_count', labelKey: 'resources.transfer_requisitions.table.items', cast: ExportColumn::CAST_INT),
        ];
    }

    public function query(Builder $tableQuery): Builder
    {
        return $tableQuery
            ->select([
                'transfer_requisitions.id as transfer_requisition_id',
                'transfer_requisitions.reference_code',
                'transfer_requisitions.status',
                'transfer_requisitions.from_warehouse_id',
                'fw.name as from_warehouse',
                'transfer_requisitions.to_warehouse_id',
                'tw.name as to_warehouse',
                'ur.name as requested_by',
                'transfer_requisitions.requested_at',
                'ua.name as approved_by',
                'ud.name as dispatched_by',
                'uu.name as received_by',
                'transfer_requisitions.notes',
            ])
            ->join('warehouses as fw', 'fw.id', '=', 'transfer_requisitions.from_warehouse_id')
            ->join('warehouses as tw', 'tw.id', '=', 'transfer_requisitions.to_warehouse_id')
            ->leftJoin('users as ur', 'ur.id', '=', 'transfer_requisitions.requested_by')
            ->leftJoin('users as ua', 'ua.id', '=', 'transfer_requisitions.approved_by')
            ->leftJoin('users as ud', 'ud.id', '=', 'transfer_requisitions.dispatched_by')
            ->leftJoin('users as uu', 'uu.id', '=', 'transfer_requisitions.received_by')
            ->withCount('items');
    }

    /**
     * A requisition belongs to either endpoint, so one group holding
     * both columns — the same either-endpoint shape
     * `TransferRequisitionResource` applies to the page.
     */
    public function warehouseScope(): array
    {
        return [['transfer_requisitions.from_warehouse_id', 'transfer_requisitions.to_warehouse_id']];
    }
}
