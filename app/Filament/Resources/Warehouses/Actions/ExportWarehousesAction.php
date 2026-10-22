<?php

declare(strict_types=1);

namespace App\Filament\Resources\Warehouses\Actions;

use App\Filament\Support\Exports\ExportRecordsAction;

/**
 * ExportWarehousesAction — export header action on ListWarehouses
 * (#225), backed by the shared export engine (registry key
 * "warehouses").
 */
class ExportWarehousesAction extends ExportRecordsAction
{
    protected function name(): string
    {
        return 'exportWarehouses';
    }

    /**
     * @return array<int, string>
     */
    protected function definitionKeys(): array
    {
        return ['warehouses'];
    }
}
