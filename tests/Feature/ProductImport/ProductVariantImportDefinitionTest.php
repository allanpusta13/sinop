<?php

declare(strict_types=1);

namespace Tests\Feature\ProductImport;

require_once __DIR__.'/../ImportEngine/Fixtures/import_helpers.php';

use App\Exceptions\DomainRuleViolationException;
use App\Jobs\ProcessImportJob;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Import\ImportService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    actingAsAdmin();
});

if (! function_exists('variantImportColumnMap')) {
    /** @return array<string, string> */
    function variantImportColumnMap(): array
    {
        return [
            'family_id' => 'Family ID',
            'family_name' => 'Family Name',
            'sku' => 'SKU',
            'name' => 'Variant Name',
            'base_unit_name' => 'Base Unit Name',
        ];
    }
}

if (! function_exists('variantImportBarcodeColumnMap')) {
    /** @return array<string, string> */
    function variantImportBarcodeColumnMap(): array
    {
        return [...variantImportColumnMap(), 'barcode' => 'Barcode'];
    }
}

it('resolves families by explicit ID or unambiguous name', function () {
    $familyA = Product::factory()->create(['name' => 'Single Origin Arabica']);

    $file = importTempFile(implode("\n", [
        'Family ID,Family Name,SKU,Variant Name,Base Unit Name',
        "{$familyA->id},,SKU-A1,Arabica Beans 1kg,Bag",
        ',single origin arabica,SKU-A2,Arabica Beans 500g,Bag',
    ]));

    $service = app(ImportService::class);
    $user = auth()->user();
    $columnMap = variantImportColumnMap();

    $preview = $service->preview($file, 'variants.csv', 'products.variants', $columnMap, $user);

    expect($preview['counts'])->toBe(['create' => 2, 'update' => 0, 'reject' => 0, 'total' => 2])
        ->and($preview['rows'][0]['resolved_family_id'])->toBe($familyA->id)
        ->and($preview['rows'][1]['resolved_family_id'])->toBe($familyA->id);

    $result = $service->execute($file, 'variants.csv', 'products.variants', $columnMap, $preview['counts'], false, $user);

    expect($result['counts']['created'])->toBe(2);

    $this->assertDatabaseHas('product_variants', ['sku' => 'SKU-A1', 'product_id' => $familyA->id]);
    $this->assertDatabaseHas('product_variants', ['sku' => 'SKU-A2', 'product_id' => $familyA->id]);
});

it('rejects invalid family references with translation-keyed errors', function () {
    Product::factory()->create(['name' => 'Blend Series', 'category' => 'Retail']);
    Product::factory()->create(['name' => 'Blend Series', 'category' => 'Wholesale']);

    $file = importTempFile(implode("\n", [
        'Family ID,Family Name,SKU,Variant Name,Base Unit Name',
        '99999,,SKU-ERR1,Item 1,Bag',
        ',Unknown Family,SKU-ERR2,Item 2,Bag',
        ',Blend Series,SKU-ERR3,Item 3,Bag',
        ',,SKU-ERR4,Item 4,Bag',
    ]));

    $preview = app(ImportService::class)->preview($file, 'variants.csv', 'products.variants', variantImportColumnMap(), auth()->user());

    expect($preview['counts']['reject'])->toBe(4)
        ->and($preview['rows'][0]['errors'][0])->toBe([
            'key' => 'resources.products.import.errors.family_id_not_found',
            'params' => [':family_id' => '99999'],
        ])
        ->and($preview['rows'][1]['errors'][0])->toBe([
            'key' => 'resources.products.import.errors.family_name_no_match',
            'params' => [':name' => 'Unknown Family'],
        ])
        ->and($preview['rows'][2]['errors'][0])->toBe([
            'key' => 'resources.products.import.errors.family_name_ambiguous',
            'params' => [':name' => 'Blend Series', ':count' => 2],
        ])
        ->and($preview['rows'][3]['errors'][0]['key'])->toBe('resources.products.import.errors.family_reference_missing');
});

it('requires at least one family mapping column', function () {
    $file = importTempFile("SKU,Variant Name,Base Unit Name\nSKU-X,Item,Bag\n");
    $columnMap = ['sku' => 'SKU', 'name' => 'Variant Name', 'base_unit_name' => 'Base Unit Name'];

    try {
        app(ImportService::class)->preview($file, 'variants.csv', 'products.variants', $columnMap, auth()->user());
        $this->fail('Expected ValidationException was not thrown.');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey('columnMap.family_id');
    }
});

