<?php

use App\Http\Controllers\HealthCheckController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Health Check Routes
|--------------------------------------------------------------------------
|
| System health monitoring endpoints for operational monitoring and
| alerting systems. These endpoints provide various levels of health
| information about the application and its dependencies.
|
*/

// Basic health check (public, no authentication required)
Route::get('/health', [HealthCheckController::class, 'index'])
    ->name('health.basic');

// Detailed system health check (requires authentication)
Route::middleware(['auth'])->group(function () {
    Route::get('/health/detailed', [HealthCheckController::class, 'detailed'])
        ->name('health.detailed');

    Route::get('/health/application', [HealthCheckController::class, 'application'])
        ->name('health.application');
});

// Administrative health checks (requires admin permissions)
Route::middleware(['auth', 'permission:manage_system'])->group(function () {
    Route::get('/health/admin', [HealthCheckController::class, 'detailed'])
        ->name('health.admin');
});
