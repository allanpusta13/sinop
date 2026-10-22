<?php

declare(strict_types=1);

namespace App\Filament\Resources\InTransits\Actions;

use App\Filament\Support\Exports\ExportRecordsAction;

/**
 * ExportInTransitsAction — export header action on ListInTransits
 * (#226), backed by the shared export engine (registry key
 * "in_transits").
 */
class ExportInTransitsAction extends ExportRecordsAction
{
    protected function name(): string
    {
        return 'exportInTransits';
    }

    /**
     * @return array<int, string>
     */
    protected function definitionKeys(): array
    {
        return ['in_transits'];
    }
}
