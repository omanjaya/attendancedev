<?php

use App\Http\Controllers\Api\HealthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Health check endpoints (no authentication required)
Route::get('/health', [HealthController::class, 'check'])->name('api.health');
Route::get('/ping', [HealthController::class, 'ping'])->name('api.ping');

// Time service endpoints (authenticated)
use App\Http\Controllers\Api\TimeController;
Route::middleware('auth')->group(function () {
    Route::get('/time/current', [TimeController::class, 'current'])->name('api.time.current');
    Route::get('/time/verify', [TimeController::class, 'verify'])->name('api.time.verify');
    Route::get('/time/system-info', [TimeController::class, 'systemInfo'])->name('api.time.system-info');
});

// API v1 routes
Route::prefix('v1')->group(function () {
    // Auth routes
    Route::post('/auth/login', [App\Http\Controllers\Api\AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [App\Http\Controllers\Api\AuthController::class, 'logout']);
        Route::get('/auth/me', [App\Http\Controllers\Api\AuthController::class, 'me']);
        // User info
        Route::get('/user', function (Request $request) {
            return $request->user()->load(['employee', 'roles', 'permissions']);
        });

        // Employee management endpoints
        Route::prefix('employees')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\EmployeeApiController::class, 'index']);
            Route::post('/', [App\Http\Controllers\Api\EmployeeApiController::class, 'store']);
            Route::get('/search', [App\Http\Controllers\Api\EmployeeApiController::class, 'search']);
            Route::get('/statistics', [App\Http\Controllers\Api\EmployeeApiController::class, 'statistics']);
            Route::get('/{id}', [App\Http\Controllers\Api\EmployeeApiController::class, 'show']);
            Route::put('/{id}', [App\Http\Controllers\Api\EmployeeApiController::class, 'update']);
            Route::delete('/{id}', [App\Http\Controllers\Api\EmployeeApiController::class, 'destroy']);
        });

        // Attendance management endpoints (React frontend)
        Route::prefix('attendance')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\AttendanceApiController::class, 'index']);
            Route::get('/today', [App\Http\Controllers\Api\AttendanceApiController::class, 'today']);
            Route::get('/statistics', [App\Http\Controllers\Api\AttendanceApiController::class, 'statistics']);
            Route::get('/trends', [App\Http\Controllers\Api\AttendanceApiController::class, 'trends']);
            Route::get('/{id}', [App\Http\Controllers\Api\AttendanceApiController::class, 'show']);
            Route::put('/{id}', [App\Http\Controllers\Api\AttendanceApiController::class, 'update']);
            Route::delete('/{id}', [App\Http\Controllers\Api\AttendanceApiController::class, 'destroy']);
        });

        // Payroll management endpoints
        Route::prefix('payroll')->group(function () {
            Route::get('/periods', [App\Http\Controllers\Api\PayrollApiController::class, 'periods']);
            Route::post('/periods', [App\Http\Controllers\Api\PayrollApiController::class, 'storePeriod']);
            Route::get('/periods/{id}', [App\Http\Controllers\Api\PayrollApiController::class, 'showPeriod']);
            Route::put('/periods/{id}', [App\Http\Controllers\Api\PayrollApiController::class, 'updatePeriod']);
            Route::delete('/periods/{id}', [App\Http\Controllers\Api\PayrollApiController::class, 'destroyPeriod']);
            Route::get('/statistics', [App\Http\Controllers\Api\PayrollApiController::class, 'statistics']);
            Route::get('/config', [App\Http\Controllers\Api\PayrollApiController::class, 'config']);
        });

        // Leave management endpoints
        Route::prefix('leave-requests')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\LeaveApiController::class, 'index']);
            Route::post('/', [App\Http\Controllers\Api\LeaveApiController::class, 'store']);
            Route::get('/pending', [App\Http\Controllers\Api\LeaveApiController::class, 'pending']);
            Route::get('/{id}', [App\Http\Controllers\Api\LeaveApiController::class, 'show']);
            Route::post('/{id}/approve', [App\Http\Controllers\Api\LeaveApiController::class, 'approve']);
            Route::post('/{id}/reject', [App\Http\Controllers\Api\LeaveApiController::class, 'reject']);
            Route::post('/{id}/cancel', [App\Http\Controllers\Api\LeaveApiController::class, 'cancel']);
        });

        Route::prefix('leave')->group(function () {
            Route::get('/balance', [App\Http\Controllers\Api\LeaveApiController::class, 'balance']);
            Route::get('/balance/{employeeId}', [App\Http\Controllers\Api\LeaveApiController::class, 'balanceByEmployee']);
            Route::get('/statistics', [App\Http\Controllers\Api\LeaveApiController::class, 'statistics']);
        });

        // Schedule management endpoints
        Route::prefix('schedules')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\ScheduleApiController::class, 'index']);
            Route::post('/', [App\Http\Controllers\Api\ScheduleApiController::class, 'store']);
            Route::get('/statistics', [App\Http\Controllers\Api\ScheduleApiController::class, 'statistics']);
            Route::get('/conflicts', [App\Http\Controllers\Api\ScheduleApiController::class, 'conflicts']);
            Route::get('/time-slots', [App\Http\Controllers\Api\ScheduleApiController::class, 'timeSlots']);
            Route::get('/subjects', [App\Http\Controllers\Api\ScheduleApiController::class, 'subjects']);
            Route::get('/classes', [App\Http\Controllers\Api\ScheduleApiController::class, 'classes']);
            Route::get('/available-teachers', [App\Http\Controllers\Api\ScheduleApiController::class, 'availableTeachers']);
            Route::get('/class/{classId}', [App\Http\Controllers\Api\ScheduleApiController::class, 'byClass']);
            Route::get('/monthly', [App\Http\Controllers\Api\ScheduleApiController::class, 'monthlySchedules']);
            Route::post('/monthly', [App\Http\Controllers\Api\ScheduleApiController::class, 'storeMonthly']);
            Route::get('/monthly/{id}', [App\Http\Controllers\Api\ScheduleApiController::class, 'showMonthly']);
            Route::post('/monthly/{id}/publish', [App\Http\Controllers\Api\ScheduleApiController::class, 'publishMonthly']);
            Route::delete('/monthly/{id}', [App\Http\Controllers\Api\ScheduleApiController::class, 'destroyMonthly']);
            Route::get('/{id}', [App\Http\Controllers\Api\ScheduleApiController::class, 'show']);
            Route::put('/{id}', [App\Http\Controllers\Api\ScheduleApiController::class, 'update']);
            Route::delete('/{id}', [App\Http\Controllers\Api\ScheduleApiController::class, 'destroy']);
            Route::post('/{id}/lock', [App\Http\Controllers\Api\ScheduleApiController::class, 'lock']);
            Route::post('/{id}/unlock', [App\Http\Controllers\Api\ScheduleApiController::class, 'unlock']);
        });

        // Reports endpoints
        Route::prefix('reports')->group(function () {
            Route::get('/data', [App\Http\Controllers\Api\ReportsApiController::class, 'data']);
            Route::get('/summary', [App\Http\Controllers\Api\ReportsApiController::class, 'summary']);
            Route::get('/attendance/monthly', [App\Http\Controllers\Api\ReportsApiController::class, 'monthlyAttendance']);
            Route::get('/attendance/weekly', [App\Http\Controllers\Api\ReportsApiController::class, 'weeklyTrend']);
            Route::get('/departments', [App\Http\Controllers\Api\ReportsApiController::class, 'departmentStats']);
            Route::get('/leave', [App\Http\Controllers\Api\ReportsApiController::class, 'leaveStats']);
            Route::post('/generate', [App\Http\Controllers\Api\ReportsApiController::class, 'generate']);
            Route::get('/templates', [App\Http\Controllers\Api\ReportsApiController::class, 'templates']);
            Route::get('/generated', [App\Http\Controllers\Api\ReportsApiController::class, 'generatedReports']);
        });

        // Admin endpoints
        Route::prefix('admin')->group(function () {
            // Users
            Route::get('/users', [App\Http\Controllers\Api\AdminApiController::class, 'users']);
            Route::post('/users', [App\Http\Controllers\Api\AdminApiController::class, 'storeUser']);
            Route::get('/users/statistics', [App\Http\Controllers\Api\AdminApiController::class, 'userStatistics']);
            Route::get('/roles', [App\Http\Controllers\Api\AdminApiController::class, 'roles']);
            Route::put('/users/{id}', [App\Http\Controllers\Api\AdminApiController::class, 'updateUser']);
            Route::delete('/users/{id}', [App\Http\Controllers\Api\AdminApiController::class, 'destroyUser']);
            Route::post('/users/{id}/toggle-status', [App\Http\Controllers\Api\AdminApiController::class, 'toggleUserStatus']);

            // Locations
            Route::get('/locations', [App\Http\Controllers\Api\AdminApiController::class, 'locations']);
            Route::post('/locations', [App\Http\Controllers\Api\AdminApiController::class, 'storeLocation']);
            Route::get('/locations/statistics', [App\Http\Controllers\Api\AdminApiController::class, 'locationStatistics']);
            Route::put('/locations/{id}', [App\Http\Controllers\Api\AdminApiController::class, 'updateLocation']);
            Route::delete('/locations/{id}', [App\Http\Controllers\Api\AdminApiController::class, 'destroyLocation']);

            // Holidays
            Route::get('/holidays', [App\Http\Controllers\Api\AdminApiController::class, 'holidays']);
            Route::post('/holidays', [App\Http\Controllers\Api\AdminApiController::class, 'storeHoliday']);
            Route::get('/holidays/statistics', [App\Http\Controllers\Api\AdminApiController::class, 'holidayStatistics']);
            Route::put('/holidays/{id}', [App\Http\Controllers\Api\AdminApiController::class, 'updateHoliday']);
            Route::delete('/holidays/{id}', [App\Http\Controllers\Api\AdminApiController::class, 'destroyHoliday']);
        });

        // Two-Factor Authentication endpoints
        Route::prefix('two-factor')->group(function () {
            Route::post('/setup/initialize', [
                App\Http\Controllers\Auth\TwoFactorController::class,
                'initializeSetup',
            ])->middleware('2fa.rate_limit:setup_attempt');
            Route::post('/setup/verify', [
                App\Http\Controllers\Auth\TwoFactorController::class,
                'enable',
            ])->middleware('2fa.rate_limit:setup_attempt');
            Route::post('/verify', [
                App\Http\Controllers\Auth\TwoFactorController::class,
                'verifyCode',
            ])->middleware('2fa.rate_limit:verification');
            Route::delete('/disable', [App\Http\Controllers\Auth\TwoFactorController::class, 'disable']);
            Route::post('/recovery-codes/regenerate', [
                App\Http\Controllers\Auth\TwoFactorController::class,
                'regenerateRecoveryCodes',
            ]);
            Route::get('/status', [App\Http\Controllers\Auth\TwoFactorController::class, 'status']);
            Route::post('/sms/send', [
                App\Http\Controllers\Auth\TwoFactorController::class,
                'sendSMS',
            ])->middleware('2fa.rate_limit:sms_request');
            Route::get('/qr-code', [App\Http\Controllers\Auth\TwoFactorController::class, 'getQRCode']);
        });

        // Attendance endpoints
        Route::prefix('attendance')->group(function () {
            Route::get('/status', [
                App\Http\Controllers\AttendanceController::class,
                'getStatus',
            ])->middleware('permission:view_attendance_own');
            Route::post('/check-in', [
                App\Http\Controllers\AttendanceController::class,
                'processCheckIn',
            ])->middleware('permission:manage_attendance_own');
            Route::post('/check-out', [
                App\Http\Controllers\AttendanceController::class,
                'processCheckOut',
            ])->middleware('permission:manage_attendance_own');
            Route::get('/data', [
                App\Http\Controllers\AttendanceController::class,
                'getAttendanceData',
            ])->middleware('permission:view_attendance_own');
            Route::get('/statistics', [
                App\Http\Controllers\AttendanceController::class,
                'getStatistics',
            ])->middleware('permission:view_attendance_reports');
            Route::get('/{attendance}/details', [
                App\Http\Controllers\AttendanceController::class,
                'getAttendanceDetails',
            ])->middleware('permission:view_attendance_own');
            Route::post('/{attendance}/manual-checkout', [
                App\Http\Controllers\AttendanceController::class,
                'manualCheckOut',
            ])->middleware('permission:manage_attendance_all');
            Route::get('/export', [
                App\Http\Controllers\AttendanceController::class,
                'exportAttendance',
            ])->middleware('permission:view_attendance_reports');
        });

        // Enhanced Attendance with Face Recognition endpoints
        Route::prefix('attendance-face')->group(function () {
            Route::post('/check-in', [
                App\Http\Controllers\Api\AttendanceController::class,
                'checkIn',
            ])->middleware('permission:manage_attendance_own');
            Route::post('/check-out', [
                App\Http\Controllers\Api\AttendanceController::class,
                'checkOut',
            ])->middleware('permission:manage_attendance_own');
            Route::post('/status', [
                App\Http\Controllers\Api\AttendanceController::class,
                'getStatus',
            ])->middleware('permission:view_attendance_own');
            Route::post('/statistics', [
                App\Http\Controllers\Api\AttendanceController::class,
                'getStatistics',
            ])->middleware('permission:view_attendance_reports');
            Route::post('/validate', [
                App\Http\Controllers\Api\AttendanceController::class,
                'validateAttendance',
            ])->middleware('permission:manage_attendance_own');
        });

        // Face detection endpoints
        Route::prefix('face-detection')->group(function () {
            Route::post('/register', [
                App\Http\Controllers\FaceDetectionController::class,
                'registerFace',
            ])->middleware('permission:manage_employees');
            Route::post('/verify', [
                App\Http\Controllers\FaceDetectionController::class,
                'verifyFace',
            ])->middleware('permission:manage_attendance_own');
            Route::get('/faces', [
                App\Http\Controllers\FaceDetectionController::class,
                'getRegisteredFaces',
            ])->middleware('permission:view_employees');
            Route::put('/faces/{employee}', [
                App\Http\Controllers\FaceDetectionController::class,
                'updateFace',
            ])->middleware('permission:manage_employees');
            Route::delete('/faces/{employee}', [
                App\Http\Controllers\FaceDetectionController::class,
                'deleteFace',
            ])->middleware('permission:manage_employees');
            Route::get('/statistics', [
                App\Http\Controllers\FaceDetectionController::class,
                'getStatistics',
            ])->middleware('permission:view_employees');

            // Enhanced face recognition endpoints
            Route::post('/batch-verify', [
                App\Http\Controllers\FaceDetectionController::class,
                'batchVerify',
            ])->middleware('permission:manage_attendance_own');
            Route::get('/performance-metrics', [
                App\Http\Controllers\FaceDetectionController::class,
                'getPerformanceMetrics',
            ])->middleware('permission:view_attendance_reports');
            Route::get('/employees-without-face', [
                App\Http\Controllers\FaceDetectionController::class,
                'getEmployeesWithoutFace',
            ])->middleware('permission:view_employees');
            Route::get('/low-quality-faces', [
                App\Http\Controllers\FaceDetectionController::class,
                'getLowQualityFaces',
            ])->middleware('permission:view_employees');
            Route::get('/search-by-status', [
                App\Http\Controllers\FaceDetectionController::class,
                'searchByFaceStatus',
            ])->middleware('permission:view_employees');
        });

        // Enhanced Face Recognition Service endpoints
        Route::prefix('face-recognition')->group(function () {
            Route::post('/register', [
                App\Http\Controllers\Api\FaceRecognitionController::class,
                'registerFace',
            ])->middleware('permission:manage_employees');
            Route::post('/verify', [
                App\Http\Controllers\Api\FaceRecognitionController::class,
                'verifyFace',
            ])->middleware('permission:manage_attendance_own');
            Route::post('/update', [
                App\Http\Controllers\Api\FaceRecognitionController::class,
                'updateFaceData',
            ])->middleware('permission:manage_employees');
            Route::post('/delete', [
                App\Http\Controllers\Api\FaceRecognitionController::class,
                'deleteFaceData',
            ])->middleware('permission:manage_employees');
            Route::post('/get-data', [
                App\Http\Controllers\Api\FaceRecognitionController::class,
                'getFaceData',
            ])->middleware('permission:view_employees');
            Route::post('/batch-verify', [
                App\Http\Controllers\Api\FaceRecognitionController::class,
                'batchVerify',
            ])->middleware('permission:manage_attendance_own');
            Route::post('/check-liveness', [
                App\Http\Controllers\Api\FaceRecognitionController::class,
                'checkLiveness',
            ])->middleware('permission:manage_attendance_own');
            Route::get('/statistics', [
                App\Http\Controllers\Api\FaceRecognitionController::class,
                'getStatistics',
            ])->middleware('permission:view_employees');
        });

        // User management endpoints
        Route::prefix('users')
            ->middleware('permission:manage_system_settings')
            ->group(function () {
                Route::get('/data', [App\Http\Controllers\UserController::class, 'getData']);
                Route::get('/statistics', [App\Http\Controllers\UserController::class, 'getStatistics']);
                Route::get('/select', [App\Http\Controllers\UserController::class, 'getUsersForSelect']);
                Route::post('/', [App\Http\Controllers\UserController::class, 'store']);
                Route::put('/{user}', [App\Http\Controllers\UserController::class, 'update']);
                Route::delete('/{user}', [App\Http\Controllers\UserController::class, 'destroy']);
                Route::post('/{user}/toggle-status', [
                    App\Http\Controllers\UserController::class,
                    'toggleStatus',
                ]);
                Route::post('/{user}/reset-password', [
                    App\Http\Controllers\UserController::class,
                    'resetPassword',
                ]);
            });

        // Location management endpoints
        Route::prefix('locations')->group(function () {
            Route::get('/select', [
                App\Http\Controllers\LocationController::class,
                'getLocationsForSelect',
            ])->middleware('permission:view_employees');
            Route::post('/verify', [
                App\Http\Controllers\LocationController::class,
                'verifyLocation',
            ])->middleware('permission:manage_attendance_own');
        });

        // Leave management endpoints
        Route::prefix('leave')->group(function () {
            Route::get('/balance', [
                App\Http\Controllers\LeaveBalanceController::class,
                'getBalance',
            ])->middleware('permission:view_leave_own');
            Route::post('/calculate-days', [
                App\Http\Controllers\LeaveController::class,
                'calculateDays',
            ])->middleware('permission:view_leave_own');
        });

        // Reports endpoints
        Route::prefix('reports')->group(function () {
            Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'getData'])
                ->middleware('permission:view_attendance_reports');
        });

        // Dashboard endpoints for Vue component (consolidated)
        Route::prefix('dashboard')->group(function () {
            Route::get('/attendance', [
                App\Http\Controllers\DashboardController::class,
                'getAttendanceDashboard',
            ])
                ->middleware('permission:view_attendance_own')
                ->name('api.attendance.dashboard');
            Route::get('/stats', [App\Http\Controllers\DashboardController::class, 'getAttendanceStats'])
                ->middleware('permission:view_attendance_own')
                ->name('api.attendance.stats');
        });
    });
});

