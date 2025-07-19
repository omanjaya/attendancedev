<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Main application routes including dashboard, profile, and core functionality.
| Domain-specific routes are organized in separate files for better maintainability.
|
*/

// Root redirect
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Dashboard routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Main Dashboard (modern floating version)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/modern', [DashboardController::class, 'modern'])->name('dashboard.modern');

    // Dashboard API endpoints
    Route::prefix('api/dashboard')->group(function () {
        Route::get('/data', [DashboardController::class, 'getData'])->name('dashboard.api.data');
        Route::get('/chart-data', [DashboardController::class, 'getChartData'])->name('dashboard.api.chart-data');
        Route::get('/widget/{widget}', [DashboardController::class, 'getWidgetData'])->name('dashboard.api.widget');
    });

    // Profile Management (Laravel Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Settings redirect
    Route::get('/settings', function () {
        return redirect()->route('system.settings');
    })->name('settings');

    // Demo routes (development only - restrict in production)
    Route::prefix('demo')
        ->middleware('permission:access_admin_panel')
        ->group(function () {
            Route::get('/notifications', function () {
                return view('demo.notifications');
            })->name('demo.notifications');

            Route::post('/notifications/test', function () {
                auth()
                    ->user()
                    ->notify(
                        new \App\Notifications\TestNotification([
                            'message' => 'This is a demo notification sent at '.now()->format('H:i:s'),
                            'type' => 'demo',
                        ]),
                    );

                return response()->json([
                    'message' => 'Demo notification sent successfully',
                    'timestamp' => now(),
                ]);
            })->name('demo.notifications.test');

            Route::get('/components', function () {
                return view('pages.demo.components');
            })->name('demo.components');

            Route::get('/mobile', function () {
                return view('pages.demo.mobile');
            })->name('demo.mobile');

            Route::get('/performance', function () {
                return view('pages.demo.performance');
            })->name('demo.performance');

            Route::get('/impersonation', function () {
                return view('pages.demo.impersonation');
            })->name('demo.impersonation');

            Route::get('/role-switch', function () {
                return view('pages.demo.role-switch');
            })->name('demo.role-switch');
        });

    // Role Switching routes (Superadmin only)
    Route::prefix('role-switch')
        ->group(function () {
            Route::post('/switch/{role}', [App\Http\Controllers\RoleSwitchController::class, 'switchRole'])
                ->middleware(['role:superadmin'])
                ->name('role.switch');

            Route::post('/restore', [App\Http\Controllers\RoleSwitchController::class, 'restoreRole'])
                ->name('role.restore');

            Route::get('/available', [App\Http\Controllers\RoleSwitchController::class, 'getAvailableRoles'])
                ->name('role.available');
        });

    // Impersonation routes (Admin/Super Admin only)
    Route::prefix('impersonate')
        ->middleware(['permission:impersonate_users'])
        ->group(function () {
            Route::post('/start/{user}', [App\Http\Controllers\ImpersonationController::class, 'start'])
                ->name('impersonate.start');

            Route::post('/stop', [App\Http\Controllers\ImpersonationController::class, 'stop'])
                ->name('impersonate.stop');

            Route::get('/users', [App\Http\Controllers\ImpersonationController::class, 'getUserList'])
                ->name('impersonate.users');
        });
});

// Face verification API endpoints for browser-based attendance
Route::prefix('api/face-verification')
    ->middleware(['auth'])
    ->group(function () {
        Route::get('/test', function () {
            return response()->json([
                'success' => true,
                'user' => auth()->user()?->name,
                'authenticated' => auth()->check(),
            ]);
        });
        Route::get('/profile-data', [
            App\Http\Controllers\FaceVerificationController::class,
            'getProfileData',
        ]);
        Route::post('/save-descriptor', [
            App\Http\Controllers\FaceVerificationController::class,
            'saveFaceDescriptor',
        ]);
        Route::post('/verify', [
            App\Http\Controllers\FaceVerificationController::class,
            'verifyFace',
        ]);
    });

/*
|--------------------------------------------------------------------------
| Include Domain-Specific Route Files
|--------------------------------------------------------------------------
|
| Each domain has its own route file for better organization and maintainability
|
*/

// Include all domain-specific route files
require __DIR__.'/auth.php'; // Authentication routes (Laravel Breeze)
require __DIR__.'/security.php'; // Two-factor auth and security
require __DIR__.'/attendance.php'; // Attendance management
require __DIR__.'/employees.php'; // Employee management
require __DIR__.'/leave.php'; // Leave management
require __DIR__.'/schedules.php'; // Schedule management
require __DIR__.'/payroll.php'; // Payroll management
require __DIR__.'/system.php'; // System administration
require __DIR__.'/reports.php'; // Reports and analytics
require __DIR__.'/holidays.php'; // Holiday and school calendar management
require __DIR__.'/manual_attendance.php'; // Manual attendance entry
