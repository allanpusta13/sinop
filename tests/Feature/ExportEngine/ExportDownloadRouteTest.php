<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
});

test('serves the export file to the user who generated it', function () {
    $user = actingAsAdmin();
    $filename = 'sinop_export_warehouses_20260101_000000_abcdefgh.csv';

    Storage::disk('local')->put('exports/'.$user->getKey().'/'.$filename, "code,name\nMAIN,Main\n");

    $this->get(route('exports.download', ['file' => $filename]))
        ->assertOk()
        ->assertDownload($filename);
});

test('sets the right content type per format', function () {
    $user = actingAsAdmin();

    Storage::disk('local')->put('exports/'.$user->getKey().'/sinop_export_warehouses_20260101_000000_abcdefgh.xlsx', 'x');

    $this->get(route('exports.download', ['file' => 'sinop_export_warehouses_20260101_000000_abcdefgh.xlsx']))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

test('tells the browser not to sniff a csv into something executable', function () {
    $user = actingAsAdmin();

    Storage::disk('local')->put('exports/'.$user->getKey().'/sinop_export_warehouses_20260101_000000_abcdefgh.csv', 'x');

    $this->get(route('exports.download', ['file' => 'sinop_export_warehouses_20260101_000000_abcdefgh.csv']))
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff');
});

test('404s for a file carrying an extension the generator never writes', function () {
    $user = actingAsAdmin();

    Storage::disk('local')->put('exports/'.$user->getKey().'/sinop_export_warehouses_20260101_000000_abcdefgh.txt', 'x');

    $this->get(route('exports.download', ['file' => 'sinop_export_warehouses_20260101_000000_abcdefgh.txt']))
        ->assertNotFound();
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