it('requires the SKU, variant name and base unit columns to be mapped', function () {
    $file = importTempFile("Family ID,SKU,Variant Name,Base Unit Name\n1,SKU-X,Item,Bag\n");

    expect(fn () => app(ImportService::class)->preview($file, 'variants.csv', 'products.variants', ['family_id' => 'Family ID'], auth()->user()))
        ->toThrow(ValidationException::class);
});

it('rejects rows missing SKU, variant name or base unit with exact error keys', function () {
    $family = Product::factory()->create(['name' => 'Frozen Goods']);

    $file = importTempFile(implode("\n", [
        'Family ID,SKU,Variant Name,Base Unit Name',
        "{$family->id},,Missing SKU,Bag",
        "{$family->id},SKU-NO-NAME,,Bag",
        "{$family->id},SKU-NO-UNIT,No Unit,",
    ]));

    $preview = app(ImportService::class)->preview($file, 'variants.csv', 'products.variants', variantImportColumnMap(), auth()->user());

    expect($preview['counts']['reject'])->toBe(3)
        ->and($preview['rows'][0]['errors'][0]['key'])->toBe('resources.products.import.errors.sku_required')
        ->and($preview['rows'][0]['identifier'])->toBe('[Row 1]')
        ->and($preview['rows'][1]['errors'][0]['key'])->toBe('resources.products.import.errors.variant_name_required')
        ->and($preview['rows'][2]['errors'][0]['key'])->toBe('resources.products.import.errors.base_unit_required');
});

it('matches existing variants by SKU, previews updates with an id identifier, and updates only after confirmation', function () {
    $family = Product::factory()->create(['name' => 'Roast Beans']);
    $existing = ProductVariant::factory()->create([
        'product_id' => $family->id,
        'sku' => 'SKU-EXISTING',
        'name' => 'Original Variant Name',
        'reorder_point' => 5,
    ]);

    $file = importTempFile(implode("\n", [
        'Family ID,SKU,Variant Name,Base Unit Name,Reorder Point',
        "{$family->id},SKU-EXISTING,Updated Variant Name,Bag,25",
        "{$family->id},SKU-NEW,New Variant Item,Box,10",
    ]));

    $columnMap = variantImportColumnMap() + ['reorder_point' => 'Reorder Point'];
    $service = app(ImportService::class);
    $user = auth()->user();

    $preview = $service->preview($file, 'variants.csv', 'products.variants', $columnMap, $user);

    expect($preview['counts'])->toBe(['create' => 1, 'update' => 1, 'reject' => 0, 'total' => 2])
        ->and($preview['has_updates'])->toBeTrue()
        ->and($preview['rows'][0]['status'])->toBe('update')
        ->and($preview['rows'][0]['identifier'])->toBe("SKU-EXISTING | {$existing->id}")
        ->and($preview['rows'][0]['existing_id'])->toBe($existing->id)
        ->and($preview['rows'][1]['status'])->toBe('create')
        ->and($preview['rows'][1]['identifier'])->toBe('SKU-NEW');

    try {
        $service->execute($file, 'variants.csv', 'products.variants', $columnMap, $preview['counts'], false, $user);
        $this->fail('Expected DomainRuleViolationException was not thrown.');
    } catch (DomainRuleViolationException $exception) {
        expect($exception->translationKey())->toBe('errors.import_update_confirmation_required');
    }

    expect($existing->refresh()->name)->toBe('Original Variant Name');

    $result = $service->execute($file, 'variants.csv', 'products.variants', $columnMap, $preview['counts'], true, $user);

    expect($result['counts']['updated'])->toBe(1)
        ->and($result['counts']['created'])->toBe(1)
        ->and($existing->refresh()->name)->toBe('Updated Variant Name')
        ->and($existing->reorder_point)->toBe(25);

    $this->assertDatabaseHas('product_variants', ['sku' => 'SKU-NEW', 'name' => 'New Variant Item', 'reorder_point' => 10]);
});

