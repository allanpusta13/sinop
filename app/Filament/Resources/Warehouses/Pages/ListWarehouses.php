<?php

declare(strict_types=1);

namespace App\Filament\Resources\Warehouses\Pages;

use App\Filament\Resources\Warehouses\Actions\ImportWarehousesAction;
use App\Filament\Resources\Warehouses\WarehouseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListWarehouses extends ListRecords
{
    protected static string $resource = WarehouseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportWarehousesAction::make(),
            CreateAction::make()
                ->label(__('resources.warehouses.actions.create'))
                ->icon(Heroicon::OutlinedBuildingOffice),
        ];
    }
}
