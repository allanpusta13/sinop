<?php

declare(strict_types=1);

namespace App\Services\Export\Definitions;

use App\Models\TransferRequisitionItem;
use App\Models\TransferRequisitionItemRevision;
use App\Models\User;
use App\Services\Export\ExportColumn;
use App\Services\Export\ExportDefinition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

/**
 * TransferRequisitionItemRevisionExportDefinition —
 * "transfer_requisitions.revisions" (#226): the negotiation revisions
 * of the items of the requisitions the user is looking at.
 *
 * Scope is derived the same way as the items export (requisition ids
 * → item ids), so filtering the requisitions page filters its
 * revisions too. `side` and `status` export their backing values, and
 * the proposing user exports as a name, never an id.
 */
class TransferRequisitionItemRevisionExportDefinition extends ExportDefinition
{
    public function key(): string
    {
        return 'transfer_requisitions.revisions';
    }

    public function labelKey(): string
    {
        return 'exports.types.transfer_requisitions.revisions';
    }

    public function modelClass(): string
    {
        return TransferRequisitionItemRevision::class;
    }

    public function authorize(User $user): void
    {
        Gate::forUser($user)->authorize('viewAny', TransferRequisitionItemRevision::class);
    }

    /**
     * @return array<int, ExportColumn>
     */
    public function columns(): array
    {
        return [
            new ExportColumn(key: 'revision_id', labelKey: 'exports.columns.revision_id', cast: ExportColumn::CAST_INT),
            new ExportColumn(key: 'transfer_requisition_item_id', labelKey: 'exports.columns.transfer_requisition_item_id', cast: ExportColumn::CAST_INT),
            new ExportColumn(key: 'transfer_requisition_id', labelKey: 'exports.columns.transfer_requisition_id', cast: ExportColumn::CAST_INT),
            new ExportColumn(key: 'requisition_reference_code', labelKey: 'resources.transfer_requisitions.fields.reference_code'),
            new ExportColumn(key: 'parent_sku', labelKey: 'resources.transfer_requisitions.fields.original_sku'),
            new ExportColumn(key: 'side', labelKey: 'resources.transfer_requisitions.fields.negotiation_side'),
            new ExportColumn(key: 'status', labelKey: 'resources.transfer_requisitions.fields.status'),
            new ExportColumn(key: 'proposed_qty', labelKey: 'resources.transfer_requisitions.fields.proposed', cast: ExportColumn::CAST_FLOAT),
            new ExportColumn(key: 'proposed_unit_name', labelKey: 'exports.columns.proposed_unit'),
            new ExportColumn(key: 'proposed_by', labelKey: 'resources.transfer_requisitions.fields.proposed_by'),
            new ExportColumn(key: 'responds_to_revision_id', labelKey: 'resources.transfer_requisitions.fields.responds_to', cast: ExportColumn::CAST_INT),
            new ExportColumn(key: 'responded_at', labelKey: 'resources.transfer_requisitions.fields.responded_at', cast: ExportColumn::CAST_DATETIME),
            new ExportColumn(key: 'negotiation_reason', labelKey: 'resources.transfer_requisitions.fields.negotiation_reason'),
        ];
    }

    public function query(Builder $tableQuery): Builder
    {
        return TransferRequisitionItemRevision::query()
            ->select([
                'transfer_requisition_item_revisions.id as revision_id',
                'transfer_requisition_item_revisions.transfer_requisition_item_id',
                'i.transfer_requisition_id',
                'tr.reference_code as requisition_reference_code',
                'pv.sku as parent_sku',
                'transfer_requisition_item_revisions.side',
                'transfer_requisition_item_revisions.status',
                'transfer_requisition_item_revisions.proposed_qty',
                'transfer_requisition_item_revisions.proposed_unit_name',
                'u.name as proposed_by',
                'transfer_requisition_item_revisions.responds_to_revision_id',
                'transfer_requisition_item_revisions.responded_at',
                'transfer_requisition_item_revisions.negotiation_reason',
            ])
            ->join('transfer_requisition_items as i', 'i.id', '=', 'transfer_requisition_item_revisions.transfer_requisition_item_id')
            ->join('transfer_requisitions as tr', 'tr.id', '=', 'i.transfer_requisition_id')
            ->join('product_variants as pv', 'pv.id', '=', 'i.product_variant_id')
            ->leftJoin('users as u', 'u.id', '=', 'transfer_requisition_item_revisions.user_id')
            ->whereIn('transfer_requisition_item_revisions.transfer_requisition_item_id', TransferRequisitionItem::query()
                ->select('transfer_requisition_items.id')
                ->whereIn('transfer_requisition_items.transfer_requisition_id', $this->parentIdsQuery($tableQuery)));
    }

    /**
     * A revision belongs to an item, which belongs to a requisition —
     * two hops, but the same either-endpoint shape either way, and the
     * requisition is already joined as `tr`.
     */
    public function warehouseScope(): array
    {
        return [['tr.from_warehouse_id', 'tr.to_warehouse_id']];
    }
}
