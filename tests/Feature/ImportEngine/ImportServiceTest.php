<?php

declare(strict_types=1);

require_once __DIR__.'/Fixtures/import_helpers.php';

use App\Exceptions\DomainRuleViolationException;
use App\Jobs\ProcessImportJob;
use App\Models\Product;
use App\Services\Import\ImportService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\Feature\ImportEngine\Fixtures\ProductImportDefinitionFixture;

beforeEach(function () {
    registerFixtureImportDefinition();
    Storage::fake('local');
    actingAsAdmin();
});

/** @return array<string, string|int|null> */
function fixtureColumnMap(): array
{
    return ['name' => 'Name', 'category' => 'Category'];
}

test('previews create, update and reject rows with counts', function () {
    Product::factory()->create(['name' => 'Arabica']);

    $file = importFixtureCsv([
        ['Arabica', 'Updated Category'],
        ['Robusta', 'Beverages'],
        ['Arabica', 'Duplicate'],
        ['', 'No name'],
    ]);

    $preview = app(ImportService::class)->preview($file, 'products.csv', 'fixture.products', fixtureColumnMap(), auth()->user());

    expect($preview['counts'])->toBe(['create' => 1, 'update' => 1, 'reject' => 2, 'total' => 4])
        ->and($preview['has_updates'])->toBeTrue()
        ->and($preview['rows'][0]['status'])->toBe('update')
        ->and($preview['rows'][1]['status'])->toBe('create')
        ->and($preview['rows'][2]['status'])->toBe('reject')
        ->and($preview['rows'][3]['status'])->toBe('reject');
});

test('previews are blocked server-side for unauthorized users', function () {
    actingAsWarehouseStaff();

    $file = importFixtureCsv([['Robusta', 'Beverages']]);

    expect(fn () => app(ImportService::class)->preview($file, 'products.csv', 'fixture.products', fixtureColumnMap(), auth()->user()))
        ->toThrow(AuthorizationException::class);
});

test('requires every required column to be mapped', function () {
    $file = importFixtureCsv([['Robusta', 'Beverages']]);

    expect(fn () => app(ImportService::class)->preview($file, 'products.csv', 'fixture.products', [], auth()->user()))
        ->toThrow(ValidationException::class);
});

test('executes creates inline below the threshold', function () {
    $service = app(ImportService::class);
    $user = auth()->user();
    $file = importFixtureCsv([
        ['Robusta', 'Beverages'],
        ['Liberica', 'Teas'],
    ]);

    $preview = $service->preview($file, 'products.csv', 'fixture.products', fixtureColumnMap(), $user);
    $result = $service->execute($file, 'products.csv', 'fixture.products', fixtureColumnMap(), $preview['counts'], false, $user);

    expect($result['status'])->toBe('completed')
        ->and($result['counts']['created'])->toBe(2)
        ->and(Product::query()->where('name', 'Robusta')->exists())->toBeTrue()
        ->and(Product::query()->where('name', 'Liberica')->exists())->toBeTrue();
});

test('executes confirmed updates', function () {
    Product::factory()->create(['name' => 'Arabica', 'category' => 'Beverages']);

    $service = app(ImportService::class);
    $user = auth()->user();
    $file = importFixtureCsv([['Arabica', 'Specialty Coffees']]);

    $preview = $service->preview($file, 'products.csv', 'fixture.products', fixtureColumnMap(), $user);
    $result = $service->execute($file, 'products.csv', 'fixture.products', fixtureColumnMap(), $preview['counts'], true, $user);

    expect($result['counts']['updated'])->toBe(1)
        ->and(Product::query()->where('name', 'Arabica')->value('category'))->toBe('Specialty Coffees');
});

test('refuses updates without the explicit confirmation', function () {
    Product::factory()->create(['name' => 'Arabica']);

    $service = app(ImportService::class);
    $user = auth()->user();
    $file = importFixtureCsv([['Arabica', 'Specialty Coffees']]);

    $preview = $service->preview($file, 'products.csv', 'fixture.products', fixtureColumnMap(), $user);

    try {
        $service->execute($file, 'products.csv', 'fixture.products', fixtureColumnMap(), $preview['counts'], false, $user);
        $this->fail('Expected DomainRuleViolationException was not thrown.');
    } catch (DomainRuleViolationException $exception) {
        expect($exception->translationKey())->toBe('errors.import_update_confirmation_required')
            ->and(Product::query()->where('name', 'Arabica')->value('category'))->not->toBe('Specialty Coffees');
    }
});

test('aborts when the database changed after the preview was built', function () {
    $service = app(ImportService::class);
    $user = auth()->user();
    $file = importFixtureCsv([
        ['New One', 'A'],
        ['New Two', 'B'],
    ]);

    $preview = $service->preview($file, 'products.csv', 'fixture.products', fixtureColumnMap(), $user);

    Product::factory()->create(['name' => 'New One']);

    try {
        $service->execute($file, 'products.csv', 'fixture.products', fixtureColumnMap(), $preview['counts'], false, $user);
        $this->fail('Expected DomainRuleViolationException was not thrown.');
    } catch (DomainRuleViolationException $exception) {
        expect($exception->translationKey())->toBe('errors.import_counts_mismatch')
            ->and(Product::query()->where('name', 'New Two')->exists())->toBeFalse();
    }
});

