<?php

declare(strict_types=1);

require_once __DIR__.'/Fixtures/import_helpers.php';

use App\Services\Import\ImportFileStore;
use Illuminate\Http\Testing\File;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
});

test('writes and reads back the frozen preview per user', function () {
    $store = app(ImportFileStore::class);

    expect($store->readPreview(1))->toBeNull();

    $store->writePreview(1, ['counts' => ['create' => 2, 'update' => 1, 'reject' => 0, 'total' => 3], 'has_updates' => true]);

    $preview = $store->readPreview(1);

    expect($preview['counts']['create'])->toBe(2)
        ->and($preview['has_updates'])->toBeTrue();
});

test('sweep deletes every user file except the kept source', function () {
    $store = app(ImportFileStore::class);
    $disk = Storage::disk('local');

    $disk->put('imports/1/current.csv', 'current');
    $disk->put('imports/1/stale.csv', 'stale');
    $disk->put('imports/1/preview.json', '{}');
    $disk->put('imports/2/other-user.csv', 'other');

    $store->sweep(1, [$disk->path('imports/1/current.csv')]);

    expect($disk->exists('imports/1/current.csv'))->toBeTrue()
        ->and($disk->exists('imports/1/stale.csv'))->toBeFalse()
        ->and($disk->exists('imports/1/preview.json'))->toBeFalse()
        ->and($disk->exists('imports/2/other-user.csv'))->toBeTrue();
});

test('cleanup user removes the whole import directory', function () {
    $store = app(ImportFileStore::class);
    $disk = Storage::disk('local');

    $disk->put('imports/1/current.csv', 'current');
    $disk->put('imports/1/preview.json', '{}');

    $store->cleanupUser(1);

    expect($disk->exists('imports/1/current.csv'))->toBeFalse()
        ->and($disk->directoryExists('imports/1'))->toBeFalse();
});

test('resolves stored path strings to physical paths', function () {
    $store = app(ImportFileStore::class);
    $disk = Storage::disk('local');

    $disk->put('imports/1/file.csv', 'content');

    expect($store->resolvePhysicalPath('imports/1/file.csv'))->toBe($disk->path('imports/1/file.csv'))
        ->and($store->resolvePhysicalPath('imports/1/missing.csv'))->toBeNull()
        ->and($store->resolvePhysicalPath(null))->toBeNull();
});

test('resolves uploaded files, temporary files and arrays', function () {
    $store = app(ImportFileStore::class);

    $upload = File::create('products.csv');

    expect($store->resolvePhysicalPath($upload))->toBe($upload->getRealPath())
        ->and($store->resolvePhysicalPath([$upload]))->toBe($upload->getRealPath())
        ->and($store->resolveOriginalName($upload))->toBe('products.csv')
        ->and($store->resolveOriginalName(['imports/1/file.xlsx']))->toBe('file.xlsx');
});
