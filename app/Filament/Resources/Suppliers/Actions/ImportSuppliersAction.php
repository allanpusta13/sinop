<?php

declare(strict_types=1);

namespace App\Filament\Resources\Suppliers\Actions;

use App\Filament\Support\Wizards\ImportWizardAction;

/**
 * ImportSuppliersAction — the shared import wizard wired to the
 * create-only `suppliers` definition (issue #223).
 */
class ImportSuppliersAction extends ImportWizardAction
{
    protected function name(): string
    {
        return 'importSuppliers';
    }

    /**
     * @return array<int, string>
     */
    protected function definitionKeys(): array
    {
        return ['suppliers'];
    }

    protected function translationKeyPrefix(): string
    {
        return 'resources.suppliers.actions.import';
    }
}