// Session-based API routes for Vue dashboard (outside Sanctum middleware)
Route::prefix('vue')
    ->middleware(['auth', 'verified'])
    ->group(function () {
        Route::get('/dashboard/attendance', [
            App\Http\Controllers\DashboardController::class,
            'getAttendanceDashboard',
        ])->name('api.vue.attendance.dashboard');
        Route::get('/dashboard/stats', [
            App\Http\Controllers\DashboardController::class,
            'getAttendanceStats',
        ])->name('api.vue.attendance.stats');
        Route::get('/face-detection/statistics', [
            App\Http\Controllers\FaceDetectionController::class,
            'getStatistics',
        ]);
        Route::get('/attendance/export', [
            App\Http\Controllers\AttendanceController::class,
            'exportAttendance',
        ])->middleware('permission:view_attendance_reports');
    });

// Navigation API routes for enhanced sidebar
Route::prefix('navigation')
    ->middleware(['auth', 'verified'])
    ->group(function () {
        Route::get('/', [App\Http\Controllers\Api\NavigationController::class, 'index'])->name(
            'api.navigation.index',
        );
        Route::post('/search', [App\Http\Controllers\Api\NavigationController::class, 'search'])->name(
            'api.navigation.search',
        );
        Route::post('/favorites', [
            App\Http\Controllers\Api\NavigationController::class,
            'updateFavorites',
        ])->name('api.navigation.favorites');
        Route::delete('/cache', [App\Http\Controllers\Api\NavigationController::class, 'clearCache'])
            ->name('api.navigation.clear-cache')
            ->middleware('permission:manage_system_settings');
        Route::get('/metrics', [App\Http\Controllers\Api\NavigationController::class, 'metrics'])
            ->name('api.navigation.metrics')
            ->middleware('permission:view_attendance_reports');
    });

