<?php

declare(strict_types=1);

require_once __DIR__.'/Fixtures/import_helpers.php';

use Filament\Actions\Action;
use Tests\Feature\ImportEngine\Fixtures\FixtureImportWizardAction;

beforeEach(function () {
    registerFixtureImportDefinition();
});

test('builds a three-step wizard action for its definition', function () {
    $action = FixtureImportWizardAction::make();

    expect($action)->toBeInstanceOf(Action::class)
        ->and($action->isWizard())->toBeTrue()
        ->and($action->getName())->toBe('fixtureImport');
});
