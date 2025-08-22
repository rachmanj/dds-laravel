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
});
