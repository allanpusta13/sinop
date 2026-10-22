<?php

declare(strict_types=1);

require_once __DIR__.'/import_helpers.php';

use App\Filament\Resources\Suppliers\Actions\ImportSuppliersAction;
use App\Filament\Resources\Suppliers\Pages\ListSuppliers;
use App\Models\User;
use Filament\Actions\Action;
use Livewire\Livewire;

it('builds a wizard action wired to the suppliers definition', function () {
    $action = ImportSuppliersAction::make();

    expect($action)->toBeInstanceOf(Action::class)
        ->and($action->isWizard())->toBeTrue()
        ->and($action->getName())->toBe('importSuppliers')
        ->and($action->getLabel())->toBe(__('resources.suppliers.actions.import'));
});

it('renders the import action on the ListSuppliers page for an authorized admin', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(ListSuppliers::class)
        ->assertActionExists('importSuppliers');
});

it('hides the import action on the ListSuppliers page for unauthorized staff', function () {
    $staff = User::factory()->warehouseStaff()->create();

    Livewire::actingAs($staff)
        ->test(ListSuppliers::class)
        ->assertActionHidden('importSuppliers');
});

it('serves the CSV template with the localized Name header', function () {
    actingAsAdmin();

    $response = $this->get(route('imports.template', [
        'import' => 'suppliers',
        'format' => 'csv',
    ]));

    $response->assertOk();
    expect($response->streamedContent())->toContain(__('resources.suppliers.fields.name'));
});

it('serves the XLSX template', function () {
    actingAsAdmin();

    $response = $this->get(route('imports.template', [
        'import' => 'suppliers',
        'format' => 'xlsx',
    ]));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

it('forbids template downloads for unauthorized staff', function () {
    actingAsWarehouseStaff();

    $this->get(route('imports.template', [
        'import' => 'suppliers',
        'format' => 'csv',
    ]))->assertForbidden();
});
