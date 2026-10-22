<?php

declare(strict_types=1);

namespace App\Filament\Resources\LossLedgers\Actions;

use App\Filament\Support\Exports\ExportRecordsAction;

/**
 * ExportLossLedgersAction — export header action on ListLossLedgers
 * (#226), backed by the shared export engine (registry key
 * "loss_ledgers").
 */
class ExportLossLedgersAction extends ExportRecordsAction
{
    protected function name(): string
    {
        return 'exportLossLedgers';
    }

    /**
     * @return array<int, string>
     */
    protected function definitionKeys(): array
    {
        return ['loss_ledgers'];
    }
}
