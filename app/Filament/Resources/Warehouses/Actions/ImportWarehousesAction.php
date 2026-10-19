<?php

declare(strict_types=1);

namespace App\Filament\Resources\Warehouses\Actions;

use App\Filament\Support\Wizards\ImportWizardAction;

/**
 * ImportWarehousesAction — warehouse import wizard on ListWarehouses,
 * backed by the shared import engine (issue #220, registry key
 * "warehouses"). Upload + template download, column mapping and a
 * frozen preview with explicit update confirmation all come from the
 * shared base.
 */
class ImportWarehousesAction extends ImportWizardAction
{
    protected function name(): string
    {
        return 'importWarehouses';
    }

    /**
     * @return array<int, string>
     */
    protected function definitionKeys(): array
    {
        return ['warehouses'];
    }

    protected function translationKeyPrefix(): string
    {
        return 'resources.warehouses.actions.import';
    }
}