it('leaves unmapped optional columns untouched on update', function () {
    $family = Product::factory()->create(['name' => 'Roast Beans']);
    $existing = ProductVariant::factory()->create([
        'product_id' => $family->id,
        'sku' => 'SKU-KEEP',
        'name' => 'Old Name',
        'barcode' => '1111111111111',
        'reorder_point' => 7,
        'is_active' => false,
    ]);

    $file = importTempFile("Family ID,SKU,Variant Name,Base Unit Name\n{$family->id},SKU-KEEP,New Name,Bag\n");

    $columnMap = variantImportColumnMap();
    $service = app(ImportService::class);
    $user = auth()->user();

    $preview = $service->preview($file, 'variants.csv', 'products.variants', $columnMap, $user);
    $service->execute($file, 'variants.csv', 'products.variants', $columnMap, $preview['counts'], true, $user);

    $existing->refresh();

    expect($existing->name)->toBe('New Name')
        ->and($existing->barcode)->toBe('1111111111111')
        ->and($existing->reorder_point)->toBe(7)
        ->and($existing->is_active)->toBeFalse();
});

it('stores a blank mapped barcode as null and clears an existing barcode on update', function () {
    $family = Product::factory()->create(['name' => 'Sauces']);
    $existing = ProductVariant::factory()->create([
        'product_id' => $family->id,
        'sku' => 'SKU-CLEAR',
        'name' => 'Old Sauce',
        'barcode' => '9999999999999',
    ]);

    $file = importTempFile(implode("\n", [
        'Family ID,SKU,Barcode,Variant Name,Base Unit Name',
        "{$family->id},SKU-CLEAR,,Updated Sauce,Bottle",
        "{$family->id},SKU-FRESH,,New Sauce,Bottle",
    ]));

    $columnMap = variantImportBarcodeColumnMap();
    $service = app(ImportService::class);
    $user = auth()->user();

    $preview = $service->preview($file, 'variants.csv', 'products.variants', $columnMap, $user);
    $service->execute($file, 'variants.csv', 'products.variants', $columnMap, $preview['counts'], true, $user);

    expect($existing->refresh()->barcode)->toBeNull()
        ->and(ProductVariant::query()->where('sku', 'SKU-FRESH')->first()?->barcode)->toBeNull();
});

it('rejects barcodes already associated with a different SKU', function () {
    $family = Product::factory()->create(['name' => 'Specialty Tea']);
    ProductVariant::factory()->create([
        'product_id' => $family->id,
        'sku' => 'SKU-OTHER',
        'barcode' => '8888888888888',
    ]);

    $file = importTempFile("Family ID,SKU,Barcode,Variant Name,Base Unit Name\n{$family->id},SKU-CONFLICT,8888888888888,Conflicting Item,Box\n");

    $preview = app(ImportService::class)->preview($file, 'variants.csv', 'products.variants', variantImportBarcodeColumnMap(), auth()->user());

    expect($preview['counts']['reject'])->toBe(1)
        ->and($preview['rows'][0]['errors'][0])->toBe([
            'key' => 'resources.products.import.errors.barcode_conflict',
            'params' => [':barcode' => '8888888888888', ':sku' => 'SKU-OTHER'],
        ]);
});

it('rejects a barcode shared by multiple SKUs within the file', function () {
    $family = Product::factory()->create(['name' => 'Snacks']);

    $file = importTempFile(implode("\n", [
        'Family ID,SKU,Barcode,Variant Name,Base Unit Name',
        "{$family->id},SKU-1,7777777777777,Chips,Bag",
        "{$family->id},SKU-2,7777777777777,Crackers,Bag",
    ]));

    $preview = app(ImportService::class)->preview($file, 'variants.csv', 'products.variants', variantImportBarcodeColumnMap(), auth()->user());

    expect($preview['counts']['reject'])->toBe(2)
        ->and($preview['rows'][0]['errors'][0])->toBe([
            'key' => 'resources.products.import.errors.barcode_multiple_in_file',
            'params' => [':barcode' => '7777777777777'],
        ])
        ->and($preview['rows'][1]['errors'][0]['key'])->toBe('resources.products.import.errors.barcode_multiple_in_file');
});

it('rejects duplicate SKUs within the uploaded file', function () {
    $family = Product::factory()->create(['name' => 'Syrups']);

    $file = importTempFile(implode("\n", [
        'Family ID,SKU,Variant Name,Base Unit Name',
        "{$family->id},SKU-DUP,Vanilla Syrup 1L,Bottle",
        "{$family->id},SKU-DUP,Caramel Syrup 1L,Bottle",
        "{$family->id},SKU-UNIQUE,Hazelnut Syrup 1L,Bottle",
    ]));

    $preview = app(ImportService::class)->preview($file, 'variants.csv', 'products.variants', variantImportColumnMap(), auth()->user());

    expect($preview['counts'])->toBe(['create' => 1, 'update' => 0, 'reject' => 2, 'total' => 3])
        ->and($preview['rows'][0]['errors'][0])->toBe([
            'key' => 'resources.products.import.errors.sku_duplicate_in_file',
            'params' => [':sku' => 'SKU-DUP'],
        ]);
});

