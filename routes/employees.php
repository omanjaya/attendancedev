<?php

use App\Http\Controllers\EmployeeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Employee Management Routes
|--------------------------------------------------------------------------
|
| Routes for employee CRUD operations, bulk operations, and data export
|
*/

Route::middleware(['auth', 'verified'])->prefix('employees')->group(function () {
    
    // Standard CRUD operations
    Route::get('/', [EmployeeController::class, 'index'])
        ->name('employees.index')
        ->middleware('permission:view_employees');
        
    Route::get('/create', [EmployeeController::class, 'create'])
        ->name('employees.create')
        ->middleware('permission:create_employees');
        
    Route::post('/', [EmployeeController::class, 'store'])
        ->name('employees.store')
        ->middleware('permission:create_employees');
        
    Route::get('/{employee}', [EmployeeController::class, 'show'])
        ->name('employees.show')
        ->middleware('permission:view_employees');
        
    Route::get('/{employee}/edit', [EmployeeController::class, 'edit'])
        ->name('employees.edit')
        ->middleware('permission:edit_employees');
        
    Route::patch('/{employee}', [EmployeeController::class, 'update'])
        ->name('employees.update')
        ->middleware('permission:edit_employees');
        
    Route::delete('/{employee}', [EmployeeController::class, 'destroy'])
        ->name('employees.destroy')
        ->middleware('permission:delete_employees');
    
    // Bulk operations
    Route::post('/upload', [EmployeeController::class, 'uploadTemplate'])
        ->name('employees.upload')
        ->middleware('permission:create_employees');
        
    Route::post('/bulk-edit', [EmployeeController::class, 'bulkEdit'])
        ->name('employees.bulk-edit')
        ->middleware('permission:edit_employees');
        
    Route::delete('/bulk-delete', [EmployeeController::class, 'bulkDelete'])
        ->name('employees.bulk-delete')
        ->middleware('permission:delete_employees');
    
    // Template and export operations
    Route::get('/download-template', [EmployeeController::class, 'downloadTemplate'])
        ->name('employees.download-template')
        ->middleware('permission:create_employees');
    
    // API endpoint for employee data (for dropdowns, etc.)
    Route::get('/api/list', function() {
        return App\Models\Employee::select('id', 'full_name', 'employee_code')
            ->where('is_active', true)
            ->orderBy('full_name')
            ->get();
    })->name('employees.api.list')
      ->middleware('permission:view_employees');
    
    // Development/Test route (remove in production)
    Route::get('/test', function () {
        $employees = App\Models\Employee::limit(3)->get()->map(function($employee) {
            return [
                'id' => $employee->id,
                'name' => $employee->full_name,
            ];
        });
        return view('pages.management.employees.index_test', compact('employees'));
    })->name('employees.test');
});