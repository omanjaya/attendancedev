<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\UserDevice;
use App\Services\DeviceService;
use App\Services\SecurityLogger;
use App\Services\SecurityNotificationService;
use App\Services\SecurityService;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class TwoFactorController extends Controller
{
    private TwoFactorService $twoFactorService;

    private SecurityService $securityService;

    private SecurityLogger $securityLogger;

    private DeviceService $deviceService;

    private SecurityNotificationService $notificationService;

    public function __construct(
        TwoFactorService $twoFactorService,
        SecurityService $securityService,
        SecurityLogger $securityLogger,
        DeviceService $deviceService,
        SecurityNotificationService $notificationService,
    ) {
        $this->twoFactorService = $twoFactorService;
        $this->securityService = $securityService;
        $this->securityLogger = $securityLogger;
        $this->deviceService = $deviceService;
        $this->notificationService = $notificationService;
        $this->middleware('auth');
    }

    /**
     * Show 2FA verification form.
     */
    public function verify()
    {
        $user = Auth::user();

        if (! $user->two_factor_enabled) {
            return redirect()->route('dashboard');
        }

        if ($this->twoFactorService->isSessionVerified($user)) {
            return redirect()->route('dashboard');
        }

        return view('pages.auth.2fa.verify');
    }

    /**
     * Process 2FA verification.
     */
    public function verifyCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string|min:6|max:8',
            'type' => 'in:totp,recovery,sms',
            'remember_device' => 'sometimes|boolean',
        ]);

        $user = Auth::user();
        $code = $request->input('code');
        $type = $request->input('type', 'totp');
        $rememberDevice = $request->boolean('remember_device', false);

        // Create unique identifier for rate limiting (user ID + IP)
        $identifier = $user->id.'_'.$request->ip();

        // Check for security lockdown
        if ($this->securityService->is2FALockedDown($identifier)) {
            return response()->json(
                [
                    'success' => false,
                    'error' => 'Account temporarily locked due to security concerns. Please contact support.',
                    'lockdown' => true,
                ],
                423,
            );
        }

        // Check rate limiting before processing
        if ($this->securityService->isRateLimited($request->ip(), "2fa_{$type}")) {
            return response()->json(
                [
                    'success' => false,
                    'error' => 'Too many attempts. Please try again later.',
                    'rate_limited' => true,
                ],
                429,
            );
        }

        $verified = match ($type) {
            'totp' => $this->twoFactorService->verifyUserCode($user, $code),
            'recovery' => $this->twoFactorService->verifyRecoveryCode($user, $code),
            'sms' => $this->twoFactorService->verifySMSCode($user, $code),
            default => false,
        };

        // Track the attempt with enhanced security monitoring
        $attemptResult = $this->securityService->track2FAAttempt($identifier, $type, $verified);

        // Log the 2FA attempt using SecurityLogger
        $this->securityLogger->log2FAAttempt($user, $type, $verified, $request, [
            'remaining_attempts' => $attemptResult['remaining_attempts'] ?? null,
            'is_locked' => $attemptResult['is_locked'] ?? false,
        ]);

        if ($verified) {
            $request->session()->regenerate();

            // Mark session as 2FA verified
            \App\Http\Middleware\TwoFactorAuthentication::markSessionAsVerified();

            // Handle device trust if user requested to remember device
            if ($rememberDevice) {
                $deviceId = session('2fa_device_id');
                if ($deviceId) {
                    $device = UserDevice::find($deviceId);
                    if ($device && $device->user_id === $user->id) {
                        $this->deviceService->trustDevice($device, $user);
                    }
                }

                // Legacy middleware support
                \App\Http\Middleware\TwoFactorAuthentication::rememberDevice($request);
            }

            // Clear rate limits on successful verification
            $this->securityService->clearFailedLoginAttempts($identifier);

            // Get remaining recovery codes for warning
            $remainingCodes = count($this->twoFactorService->getRecoveryCodes($user));
            $warning = null;

            if ($type === 'recovery' && $remainingCodes <= 2) {
                $warning = 'You have few recovery codes remaining. Consider regenerating them.';
            }

            return response()->json([
                'success' => true,
                'redirect' => session('intended', route('dashboard')),
                'warning' => $warning,
                'remaining_codes' => $type === 'recovery' ? $remainingCodes : null,
            ]);
        }

        // Record global failure for attack detection
        $this->securityService->recordGlobal2FAFailure($request->ip(), $user->id);

        // Log failed attempt for security monitoring
        $this->logFailedVerification($request, $user, $type);

        // Check if admin intervention is required
        if ($attemptResult['requires_admin_intervention']) {
            return response()->json(
                [
                    'success' => false,
                    'error' => 'Account locked due to repeated security violations. Administrator assistance required.',
                    'admin_required' => true,
                ],
                423,
            );
        }

        // Return attempt information for progressive feedback
        $errorMessage = $attemptResult['is_locked']
          ? 'Too many failed attempts. Locked until '.$attemptResult['locked_until']->format('H:i:s')
          : "Invalid code. {$attemptResult['remaining_attempts']} attempts remaining.";

        throw ValidationException::withMessages([
            'code' => [$errorMessage],
        ]);
    }

    /**
     * Show recovery code verification form.
     */
    public function showRecovery()
    {
        $user = Auth::user();

        if (! $user->two_factor_enabled) {
            return redirect()->route('dashboard');
        }

        $recoveryCodes = $this->twoFactorService->getRecoveryCodes($user);

        return view('pages.auth.2fa.recovery', [
            'remaining_codes' => count($recoveryCodes),
        ]);
    }

    /**
     * Process recovery code verification.
     */
    public function verifyRecovery(Request $request)
    {
        $request->validate([
            'recovery_code' => 'required|string|size:8',
        ]);

        $user = Auth::user();
        $code = strtoupper($request->input('recovery_code'));

        if ($this->twoFactorService->verifyRecoveryCode($user, $code)) {
            $request->session()->regenerate();

            // Mark session as 2FA verified
            \App\Http\Middleware\TwoFactorAuthentication::markSessionAsVerified();

            $remainingCodes = count($this->twoFactorService->getRecoveryCodes($user));

            // Show warning if running low on codes
            if ($remainingCodes <= 2) {
                session()->flash(
                    'warning',
                    'You have few recovery codes remaining. Consider regenerating them from your security settings.',
                );
            }

            return response()->json([
                'success' => true,
                'redirect' => session('intended', route('dashboard')),
                'remaining_codes' => $remainingCodes,
            ]);
        }

        throw ValidationException::withMessages([
            'recovery_code' => ['The provided recovery code is invalid or has already been used.'],
        ]);
    }

    /**
     * Request emergency recovery (admin assistance).
     */
    public function requestEmergencyRecovery(Request $request)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
            'contact_method' => 'required|in:email,phone',
            'emergency_contact' => 'required|string|max:255',
        ]);

        $user = Auth::user();

        // Create emergency recovery request
        $recoveryData = [
            'user_id' => $user->id,
            'reason' => $request->input('reason'),
            'contact_method' => $request->input('contact_method'),
            'emergency_contact' => $request->input('emergency_contact'),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'requested_at' => now()->toISOString(),
            'status' => 'pending',
        ];

        // Store in cache for admin review
        cache()->put("emergency_recovery_{$user->id}_".time(), $recoveryData, now()->addHours(24));

        // Send notification to admins
        $this->notifyAdminsOfEmergencyRecovery($user, $recoveryData);

        // Log emergency recovery request using SecurityLogger
        $this->securityLogger->logEmergencyRecovery($user, $recoveryData, $request);

        return response()->json([
            'success' => true,
            'message' => 'Emergency recovery request submitted. An administrator will contact you within 24 hours.',
        ]);
    }

    /**
     * Show account lockout recovery form.
     */
    public function showAccountRecovery()
    {
        return view('pages.auth.2fa.account-recovery');
    }

    /**
     * Log failed verification attempt.
     */
    private function logFailedVerification(Request $request, $user, string $type): void
    {
        $attemptData = [
            'user_id' => $user->id,
            'attempt_type' => $type,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_id' => session()->getId(),
            'timestamp' => now()->toISOString(),
        ];

        // Log to security channel
        logger()->channel('security')->warning('Failed 2FA Verification', $attemptData);

        // Increment failed attempts counter
        $failureKey = "2fa_failures_{$user->id}";
        $failures = cache()->get($failureKey, 0);
        $failures++;

        cache()->put($failureKey, $failures, now()->addMinutes(30));

        // Lock account after too many failures
        if ($failures >= 5) {
            $user->lockAccount(now()->addMinutes(30));

            logger()->channel('security')->critical('Account Locked Due to 2FA Failures', $attemptData);

            // Send security alert
            $this->sendSecurityAlert($user, 'Account locked due to repeated 2FA failures');
        }
    }

    /**
     * Notify admins of emergency recovery request.
     */
    private function notifyAdminsOfEmergencyRecovery($user, array $recoveryData): void
    {
        // Get all admin users
        $admins = \App\Models\User::role(['admin', 'superadmin'])->get();

        foreach ($admins as $admin) {
            // Send email notification (implement with your mail system)
            \Mail::send(
                'emails.emergency-recovery-request',
                [
                    'user' => $user,
                    'admin' => $admin,
                    'recovery_data' => $recoveryData,
                ],
                function ($message) use ($admin) {
                    $message->to($admin->email)->subject('Emergency 2FA Recovery Request');
                },
            );
        }
    }

    /**
     * Send security alert notification.
     */
    private function sendSecurityAlert($user, string $message): void
    {
        // Send email to user
        \Mail::send(
            'emails.security-alert',
            [
                'user' => $user,
                'message' => $message,
                'timestamp' => now(),
                'ip_address' => request()->ip(),
            ],
            function ($mail) use ($user) {
                $mail->to($user->email)->subject('Security Alert - '.config('app.name'));
            },
        );
    }

    /**
     * Show 2FA setup page.
     */
    public function setup()
    {
        $user = Auth::user();

        if ($user->two_factor_enabled) {
            return redirect()->route('2fa.manage');
        }

        $secretKey = $this->twoFactorService->generateSecretKey($user);
        $qrCode = $this->twoFactorService->generateQRCode($user, $secretKey);

        return view('pages.auth.2fa.setup', compact('secretKey', 'qrCode'));
    }

    /**
     * Enable 2FA for user.
     */
    public function enable(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = Auth::user();
        $code = $request->input('code');

        if ($this->twoFactorService->enableTwoFactor($user, $code)) {
            // Log 2FA setup completion
            $this->securityLogger->log2FASetup($user, 'enabled', $request, [
                'verification_code_used' => true,
                'recovery_codes_generated' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Two-factor authentication has been enabled successfully.',
                'recovery_codes' => $this->twoFactorService->getRecoveryCodes($user),
            ]);
        }

        throw ValidationException::withMessages([
            'code' => ['The provided code is invalid. Please try again.'],
        ]);
    }

    /**
     * Show 2FA management page.
     */
    public function manage()
    {
        $user = Auth::user();

        if (! $user->two_factor_enabled) {
            return redirect()->route('2fa.setup');
        }

        $recoveryCodes = $this->twoFactorService->getRecoveryCodes($user);
        $isRequired = $this->twoFactorService->isRequiredForUser($user);

        return view('pages.auth.2fa.manage', compact('recoveryCodes', 'isRequired'));
    }

    /**
     * Disable 2FA for user.
     */
    public function disable(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = Auth::user();
        $password = $request->input('password');

        // Check if 2FA is required for this user's role
        if ($this->twoFactorService->isRequiredForUser($user)) {
            throw ValidationException::withMessages([
                'password' => ['Two-factor authentication cannot be disabled for your role.'],
            ]);
        }

        if ($this->twoFactorService->disableTwoFactor($user, $password)) {
            // Log 2FA disable action
            $this->securityLogger->log2FASetup($user, 'disabled', $request, [
                'password_verified' => true,
                'session_cleared' => true,
            ]);

            $this->twoFactorService->clearSessionVerification();

            return response()->json([
                'success' => true,
                'message' => 'Two-factor authentication has been disabled.',
            ]);
        }

        throw ValidationException::withMessages([
            'password' => ['The provided password is incorrect.'],
        ]);
    }

    /**
     * Regenerate recovery codes.
     */
    public function regenerateRecoveryCodes(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = Auth::user();
        $password = $request->input('password');

        if (! password_verify($password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['The provided password is incorrect.'],
            ]);
        }

        $newCodes = $this->twoFactorService->regenerateRecoveryCodes($user);

        // Log recovery codes regeneration
        $this->securityLogger->log2FASetup($user, 'recovery_codes_regenerated', $request, [
            'password_verified' => true,
            'new_codes_count' => count($newCodes),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'New recovery codes have been generated.',
            'recovery_codes' => $newCodes,
        ]);
    }

    /**
     * Send SMS verification code.
     */
    public function sendSMS(Request $request)
    {
        $user = Auth::user();

        if (! $user->phone) {
            throw ValidationException::withMessages([
                'phone' => ['No phone number is configured for SMS verification.'],
            ]);
        }

        // Check SMS rate limiting
        if ($this->securityService->isRateLimited($request->ip(), '2fa_sms_request')) {
            return response()->json(
                [
                    'success' => false,
                    'error' => 'Too many SMS requests. Please try again later.',
                ],
                429,
            );
        }

        // Track SMS request attempt
        $this->securityService->trackRateLimit($request->ip(), '2fa_sms_request');

        if ($this->twoFactorService->sendSMSCode($user)) {
            return response()->json([
                'success' => true,
                'message' => 'SMS verification code has been sent.',
            ]);
        }

        throw ValidationException::withMessages([
            'sms' => ['Failed to send SMS verification code.'],
        ]);
    }

    /**
     * Initialize 2FA setup - Generate new secret and QR code for API
     */
    public function initializeSetup()
    {
        $user = Auth::user();

        if ($user->two_factor_enabled) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Two-factor authentication is already enabled.',
                ],
                400,
            );
        }

        $secretKey = $this->twoFactorService->generateSecretKey($user);
        $qrCodeSvg = $this->twoFactorService->generateQRCode($user, $secretKey);

        // Convert SVG to base64 data URL for frontend
        $qrCodeDataUrl = 'data:image/svg+xml;base64,'.base64_encode($qrCodeSvg);

        return response()->json([
            'success' => true,
            'data' => [
                'secret_key' => $secretKey,
                'qr_code_url' => $qrCodeDataUrl,
                'manual_entry_key' => $secretKey,
                'company_name' => config('app.name', 'Attendance System'),
                'user_email' => $user->email,
            ],
        ]);
    }

    /**
     * Get 2FA QR code for re-display.
     */
    public function getQRCode()
    {
        $user = Auth::user();

        if (! $user->two_factor_secret) {
            return response()->json(['error' => '2FA not configured'], 400);
        }

        $secret = decrypt($user->two_factor_secret);
        $qrCode = $this->twoFactorService->generateQRCode($user, $secret);

        return response()->json([
            'qr_code' => $qrCode,
            'secret' => $secret,
        ]);
    }

    /**
     * Check 2FA status for user.
     */
    public function status()
    {
        $user = Auth::user();

        return response()->json([
            'enabled' => $user->two_factor_enabled,
            'required' => $this->twoFactorService->isRequiredForUser($user),
            'verified' => $this->twoFactorService->isSessionVerified($user),
            'has_recovery_codes' => ! empty($user->two_factor_recovery_codes),
            'recovery_codes_count' => count($this->twoFactorService->getRecoveryCodes($user)),
        ]);
    }
}
