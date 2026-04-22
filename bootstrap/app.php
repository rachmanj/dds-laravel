<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $wita = 'Asia/Makassar';

        $schedule->command('sap:sync-ito --today')
            ->hourly()
            ->timezone($wita)
            ->withoutOverlapping();

        $schedule->command('sap:sync-ito --yesterday')
            ->dailyAt('00:10')
            ->timezone($wita)
            ->withoutOverlapping();

        $schedule->command('solar:price:sync-from-last-pertamina')
            ->dailyAt('07:30')
            ->timezone($wita)
            ->withoutOverlapping();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'telegram/webhook/*',
        ]);

        // Register Spatie Permission middleware aliases
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'active.user' => \App\Http\Middleware\CheckActiveUser::class,
            'api.key' => \App\Http\Middleware\ApiKeyMiddleware::class,
            'api.rate.limit' => \App\Http\Middleware\ApiRateLimitMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
