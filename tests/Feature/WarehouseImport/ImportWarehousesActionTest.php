<?php

declare(strict_types=1);

use App\Filament\Resources\Warehouses\Actions\ImportWarehousesAction;
use App\Filament\Resources\Warehouses\Pages\ListWarehouses;
use App\Models\User;
use Filament\Actions\Action;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('builds a wizard action for the warehouses definition', function () {
    $action = ImportWarehousesAction::make();

    expect($action)->toBeInstanceOf(Action::class)
        ->and($action->isWizard())->toBeTrue()
        ->and($action->getName())->toBe('importWarehouses');
});

test('ListWarehouses header actions include the import action', function () {
    $page = new ListWarehouses;

    $names = array_map(
        static fn (Action $action): string => $action->getName(),
        callProtected($page, 'getHeaderActions'),
    );

    expect($names)->toContain('importWarehouses');
});

test('renders the import wizard action on ListWarehouses for authorized admins', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(ListWarehouses::class)
        ->assertActionExists('importWarehouses', fn (Action $action): bool => $action->isWizard());
});

test('hides the import action from unauthorized warehouse staff', function () {
    $staff = User::factory()->warehouseStaff()->create();

    Livewire::actingAs($staff)
        ->test(ListWarehouses::class)
        ->assertActionHidden('importWarehouses');
});
