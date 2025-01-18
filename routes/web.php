<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RanapController;
use App\Http\Controllers\RekapController;
use App\Http\Controllers\ExportController;
use App\Http\Middleware\checkIpMapping;

// Route dashboard ranap.
Route::get('/', [RanapController::class, 'showDashboardRanap'])->name('ranap');

// Route untuk api data.
Route::get('/ajax/patients', [RanapController::class, 'getPatientDataAjax'])->name('ajax.patients');

// Route::get('/rekap', [RekapController::class, 'showDashboardRekap'])->name('rekap.show');

// Middleware untuk dashboard rekap data.
Route::middleware([checkIpMapping::class])
    ->group(function () {
        Route::get('/rkp', [RekapController::class, 'showRekap'])->name('rkp');
        Route::get('/rkp/export/excel', [ExportController::class, 'exportToExcel'])->name('export.excel');
    });

/* Route untuk pengujian koneksi ke SQL Server. */
/*
    Route::get('/test-sqlserver', function () {
        try {
            DB::connection('sqlsrv')->getPdo();
            echo "Connected successfully to the database ms sql server!";
        } catch (\Exception $e) {
            die("Could not connect to the database. Error: " . $e->getMessage());
        }
    }); 
*/
