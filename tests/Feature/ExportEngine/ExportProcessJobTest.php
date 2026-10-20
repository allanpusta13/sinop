<?php

declare(strict_types=1);

require_once __DIR__.'/Fixtures/export_helpers.php';

use App\Filament\Resources\StockMovements\StockMovementResource;
use App\Jobs\ProcessExportJob;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\Export\ExportColumn;
use App\Services\Export\ExportFileStore;
use App\Services\Export\ExportGenerator;
use App\Services\Export\ExportService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;

/**
 * @return array{0: string, 1: array<int, mixed>}
 */
function exportJobSnapshot(string $key): array
{
    $service = app(ExportService::class);
    $definition = $service->resolveDefinition($key);

    return $service->snapshot($definition, $definition->query(exportTableQueryFor($key)));
}

/**
 * The snapshot a queued export is dispatched with — the scoped page
 * query exactly as it stood at dispatch time, before anything the job
 * could see had changed.
 *
 * @return array{0: string, 1: array<int, mixed>}
 */
function exportScopedSnapshot(string $key, Builder $pageQuery): array
{
    $service = app(ExportService::class);
    $definition = $service->resolveDefinition($key);

    return $service->snapshot($definition, $definition->query($pageQuery));
}

/**
 * @return array<int, array<string, string>>
 */
function exportCsvRecords(string $path): array
{
    $reader = Reader::createFromPath($path, 'r');
    $reader->setHeaderOffset(0);

    // Records come back keyed by the translated header, so both the
    // outer list and each row are flattened to positions — the declared
    // column order is the only stable handle on a cell.
    return array_map(
        'array_values',
        array_values(iterator_to_array($reader->getRecords())),
    );
}

/**
 * Position of a column in a file — the CSV carries the translated
 * label as its header, not the column key, so a value can only be
 * found by where its column sits in the declared order.
 */
function exportCsvColumnIndex(string $definitionKey, string $columnKey): int
{
    return (int) array_search($columnKey, array_map(
        static fn (ExportColumn $column): string => $column->key,
        app(ExportService::class)->resolveDefinition($definitionKey)->columns(),
    ), true);
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

test('narrows a queued export when a warehouse was taken away while it waited', function () {
    $mine = Warehouse::factory()->create();
    $theirs = Warehouse::factory()->create();
    $variant = ProductVariant::factory()->create();

    StockMovement::factory()->create(['product_variant_id' => $variant->getKey(), 'warehouse_id' => $mine->getKey()]);
    StockMovement::factory()->create(['product_variant_id' => $variant->getKey(), 'warehouse_id' => $theirs->getKey()]);

    $staff = actingAsStaff($mine, $theirs);

    [$sql, $bindings] = exportScopedSnapshot('stock_movements', StockMovementResource::getEloquentQuery());

    // The assignment changes while the job sits in the queue.
    $staff->warehouses()->detach($theirs->getKey());

    $fileStore = app(ExportFileStore::class);
    $fileStore->ensureUserDirectory((int) $staff->getKey());
    $filename = $fileStore->allocateFilename((int) $staff->getKey(), 'stock_movements', 'csv');

    (new ProcessExportJob($sql, $bindings, 'stock_movements', 'csv', $filename, $staff))->handle(
        app(ExportService::class),
        app(ExportGenerator::class),
        $fileStore,
    );

    $records = exportCsvRecords($fileStore->absolutePath((int) $staff->getKey(), $filename));
    $warehouseColumn = exportCsvColumnIndex('stock_movements', 'warehouse_id');

    expect($records)->toHaveCount(1)
        ->and((int) $records[0][$warehouseColumn])->toBe($mine->getKey());
});

test('does not widen a queued export when a warehouse was added while it waited', function () {
    $mine = Warehouse::factory()->create();
    $theirs = Warehouse::factory()->create();
    $variant = ProductVariant::factory()->create();

    StockMovement::factory()->create(['product_variant_id' => $variant->getKey(), 'warehouse_id' => $mine->getKey()]);
    StockMovement::factory()->create(['product_variant_id' => $variant->getKey(), 'warehouse_id' => $theirs->getKey()]);

    $staff = actingAsStaff($mine);

    // Dispatched under one warehouse, so one row is what the file holds.
    [$sql, $bindings] = exportScopedSnapshot('stock_movements', StockMovementResource::getEloquentQuery());

    $staff->warehouses()->attach($theirs->getKey());

    $fileStore = app(ExportFileStore::class);
    $fileStore->ensureUserDirectory((int) $staff->getKey());
    $filename = $fileStore->allocateFilename((int) $staff->getKey(), 'stock_movements', 'csv');

    (new ProcessExportJob($sql, $bindings, 'stock_movements', 'csv', $filename, $staff))->handle(
        app(ExportService::class),
        app(ExportGenerator::class),
        $fileStore,
    );

    $records = exportCsvRecords($fileStore->absolutePath((int) $staff->getKey(), $filename));
    $warehouseColumn = exportCsvColumnIndex('stock_movements', 'warehouse_id');

    expect($records)->toHaveCount(1)
        ->and((int) $records[0][$warehouseColumn])->toBe($mine->getKey());
});

test('fails the export and writes no file once every warehouse is gone', function () {
    $mine = Warehouse::factory()->create();

    StockMovement::factory()->create(['warehouse_id' => $mine->getKey()]);

    $staff = actingAsStaff($mine);

    [$sql, $bindings] = exportScopedSnapshot('stock_movements', StockMovementResource::getEloquentQuery());

    $staff->warehouses()->detach();

    $fileStore = app(ExportFileStore::class);
    $fileStore->ensureUserDirectory((int) $staff->getKey());
    $filename = $fileStore->allocateFilename((int) $staff->getKey(), 'stock_movements', 'csv');

    (new ProcessExportJob($sql, $bindings, 'stock_movements', 'csv', $filename, $staff))->handle(
        app(ExportService::class),
        app(ExportGenerator::class),
        $fileStore,
    );

    $notifications = $staff->notifications()->get();

    expect($notifications)->toHaveCount(1)
        ->and($notifications->first()->data['title'])->toBe(__('exports.notifications.scope_lost_title'))
        ->and($fileStore->exists((int) $staff->getKey(), $filename))->toBeFalse();
});

test('discards a half written file when generation fails part way', function () {
    $user = actingAsAdmin();

    $fileStore = app(ExportFileStore::class);
    $fileStore->ensureUserDirectory((int) $user->getKey());
    $filename = $fileStore->allocateFilename((int) $user->getKey(), 'warehouses', 'csv');

    // The header row reaches disk before the first page is read, so a
    // snapshot that cannot run would otherwise leave a partial file
    // behind — still reachable by its own download link.
    (new ProcessExportJob('select * from not_a_table', [], 'warehouses', 'csv', $filename, $user))->handle(
        app(ExportService::class),
        app(ExportGenerator::class),
        $fileStore,
    );

    $notifications = $user->notifications()->get();

    expect(Storage::disk('local')->files('exports/'.$user->getKey()))->toBeEmpty()
        ->and($notifications)->toHaveCount(1)
        ->and($notifications->first()->data['title'])->toBe(__('exports.notifications.failed_title'));
});
