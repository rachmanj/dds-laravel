<?php

use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoiceAttachmentController;
use Illuminate\Support\Facades\Route;


Route::prefix('invoices')->name('invoices.')->group(function () {
    // Invoices data endpoint (define before resource to avoid shadowing by {invoice})
    Route::get('/data', [InvoiceController::class, 'data'])->name('data');

    // Invoice validation endpoint
    Route::post('/validate-invoice-number', [InvoiceController::class, 'validateInvoiceNumber'])->name('validate-invoice-number');

    // Session check endpoint
    Route::get('/check-session', [InvoiceController::class, 'checkSession'])->name('check-session');

    // Optional dashboard redirect within invoices section
    Route::get('/dashboard', function () {
        return redirect()->route('invoices.index');
    })->name('dashboard');

    Route::post('/{invoice}/attachments', [InvoiceAttachmentController::class, 'store'])->name('attachments.store');

    // Search additional documents by PO number (AJAX)
    Route::post('/search-additional-documents', [InvoiceController::class, 'searchAdditionalDocuments'])->name('search-additional-documents');

    // Invoice attachment routes
    Route::prefix('attachments')->group(function () {
        Route::get('/{invoice}/show', [InvoiceAttachmentController::class, 'show'])->name('attachments.show');
        Route::get('', [InvoiceAttachmentController::class, 'index'])->name('attachments.index');
        Route::get('/data', [InvoiceAttachmentController::class, 'data'])->name('attachments.data');
        Route::put('/{attachment}', [InvoiceAttachmentController::class, 'update'])->name('attachments.update');
        Route::get('/{attachment}/download', [InvoiceAttachmentController::class, 'download'])->name('attachments.download');
        Route::get('/{attachment}/preview', [InvoiceAttachmentController::class, 'preview'])->name('attachments.preview');
        Route::delete('/{attachment}', [InvoiceAttachmentController::class, 'destroy'])->name('attachments.destroy');
    });
});
// API routes for invoice attachments
use App\Http\Controllers\Api\InvoiceAttachmentController as ApiInvoiceAttachmentController;

Route::get('/api/invoices/{invoice}/attachments', [ApiInvoiceAttachmentController::class, 'getInvoiceAttachments']);
Route::get('/api/invoices/attachments/stats', [ApiInvoiceAttachmentController::class, 'getAttachmentStats']);

// Resource routes for invoices (index, create, store, show, edit, update, destroy)
Route::resource('/invoices', InvoiceController::class);
