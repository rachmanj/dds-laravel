<?php

use Illuminate\Support\Facades\Route;

// Additional Documents Routes (accessible to all authenticated users)
Route::prefix('additional-documents')->name('additional-documents.')->group(function () {
    Route::get('dashboard', function () {
        return redirect()->route('additional-documents.index');
    })->name('dashboard');
    Route::get('data', [\App\Http\Controllers\AdditionalDocumentController::class, 'data'])->name('data');
    Route::get('{additionalDocument}/download', [\App\Http\Controllers\AdditionalDocumentController::class, 'downloadAttachment'])->name('download');

    // Import routes
    Route::get('import', [\App\Http\Controllers\AdditionalDocumentController::class, 'import'])->name('import');
    Route::post('import', [\App\Http\Controllers\AdditionalDocumentController::class, 'processImport'])->name('process-import');
    Route::get('download-template', [\App\Http\Controllers\AdditionalDocumentController::class, 'downloadTemplate'])->name('download-template');

    // Use resource route without additional prefix
    Route::resource('', \App\Http\Controllers\AdditionalDocumentController::class)->names([
        'index' => 'index',
        'create' => 'create',
        'store' => 'store',
        'show' => 'show',
        'edit' => 'edit',
        'update' => 'update',
        'destroy' => 'destroy',
    ]);
});
