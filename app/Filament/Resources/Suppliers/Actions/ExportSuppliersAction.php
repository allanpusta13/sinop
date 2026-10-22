<?php

declare(strict_types=1);

namespace App\Filament\Resources\Suppliers\Actions;

use App\Filament\Support\Exports\ExportRecordsAction;

/**
 * ExportSuppliersAction — export header action on ListSuppliers
 * (#225), backed by the shared export engine (registry key
 * "suppliers").
 */
class ExportSuppliersAction extends ExportRecordsAction
{
    protected function name(): string
    {
        return 'exportSuppliers';
    }

    /**
     * @return array<int, string>
     */
    protected function definitionKeys(): array
    {
        return ['suppliers'];
    }
}
