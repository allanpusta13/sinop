<?php

declare(strict_types=1);

namespace App\Filament\Resources\Customers\Actions;

use App\Filament\Support\Wizards\ImportWizardAction;

/**
 * ImportCustomersAction — the shared import wizard wired to the
 * create-only `customers` definition (issue #223).
 */
class ImportCustomersAction extends ImportWizardAction
{
    protected function name(): string
    {
        return 'importCustomers';
    }

    /**
     * @return array<int, string>
     */
    protected function definitionKeys(): array
    {
        return ['customers'];
    }

    protected function translationKeyPrefix(): string
    {
        return 'resources.customers.actions.import';
    }
}
