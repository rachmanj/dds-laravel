<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdditionalDocumentDashboardController;

// Additional Documents Routes (accessible to all authenticated users)
Route::prefix('additional-documents')->name('additional-documents.')->group(function () {
    // Special routes that need to come before the resource routes
    Route::get('dashboard', [AdditionalDocumentDashboardController::class, 'index'])->name('dashboard');
    Route::get('data', [\App\Http\Controllers\AdditionalDocumentController::class, 'data'])->name('data');
    Route::get('import', [\App\Http\Controllers\AdditionalDocumentController::class, 'import'])->name('import');
    Route::post('import', [\App\Http\Controllers\AdditionalDocumentController::class, 'processImport'])->name('process-import');
    Route::get('download-template', [\App\Http\Controllers\AdditionalDocumentController::class, 'downloadTemplate'])->name('download-template');
    Route::get('export', [\App\Http\Controllers\AdditionalDocumentController::class, 'export'])->name('export');

    // Search presets routes
    Route::prefix('search-presets')->name('search-presets.')->group(function () {
        Route::get('/', [\App\Http\Controllers\AdditionalDocumentController::class, 'searchPresetsIndex'])->name('index');
        Route::post('/', [\App\Http\Controllers\AdditionalDocumentController::class, 'searchPresetsStore'])->name('store');
        Route::get('/{id}', [\App\Http\Controllers\AdditionalDocumentController::class, 'searchPresetsShow'])->name('show');
        Route::delete('/{id}', [\App\Http\Controllers\AdditionalDocumentController::class, 'searchPresetsDestroy'])->name('destroy');
    });

    // Resource routes with explicit parameter name
    Route::get('/', [\App\Http\Controllers\AdditionalDocumentController::class, 'index'])->name('index');
    Route::get('create', [\App\Http\Controllers\AdditionalDocumentController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\AdditionalDocumentController::class, 'store'])->name('store');

    // On-the-fly creation for invoice forms
    Route::post('on-the-fly', [\App\Http\Controllers\AdditionalDocumentController::class, 'createOnTheFly'])->name('on-the-fly');
    Route::get('{additionalDocument}', [\App\Http\Controllers\AdditionalDocumentController::class, 'show'])->name('show');
    Route::get('{additionalDocument}/edit', [\App\Http\Controllers\AdditionalDocumentController::class, 'edit'])->name('edit');
    Route::put('{additionalDocument}', [\App\Http\Controllers\AdditionalDocumentController::class, 'update'])->name('update');
    Route::patch('{additionalDocument}', [\App\Http\Controllers\AdditionalDocumentController::class, 'update'])->name('update');
    Route::delete('{additionalDocument}', [\App\Http\Controllers\AdditionalDocumentController::class, 'destroy'])->name('destroy');
    Route::get('{additionalDocument}/download', [\App\Http\Controllers\AdditionalDocumentController::class, 'downloadAttachment'])->name('download');
});
