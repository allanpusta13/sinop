<?php

declare(strict_types=1);

namespace App\Services\Export\Definitions;

use App\Models\DirectTransfer;
use App\Models\DirectTransferItem;
use App\Services\Export\ExportColumn;
use App\Services\Export\ExportDefinition;
use Illuminate\Database\Eloquent\Builder;

/**
 * DirectTransferItemExportDefinition — "direct_transfers.items"
 * (#226): the line items of the direct transfers the user is looking
 * at.
 *
 * Scope is the direct transfers table query narrowed to its ids, so
 * filtering the direct transfers page filters the exported items with
 * it.
 */
class DirectTransferItemExportDefinition extends ExportDefinition
{
    public function key(): string
    {
        return 'direct_transfers.items';
    }

    public function labelKey(): string
    {
        return 'exports.types.direct_transfers.items';
    }

    public function modelClass(): string
    {
        return DirectTransfer::class;
    }

    /**
     * @return array<int, ExportColumn>
     */
    public function columns(): array
    {
        return [
            new ExportColumn(key: 'direct_transfer_id', labelKey: 'exports.columns.direct_transfer_id', cast: ExportColumn::CAST_INT),
            new ExportColumn(key: 'direct_transfer_reference_code', labelKey: 'resources.direct_transfers.fields.reference_code'),
            new ExportColumn(key: 'product_variant_id', labelKey: 'exports.columns.product_variant_id', cast: ExportColumn::CAST_INT),
            new ExportColumn(key: 'sku', labelKey: 'resources.direct_transfers.fields.sku'),
            new ExportColumn(key: 'qty', labelKey: 'resources.direct_transfers.fields.qty', cast: ExportColumn::CAST_FLOAT),
            new ExportColumn(key: 'unit_name', labelKey: 'resources.direct_transfers.fields.unit'),
            new ExportColumn(key: 'unit_ratio', labelKey: 'resources.direct_transfers.fields.ratio', cast: ExportColumn::CAST_FLOAT),
            new ExportColumn(key: 'base_qty', labelKey: 'resources.direct_transfers.fields.base_qty', cast: ExportColumn::CAST_FLOAT),
            new ExportColumn(key: 'notes', labelKey: 'resources.direct_transfers.fields.notes'),
        ];
    }

    public function query(Builder $tableQuery): Builder
    {
        return DirectTransferItem::query()
            ->select([
                'direct_transfer_items.direct_transfer_id',
                'dt.reference_code as direct_transfer_reference_code',
                'direct_transfer_items.product_variant_id',
                'pv.sku',
                'direct_transfer_items.qty',
                'direct_transfer_items.unit_name',
                'direct_transfer_items.unit_ratio',
                'direct_transfer_items.base_qty',
                'direct_transfer_items.notes',
            ])
            ->join('direct_transfers as dt', 'dt.id', '=', 'direct_transfer_items.direct_transfer_id')
            ->join('product_variants as pv', 'pv.id', '=', 'direct_transfer_items.product_variant_id')
            ->whereIn('direct_transfer_items.direct_transfer_id', $this->parentIdsQuery($tableQuery));
    }

    /**
     * An item inherits its direct transfer's both-endpoints rule
     * (§20.1) — two groups, not one.
     */
    public function warehouseScope(): array
    {
        return [
            ['dt.from_warehouse_id'],
            ['dt.to_warehouse_id'],
        ];
    }
}
