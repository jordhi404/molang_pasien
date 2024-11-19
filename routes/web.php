<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\RanapController;

Route::get('/', function () {
    return view('welcome');
});

// Route::get('/ranap', [RanapController::class, 'showDashboardRanap'])->name('ranap');

Route::get('/patients', [RanapController::class, 'showPatientTable'])->name('patients.table');

// Route untuk api data.
Route::get('/ajax/patients', [RanapController::class, 'getPatientData'])->name('ajax.patients');

// Route untuk pengujian koneksi ke SQL Server.
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
