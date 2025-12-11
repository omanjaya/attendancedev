<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class TwoFactorService
{
    private Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA;
    }

    /**
     * Generate a new 2FA secret for the user.
     */
    public function generateSecretKey(User $user): string
    {
        $secretKey = $this->google2fa->generateSecretKey();

        // Store the secret temporarily (user must verify before saving)
        Cache::put("2fa_temp_secret_{$user->id}", $secretKey, 600); // 10 minutes

        return $secretKey;
    }

    /**
     * Generate QR code for 2FA setup.
     */
    public function generateQRCode(User $user, string $secretKey): string
    {
        $companyName = config('app.name', 'Attendance System');
        $userEmail = $user->email;

        $qrCodeUrl = $this->google2fa->getQRCodeUrl($companyName, $userEmail, $secretKey);

        return QrCode::size(200)->generate($qrCodeUrl);
    }

    /**
     * Verify 2FA code and enable 2FA for user.
     */
    public function enableTwoFactor(User $user, string $code): bool
    {
        $secretKey = Cache::get("2fa_temp_secret_{$user->id}");

        if (! $secretKey) {
            return false;
        }

        if ($this->verifyCode($secretKey, $code)) {
            // Save the secret and enable 2FA
            $user->update([
                'two_factor_secret' => encrypt($secretKey),
                'two_factor_enabled' => true,
                'two_factor_recovery_codes' => $this->generateRecoveryCodes(),
            ]);

            // Clear temporary secret
            Cache::forget("2fa_temp_secret_{$user->id}");

            // Send confirmation email
            $this->sendTwoFactorEnabledNotification($user);

            return true;
        }

        return false;
    }

    /**
     * Disable 2FA for user.
     */
    public function disableTwoFactor(User $user, string $password): bool
    {
        if (! password_verify($password, $user->password)) {
            return false;
        }

        $user->update([
            'two_factor_secret' => null,
            'two_factor_enabled' => false,
            'two_factor_recovery_codes' => null,
        ]);

        // Send notification email
        $this->sendTwoFactorDisabledNotification($user);

        return true;
    }

    /**
     * Verify 2FA code.
     */
    public function verifyCode(string $secret, string $code): bool
    {
        return $this->google2fa->verifyKey($secret, $code, 2); // 2 window tolerance
    }

    /**
     * Verify 2FA code for authenticated user.
     */
    public function verifyUserCode(User $user, string $code): bool
    {
        if (! $user->two_factor_enabled || ! $user->two_factor_secret) {
            return false;
        }

        $secret = decrypt($user->two_factor_secret);

        // Check if code was recently used (prevent replay attacks)
        $cacheKey = "2fa_used_code_{$user->id}_{$code}";
        if (Cache::has($cacheKey)) {
            return false;
        }

        if ($this->verifyCode($secret, $code)) {
            // Mark code as used for 60 seconds
            Cache::put($cacheKey, true, 60);

            // Mark session as 2FA verified
            session(['2fa_verified' => $user->id]);

            return true;
        }

        return false;
    }

    /**
     * Verify recovery code.
     */
    public function verifyRecoveryCode(User $user, string $code): bool
    {
        if (! $user->two_factor_enabled || ! $user->two_factor_recovery_codes) {
            return false;
        }

        $recoveryCodes = json_decode($user->two_factor_recovery_codes, true);

        if (in_array($code, $recoveryCodes)) {
            // Remove used recovery code
            $remainingCodes = array_filter($recoveryCodes, fn ($c) => $c !== $code);

            $user->update([
                'two_factor_recovery_codes' => json_encode(array_values($remainingCodes)),
            ]);

            // Mark session as 2FA verified
            session(['2fa_verified' => $user->id]);

            // Send warning if running low on recovery codes
            if (count($remainingCodes) <= 2) {
                $this->sendLowRecoveryCodesWarning($user);
            }

            return true;
        }

        return false;
    }

    /**
     * Generate new recovery codes.
     */
    public function generateRecoveryCodes(): string
    {
        $codes = [];

        for ($i = 0; $i < 8; $i++) {
            $codes[] = strtoupper(Str::random(8));
        }

        return json_encode($codes);
    }

    /**
     * Regenerate recovery codes for user.
     */
    public function regenerateRecoveryCodes(User $user): array
    {
        if (! $user->two_factor_enabled) {
            return [];
        }

        $newCodes = $this->generateRecoveryCodes();

        $user->update([
            'two_factor_recovery_codes' => $newCodes,
        ]);

        return json_decode($newCodes, true);
    }

    /**
     * Send SMS code (if SMS 2FA is enabled).
     */
    public function sendSMSCode(User $user): bool
    {
        if (! $user->phone || ! config('services.sms.enabled')) {
            return false;
        }

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Store code temporarily
        Cache::put("2fa_sms_code_{$user->id}", $code, 300); // 5 minutes

        // Send SMS (implement with your SMS provider)
        // Example: SMS::send($user->phone, "Your verification code: {$code}");

        return true;
    }

    /**
     * Verify SMS code.
     */
    public function verifySMSCode(User $user, string $code): bool
    {
        $storedCode = Cache::get("2fa_sms_code_{$user->id}");

        if ($storedCode && $storedCode === $code) {
            Cache::forget("2fa_sms_code_{$user->id}");
            session(['2fa_verified' => $user->id]);

            return true;
        }

        return false;
    }

    /**
     * Send email notification when 2FA is enabled.
     */
    private function sendTwoFactorEnabledNotification(User $user): void
    {
        Mail::send('emails.2fa-enabled', ['user' => $user], function ($message) use ($user) {
            $message->to($user->email)->subject('Two-Factor Authentication Enabled');
        });
    }

    /**
     * Send email notification when 2FA is disabled.
     */
    private function sendTwoFactorDisabledNotification(User $user): void
    {
        Mail::send('emails.2fa-disabled', ['user' => $user], function ($message) use ($user) {
            $message->to($user->email)->subject('Two-Factor Authentication Disabled');
        });
    }

    /**
     * Send warning when recovery codes are running low.
     */
    private function sendLowRecoveryCodesWarning(User $user): void
    {
        Mail::send('emails.low-recovery-codes', ['user' => $user], function ($message) use ($user) {
            $message->to($user->email)->subject('Low Recovery Codes Warning');
        });
    }

    /**
     * Check if user's session is 2FA verified.
     */
    public function isSessionVerified(User $user): bool
    {
        return session('2fa_verified') === $user->id;
    }

    /**
     * Clear 2FA verification from session.
     */
    public function clearSessionVerification(): void
    {
        session()->forget('2fa_verified');
    }

    /**
     * Get user's recovery codes (decrypted).
     */
    public function getRecoveryCodes(User $user): array
    {
        if (! $user->two_factor_recovery_codes) {
            return [];
        }

        return json_decode($user->two_factor_recovery_codes, true);
    }

    /**
     * Check if 2FA is required for user's role.
     */
    public function isRequiredForUser(User $user): bool
    {
        $requiredRoles = config('auth.2fa.required_roles', ['admin', 'manager']);

        return $user->hasAnyRole($requiredRoles);
    }

    /**
     * Get 2FA statistics for admin dashboard.
     */
    public function getStatistics(): array
    {
        $totalUsers = User::count();
        $enabledUsers = User::where('two_factor_enabled', true)->count();
        $requiredUsers = User::whereHas('roles', function ($query) {
            $requiredRoles = config('auth.2fa.required_roles', ['admin', 'manager']);
            $query->whereIn('name', $requiredRoles);
        })->count();

        return [
            'total_users' => $totalUsers,
            'enabled_users' => $enabledUsers,
            'required_users' => $requiredUsers,
            'compliance_rate' => $requiredUsers > 0 ? round(($enabledUsers / $requiredUsers) * 100, 2) : 100,
            'adoption_rate' => $totalUsers > 0 ? round(($enabledUsers / $totalUsers) * 100, 2) : 0,
        ];
    }
}
