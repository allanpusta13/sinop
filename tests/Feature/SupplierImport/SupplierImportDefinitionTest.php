<?php

declare(strict_types=1);

require_once __DIR__.'/import_helpers.php';

use App\Exceptions\DomainRuleViolationException;
use App\Models\Supplier;
use App\Services\Import\Definitions\SupplierImportDefinition;
use App\Services\Import\ImportService;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function () {
    actingAsAdmin();
});

/**
 * @param  array<int, array<string, mixed>>  $data
 * @return array<int, array{row_number: int, data: array<string, mixed>}>
 */
function supplierImportRows(array $data): array
{
    return array_map(
        static fn (array $row, int $index): array => ['row_number' => $index + 1, 'data' => $row],
        $data,
        array_keys($data),
    );
}

test('classifies the full preview matrix with exact counts and error keys', function () {
    $existing = Supplier::factory()->create(['name' => 'Acme Trading']);
    $deleted = createSoftDeletedSupplier('Ghost Ltd');

    $result = app(SupplierImportDefinition::class)->validate(supplierImportRows([
        ['name' => 'Globex', 'contact_person' => 'Jane Doe'],
        ['name' => ''],
        ['name' => 'globex'],
        ['name' => 'ACME  Trading'],
        ['name' => 'Acme Trading'],
        ['name' => 'Ghost Ltd'],
        ['name' => 'Fresh One', 'phone' => '+1 555 0100'],
    ]));

    expect($result['counts'])->toBe(['create' => 2, 'update' => 0, 'reject' => 5, 'total' => 7])
        ->and($result['rows'][0]['status'])->toBe('create')
        ->and($result['rows'][0]['identifier'])->toBe('Globex')
        ->and($result['rows'][0]['errors'])->toBe([])
        ->and($result['rows'][1]['status'])->toBe('reject')
        ->and($result['rows'][1]['identifier'])->toBe('-')
        ->and($result['rows'][1]['errors'])->toBe([
            ['key' => 'resources.suppliers.import.errors.name_required', 'params' => []],
        ])
        ->and($result['rows'][2]['status'])->toBe('reject')
        ->and($result['rows'][2]['errors'])->toBe([
            ['key' => 'resources.suppliers.import.errors.duplicate_name_in_file', 'params' => [':name' => 'globex']],
        ])
        ->and($result['rows'][3]['status'])->toBe('reject')
        ->and($result['rows'][3]['identifier'])->toBe("existing id:{$existing->getKey()}")
        ->and($result['rows'][3]['errors'])->toBe([
            ['key' => 'resources.suppliers.import.errors.possible_match', 'params' => [':name' => 'ACME  Trading', ':id' => $existing->getKey()]],
        ])
        ->and($result['rows'][4]['status'])->toBe('reject')
        ->and($result['rows'][4]['errors'])->toBe([
            ['key' => 'resources.suppliers.import.errors.duplicate_name_in_file', 'params' => [':name' => 'Acme Trading']],
        ])
        ->and($result['rows'][5]['status'])->toBe('reject')
        ->and($result['rows'][5]['identifier'])->toBe("existing id:{$deleted->getKey()}")
        ->and($result['rows'][5]['errors'])->toBe([
            ['key' => 'resources.suppliers.import.errors.possible_match', 'params' => [':name' => 'Ghost Ltd', ':id' => $deleted->getKey()]],
        ])
        ->and($result['rows'][6]['status'])->toBe('create');
});

test('previews a CSV file through the engine with matching counts', function () {
    Supplier::factory()->create(['name' => 'Acme Trading']);

    $file = supplierImportTempCsv([
        ['Globex', 'Jane Doe', '+1 555 0100', 'sales@globex.example', '123 Main St'],
        ['Acme Trading', '', '', '', ''],
        ['', 'No Name', '', '', ''],
    ]);

    $preview = app(ImportService::class)->preview($file, 'suppliers.csv', 'suppliers', supplierImportColumnMap(), auth()->user());

    expect($preview['counts'])->toBe(['create' => 1, 'update' => 0, 'reject' => 2, 'total' => 3])
        ->and($preview['has_updates'])->toBeFalse()
        ->and($preview['rows'][0]['status'])->toBe('create')
        ->and($preview['rows'][1]['status'])->toBe('reject')
        ->and($preview['rows'][2]['status'])->toBe('reject');
});

test('persists creates with mapped fields only', function () {
    $file = supplierImportTempCsv([
        ['Globex', 'Jane Doe', '+1 555 0100', 'sales@globex.example', '123 Main St'],
        ['Bare Corp', '', '', '', ''],
    ]);

    $service = app(ImportService::class);
    $user = auth()->user();
    $columnMap = supplierImportColumnMap();

    $preview = $service->preview($file, 'suppliers.csv', 'suppliers', $columnMap, $user);
    $result = $service->execute($file, 'suppliers.csv', 'suppliers', $columnMap, $preview['counts'], false, $user);

    expect($result['status'])->toBe('completed')
        ->and($result['counts']['created'])->toBe(2)
        ->and($result['counts']['updated'])->toBe(0)
        ->and($result['counts']['rejected'])->toBe(0);

    $this->assertDatabaseHas('suppliers', [
        'name' => 'Globex',
        'contact_person' => 'Jane Doe',
        'phone' => '+1 555 0100',
        'email' => 'sales@globex.example',
        'address' => '123 Main St',
    ]);
    $this->assertDatabaseHas('suppliers', [
        'name' => 'Bare Corp',
        'contact_person' => '',
        'phone' => '',
        'email' => '',
        'address' => '',
    ]);
    expect(Supplier::query()->count())->toBe(2);
});

