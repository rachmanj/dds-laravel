<?php

use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoiceAttachmentController;
use App\Http\Controllers\InvoiceDashboardController;
use App\Http\Controllers\InvoicePaymentController;
use App\Http\Controllers\SapUpdateController;
use Illuminate\Support\Facades\Route;


Route::prefix('invoices')->name('invoices.')->group(function () {
    // Invoices data endpoint (define before resource to avoid shadowing by {invoice})
    Route::get('/data', [InvoiceController::class, 'data'])->name('data');

    // Invoice validation endpoint
    Route::post('/validate-invoice-number', [InvoiceController::class, 'validateInvoiceNumber'])->name('validate-invoice-number');

    // SAP document validation endpoint
    Route::post('/validate-sap-doc', [InvoiceController::class, 'validateSapDoc'])->name('validate-sap-doc');

    // Session check endpoint
    Route::get('/check-session', [InvoiceController::class, 'checkSession'])->name('check-session');

    // Dashboard route
    Route::get('/dashboard', [InvoiceDashboardController::class, 'index'])->name('dashboard');

    // Payment routes
    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/dashboard', [InvoicePaymentController::class, 'dashboard'])->name('dashboard');
        Route::get('/waiting', [InvoicePaymentController::class, 'waitingPayment'])->name('waiting');
        Route::get('/paid', [InvoicePaymentController::class, 'paidInvoices'])->name('paid');
        Route::put('/{invoice}/update', [InvoicePaymentController::class, 'updatePayment'])->name('update');
        Route::put('/{invoice}/update-paid', [InvoicePaymentController::class, 'updatePaidInvoice'])->name('update-paid');
        Route::post('/bulk-update', [InvoicePaymentController::class, 'bulkUpdatePayment'])->name('bulk-update');
    });

    // SAP Update routes
    Route::prefix('sap-update')->name('sap-update.')->group(function () {
        // Main index page (dashboard)
        Route::get('/', [SapUpdateController::class, 'index'])->name('index');

        // Dashboard data endpoint
        Route::get('/dashboard-data', [SapUpdateController::class, 'dashboard'])->name('dashboard-data');

        // Without SAP Doc page and data
        Route::get('/without-sap', [SapUpdateController::class, 'withoutSapPage'])->name('without-sap-page');
        Route::get('/without-sap-data', [SapUpdateController::class, 'withoutSap'])->name('without-sap');

        // With SAP Doc page and data
        Route::get('/with-sap', [SapUpdateController::class, 'withSapPage'])->name('with-sap-page');
        Route::get('/with-sap-data', [SapUpdateController::class, 'withSap'])->name('with-sap');

        // SAP Doc update and validation
        Route::put('/{invoice}/update-sap-doc', [SapUpdateController::class, 'updateSapDoc'])->name('update-sap-doc');
        Route::post('/validate-sap-doc', [SapUpdateController::class, 'validateSapDoc'])->name('validate-sap-doc');
    });

    Route::post('/{invoice}/attachments', [InvoiceAttachmentController::class, 'store'])->name('attachments.store');

    // Search additional documents by PO number (AJAX)
    Route::post('/search-additional-documents', [InvoiceController::class, 'searchAdditionalDocuments'])->name('search-additional-documents');

    // Supplier defaults for auto-suggestion (AJAX)
    Route::get('/supplier-defaults/{supplier}', [InvoiceController::class, 'getSupplierDefaults'])->name('supplier-defaults');

    // Check for duplicate invoice (AJAX)
    Route::post('/check-duplicate', [InvoiceController::class, 'checkDuplicate'])->name('check-duplicate');

    // Get recent invoices for quick fill (AJAX)
    Route::get('/recent-for-autofill', [InvoiceController::class, 'getRecentInvoices'])->name('recent-for-autofill');

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
