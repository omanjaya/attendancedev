<?php

use App\Http\Controllers\EmployeeController;

/**
 * Employee Management Routes
 *
 * Simple, clean, RESTful routes with consistent patterns
 */
Route::middleware(['auth', 'verified'])
    ->prefix('employees')
    ->name('employees.')
    ->group(function () {

        // Standard CRUD routes
        Route::get('/', [EmployeeController::class, 'index'])->name('index');
        Route::get('/create', [EmployeeController::class, 'create'])->name('create');
        Route::post('/', [EmployeeController::class, 'store'])->name('store');
        Route::get('/{employee}', [EmployeeController::class, 'show'])->name('show');
        Route::get('/{employee}/edit', [EmployeeController::class, 'edit'])->name('edit');
        Route::put('/{employee}', [EmployeeController::class, 'update'])->name('update');
        Route::delete('/{employee}', [EmployeeController::class, 'destroy'])->name('destroy');

        // AJAX routes
        Route::get('/api/data', [EmployeeController::class, 'data'])->name('data');
        Route::post('/api/bulk', [EmployeeController::class, 'bulk'])->name('bulk');

        // Bulk action routes
        Route::post('/bulk/export', [EmployeeController::class, 'bulk'])->name('bulk-export');
        Route::post('/bulk/activate', [EmployeeController::class, 'bulk'])->name('bulk-activate');
        Route::post('/bulk/deactivate', [EmployeeController::class, 'bulk'])->name('bulk-deactivate');
        Route::post('/bulk/delete', [EmployeeController::class, 'bulk'])->name('bulk-delete');

        // Export/Import routes
        Route::get('/export', [EmployeeController::class, 'export'])->name('export');
        Route::post('/import', [EmployeeController::class, 'import'])->name('import');
        Route::get('/download/template', [EmployeeController::class, 'template'])->name('template');
    });
