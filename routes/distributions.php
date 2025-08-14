<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DistributionController;

Route::prefix('distributions')->name('distributions.')->group(function () {
    Route::get('/numbering-stats', [DistributionController::class, 'numberingStatsView'])->name('numbering-stats');
    // Resource routes
    Route::get('/', [DistributionController::class, 'index'])->name('index');
    Route::get('/create', [DistributionController::class, 'create'])->name('create');
    Route::post('/', [DistributionController::class, 'store'])->name('store');
    Route::get('/{distribution}', [DistributionController::class, 'show'])->name('show');
    Route::get('/{distribution}/edit', [DistributionController::class, 'edit'])->name('edit');
    Route::put('/{distribution}', [DistributionController::class, 'update'])->name('update');
    Route::delete('/{distribution}', [DistributionController::class, 'destroy'])->name('destroy');

    // Workflow transition routes
    Route::post('/{distribution}/verify-sender', [DistributionController::class, 'verifyBySender'])->name('verify-sender');
    Route::post('/{distribution}/send', [DistributionController::class, 'send'])->name('send');
    Route::post('/{distribution}/receive', [DistributionController::class, 'receive'])->name('receive');
    Route::post('/{distribution}/verify-receiver', [DistributionController::class, 'verifyByReceiver'])->name('verify-receiver');
    Route::post('/{distribution}/complete', [DistributionController::class, 'complete'])->name('complete');

    // Additional routes
    Route::get('/{distribution}/history', [DistributionController::class, 'history'])->name('history');
    Route::get('/{distribution}/discrepancy-summary', [DistributionController::class, 'discrepancySummary'])->name('discrepancy-summary');

    // Utility routes
    Route::get('/next-sequence', [DistributionController::class, 'getNextSequence'])->name('next-sequence');
});
