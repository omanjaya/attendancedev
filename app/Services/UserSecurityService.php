<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * User Security Service
 *
 * Handles all security-related operations for users including:
 * - Two-Factor Authentication management
 * - Account locking/unlocking
 * - Failed login tracking
 * - Device management
 * - Security preferences
 * - Password security
 */
class UserSecurityService
{
    /**
     * Two-Factor Authentication Methods
     */

    /**
     * Check if user has 2FA enabled.
     */
    public function hasTwoFactorEnabled(User $user): bool
    {
        return $user->two_factor_enabled && ! empty($user->two_factor_secret);
    }

    /**
     * Get decrypted 2FA secret.
     */
    public function getTwoFactorSecret(User $user): ?string
    {
        if (empty($user->two_factor_secret)) {
            return null;
        }

        try {
            return decrypt($user->two_factor_secret);
        } catch (\Exception $e) {
            \Log::warning('Failed to decrypt 2FA secret for user', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Set encrypted 2FA secret.
     */
    public function setTwoFactorSecret(User $user, string $secret): void
    {
        $user->update([
            'two_factor_secret' => encrypt($secret),
            'two_factor_enabled' => true,
        ]);
    }

    /**
     * Disable 2FA for user.
     */
    public function disableTwoFactor(User $user): void
    {
        $user->update([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
        ]);

        // Log security event
        \Log::info('2FA disabled for user', ['user_id' => $user->id]);
    }

    /**
     * Get recovery codes.
     */
    public function getRecoveryCodes(User $user): array
    {
        if (empty($user->two_factor_recovery_codes)) {
            return [];
        }

        try {
            return json_decode(decrypt($user->two_factor_recovery_codes), true) ?? [];
        } catch (\Exception $e) {
            \Log::warning('Failed to decrypt recovery codes for user', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Generate and set new recovery codes.
     */
    public function generateRecoveryCodes(User $user): array
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8));
        }

        $this->setRecoveryCodes($user, $codes);

        // Log security event
        \Log::info('Recovery codes regenerated for user', ['user_id' => $user->id]);

        return $codes;
    }

    /**
     * Set recovery codes.
     */
    public function setRecoveryCodes(User $user, array $codes): void
    {
        $user->update([
            'two_factor_recovery_codes' => encrypt(json_encode($codes)),
        ]);
    }

    /**
     * Use a recovery code.
     */
    public function useRecoveryCode(User $user, string $code): bool
    {
        $codes = $this->getRecoveryCodes($user);
        $index = array_search(strtoupper($code), array_map('strtoupper', $codes));

        if ($index !== false) {
            unset($codes[$index]);
            $this->setRecoveryCodes($user, array_values($codes));

            // Log security event
            \Log::info('Recovery code used', [
                'user_id' => $user->id,
                'remaining_codes' => count($codes) - 1,
            ]);

            return true;
        }

        return false;
    }

    /**
     * Account Security Methods
     */

    /**
     * Check if user account is locked.
     */
    public function isAccountLocked(User $user): bool
    {
        if ($user->account_locked) {
            return true;
        }

        if ($user->locked_until && $user->locked_until->isFuture()) {
            return true;
        }

        return false;
    }

    /**
     * Lock user account.
     */
    public function lockAccount(
        User $user,
        ?Carbon $until = null,
        string $reason = 'Manual lock',
    ): void {
        $user->update([
            'locked_until' => $until,
            'account_locked' => $until === null,
        ]);

        // Log security event
        \Log::warning('User account locked', [
            'user_id' => $user->id,
            'locked_until' => $until?->toDateTimeString(),
            'reason' => $reason,
        ]);
    }

    /**
     * Unlock user account.
     */
    public function unlockAccount(User $user, string $reason = 'Manual unlock'): void
    {
        $user->update([
            'locked_until' => null,
            'account_locked' => false,
            'failed_login_attempts' => 0,
        ]);

        // Log security event
        \Log::info('User account unlocked', [
            'user_id' => $user->id,
            'reason' => $reason,
        ]);
    }

    /**
     * Login Attempt Management
     */

    /**
     * Increment failed login attempts.
     */
    public function incrementFailedLogins(User $user, ?string $ipAddress = null): void
    {
        $attempts = $user->failed_login_attempts + 1;
        $user->update(['failed_login_attempts' => $attempts]);

        // Auto-lock after configured attempts
        $maxAttempts = config('security.rate_limiting.login.max_attempts', 5);
        if ($attempts >= $maxAttempts) {
            $lockoutMinutes = config('security.rate_limiting.login.lockout_minutes', 60);
            $this->lockAccount(
                $user,
                now()->addMinutes($lockoutMinutes),
                'Too many failed login attempts',
            );
        }

        // Log security event
        \Log::warning('Failed login attempt', [
            'user_id' => $user->id,
            'attempt_count' => $attempts,
            'ip_address' => $ipAddress,
            'locked' => $attempts >= $maxAttempts,
        ]);
    }

    /**
     * Reset failed login attempts.
     */
    public function resetFailedLogins(User $user): void
    {
        $user->update(['failed_login_attempts' => 0]);
    }

    /**
     * Update last login information.
     */
    public function updateLastLogin(User $user, ?string $ipAddress = null): void
    {
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $ipAddress,
            'failed_login_attempts' => 0,
        ]);

        // Log successful login
        \Log::info('User logged in successfully', [
            'user_id' => $user->id,
            'ip_address' => $ipAddress,
        ]);
    }

