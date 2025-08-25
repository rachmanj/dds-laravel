<?php

use App\Http\Controllers\Api\InvoiceApiController;
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

    // Get available departments for reference
    Route::get('/departments', [InvoiceApiController::class, 'getDepartments']);
});
