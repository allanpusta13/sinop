<?php

use App\Http\Controllers\ExportDownloadController;
use App\Http\Controllers\ImportTemplateController;
use App\Http\Controllers\STNManifestController;
use Filament\Http\Middleware\Authenticate as AuthenticatePanel;
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
        // `auth` alone only proves a session exists; the panel's own
        // middleware also re-runs `canAccessPanel()`, so a deactivated
        // account is turned away here rather than handed templates
        // (same contract as the export download below).
        ->middleware(AuthenticatePanel::class)
        ->name('imports.template');

    Route::get('/admin/exports/{file}/download', ExportDownloadController::class)
        // `auth` alone only proves a session exists; the panel's own
        // middleware also re-runs `canAccessPanel()`, so a deactivated
        // account is turned away here rather than handed its files.
        ->middleware(AuthenticatePanel::class)
        ->where('file', '[A-Za-z0-9._-]+')
        ->name('exports.download');
});

Route::middleware(['auth', 'signed'])->group(function () {
    Route::get('/stn/{transferRequisition}/scan', [STNManifestController::class, 'scan'])
        ->name('stn.scan');
});
