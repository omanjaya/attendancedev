<?php

/**
 * Laravel OPcache Preload Script
 * 
 * This script preloads commonly used classes into OPcache for faster performance.
 * It's executed once when PHP-FPM starts.
 * 
 * Enable in php.ini:
 * opcache.preload=/var/www/html/preload.php
 * opcache.preload_user=www-data
 */

// Only run in CLI context during preload
if (php_sapi_name() !== 'cli') {
    return;
}

// Autoload composer
require_once __DIR__ . '/vendor/autoload.php';

// Core Laravel Framework Classes
$preloadClasses = [
    // Illuminate Core
    \Illuminate\Foundation\Application::class,
    \Illuminate\Http\Request::class,
    \Illuminate\Http\Response::class,
    \Illuminate\Http\JsonResponse::class,
    \Illuminate\Routing\Router::class,
    \Illuminate\Routing\Route::class,
    \Illuminate\Database\Eloquent\Model::class,
    \Illuminate\Database\Eloquent\Builder::class,
    \Illuminate\Database\Query\Builder::class,
    \Illuminate\Support\Collection::class,
    \Illuminate\Support\Facades\Facade::class,
    \Illuminate\Support\ServiceProvider::class,
    \Illuminate\Auth\AuthManager::class,
    \Illuminate\Cache\CacheManager::class,
    \Illuminate\Session\SessionManager::class,
    \Illuminate\Validation\Validator::class,
    
    // Database
    \Illuminate\Database\Connection::class,
    \Illuminate\Database\PostgresConnection::class,
    \Illuminate\Database\Eloquent\Relations\HasMany::class,
    \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
    \Illuminate\Database\Eloquent\Relations\BelongsToMany::class,
    
    // HTTP
    \Illuminate\Http\Middleware\HandleCors::class,
    \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
    \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
];

// Application Models
$appModels = [
    \App\Models\User::class,
    \App\Models\Employee::class,
    \App\Models\Attendance::class,
    \App\Models\Location::class,
    \App\Models\MonthlySchedule::class,
    \App\Models\EmployeeMonthlySchedule::class,
    \App\Models\Leave::class,
    \App\Models\LeaveType::class,
    \App\Models\Holiday::class,
];

// Application Controllers
$appControllers = [
    \App\Http\Controllers\Api\AttendanceController::class,
    \App\Http\Controllers\Api\EmployeeApiController::class,
    \App\Http\Controllers\Api\AuthController::class,
    \App\Http\Controllers\Api\ScheduleApiController::class,
    \App\Http\Controllers\Api\MonthlyScheduleApiController::class,
];

// Services
$appServices = [
    \App\Services\AttendanceService::class,
    \App\Services\FaceRecognitionService::class,
];

// Merge all classes
$allClasses = array_merge(
    $preloadClasses,
    $appModels,
    $appControllers,
    $appServices
);

// Preload classes
foreach ($allClasses as $class) {
    try {
        if (class_exists($class)) {
            // Class is now loaded into OPcache
        }
    } catch (\Throwable $e) {
        // Skip classes that can't be preloaded
    }
}

// Preload commonly used config files
$configFiles = [
    __DIR__ . '/bootstrap/cache/config.php',
    __DIR__ . '/bootstrap/cache/routes-v7.php',
    __DIR__ . '/bootstrap/cache/services.php',
];

foreach ($configFiles as $file) {
    if (file_exists($file) && function_exists('opcache_compile_file')) {
        opcache_compile_file($file);
    }
}
