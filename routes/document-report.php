<?php

use App\Http\Controllers\ReportsDocumentController;
use Illuminate\Support\Facades\Route;

Route::prefix('reports/document-report')->name('document-report.')->middleware('permission:view-document-report')->group(function () {
    Route::get('/invoices', [ReportsDocumentController::class, 'invoicesIndex'])->name('invoices');
    Route::get('/additional-documents', [ReportsDocumentController::class, 'additionalDocumentsIndex'])->name('additional-documents');
    Route::get('/invoices-data', [ReportsDocumentController::class, 'invoicesData'])->name('invoices-data');
    Route::get('/additional-documents-data', [ReportsDocumentController::class, 'additionalDocumentsData'])->name('additional-documents-data');
    Route::get('/invoices-export', [ReportsDocumentController::class, 'exportInvoices'])->name('invoices-export');
    Route::get('/additional-documents-export', [ReportsDocumentController::class, 'exportAdditionalDocuments'])->name('additional-documents-export');
    Route::get('/invoices/{invoice}', [ReportsDocumentController::class, 'invoiceDetail'])->name('invoice-detail');
    Route::get('/additional-documents/{additionalDocument}', [ReportsDocumentController::class, 'additionalDocumentDetail'])->name('additional-document-detail');
});