it('rejects non-integer or negative reorder points and invalid attributes JSON', function () {
    $family = Product::factory()->create(['name' => 'Pantry']);

    $file = importTempFile(implode("\n", [
        'Family ID,SKU,Variant Name,Base Unit Name,Reorder Point,Attributes',
        "{$family->id},SKU-BAD-RO,Item A,Bag,-5,",
        "{$family->id},SKU-BAD-JSON,Item B,Bag,3,not-json",
        "{$family->id},SKU-FRACTIONAL-RO,Item C,Bag,5.5,",
    ]));

    $columnMap = variantImportColumnMap() + [
        'reorder_point' => 'Reorder Point',
        'attributes' => 'Attributes',
    ];

    $preview = app(ImportService::class)->preview($file, 'variants.csv', 'products.variants', $columnMap, auth()->user());

    expect($preview['counts']['reject'])->toBe(3)
        ->and($preview['rows'][0]['errors'][0]['key'])->toBe('resources.products.import.errors.reorder_point_invalid')
        ->and($preview['rows'][1]['errors'][0]['key'])->toBe('resources.products.import.errors.attributes_invalid_json')
        ->and($preview['rows'][2]['errors'][0]['key'])->toBe('resources.products.import.errors.reorder_point_invalid');
});

it('creates variants with decoded attributes, active default and the observer base-unit conversion', function () {
    $family = Product::factory()->create(['name' => 'Spices']);

    $attributesJson = '{"Roast":"Medium"}';
    $file = importTempFile(implode("\n", [
        'Family ID,SKU,Variant Name,Base Unit Name,Attributes',
        "{$family->id},SKU-CINNAMON,Cinnamon Sticks 100g,Pouch,\"".str_replace('"', '""', $attributesJson).'"',
    ]));

    $columnMap = variantImportColumnMap() + ['attributes' => 'Attributes'];
    $service = app(ImportService::class);
    $user = auth()->user();

    $preview = $service->preview($file, 'variants.csv', 'products.variants', $columnMap, $user);
    $result = $service->execute($file, 'variants.csv', 'products.variants', $columnMap, $preview['counts'], false, $user);

    $variant = ProductVariant::query()->where('sku', 'SKU-CINNAMON')->firstOrFail();

    expect($result['counts']['created'])->toBe(1)
        ->and($variant->attributes)->toBe(['Roast' => 'Medium'])
        ->and($variant->is_active)->toBeTrue();

    $this->assertDatabaseHas('product_variant_unit_conversions', [
        'product_variant_id' => $variant->id,
        'unit_name' => 'Pouch',
        'base_unit_ratio' => 1,
    ]);
});

it('applies the active flag from mapped cells', function () {
    $family = Product::factory()->create(['name' => 'Cold Drinks']);
    ProductVariant::factory()->create([
        'product_id' => $family->id,
        'sku' => 'SKU-ACTIVE',
        'name' => 'Cola',
        'is_active' => false,
    ]);

    $file = importTempFile(implode("\n", [
        'Family ID,SKU,Variant Name,Base Unit Name,Active Status',
        "{$family->id},SKU-ACTIVE,Cola Classic,Bottle,",
        "{$family->id},SKU-ZERO,Zero Cola,Bottle,0",
    ]));

    $columnMap = variantImportColumnMap() + ['is_active' => 'Active Status'];
    $service = app(ImportService::class);
    $user = auth()->user();

    $preview = $service->preview($file, 'variants.csv', 'products.variants', $columnMap, $user);
    $result = $service->execute($file, 'variants.csv', 'products.variants', $columnMap, $preview['counts'], true, $user);

    expect($result['counts']['created'])->toBe(1)
        ->and($result['counts']['updated'])->toBe(1)
        ->and(ProductVariant::query()->where('sku', 'SKU-ACTIVE')->first()?->is_active)->toBeTrue()
        ->and(ProductVariant::query()->where('sku', 'SKU-ZERO')->first()?->is_active)->toBeFalse();
});

