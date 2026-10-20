<?php

declare(strict_types=1);

require_once __DIR__.'/Fixtures/export_helpers.php';

use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\DirectTransfers\Actions\ExportDirectTransfersAction;
use App\Filament\Resources\DirectTransfers\Pages\ListDirectTransfers;
use App\Filament\Resources\InTransits\Pages\ListInTransits;
use App\Filament\Resources\LossLedgers\Pages\ListLossLedgers;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\PurchaseOrders\Pages\ListPurchaseOrders;
use App\Filament\Resources\SalesOrders\Pages\ListSalesOrders;
use App\Filament\Resources\StockMovements\Pages\ListStockMovements;
use App\Filament\Resources\Suppliers\Pages\ListSuppliers;
use App\Filament\Resources\TransferRequisitions\Actions\ExportTransferRequisitionsAction;
use App\Filament\Resources\TransferRequisitions\Pages\ListTransferRequisitions;
use App\Filament\Resources\Warehouses\Pages\ListWarehouses;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Filament\Notifications\NotificationMailbox;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;
use Livewire\Livewire;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

beforeEach(function () {
    Storage::fake('local');
});

test('exposes an export action on every exportable list page', function (string $page, string $action) {
    actingAsAdmin();

    Livewire::test($page)->assertActionExists($action);
})->with([
    [ListProducts::class, 'exportProducts'],
    [ListWarehouses::class, 'exportWarehouses'],
    [ListSuppliers::class, 'exportSuppliers'],
    [ListCustomers::class, 'exportCustomers'],
    [ListStockMovements::class, 'exportStockMovements'],
    [ListLossLedgers::class, 'exportLossLedgers'],
    [ListTransferRequisitions::class, 'exportTransferRequisitions'],
    [ListInTransits::class, 'exportInTransits'],
    [ListPurchaseOrders::class, 'exportPurchaseOrders'],
    [ListSalesOrders::class, 'exportSalesOrders'],
    [ListDirectTransfers::class, 'exportDirectTransfers'],
]);

test('exports a single-type page', function () {
    actingAsAdmin();
    Warehouse::factory()->create(['code' => 'MAIN']);

    Livewire::test(ListWarehouses::class)
        ->callAction('exportWarehouses', data: ['format' => 'csv'])
        ->assertNotified(__('exports.notifications.completed_title'));

    $files = Storage::disk('local')->files('exports/'.auth()->id());

    expect($files)->toHaveCount(1)
        ->and($files[0])->toContain('sinop_export_warehouses_')
        ->and($files[0])->toEndWith('.csv');
});

test('exports the chosen type from a multi-type page', function () {
    actingAsAdmin();
    seedExportGraph();

    Livewire::test(ListTransferRequisitions::class)
        ->callAction('exportTransferRequisitions', data: [
            'type' => 'transfer_requisitions.items',
            'format' => 'xlsx',
        ])
        ->assertNotified(__('exports.notifications.completed_title'));

    $files = Storage::disk('local')->files('exports/'.auth()->id());

    expect($files)->toHaveCount(1)
        ->and($files[0])->toContain('sinop_export_transfer_requisitions_items_')
        ->and($files[0])->toEndWith('.xlsx');

    $path = Storage::disk('local')->path($files[0]);

    $reader = new XlsxReader;
    $reader->open($path);

    $rows = [];

    foreach ($reader->getSheetIterator() as $sheet) {
        foreach ($sheet->getRowIterator() as $row) {
            $rows[] = $row->toArray();
        }
    }

    $reader->close();

    expect($rows[0][0])->toBe(__('exports.columns.transfer_requisition_item_id'))
        ->and($rows)->toHaveCount(2)
        ->and($rows[1][0])->not->toBe('');
});

test('exports only the rows a warehouse-scoped user can see', function () {
    $assigned = Warehouse::factory()->create();
    $staff = actingAsStaff($assigned);

    StockMovement::factory()->create(['warehouse_id' => $assigned->getKey()]);
    StockMovement::factory()->create();

    Livewire::test(ListStockMovements::class)
        ->callAction('exportStockMovements', data: ['format' => 'csv']);

    $files = Storage::disk('local')->files('exports/'.$staff->getKey());

    expect($files)->toHaveCount(1);

    $records = array_values(iterator_to_array(
        Reader::createFromPath(Storage::disk('local')->path($files[0]), 'r')->getRecords(),
        false,
    ));

    expect($records)->toHaveCount(2)
        ->and((int) $records[1][4])->toBe($assigned->getKey());
});

test('hides the action from users who may not export the resource', function () {
    actingAsWarehouseStaff();

    expect(ExportDirectTransfersAction::make()->isAuthorized())->toBeFalse();
});

test('shows the action to users who may export the resource', function () {
    actingAsAdmin();

    expect(ExportDirectTransfersAction::make()->isAuthorized())->toBeTrue();
});

test('rejects an export type the page does not offer', function () {
    actingAsAdmin();

    $livewire = Livewire::test(ListTransferRequisitions::class)->instance();

    // The modal's options() already block an off-list value server-side;
    // this exercises the tamper guard inside the action itself.
    $guard = new ReflectionMethod(ExportTransferRequisitionsAction::class, 'runExport');
    $guard->setAccessible(true);
    $guard->invoke(new ExportTransferRequisitionsAction, [
        'type' => 'suppliers',
        'format' => 'csv',
    ], $livewire);

    $mailbox = app(NotificationMailbox::class);
    $mailbox->flush();

    $notifications = $mailbox->pull();

    expect($notifications)->toHaveCount(1)
        ->and($notifications[0]['title'])
        ->toBe(__('exports.errors.unknown_definition', ['key' => 'suppliers']))
        ->and(Storage::disk('local')->files('exports/'.auth()->id()))->toBeEmpty();
});

test('carries the table filters into the file', function () {
    actingAsAdmin();

    Warehouse::factory()->create(['code' => 'MAIN', 'name' => 'Main', 'is_active' => true]);
    Warehouse::factory()->create(['code' => 'WEST', 'name' => 'West', 'is_active' => false]);

    Livewire::test(ListWarehouses::class)
        ->filterTable('is_active', 'true')
        ->callAction('exportWarehouses', data: ['format' => 'csv']);

    $files = Storage::disk('local')->files('exports/'.auth()->id());

    $records = array_values(iterator_to_array(
        Reader::createFromPath(Storage::disk('local')->path($files[0]), 'r')->getRecords(),
        false,
    ));

    expect($files)->toHaveCount(1)
        ->and($records)->toHaveCount(2)
        ->and($records[1][0])->toBe('MAIN');
});
