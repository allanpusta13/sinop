<?php

declare(strict_types=1);

namespace App\Services\Export\Definitions;

use App\Models\User;
use App\Models\Warehouse;
use App\Services\Export\ExportColumn;
use App\Services\Export\ExportDefinition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

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

    public function authorize(User $user): void
    {
        Gate::forUser($user)->authorize('viewAny', Warehouse::class);
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
}