    /**
     * Password Security Methods
     */

    /**
     * Check if password needs to be changed.
     */
    public function needsPasswordChange(User $user): bool
    {
        if ($user->force_password_change) {
            return true;
        }

        $expiryDays = config('security.password.expiry_days', 0);
        if ($expiryDays > 0 && $user->password_changed_at) {
            return $user->password_changed_at->addDays($expiryDays)->isPast();
        }

        return false;
    }

    /**
     * Mark password as changed.
     */
    public function markPasswordChanged(User $user): void
    {
        $user->update([
            'password_changed_at' => now(),
            'force_password_change' => false,
        ]);

        // Log security event
        \Log::info('User password changed', ['user_id' => $user->id]);
    }

    /**
     * Force password change on next login.
     */
    public function forcePasswordChange(User $user, string $reason = 'Administrative action'): void
    {
        $user->update(['force_password_change' => true]);

        // Log security event
        \Log::warning('Password change forced for user', [
            'user_id' => $user->id,
            'reason' => $reason,
        ]);
    }

    /**
     * Validate password against security policies.
     */
    public function validatePassword(string $password): array
    {
        $errors = [];
        $config = config('security.password', []);

        // Check minimum length
        $minLength = $config['min_length'] ?? 8;
        if (strlen($password) < $minLength) {
            $errors[] = "Password must be at least {$minLength} characters long";
        }

        // Check uppercase requirement
        if (($config['require_uppercase'] ?? true) && ! preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }

        // Check lowercase requirement
        if (($config['require_lowercase'] ?? true) && ! preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }

        // Check number requirement
        if (($config['require_numbers'] ?? true) && ! preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }

        // Check special character requirement
        if (($config['require_special'] ?? true) && ! preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }

        return $errors;
    }

    /**
     * Security Preferences Management
     */

    /**
     * Get security preference.
     */
    public function getSecurityPreference(User $user, string $key, $default = null)
    {
        $preferences = $user->security_preferences ?? [];

        return $preferences[$key] ?? $default;
    }

    /**
     * Set security preference.
     */
    public function setSecurityPreference(User $user, string $key, $value): void
    {
        $preferences = $user->security_preferences ?? [];
        $preferences[$key] = $value;

        $user->update(['security_preferences' => $preferences]);

        // Log preference change
        \Log::info('Security preference updated', [
            'user_id' => $user->id,
            'preference' => $key,
            'value' => $value,
        ]);
    }

    /**
     * Check if user has specific security permission.
     */
    public function hasSecurityPermission(User $user, string $permission): bool
    {
        return $user->can($permission) || $user->hasRole('admin');
    }

    /**
     * Get user's security level (1-5, 5 being highest).
     */
    public function getSecurityLevel(User $user): int
    {
        $level = 1;

        // Check role-based security level
        if ($user->hasRole('admin')) {
            $level = 5;
        } elseif ($user->hasRole('manager')) {
            $level = 4;
        } elseif ($user->hasRole('teacher')) {
            $level = 3;
        } elseif ($user->hasRole('staff')) {
            $level = 2;
        }

        // Increase level if 2FA is enabled
        if ($this->hasTwoFactorEnabled($user)) {
            $level = min(5, $level + 1);
        }

        return $level;
    }

