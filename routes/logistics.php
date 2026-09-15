<?php

use App\Http\Controllers\Logistics\CategoryMapController;
use App\Http\Controllers\Logistics\GrpoSummaryController;
use App\Http\Controllers\Logistics\InventorySummaryController;
use App\Http\Controllers\Logistics\UsageSummaryController;
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

Route::prefix('logistics/usage')
    ->name('logistics.usage.')
    ->middleware(['auth', 'active.user', 'permission:view-logistics-summary'])
    ->group(function () {
        Route::get('/', [UsageSummaryController::class, 'index'])->name('index');
        Route::get('/data', [UsageSummaryController::class, 'data'])->name('data');
        Route::get('/export', [UsageSummaryController::class, 'export'])
            ->middleware('permission:export-logistics-summary')
            ->name('export');
    });

Route::prefix('logistics/categories')
    ->name('logistics.categories.')
    ->middleware(['auth', 'active.user', 'permission:manage-logistics-category-map'])
    ->group(function () {
        Route::get('/', [CategoryMapController::class, 'index'])->name('index');
        Route::post('/', [CategoryMapController::class, 'store'])->name('store');
        Route::put('/{logisticsItemCategory}', [CategoryMapController::class, 'update'])->name('update');
        Route::patch('/{logisticsItemCategory}/toggle', [CategoryMapController::class, 'toggle'])->name('toggle');
        Route::post('/recompute', [CategoryMapController::class, 'recompute'])->name('recompute');
    });
