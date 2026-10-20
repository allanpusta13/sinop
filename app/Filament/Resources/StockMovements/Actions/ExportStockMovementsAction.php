<?php

declare(strict_types=1);

namespace App\Filament\Resources\StockMovements\Actions;

use App\Filament\Support\Exports\ExportRecordsAction;

/**
 * ExportStockMovementsAction — export header action on
 * ListStockMovements (#226), backed by the shared export engine
 * (registry key "stock_movements").
 */
class ExportStockMovementsAction extends ExportRecordsAction
{
    protected function name(): string
    {
        return 'exportStockMovements';
    }

    /**
     * @return array<int, string>
     */
    protected function definitionKeys(): array
    {
        return ['stock_movements'];
    }
}
