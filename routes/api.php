<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\HealthController;

// Health check endpoints (no authentication required)
Route::get('/health', [HealthController::class, 'check'])->name('api.health');
Route::get('/ping', [HealthController::class, 'ping'])->name('api.ping');

// API v1 routes
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    
    // User info
    Route::get('/user', function (Request $request) {
        return $request->user()->load(['employee', 'roles', 'permissions']);
    });

    // Two-Factor Authentication endpoints
    Route::prefix('two-factor')->group(function () {
        Route::post('/setup/initialize', [App\Http\Controllers\Auth\TwoFactorController::class, 'initializeSetup'])
            ->middleware('2fa.rate_limit:setup_attempt');
        Route::post('/setup/verify', [App\Http\Controllers\Auth\TwoFactorController::class, 'enable'])
            ->middleware('2fa.rate_limit:setup_attempt');
        Route::post('/verify', [App\Http\Controllers\Auth\TwoFactorController::class, 'verifyCode'])
            ->middleware('2fa.rate_limit:verification');
        Route::delete('/disable', [App\Http\Controllers\Auth\TwoFactorController::class, 'disable']);
        Route::post('/recovery-codes/regenerate', [App\Http\Controllers\Auth\TwoFactorController::class, 'regenerateRecoveryCodes']);
        Route::get('/status', [App\Http\Controllers\Auth\TwoFactorController::class, 'status']);
        Route::post('/sms/send', [App\Http\Controllers\Auth\TwoFactorController::class, 'sendSMS'])
            ->middleware('2fa.rate_limit:sms_request');
        Route::get('/qr-code', [App\Http\Controllers\Auth\TwoFactorController::class, 'getQRCode']);
    });
    
    // Attendance endpoints
    Route::prefix('attendance')->group(function () {
        Route::get('/status', [App\Http\Controllers\AttendanceController::class, 'getStatus'])
            ->middleware('permission:view_attendance');
        Route::post('/check-in', [App\Http\Controllers\AttendanceController::class, 'processCheckIn'])
            ->middleware('permission:manage_own_attendance');
        Route::post('/check-out', [App\Http\Controllers\AttendanceController::class, 'processCheckOut'])
            ->middleware('permission:manage_own_attendance');
        Route::get('/data', [App\Http\Controllers\AttendanceController::class, 'getAttendanceData'])
            ->middleware('permission:view_attendance');
        Route::get('/statistics', [App\Http\Controllers\AttendanceController::class, 'getStatistics'])
            ->middleware('permission:view_attendance_reports');
        Route::get('/{attendance}/details', [App\Http\Controllers\AttendanceController::class, 'getAttendanceDetails'])
            ->middleware('permission:view_attendance');
        Route::post('/{attendance}/manual-checkout', [App\Http\Controllers\AttendanceController::class, 'manualCheckOut'])
            ->middleware('permission:manage_all_attendance');
        Route::get('/export', [App\Http\Controllers\AttendanceController::class, 'exportAttendance'])
            ->middleware('permission:view_attendance_reports');
    });

    // Face detection endpoints
    Route::prefix('face-detection')->group(function () {
        Route::post('/register', [App\Http\Controllers\FaceDetectionController::class, 'registerFace'])
            ->middleware('permission:manage_employees');
        Route::post('/verify', [App\Http\Controllers\FaceDetectionController::class, 'verifyFace'])
            ->middleware('permission:manage_own_attendance');
        Route::get('/faces', [App\Http\Controllers\FaceDetectionController::class, 'getRegisteredFaces'])
            ->middleware('permission:view_employees');
        Route::put('/faces/{employee}', [App\Http\Controllers\FaceDetectionController::class, 'updateFace'])
            ->middleware('permission:manage_employees');
        Route::delete('/faces/{employee}', [App\Http\Controllers\FaceDetectionController::class, 'deleteFace'])
            ->middleware('permission:manage_employees');
        Route::get('/statistics', [App\Http\Controllers\FaceDetectionController::class, 'getStatistics'])
            ->middleware('permission:view_employees');
    });

    // User management endpoints
    Route::prefix('users')->middleware('permission:manage_system')->group(function () {
        Route::get('/data', [App\Http\Controllers\UserController::class, 'getData']);
        Route::get('/statistics', [App\Http\Controllers\UserController::class, 'getStatistics']);
        Route::get('/select', [App\Http\Controllers\UserController::class, 'getUsersForSelect']);
        Route::post('/', [App\Http\Controllers\UserController::class, 'store']);
        Route::put('/{user}', [App\Http\Controllers\UserController::class, 'update']);
        Route::delete('/{user}', [App\Http\Controllers\UserController::class, 'destroy']);
        Route::post('/{user}/toggle-status', [App\Http\Controllers\UserController::class, 'toggleStatus']);
        Route::post('/{user}/reset-password', [App\Http\Controllers\UserController::class, 'resetPassword']);
    });

    // Location management endpoints
    Route::prefix('locations')->group(function () {
        Route::get('/select', [App\Http\Controllers\LocationController::class, 'getLocationsForSelect'])
            ->middleware('permission:view_employees');
        Route::post('/verify', [App\Http\Controllers\LocationController::class, 'verifyLocation'])
            ->middleware('permission:manage_own_attendance');
    });

    // Leave management endpoints
    Route::prefix('leave')->group(function () {
        Route::get('/balance', [App\Http\Controllers\LeaveBalanceController::class, 'getBalance'])
            ->middleware('permission:view_leave');
        Route::post('/calculate-days', [App\Http\Controllers\LeaveController::class, 'calculateDays'])
            ->middleware('permission:view_leave');
    });

    // Dashboard endpoints for Vue component
    Route::prefix('dashboard')->group(function () {
        Route::get('/attendance', [App\Http\Controllers\Api\AttendanceDashboardController::class, 'dashboard'])
            ->middleware('permission:view_attendance')
            ->name('api.attendance.dashboard');
        Route::get('/stats', [App\Http\Controllers\Api\AttendanceDashboardController::class, 'stats'])
            ->middleware('permission:view_attendance')
            ->name('api.attendance.stats');
    });
});

