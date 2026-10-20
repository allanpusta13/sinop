<?php

declare(strict_types=1);

namespace App\Filament\Resources\Customers\Actions;

use App\Filament\Support\Exports\ExportRecordsAction;

/**
 * ExportCustomersAction — export header action on ListCustomers
 * (#225), backed by the shared export engine (registry key
 * "customers").
 */
class ExportCustomersAction extends ExportRecordsAction
{
    protected function name(): string
    {
        return 'exportCustomers';
    }

    /**
     * @return array<int, string>
     */
    protected function definitionKeys(): array
    {
        return ['customers'];
    }
}
