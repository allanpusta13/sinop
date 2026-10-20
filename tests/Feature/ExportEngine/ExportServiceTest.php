<?php

declare(strict_types=1);

require_once __DIR__.'/Fixtures/export_helpers.php';

use App\Filament\Resources\StockMovements\StockMovementResource;
use App\Jobs\ProcessExportJob;
use App\Models\DirectTransfer;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\Export\ExportService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    actingAsAdmin();
});

test('resolves an unknown definition key with its key in the message', function () {
    expect(fn () => app(ExportService::class)->resolveDefinition('nope.nope'))
        ->toThrow(InvalidArgumentException::class, 'nope.nope');
});

test('exports inline below the queue threshold', function () {
    Warehouse::factory()->count(2)->create();

    $result = app(ExportService::class)->export(
        Warehouse::query(),
        'warehouses',
        'csv',
        auth()->user(),
    );

    expect($result['status'])->toBe('completed')
        ->and($result['rows'])->toBe(2)
        ->and($result['filename'])->toStartWith('sinop_export_warehouses_')
        ->and(Storage::disk('local')->exists('exports/'.auth()->id().'/'.$result['filename']))->toBeTrue();
});

test('queues exports above the queue threshold', function () {
    Bus::fake();

    Warehouse::factory()->count(ExportService::QUEUE_THRESHOLD + 1)->create();

    $result = app(ExportService::class)->export(
        Warehouse::query(),
        'warehouses',
        'csv',
        auth()->user(),
    );

    expect($result['status'])->toBe('queued')
        ->and($result['rows'])->toBe(ExportService::QUEUE_THRESHOLD + 1);

    Bus::assertDispatched(ProcessExportJob::class, function (ProcessExportJob $job) use ($result): bool {
        return $job->definitionKey === 'warehouses'
            && $job->filename === $result['filename']
            && $job->format === 'csv'
            && $job->sql !== ''
            && is_array($job->bindings);
    });
});

test('runs queued exports on the dedicated export queue connection', function () {
    Bus::fake();

    Warehouse::factory()->count(ExportService::QUEUE_THRESHOLD + 1)->create();

    app(ExportService::class)->export(
        Warehouse::query(),
        'warehouses',
        'csv',
        auth()->user(),
    );

    Bus::assertDispatched(
        ProcessExportJob::class,
        fn (ProcessExportJob $job): bool => $job->connection === config('queue.export_connection'),
    );
});

test('rejects an unsupported format before allocating a file or dispatching a job', function () {
    Bus::fake();

    Warehouse::factory()->count(ExportService::QUEUE_THRESHOLD + 1)->create();

    expect(fn () => app(ExportService::class)->export(
        Warehouse::query(),
        'warehouses',
        'pdf',
        auth()->user(),
    ))->toThrow(InvalidArgumentException::class, __('exports.errors.unsupported_format'));

    Bus::assertNothingDispatched();
    expect(Storage::disk('local')->files('exports/'.auth()->id()))->toBeEmpty();
});

test('exports exactly the rows the table query holds', function () {
    Warehouse::factory()->count(3)->create();
    $wanted = Warehouse::query()->first();

    $result = app(ExportService::class)->export(
        Warehouse::query()->whereKey($wanted->getKey()),
        'warehouses',
        'csv',
        auth()->user(),
    );

    expect($result['status'])->toBe('completed')
        ->and($result['rows'])->toBe(1);
});

test('blocks an unauthorized user server-side', function () {
    actingAsWarehouseStaff();

    expect(fn () => app(ExportService::class)->export(
        DirectTransfer::query(),
        'direct_transfers',
        'csv',
        auth()->user(),
    ))->toThrow(AuthorizationException::class);
});

test('exports only the warehouses a staff member is assigned to', function () {
    $assigned = Warehouse::factory()->create();
    Warehouse::factory()->create();

    StockMovement::factory()->create(['warehouse_id' => $assigned->getKey()]);
    StockMovement::factory()->create();

    $staff = actingAsStaff($assigned);

    $result = app(ExportService::class)->export(
        StockMovementResource::getEloquentQuery(),
        'stock_movements',
        'csv',
        $staff,
    );

    expect($result['rows'])->toBe(1)
        ->and(Storage::disk('local')->exists('exports/'.$staff->getKey().'/'.$result['filename']))->toBeTrue();
});

test('sweeps stale exports before writing a new one', function () {
    $user = auth()->user();
    $directory = 'exports/'.$user->getKey();

    Storage::disk('local')->put($directory.'/sinop_export_stale.csv', 'old');
    $stale = Storage::disk('local')->path($directory.'/sinop_export_stale.csv');
    touch($stale, time() - (25 * 60 * 60));

    Warehouse::factory()->create();

    app(ExportService::class)->export(Warehouse::query(), 'warehouses', 'csv', $user);

    expect(Storage::disk('local')->exists($directory.'/sinop_export_stale.csv'))->toBeFalse();
});
