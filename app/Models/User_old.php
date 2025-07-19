<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
// use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'is_active',
        'last_login_at',
        'last_login_ip',
        'password_changed_at',
        'failed_login_attempts',
        'locked_until',
        'security_preferences',
        'force_password_change',
        'account_locked',
        'two_factor_enabled',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'locked_until' => 'datetime',
            'two_factor_enabled' => 'boolean',
            'force_password_change' => 'boolean',
            'account_locked' => 'boolean',
            'failed_login_attempts' => 'integer',
            'security_preferences' => 'json',
        ];
    }

    /**
     * Get the employee associated with the user.
     */
    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    /**
     * Get the devices associated with the user.
     */
    public function devices()
    {
        return $this->hasMany(UserDevice::class);
    }

    /**
     * Get the notification preferences for the user.
     */
    public function notificationPreferences()
    {
        return $this->hasOne(UserNotificationPreferences::class);
    }

    /**
     * Two-Factor Authentication Methods
     */

    /**
     * Check if user has 2FA enabled.
     */
    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_enabled && ! empty($this->two_factor_secret);
    }

    /**
     * Get decrypted 2FA secret.
     */
    public function getTwoFactorSecret(): ?string
    {
        if (empty($this->two_factor_secret)) {
            return null;
        }

        try {
            return decrypt($this->two_factor_secret);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Set encrypted 2FA secret.
     */
    public function setTwoFactorSecret(string $secret): void
    {
        $this->two_factor_secret = encrypt($secret);
    }

    /**
     * Get recovery codes.
     */
    public function getRecoveryCodes(): array
    {
        if (empty($this->two_factor_recovery_codes)) {
            return [];
        }

        try {
            return json_decode(decrypt($this->two_factor_recovery_codes), true) ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Set recovery codes.
     */
    public function setRecoveryCodes(array $codes): void
    {
        $this->two_factor_recovery_codes = encrypt(json_encode($codes));
    }

    /**
     * Use a recovery code.
     */
    public function useRecoveryCode(string $code): bool
    {
        $codes = $this->getRecoveryCodes();
        $index = array_search($code, $codes);

        if ($index !== false) {
            unset($codes[$index]);
            $this->setRecoveryCodes(array_values($codes));
            $this->save();

            return true;
        }

        return false;
    }

    /**
     * Security Methods
     */

    /**
     * Check if user account is locked.
     */
    public function isLocked(): bool
    {
        if ($this->account_locked) {
            return true;
        }

        if ($this->locked_until && $this->locked_until->isFuture()) {
            return true;
        }

        return false;
    }

    /**
     * Lock user account.
     */
    public function lockAccount(?Carbon $until = null): void
    {
        $this->locked_until = $until;
        $this->account_locked = $until === null;
        $this->save();
    }

    /**
     * Unlock user account.
     */
    public function unlockAccount(): void
    {
        $this->locked_until = null;
        $this->account_locked = false;
        $this->failed_login_attempts = 0;
        $this->save();
    }

    /**
     * Increment failed login attempts.
     */
    public function incrementFailedLogins(): void
    {
        $this->failed_login_attempts++;

        // Auto-lock after configured attempts
        $maxAttempts = config('security.rate_limiting.login.max_attempts', 5);
        if ($this->failed_login_attempts >= $maxAttempts) {
            $lockoutMinutes = config('security.rate_limiting.login.lockout_minutes', 60);
            $this->lockAccount(now()->addMinutes($lockoutMinutes));
        }

        $this->save();
    }

    /**
     * Reset failed login attempts.
     */
    public function resetFailedLogins(): void
    {
        $this->failed_login_attempts = 0;
        $this->save();
    }

    /**
     * Update last login information.
     */
    public function updateLastLogin(?string $ipAddress = null): void
    {
        $this->last_login_at = now();
        $this->last_login_ip = $ipAddress;
        $this->resetFailedLogins();
        $this->save();
    }

    /**
     * Check if password needs to be changed.
     */
    public function needsPasswordChange(): bool
    {
        if ($this->force_password_change) {
            return true;
        }

        $expiryDays = config('security.password.expiry_days', 0);
        if ($expiryDays > 0 && $this->password_changed_at) {
            return $this->password_changed_at->addDays($expiryDays)->isPast();
        }

        return false;
    }

    /**
     * Mark password as changed.
     */
    public function markPasswordChanged(): void
    {
        $this->password_changed_at = now();
        $this->force_password_change = false;
        $this->save();
    }

    /**
     * Force password change on next login.
     */
    public function forcePasswordChange(): void
    {
        $this->force_password_change = true;
        $this->save();
    }

    /**
     * Get security preferences.
     */
    public function getSecurityPreference(string $key, $default = null)
    {
        $preferences = $this->security_preferences ?? [];

        return $preferences[$key] ?? $default;
    }

    /**
     * Set security preference.
     */
    public function setSecurityPreference(string $key, $value): void
    {
        $preferences = $this->security_preferences ?? [];
        $preferences[$key] = $value;
        $this->security_preferences = $preferences;
        $this->save();
    }

    /**
     * Check if user has specific security permission.
     */
    public function hasSecurityPermission(string $permission): bool
    {
        return $this->can($permission) || $this->hasRole('admin');
    }

    /**
     * Get user's security level (1-5, 5 being highest).
     */
    public function getSecurityLevel(): int
    {
        $level = 1;

        // Check role-based security level
        if ($this->hasRole('admin')) {
            $level = 5;
        } elseif ($this->hasRole('manager')) {
            $level = 4;
        } elseif ($this->hasRole('teacher')) {
            $level = 3;
        } elseif ($this->hasRole('staff')) {
            $level = 2;
        }

        // Increase level if 2FA is enabled
        if ($this->hasTwoFactorEnabled()) {
            $level = min(5, $level + 1);
        }

        return $level;
    }

    /**
     * Check if user requires 2FA based on role or security settings.
     */
    public function requires2FA(): bool
    {
        // Admin and managers always require 2FA
        if ($this->hasRole(['admin', 'manager'])) {
            return true;
        }

        // Check if required by security preferences
        return $this->getSecurityPreference('require_2fa', false);
    }

    /**
     * Get formatted last login information.
     */
    public function getLastLoginInfo(): array
    {
        return [
            'date' => $this->last_login_at?->format('Y-m-d H:i:s'),
            'ip' => $this->last_login_ip,
            'human_date' => $this->last_login_at?->diffForHumans(),
        ];
    }

    /**
     * Check if this is a new device/location for the user.
     */
    public function isNewDevice(string $deviceFingerprint): bool
    {
        $knownDevices = Cache::get("user_devices_{$this->id}", []);

        return ! in_array($deviceFingerprint, $knownDevices);
    }

    /**
     * Remember device for future logins.
     */
    public function rememberDevice(string $deviceFingerprint): void
    {
        $knownDevices = Cache::get("user_devices_{$this->id}", []);

        if (! in_array($deviceFingerprint, $knownDevices)) {
            $knownDevices[] = $deviceFingerprint;

            // Keep only last 5 devices
            if (count($knownDevices) > 5) {
                $knownDevices = array_slice($knownDevices, -5);
            }

            Cache::put("user_devices_{$this->id}", $knownDevices, 86400 * 30); // 30 days
        }
    }

    /**
     * Scopes
     */

    /**
     * Scope for active users only.
     */
    public function scopeActive($query)
    {
        return $query
            ->where('is_active', true)
            ->where('account_locked', false)
            ->where(function ($q) {
                $q->whereNull('locked_until')->orWhere('locked_until', '<=', now());
            });
    }

    /**
     * Scope for users with 2FA enabled.
     */
    public function scopeWithTwoFactor($query)
    {
        return $query->where('two_factor_enabled', true);
    }

    /**
     * Scope for locked users.
     */
    public function scopeLocked($query)
    {
        return $query->where(function ($q) {
            $q->where('account_locked', true)->orWhere('locked_until', '>', now());
        });
    }

    /**
     * Scope for users requiring password change.
     */
    public function scopeNeedsPasswordChange($query)
    {
        return $query->where('force_password_change', true);
    }
}
