<?php

use App\Http\Controllers\Api\InvoiceApiController;
use App\Http\Controllers\Api\SupplierApiController;
use App\Http\Controllers\Api\DistributionDocumentController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\ProcessingAnalyticsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Health check endpoint (no authentication required) - Place this FIRST
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toISOString(),
        'version' => '1.0.0'
    ]);
});

// API v1 routes with authentication and rate limiting
Route::prefix('v1')->middleware(['api.key', 'api.rate.limit'])->group(function () {

    // Get invoices by department location code
    Route::get('/departments/{location_code}/invoices', [InvoiceApiController::class, 'getInvoicesByDepartment']);

    // New payment status endpoints
    Route::get('/departments/{location_code}/wait-payment-invoices', [InvoiceApiController::class, 'getWaitPaymentInvoicesByDepartment']);
    Route::get('/departments/{location_code}/paid-invoices', [InvoiceApiController::class, 'getPaidInvoicesByDepartment']);

    // Invoice payment update endpoint
    Route::put('/invoices/{invoice_id}/payment', [InvoiceApiController::class, 'updateInvoicePayment']);

    // Get invoice by document number (invoice number or additional document number)
    Route::get('/documents/{document_number?}', [InvoiceApiController::class, 'getInvoiceByDocumentNumber']);

    // Get available departments for reference
    Route::get('/departments', [InvoiceApiController::class, 'getDepartments']);

    // Supplier API endpoints for vendor code and PO suggestions
    Route::get('/suppliers/sap-codes', [SupplierApiController::class, 'getSapCodes']);
    Route::get('/suppliers/{id}', [SupplierApiController::class, 'getSupplier']);
    Route::post('/suppliers/validate-vendor-code', [SupplierApiController::class, 'validateVendorCode']);
    Route::post('/suppliers/po-suggestions', [SupplierApiController::class, 'getPoSuggestions']);

    // Distribution Document API endpoints
    Route::prefix('distributions')->group(function () {
        Route::put('/documents/{documentId}/status', [DistributionDocumentController::class, 'updateStatus']);
        Route::post('/documents/{documentId}/verify', [DistributionDocumentController::class, 'verify']);
        Route::put('/documents/{documentId}/notes', [DistributionDocumentController::class, 'addNotes']);
        Route::post('/bulk-update-status', [DistributionDocumentController::class, 'bulkUpdateStatus']);
        Route::post('/bulk-verify', [DistributionDocumentController::class, 'bulkVerify']);
        Route::post('/bulk-add-notes', [DistributionDocumentController::class, 'bulkAddNotes']);
        Route::post('/bulk-export', [DistributionDocumentController::class, 'bulkExport']);
        Route::post('/bulk-print', [DistributionDocumentController::class, 'bulkPrint']);
    });
});

// Analytics API endpoints (no authentication required for internal analytics)
Route::prefix('v1/analytics')->group(function () {
    Route::post('/distribution', [AnalyticsController::class, 'storeDistributionAnalytics']);
    Route::get('/performance-metrics', [AnalyticsController::class, 'getPerformanceMetrics']);
    Route::get('/user-behavior', [AnalyticsController::class, 'getUserBehaviorAnalytics']);
    Route::get('/document-flow', [AnalyticsController::class, 'getDocumentFlowAnalytics']);
    Route::get('/real-time-dashboard', [AnalyticsController::class, 'getRealTimeDashboard']);
    Route::get('/predictive', [AnalyticsController::class, 'getPredictiveAnalytics']);
});

// Processing Analytics API endpoints
Route::prefix('v1/processing-analytics')->group(function () {
    Route::get('/monthly/{year}/{month}', [ProcessingAnalyticsController::class, 'getMonthlyProcessing']);
    Route::get('/department/{departmentId}', [ProcessingAnalyticsController::class, 'getDepartmentProcessing']);
    Route::get('/trends/{monthsBack?}', [ProcessingAnalyticsController::class, 'getProcessingTrends']);
    Route::get('/overview', [ProcessingAnalyticsController::class, 'getMonthlyOverview']);
    Route::get('/efficiency', [ProcessingAnalyticsController::class, 'getDepartmentEfficiency']);
    Route::get('/export', [ProcessingAnalyticsController::class, 'exportMonthlyReport']);

    // Enhanced Processing Analytics endpoints
    Route::get('/accurate-processing-days', [ProcessingAnalyticsController::class, 'getAccurateProcessingDays']);
    Route::get('/document-timeline', [ProcessingAnalyticsController::class, 'getDocumentTimeline']);
    Route::get('/department-efficiency-accurate', [ProcessingAnalyticsController::class, 'getDepartmentEfficiencyAccurate']);
    Route::get('/processing-bottlenecks', [ProcessingAnalyticsController::class, 'getProcessingBottlenecks']);
    Route::get('/slow-processing-documents', [ProcessingAnalyticsController::class, 'getSlowProcessingDocuments']);
    Route::get('/department-monthly-performance', [ProcessingAnalyticsController::class, 'getDepartmentMonthlyPerformance']);
});
