<?php

namespace App\Providers;

use App\Models\AssistantConversation;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Pennant\Feature;
use Laravel\Pennant\Pennant;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Route::bind('conversation', function (string $value) {
            $userId = Auth::id();
            if ($userId === null) {
                abort(404);
            }

            return AssistantConversation::query()
                ->whereKey($value)
                ->where('user_id', $userId)
                ->firstOrFail();
        });

        // Use custom pagination view with FontAwesome icons
        Paginator::defaultView('vendor.pagination.bootstrap-4-custom');

        // Pennant::use('database');
        // Feature::define('sap-sync', false); // Default off for query sync
        // Feature::define('sap-invoice', false); // Default off for invoice creation
    }
}
