<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

use App\Exceptions\DomainRuleViolationException;
use App\Models\Warehouse;
use App\Services\Import\ImportService;

beforeEach(function () {
    actingAsAdmin();
});

test('executes creates, omitting location only when the column is unmapped and never writing is_active', function () {
    $service = app(ImportService::class);
    $user = auth()->user();

    $file = warehouseImportCsv([
        ['WH-0100', 'North Warehouse', 'Main St. 123'],
        ['WH-0101', 'South Warehouse', ''],
    ]);

    $preview = $service->preview($file, 'warehouses.csv', 'warehouses', warehouseImportColumnMap(), $user);
    $result = $service->execute($file, 'warehouses.csv', 'warehouses', warehouseImportColumnMap(), $preview['counts'], false, $user);

    expect($result['status'])->toBe('completed')
        ->and($result['counts'])->toBe(['created' => 2, 'updated' => 0, 'skipped' => 0, 'rejected' => 0]);

    $first = Warehouse::query()->where('code', 'WH-0100')->sole();
    $second = Warehouse::query()->where('code', 'WH-0101')->sole();

    expect($first->name)->toBe('North Warehouse')
        ->and($first->location)->toBe('Main St. 123')
        ->and($first->is_active)->toBeTrue()
        ->and($second->name)->toBe('South Warehouse')
        ->and($second->location)->toBe('')
        ->and($second->is_active)->toBeTrue();
});

test('confirmed updates change name and location but never code or is_active', function () {
    $warehouse = Warehouse::factory()->create([
        'code' => 'WH-0200',
        'name' => 'Old Name',
        'location' => 'Old Location',
        'is_active' => false,
    ]);

    $service = app(ImportService::class);
    $user = auth()->user();

    $file = warehouseImportCsv([['WH-0200', 'New Name', 'New Location']]);

    $preview = $service->preview($file, 'warehouses.csv', 'warehouses', warehouseImportColumnMap(), $user);
    $result = $service->execute($file, 'warehouses.csv', 'warehouses', warehouseImportColumnMap(), $preview['counts'], true, $user);

    $warehouse->refresh();

    expect($result['counts'])->toBe(['created' => 0, 'updated' => 1, 'skipped' => 0, 'rejected' => 0])
        ->and($warehouse->code)->toBe('WH-0200')
        ->and($warehouse->name)->toBe('New Name')
        ->and($warehouse->location)->toBe('New Location')
        ->and($warehouse->is_active)->toBeFalse();
});

test('unconfirmed updates are blocked by the engine before anything is written', function () {
    Warehouse::factory()->create(['code' => 'WH-0201', 'name' => 'Old Name']);

    $service = app(ImportService::class);
    $user = auth()->user();

    $file = warehouseImportCsv([['WH-0201', 'New Name', 'Somewhere']]);

    $preview = $service->preview($file, 'warehouses.csv', 'warehouses', warehouseImportColumnMap(), $user);

    try {
        $service->execute($file, 'warehouses.csv', 'warehouses', warehouseImportColumnMap(), $preview['counts'], false, $user);
        $this->fail('Expected DomainRuleViolationException was not thrown.');
    } catch (DomainRuleViolationException $exception) {
        expect($exception->translationKey())->toBe('errors.import_update_confirmation_required')
            ->and(Warehouse::query()->where('code', 'WH-0201')->value('name'))->toBe('Old Name');
    }
});

test('unmapped location leaves the stored location untouched on update', function () {
    Warehouse::factory()->create(['code' => 'WH-0202', 'name' => 'Old', 'location' => 'Keep Me']);

    $columnMap = ['code' => 'Code', 'name' => 'Name'];
    $service = app(ImportService::class);
    $user = auth()->user();

    $file = warehouseImportCsv([['WH-0202', 'Renamed', 'Ignored Because Unmapped']]);

    $preview = $service->preview($file, 'warehouses.csv', 'warehouses', $columnMap, $user);
    $result = $service->execute($file, 'warehouses.csv', 'warehouses', $columnMap, $preview['counts'], true, $user);

    $warehouse = Warehouse::query()->where('code', 'WH-0202')->sole();

    expect($result['counts']['updated'])->toBe(1)
        ->and($warehouse->name)->toBe('Renamed')
        ->and($warehouse->location)->toBe('Keep Me');
});

test('a mapped but blank location cell overwrites the stored location on update', function () {
    Warehouse::factory()->create(['code' => 'WH-0203', 'name' => 'Old Name', 'location' => 'Old Location']);

    $service = app(ImportService::class);
    $user = auth()->user();

    $file = warehouseImportCsv([['WH-0203', 'Renamed Warehouse', '']]);

    $preview = $service->preview($file, 'warehouses.csv', 'warehouses', warehouseImportColumnMap(), $user);
    $result = $service->execute($file, 'warehouses.csv', 'warehouses', warehouseImportColumnMap(), $preview['counts'], true, $user);

    $warehouse = Warehouse::query()->where('code', 'WH-0203')->sole();

    expect($result['counts']['updated'])->toBe(1)
        ->and($warehouse->name)->toBe('Renamed Warehouse')
        ->and($warehouse->location)->toBe('');
});

test('rejected rows are never persisted', function () {
    Warehouse::factory()->create(['code' => 'WH-0009', 'name' => 'South Depot']);

    $service = app(ImportService::class);
    $user = auth()->user();

    $file = warehouseImportCsv([
        ['WH-0300', 'South Depot', 'Anywhere'],
        ['WH-0301', 'Fresh Depot', 'Pier 9'],
    ]);

    $preview = $service->preview($file, 'warehouses.csv', 'warehouses', warehouseImportColumnMap(), $user);
    $result = $service->execute($file, 'warehouses.csv', 'warehouses', warehouseImportColumnMap(), $preview['counts'], false, $user);

    expect($result['counts'])->toBe(['created' => 1, 'updated' => 0, 'skipped' => 0, 'rejected' => 1])
        ->and(Warehouse::query()->where('code', 'WH-0300')->exists())->toBeFalse()
        ->and(Warehouse::query()->where('code', 'WH-0301')->exists())->toBeTrue();
});