// Academic Schedule Management API routes
Route::prefix('academic-schedules')
    ->middleware(['auth', 'verified'])
    ->group(function () {
        // Grid and data endpoints
        Route::get('/grid/{classId}', [
            App\Http\Controllers\AcademicScheduleController::class,
            'getGrid',
        ])->middleware('permission:view_schedules');
        Route::get('/conflicts/{classId}', [
            App\Http\Controllers\AcademicScheduleController::class,
            'getConflicts',
        ])->middleware('permission:view_schedules');
        Route::post('/available-teachers', [
            App\Http\Controllers\AcademicScheduleController::class,
            'getAvailableTeachers',
        ])->middleware('permission:view_schedules');

        // CRUD operations
        Route::post('/', [App\Http\Controllers\AcademicScheduleController::class, 'store'])->middleware(
            'permission:manage_schedules',
        );
        Route::put('/{schedule}', [
            App\Http\Controllers\AcademicScheduleController::class,
            'update',
        ])->middleware('permission:create_schedules');
        Route::delete('/{schedule}', [
            App\Http\Controllers\AcademicScheduleController::class,
            'destroy',
        ])->middleware('permission:create_schedules');

        // Schedule management operations
        Route::post('/swap', [
            App\Http\Controllers\AcademicScheduleController::class,
            'swapSchedules',
        ])->middleware('permission:create_schedules');
        Route::post('/{schedule}/toggle-lock', [
            App\Http\Controllers\AcademicScheduleController::class,
            'toggleLock',
        ])->middleware('permission:create_schedules');
        Route::post('/{schedule}/duplicate', [
            App\Http\Controllers\AcademicScheduleController::class,
            'duplicate',
        ])->middleware('permission:create_schedules');

        // Import/Export
        Route::post('/import', [
            App\Http\Controllers\AcademicScheduleController::class,
            'import',
        ])->middleware('permission:create_schedules');
        Route::get('/export/{classId}', [
            App\Http\Controllers\AcademicScheduleController::class,
            'export',
        ])->middleware('permission:view_schedules');
        Route::get('/{schedule}/export', [
            App\Http\Controllers\AcademicScheduleController::class,
            'exportSingle',
        ])->middleware('permission:view_schedules');

        // History and conflicts
        Route::get('/{schedule}/history', [
            App\Http\Controllers\AcademicScheduleController::class,
            'getHistory',
        ])->middleware('permission:view_schedules');
        Route::get('/{schedule}/conflicts', [
            App\Http\Controllers\AcademicScheduleController::class,
            'getScheduleConflicts',
        ])->middleware('permission:view_schedules');
        Route::post('/conflicts/{conflict}/resolve', [
            App\Http\Controllers\AcademicScheduleController::class,
            'resolveConflict',
        ])->middleware('permission:create_schedules');

        // Bulk operations
        Route::post('/bulk-update', [
            App\Http\Controllers\AcademicScheduleController::class,
            'bulkUpdate',
        ])->middleware('permission:create_schedules');
        Route::delete('/bulk-delete', [
            App\Http\Controllers\AcademicScheduleController::class,
            'bulkDelete',
        ])->middleware('permission:create_schedules');

        // Statistics and analytics
        Route::get('/statistics', [
            App\Http\Controllers\AcademicScheduleController::class,
            'getStatistics',
        ])->middleware('permission:view_schedules');
        Route::get('/analytics/{classId}', [
            App\Http\Controllers\AcademicScheduleController::class,
            'getAnalytics',
        ])->middleware('permission:view_schedules');
    });

