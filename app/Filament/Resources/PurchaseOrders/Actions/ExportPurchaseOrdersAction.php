<?php

declare(strict_types=1);

namespace App\Filament\Resources\PurchaseOrders\Actions;

use App\Filament\Support\Exports\ExportRecordsAction;

/**
 * ExportPurchaseOrdersAction — export header action on
 * ListPurchaseOrders (#226). Offers the header and its items; the item
 * file follows whatever the user has filtered on the purchase orders
 * table.
 */
class ExportPurchaseOrdersAction extends ExportRecordsAction
{
    protected function name(): string
    {
        return 'exportPurchaseOrders';
    }

    /**
     * @return array<int, string>
     */
    protected function definitionKeys(): array
    {
        return [
            'purchase_orders',
            'purchase_orders.items',
        ];
    }
}
