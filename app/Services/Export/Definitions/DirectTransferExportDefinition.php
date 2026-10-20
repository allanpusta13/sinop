<?php

declare(strict_types=1);

namespace App\Services\Export\Definitions;

use App\Models\DirectTransfer;
use App\Models\User;
use App\Services\Export\ExportColumn;
use App\Services\Export\ExportDefinition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

/**
 * DirectTransferExportDefinition — "direct_transfers" (#226): direct
 * transfer headers, exported as the List page scopes them (both
 * warehouses inside the user's assignments).
 *
 * Direct transfers carry no status column — a transfer is the record
 * of goods that already moved — so the header ships its warehouses,
 * timestamp, actor name and notes.
 */
class DirectTransferExportDefinition extends ExportDefinition
{
    public function key(): string
    {
        return 'direct_transfers';
    }

    public function labelKey(): string
    {
        return 'exports.types.direct_transfers.label';
    }

    public function modelClass(): string
    {
        return DirectTransfer::class;
    }

    public function authorize(User $user): void
    {
        Gate::forUser($user)->authorize('viewAny', DirectTransfer::class);
    }

    /**
     * @return array<int, ExportColumn>
     */
    public function columns(): array
    {
        return [
            new ExportColumn(key: 'direct_transfer_id', labelKey: 'exports.columns.direct_transfer_id', cast: ExportColumn::CAST_INT),
            new ExportColumn(key: 'reference_code', labelKey: 'resources.direct_transfers.fields.reference_code'),
            new ExportColumn(key: 'from_warehouse_id', labelKey: 'exports.columns.from_warehouse_id', cast: ExportColumn::CAST_INT),
            new ExportColumn(key: 'from_warehouse', labelKey: 'resources.direct_transfers.fields.from_warehouse'),
            new ExportColumn(key: 'to_warehouse_id', labelKey: 'exports.columns.to_warehouse_id', cast: ExportColumn::CAST_INT),
            new ExportColumn(key: 'to_warehouse', labelKey: 'resources.direct_transfers.fields.to_warehouse'),
            new ExportColumn(key: 'transferred_at', labelKey: 'resources.direct_transfers.fields.transferred_at', cast: ExportColumn::CAST_DATETIME),
            new ExportColumn(key: 'transferred_by', labelKey: 'resources.direct_transfers.table.by'),
            new ExportColumn(key: 'notes', labelKey: 'resources.direct_transfers.fields.notes'),
            new ExportColumn(key: 'items_count', labelKey: 'resources.direct_transfers.table.items', cast: ExportColumn::CAST_INT),
        ];
    }

    public function query(Builder $tableQuery): Builder
    {
        return $tableQuery
            ->select([
                'direct_transfers.id as direct_transfer_id',
                'direct_transfers.reference_code',
                'direct_transfers.from_warehouse_id',
                'fw.name as from_warehouse',
                'direct_transfers.to_warehouse_id',
                'tw.name as to_warehouse',
                'direct_transfers.transferred_at',
                'u.name as transferred_by',
                'direct_transfers.notes',
            ])
            ->join('warehouses as fw', 'fw.id', '=', 'direct_transfers.from_warehouse_id')
            ->join('warehouses as tw', 'tw.id', '=', 'direct_transfers.to_warehouse_id')
            ->leftJoin('users as u', 'u.id', '=', 'direct_transfers.transferred_by')
            ->withCount('items');
    }
}
