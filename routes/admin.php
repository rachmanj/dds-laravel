<?php

use Illuminate\Support\Facades\Route;

// Admin Routes
Route::prefix('admin')->name('admin.')->middleware(['role:superadmin|admin'])->group(function () {
    Route::get('users/data', [\App\Http\Controllers\Admin\UserController::class, 'data'])->name('users.data');
    Route::resource('users', \App\Http\Controllers\Admin\UserController::class);
    Route::patch('users/{user}/toggle-status', [\App\Http\Controllers\Admin\UserController::class, 'toggleStatus'])->name('users.toggle-status');

    Route::get('roles/data', [\App\Http\Controllers\Admin\RoleController::class, 'data'])->name('roles.data');
    Route::resource('roles', \App\Http\Controllers\Admin\RoleController::class);

    Route::get('permissions/data', [\App\Http\Controllers\Admin\PermissionController::class, 'data'])->name('permissions.data');
    Route::resource('permissions', \App\Http\Controllers\Admin\PermissionController::class)->except(['create', 'edit']);

    Route::get('projects/data', [\App\Http\Controllers\Admin\ProjectController::class, 'data'])->name('projects.data');
    Route::resource('projects', \App\Http\Controllers\Admin\ProjectController::class)->except(['create', 'edit']);

    Route::get('departments/data', [\App\Http\Controllers\Admin\DepartmentController::class, 'data'])->name('departments.data');
    Route::resource('departments', \App\Http\Controllers\Admin\DepartmentController::class)->except(['create', 'edit']);

    Route::get('additional-document-types/data', [\App\Http\Controllers\Admin\AdditionalDocumentTypeController::class, 'data'])->name('additional-document-types.data');
    Route::resource('additional-document-types', \App\Http\Controllers\Admin\AdditionalDocumentTypeController::class)->except(['create', 'edit']);

    Route::get('invoice-types/data', [\App\Http\Controllers\Admin\InvoiceTypeController::class, 'data'])->name('invoice-types.data');
    Route::resource('invoice-types', \App\Http\Controllers\Admin\InvoiceTypeController::class)->except(['create', 'edit']);

    Route::get('suppliers/data', [\App\Http\Controllers\Admin\SupplierController::class, 'data'])->name('suppliers.data');
    Route::post('suppliers/import', [\App\Http\Controllers\Admin\SupplierController::class, 'import'])->name('suppliers.import');
    Route::resource('suppliers', \App\Http\Controllers\Admin\SupplierController::class)->except(['create', 'edit']);
});