// Security Monitoring API routes
Route::prefix('security')
    ->middleware(['auth', 'verified', 'permission:view_security_dashboard'])
    ->group(function () {
        Route::get('/metrics', [App\Http\Controllers\SecurityController::class, 'getMetrics']);
        Route::get('/events', [App\Http\Controllers\SecurityController::class, 'getEvents']);
        Route::get('/2fa-report', [App\Http\Controllers\SecurityController::class, 'get2FAReport']);
        Route::get('/alerts', [App\Http\Controllers\SecurityController::class, 'getAlerts']);
        Route::get('/statistics', [App\Http\Controllers\SecurityController::class, 'getStatistics']);
        Route::get('/report/download', [
            App\Http\Controllers\SecurityController::class,
            'downloadReport',
        ]);
        Route::post('/alerts/{alert}/acknowledge', [
            App\Http\Controllers\SecurityController::class,
            'acknowledgeAlert',
        ]);
    });

// Device Management API routes
Route::prefix('devices')
    ->middleware(['auth', 'verified'])
    ->group(function () {
        Route::get('/', [App\Http\Controllers\Api\DeviceController::class, 'index']);
        Route::get('/current', [App\Http\Controllers\Api\DeviceController::class, 'current']);
        Route::patch('/{device}/name', [
            App\Http\Controllers\Api\DeviceController::class,
            'updateName',
        ]);
        Route::post('/{device}/trust', [App\Http\Controllers\Api\DeviceController::class, 'trust']);
        Route::delete('/{device}/trust', [
            App\Http\Controllers\Api\DeviceController::class,
            'revokeTrust',
        ]);
        Route::delete('/{device}', [App\Http\Controllers\Api\DeviceController::class, 'destroy']);
        Route::delete('/all', [App\Http\Controllers\Api\DeviceController::class, 'removeAll']);
    });

