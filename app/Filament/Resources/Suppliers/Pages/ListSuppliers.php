<?php

declare(strict_types=1);

namespace App\Filament\Resources\Suppliers\Pages;

use App\Filament\Resources\Suppliers\Actions\ImportSuppliersAction;
use App\Filament\Resources\Suppliers\SupplierResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListSuppliers extends ListRecords
{
    protected static string $resource = SupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportSuppliersAction::make(),
            CreateAction::make()
                ->label(__('resources.suppliers.actions.create'))
                ->icon(Heroicon::OutlinedBuildingStorefront),
        ];
    }
}
