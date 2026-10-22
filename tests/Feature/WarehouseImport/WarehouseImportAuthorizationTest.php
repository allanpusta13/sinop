<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

use App\Enums\UserRole;
use App\Models\User;
use App\Services\Import\Definitions\WarehouseImportDefinition;
use App\Services\Import\ImportService;
use Illuminate\Auth\Access\AuthorizationException;

test('previews are blocked server-side for users the warehouse policy denies', function () {
    $user = actingAsWarehouseStaff();

    $file = warehouseImportCsv([['WH-0300', 'New Warehouse', '']]);

    expect(fn () => app(ImportService::class)->preview(
        $file,
        'warehouses.csv',
        'warehouses',
        warehouseImportColumnMap(),
        $user,
    ))->toThrow(AuthorizationException::class);
});

test('authorize() follows the warehouse create policy', function () {
    $definition = app(WarehouseImportDefinition::class);

    $staff = actingAsWarehouseStaff();

    expect(fn () => $definition->authorize($staff))->toThrow(AuthorizationException::class)
        ->and($definition->can($staff))->toBeFalse();

    $auditor = actingAsAuditor();

    expect(fn () => $definition->authorize($auditor))->toThrow(AuthorizationException::class)
        ->and($definition->can($auditor))->toBeFalse();

    $admin = actingAsAdmin();

    $definition->authorize($admin);

    expect($definition->can($admin))->toBeTrue();
});

test('authorize() checks the given user rather than the session, so queued jobs re-authorize correctly', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin->value]);

    auth()->logout();

    $definition = app(WarehouseImportDefinition::class);

    $definition->authorize($admin);

    expect($definition->can($admin))->toBeTrue();
});
