<?php

use Illuminate\Support\Facades\Route;
use App\Models\User;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LogoutController;

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);

    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
});

// Logout Route
Route::post('/logout', [LogoutController::class, 'logout'])->name('logout')->middleware('auth');

// Redirect root to dashboard if authenticated, otherwise to login
Route::get('/', function () {
    if (\Illuminate\Support\Facades\Auth::check()) {
        return redirect('/dashboard');
    }
    return redirect('/login');
});

// Protected Routes
Route::middleware(['auth', 'active.user'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

    // Profile Routes
    Route::get('/profile/change-password', [\App\Http\Controllers\ProfileController::class, 'changePassword'])->name('profile.change-password');
    Route::post('/profile/update-password', [\App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('profile.update-password');

    // Include Additional Documents Routes
    require __DIR__ . '/additional-docs.php';

    // Include Invoice Routes
    require __DIR__ . '/invoice.php';

    // Include Distribution Routes
    require __DIR__ . '/distributions.php';

    // Include Admin Routes
    require __DIR__ . '/admin.php';

    // Include Reconcile Routes
    require __DIR__ . '/reconcile.php';

    // Message Routes
    Route::prefix('messages')->name('messages.')->group(function () {
        Route::get('/', [\App\Http\Controllers\MessageController::class, 'index'])->name('index');
        Route::get('/sent', [\App\Http\Controllers\MessageController::class, 'sent'])->name('sent');
        Route::get('/create', [\App\Http\Controllers\MessageController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\MessageController::class, 'store'])->name('store');
        Route::get('/{message}', [\App\Http\Controllers\MessageController::class, 'show'])->name('show');
        Route::delete('/{message}', [\App\Http\Controllers\MessageController::class, 'destroy'])->name('destroy');

        // AJAX Routes
        Route::get('/unread-count', [\App\Http\Controllers\MessageController::class, 'unreadCount'])->name('unread-count');
        Route::post('/{message}/mark-read', [\App\Http\Controllers\MessageController::class, 'markAsRead'])->name('mark-read');
        Route::get('/search-users', [\App\Http\Controllers\MessageController::class, 'searchUsers'])->name('search-users');
    });

    // Supplier API routes for internal use (no API key required)
    Route::prefix('suppliers')->name('suppliers.')->group(function () {
        Route::get('/sap-codes', [\App\Http\Controllers\Api\SupplierApiController::class, 'getSapCodes'])->name('sap-codes');
        Route::get('/{id}', [\App\Http\Controllers\Api\SupplierApiController::class, 'getSupplier'])->name('show');
        Route::post('/validate-vendor-code', [\App\Http\Controllers\Api\SupplierApiController::class, 'validateVendorCode'])->name('validate-vendor-code');
        Route::post('/po-suggestions', [\App\Http\Controllers\Api\SupplierApiController::class, 'getPoSuggestions'])->name('po-suggestions');
    });
});
