<?php

use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\UserCredentialController;

/**
 * Employee Management Routes
 *
 * Simple, clean, RESTful routes with consistent patterns
 */
Route::middleware(['auth', 'verified'])
    ->prefix('employees')
    ->name('employees.')
    ->group(function () {

        // Standard CRUD routes (non-wildcard routes first)
        Route::get('/', [EmployeeController::class, 'index'])->name('index');
        Route::get('/create', [EmployeeController::class, 'create'])->name('create');
        Route::post('/', [EmployeeController::class, 'store'])->name('store');

        // AJAX routes
        Route::get('/api/data', [EmployeeController::class, 'data'])->name('data');
        Route::post('/api/bulk', [EmployeeController::class, 'bulk'])->name('bulk');

        // Bulk action routes
        Route::post('/bulk/export', [EmployeeController::class, 'bulk'])->name('bulk-export');
        Route::post('/bulk/activate', [EmployeeController::class, 'bulk'])->name('bulk-activate');
        Route::post('/bulk/deactivate', [EmployeeController::class, 'bulk'])->name('bulk-deactivate');
        Route::post('/bulk/delete', [EmployeeController::class, 'bulk'])->name('bulk-delete');

        // Export/Import routes
        Route::get('/export', [EmployeeController::class, 'export'])
            ->middleware('permission:export_employees_data')
            ->name('export');
        Route::post('/import', [EmployeeController::class, 'import'])
            ->middleware('permission:import_employees_data')
            ->name('import');
        Route::post('/preview-import', [EmployeeController::class, 'previewImport'])
            ->middleware('permission:import_employees_data')
            ->name('preview-import');
        Route::get('/download/template', [EmployeeController::class, 'template'])
            ->middleware('permission:import_employees_data')
            ->name('template');

        // User Credential Management Routes
        Route::prefix('credentials')
            ->name('credentials.')
            ->group(function () {
                
                // Main page for user credential management
                Route::get('/', [UserCredentialController::class, 'index'])->name('index');
                
                // AJAX data endpoints
                Route::get('/api/without-users', [UserCredentialController::class, 'getEmployeesWithoutUsers'])
                    ->name('api.without-users');
                Route::get('/api/with-users', [UserCredentialController::class, 'getEmployeesWithUsers'])
                    ->name('api.with-users');
                
                // Single user operations
                Route::post('/create-user', [UserCredentialController::class, 'createUser'])
                    ->name('create-user');
                Route::post('/reset-password', [UserCredentialController::class, 'resetPassword'])
                    ->name('reset-password');
                
                // Bulk operations
                Route::post('/bulk/create-users', [UserCredentialController::class, 'bulkCreateUsers'])
                    ->name('bulk.create-users');
                Route::post('/bulk/reset-passwords', [UserCredentialController::class, 'bulkResetPasswords'])
                    ->name('bulk.reset-passwords');
                
                // Export credentials
                Route::post('/export', [UserCredentialController::class, 'exportCredentials'])
                    ->middleware('permission:export_data')
                    ->name('export');
            });

        // Wildcard routes (must be last to avoid conflicts)
        Route::get('/{employee}', [EmployeeController::class, 'show'])->name('show');
        Route::get('/{employee}/edit', [EmployeeController::class, 'edit'])->name('edit');
        Route::put('/{employee}', [EmployeeController::class, 'update'])->name('update');
        Route::delete('/{employee}', [EmployeeController::class, 'destroy'])->name('destroy');
    });