// Session-based API routes for Vue dashboard (outside Sanctum middleware)
Route::prefix('vue')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard/attendance', [App\Http\Controllers\Api\AttendanceDashboardController::class, 'dashboard'])
        ->name('api.vue.attendance.dashboard');
    Route::get('/dashboard/stats', [App\Http\Controllers\Api\AttendanceDashboardController::class, 'stats'])
        ->name('api.vue.attendance.stats');
});

// Navigation API routes for enhanced sidebar
Route::prefix('navigation')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/', [App\Http\Controllers\Api\NavigationController::class, 'index'])
        ->name('api.navigation.index');
    Route::post('/search', [App\Http\Controllers\Api\NavigationController::class, 'search'])
        ->name('api.navigation.search');
    Route::post('/favorites', [App\Http\Controllers\Api\NavigationController::class, 'updateFavorites'])
        ->name('api.navigation.favorites');
    Route::delete('/cache', [App\Http\Controllers\Api\NavigationController::class, 'clearCache'])
        ->name('api.navigation.clear-cache')
        ->middleware('permission:manage_settings');
    Route::get('/metrics', [App\Http\Controllers\Api\NavigationController::class, 'metrics'])
        ->name('api.navigation.metrics')
        ->middleware('permission:view_attendance_reports');
});

// Academic Schedule Management API routes
Route::prefix('academic-schedules')->middleware(['auth', 'verified'])->group(function () {
    // Grid and data endpoints
    Route::get('/grid/{classId}', [App\Http\Controllers\AcademicScheduleController::class, 'getGrid'])
        ->middleware('permission:view_schedules');
    Route::get('/conflicts/{classId}', [App\Http\Controllers\AcademicScheduleController::class, 'getConflicts'])
        ->middleware('permission:view_schedules');
    Route::post('/available-teachers', [App\Http\Controllers\AcademicScheduleController::class, 'getAvailableTeachers'])
        ->middleware('permission:view_schedules');
    
    // CRUD operations
    Route::post('/', [App\Http\Controllers\AcademicScheduleController::class, 'store'])
        ->middleware('permission:manage_schedules');
    Route::put('/{schedule}', [App\Http\Controllers\AcademicScheduleController::class, 'update'])
        ->middleware('permission:manage_schedules');
    Route::delete('/{schedule}', [App\Http\Controllers\AcademicScheduleController::class, 'destroy'])
        ->middleware('permission:manage_schedules');
    
    // Schedule management operations
    Route::post('/swap', [App\Http\Controllers\AcademicScheduleController::class, 'swapSchedules'])
        ->middleware('permission:manage_schedules');
    Route::post('/{schedule}/toggle-lock', [App\Http\Controllers\AcademicScheduleController::class, 'toggleLock'])
        ->middleware('permission:manage_schedules');
    Route::post('/{schedule}/duplicate', [App\Http\Controllers\AcademicScheduleController::class, 'duplicate'])
        ->middleware('permission:manage_schedules');
    
    // Import/Export
    Route::post('/import', [App\Http\Controllers\AcademicScheduleController::class, 'import'])
        ->middleware('permission:manage_schedules');
    Route::get('/export/{classId}', [App\Http\Controllers\AcademicScheduleController::class, 'export'])
        ->middleware('permission:view_schedules');
    Route::get('/{schedule}/export', [App\Http\Controllers\AcademicScheduleController::class, 'exportSingle'])
        ->middleware('permission:view_schedules');
    
    // History and conflicts
    Route::get('/{schedule}/history', [App\Http\Controllers\AcademicScheduleController::class, 'getHistory'])
        ->middleware('permission:view_schedules');
    Route::get('/{schedule}/conflicts', [App\Http\Controllers\AcademicScheduleController::class, 'getScheduleConflicts'])
        ->middleware('permission:view_schedules');
    Route::post('/conflicts/{conflict}/resolve', [App\Http\Controllers\AcademicScheduleController::class, 'resolveConflict'])
        ->middleware('permission:manage_schedules');
    
    // Bulk operations
    Route::post('/bulk-update', [App\Http\Controllers\AcademicScheduleController::class, 'bulkUpdate'])
        ->middleware('permission:manage_schedules');
    Route::delete('/bulk-delete', [App\Http\Controllers\AcademicScheduleController::class, 'bulkDelete'])
        ->middleware('permission:manage_schedules');
    
    // Statistics and analytics
    Route::get('/statistics', [App\Http\Controllers\AcademicScheduleController::class, 'getStatistics'])
        ->middleware('permission:view_schedules');
    Route::get('/analytics/{classId}', [App\Http\Controllers\AcademicScheduleController::class, 'getAnalytics'])
        ->middleware('permission:view_schedules');
});

