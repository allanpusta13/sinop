<?php

declare(strict_types=1);

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\Actions\ExportCustomersAction;
use App\Filament\Resources\Customers\Actions\ImportCustomersAction;
use App\Filament\Resources\Customers\CustomerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportCustomersAction::make(),
            ExportCustomersAction::make(),
            CreateAction::make()
                ->label(__('resources.customers.actions.create'))
                ->icon(Heroicon::OutlinedUserGroup),
        ];
    }
}
