<?php

use App\Http\Controllers\ReportsReconcileController;
use Illuminate\Support\Facades\Route;

Route::prefix('reconcile')->name('reconcile.')->middleware('permission:view-reconcile')->group(function () {
    // Main reconcile page
    Route::get('/', [ReportsReconcileController::class, 'index'])->name('index');

    // Data endpoints
    Route::get('/data', [ReportsReconcileController::class, 'data'])->name('data');
    Route::get('/stats', [ReportsReconcileController::class, 'getStats'])->name('stats');
    Route::get('/suppliers', [ReportsReconcileController::class, 'getSuppliers'])->name('suppliers');

    // File operations
    Route::post('/upload', [ReportsReconcileController::class, 'upload'])->middleware('permission:upload-reconcile')->name('upload');
    Route::get('/export', [ReportsReconcileController::class, 'export'])->middleware('permission:export-reconcile')->name('export');
    Route::get('/template', [ReportsReconcileController::class, 'downloadTemplate'])->name('template');
    Route::get('/delete-mine', [ReportsReconcileController::class, 'deleteMine'])->middleware('permission:delete-reconcile')->name('delete-mine');
    Route::get('/delete', [ReportsReconcileController::class, 'deleteMine'])->middleware('permission:delete-reconcile')->name('delete');

    // Invoice matching
    Route::get('/invoice-irr/{invoiceNo}', [ReportsReconcileController::class, 'getInvoiceIrr'])->name('invoice-irr');
    Route::get('/reconcile-data', [ReportsReconcileController::class, 'getReconcileData'])->name('reconcile-data');
    Route::get('/invoice/{id}', [ReportsReconcileController::class, 'getInvoiceDetails'])->name('invoice');
});
