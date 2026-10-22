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

test('sweep deletes stale user files except the kept source', function () {
    $store = app(ImportFileStore::class);
    $disk = Storage::disk('local');

    $disk->put('imports/1/current.csv', 'current');
    $disk->put('imports/1/stale.csv', 'stale');
    $disk->put('imports/1/preview.json', '{}');
    $disk->put('imports/1/queued.csv', 'queued');
    $disk->put('imports/2/other-user.csv', 'other');

    foreach (['stale.csv', 'preview.json'] as $staleFile) {
        touch(
            $disk->path('imports/1/'.$staleFile),
            time() - (ImportFileStore::STALE_HOURS * 60 * 60) - 60,
        );
    }

    $store->sweep(1, [$disk->path('imports/1/current.csv')]);

    expect($disk->exists('imports/1/current.csv'))->toBeTrue()
        ->and($disk->exists('imports/1/stale.csv'))->toBeFalse()
        ->and($disk->exists('imports/1/preview.json'))->toBeFalse()
        ->and($disk->exists('imports/1/queued.csv'))->toBeTrue()
        ->and($disk->exists('imports/2/other-user.csv'))->toBeTrue();
});

test('cleanup run removes its source and the preview, never a sibling file', function () {
    $store = app(ImportFileStore::class);
    $disk = Storage::disk('local');

    $disk->put('imports/1/run.csv', 'source');
    $disk->put('imports/1/sibling.csv', 'another staged import');
    $disk->put('imports/1/preview.json', '{}');

    $store->cleanupRun(1, $disk->path('imports/1/run.csv'));

    expect($disk->exists('imports/1/run.csv'))->toBeFalse()
        ->and($disk->exists('imports/1/preview.json'))->toBeFalse()
        ->and($disk->exists('imports/1/sibling.csv'))->toBeTrue()
        ->and($disk->directoryExists('imports/1'))->toBeTrue();
});

test('cleanup run ignores paths outside the user directory', function () {
    $store = app(ImportFileStore::class);
    $disk = Storage::disk('local');

    $disk->put('imports/1/source.csv', 'source');
    $disk->put('imports/1/preview.json', '{}');

    $outside = tempnam(sys_get_temp_dir(), 'import_cleanup_');
    file_put_contents($outside, 'not this run');

    $store->cleanupRun(1, $outside);

    expect($disk->exists('imports/1/source.csv'))->toBeTrue()
        ->and($disk->exists('imports/1/preview.json'))->toBeFalse()
        ->and(is_file($outside))->toBeTrue();

    @unlink($outside);
});

test('resolves stored path strings inside the authenticated user directory', function () {
    $store = app(ImportFileStore::class);
    $disk = Storage::disk('local');
    $userId = (int) auth()->id();

    $disk->put("imports/{$userId}/file.csv", 'content');

    // realpath on both sides: the resolved path carries the
    // platform's native separators.
    expect($store->resolvePhysicalPath("imports/{$userId}/file.csv"))
        ->toBe(realpath($disk->path("imports/{$userId}/file.csv")))
        ->and($store->resolvePhysicalPath("imports/{$userId}/missing.csv"))->toBeNull()
        ->and($store->resolvePhysicalPath(null))->toBeNull();
});

test('refuses traversal, absolute paths and other users directories', function () {
    $store = app(ImportFileStore::class);
    $disk = Storage::disk('local');
    $userId = (int) auth()->id();

    $disk->put('imports/2/secret.csv', 'another users file');
    $disk->put('imports/.env', 'APP_KEY=leaked');
    $disk->put("imports/{$userId}/file.csv", 'content');

    // String file state arrives in the Livewire payload and is client-
    // mutable — none of these may resolve, or the mapping step would
    // echo arbitrary file headers back to the user.
    expect($store->resolvePhysicalPath("imports/{$userId}/../2/secret.csv"))->toBeNull()
        ->and($store->resolvePhysicalPath('imports/2/secret.csv'))->toBeNull()
        ->and($store->resolvePhysicalPath("imports/{$userId}/../.env"))->toBeNull()
        ->and($store->resolvePhysicalPath('imports/2/../.env'))->toBeNull()
        ->and($store->resolvePhysicalPath($disk->path("imports/{$userId}/file.csv")))->toBeNull();
});

test('resolves no string state without an authenticated user', function () {
    $store = app(ImportFileStore::class);
    $disk = Storage::disk('local');

    $disk->put('imports/1/file.csv', 'content');

    auth()->logout();

    expect($store->resolvePhysicalPath('imports/1/file.csv'))->toBeNull();
});

test('resolves uploaded files, temporary files and arrays', function () {
    $store = app(ImportFileStore::class);

    $upload = File::create('products.csv');

    expect($store->resolvePhysicalPath($upload))->toBe($upload->getRealPath())
        ->and($store->resolvePhysicalPath([$upload]))->toBe($upload->getRealPath())
        ->and($store->resolveOriginalName($upload))->toBe('products.csv')
        ->and($store->resolveOriginalName(['imports/1/file.xlsx']))->toBe('file.xlsx');
});
