<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// Redirect root to modern dashboard
Route::get('/', function () {
    return redirect()->route('dashboard');
});


// Dashboard routes (multiple variants)
Route::middleware(['auth', 'verified'])->group(function () {
    // Main Dashboard (modern floating version)
    Route::get('/dashboard', function () {
        return view('pages.dashboard');
    })->name('dashboard');

    // Demo routes
    Route::prefix('demo')->name('demo.')->group(function () {
        Route::get('/notifications', function () {
            return view('demo.notifications');
        })->name('notifications');
        
        Route::post('/notifications/test', function () {
            auth()->user()->notify(new \App\Notifications\TestNotification([
                'message' => 'This is a demo notification sent at ' . now()->format('H:i:s'),
                'type' => 'demo'
            ]));
            
            return response()->json([
                'message' => 'Demo notification sent successfully',
                'timestamp' => now()
            ]);
        })->name('notifications.test');
    });
});

// Protected routes
Route::middleware('auth')->group(function () {
    // Profile routes (from Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Settings route
    Route::get('/settings', function () {
        return redirect()->route('system.settings');
    })->name('settings');
    
    

    // Attendance routes
    Route::prefix('attendance')->group(function () {
        Route::get('/', [App\Http\Controllers\AttendanceController::class, 'index'])->name('attendance.index')->middleware('permission:view_attendance');
        Route::get('/check-in', [App\Http\Controllers\AttendanceController::class, 'checkIn'])->name('attendance.check-in')->middleware('permission:manage_own_attendance');
        Route::get('/history', [App\Http\Controllers\AttendanceController::class, 'history'])->name('attendance.history')->middleware('permission:view_attendance');
        Route::get('/reports', function () {
            return view('pages.reports.index');
        })->name('attendance.reports')->middleware('permission:view_attendance_reports');
        
        // Mobile attendance routes
        Route::get('/mobile', function () {
            return view('pages.attendance.checkin');
        })->name('attendance.mobile');
    });

    // Employee routes
    Route::prefix('employees')->group(function () {
        Route::get('/', [App\Http\Controllers\EmployeeController::class, 'index'])->name('employees.index')->middleware('permission:view_employees');
        Route::get('/create', [App\Http\Controllers\EmployeeController::class, 'create'])->name('employees.create')->middleware('permission:create_employees');
        Route::post('/', [App\Http\Controllers\EmployeeController::class, 'store'])->name('employees.store')->middleware('permission:create_employees');
        Route::get('/{employee}', [App\Http\Controllers\EmployeeController::class, 'show'])->name('employees.show')->middleware('permission:view_employees');
        Route::get('/{employee}/edit', [App\Http\Controllers\EmployeeController::class, 'edit'])->name('employees.edit')->middleware('permission:edit_employees');
        Route::patch('/{employee}', [App\Http\Controllers\EmployeeController::class, 'update'])->name('employees.update')->middleware('permission:edit_employees');
        Route::delete('/{employee}', [App\Http\Controllers\EmployeeController::class, 'destroy'])->name('employees.destroy')->middleware('permission:delete_employees');
        
        // Bulk operations
        Route::post('/upload', [App\Http\Controllers\EmployeeController::class, 'uploadTemplate'])->name('employees.upload')->middleware('permission:create_employees');
        Route::post('/bulk-edit', [App\Http\Controllers\EmployeeController::class, 'bulkEdit'])->name('employees.bulk-edit')->middleware('permission:edit_employees');
        Route::delete('/bulk-delete', [App\Http\Controllers\EmployeeController::class, 'bulkDelete'])->name('employees.bulk-delete')->middleware('permission:delete_employees');
        Route::get('/download-template', [App\Http\Controllers\EmployeeController::class, 'downloadTemplate'])->name('employees.download-template')->middleware('permission:create_employees');
        
        // Test route
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

    // Leave Management routes
    Route::prefix('leave')->middleware(['auth', 'permission:view_leave'])->group(function () {
        Route::get('/', [App\Http\Controllers\LeaveController::class, 'index'])->name('leave.index');
        Route::get('/requests', [App\Http\Controllers\LeaveController::class, 'index'])->name('leave.requests');
        Route::get('/create', [App\Http\Controllers\LeaveController::class, 'create'])->name('leave.create');
        Route::post('/', [App\Http\Controllers\LeaveController::class, 'store'])->name('leave.store');
        Route::get('/{leave}', [App\Http\Controllers\LeaveController::class, 'show'])->name('leave.show');
        
        // Leave calendar view
        Route::get('/calendar', function () {
            return view('pages.leave.calendar');
        })->name('leave.calendar');
        
        // Additional leave routes
        Route::get('/calendar/manager', function () {
            return view('pages.leave.calendar', ['view' => 'manager']);
        })->name('leave.calendar.manager')->middleware('permission:approve_leave');
        
        Route::get('/analytics', function () {
            return view('pages.leave.analytics');
        })->name('leave.analytics')->middleware('permission:view_leave_analytics');
        
        Route::get('/approvals/data', [App\Http\Controllers\LeaveApprovalController::class, 'data'])->name('leave.approvals.data')->middleware('permission:approve_leave');
        
        // Leave balance routes
        Route::get('/balance', [App\Http\Controllers\LeaveBalanceController::class, 'index'])->name('leave.balance.index');
        Route::get('/balance/manage', [App\Http\Controllers\LeaveBalanceController::class, 'manage'])->name('leave.balance.manage')->middleware('permission:manage_leave_balances');
        
        // Leave approval routes
        Route::get('/approvals', [App\Http\Controllers\LeaveApprovalController::class, 'index'])->name('leave.approvals.index')->middleware('permission:approve_leave');
        Route::get('/approvals/{leave}', [App\Http\Controllers\LeaveApprovalController::class, 'show'])->name('leave.approvals.show')->middleware('permission:approve_leave');
        
        // Data routes for AJAX/DataTables (inherit permissions from parent group)
        Route::get('/requests/data', [App\Http\Controllers\LeaveController::class, 'data'])->name('leave.requests.data');
        Route::get('/calendar/data', [App\Http\Controllers\LeaveController::class, 'calendarData'])->name('leave.calendar.data');
    });

    // Schedule routes
    Route::prefix('schedules')->middleware(['auth', 'permission:view_schedules'])->group(function () {
        Route::get('/', [App\Http\Controllers\ScheduleController::class, 'index'])->name('schedules.index');
        Route::get('/calendar', [App\Http\Controllers\ScheduleController::class, 'calendar'])->name('schedules.calendar');
        Route::get('/create', function () {
            $periods = \App\Models\Period::active()->orderBy('day_of_week')->orderBy('start_time')->get();
            $teachers = \App\Models\Employee::with('user')->where('is_active', true)->get();
            return view('pages.schedules.create', compact('periods', 'teachers'));
        })->name('schedules.create')->middleware('permission:manage_schedules');
        Route::post('/', [App\Http\Controllers\ScheduleController::class, 'store'])->name('schedules.store')->middleware('permission:manage_schedules');
        Route::get('/{schedule}/edit', [App\Http\Controllers\ScheduleController::class, 'edit'])->name('schedules.edit')->middleware('permission:manage_schedules');
        Route::put('/{schedule}', [App\Http\Controllers\ScheduleController::class, 'update'])->name('schedules.update')->middleware('permission:manage_schedules');
        Route::delete('/{schedule}', [App\Http\Controllers\ScheduleController::class, 'destroy'])->name('schedules.destroy')->middleware('permission:manage_schedules');
        
        // AJAX/DataTable routes
        Route::get('/periods/data', [App\Http\Controllers\ScheduleController::class, 'getPeriodsData'])->name('schedules.periods.data');
        Route::get('/calendar/data', [App\Http\Controllers\ScheduleController::class, 'getCalendarData'])->name('schedules.calendar.data');
        Route::post('/assign-employees', [App\Http\Controllers\ScheduleController::class, 'assignEmployees'])->name('schedules.assign-employees');
        Route::get('/periods/{period}/assignments', [App\Http\Controllers\ScheduleController::class, 'getPeriodAssignments'])->name('schedules.period.assignments');
        Route::delete('/assignments/{schedule}', [App\Http\Controllers\ScheduleController::class, 'removeAssignment'])->name('schedules.remove-assignment');
        Route::post('/import', [App\Http\Controllers\ScheduleController::class, 'import'])->name('schedules.import');
        Route::get('/download-template', [App\Http\Controllers\ScheduleController::class, 'downloadTemplate'])->name('schedules.download-template');
    });

    // Academic Schedule Management routes
    Route::prefix('academic')->middleware('permission:view_schedules')->group(function () {
        Route::get('/schedules', [App\Http\Controllers\AcademicScheduleController::class, 'index'])->name('academic.schedules.index');
    });
    
    // Temporary route for calendar - using simple data for now
    Route::get('/academic/schedules/calendar', function () {
        // Simple static data to get the interface working
        $academicClasses = collect([
            (object)['id' => 1, 'grade_level' => 10, 'major' => 'IPA', 'class_number' => 1, 'name' => 'X IPA 1', 'section' => '1'],
            (object)['id' => 2, 'grade_level' => 10, 'major' => 'IPA', 'class_number' => 2, 'name' => 'X IPA 2', 'section' => '2'],
            (object)['id' => 3, 'grade_level' => 10, 'major' => 'IPS', 'class_number' => 1, 'name' => 'X IPS 1', 'section' => '1'],
            (object)['id' => 4, 'grade_level' => 11, 'major' => 'IPA', 'class_number' => 1, 'name' => 'XI IPA 1', 'section' => '1'],
            (object)['id' => 5, 'grade_level' => 11, 'major' => 'IPA', 'class_number' => 2, 'name' => 'XI IPA 2', 'section' => '2'],
            (object)['id' => 6, 'grade_level' => 11, 'major' => 'IPS', 'class_number' => 1, 'name' => 'XI IPS 1', 'section' => '1'],
            (object)['id' => 7, 'grade_level' => 12, 'major' => 'IPA', 'class_number' => 1, 'name' => 'XII IPA 1', 'section' => '1'],
            (object)['id' => 8, 'grade_level' => 12, 'major' => 'IPA', 'class_number' => 2, 'name' => 'XII IPA 2', 'section' => '2'],
            (object)['id' => 9, 'grade_level' => 12, 'major' => 'IPS', 'class_number' => 1, 'name' => 'XII IPS 1', 'section' => '1'],
        ]);
        
        $timeSlots = collect([
            (object)['id' => 1, 'name' => 'Jam 1', 'start_time' => '07:00', 'end_time' => '07:45'],
            (object)['id' => 2, 'name' => 'Jam 2', 'start_time' => '07:45', 'end_time' => '08:30'],
            (object)['id' => 3, 'name' => 'Jam 3', 'start_time' => '08:30', 'end_time' => '09:15'],
            (object)['id' => 4, 'name' => 'Jam 4', 'start_time' => '09:35', 'end_time' => '10:20'],
            (object)['id' => 5, 'name' => 'Jam 5', 'start_time' => '10:20', 'end_time' => '11:05'],
        ]);
        
        return view('pages.academic.schedules.calendar', compact('academicClasses', 'timeSlots'));
    })->middleware('auth')->name('academic.schedules.calendar');

    // API routes for schedule management
    Route::prefix('api/academic')->middleware(['auth', 'permission:view_schedules'])->group(function () {
        Route::get('/teachers-with-subjects', [App\Http\Controllers\AcademicScheduleController::class, 'getTeachersWithSubjects']);
        Route::get('/schedules/grid/{classId}', [App\Http\Controllers\AcademicScheduleController::class, 'getScheduleGrid']);
        Route::post('/schedules', [App\Http\Controllers\AcademicScheduleController::class, 'store'])->middleware('permission:create_schedules');
        Route::put('/schedules/{schedule}', [App\Http\Controllers\AcademicScheduleController::class, 'update'])->middleware('permission:edit_schedules');
        Route::delete('/schedules/{schedule}', [App\Http\Controllers\AcademicScheduleController::class, 'destroy'])->middleware('permission:delete_schedules');
        Route::post('/schedules/swap', [App\Http\Controllers\AcademicScheduleController::class, 'swapSchedules'])->middleware('permission:edit_schedules');
        Route::post('/schedules/{schedule}/lock', [App\Http\Controllers\AcademicScheduleController::class, 'lockSchedule'])->middleware('permission:lock_schedules');
        Route::delete('/schedules/{schedule}/lock', [App\Http\Controllers\AcademicScheduleController::class, 'unlockSchedule'])->middleware('permission:lock_schedules');
        Route::get('/schedules/conflicts', [App\Http\Controllers\AcademicScheduleController::class, 'getConflicts']);
        Route::post('/schedules/conflicts/{conflict}/resolve', [App\Http\Controllers\AcademicScheduleController::class, 'resolveConflict'])->middleware('permission:resolve_conflicts');
        Route::post('/schedules/import', [App\Http\Controllers\AcademicScheduleController::class, 'importSchedules'])->middleware('permission:import_schedules');
        Route::get('/schedules/export', [App\Http\Controllers\AcademicScheduleController::class, 'exportSchedules']);
    });
    
    // API route for employees (requires permission)
    Route::get('/api/employees', function() {
        return App\Models\Employee::select('id', 'full_name', 'employee_code')
            ->where('is_active', true)
            ->orderBy('full_name')
            ->get();
    })->middleware(['auth', 'permission:view_employees']);

    // Payroll routes
    Route::prefix('payroll')->middleware('permission:view_payroll')->group(function () {
        Route::get('/', [App\Http\Controllers\PayrollController::class, 'index'])->name('payroll.index');
        Route::get('/create', [App\Http\Controllers\PayrollController::class, 'create'])->name('payroll.create')->middleware('permission:create_payroll');
        Route::get('/bulk-calculate', function () {
            return view('pages.payroll.bulk_calculate');
        })->name('payroll.bulk-calculate')->middleware('permission:create_payroll');
        Route::post('/bulk-calculate', function () {
            // In a real implementation, this would handle the bulk calculation
            return redirect()->route('payroll.index')->with('success', 'Payroll calculated successfully for all employees');
        })->name('payroll.bulk-calculate')->middleware('permission:create_payroll');
        Route::get('/{payroll}', [App\Http\Controllers\PayrollController::class, 'show'])->name('payroll.show');
        Route::get('/summary', function () {
            return view('pages.payroll.summary');
        })->name('payroll.summary')->middleware('permission:view_payroll_reports');
    });

    // System Management routes
    Route::prefix('system')->middleware('permission:manage_system')->group(function () {
        Route::get('/settings', function () {
            return view('pages.settings.settings');
        })->name('system.settings');
        
        Route::put('/settings', function () {
            // In a real implementation, this would handle the settings update
            return redirect()->route('system.settings')->with('success', 'System settings have been updated successfully');
        })->name('system.settings.update');
        
        Route::get('/permissions', function () {
            return view('pages.settings.permissions');
        })->name('system.permissions');
        
        // Permission management routes
        Route::prefix('permissions')->group(function () {
            Route::get('/roles/data', [App\Http\Controllers\RoleController::class, 'getData'])->name('system.permissions.roles.data');
            Route::post('/roles/create', [App\Http\Controllers\RoleController::class, 'store'])->name('system.permissions.roles.create');
            Route::post('/roles/{role}/permissions', [App\Http\Controllers\RoleController::class, 'updatePermissions'])->name('system.permissions.roles.permissions');
            Route::delete('/roles/{role}', [App\Http\Controllers\RoleController::class, 'destroy'])->name('system.permissions.roles.destroy');
        });
    });

    // User Management routes
    Route::prefix('users')->middleware('permission:manage_users')->group(function () {
        Route::get('/', [App\Http\Controllers\UserController::class, 'index'])->name('users.index');
    });

    // Location Management routes
    Route::prefix('locations')->middleware('permission:manage_locations')->group(function () {
        Route::get('/', [App\Http\Controllers\LocationController::class, 'index'])->name('locations.index');
        Route::get('/create', [App\Http\Controllers\LocationController::class, 'create'])->name('locations.create');
        Route::get('/{location}/edit', [App\Http\Controllers\LocationController::class, 'edit'])->name('locations.edit');
    });

    // Admin routes
    Route::prefix('admin')->middleware('permission:admin_access')->group(function () {
        // Audit logs
        Route::get('/audit', [App\Http\Controllers\AuditLogController::class, 'index'])->name('audit.index');
        Route::get('/audit/{audit}', [App\Http\Controllers\AuditLogController::class, 'show'])->name('audit.show');
        Route::get('/audit/export', [App\Http\Controllers\AuditLogController::class, 'export'])->name('audit.export');
        Route::post('/audit/cleanup', [App\Http\Controllers\AuditLogController::class, 'cleanup'])->name('audit.cleanup');
        Route::get('/audit/stats', [App\Http\Controllers\AuditLogController::class, 'stats'])->name('audit.stats');
        Route::get('/audit/data', [App\Http\Controllers\AuditLogController::class, 'data'])->name('audit.data');
        
        // Backup management
        Route::get('/backup', [App\Http\Controllers\BackupController::class, 'index'])->name('backup.index');
        Route::post('/backup/create', [App\Http\Controllers\BackupController::class, 'create'])->name('backup.create');
        Route::post('/backup/restore', [App\Http\Controllers\BackupController::class, 'restore'])->name('backup.restore');
        Route::delete('/backup/{backup}', [App\Http\Controllers\BackupController::class, 'destroy'])->name('backup.destroy');
        
        // Performance monitoring
        Route::get('/performance', [App\Http\Controllers\Admin\PerformanceController::class, 'index'])->name('admin.performance');
        Route::get('/performance/data', [App\Http\Controllers\Admin\PerformanceController::class, 'data'])->name('admin.performance.data');
        
        // Security monitoring
        Route::get('/security', [App\Http\Controllers\Admin\SecurityController::class, 'index'])->name('admin.security');
        Route::get('/security/data', [App\Http\Controllers\Admin\SecurityController::class, 'data'])->name('admin.security.data');
        
        // Enhanced Security Dashboard
        Route::get('/security/dashboard', [App\Http\Controllers\SecurityController::class, 'dashboard'])->name('admin.security.dashboard');
    });
    
    // Settings routes
    Route::prefix('settings')->middleware('auth')->group(function () {
        Route::get('/security', function () {
            return view('pages.settings.security');
        })->name('settings.security');
    });
    
    // Reports routes
    Route::prefix('reports')->middleware('permission:view_reports')->group(function () {
        Route::get('/', [App\Http\Controllers\ReportsController::class, 'index'])->name('reports.index');
        Route::get('/builder', [App\Http\Controllers\ReportsController::class, 'builder'])->name('reports.builder');
        Route::get('/analytics', [App\Http\Controllers\AnalyticsController::class, 'index'])->name('reports.analytics');
        Route::get('/payroll', [App\Http\Controllers\PayrollReportController::class, 'index'])->name('reports.payroll');
    });

    // Mobile routes (removed broken mobile dashboard route)
    
    // Demo routes (development only - restrict in production)
    Route::prefix('demo')->middleware(['auth', 'permission:admin_access'])->group(function () {
        Route::get('/notifications', function () {
            return view('pages.demo.notifications');
        })->name('demo.notifications');
        
        Route::post('/notifications/test', function () {
            $type = request('type', 'success');
            $message = request('message', 'Test notification message');
            
            return redirect()->route('demo.notifications')->with($type, $message);
        })->name('demo.notifications.test');
        
        Route::get('/components', function () {
            return view('pages.demo.components');
        })->name('demo.components');
        
        Route::get('/mobile', function () {
            return view('pages.demo.mobile');
        })->name('demo.mobile');
        
        Route::get('/performance', function () {
            return view('pages.demo.performance');
        })->name('demo.performance');
    });
});

// Include Breeze authentication routes
require __DIR__.'/auth.php';

// Two-Factor Authentication routes
Route::middleware('auth')->prefix('2fa')->name('2fa.')->group(function () {
    Route::get('/verify', [App\Http\Controllers\Auth\TwoFactorController::class, 'verify'])->name('verify');
    Route::post('/verify', [App\Http\Controllers\Auth\TwoFactorController::class, 'verifyCode'])
        ->middleware('2fa.rate_limit:verification');
    Route::get('/setup', [App\Http\Controllers\Auth\TwoFactorController::class, 'setup'])->name('setup');
    Route::post('/enable', [App\Http\Controllers\Auth\TwoFactorController::class, 'enable'])
        ->middleware('2fa.rate_limit:setup_attempt');
    Route::get('/manage', [App\Http\Controllers\Auth\TwoFactorController::class, 'manage'])->name('manage');
    Route::post('/disable', [App\Http\Controllers\Auth\TwoFactorController::class, 'disable']);
    Route::post('/regenerate-codes', [App\Http\Controllers\Auth\TwoFactorController::class, 'regenerateRecoveryCodes']);
    Route::post('/sms', [App\Http\Controllers\Auth\TwoFactorController::class, 'sendSMS'])
        ->middleware('2fa.rate_limit:sms_request');
    Route::get('/qr', [App\Http\Controllers\Auth\TwoFactorController::class, 'getQRCode']);
    Route::get('/status', [App\Http\Controllers\Auth\TwoFactorController::class, 'status']);
    
    // Recovery routes
    Route::get('/recovery', [App\Http\Controllers\Auth\TwoFactorController::class, 'showRecovery'])->name('recovery');
    Route::post('/recovery', [App\Http\Controllers\Auth\TwoFactorController::class, 'verifyRecovery'])
        ->middleware('2fa.rate_limit:recovery_code');
    Route::get('/account-recovery', [App\Http\Controllers\Auth\TwoFactorController::class, 'showAccountRecovery'])->name('account-recovery');
    Route::post('/emergency-recovery', [App\Http\Controllers\Auth\TwoFactorController::class, 'requestEmergencyRecovery'])
        ->middleware('2fa.rate_limit:emergency_recovery')->name('emergency-recovery');
});