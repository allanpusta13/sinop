<?php

declare(strict_types=1);

namespace Tests\Feature\ProductImport;

require_once __DIR__.'/../ImportEngine/Fixtures/import_helpers.php';

use App\Exceptions\DomainRuleViolationException;
use App\Jobs\ProcessImportJob;
use App\Models\Product;
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

if (! function_exists('familyImportColumnMap')) {
    /** @return array<string, string> */
    function familyImportColumnMap(): array
    {
        return ['name' => 'Family Name', 'category' => 'Category'];
    }
}

it('previews family rows with counts and translation-keyed errors', function () {
    Product::factory()->create(['name' => 'Specialty Arabica', 'category' => 'Original']);

    $file = importTempFile(implode("\n", [
        'Family Name,Category',
        '"  specialty arabica  ",New Category',
        'Brand New Family,Beverages',
        'Brand New Family,Second',
        'Unique Family,Other',
        '"",Snacks',
    ]));

    $preview = app(ImportService::class)->preview($file, 'families.csv', 'products.families', familyImportColumnMap(), auth()->user());

    expect($preview['counts'])->toBe(['create' => 1, 'update' => 0, 'reject' => 4, 'total' => 5])
        ->and($preview['has_updates'])->toBeFalse()
        ->and($preview['rows'][0]['status'])->toBe('reject')
        ->and($preview['rows'][0]['identifier'])->toBe('specialty arabica')
        ->and($preview['rows'][0]['errors'][0])->toBe([
            'key' => 'resources.products.import.errors.family_name_exists',
            'params' => [':name' => 'specialty arabica'],
        ])
        ->and($preview['rows'][1]['status'])->toBe('reject')
        ->and($preview['rows'][1]['errors'][0])->toBe([
            'key' => 'resources.products.import.errors.family_duplicate_in_file',
            'params' => [':name' => 'Brand New Family'],
        ])
        ->and($preview['rows'][2]['errors'][0]['key'])->toBe('resources.products.import.errors.family_duplicate_in_file')
        ->and($preview['rows'][3]['status'])->toBe('create')
        ->and($preview['rows'][3]['identifier'])->toBe('Unique Family')
        ->and($preview['rows'][4]['status'])->toBe('reject')
        ->and($preview['rows'][4]['identifier'])->toBe('[Row 5]')
        ->and($preview['rows'][4]['errors'][0]['key'])->toBe('resources.products.import.errors.family_name_required');
});

it('is create-only: an existing family is never updated by name', function () {
    Product::factory()->create(['name' => 'Specialty Arabica', 'category' => 'Original Category']);

    $file = importTempFile("Family Name,Category\nSpecialty Arabica,New Category\nBrand New Family,Beverages\n");

    $service = app(ImportService::class);
    $user = auth()->user();

    $preview = $service->preview($file, 'families.csv', 'products.families', familyImportColumnMap(), $user);
    $result = $service->execute($file, 'families.csv', 'products.families', familyImportColumnMap(), $preview['counts'], false, $user);

    expect($result['status'])->toBe('completed')
        ->and($result['counts']['created'])->toBe(1)
        ->and($result['counts']['updated'])->toBe(0)
        ->and($result['counts']['rejected'])->toBe(1);

    $this->assertDatabaseHas('products', ['name' => 'Specialty Arabica', 'category' => 'Original Category']);
    $this->assertDatabaseHas('products', ['name' => 'Brand New Family', 'category' => 'Beverages']);
});

it('stores a blank category cell as null', function () {
    $file = importTempFile("Family Name,Category\nTea Family,\nHerbal Family,Wellness\n");

    $service = app(ImportService::class);
    $user = auth()->user();

    $preview = $service->preview($file, 'families.csv', 'products.families', familyImportColumnMap(), $user);
    $service->execute($file, 'families.csv', 'products.families', familyImportColumnMap(), $preview['counts'], false, $user);

    $this->assertDatabaseHas('products', ['name' => 'Tea Family', 'category' => null]);
    $this->assertDatabaseHas('products', ['name' => 'Herbal Family', 'category' => 'Wellness']);
});

it('omits an unmapped category column from the row data and stores null', function () {
    $file = importTempFile("Family Name,Category\nTea Family,Herbal\n");

    $service = app(ImportService::class);
    $user = auth()->user();
    $columnMap = ['name' => 'Family Name'];

    $preview = $service->preview($file, 'families.csv', 'products.families', $columnMap, $user);
    $result = $service->execute($file, 'families.csv', 'products.families', $columnMap, $preview['counts'], false, $user);

    expect($preview['rows'][0]['data']['category'])->toBeNull()
        ->and($result['counts']['created'])->toBe(1);
    $this->assertDatabaseHas('products', ['name' => 'Tea Family', 'category' => null]);
});

it('requires the family name column to be mapped', function () {
    $file = importTempFile("Family Name,Category\nRobusta,Beverages\n");

    expect(fn () => app(ImportService::class)->preview($file, 'families.csv', 'products.families', ['category' => 'Category'], auth()->user()))
        ->toThrow(ValidationException::class);
});

it('denies warehouse staff server-side', function () {
    actingAsWarehouseStaff();

    $file = importTempFile("Family Name,Category\nRobusta,Beverages\n");

    expect(fn () => app(ImportService::class)->preview($file, 'families.csv', 'products.families', familyImportColumnMap(), auth()->user()))
        ->toThrow(AuthorizationException::class);
});

it('aborts when the database changed after the preview was built', function () {
    $service = app(ImportService::class);
    $user = auth()->user();
    $file = importTempFile("Family Name,Category\nNew One,A\nNew Two,B\n");

    $preview = $service->preview($file, 'families.csv', 'products.families', familyImportColumnMap(), $user);

    Product::factory()->create(['name' => 'New One']);

    try {
        $service->execute($file, 'families.csv', 'products.families', familyImportColumnMap(), $preview['counts'], false, $user);
        $this->fail('Expected DomainRuleViolationException was not thrown.');
    } catch (DomainRuleViolationException $exception) {
        expect($exception->translationKey())->toBe('errors.import_counts_mismatch')
            ->and(Product::query()->where('name', 'New Two')->exists())->toBeFalse();
    }
});

it('queues family imports above the inline threshold', function () {
    Queue::fake();

    $lines = ['Family Name,Category'];

    for ($i = 1; $i <= 101; $i++) {
        $lines[] = "Family {$i},Beverages";
    }

    $file = importTempFile(implode("\n", $lines));

    $service = app(ImportService::class);
    $user = auth()->user();

    $preview = $service->preview($file, 'families.csv', 'products.families', familyImportColumnMap(), $user);
    $result = $service->execute($file, 'families.csv', 'products.families', familyImportColumnMap(), $preview['counts'], false, $user);

    expect($result['status'])->toBe('queued')
        ->and(Product::query()->count())->toBe(0);

    Queue::assertPushed(
        ProcessImportJob::class,
        fn (ProcessImportJob $job): bool => $job->definitionKey === 'products.families'
            && $job->expectedCounts['create'] === 101
            && $job->userId === (int) $user->getKey(),
    );
});

it('guesses family column mappings from template headers', function () {
    $service = app(ImportService::class);
    $definition = $service->resolveDefinition('products.families');

    $guesses = $service->guessMappings($definition, ['Family Name', 'Family Category (optional)']);

    expect($guesses['name'])->toBe('Family Name')
        ->and($guesses['category'])->toBe('Family Category (optional)');
});
