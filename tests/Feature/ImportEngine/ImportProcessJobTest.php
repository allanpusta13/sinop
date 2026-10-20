<?php

declare(strict_types=1);

require_once __DIR__.'/Fixtures/import_helpers.php';

use App\Exceptions\DomainRuleViolationException;
use App\Jobs\ProcessImportJob;
use App\Models\Product;
use App\Services\Import\ImportFileStore;
use App\Services\Import\ImportService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    registerFixtureImportDefinition();
    Storage::fake('local');
    actingAsAdmin();
});

test('processes a queued import, notifies the counts and cleans up the user files', function () {
    $user = auth()->user();
    $disk = Storage::disk('local');

    $file = importFixtureCsv([
        ['Robusta', 'Beverages'],
        ['Liberica', 'Teas'],
    ]);

    $disk->put("imports/{$user->getKey()}/source.csv", 'leftover');
    $disk->put("imports/{$user->getKey()}/preview.json", '{}');

    $job = new ProcessImportJob(
        filePath: $file,
        originalName: 'products.csv',
        definitionKey: 'fixture.products',
        columnMap: ['name' => 'Name', 'category' => 'Category'],
        expectedCounts: ['create' => 2, 'update' => 0, 'reject' => 0, 'total' => 2],
        confirmUpdates: false,
        userId: (int) $user->getKey(),
    );

    $job->handle(app(ImportService::class), app(ImportFileStore::class));

    expect(Product::query()->count())->toBe(2)
        ->and($disk->directoryExists("imports/{$user->getKey()}"))->toBeFalse();

    $notifications = $user->notifications()->get();

    expect($notifications)->toHaveCount(1)
        ->and($notifications->first()->data['title'])->toBe(__('imports.notifications.completed_title'))
        ->and($notifications->first()->data['body'])->toBe(__('imports.notifications.completed_body', [
            'created' => 2,
            'updated' => 0,
            'rejected' => 0,
            'skipped' => 0,
        ]));
});

test('re-authorizes inside the job and reports failures', function () {
    $staff = actingAsWarehouseStaff();
    $disk = Storage::disk('local');

    $file = importFixtureCsv([['Robusta', 'Beverages']]);

    $disk->put("imports/{$staff->getKey()}/source.csv", 'leftover');

    $job = new ProcessImportJob(
        filePath: $file,
        originalName: 'products.csv',
        definitionKey: 'fixture.products',
        columnMap: ['name' => 'Name', 'category' => 'Category'],
        expectedCounts: ['create' => 1, 'update' => 0, 'reject' => 0, 'total' => 1],
        confirmUpdates: false,
        userId: (int) $staff->getKey(),
    );

    try {
        $job->handle(app(ImportService::class), app(ImportFileStore::class));
        $this->fail('Expected AuthorizationException was not thrown.');
    } catch (AuthorizationException) {
        $notifications = $staff->notifications()->get();

        expect(Product::query()->count())->toBe(0)
            ->and($disk->directoryExists("imports/{$staff->getKey()}"))->toBeFalse()
            ->and($notifications)->toHaveCount(1)
            ->and($notifications->first()->data['title'])->toBe(__('imports.notifications.failed_title'))
            ->and($notifications->first()->data['body'])->toContain('unauthorized');
    }
});

test('reports a counts mismatch as a domain failure without writing', function () {
    $user = auth()->user();

    $file = importFixtureCsv([['Robusta', 'Beverages']]);

    Product::factory()->create(['name' => 'Robusta']);

    $job = new ProcessImportJob(
        filePath: $file,
        originalName: 'products.csv',
        definitionKey: 'fixture.products',
        columnMap: ['name' => 'Name', 'category' => 'Category'],
        expectedCounts: ['create' => 1, 'update' => 0, 'reject' => 0, 'total' => 1],
        confirmUpdates: false,
        userId: (int) $user->getKey(),
    );

    try {
        $job->handle(app(ImportService::class), app(ImportFileStore::class));
        $this->fail('Expected DomainRuleViolationException was not thrown.');
    } catch (DomainRuleViolationException $exception) {
        $notifications = $user->notifications()->get();

        expect($exception->translationKey())->toBe('errors.import_counts_mismatch')
            ->and(Product::query()->count())->toBe(1)
            ->and($notifications)->toHaveCount(1)
            ->and($notifications->first()->data['title'])->toBe(__('errors.import_counts_mismatch.title'))
            ->and($notifications->first()->data['body'])
            ->toBe(__('errors.import_counts_mismatch.body', $exception->context()));
    }
});

test('keeps the user files when the job fails unexpectedly so a retry can read them', function () {
    $user = auth()->user();
    $disk = Storage::disk('local');

    $file = importFixtureCsv([['Robusta', 'Beverages']]);

    $disk->put("imports/{$user->getKey()}/source.csv", 'Name,Category');

    $job = new ProcessImportJob(
        filePath: $file,
        originalName: 'products.csv',
        definitionKey: 'fixture.unknown_definition',
        columnMap: ['name' => 'Name', 'category' => 'Category'],
        expectedCounts: ['create' => 1, 'update' => 0, 'reject' => 0, 'total' => 1],
        confirmUpdates: false,
        userId: (int) $user->getKey(),
    );

    expect(fn () => $job->handle(app(ImportService::class), app(ImportFileStore::class)))
        ->toThrow(InvalidArgumentException::class);

    expect($disk->directoryExists("imports/{$user->getKey()}"))->toBeTrue()
        ->and($disk->exists("imports/{$user->getKey()}/source.csv"))->toBeTrue();

    $this->assertDatabaseCount('notifications', 1);
});
