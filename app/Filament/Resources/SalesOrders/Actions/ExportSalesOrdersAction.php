<?php

declare(strict_types=1);

namespace App\Filament\Resources\SalesOrders\Actions;

use App\Filament\Support\Exports\ExportRecordsAction;

/**
 * ExportSalesOrdersAction — export header action on ListSalesOrders
 * (#226). Offers the header and its items; the item file follows
 * whatever the user has filtered on the sales orders table.
 */
class ExportSalesOrdersAction extends ExportRecordsAction
{
    protected function name(): string
    {
        return 'exportSalesOrders';
    }

    /**
     * @return array<int, string>
     */
    protected function definitionKeys(): array
    {
        return [
            'sales_orders',
            'sales_orders.items',
        ];
    }
}
