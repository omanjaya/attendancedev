<?php

use App\Http\Controllers\Auth\TwoFactorController;
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
Route::middleware('auth')->prefix('2fa')->name('2fa.')->group(function () {
    
    // 2FA Verification (required after login)
    Route::get('/verify', [TwoFactorController::class, 'verify'])->name('verify');
    Route::post('/verify', [TwoFactorController::class, 'verifyCode'])
        ->middleware('2fa.rate_limit:verification');
    
    // 2FA Setup and Management
    Route::get('/setup', [TwoFactorController::class, 'setup'])->name('setup');
    Route::post('/enable', [TwoFactorController::class, 'enable'])
        ->middleware('2fa.rate_limit:setup_attempt');
    Route::get('/manage', [TwoFactorController::class, 'manage'])->name('manage');
    Route::post('/disable', [TwoFactorController::class, 'disable']);
    Route::post('/regenerate-codes', [TwoFactorController::class, 'regenerateRecoveryCodes']);
    
    // SMS Two-Factor (if enabled)
    Route::post('/sms', [TwoFactorController::class, 'sendSMS'])
        ->middleware('2fa.rate_limit:sms_request');
    
    // QR Code and Status API
    Route::get('/qr', [TwoFactorController::class, 'getQRCode']);
    Route::get('/status', [TwoFactorController::class, 'status']);
    
    // Recovery Methods
    Route::get('/recovery', [TwoFactorController::class, 'showRecovery'])->name('recovery');
    Route::post('/recovery', [TwoFactorController::class, 'verifyRecovery'])
        ->middleware('2fa.rate_limit:recovery_code');
    
    // Emergency Recovery (for locked accounts)
    Route::get('/account-recovery', [TwoFactorController::class, 'showAccountRecovery'])
        ->name('account-recovery');
    Route::post('/emergency-recovery', [TwoFactorController::class, 'requestEmergencyRecovery'])
        ->middleware('2fa.rate_limit:emergency_recovery')
        ->name('emergency-recovery');
});