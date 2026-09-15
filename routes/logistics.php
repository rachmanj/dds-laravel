<?php

use App\Http\Controllers\Logistics\GrpoSummaryController;
use App\Http\Controllers\Logistics\InventorySummaryController;
use Illuminate\Support\Facades\Route;

Route::prefix('logistics/inventory')
    ->name('logistics.inventory.')
    ->middleware(['auth', 'active.user', 'permission:view-logistics-summary'])
    ->group(function () {
        Route::get('/', [InventorySummaryController::class, 'index'])->name('index');
        Route::get('/data', [InventorySummaryController::class, 'data'])->name('data');
        Route::get('/export', [InventorySummaryController::class, 'export'])
            ->middleware('permission:export-logistics-summary')
            ->name('export');
    });

Route::prefix('logistics/grpo')
    ->name('logistics.grpo.')
    ->middleware(['auth', 'active.user', 'permission:view-logistics-summary'])
    ->group(function () {
        Route::get('/', [GrpoSummaryController::class, 'index'])->name('index');
        Route::get('/data', [GrpoSummaryController::class, 'data'])->name('data');
        Route::get('/export', [GrpoSummaryController::class, 'export'])
            ->middleware('permission:export-logistics-summary')
            ->name('export');
    });