it('never writes stock movement or price rows', function () {
    $family = Product::factory()->create(['name' => 'Coffee Beans']);

    $attributesJson = '{"Roast":"Medium"}';
    $file = importTempFile(implode("\n", [
        'Family ID,SKU,Barcode,Variant Name,Base Unit Name,Reorder Point,Attributes,Active Status',
        "{$family->id},SKU-NO-LEDGER,,Retail Blend 250g,Bag,5,\"".str_replace('"', '""', $attributesJson).'",1',
    ]));

    $columnMap = variantImportColumnMap() + [
        'barcode' => 'Barcode',
        'reorder_point' => 'Reorder Point',
        'attributes' => 'Attributes',
        'is_active' => 'Active Status',
    ];

    $service = app(ImportService::class);
    $user = auth()->user();

    $preview = $service->preview($file, 'variants.csv', 'products.variants', $columnMap, $user);
    $service->execute($file, 'variants.csv', 'products.variants', $columnMap, $preview['counts'], false, $user);

    $this->assertDatabaseCount('stock_movements', 0);
    $this->assertDatabaseCount('product_variant_prices', 0);
});

it('denies warehouse staff server-side', function () {
    actingAsWarehouseStaff();

    $file = importTempFile("Family ID,SKU,Variant Name,Base Unit Name\n1,SKU-X,Item,Bag\n");

    expect(fn () => app(ImportService::class)->preview($file, 'variants.csv', 'products.variants', variantImportColumnMap(), auth()->user()))
        ->toThrow(AuthorizationException::class);
});

it('aborts when the database changed after the preview was built', function () {
    $family = Product::factory()->create(['name' => 'Juice Family']);

    $file = importTempFile("Family ID,SKU,Variant Name,Base Unit Name\n{$family->id},SKU-RACE,Apple Juice,Bottle\n");

    $columnMap = variantImportColumnMap();
    $service = app(ImportService::class);
    $user = auth()->user();

    $preview = $service->preview($file, 'variants.csv', 'products.variants', $columnMap, $user);

    ProductVariant::factory()->create([
        'product_id' => $family->id,
        'sku' => 'SKU-RACE',
        'name' => 'Created in background',
    ]);

    try {
        $service->execute($file, 'variants.csv', 'products.variants', $columnMap, $preview['counts'], true, $user);
        $this->fail('Expected DomainRuleViolationException was not thrown.');
    } catch (DomainRuleViolationException $exception) {
        expect($exception->translationKey())->toBe('errors.import_counts_mismatch')
            ->and(ProductVariant::query()->where('sku', 'SKU-RACE')->count())->toBe(1);
    }
});

it('queues variant imports above the inline threshold', function () {
    Queue::fake();

    $family = Product::factory()->create(['name' => 'Bulk Family']);

    $lines = ['Family ID,SKU,Variant Name,Base Unit Name'];

    for ($i = 1; $i <= 101; $i++) {
        $lines[] = "{$family->id},SKU-BULK-{$i},Item {$i},Piece";
    }

    $file = importTempFile(implode("\n", $lines));

    $service = app(ImportService::class);
    $user = auth()->user();

    $preview = $service->preview($file, 'variants.csv', 'products.variants', variantImportColumnMap(), $user);
    $result = $service->execute($file, 'variants.csv', 'products.variants', variantImportColumnMap(), $preview['counts'], false, $user);

    expect($result['status'])->toBe('queued')
        ->and(ProductVariant::query()->count())->toBe(0);

    Queue::assertPushed(
        ProcessImportJob::class,
        fn (ProcessImportJob $job): bool => $job->definitionKey === 'products.variants'
            && $job->expectedCounts['create'] === 101
            && $job->userId === (int) $user->getKey(),
    );
});

it('guesses variant column mappings from template headers', function () {
    $service = app(ImportService::class);
    $definition = $service->resolveDefinition('products.variants');

    $guesses = $service->guessMappings($definition, [
        'Family ID (optional)',
        'Family Name',
        'SKU',
        'Variant Name',
        'Base Unit Name',
        'Barcode (optional)',
        'Reorder Point (optional)',
        'Attributes (optional)',
        'Active Status (optional)',
    ]);

    expect($guesses['family_id'])->toBe('Family ID (optional)')
        ->and($guesses['family_name'])->toBe('Family Name')
        ->and($guesses['sku'])->toBe('SKU')
        ->and($guesses['name'])->toBe('Variant Name')
        ->and($guesses['base_unit_name'])->toBe('Base Unit Name')
        ->and($guesses['barcode'])->toBe('Barcode (optional)')
        ->and($guesses['reorder_point'])->toBe('Reorder Point (optional)')
        ->and($guesses['attributes'])->toBe('Attributes (optional)')
        ->and($guesses['is_active'])->toBe('Active Status (optional)');
});
