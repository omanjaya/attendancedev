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
        // Enable CORS for API routes
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
            \App\Http\Middleware\AddRequestId::class, // Add Request ID for error tracking
        ], append: [
            \App\Http\Middleware\EnsureUserIsActive::class,
            \App\Http\Middleware\ForcePasswordChangeMiddleware::class, // Security: Enforce password change
        ]);

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
            'force.password.change' => \App\Http\Middleware\ForcePasswordChangeMiddleware::class, // Security: Force password change
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
        // Enhanced exception rendering dengan detail untuk debugging
        $exceptions->render(function (Throwable $e, $request) {
            // Only for API requests
            if ($request->is('api/*') || $request->expectsJson()) {
                $requestId = $request->attributes->get('request_id', 'unknown');
                $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;

                // Base error response (safe for production)
                $error = [
                    'message' => $e->getMessage() ?: 'Server error occurred',
                    'request_id' => $requestId,
                ];

                // Add detailed information ONLY in development
                // SECURITY: Never expose file paths in production
                if (config('app.debug')) {
                    $error['debug'] = [
                        'exception' => get_class($e),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => collect($e->getTrace())->take(5)->map(function ($trace) {
                            return [
                                'file' => $trace['file'] ?? 'unknown',
                                'line' => $trace['line'] ?? 0,
                                'function' => $trace['function'] ?? 'unknown',
                            ];
                        })->toArray(),
                    ];
                } else {
                    // Production: Only show exception class name (not full path)
                    $error['type'] = class_basename($e);
                }

                return response()->json($error, $statusCode);
            }
        });

        // Log all exceptions dengan context
        $exceptions->report(function (Throwable $e) {
            \Illuminate\Support\Facades\Log::error($e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        });
    })
    ->create();
