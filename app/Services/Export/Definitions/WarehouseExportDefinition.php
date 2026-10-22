<?php

declare(strict_types=1);

namespace App\Services\Export\Definitions;

use App\Models\Warehouse;
use App\Services\Export\ExportColumn;
use App\Services\Export\ExportDefinition;
use Illuminate\Database\Eloquent\Builder;

/**
 * WarehouseExportDefinition — "warehouses" (#225): the Warehouses List
 * page's own rows, including the staff and ledger-entry counters the
 * table shows.
 *
 * The counters come from the same `withCount()` relations the List
 * page uses; re-adding them after the explicit select keeps the export
 * independent of whatever select list the table happens to carry.
 */
class WarehouseExportDefinition extends ExportDefinition
{
    public function key(): string
    {
        return 'warehouses';
    }

    public function labelKey(): string
    {
        return 'exports.types.warehouses';
    }

    public function modelClass(): string
    {
        return Warehouse::class;
    }

    /**
     * @return array<int, ExportColumn>
     */
    public function columns(): array
    {
        return [
            new ExportColumn(key: 'code', labelKey: 'resources.warehouses.table.code'),
            new ExportColumn(key: 'name', labelKey: 'resources.warehouses.table.name'),
            new ExportColumn(key: 'location', labelKey: 'resources.warehouses.table.location'),
            new ExportColumn(key: 'is_active', labelKey: 'resources.warehouses.table.status', cast: ExportColumn::CAST_INT),
            new ExportColumn(key: 'users_count', labelKey: 'resources.warehouses.table.staff', cast: ExportColumn::CAST_INT),
            new ExportColumn(key: 'stock_movements_count', labelKey: 'resources.warehouses.table.ledger_entries', cast: ExportColumn::CAST_INT),
        ];
    }

    public function query(Builder $tableQuery): Builder
    {
        return $tableQuery
            ->select([
                'warehouses.code',
                'warehouses.name',
                'warehouses.location',
                'warehouses.is_active',
            ])
            ->withCount(['users', 'stockMovements']);
    }

    /**
     * The export *is* the warehouse table, so a row is in scope when its
     * own id is one the user is assigned to — the same predicate
     * `WarehouseResource::getEloquentQuery()` applies to the page.
     */
    public function warehouseScope(): array
    {
        return [['warehouses.id']];
    }
}
