<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

use App\Models\Warehouse;
use App\Services\Import\Definitions\WarehouseImportDefinition;
use App\Services\Import\ImportService;

beforeEach(function () {
    actingAsAdmin();
});

test('classifies creates, code updates, in-file duplicates, name conflicts and blank required cells', function () {
    $existing = Warehouse::factory()->create(['code' => 'WH-0001', 'name' => 'North Warehouse']);
    Warehouse::factory()->create(['code' => 'WH-0009', 'name' => 'South Depot']);

    $file = warehouseImportCsv([
        ['WH-0001', 'North Warehouse Renamed', 'New Address'],
        ['WH-0002', 'West Depot', 'Pier 4'],
        ['WH-0002', 'West Depot Again', 'Pier 5'],
        ['WH-0003', 'South Depot', 'Anywhere'],
        ['', 'No Code', 'Somewhere'],
        ['WH-0004', '', 'Somewhere'],
    ]);

    $preview = app(ImportService::class)->preview(
        $file,
        'warehouses.csv',
        'warehouses',
        warehouseImportColumnMap(),
        auth()->user(),
    );

    expect($preview['counts'])->toBe(['create' => 1, 'update' => 1, 'reject' => 4, 'total' => 6])
        ->and($preview['has_updates'])->toBeTrue();

    $rows = $preview['rows'];

    expect($rows[0]['status'])->toBe('update')
        ->and($rows[0]['identifier'])->toBe("WH-0001 | id {$existing->getKey()}")
        ->and($rows[0]['existing_id'])->toBe((int) $existing->getKey())
        ->and($rows[0]['errors'])->toBe([])
        ->and($rows[1]['status'])->toBe('create')
        ->and($rows[1]['identifier'])->toBe('WH-0002')
        ->and($rows[2]['status'])->toBe('reject')
        ->and($rows[2]['errors'])->toBe([[
            'key' => 'resources.warehouses.import.errors.duplicate_code_in_file',
            'params' => ['code' => 'WH-0002'],
        ]])
        ->and($rows[3]['status'])->toBe('reject')
        ->and($rows[3]['errors'])->toBe([[
            'key' => 'resources.warehouses.import.errors.name_conflict',
            'params' => ['code' => 'WH-0003', 'name' => 'South Depot', 'count' => 1],
        ]])
        ->and($rows[4]['status'])->toBe('reject')
        ->and($rows[4]['identifier'])->toBe('-')
        ->and($rows[4]['errors'])->toBe([[
            'key' => 'resources.warehouses.import.errors.code_required',
            'params' => [],
        ]])
        ->and($rows[5]['status'])->toBe('reject')
        ->and($rows[5]['errors'])->toBe([[
            'key' => 'resources.warehouses.import.errors.name_required',
            'params' => [],
        ]]);
});

test('matches existing codes case-insensitively for the update path', function () {
    $existing = Warehouse::factory()->create(['code' => 'WH-0010', 'name' => 'East Depot']);

    $definition = app(WarehouseImportDefinition::class);

    $result = $definition->validate([
        ['row_number' => 1, 'data' => ['code' => 'wh-0010', 'name' => 'East Depot Renamed', 'location' => '']],
    ]);

    expect($result['counts'])->toBe(['create' => 0, 'update' => 1, 'reject' => 0, 'total' => 1])
        ->and($result['rows'][0]['status'])->toBe('update')
        ->and($result['rows'][0]['existing_id'])->toBe((int) $existing->getKey());
});

test('rejects a create whose normalized name exists under any other code and reports every match', function () {
    Warehouse::factory()->create(['code' => 'A-1', 'name' => 'Main Store']);
    Warehouse::factory()->create(['code' => 'A-2', 'name' => '  main store  ']);

    $definition = app(WarehouseImportDefinition::class);

    $result = $definition->validate([
        ['row_number' => 1, 'data' => ['code' => 'B-1', 'name' => 'MAIN STORE', 'location' => '']],
    ]);

    expect($result['counts'])->toBe(['create' => 0, 'update' => 0, 'reject' => 1, 'total' => 1])
        ->and($result['rows'][0]['status'])->toBe('reject')
        ->and($result['rows'][0]['errors'])->toBe([[
            'key' => 'resources.warehouses.import.errors.name_conflict',
            'params' => ['code' => 'B-1', 'name' => 'MAIN STORE', 'count' => 2],
        ]]);
});

test('rejects the second and later occurrences of a code even when it matches an existing warehouse', function () {
    Warehouse::factory()->create(['code' => 'WH-0020', 'name' => 'Twin Depot']);

    $definition = app(WarehouseImportDefinition::class);

    $result = $definition->validate([
        ['row_number' => 1, 'data' => ['code' => 'WH-0020', 'name' => 'Twin Depot', 'location' => '']],
        ['row_number' => 2, 'data' => ['code' => 'WH-0020', 'name' => 'Twin Depot Again', 'location' => '']],
    ]);

    expect($result['counts'])->toBe(['create' => 0, 'update' => 1, 'reject' => 1, 'total' => 2])
        ->and($result['rows'][1]['status'])->toBe('reject')
        ->and($result['rows'][1]['errors'])->toBe([[
            'key' => 'resources.warehouses.import.errors.duplicate_code_in_file',
            'params' => ['code' => 'WH-0020'],
        ]]);
});

test('rejects in-file duplicate codes case-insensitively', function () {
    $definition = app(WarehouseImportDefinition::class);

    $result = $definition->validate([
        ['row_number' => 1, 'data' => ['code' => 'WH-0021', 'name' => 'North Depot', 'location' => '']],
        ['row_number' => 2, 'data' => ['code' => 'wh-0021', 'name' => 'South Depot', 'location' => '']],
    ]);

    expect($result['counts'])->toBe(['create' => 1, 'update' => 0, 'reject' => 1, 'total' => 2])
        ->and($result['rows'][0]['status'])->toBe('create')
        ->and($result['rows'][1]['status'])->toBe('reject')
        ->and($result['rows'][1]['errors'])->toBe([[
            'key' => 'resources.warehouses.import.errors.duplicate_code_in_file',
            'params' => ['code' => 'wh-0021'],
        ]]);
});

test('collapses internal whitespace when comparing names for conflicts', function () {
    Warehouse::factory()->create(['code' => 'A-5', 'name' => 'Main Store']);

    $definition = app(WarehouseImportDefinition::class);

    $result = $definition->validate([
        ['row_number' => 1, 'data' => ['code' => 'B-5', 'name' => 'Main   Store', 'location' => '']],
    ]);

    expect($result['counts'])->toBe(['create' => 0, 'update' => 0, 'reject' => 1, 'total' => 1])
        ->and($result['rows'][0]['status'])->toBe('reject')
        ->and($result['rows'][0]['errors'])->toBe([[
            'key' => 'resources.warehouses.import.errors.name_conflict',
            'params' => ['code' => 'B-5', 'name' => 'Main   Store', 'count' => 1],
        ]]);
});

test('describes itself from the warehouse model label and exposes its columns in template order', function () {
    $definition = app(WarehouseImportDefinition::class);

    expect($definition->key())->toBe('warehouses')
        ->and($definition->labelKey())->toBe('resources.warehouses.model.singular')
        ->and(array_column($definition->columns(), 'key'))->toBe(['code', 'name', 'location']);
});