// Notification Preferences API routes
Route::prefix('notification-preferences')
    ->middleware(['auth', 'verified'])
    ->group(function () {
        Route::get('/', [App\Http\Controllers\Api\NotificationPreferencesController::class, 'index']);
        Route::put('/', [App\Http\Controllers\Api\NotificationPreferencesController::class, 'update']);
        Route::put('/quiet-hours', [
            App\Http\Controllers\Api\NotificationPreferencesController::class,
            'updateQuietHours',
        ]);
        Route::put('/digest-frequency', [
            App\Http\Controllers\Api\NotificationPreferencesController::class,
            'updateDigestFrequency',
        ]);
        Route::post('/test', [
            App\Http\Controllers\Api\NotificationPreferencesController::class,
            'testNotification',
        ]);
        Route::get('/history', [
            App\Http\Controllers\Api\NotificationPreferencesController::class,
            'history',
        ]);
        Route::post('/mark-read', [
            App\Http\Controllers\Api\NotificationPreferencesController::class,
            'markAsRead',
        ]);
    });

// Real-time Notification Streaming API routes
Route::prefix('notifications')
    ->middleware(['auth', 'verified'])
    ->group(function () {
        Route::get('/stream', [App\Http\Controllers\Api\NotificationStreamController::class, 'stream']);
        Route::get('/status', [App\Http\Controllers\Api\NotificationStreamController::class, 'status']);
        Route::post('/test', [
            App\Http\Controllers\Api\NotificationStreamController::class,
            'sendTestNotification',
        ]);
    });
