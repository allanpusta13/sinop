<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Warehouse;
use App\Services\Export\ExportFileStore;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
});

/**
 * Write an export file with the provenance sidecar production always
 * writes alongside it.
 *
 * @param  array<int, int>|null  $scope
 */
function putExportFileWithMeta(int $userId, string $filename, string $contents = 'x', ?array $scope = null, string $definitionKey = 'warehouses'): void
{
    Storage::disk('local')->put('exports/'.$userId.'/'.$filename, $contents);

    app(ExportFileStore::class)->writeScopeMeta($userId, $filename, $definitionKey, $scope);
}

test('serves the export file to the user who generated it', function () {
    $user = actingAsAdmin();
    $filename = 'sinop_export_warehouses_20260101_000000_abcdefgh.csv';

    putExportFileWithMeta((int) $user->getKey(), $filename, "code,name\nMAIN,Main\n");

    $this->get(route('exports.download', ['file' => $filename]))
        ->assertOk()
        ->assertDownload($filename);
});

test('sets the right content type per format', function () {
    $user = actingAsAdmin();

    putExportFileWithMeta((int) $user->getKey(), 'sinop_export_warehouses_20260101_000000_abcdefgh.xlsx');

    $this->get(route('exports.download', ['file' => 'sinop_export_warehouses_20260101_000000_abcdefgh.xlsx']))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

test('tells the browser not to sniff a csv into something executable', function () {
    $user = actingAsAdmin();

    putExportFileWithMeta((int) $user->getKey(), 'sinop_export_warehouses_20260101_000000_abcdefgh.csv');

    $this->get(route('exports.download', ['file' => 'sinop_export_warehouses_20260101_000000_abcdefgh.csv']))
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff');
});

test('refuses a file whose warehouse scope has since shrunk', function () {
    $mine = Warehouse::factory()->create();
    $theirs = Warehouse::factory()->create();
    $staff = actingAsStaff($mine, $theirs);

    $filename = 'sinop_export_stock_movements_20260101_000000_abcdefgh.csv';

    putExportFileWithMeta((int) $staff->getKey(), $filename, 'x', [(int) $mine->getKey(), (int) $theirs->getKey()], 'stock_movements');

    // A warehouse is taken away between generation and the download;
    // the file may still hold its rows for up to 24 hours.
    $staff->warehouses()->detach($theirs->getKey());

    $this->get(route('exports.download', ['file' => $filename]))
        ->assertNotFound();
});

test('serves a file whose recorded scope is still held', function () {
    $mine = Warehouse::factory()->create();
    $staff = actingAsStaff($mine);

    $filename = 'sinop_export_stock_movements_20260101_000000_abcdefgh.csv';

    putExportFileWithMeta((int) $staff->getKey(), $filename, 'x', [(int) $mine->getKey()], 'stock_movements');

    $this->get(route('exports.download', ['file' => $filename]))
        ->assertOk()
        ->assertDownload($filename);
});

test('refuses a file recorded as unrestricted once the user is scoped', function () {
    $mine = Warehouse::factory()->create();
    $staff = actingAsStaff($mine);

    $filename = 'sinop_export_stock_movements_20260101_000000_abcdefgh.csv';

    putExportFileWithMeta((int) $staff->getKey(), $filename, 'x', null, 'stock_movements');

    $this->get(route('exports.download', ['file' => $filename]))
        ->assertNotFound();
});

test('404s a file without its provenance sidecar', function () {
    $user = actingAsAdmin();

    Storage::disk('local')->put('exports/'.$user->getKey().'/sinop_export_warehouses_20260101_000000_abcdefgh.csv', 'x');

    $this->get(route('exports.download', ['file' => 'sinop_export_warehouses_20260101_000000_abcdefgh.csv']))
        ->assertNotFound();
});

test('404s for a file carrying an extension the generator never writes', function () {
    $user = actingAsAdmin();

    Storage::disk('local')->put('exports/'.$user->getKey().'/sinop_export_warehouses_20260101_000000_abcdefgh.txt', 'x');

    $this->get(route('exports.download', ['file' => 'sinop_export_warehouses_20260101_000000_abcdefgh.txt']))
        ->assertNotFound();
});

test('turns away a user the panel no longer accepts', function () {
    // A live session alone is not enough: the panel re-runs
    // canAccessPanel(), so a deactivated account is refused its own
    // files rather than handed them.
    $user = actingAsAdmin(['is_active' => false]);

    Storage::disk('local')->put('exports/'.$user->getKey().'/sinop_export_warehouses_20260101_000000_abcdefgh.csv', 'x');

    $this->get(route('exports.download', ['file' => 'sinop_export_warehouses_20260101_000000_abcdefgh.csv']))
        ->assertForbidden();
});

test('404s when the filename belongs to another user', function () {
    $user = actingAsAdmin();
    $other = User::factory()->create();

    Storage::disk('local')->put('exports/'.$other->getKey().'/sinop_export_other.csv', 'x');

    expect($user->getKey())->not->toBe($other->getKey());

    $this->get(route('exports.download', ['file' => 'sinop_export_other.csv']))
        ->assertNotFound();
});

test('404s for a filename that was never generated', function () {
    actingAsAdmin();

    $this->get(route('exports.download', ['file' => 'sinop_export_missing.csv']))
        ->assertNotFound();
});

test('rejects filenames carrying a path separator', function () {
    actingAsAdmin();

    $this->get('/admin/exports/..%2F..%2Fsinop_export_x.csv/download')->assertNotFound();
});