    /**
     * Check if user requires 2FA based on role or security settings.
     */
    public function requires2FA(User $user): bool
    {
        // Admin and managers always require 2FA
        if ($user->hasRole(['admin', 'manager'])) {
            return true;
        }

        // Check if required by security preferences
        return $this->getSecurityPreference($user, 'require_2fa', false);
    }

    /**
     * Device Management Methods
     */

    /**
     * Check if this is a new device/location for the user.
     */
    public function isNewDevice(User $user, string $deviceFingerprint): bool
    {
        $knownDevices = Cache::get("user_devices_{$user->id}", []);

        return ! in_array($deviceFingerprint, $knownDevices);
    }

    /**
     * Remember device for future logins.
     */
    public function rememberDevice(User $user, string $deviceFingerprint): void
    {
        $knownDevices = Cache::get("user_devices_{$user->id}", []);

        if (! in_array($deviceFingerprint, $knownDevices)) {
            $knownDevices[] = $deviceFingerprint;

            // Keep only last 5 devices
            if (count($knownDevices) > 5) {
                $knownDevices = array_slice($knownDevices, -5);
            }

            Cache::put("user_devices_{$user->id}", $knownDevices, 86400 * 30); // 30 days

            // Log device registration
            \Log::info('New device registered for user', [
                'user_id' => $user->id,
                'device_fingerprint' => substr($deviceFingerprint, 0, 8).'...',
                'total_devices' => count($knownDevices),
            ]);
        }
    }

    /**
     * Forget all devices for user.
     */
    public function forgetAllDevices(User $user): void
    {
        Cache::forget("user_devices_{$user->id}");

        // Log security event
        \Log::info('All devices forgotten for user', ['user_id' => $user->id]);
    }

    /**
     * Get formatted last login information.
     */
    public function getLastLoginInfo(User $user): array
    {
        return [
            'date' => $user->last_login_at?->format('Y-m-d H:i:s'),
            'ip' => $user->last_login_ip,
            'human_date' => $user->last_login_at?->diffForHumans(),
            'location' => $this->getLocationFromIP($user->last_login_ip),
        ];
    }

    /**
     * Security Analytics Methods
     */

    /**
     * Get user security score (0-100).
     */
    public function getSecurityScore(User $user): int
    {
        $score = 0;

        // Base score for active account
        if ($user->is_active && ! $this->isAccountLocked($user)) {
            $score += 20;
        }

        // 2FA enabled
        if ($this->hasTwoFactorEnabled($user)) {
            $score += 30;
        }

        // Recent password change
        if ($user->password_changed_at && $user->password_changed_at->greaterThan(now()->subDays(90))) {
            $score += 15;
        }

        // No recent failed logins
        if ($user->failed_login_attempts == 0) {
            $score += 10;
        }

        // Security level based on role
        $score += $this->getSecurityLevel($user) * 5;

        return min(100, $score);
    }

    /**
     * Get security recommendations for user.
     */
    public function getSecurityRecommendations(User $user): array
    {
        $recommendations = [];

        if (! $this->hasTwoFactorEnabled($user)) {
            $recommendations[] = [
                'type' => 'warning',
                'title' => 'Enable Two-Factor Authentication',
                'description' => 'Add an extra layer of security to your account',
                'action' => 'setup_2fa',
            ];
        }

        if ($this->needsPasswordChange($user)) {
            $recommendations[] = [
                'type' => 'error',
                'title' => 'Password Change Required',
                'description' => 'Your password needs to be updated',
                'action' => 'change_password',
            ];
        }

        if ($user->failed_login_attempts > 0) {
            $recommendations[] = [
                'type' => 'info',
                'title' => 'Recent Failed Login Attempts',
                'description' => "There have been {$user->failed_login_attempts} failed login attempts",
                'action' => 'review_activity',
            ];
        }

        return $recommendations;
    }

    /**
     * Private helper methods
     */

    /**
     * Get approximate location from IP address.
     */
    private function getLocationFromIP(?string $ip): ?string
    {
        if (! $ip || $ip === '127.0.0.1') {
            return 'Local';
        }

        // In a real implementation, you might use a GeoIP service
        // For now, just return the IP
        return $ip;
    }
}