test('omits unmapped optional columns from the create payload', function () {
    $file = supplierImportTempCsv([
        ['Globex', 'Jane Doe', '+1 555 0100', 'sales@globex.example', '123 Main St'],
    ]);

    $service = app(ImportService::class);
    $user = auth()->user();
    $columnMap = ['name' => 'Name'];

    $preview = $service->preview($file, 'suppliers.csv', 'suppliers', $columnMap, $user);
    $result = $service->execute($file, 'suppliers.csv', 'suppliers', $columnMap, $preview['counts'], false, $user);

    expect($result['counts']['created'])->toBe(1);

    $supplier = Supplier::query()->firstOrFail();

    expect($supplier->name)->toBe('Globex')
        ->and($supplier->contact_person)->toBeNull()
        ->and($supplier->phone)->toBeNull()
        ->and($supplier->email)->toBeNull()
        ->and($supplier->address)->toBeNull()
        ->and($supplier->is_active)->toBeTrue();
});

test('blocks execution on database drift with a counts mismatch', function () {
    $file = supplierImportTempCsv([
        ['New One', '', '', '', ''],
        ['New Two', '', '', '', ''],
    ]);

    $service = app(ImportService::class);
    $user = auth()->user();
    $columnMap = supplierImportColumnMap();

    $preview = $service->preview($file, 'suppliers.csv', 'suppliers', $columnMap, $user);

    Supplier::factory()->create(['name' => 'New One']);

    try {
        $service->execute($file, 'suppliers.csv', 'suppliers', $columnMap, $preview['counts'], false, $user);
        $this->fail('Expected DomainRuleViolationException was not thrown.');
    } catch (DomainRuleViolationException $exception) {
        expect($exception->translationKey())->toBe('errors.import_counts_mismatch')
            ->and(Supplier::query()->where('name', 'New Two')->exists())->toBeFalse();
    }
});

test('rejects possible matches at execution time so nothing is written for them', function () {
    Supplier::factory()->create(['name' => 'Acme Trading']);

    $file = supplierImportTempCsv([
        ['Acme Trading', '', '', '', ''],
        ['Fresh One', '', '', '', ''],
    ]);

    $service = app(ImportService::class);
    $user = auth()->user();
    $columnMap = supplierImportColumnMap();

    $preview = $service->preview($file, 'suppliers.csv', 'suppliers', $columnMap, $user);
    $result = $service->execute($file, 'suppliers.csv', 'suppliers', $columnMap, $preview['counts'], false, $user);

    expect($result['counts']['created'])->toBe(1)
        ->and($result['counts']['rejected'])->toBe(1)
        ->and(Supplier::query()->where('name', 'Acme Trading')->count())->toBe(1)
        ->and(Supplier::query()->where('name', 'Fresh One')->exists())->toBeTrue()
        ->and(Supplier::query()->count())->toBe(2);
});

test('previews are blocked server-side for unauthorized users', function () {
    actingAsWarehouseStaff();

    $file = supplierImportTempCsv([['Robusta', '', '', '', '']]);

    expect(fn () => app(ImportService::class)->preview($file, 'suppliers.csv', 'suppliers', supplierImportColumnMap(), auth()->user()))
        ->toThrow(AuthorizationException::class);
});

test('in-file duplicate names are rejected end to end at execution time', function () {
    $file = supplierImportTempCsv([
        ['Fresh One', '', '', '', ''],
        ['fresh   one', '', '', '', ''],
        ['Fresh Two', '', '', '', ''],
    ]);

    $service = app(ImportService::class);
    $user = auth()->user();
    $columnMap = supplierImportColumnMap();

    $preview = $service->preview($file, 'suppliers.csv', 'suppliers', $columnMap, $user);
    $result = $service->execute($file, 'suppliers.csv', 'suppliers', $columnMap, $preview['counts'], false, $user);

    expect($result['counts']['created'])->toBe(2)
        ->and($result['counts']['rejected'])->toBe(1)
        ->and(Supplier::query()->where('name', 'Fresh One')->count())->toBe(1)
        ->and(Supplier::query()->where('name', 'Fresh Two')->exists())->toBeTrue();
});

test('execution is blocked server-side for unauthorized users', function () {
    actingAsWarehouseStaff();

    $file = supplierImportTempCsv([['Robusta', '', '', '', '']]);

    expect(fn () => app(ImportService::class)->execute($file, 'suppliers.csv', 'suppliers', supplierImportColumnMap(), ['create' => 1, 'update' => 0, 'reject' => 0, 'total' => 1], false, auth()->user()))
        ->toThrow(AuthorizationException::class);
});
