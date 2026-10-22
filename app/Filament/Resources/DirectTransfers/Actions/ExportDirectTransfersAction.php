<?php

declare(strict_types=1);

namespace App\Filament\Resources\DirectTransfers\Actions;

use App\Filament\Support\Exports\ExportRecordsAction;

/**
 * ExportDirectTransfersAction — export header action on
 * ListDirectTransfers (#226). Offers the header and its items; the
 * item file follows whatever the user has filtered on the direct
 * transfers table.
 */
class ExportDirectTransfersAction extends ExportRecordsAction
{
    protected function name(): string
    {
        return 'exportDirectTransfers';
    }

    /**
     * @return array<int, string>
     */
    protected function definitionKeys(): array
    {
        return [
            'direct_transfers',
            'direct_transfers.items',
        ];
    }
}
