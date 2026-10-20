<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\Export\ExportFileStore;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
});

test('allocates a unique filename carrying the definition key and format', function () {
    $store = app(ExportFileStore::class);
    $first = $store->allocateFilename(7, 'products.variants', 'csv');
    $second = $store->allocateFilename(7, 'products.variants', 'csv');

    expect($first)->toStartWith('sinop_export_products_variants_')
        ->and($first)->toEndWith('.csv')
        ->and($first)->toMatch('/\A[A-Za-z0-9._-]+\z/')
        ->and($first)->not->toBe($second);
});

test('resolves paths inside the user directory', function () {
    $store = app(ExportFileStore::class);

    $store->ensureUserDirectory(7);
    Storage::disk('local')->put('exports/7/sinop_export_x.csv', 'hello');

    expect($store->exists(7, 'sinop_export_x.csv'))->toBeTrue()
        ->and($store->exists(8, 'sinop_export_x.csv'))->toBeFalse()
        ->and($store->absolutePath(7, 'sinop_export_x.csv'))
        ->toEndWith('exports/7/sinop_export_x.csv');
});

test('sweeps only stale files, keeping the fresh ones', function () {
    $store = app(ExportFileStore::class);
    $user = User::factory()->create();

    $store->ensureUserDirectory((int) $user->getKey());

    Storage::disk('local')->put('exports/'.$user->getKey().'/sinop_export_old.csv', 'old');
    Storage::disk('local')->put('exports/'.$user->getKey().'/sinop_export_new.csv', 'new');

    touch(
        Storage::disk('local')->path('exports/'.$user->getKey().'/sinop_export_old.csv'),
        time() - (ExportFileStore::STALE_HOURS * 60 * 60) - 60,
    );

    $store->sweep((int) $user->getKey());

    expect(Storage::disk('local')->exists('exports/'.$user->getKey().'/sinop_export_old.csv'))->toBeFalse()
        ->and(Storage::disk('local')->exists('exports/'.$user->getKey().'/sinop_export_new.csv'))->toBeTrue();
});

test('sweep never touches another user directory', function () {
    $store = app(ExportFileStore::class);
    $user = User::factory()->create();

    $store->ensureUserDirectory((int) $user->getKey());
    $store->ensureUserDirectory(99);

    Storage::disk('local')->put('exports/99/sinop_export_other.csv', 'other');

    touch(
        Storage::disk('local')->path('exports/99/sinop_export_other.csv'),
        time() - (ExportFileStore::STALE_HOURS * 60 * 60) - 60,
    );

    $store->sweep((int) $user->getKey());

    expect(Storage::disk('local')->exists('exports/99/sinop_export_other.csv'))->toBeTrue();
});

test('cleanup removes the whole user directory', function () {
    $store = app(ExportFileStore::class);
    $user = User::factory()->create();

    $store->ensureUserDirectory((int) $user->getKey());
    Storage::disk('local')->put('exports/'.$user->getKey().'/sinop_export_x.csv', 'x');

    $store->cleanupUser((int) $user->getKey());

    expect($store->exists((int) $user->getKey(), 'sinop_export_x.csv'))->toBeFalse()
        ->and(Storage::disk('local')->exists('exports/'.$user->getKey()))->toBeFalse();
});
