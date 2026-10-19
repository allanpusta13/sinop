<?php

declare(strict_types=1);

namespace Tests\Feature\ImportEngine\Fixtures;

use App\Filament\Support\Wizards\ImportWizardAction;

/**
 * Concrete wizard action over the fixture definition, for structural
 * assertions on the shared engine action.
 */
class FixtureImportWizardAction extends ImportWizardAction
{
    protected function name(): string
    {
        return 'fixtureImport';
    }

    protected function definitionKeys(): array
    {
        return [ProductImportDefinitionFixture::KEY];
    }

    protected function translationKeyPrefix(): string
    {
        return 'resources.products.actions.import';
    }
}
