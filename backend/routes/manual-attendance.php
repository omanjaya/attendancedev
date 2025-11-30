<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ManualAttendanceController;

/*
|--------------------------------------------------------------------------
| Manual Attendance Routes
|--------------------------------------------------------------------------
|
| Routes for manual attendance entry by administrators
|
*/

Route::middleware(['auth', 'verified'])->group(function () {
    // Manual Attendance Management
    Route::prefix('manual-attendance')->name('manual-attendance.')->group(function () {
        // Web routes
        Route::get('/', [ManualAttendanceController::class, 'index'])->name('index');
        
        // API routes
        Route::prefix('api')->group(function () {
            Route::post('/', [ManualAttendanceController::class, 'store'])->name('store');
            Route::put('/{attendance}', [ManualAttendanceController::class, 'update'])->name('update');
            Route::delete('/{attendance}', [ManualAttendanceController::class, 'destroy'])->name('destroy');
            Route::get('/employees', [ManualAttendanceController::class, 'getEmployees'])->name('employees');
            Route::get('/entries', [ManualAttendanceController::class, 'getManualEntries'])->name('entries');
            Route::post('/bulk', [ManualAttendanceController::class, 'bulkStore'])->name('bulk-store');
        });
    });
});

// API v1 routes for external access
Route::prefix('api/v1')->middleware('auth:sanctum')->group(function () {
    Route::prefix('manual-attendance')->group(function () {
        Route::post('/', [ManualAttendanceController::class, 'store'])
            ->middleware('permission:manage_attendance_all');
        Route::put('/{attendance}', [ManualAttendanceController::class, 'update'])
            ->middleware('permission:manage_attendance_all');
        Route::delete('/{attendance}', [ManualAttendanceController::class, 'destroy'])
            ->middleware('permission:manage_attendance_all');
        Route::get('/employees', [ManualAttendanceController::class, 'getEmployees'])
            ->middleware('permission:view_employees');
        Route::get('/entries', [ManualAttendanceController::class, 'getManualEntries'])
            ->middleware('permission:view_attendance_all');
        Route::post('/bulk', [ManualAttendanceController::class, 'bulkStore'])
            ->middleware('permission:manage_attendance_all');
    });
});