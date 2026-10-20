<?php

declare(strict_types=1);

require_once __DIR__.'/Fixtures/export_helpers.php';

use App\Jobs\ProcessExportJob;
use App\Models\Warehouse;
use App\Services\Export\ExportFileStore;
use App\Services\Export\ExportGenerator;
use App\Services\Export\ExportService;
use Illuminate\Support\Facades\Storage;

/**
 * @return array{0: string, 1: array<int, mixed>}
 */
function exportJobSnapshot(string $key): array
{
    $service = app(ExportService::class);
    $definition = $service->resolveDefinition($key);

    return $service->snapshot($definition->query(exportTableQueryFor($key)));
}

beforeEach(function () {
    Storage::fake('local');
});

test('generates the file and reports it as a database notification', function () {
    $user = actingAsAdmin();
    Warehouse::factory()->create(['code' => 'MAIN']);

    [$sql, $bindings] = exportJobSnapshot('warehouses');

    $fileStore = app(ExportFileStore::class);
    $fileStore->ensureUserDirectory((int) $user->getKey());
    $filename = $fileStore->allocateFilename((int) $user->getKey(), 'warehouses', 'csv');

    (new ProcessExportJob($sql, $bindings, 'warehouses', 'csv', $filename, $user))->handle(
        app(ExportService::class),
        app(ExportGenerator::class),
        $fileStore,
    );

    expect(Storage::disk('local')->exists('exports/'.$user->getKey().'/'.$filename))->toBeTrue();

    $notifications = $user->notifications()->get();

    expect($notifications)->toHaveCount(1);

    $data = $notifications->first()->data;

    expect($data['title'])->toBe(__('exports.notifications.completed_title'))
        ->and($data['body'])->toContain(number_format(1))
        ->and($data['actions'][0]['url'])->toBe(route('exports.download', ['file' => $filename]));
});

test('re-authorizes the snapshot instead of trusting it', function () {
    $staff = actingAsWarehouseStaff();

    [$sql, $bindings] = exportJobSnapshot('direct_transfers');

    (new ProcessExportJob($sql, $bindings, 'direct_transfers', 'csv', 'sinop_export_x.csv', $staff))->handle(
        app(ExportService::class),
        app(ExportGenerator::class),
        app(ExportFileStore::class),
    );

    $notifications = $staff->notifications()->get();

    expect($notifications)->toHaveCount(1)
        ->and($notifications->first()->data['title'])->toBe(__('exports.notifications.failed_title'))
        ->and(Storage::disk('local')->exists('exports/'.$staff->getKey().'/sinop_export_x.csv'))->toBeFalse();
});

test('reports an unknown definition key as a failed notification', function () {
    $user = actingAsAdmin();

    (new ProcessExportJob('select 1', [], 'nope.nope', 'csv', 'sinop_export_x.csv', $user))->handle(
        app(ExportService::class),
        app(ExportGenerator::class),
        app(ExportFileStore::class),
    );

    $notifications = $user->notifications()->get();

    expect($notifications)->toHaveCount(1)
        ->and($notifications->first()->data['title'])->toBe(__('exports.notifications.failed_title'));
});

test('refuses a filename that travelled through the queue in an unsafe shape', function () {
    $user = actingAsAdmin();
    Warehouse::factory()->create(['code' => 'MAIN']);

    [$sql, $bindings] = exportJobSnapshot('warehouses');

    $fileStore = app(ExportFileStore::class);
    $fileStore->ensureUserDirectory((int) $user->getKey());

    (new ProcessExportJob($sql, $bindings, 'warehouses', 'csv', '../../.env', $user))->handle(
        app(ExportService::class),
        app(ExportGenerator::class),
        $fileStore,
    );

    $notifications = $user->notifications()->get();

    expect($notifications)->toHaveCount(1)
        ->and($notifications->first()->data['title'])->toBe(__('exports.notifications.failed_title'))
        ->and(Storage::disk('local')->files('exports/'.$user->getKey()))->toBeEmpty();
});
