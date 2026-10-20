<?php

declare(strict_types=1);

namespace App\Services\Export\Definitions;

use App\Models\TransferRequisition;
use App\Models\TransferRequisitionItem;
use App\Models\User;
use App\Services\Export\ExportColumn;
use App\Services\Export\ExportDefinition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

/**
 * TransferRequisitionItemExportDefinition —
 * "transfer_requisitions.items" (#226): the line items of the
 * requisitions the user is looking at.
 *
 * The items have no List page of their own, so the scope subquery is
 * the requisitions table query narrowed to its ids: filtering or
 * warehouse-scoping the requisitions page filters the exported items
 * with it. Both the item id and the parent requisition id plus its
 * reference code travel with every row.
 */
class TransferRequisitionItemExportDefinition extends ExportDefinition
{
    public function key(): string
    {
        return 'transfer_requisitions.items';
    }

    public function labelKey(): string
    {
        return 'exports.types.transfer_requisitions.items';
    }

    public function modelClass(): string
    {
        return TransferRequisition::class;
    }

    public function authorize(User $user): void
    {
        Gate::forUser($user)->authorize('viewAny', TransferRequisition::class);
    }

    /**
     * @return array<int, ExportColumn>
     */
    public function columns(): array
    {
        return [
            new ExportColumn(key: 'transfer_requisition_item_id', labelKey: 'exports.columns.transfer_requisition_item_id', cast: ExportColumn::CAST_INT),
            new ExportColumn(key: 'transfer_requisition_id', labelKey: 'exports.columns.transfer_requisition_id', cast: ExportColumn::CAST_INT),
            new ExportColumn(key: 'requisition_reference_code', labelKey: 'resources.transfer_requisitions.fields.reference_code'),
            new ExportColumn(key: 'product_variant_id', labelKey: 'exports.columns.product_variant_id', cast: ExportColumn::CAST_INT),
            new ExportColumn(key: 'sku', labelKey: 'resources.transfer_requisitions.fields.original_sku'),
            new ExportColumn(key: 'substitute_product_variant_id', labelKey: 'exports.columns.substitute_product_variant_id', cast: ExportColumn::CAST_INT),
            new ExportColumn(key: 'substitute_sku', labelKey: 'resources.transfer_requisitions.fields.substitute_sku'),
            new ExportColumn(key: 'requested_qty', labelKey: 'resources.transfer_requisitions.fields.requested', cast: ExportColumn::CAST_FLOAT),
            new ExportColumn(key: 'requested_unit_name', labelKey: 'exports.columns.requested_unit'),
            new ExportColumn(key: 'approved_qty', labelKey: 'resources.transfer_requisitions.fields.approved', cast: ExportColumn::CAST_FLOAT),
            new ExportColumn(key: 'approved_unit_name', labelKey: 'exports.columns.approved_unit'),
            new ExportColumn(key: 'shipped_base_qty', labelKey: 'resources.transfer_requisitions.fields.shipped_base', cast: ExportColumn::CAST_FLOAT),
            new ExportColumn(key: 'received_good_base_qty', labelKey: 'resources.transfer_requisitions.fields.received_good_base', cast: ExportColumn::CAST_FLOAT),
        ];
    }

    public function query(Builder $tableQuery): Builder
    {
        return TransferRequisitionItem::query()
            ->select([
                'transfer_requisition_items.id as transfer_requisition_item_id',
                'transfer_requisition_items.transfer_requisition_id',
                'tr.reference_code as requisition_reference_code',
                'transfer_requisition_items.product_variant_id',
                'pv.sku',
                'transfer_requisition_items.substitute_product_variant_id',
                'spv.sku as substitute_sku',
                'transfer_requisition_items.requested_qty',
                'transfer_requisition_items.requested_unit_name',
                'transfer_requisition_items.approved_qty',
                'transfer_requisition_items.approved_unit_name',
                'transfer_requisition_items.shipped_base_qty',
                'transfer_requisition_items.received_good_base_qty',
            ])
            ->join('transfer_requisitions as tr', 'tr.id', '=', 'transfer_requisition_items.transfer_requisition_id')
            ->join('product_variants as pv', 'pv.id', '=', 'transfer_requisition_items.product_variant_id')
            ->leftJoin('product_variants as spv', 'spv.id', '=', 'transfer_requisition_items.substitute_product_variant_id')
            ->whereIn('transfer_requisition_items.transfer_requisition_id', $this->parentIdsQuery($tableQuery));
    }

    /**
     * An item carries no warehouse either; it inherits its requisition's
     * (joined as `tr`) either-endpoint shape.
     */
    public function warehouseScope(): array
    {
        return [['tr.from_warehouse_id', 'tr.to_warehouse_id']];
    }
}
