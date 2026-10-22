<?php

declare(strict_types=1);

namespace App\Filament\Resources\SalesOrders\Pages;

use App\Filament\Resources\SalesOrders\Actions\ExportSalesOrdersAction;
use App\Filament\Resources\SalesOrders\SalesOrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListSalesOrders extends ListRecords
{
    protected static string $resource = SalesOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportSalesOrdersAction::make(),
            CreateAction::make()
                ->label(__('resources.sales_orders.actions.create'))
                ->icon(Heroicon::OutlinedBanknotes),
        ];
    }
}
