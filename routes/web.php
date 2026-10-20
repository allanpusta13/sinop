<?php

use App\Http\Controllers\ExportDownloadController;
use App\Http\Controllers\ImportTemplateController;
use App\Http\Controllers\STNManifestController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('/stn/{transferRequisition}/print', [STNManifestController::class, 'print'])
        ->name('stn.print');

    // Widget routes testing
    Route::get('/widgets/low-stock-alerts', function () {
        return view('filament.widgets.low-stock-alerts');
    })->name('widgets.low-stock-alerts');

    Route::get('/widgets/recent-movements', function () {
        return view('filament.widgets.recent-movements');
    })->name('widgets.recent-movements');

    Route::get('/admin/imports/{import}/template/{format}', [ImportTemplateController::class, 'download'])
        ->whereIn('format', ['csv', 'xlsx'])
        ->name('imports.template');

    Route::get('/admin/exports/{file}/download', ExportDownloadController::class)
        ->where('file', '[A-Za-z0-9._-]+')
        ->name('exports.download');
});

Route::middleware(['auth', 'signed'])->group(function () {
    Route::get('/stn/{transferRequisition}/scan', [STNManifestController::class, 'scan'])
        ->name('stn.scan');
});
