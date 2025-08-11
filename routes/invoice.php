<?php

use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoiceAttachmentController;
use Illuminate\Support\Facades\Route;

// Invoices data endpoint (define before resource to avoid shadowing by {invoice})
Route::get('/invoices/data', [InvoiceController::class, 'data'])->name('invoices.data');

// Invoice validation endpoint
Route::post('/invoices/validate-invoice-number', [InvoiceController::class, 'validateInvoiceNumber'])->name('invoices.validate-invoice-number');

// Session check endpoint
Route::get('/invoices/check-session', [InvoiceController::class, 'checkSession'])->name('invoices.check-session');

// Optional dashboard redirect within invoices section
Route::get('/invoices/dashboard', function () {
    return redirect()->route('invoices.index');
})->name('invoices.dashboard');

// Invoice attachment routes
Route::get('/invoices/attachments', [InvoiceAttachmentController::class, 'index'])->name('invoices.attachments.index');
Route::get('/invoices/attachments/data', [InvoiceAttachmentController::class, 'data'])->name('invoices.attachments.data');
Route::post('/invoices/{invoice}/attachments', [InvoiceAttachmentController::class, 'store'])->name('invoices.attachments.store');
Route::get('/invoices/attachments/{attachment}/show', [InvoiceAttachmentController::class, 'show'])->name('invoices.attachments.show');
Route::put('/invoices/attachments/{attachment}', [InvoiceAttachmentController::class, 'update'])->name('invoices.attachments.update');
Route::get('/invoices/attachments/{attachment}/download', [InvoiceAttachmentController::class, 'download'])->name('invoices.attachments.download');
Route::delete('/invoices/attachments/{attachment}', [InvoiceAttachmentController::class, 'destroy'])->name('invoices.attachments.destroy');

// API routes for invoice attachments
use App\Http\Controllers\Api\InvoiceAttachmentController as ApiInvoiceAttachmentController;

Route::get('/api/invoices/{invoice}/attachments', [ApiInvoiceAttachmentController::class, 'getInvoiceAttachments']);
Route::get('/api/invoices/attachments/stats', [ApiInvoiceAttachmentController::class, 'getAttachmentStats']);

// Resource routes for invoices (index, create, store, show, edit, update, destroy)
Route::resource('/invoices', InvoiceController::class);
