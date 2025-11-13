<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Laravel\Pennant\Pennant;
use Laravel\Pennant\Feature;

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
        // Use custom pagination view with FontAwesome icons
        Paginator::defaultView('vendor.pagination.bootstrap-4-custom');

        // Pennant::use('database');
        // Feature::define('sap-sync', false); // Default off for query sync
        // Feature::define('sap-invoice', false); // Default off for invoice creation
    }
}
