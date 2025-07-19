<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Services\UserSecurityService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
// use Illuminate\Database\Eloquent\SoftDeletes;
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
            'face_descriptor' => 'array',
            'face_registered_at' => 'datetime',
        ];
    }

    /**
     * Relationships
     */

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
     * Security Methods (delegated to UserSecurityService)
     *
     * These methods provide a clean API while delegating the actual
     * implementation to the dedicated security service.
     */

    /**
     * Get the security service instance.
     */
    protected function securityService(): UserSecurityService
    {
        return app(UserSecurityService::class);
    }

    /**
     * Two-Factor Authentication Methods
     */
    public function hasTwoFactorEnabled(): bool
    {
        return $this->securityService()->hasTwoFactorEnabled($this);
    }

    public function getTwoFactorSecret(): ?string
    {
        return $this->securityService()->getTwoFactorSecret($this);
    }

    public function setTwoFactorSecret(string $secret): void
    {
        $this->securityService()->setTwoFactorSecret($this, $secret);
    }

    public function getRecoveryCodes(): array
    {
        return $this->securityService()->getRecoveryCodes($this);
    }

    public function setRecoveryCodes(array $codes): void
    {
        $this->securityService()->setRecoveryCodes($this, $codes);
    }

    public function useRecoveryCode(string $code): bool
    {
        return $this->securityService()->useRecoveryCode($this, $code);
    }

    public function generateRecoveryCodes(): array
    {
        return $this->securityService()->generateRecoveryCodes($this);
    }

    /**
     * Account Security Methods
     */
    public function isLocked(): bool
    {
        return $this->securityService()->isAccountLocked($this);
    }

    public function lockAccount(?\Carbon\Carbon $until = null, string $reason = 'Manual lock'): void
    {
        $this->securityService()->lockAccount($this, $until, $reason);
    }

    public function unlockAccount(string $reason = 'Manual unlock'): void
    {
        $this->securityService()->unlockAccount($this, $reason);
    }

    public function incrementFailedLogins(?string $ipAddress = null): void
    {
        $this->securityService()->incrementFailedLogins($this, $ipAddress);
    }

    public function resetFailedLogins(): void
    {
        $this->securityService()->resetFailedLogins($this);
    }

    public function updateLastLogin(?string $ipAddress = null): void
    {
        $this->securityService()->updateLastLogin($this, $ipAddress);
    }

    /**
     * Password Security Methods
     */
    public function needsPasswordChange(): bool
    {
        return $this->securityService()->needsPasswordChange($this);
    }

    public function markPasswordChanged(): void
    {
        $this->securityService()->markPasswordChanged($this);
    }

    public function forcePasswordChange(string $reason = 'Administrative action'): void
    {
        $this->securityService()->forcePasswordChange($this, $reason);
    }

    /**
     * Security Preferences
     */
    public function getSecurityPreference(string $key, $default = null)
    {
        return $this->securityService()->getSecurityPreference($this, $key, $default);
    }

    public function setSecurityPreference(string $key, $value): void
    {
        $this->securityService()->setSecurityPreference($this, $key, $value);
    }

    public function hasSecurityPermission(string $permission): bool
    {
        return $this->securityService()->hasSecurityPermission($this, $permission);
    }

    public function getSecurityLevel(): int
    {
        return $this->securityService()->getSecurityLevel($this);
    }

    public function requires2FA(): bool
    {
        return $this->securityService()->requires2FA($this);
    }

    /**
     * Device Management
     */
    public function isNewDevice(string $deviceFingerprint): bool
    {
        return $this->securityService()->isNewDevice($this, $deviceFingerprint);
    }

    public function rememberDevice(string $deviceFingerprint): void
    {
        $this->securityService()->rememberDevice($this, $deviceFingerprint);
    }

    public function forgetAllDevices(): void
    {
        $this->securityService()->forgetAllDevices($this);
    }

    public function getLastLoginInfo(): array
    {
        return $this->securityService()->getLastLoginInfo($this);
    }

    /**
     * Security Analytics
     */
    public function getSecurityScore(): int
    {
        return $this->securityService()->getSecurityScore($this);
    }

    public function getSecurityRecommendations(): array
    {
        return $this->securityService()->getSecurityRecommendations($this);
    }

    /**
     * Model Scopes
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

    /**
     * High-level convenience methods
     */

    /**
     * Get comprehensive security status.
     */
    public function getSecurityStatus(): array
    {
        return [
            'is_locked' => $this->isLocked(),
            'has_2fa' => $this->hasTwoFactorEnabled(),
            'requires_2fa' => $this->requires2FA(),
            'needs_password_change' => $this->needsPasswordChange(),
            'security_level' => $this->getSecurityLevel(),
            'security_score' => $this->getSecurityScore(),
            'failed_attempts' => $this->failed_login_attempts,
            'last_login' => $this->getLastLoginInfo(),
            'recommendations' => $this->getSecurityRecommendations(),
        ];
    }

    /**
     * Check if user can perform administrative actions.
     */
    public function isAdministrator(): bool
    {
        return $this->hasRole(['admin', 'superadmin']);
    }

    /**
     * Check if user can manage other users.
     */
    public function canManageUsers(): bool
    {
        return $this->hasRole(['admin', 'superadmin', 'manager']);
    }

    /**
     * Get user's display name with role context.
     */
    public function getDisplayNameWithRole(): string
    {
        $role = $this->roles->first()?->name ?? 'User';

        return "{$this->name} ({$role})";
    }
}
