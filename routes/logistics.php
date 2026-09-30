<?php

use App\Http\Controllers\Logistics\CategoryMapController;
use App\Http\Controllers\Logistics\DeliveryPartCancelController;
use App\Http\Controllers\Logistics\DeliveryPartController;
use App\Http\Controllers\Logistics\DeliveryPartSpbController;
use App\Http\Controllers\Logistics\GrpoSummaryController;
use App\Http\Controllers\Logistics\InventorySummaryController;
use App\Http\Controllers\Logistics\UsageSummaryController;
use App\Http\Controllers\Logistics\WarehouseProjectMappingController;
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

Route::prefix('logistics/delivery-part')
    ->name('logistics.delivery-part.')
    ->middleware(['auth', 'active.user', 'permission:view-delivery-part'])
    ->group(function () {
        Route::get('/', [DeliveryPartController::class, 'index'])->name('index');
        Route::get('/data', [DeliveryPartController::class, 'data'])->name('data');
        Route::post('/entry', [DeliveryPartController::class, 'storeEntry'])
            ->middleware('permission:edit-delivery-part')
            ->name('entry.store');
        Route::patch('/entry/{entry}', [DeliveryPartController::class, 'updateEntry'])
            ->middleware('permission:edit-delivery-part')
            ->name('entry.update');
        Route::post('/refresh', [DeliveryPartController::class, 'refresh'])->name('refresh');
        Route::get('/export', [DeliveryPartController::class, 'export'])
            ->middleware('permission:export-delivery-part')
            ->name('export');

        Route::get('/spb/data', [DeliveryPartSpbController::class, 'data'])->name('spb.data');
        Route::get('/spb/{spb}', [DeliveryPartSpbController::class, 'show'])->name('spb.show');
        Route::post('/spb', [DeliveryPartSpbController::class, 'store'])
            ->middleware('permission:edit-delivery-part')
            ->name('spb.store');
        Route::put('/spb/{spb}', [DeliveryPartSpbController::class, 'update'])
            ->middleware('permission:edit-delivery-part')
            ->name('spb.update');
        Route::delete('/spb/{spb}', [DeliveryPartSpbController::class, 'destroy'])
            ->middleware('permission:edit-delivery-part')
            ->name('spb.destroy');

        Route::post('/cancel', [DeliveryPartCancelController::class, 'store'])
            ->middleware('permission:cancel-ito')
            ->name('cancel.store');
        Route::get('/cancel/data', [DeliveryPartCancelController::class, 'data'])->name('cancel.data');
    });

Route::prefix('logistics/warehouse-projects')
    ->name('logistics.warehouse-projects.')
    ->middleware(['auth', 'active.user', 'permission:manage-delivery-part-mapping'])
    ->group(function () {
        Route::get('/', [WarehouseProjectMappingController::class, 'index'])->name('index');
        Route::post('/', [WarehouseProjectMappingController::class, 'store'])->name('store');
        Route::put('/{mapping}', [WarehouseProjectMappingController::class, 'update'])->name('update');
        Route::patch('/{mapping}/toggle', [WarehouseProjectMappingController::class, 'toggle'])->name('toggle');
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
