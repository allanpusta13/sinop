<?php

declare(strict_types=1);

namespace App\Filament\Resources\TransferRequisitions\Actions;

use App\Filament\Support\Exports\ExportRecordsAction;

/**
 * ExportTransferRequisitionsAction — export header action on
 * ListTransferRequisitions (#226). Offers the header, its items and
 * the item revisions; the item and revision files follow whatever the
 * user has filtered on the requisitions table.
 */
class ExportTransferRequisitionsAction extends ExportRecordsAction
{
    protected function name(): string
    {
        return 'exportTransferRequisitions';
    }

    /**
     * @return array<int, string>
     */
    protected function definitionKeys(): array
    {
        return [
            'transfer_requisitions',
            'transfer_requisitions.items',
            'transfer_requisitions.revisions',
        ];
    }
}