test('leaves unmapped optional columns untouched on update', function () {
    Product::factory()->create(['name' => 'Arabica', 'category' => 'Beverages']);

    $service = app(ImportService::class);
    $user = auth()->user();
    $file = importFixtureCsv([['Arabica', 'ignored-if-unmapped']]);

    $preview = $service->preview($file, 'products.csv', 'fixture.products', ['name' => 'Name'], $user);
    $result = $service->execute($file, 'products.csv', 'fixture.products', ['name' => 'Name'], $preview['counts'], true, $user);

    expect($result['counts']['updated'])->toBe(1)
        ->and(Product::query()->where('name', 'Arabica')->value('category'))->toBe('Beverages');
});

test('queues imports above the inline row threshold', function () {
    Queue::fake();

    $service = app(ImportService::class);
    $user = auth()->user();

    $pairs = [];

    for ($i = 1; $i <= 101; $i++) {
        $pairs[] = ["Product {$i}", 'Beverages'];
    }

    $file = importFixtureCsv($pairs);
    $preview = $service->preview($file, 'products.csv', 'fixture.products', fixtureColumnMap(), $user);
    $result = $service->execute($file, 'products.csv', 'fixture.products', fixtureColumnMap(), $preview['counts'], false, $user);

    expect($result['status'])->toBe('queued')
        ->and(Product::query()->count())->toBe(0);

    Queue::assertPushed(
        ProcessImportJob::class,
        fn (ProcessImportJob $job): bool => $job->definitionKey === 'fixture.products'
            && $job->expectedCounts['create'] === 101
            && $job->userId === (int) $user->getKey()
            && $job->connection === config('queue.import_connection'),
    );
});

test('maps a duplicate-key write conflict onto the counts-mismatch flow', function () {
    $user = auth()->user();
    $file = importFixtureCsv([['Robusta', 'Beverages']]);

    // A concurrent writer inserted the same row between our validate()
    // and persist(): the transaction rolled back (no writes), and the
    // user is asked to review the refreshed counts instead.
    ProductImportDefinitionFixture::$failPersistWithDuplicateKey = true;

    try {
        app(ImportService::class)->executeTransactional(
            $file,
            'products.csv',
            'fixture.products',
            fixtureColumnMap(),
            ['create' => 1, 'update' => 0, 'reject' => 0, 'total' => 1],
            false,
            $user,
        );

        $this->fail('Expected DomainRuleViolationException was not thrown.');
    } catch (DomainRuleViolationException $exception) {
        expect($exception->translationKey())->toBe('errors.import_counts_mismatch')
            ->and($exception->context()['expected_create'])->toBe(1)
            ->and(Product::query()->count())->toBe(0);
    } finally {
        ProductImportDefinitionFixture::$failPersistWithDuplicateKey = false;
    }
});

test('rolls the run back when persist reports a skipped row', function () {
    $user = auth()->user();
    $file = importFixtureCsv([['Robusta', 'Beverages']]);

    // An update target vanished between the confirmation and the write:
    // the confirmed counts did not materialise, so nothing commits and
    // the user re-reviews the refreshed counts instead of silently
    // receiving a short-handed run.
    ProductImportDefinitionFixture::$failPersistWithSkip = true;

    try {
        app(ImportService::class)->executeTransactional(
            $file,
            'products.csv',
            'fixture.products',
            fixtureColumnMap(),
            ['create' => 1, 'update' => 0, 'reject' => 0, 'total' => 1],
            false,
            $user,
        );

        $this->fail('Expected DomainRuleViolationException was not thrown.');
    } catch (DomainRuleViolationException $exception) {
        expect($exception->translationKey())->toBe('errors.import_counts_mismatch')
            ->and($exception->context()['expected_create'])->toBe(1)
            // persist() wrote the row before reporting the skip — the
            // transaction still took it back.
            ->and(Product::query()->count())->toBe(0);
    } finally {
        ProductImportDefinitionFixture::$failPersistWithSkip = false;
    }
});

test('rejects unknown definition keys', function () {
    $file = importFixtureCsv([['Robusta', 'Beverages']]);

    expect(fn () => app(ImportService::class)->preview($file, 'products.csv', 'fixture.missing', fixtureColumnMap(), auth()->user()))
        ->toThrow(InvalidArgumentException::class, __('imports.errors.unknown_definition', ['key' => 'fixture.missing']));
});

test('guesses column mappings from headers and keywords', function () {
    $service = app(ImportService::class);
    $definition = $service->resolveDefinition('fixture.products');

    $guesses = $service->guessMappings($definition, ['Product Name', 'Family Category']);

    expect($guesses['name'])->toBe('Product Name')
        ->and($guesses['category'])->toBe('Family Category');
});