// Security Monitoring API routes
Route::prefix('security')->middleware(['auth', 'verified', 'permission:view_security_dashboard'])->group(function () {
    Route::get('/metrics', [App\Http\Controllers\SecurityController::class, 'getMetrics']);
    Route::get('/events', [App\Http\Controllers\SecurityController::class, 'getEvents']);
    Route::get('/2fa-report', [App\Http\Controllers\SecurityController::class, 'get2FAReport']);
    Route::get('/alerts', [App\Http\Controllers\SecurityController::class, 'getAlerts']);
    Route::get('/statistics', [App\Http\Controllers\SecurityController::class, 'getStatistics']);
    Route::get('/report/download', [App\Http\Controllers\SecurityController::class, 'downloadReport']);
    Route::post('/alerts/{alert}/acknowledge', [App\Http\Controllers\SecurityController::class, 'acknowledgeAlert']);
});

// Device Management API routes
Route::prefix('devices')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/', [App\Http\Controllers\Api\DeviceController::class, 'index']);
    Route::get('/current', [App\Http\Controllers\Api\DeviceController::class, 'current']);
    Route::patch('/{device}/name', [App\Http\Controllers\Api\DeviceController::class, 'updateName']);
    Route::post('/{device}/trust', [App\Http\Controllers\Api\DeviceController::class, 'trust']);
    Route::delete('/{device}/trust', [App\Http\Controllers\Api\DeviceController::class, 'revokeTrust']);
    Route::delete('/{device}', [App\Http\Controllers\Api\DeviceController::class, 'destroy']);
    Route::delete('/all', [App\Http\Controllers\Api\DeviceController::class, 'removeAll']);
});

// Notification Preferences API routes
Route::prefix('notification-preferences')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/', [App\Http\Controllers\Api\NotificationPreferencesController::class, 'index']);
    Route::put('/', [App\Http\Controllers\Api\NotificationPreferencesController::class, 'update']);
    Route::put('/quiet-hours', [App\Http\Controllers\Api\NotificationPreferencesController::class, 'updateQuietHours']);
    Route::put('/digest-frequency', [App\Http\Controllers\Api\NotificationPreferencesController::class, 'updateDigestFrequency']);
    Route::post('/test', [App\Http\Controllers\Api\NotificationPreferencesController::class, 'testNotification']);
    Route::get('/history', [App\Http\Controllers\Api\NotificationPreferencesController::class, 'history']);
    Route::post('/mark-read', [App\Http\Controllers\Api\NotificationPreferencesController::class, 'markAsRead']);
});

// Real-time Notification Streaming API routes
Route::prefix('notifications')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/stream', [App\Http\Controllers\Api\NotificationStreamController::class, 'stream']);
    Route::get('/status', [App\Http\Controllers\Api\NotificationStreamController::class, 'status']);
    Route::post('/test', [App\Http\Controllers\Api\NotificationStreamController::class, 'sendTestNotification']);
});
