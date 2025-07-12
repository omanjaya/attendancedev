<?php

use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PayrollReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Payroll Management Routes
|--------------------------------------------------------------------------
|
| Routes for payroll calculations, reports, and financial management
|
*/

Route::middleware(['auth', 'verified', 'permission:view_payroll'])->prefix('payroll')->group(function () {
    
    // Main payroll management
    Route::get('/', [PayrollController::class, 'index'])->name('payroll.index');
    Route::get('/{payroll}', [PayrollController::class, 'show'])->name('payroll.show');
    
    // Payroll creation and calculation (HR/Finance only)
    Route::middleware('permission:create_payroll')->group(function () {
        Route::get('/create', [PayrollController::class, 'create'])->name('payroll.create');
        
        Route::get('/bulk-calculate', function () {
            return view('pages.payroll.bulk_calculate');
        })->name('payroll.bulk-calculate');
        
        Route::post('/bulk-calculate', function () {
            // In a real implementation, this would handle the bulk calculation
            return redirect()->route('payroll.index')
                ->with('success', 'Payroll calculated successfully for all employees');
        })->name('payroll.bulk-calculate.process');
    });
    
    // Payroll reports and summaries
    Route::middleware('permission:view_payroll_reports')->group(function () {
        Route::get('/summary', function () {
            return view('pages.payroll.summary');
        })->name('payroll.summary');
        
        Route::get('/reports', [PayrollReportController::class, 'index'])->name('payroll.reports');
        Route::get('/reports/export', [PayrollReportController::class, 'export'])->name('payroll.reports.export');
    });
    
    // API routes for payroll data
    Route::prefix('api')->group(function () {
        Route::get('/calculate/{employee}', [PayrollController::class, 'calculatePayroll'])
            ->name('payroll.api.calculate')
            ->middleware('permission:create_payroll');
            
        Route::get('/summary-stats', [PayrollController::class, 'getSummaryStats'])
            ->name('payroll.api.summary-stats')
            ->middleware('permission:view_payroll_reports');
            
        Route::get('/monthly-trends', [PayrollController::class, 'getMonthlyTrends'])
            ->name('payroll.api.monthly-trends')
            ->middleware('permission:view_payroll_reports');
    });
});