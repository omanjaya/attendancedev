<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotificationPreferences extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'new_device_login_email',
        'new_device_login_browser',
        'failed_login_attempts_email',
        'failed_login_attempts_browser',
        'suspicious_activity_email',
        'suspicious_activity_browser',
        'account_locked_email',
        'account_locked_browser',
        'password_changed_email',
        'password_changed_browser',
        'two_factor_changes_email',
        'two_factor_changes_browser',
        'device_trusted_email',
        'device_trusted_browser',
        'admin_access_email',
        'admin_access_browser',
        'attendance_reminders_email',
        'attendance_reminders_browser',
        'leave_status_email',
        'leave_status_browser',
        'payroll_notifications_email',
        'payroll_notifications_browser',
        'system_maintenance_email',
        'system_maintenance_browser',
        'quiet_hours',
        'digest_frequency',
    ];

    protected $casts = [
        'new_device_login_email' => 'boolean',
        'new_device_login_browser' => 'boolean',
        'failed_login_attempts_email' => 'boolean',
        'failed_login_attempts_browser' => 'boolean',
        'suspicious_activity_email' => 'boolean',
        'suspicious_activity_browser' => 'boolean',
        'account_locked_email' => 'boolean',
        'account_locked_browser' => 'boolean',
        'password_changed_email' => 'boolean',
        'password_changed_browser' => 'boolean',
        'two_factor_changes_email' => 'boolean',
        'two_factor_changes_browser' => 'boolean',
        'device_trusted_email' => 'boolean',
        'device_trusted_browser' => 'boolean',
        'admin_access_email' => 'boolean',
        'admin_access_browser' => 'boolean',
        'attendance_reminders_email' => 'boolean',
        'attendance_reminders_browser' => 'boolean',
        'leave_status_email' => 'boolean',
        'leave_status_browser' => 'boolean',
        'payroll_notifications_email' => 'boolean',
        'payroll_notifications_browser' => 'boolean',
        'system_maintenance_email' => 'boolean',
        'system_maintenance_browser' => 'boolean',
        'quiet_hours' => 'array',
        'digest_frequency' => 'array',
    ];

    /**
     * Get the user that owns the notification preferences.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if user wants email notifications for a specific type.
     */
    public function wantsEmailNotification(string $type): bool
    {
        $field = $type.'_email';

        return $this->$field ?? false;
    }

    /**
     * Check if user wants browser notifications for a specific type.
     */
    public function wantsBrowserNotification(string $type): bool
    {
        $field = $type.'_browser';

        return $this->$field ?? false;
    }

    /**
     * Check if current time is within quiet hours.
     */
    public function isInQuietHours(): bool
    {
        if (! $this->quiet_hours) {
            return false;
        }

        $now = now();
        $timezone = $this->quiet_hours['timezone'] ?? config('app.timezone');
        $currentTime = $now->setTimezone($timezone);

        $startTime = $currentTime->copy()->setTimeFromTimeString($this->quiet_hours['start']);
        $endTime = $currentTime->copy()->setTimeFromTimeString($this->quiet_hours['end']);

        // Handle overnight quiet hours (e.g., 22:00 to 08:00)
        if ($startTime->greaterThan($endTime)) {
            return $currentTime->greaterThanOrEqualTo($startTime) ||
              $currentTime->lessThanOrEqualTo($endTime);
        }

        return $currentTime->between($startTime, $endTime);
    }

    /**
     * Get digest frequency for a notification category.
     */
    public function getDigestFrequency(string $category): string
    {
        return $this->digest_frequency[$category] ?? 'immediate';
    }

    /**
     * Get default notification preferences.
     */
    public static function getDefaults(): array
    {
        return [
            'new_device_login_email' => true,
            'new_device_login_browser' => true,
            'failed_login_attempts_email' => true,
            'failed_login_attempts_browser' => true,
            'suspicious_activity_email' => true,
            'suspicious_activity_browser' => true,
            'account_locked_email' => true,
            'account_locked_browser' => true,
            'password_changed_email' => true,
            'password_changed_browser' => true,
            'two_factor_changes_email' => true,
            'two_factor_changes_browser' => true,
            'device_trusted_email' => true,
            'device_trusted_browser' => false,
            'admin_access_email' => true,
            'admin_access_browser' => true,
            'attendance_reminders_email' => true,
            'attendance_reminders_browser' => true,
            'leave_status_email' => true,
            'leave_status_browser' => true,
            'payroll_notifications_email' => true,
            'payroll_notifications_browser' => false,
            'system_maintenance_email' => false,
            'system_maintenance_browser' => true,
            'quiet_hours' => null,
            'digest_frequency' => [
                'security' => 'immediate',
                'system' => 'daily',
                'attendance' => 'daily',
            ],
        ];
    }

    /**
     * Get security notification types.
     */
    public static function getSecurityNotificationTypes(): array
    {
        return [
            'new_device_login',
            'failed_login_attempts',
            'suspicious_activity',
            'account_locked',
            'password_changed',
            'two_factor_changes',
            'device_trusted',
            'admin_access',
        ];
    }

    /**
     * Get system notification types.
     */
    public static function getSystemNotificationTypes(): array
    {
        return ['attendance_reminders', 'leave_status', 'payroll_notifications', 'system_maintenance'];
    }
}
