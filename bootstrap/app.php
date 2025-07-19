<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/health.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission.any' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'performance.monitor' => \App\Http\Middleware\PerformanceMonitor::class,
            '2fa.rate_limit' => \App\Http\Middleware\TwoFactorRateLimit::class,
            'security.logger' => \App\Http\Middleware\SecurityLogger::class,
            'persistent.auth' => \App\Http\Middleware\EnsurePersistentAuth::class,
            'impersonation' => \App\Http\Middleware\ImpersonationMiddleware::class,
            'error.boundary' => \App\Http\Middleware\ErrorBoundary::class,
        ]);

        // Add security logging to web middleware group (always enabled for security tracking)
        $middleware->web(append: [
            \App\Http\Middleware\SecurityLogger::class,
            \App\Http\Middleware\ImpersonationMiddleware::class,
            \App\Http\Middleware\ErrorBoundary::class,
        ]);

        // Add performance monitoring to web middleware group (disabled in development for performance)
        if (env('PERFORMANCE_MONITOR_ENABLED', false)) {
            $middleware->web(append: [\App\Http\Middleware\PerformanceMonitor::class]);
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
