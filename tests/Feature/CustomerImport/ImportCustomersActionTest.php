<?php

declare(strict_types=1);

require_once __DIR__.'/import_helpers.php';

use App\Filament\Resources\Customers\Actions\ImportCustomersAction;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Models\User;
use Filament\Actions\Action;
use Livewire\Livewire;

it('builds a wizard action wired to the customers definition', function () {
    $action = ImportCustomersAction::make();

    expect($action)->toBeInstanceOf(Action::class)
        ->and($action->isWizard())->toBeTrue()
        ->and($action->getName())->toBe('importCustomers')
        ->and($action->getLabel())->toBe(__('resources.customers.actions.import'));
});

it('renders the import action on the ListCustomers page for an authorized admin', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(ListCustomers::class)
        ->assertActionExists('importCustomers');
});

it('hides the import action on the ListCustomers page for unauthorized staff', function () {
    $staff = User::factory()->warehouseStaff()->create();

    Livewire::actingAs($staff)
        ->test(ListCustomers::class)
        ->assertActionHidden('importCustomers');
});

it('serves the CSV template with the localized Name header', function () {
    actingAsAdmin();

    $response = $this->get(route('imports.template', [
        'import' => 'customers',
        'format' => 'csv',
    ]));

    $response->assertOk();
    expect($response->streamedContent())->toContain(__('resources.customers.fields.name'));
});

it('serves the XLSX template', function () {
    actingAsAdmin();

    $response = $this->get(route('imports.template', [
        'import' => 'customers',
        'format' => 'xlsx',
    ]));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

it('forbids template downloads for unauthorized staff', function () {
    actingAsWarehouseStaff();

    $this->get(route('imports.template', [
        'import' => 'customers',
        'format' => 'csv',
    ]))->assertForbidden();
});
