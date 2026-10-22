<?php

declare(strict_types=1);

namespace App\Filament\Resources\InTransits\Pages;

use App\Filament\Resources\InTransits\Actions\ExportInTransitsAction;
use App\Filament\Resources\InTransits\InTransitResource;
use Filament\Resources\Pages\ListRecords;

class ListInTransits extends ListRecords
{
    protected static string $resource = InTransitResource::class;

    // No create route — read-only monitor; its only header action is
    // the export of what the page is showing.
    protected function getHeaderActions(): array
    {
        return [
            ExportInTransitsAction::make(),
        ];
    }
}
