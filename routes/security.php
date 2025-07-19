<?php

use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\SecurityController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Security & Authentication Routes
|--------------------------------------------------------------------------
|
| Routes for two-factor authentication, security settings, and account protection
|
*/

// Two-Factor Authentication routes
Route::middleware('auth')
    ->prefix('2fa')
    ->name('2fa.')
    ->group(function () {
        // 2FA Verification (required after login)
        Route::get('/verify', [TwoFactorController::class, 'verify'])->name('verify');
        Route::post('/verify', [TwoFactorController::class, 'verifyCode'])->middleware(
            '2fa.rate_limit:verification',
        );

        // 2FA Setup and Management
        Route::get('/setup', [TwoFactorController::class, 'setup'])->name('setup');
        Route::post('/enable', [TwoFactorController::class, 'enable'])->middleware(
            '2fa.rate_limit:setup_attempt',
        )->name('enable');
        Route::get('/manage', [TwoFactorController::class, 'manage'])->name('manage');
        Route::post('/disable', [TwoFactorController::class, 'disable'])->name('disable');
        Route::post('/regenerate-codes', [TwoFactorController::class, 'regenerateRecoveryCodes'])->name('regenerate-codes');

        // SMS Two-Factor (if enabled)
        Route::post('/sms', [TwoFactorController::class, 'sendSMS'])->middleware(
            '2fa.rate_limit:sms_request',
        )->name('sms');

        // QR Code and Status API
        Route::get('/qr', [TwoFactorController::class, 'getQRCode'])->name('qr');
        Route::get('/status', [TwoFactorController::class, 'status'])->name('status');

        // Recovery Methods
        Route::get('/recovery', [TwoFactorController::class, 'showRecovery'])->name('recovery');
        Route::post('/recovery', [TwoFactorController::class, 'verifyRecovery'])->middleware(
            '2fa.rate_limit:recovery_code',
        );

        // Emergency Recovery (for locked accounts)
        Route::get('/account-recovery', [TwoFactorController::class, 'showAccountRecovery'])->name(
            'account-recovery',
        );
        Route::post('/emergency-recovery', [TwoFactorController::class, 'requestEmergencyRecovery'])
            ->middleware('2fa.rate_limit:emergency_recovery')
            ->name('emergency-recovery');
    });

// Security Dashboard routes
Route::middleware('auth')
    ->prefix('security')
    ->name('security.')
    ->group(function () {
        // Security Dashboard
        Route::get('/dashboard', [SecurityController::class, 'dashboard'])->name('dashboard');

        // Device Management
        Route::get('/devices', [SecurityController::class, 'devices'])->name('devices');
        Route::post('/devices/{device}/trust', [SecurityController::class, 'trustDevice'])->name('devices.trust');
        Route::delete('/devices/{device}', [SecurityController::class, 'removeDevice'])->name('devices.remove');

        // Security Notifications
        Route::get('/notifications', [SecurityController::class, 'notifications'])->name('notifications');
        Route::post('/notifications/mark-read', [SecurityController::class, 'markNotificationRead'])->name('notifications.mark-read');

        // Security Events
        Route::get('/events', [SecurityController::class, 'events'])->name('events');

        // Two-Factor Authentication (alias for better navigation)
        Route::get('/two-factor', [SecurityController::class, 'twoFactor'])->name('two-factor');
    });
