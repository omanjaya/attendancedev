<?php

use App\Http\Controllers\AttendanceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Attendance Routes
|--------------------------------------------------------------------------
|
| Routes for attendance management including check-in, history, and reports
|
*/

Route::middleware(['auth', 'verified'])
    ->prefix('attendance')
    ->group(function () {
        // Main attendance management
        Route::get('/', [AttendanceController::class, 'index'])
            ->name('attendance.index')
            ->middleware('permission:view_attendance_own');

        Route::get('/check-in', [AttendanceController::class, 'checkIn'])
            ->name('attendance.check-in')
            ->middleware('permission:manage_attendance_own');

        Route::get('/history', [AttendanceController::class, 'history'])
            ->name('attendance.history')
            ->middleware('permission:view_attendance_own');

        // Attendance reports
        Route::get('/reports', function () {
            return view('pages.reports.index');
        })
            ->name('attendance.reports')
            ->middleware('permission:view_attendance_reports');

        // Import/Export routes
        Route::get('/template', [AttendanceController::class, 'downloadTemplate'])
            ->name('attendance.template')
            ->middleware('permission:manage_attendance_all');

        Route::post('/import', [AttendanceController::class, 'importAttendance'])
            ->name('attendance.import')
            ->middleware('permission:manage_attendance_all');

        // API routes for AJAX/DataTables
        Route::prefix('api')->group(function () {
            Route::get('/today-stats', [AttendanceController::class, 'getTodayStats'])
                ->name('attendance.api.today-stats')
                ->middleware('permission:view_attendance_reports');

            Route::get('/chart-data', [AttendanceController::class, 'getChartData'])
                ->name('attendance.api.chart-data')
                ->middleware('permission:view_attendance_reports');

            Route::post('/check-in', [AttendanceController::class, 'processCheckIn'])
                ->name('attendance.api.check-in')
                ->middleware('permission:manage_attendance_own');

            Route::post('/check-out', [AttendanceController::class, 'processCheckOut'])
                ->name('attendance.api.check-out')
                ->middleware('permission:manage_attendance_own');

            // Add missing status route for enhanced-checkin
            Route::get('/v1/attendance/status', [AttendanceController::class, 'getCurrentStatus'])
                ->name('attendance.api.status')
                ->middleware('permission:view_attendance_own');

            Route::get('/today-schedule', [AttendanceController::class, 'getTodayScheduleAndStatus'])
                ->name('attendance.api.today-schedule')
                ->middleware('permission:view_attendance_own');
        });
    });
