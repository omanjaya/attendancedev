<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\ReportsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Reports & Analytics Routes
|--------------------------------------------------------------------------
|
| Routes for data analytics, reporting, and business intelligence
|
*/

Route::middleware(['auth', 'verified', 'permission:view_reports'])
    ->prefix('reports')
    ->group(function () {
        // Main reports dashboard
        Route::get('/', [ReportsController::class, 'index'])->name('reports.index');

        // Report builder (advanced users)
        Route::get('/builder', [ReportsController::class, 'builder'])
            ->name('reports.builder')
            ->middleware('permission:create_reports');

        // Analytics dashboard
        Route::get('/analytics', [AnalyticsController::class, 'index'])
            ->name('reports.analytics')
            ->middleware('permission:view_analytics');

        // Specific report types
        Route::prefix('attendance')->group(function () {
            Route::get('/', [ReportsController::class, 'attendance'])->name('reports.attendance');
            Route::get('/export', [ReportsController::class, 'exportAttendance'])->name(
                'reports.attendance.export',
            );
            Route::get('/summary', [ReportsController::class, 'attendanceSummary'])->name(
                'reports.attendance.summary',
            );
        });

        Route::prefix('leave')->group(function () {
            Route::get('/', [ReportsController::class, 'leave'])->name('reports.leave');
            Route::get('/export', [ReportsController::class, 'exportLeave'])->name(
                'reports.leave.export',
            );
            Route::get('/analytics', [ReportsController::class, 'leaveAnalytics'])->name(
                'reports.leave.analytics',
            );
        });

        Route::prefix('employee')->group(function () {
            Route::get('/', [ReportsController::class, 'employee'])->name('reports.employee');
            Route::get('/export', [ReportsController::class, 'exportEmployee'])->name(
                'reports.employee.export',
            );
            Route::get('/performance', [ReportsController::class, 'employeePerformance'])->name(
                'reports.employee.performance',
            );
        });

        Route::prefix('payroll')->group(function () {
            Route::get('/', [ReportsController::class, 'payroll'])->name('reports.payroll');
            Route::get('/export', [ReportsController::class, 'exportPayroll'])->name(
                'reports.payroll.export',
            );
            Route::get('/summary', [ReportsController::class, 'payrollSummary'])->name(
                'reports.payroll.summary',
            );
        });

        // API routes for chart data
        Route::prefix('api')->group(function () {
            Route::get('/attendance-trends', [AnalyticsController::class, 'getAttendanceTrends'])->name(
                'reports.api.attendance-trends',
            );

            Route::get('/leave-patterns', [AnalyticsController::class, 'getLeavePatterns'])->name(
                'reports.api.leave-patterns',
            );

            Route::get('/employee-metrics', [AnalyticsController::class, 'getEmployeeMetrics'])->name(
                'reports.api.employee-metrics',
            );

            Route::get('/dashboard-stats', [AnalyticsController::class, 'getDashboardStats'])->name(
                'reports.api.dashboard-stats',
            );
        });
    });
