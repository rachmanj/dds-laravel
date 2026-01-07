<?php

use App\Http\Controllers\ReportsAccountingFulfillmentController;
use Illuminate\Support\Facades\Route;

Route::prefix('reports/accounting-fulfillment')->name('accounting-fulfillment.')->middleware(['auth', 'role:superadmin|admin|accounting'])->group(function () {
    Route::get('/', [ReportsAccountingFulfillmentController::class, 'index'])->name('index');
    Route::get('/data', [ReportsAccountingFulfillmentController::class, 'data'])->name('data');
});
