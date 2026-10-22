<?php

declare(strict_types=1);

namespace App\Services\Export\Definitions;

use App\Models\StockMovement;
use App\Services\Export\ExportColumn;
use App\Services\Export\ExportDefinition;
use Illuminate\Database\Eloquent\Builder;

/**
 * StockMovementExportDefinition — "stock_movements" (#226): the stock
 * ledger, exported exactly as the List page scopes it.
 *
 * The row is a ledger entry, so it carries the ids it points at
 * (movement, variant, warehouse, related movement, reference) next to
 * the human-readable names — the file has to survive on its own
 * without the app. Quantity is the signed movement amount. The
 * `stock_movement_idempotency_keys` table is control state, never a
 * column here, and the idempotency key is never exported.
 *
 * Enum columns (`type`) export their backing value, not a label.
 */
class StockMovementExportDefinition extends ExportDefinition
{
    public function key(): string
    {
        return 'stock_movements';
    }

    public function labelKey(): string
    {
        return 'exports.types.stock_movements';
    }

    public function modelClass(): string
    {
        return StockMovement::class;
    }

    /**
     * @return array<int, ExportColumn>
     */
    public function columns(): array
    {
        return [
            new ExportColumn(key: 'movement_id', labelKey: 'exports.columns.movement_id', cast: ExportColumn::CAST_INT),
            new ExportColumn(key: 'created_at', labelKey: 'resources.stock_movements.fields.timestamp', cast: ExportColumn::CAST_DATETIME),
            new ExportColumn(key: 'product_variant_id', labelKey: 'exports.columns.product_variant_id', cast: ExportColumn::CAST_INT),
            new ExportColumn(key: 'sku', labelKey: 'resources.stock_movements.fields.sku'),
            new ExportColumn(key: 'warehouse_id', labelKey: 'exports.columns.warehouse_id', cast: ExportColumn::CAST_INT),
            new ExportColumn(key: 'warehouse', labelKey: 'resources.stock_movements.fields.warehouse'),
            new ExportColumn(key: 'type', labelKey: 'resources.stock_movements.fields.type'),
            new ExportColumn(key: 'quantity', labelKey: 'resources.stock_movements.fields.quantity', cast: ExportColumn::CAST_FLOAT),
            new ExportColumn(key: 'unit_name_used', labelKey: 'resources.stock_movements.fields.unit'),
            new ExportColumn(key: 'unit_ratio_used', labelKey: 'exports.columns.unit_ratio_used', cast: ExportColumn::CAST_FLOAT),
            new ExportColumn(key: 'related_movement_id', labelKey: 'exports.columns.related_movement_id', cast: ExportColumn::CAST_INT),
            new ExportColumn(key: 'reference_type', labelKey: 'exports.columns.reference_type'),
            new ExportColumn(key: 'reference_id', labelKey: 'exports.columns.reference_id', cast: ExportColumn::CAST_INT),
            new ExportColumn(key: 'reference_code', labelKey: 'resources.stock_movements.fields.reference_code'),
            new ExportColumn(key: 'notes', labelKey: 'resources.stock_movements.fields.notes'),
            new ExportColumn(key: 'recorded_by', labelKey: 'resources.stock_movements.fields.by'),
        ];
    }

    public function query(Builder $tableQuery): Builder
    {
        return $tableQuery
            ->select([
                'stock_movements.id as movement_id',
                'stock_movements.created_at',
                'stock_movements.product_variant_id',
                'pv.sku',
                'stock_movements.warehouse_id',
                'wh.code as warehouse',
                'stock_movements.type',
                'stock_movements.quantity',
                'stock_movements.unit_name_used',
                'stock_movements.unit_ratio_used',
                'stock_movements.related_movement_id',
                'stock_movements.reference_type',
                'stock_movements.reference_id',
                'stock_movements.reference_code',
                'stock_movements.notes',
                'u.name as recorded_by',
            ])
            ->join('product_variants as pv', 'pv.id', '=', 'stock_movements.product_variant_id')
            ->join('warehouses as wh', 'wh.id', '=', 'stock_movements.warehouse_id')
            ->leftJoin('users as u', 'u.id', '=', 'stock_movements.created_by');
    }

    /** One ledger entry belongs to exactly one warehouse. */
    public function warehouseScope(): array
    {
        return [['stock_movements.warehouse_id']];
    }
}
