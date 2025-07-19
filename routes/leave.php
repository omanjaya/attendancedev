<?php

use App\Http\Controllers\LeaveApprovalController;
use App\Http\Controllers\LeaveBalanceController;
use App\Http\Controllers\LeaveController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Leave Management Routes
|--------------------------------------------------------------------------
|
| Routes for leave requests, approvals, balance management, and analytics
|
*/

Route::middleware(['auth', 'verified'])
    ->prefix('leave')
    ->group(function () {
        // Employee leave management - available for users with own or all leave permissions
        Route::get('/', [LeaveController::class, 'index'])
            ->name('leave.index')
            ->middleware('permission:view_leave_all');

        Route::get('/requests', [LeaveController::class, 'index'])
            ->name('leave.requests')
            ->middleware('permission:view_leave_all');

        Route::get('/create', [LeaveController::class, 'create'])
            ->name('leave.create')
            ->middleware('permission:create_leave_requests');

        Route::post('/', [LeaveController::class, 'store'])
            ->name('leave.store')
            ->middleware('permission:create_leave_requests');

        Route::get('/{leave}', [LeaveController::class, 'show'])
            ->name('leave.show')
            ->middleware('permission:view_leave_all');

        // Import/Export routes
        Route::get('/template', [LeaveController::class, 'downloadTemplate'])
            ->name('leave.template')
            ->middleware('permission:manage_leave_all');

        Route::post('/import', [LeaveController::class, 'importLeave'])
            ->name('leave.import')
            ->middleware('permission:manage_leave_all');

        // Leave calendar views
        Route::get('/calendar', function () {
            return view('pages.leave.calendar');
        })->name('leave.calendar');

        Route::get('/calendar/manager', function () {
            return view('pages.leave.calendar', ['view' => 'manager']);
        })
            ->name('leave.calendar.manager')
            ->middleware('permission:approve_leave');

        // Leave analytics and reporting
        Route::get('/analytics', function () {
            return view('pages.leave.analytics');
        })
            ->name('leave.analytics')
            ->middleware('permission:view_leave_analytics');

        // Leave balance management
        Route::prefix('balance')->group(function () {
            Route::get('/', [LeaveBalanceController::class, 'index'])->name('leave.balance.index');

            Route::get('/manage', [LeaveBalanceController::class, 'manage'])
                ->name('leave.balance.manage')
                ->middleware('permission:manage_leave_balances');
        });

        // Leave approval management (manager/HR only)
        Route::prefix('approvals')
            ->middleware('permission:approve_leave')
            ->group(function () {
                Route::get('/', [LeaveApprovalController::class, 'index'])->name('leave.approvals.index');

                Route::get('/{leave}', [LeaveApprovalController::class, 'show'])->name(
                    'leave.approvals.show',
                );

                Route::get('/data', [LeaveApprovalController::class, 'data'])->name('leave.approvals.data');
            });

        // AJAX/DataTable data routes (inherit permissions from parent group)
        Route::prefix('data')->group(function () {
            Route::get('/requests', [LeaveController::class, 'data'])->name('leave.requests.data');

            Route::get('/calendar', [LeaveController::class, 'calendarData'])->name(
                'leave.calendar.data',
            );
        });

        // Calendar specific routes
        Route::get('/calendar/stats', [LeaveController::class, 'calendarStats'])->name('leave.calendar.stats');
        Route::get('/calendar/details/{id}', [LeaveController::class, 'calendarDetails'])->name('leave.calendar.details');
    });
