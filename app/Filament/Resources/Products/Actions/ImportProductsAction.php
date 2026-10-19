<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Actions;

use App\Filament\Support\Wizards\ImportWizardAction;

/**
 * ImportProductsAction — ListProducts entry point for the shared
 * import wizard, covering product families (create-only) and product
 * variants (create + confirmed updates). Row classification and
 * persistence live in the "products.families" / "products.variants"
 * import definitions (issue #219).
 */
class ImportProductsAction extends ImportWizardAction
{
    protected function name(): string
    {
        return 'importProducts';
    }

    /**
     * @return array<int, string>
     */
    protected function definitionKeys(): array
    {
        return ['products.families', 'products.variants'];
    }

    protected function translationKeyPrefix(): string
    {
        return 'resources.products.actions.import';
    }
}
