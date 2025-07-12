<?php

use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\Admin\PerformanceController;
use App\Http\Controllers\Admin\SecurityController;
use App\Http\Controllers\SecurityController as MainSecurityController;
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
    Route::prefix('system')->middleware('permission:manage_system')->group(function () {
        Route::get('/settings', function () {
            return view('pages.settings.settings');
        })->name('system.settings');
        
        Route::put('/settings', function () {
            // In a real implementation, this would handle the settings update
            return redirect()->route('system.settings')
                ->with('success', 'System settings have been updated successfully');
        })->name('system.settings.update');
        
        Route::get('/permissions', function () {
            return view('pages.settings.permissions');
        })->name('system.permissions');
        
        // Permission management routes
        Route::prefix('permissions')->group(function () {
            Route::get('/roles/data', [RoleController::class, 'getData'])
                ->name('system.permissions.roles.data');
            Route::post('/roles/create', [RoleController::class, 'store'])
                ->name('system.permissions.roles.create');
            Route::post('/roles/{role}/permissions', [RoleController::class, 'updatePermissions'])
                ->name('system.permissions.roles.permissions');
            Route::delete('/roles/{role}', [RoleController::class, 'destroy'])
                ->name('system.permissions.roles.destroy');
        });
    });
    
    // User Management (Admin only)
    Route::prefix('users')->middleware('permission:manage_users')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('users.index');
        Route::get('/{user}', [UserController::class, 'show'])->name('users.show');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::patch('/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });
    
    // Location Management (Admin only)
    Route::prefix('locations')->middleware('permission:manage_locations')->group(function () {
        Route::get('/', [LocationController::class, 'index'])->name('locations.index');
        Route::get('/create', [LocationController::class, 'create'])->name('locations.create');
        Route::post('/', [LocationController::class, 'store'])->name('locations.store');
        Route::get('/{location}', [LocationController::class, 'show'])->name('locations.show');
        Route::get('/{location}/edit', [LocationController::class, 'edit'])->name('locations.edit');
        Route::patch('/{location}', [LocationController::class, 'update'])->name('locations.update');
        Route::delete('/{location}', [LocationController::class, 'destroy'])->name('locations.destroy');
    });
    
    // Security Settings (User level)
    Route::prefix('settings')->group(function () {
        Route::get('/security', function () {
            return view('pages.settings.security');
        })->name('settings.security');
    });
    
    // Admin Panel (Super Admin only)
    Route::prefix('admin')->middleware('permission:admin_access')->group(function () {
        
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
        });
        
        // Performance Monitoring
        Route::prefix('performance')->group(function () {
            Route::get('/', [PerformanceController::class, 'index'])->name('admin.performance');
            Route::get('/data', [PerformanceController::class, 'data'])->name('admin.performance.data');
        });
        
        // Security Monitoring
        Route::prefix('security')->group(function () {
            Route::get('/', [SecurityController::class, 'index'])->name('admin.security');
            Route::get('/data', [SecurityController::class, 'data'])->name('admin.security.data');
            Route::get('/dashboard', [MainSecurityController::class, 'dashboard'])->name('admin.security.dashboard');
        });
    });
});