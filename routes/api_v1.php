<?php

use App\Http\Controllers\Api\EmployeeApiController;
use App\Http\Controllers\Api\AttendanceApiController;
use App\Http\Controllers\Api\AuthApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 Routes
|--------------------------------------------------------------------------
|
| API routes for version 1 of the attendance management system.
| All routes require authentication via Laravel Sanctum.
|
*/

// Authentication routes (no auth required)
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthApiController::class, 'login']);
    Route::post('/register', [AuthApiController::class, 'register']);
    Route::post('/forgot-password', [AuthApiController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthApiController::class, 'resetPassword']);
});

// Protected API routes
Route::middleware(['auth:sanctum'])->group(function () {
    
    // Authentication management
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthApiController::class, 'logout']);
        Route::get('/user', [AuthApiController::class, 'user']);
        Route::post('/refresh', [AuthApiController::class, 'refresh']);
        Route::post('/verify-email', [AuthApiController::class, 'verifyEmail']);
    });
    
    // Employee management
    Route::prefix('employees')->group(function () {
        Route::get('/', [EmployeeApiController::class, 'index']);
        Route::post('/', [EmployeeApiController::class, 'store']);
        Route::get('/search', [EmployeeApiController::class, 'search']);
        Route::get('/statistics', [EmployeeApiController::class, 'statistics']);
        Route::get('/{id}', [EmployeeApiController::class, 'show']);
        Route::put('/{id}', [EmployeeApiController::class, 'update']);
        Route::delete('/{id}', [EmployeeApiController::class, 'destroy']);
    });
    
    // Attendance management
    Route::prefix('attendance')->group(function () {
        Route::get('/', [AttendanceApiController::class, 'index']);
        Route::post('/check-in', [AttendanceApiController::class, 'checkIn']);
        Route::post('/check-out', [AttendanceApiController::class, 'checkOut']);
        Route::get('/today', [AttendanceApiController::class, 'todayAttendance']);
        Route::get('/statistics', [AttendanceApiController::class, 'statistics']);
        Route::get('/trends', [AttendanceApiController::class, 'trends']);
        Route::get('/{id}', [AttendanceApiController::class, 'show']);
        Route::put('/{id}', [AttendanceApiController::class, 'update']);
        Route::delete('/{id}', [AttendanceApiController::class, 'destroy']);
    });
    
    // Leave management
    Route::prefix('leave')->group(function () {
        Route::get('/', [LeaveApiController::class, 'index']);
        Route::post('/', [LeaveApiController::class, 'store']);
        Route::get('/pending', [LeaveApiController::class, 'pending']);
        Route::get('/calendar', [LeaveApiController::class, 'calendar']);
        Route::get('/balance', [LeaveApiController::class, 'balance']);
        Route::get('/{id}', [LeaveApiController::class, 'show']);
        Route::put('/{id}', [LeaveApiController::class, 'update']);
        Route::delete('/{id}', [LeaveApiController::class, 'destroy']);
        Route::post('/{id}/approve', [LeaveApiController::class, 'approve']);
        Route::post('/{id}/reject', [LeaveApiController::class, 'reject']);
    });
    
    // Schedule management
    Route::prefix('schedules')->group(function () {
        Route::get('/', [ScheduleApiController::class, 'index']);
        Route::post('/', [ScheduleApiController::class, 'store']);
        Route::get('/calendar', [ScheduleApiController::class, 'calendar']);
        Route::get('/{id}', [ScheduleApiController::class, 'show']);
        Route::put('/{id}', [ScheduleApiController::class, 'update']);
        Route::delete('/{id}', [ScheduleApiController::class, 'destroy']);
    });
    
    // Payroll management
    Route::prefix('payroll')->group(function () {
        Route::get('/', [PayrollApiController::class, 'index']);
        Route::post('/calculate', [PayrollApiController::class, 'calculate']);
        Route::get('/summary', [PayrollApiController::class, 'summary']);
        Route::get('/{id}', [PayrollApiController::class, 'show']);
        Route::put('/{id}', [PayrollApiController::class, 'update']);
    });
    
    // Reports and analytics
    Route::prefix('reports')->group(function () {
        Route::get('/attendance', [ReportsApiController::class, 'attendance']);
        Route::get('/leave', [ReportsApiController::class, 'leave']);
        Route::get('/payroll', [ReportsApiController::class, 'payroll']);
        Route::get('/dashboard', [ReportsApiController::class, 'dashboard']);
        Route::post('/export', [ReportsApiController::class, 'export']);
    });
});