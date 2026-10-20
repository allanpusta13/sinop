<?php

declare(strict_types=1);

namespace App\Services\Export\Definitions;

use App\Models\LossLedger;
use App\Models\User;
use App\Services\Export\ExportColumn;
use App\Services\Export\ExportDefinition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

/**
 * LossLedgerExportDefinition — "loss_ledgers" (#226): the loss ledger,
 * exported as the List page scopes it.
 *
 * Losses hang off a transfer requisition (and often off a specific
 * requisition item), so both ids and the requisition's reference code
 * travel with the row. `loss_category` exports its backing value.
 */
class LossLedgerExportDefinition extends ExportDefinition
{
    public function key(): string
    {
        return 'loss_ledgers';
    }

    public function labelKey(): string
    {
        return 'exports.types.loss_ledgers';
    }

    public function modelClass(): string
    {
        return LossLedger::class;
    }

    public function authorize(User $user): void
    {
        Gate::forUser($user)->authorize('viewAny', LossLedger::class);
    }

    /**
     * @return array<int, ExportColumn>
     */
    public function columns(): array
    {
        return [
            new ExportColumn(key: 'loss_id', labelKey: 'exports.columns.loss_id', cast: ExportColumn::CAST_INT),
            new ExportColumn(key: 'recorded_at', labelKey: 'resources.loss_ledgers.fields.recorded_at', cast: ExportColumn::CAST_DATETIME),
            new ExportColumn(key: 'transfer_requisition_id', labelKey: 'exports.columns.transfer_requisition_id', cast: ExportColumn::CAST_INT),
            new ExportColumn(key: 'requisition_reference_code', labelKey: 'resources.loss_ledgers.fields.requisition'),
            new ExportColumn(key: 'transfer_requisition_item_id', labelKey: 'exports.columns.transfer_requisition_item_id', cast: ExportColumn::CAST_INT),
            new ExportColumn(key: 'product_variant_id', labelKey: 'exports.columns.product_variant_id', cast: ExportColumn::CAST_INT),
            new ExportColumn(key: 'sku', labelKey: 'resources.loss_ledgers.fields.sku'),
            new ExportColumn(key: 'warehouse_id', labelKey: 'exports.columns.warehouse_id', cast: ExportColumn::CAST_INT),
            new ExportColumn(key: 'warehouse', labelKey: 'resources.loss_ledgers.fields.warehouse'),
            new ExportColumn(key: 'lost_base_qty', labelKey: 'resources.loss_ledgers.fields.lost_base', cast: ExportColumn::CAST_FLOAT),
            new ExportColumn(key: 'damaged_base_qty', labelKey: 'resources.loss_ledgers.fields.damaged_base', cast: ExportColumn::CAST_FLOAT),
            new ExportColumn(key: 'loss_category', labelKey: 'resources.loss_ledgers.fields.loss_category'),
            new ExportColumn(key: 'unit_cost_price', labelKey: 'resources.loss_ledgers.fields.unit_cost_price', cast: ExportColumn::CAST_FLOAT),
            new ExportColumn(key: 'total_financial_loss', labelKey: 'resources.loss_ledgers.fields.total_financial_loss', cast: ExportColumn::CAST_FLOAT),
            new ExportColumn(key: 'recorded_by', labelKey: 'resources.loss_ledgers.table.by'),
        ];
    }

    public function query(Builder $tableQuery): Builder
    {
        return $tableQuery
            ->select([
                'loss_ledgers.id as loss_id',
                'loss_ledgers.recorded_at',
                'loss_ledgers.transfer_requisition_id',
                'tr.reference_code as requisition_reference_code',
                'loss_ledgers.transfer_requisition_item_id',
                'loss_ledgers.product_variant_id',
                'pv.sku',
                'loss_ledgers.warehouse_id',
                'wh.code as warehouse',
                'loss_ledgers.lost_base_qty',
                'loss_ledgers.damaged_base_qty',
                'loss_ledgers.loss_category',
                'loss_ledgers.unit_cost_price',
                'loss_ledgers.total_financial_loss',
                'u.name as recorded_by',
            ])
            ->join('product_variants as pv', 'pv.id', '=', 'loss_ledgers.product_variant_id')
            ->join('warehouses as wh', 'wh.id', '=', 'loss_ledgers.warehouse_id')
            ->leftJoin('transfer_requisitions as tr', 'tr.id', '=', 'loss_ledgers.transfer_requisition_id')
            ->leftJoin('users as u', 'u.id', '=', 'loss_ledgers.recorded_by');
    }

    /** One write-off belongs to exactly one warehouse. */
    public function warehouseScope(): array
    {
        return [['loss_ledgers.warehouse_id']];
    }
}
