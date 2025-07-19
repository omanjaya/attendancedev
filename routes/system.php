<?php

use App\Http\Controllers\Admin\PerformanceController;
use App\Http\Controllers\Admin\SecurityManagementController;
use App\Http\Controllers\Api\ErrorTrackingController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SecurityController as MainSecurityController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| System Management Routes
|--------------------------------------------------------------------------
|
| Routes for system administration, settings, security, and monitoring
|
*/

Route::middleware(['auth', 'verified'])->group(function () {
    // System Settings (Admin only)
    Route::prefix('system')
        ->middleware('permission:manage_system_settings')
        ->group(function () {
            Route::get('/settings', function () {
                return view('pages.settings.settings');
            })->name('system.settings');

            Route::put('/settings', function () {
                // In a real implementation, this would handle the settings update
                return redirect()
                    ->route('system.settings')
                    ->with('success', 'System settings have been updated successfully');
            })->name('system.settings.update');

            Route::get('/permissions', function () {
                $roles = \Spatie\Permission\Models\Role::with('permissions')->get();

                // Group permissions by category (based on naming convention)
                $allPermissions = \Spatie\Permission\Models\Permission::all();
                $permissions = [];

                foreach ($allPermissions as $permission) {
                    $parts = explode('_', $permission->name);
                    $category = $parts[0] ?? 'general'; // First word as category

                    if (! isset($permissions[$category])) {
                        $permissions[$category] = [];
                    }
                    $permissions[$category][] = $permission;
                }

                return view('pages.settings.permissions', compact('roles', 'permissions'));
            })->name('system.permissions');

            // Permission management routes
            Route::prefix('permissions')->group(function () {
                Route::get('/roles/data', [RoleController::class, 'getData'])->name(
                    'system.permissions.roles.data',
                );
                Route::post('/roles/create', [RoleController::class, 'store'])->name(
                    'system.permissions.roles.create',
                );
                Route::post('/roles/{role}/permissions', [
                    RoleController::class,
                    'updatePermissions',
                ])->name('system.permissions.roles.permissions');
                Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name(
                    'system.permissions.roles.destroy',
                );
            });
        });

    // User Management (Admin only)
    Route::prefix('users')
        ->middleware('permission:manage_users')
        ->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('users.index');
            Route::get('/data', [UserController::class, 'getData'])->name('users.data');
            Route::get('/statistics', [UserController::class, 'getStatistics'])->name('users.statistics');
            Route::post('/', [UserController::class, 'store'])->name('users.store');
            Route::get('/{user}', [UserController::class, 'show'])->name('users.show');
            Route::get('/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
            Route::patch('/{user}', [UserController::class, 'update'])->name('users.update');
            Route::delete('/{user}', [UserController::class, 'destroy'])->name('users.destroy');
            Route::patch('/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
        });

    // Location Management (Admin only)
    Route::prefix('locations')
        ->middleware('permission:manage_locations')
        ->group(function () {
            Route::get('/', [LocationController::class, 'index'])->name('locations.index');
            Route::get('/create', [LocationController::class, 'create'])->name('locations.create');
            Route::post('/', [LocationController::class, 'store'])->name('locations.store');
            Route::get('/{location}', [LocationController::class, 'show'])->name('locations.show');
            Route::get('/{location}/edit', [LocationController::class, 'edit'])->name('locations.edit');
            Route::patch('/{location}', [LocationController::class, 'update'])->name('locations.update');
            Route::delete('/{location}', [LocationController::class, 'destroy'])->name(
                'locations.destroy',
            );
        });

    // Security Settings (User level)
    Route::prefix('settings')->group(function () {
        Route::get('/security', function () {
            return view('pages.settings.security');
        })->name('settings.security');
    });

    // Admin Panel (Super Admin only)
    Route::prefix('admin')
        ->middleware('permission:access_admin_panel')
        ->group(function () {
            // Audit Logs Management
            Route::prefix('audit')->group(function () {
                Route::get('/', [AuditLogController::class, 'index'])->name('audit.index');
                Route::get('/{audit}', [AuditLogController::class, 'show'])->name('audit.show');
                Route::get('/export', [AuditLogController::class, 'export'])->name('audit.export');
                Route::post('/cleanup', [AuditLogController::class, 'cleanup'])->name('audit.cleanup');
                Route::get('/stats', [AuditLogController::class, 'stats'])->name('audit.stats');
                Route::get('/data', [AuditLogController::class, 'data'])->name('audit.data');
            });

            // Backup Management
            Route::prefix('backup')->group(function () {
                Route::get('/', [BackupController::class, 'index'])->name('backup.index');
                Route::post('/create', [BackupController::class, 'create'])->name('backup.create');
                Route::post('/restore', [BackupController::class, 'restore'])->name('backup.restore');
                Route::delete('/{backup}', [BackupController::class, 'destroy'])->name('backup.destroy');
                Route::get('/download/{backup}', [BackupController::class, 'download'])->name('backup.download');
                Route::post('/schedule/update', [BackupController::class, 'updateSchedule'])->name('backup.schedule.update');
                Route::post('/cleanup', [BackupController::class, 'cleanup'])->name('backup.cleanup');
            });

            // Performance Monitoring
            Route::prefix('performance')->group(function () {
                Route::get('/', [PerformanceController::class, 'index'])->name('admin.performance');
                Route::get('/data', [PerformanceController::class, 'data'])->name('admin.performance.data');
            });

            // Security Monitoring
            Route::prefix('security')->group(function () {
                Route::get('/', [SecurityManagementController::class, 'index'])->name('admin.security');
                Route::get('/data', [SecurityManagementController::class, 'data'])->name('admin.security.data');
                Route::get('/dashboard', [MainSecurityController::class, 'dashboard'])->name(
                    'admin.security.dashboard',
                );
            });

            // Error Tracking Management
            Route::prefix('errors')->group(function () {
                Route::get('/statistics', [ErrorTrackingController::class, 'statistics'])->name(
                    'admin.errors.statistics',
                );
                Route::get('/recent', [ErrorTrackingController::class, 'recent'])->name(
                    'admin.errors.recent',
                );
            });
        });

    // Error Tracking API Routes (for frontend error reporting)
    Route::prefix('api')->group(function () {
        Route::post('/errors', [ErrorTrackingController::class, 'store'])
            ->middleware('throttle:error-tracking')
            ->name('api.errors.store');
    });
});
