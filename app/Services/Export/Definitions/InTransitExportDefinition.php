<?php

declare(strict_types=1);

namespace App\Services\Export\Definitions;

use App\Models\InTransit;
use App\Models\User;
use App\Services\Export\ExportColumn;
use App\Services\Export\ExportDefinition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

/**
 * InTransitExportDefinition — "in_transits" (#226): goods dispatched
 * on a requisition and not yet cleared.
 *
 * Served from its own List page, so the page's scoped and filtered
 * query is the row set — there is no parent sub-select to derive.
 * `status` exports its backing value and `cleared_at` stays nullable
 * for the entries that have not cleared yet.
 */
class InTransitExportDefinition extends ExportDefinition
{
    public function key(): string
    {
        return 'in_transits';
    }

    public function labelKey(): string
    {
        return 'exports.types.in_transits';
    }

    public function modelClass(): string
    {
        return InTransit::class;
    }

    public function authorize(User $user): void
    {
        Gate::forUser($user)->authorize('viewAny', InTransit::class);
    }

    /**
     * @return array<int, ExportColumn>
     */
    public function columns(): array
    {
        return [
            new ExportColumn(key: 'transfer_requisition_id', labelKey: 'exports.columns.transfer_requisition_id', cast: ExportColumn::CAST_INT),
            new ExportColumn(key: 'requisition_reference_code', labelKey: 'resources.in_transits.fields.requisition'),
            new ExportColumn(key: 'transfer_requisition_item_id', labelKey: 'exports.columns.transfer_requisition_item_id', cast: ExportColumn::CAST_INT),
            new ExportColumn(key: 'product_variant_id', labelKey: 'exports.columns.product_variant_id', cast: ExportColumn::CAST_INT),
            new ExportColumn(key: 'sku', labelKey: 'resources.in_transits.fields.sku'),
            new ExportColumn(key: 'dispatched_base_qty', labelKey: 'resources.in_transits.fields.dispatched_base', cast: ExportColumn::CAST_FLOAT),
            new ExportColumn(key: 'dispatched_at', labelKey: 'resources.in_transits.fields.dispatched_at', cast: ExportColumn::CAST_DATETIME),
            new ExportColumn(key: 'status', labelKey: 'resources.in_transits.fields.status'),
            new ExportColumn(key: 'cleared_at', labelKey: 'resources.in_transits.fields.cleared_at', cast: ExportColumn::CAST_DATETIME),
        ];
    }

    public function query(Builder $tableQuery): Builder
    {
        return $tableQuery
            ->clone()
            ->select([
                'in_transits.transfer_requisition_id',
                'tr.reference_code as requisition_reference_code',
                'in_transits.transfer_requisition_item_id',
                'in_transits.product_variant_id',
                'pv.sku',
                'in_transits.dispatched_base_qty',
                'in_transits.dispatched_at',
                'in_transits.status',
                'in_transits.cleared_at',
            ])
            ->join('transfer_requisitions as tr', 'tr.id', '=', 'in_transits.transfer_requisition_id')
            ->join('product_variants as pv', 'pv.id', '=', 'in_transits.product_variant_id');
    }

    /**
     * An in-transit record carries no warehouse of its own — it is
     * derived from the requisition it joined as `tr`, and inherits that
     * record's either-endpoint shape.
     */
    public function warehouseScope(): array
    {
        return [['tr.from_warehouse_id', 'tr.to_warehouse_id']];
    }
}
