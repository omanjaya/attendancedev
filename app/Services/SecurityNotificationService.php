<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserDevice;
use App\Models\UserNotificationPreferences;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SecurityNotificationService
{
    /**
     * Send new device login notification.
     */
    public function notifyNewDeviceLogin(User $user, UserDevice $device, Request $request): void
    {
        $this->sendSecurityNotification($user, 'new_device_login', [
            'device' => $device,
            'ip_address' => $request->ip(),
            'location' => $this->getLocationFromIP($request->ip()),
            'user_agent' => $request->userAgent(),
            'timestamp' => now(),
        ]);
    }

    /**
     * Send failed login attempts notification.
     */
    public function notifyFailedLoginAttempts(User $user, int $attemptCount, Request $request): void
    {
        // Only notify after threshold is reached and not too frequently
        if ($attemptCount >= 3 && ! $this->wasRecentlyNotified($user, 'failed_login_attempts', 300)) {
            $this->sendSecurityNotification($user, 'failed_login_attempts', [
                'attempt_count' => $attemptCount,
                'ip_address' => $request->ip(),
                'location' => $this->getLocationFromIP($request->ip()),
                'timestamp' => now(),
            ]);
        }
    }

    /**
     * Send suspicious activity notification.
     */
    public function notifySuspiciousActivity(User $user, string $activityType, array $details): void
    {
        $this->sendSecurityNotification($user, 'suspicious_activity', [
            'activity_type' => $activityType,
            'details' => $details,
            'risk_level' => $details['risk_level'] ?? 'medium',
            'timestamp' => now(),
        ]);
    }

    /**
     * Send account locked notification.
     */
    public function notifyAccountLocked(User $user, string $reason, $lockDuration = null): void
    {
        $this->sendSecurityNotification($user, 'account_locked', [
            'reason' => $reason,
            'lock_duration' => $lockDuration,
            'unlock_time' => $lockDuration ? now()->addMinutes($lockDuration) : null,
            'timestamp' => now(),
        ]);
    }

    /**
     * Send password changed notification.
     */
    public function notifyPasswordChanged(User $user, Request $request): void
    {
        $this->sendSecurityNotification($user, 'password_changed', [
            'ip_address' => $request->ip(),
            'location' => $this->getLocationFromIP($request->ip()),
            'user_agent' => $request->userAgent(),
            'timestamp' => now(),
        ]);
    }

    /**
     * Send two-factor authentication changes notification.
     */
    public function notifyTwoFactorChanges(User $user, string $changeType, Request $request): void
    {
        $this->sendSecurityNotification($user, 'two_factor_changes', [
            'change_type' => $changeType, // enabled, disabled, recovery_codes_regenerated
            'ip_address' => $request->ip(),
            'location' => $this->getLocationFromIP($request->ip()),
            'user_agent' => $request->userAgent(),
            'timestamp' => now(),
        ]);
    }

    /**
     * Send device trusted notification.
     */
    public function notifyDeviceTrusted(User $user, UserDevice $device, Request $request): void
    {
        $this->sendSecurityNotification($user, 'device_trusted', [
            'device' => $device,
            'ip_address' => $request->ip(),
            'location' => $this->getLocationFromIP($request->ip()),
            'timestamp' => now(),
        ]);
    }

    /**
     * Send admin access notification.
     */
    public function notifyAdminAccess(
        User $user,
        string $action,
        array $details,
        Request $request,
    ): void {
        $this->sendSecurityNotification($user, 'admin_access', [
            'action' => $action,
            'details' => $details,
            'ip_address' => $request->ip(),
            'location' => $this->getLocationFromIP($request->ip()),
            'timestamp' => now(),
        ]);
    }

    /**
     * Send attendance reminder notification.
     */
    public function notifyAttendanceReminder(User $user, string $reminderType): void
    {
        $this->sendSystemNotification($user, 'attendance_reminders', [
            'reminder_type' => $reminderType, // check_in, check_out, missing_attendance
            'timestamp' => now(),
        ]);
    }

    /**
     * Send leave status notification.
     */
    public function notifyLeaveStatus(User $user, string $status, array $leaveDetails): void
    {
        $this->sendSystemNotification($user, 'leave_status', [
            'status' => $status, // approved, rejected, pending
            'leave_details' => $leaveDetails,
            'timestamp' => now(),
        ]);
    }

    /**
     * Send payroll notification.
     */
    public function notifyPayroll(User $user, string $notificationType, array $details): void
    {
        $this->sendSystemNotification($user, 'payroll_notifications', [
            'notification_type' => $notificationType,
            'details' => $details,
            'timestamp' => now(),
        ]);
    }

    /**
     * Send system maintenance notification.
     */
    public function notifySystemMaintenance(User $user, array $maintenanceDetails): void
    {
        $this->sendSystemNotification($user, 'system_maintenance', [
            'maintenance_details' => $maintenanceDetails,
            'timestamp' => now(),
        ]);
    }

    /**
     * Send security notification with user preferences check.
     */
    private function sendSecurityNotification(User $user, string $type, array $data): void
    {
        $preferences = $this->getUserPreferences($user);

        // Check if user wants this type of notification
        if (
            ! $preferences->wantsEmailNotification($type) &&
            ! $preferences->wantsBrowserNotification($type)
        ) {
            return;
        }

        // Check quiet hours for non-critical notifications
        if (! $this->isCriticalSecurityEvent($type) && $preferences->isInQuietHours()) {
            $this->queueForDigest($user, $type, $data);

            return;
        }

        $this->sendNotification($user, $type, $data, 'security', $preferences);
    }

    /**
     * Send system notification with user preferences check.
     */
    private function sendSystemNotification(User $user, string $type, array $data): void
    {
        $preferences = $this->getUserPreferences($user);

        // Check if user wants this type of notification
        if (
            ! $preferences->wantsEmailNotification($type) &&
            ! $preferences->wantsBrowserNotification($type)
        ) {
            return;
        }

        // Check digest frequency
        $digestFrequency = $preferences->getDigestFrequency('system');
        if ($digestFrequency !== 'immediate') {
            $this->queueForDigest($user, $type, $data);

            return;
        }

        $this->sendNotification($user, $type, $data, 'system', $preferences);
    }

    /**
     * Send the actual notification.
     */
    private function sendNotification(
        User $user,
        string $type,
        array $data,
        string $category,
        UserNotificationPreferences $preferences,
    ): void {
        try {
            $channels = [];

            if ($preferences->wantsEmailNotification($type)) {
                $channels[] = 'mail';
            }

            if ($preferences->wantsBrowserNotification($type)) {
                $channels[] = 'database';
            }

            if (empty($channels)) {
                return;
            }

            // Create notification class dynamically
            $notificationClass = $this->getNotificationClass($type, $category);

            if (class_exists($notificationClass)) {
                $notification = new $notificationClass($data);
                $user->notify($notification);

                // Mark as recently notified to prevent spam
                $this->markAsRecentlyNotified($user, $type);

                Log::info('Security notification sent', [
                    'user_id' => $user->id,
                    'type' => $type,
                    'category' => $category,
                    'channels' => $channels,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send security notification', [
                'user_id' => $user->id,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get user notification preferences.
     */
    private function getUserPreferences(User $user): UserNotificationPreferences
    {
        return $user->notificationPreferences ?? $this->createDefaultPreferences($user);
    }

    /**
     * Create default notification preferences for user.
     */
    private function createDefaultPreferences(User $user): UserNotificationPreferences
    {
        return UserNotificationPreferences::create(
            array_merge(['user_id' => $user->id], UserNotificationPreferences::getDefaults()),
        );
    }

    /**
     * Check if this is a critical security event that ignores quiet hours.
     */
    private function isCriticalSecurityEvent(string $type): bool
    {
        return in_array($type, ['account_locked', 'suspicious_activity', 'admin_access']);
    }

    /**
     * Check if user was recently notified about this type.
     */
    private function wasRecentlyNotified(User $user, string $type, int $seconds = 3600): bool
    {
        $cacheKey = "notification_sent_{$user->id}_{$type}";

        return Cache::has($cacheKey);
    }

    /**
     * Mark user as recently notified.
     */
    private function markAsRecentlyNotified(User $user, string $type, int $seconds = 3600): void
    {
        $cacheKey = "notification_sent_{$user->id}_{$type}";
        Cache::put($cacheKey, true, $seconds);
    }

    /**
     * Queue notification for digest delivery.
     */
    private function queueForDigest(User $user, string $type, array $data): void
    {
        $cacheKey = "digest_queue_{$user->id}";
        $queue = Cache::get($cacheKey, []);

        $queue[] = [
            'type' => $type,
            'data' => $data,
            'timestamp' => now()->toISOString(),
        ];

        Cache::put($cacheKey, $queue, 86400); // 24 hours
    }

    /**
     * Get notification class name for type and category.
     */
    private function getNotificationClass(string $type, string $category): string
    {
        $className = str_replace('_', '', ucwords($type, '_'));

        return "App\\Notifications\\{$category}\\{$className}Notification";
    }

    /**
     * Get location from IP address.
     */
    private function getLocationFromIP(string $ip): ?string
    {
        // This is a placeholder. In production, you might use a service like:
        // - MaxMind GeoIP
        // - IP-API
        // - ipstack

        // For local development IPs, return a generic location
        if (in_array($ip, ['127.0.0.1', '::1', 'localhost'])) {
            return 'Local Development';
        }

        return null; // Return null if no location service is configured
    }

    /**
     * Process digest notifications for all users.
     */
    public function processDigestNotifications(): void
    {
        $users = User::whereHas('notificationPreferences')->get();

        foreach ($users as $user) {
            $this->processUserDigest($user);
        }
    }

    /**
     * Process digest notifications for a specific user.
     */
    private function processUserDigest(User $user): void
    {
        $cacheKey = "digest_queue_{$user->id}";
        $queue = Cache::get($cacheKey, []);

        if (empty($queue)) {
            return;
        }

        $preferences = $this->getUserPreferences($user);

        // Group notifications by category
        $groupedNotifications = [];
        foreach ($queue as $notification) {
            $category = $this->getCategoryForType($notification['type']);
            $groupedNotifications[$category][] = $notification;
        }

        // Send digest for each category based on user preferences
        foreach ($groupedNotifications as $category => $notifications) {
            $frequency = $preferences->getDigestFrequency($category);

            if ($this->shouldSendDigest($user, $category, $frequency)) {
                $this->sendDigestNotification($user, $category, $notifications);
            }
        }

        // Clear processed notifications
        Cache::forget($cacheKey);
    }

    /**
     * Get category for notification type.
     */
    private function getCategoryForType(string $type): string
    {
        if (in_array($type, UserNotificationPreferences::getSecurityNotificationTypes())) {
            return 'security';
        }

        return 'system';
    }

    /**
     * Check if digest should be sent based on frequency.
     */
    private function shouldSendDigest(User $user, string $category, string $frequency): bool
    {
        if ($frequency === 'immediate') {
            return true;
        }

        $cacheKey = "digest_sent_{$user->id}_{$category}_{$frequency}";

        if (Cache::has($cacheKey)) {
            return false;
        }

        $ttl = match ($frequency) {
            'daily' => 86400,
            'weekly' => 604800,
            default => 3600,
        };

        Cache::put($cacheKey, true, $ttl);

        return true;
    }

    /**
     * Send digest notification.
     */
    private function sendDigestNotification(User $user, string $category, array $notifications): void
    {
        try {
            $notificationClass = 'App\\Notifications\\Digest'.ucfirst($category).'Notification';

            if (class_exists($notificationClass)) {
                $notification = new $notificationClass($notifications);
                $user->notify($notification);

                Log::info('Digest notification sent', [
                    'user_id' => $user->id,
                    'category' => $category,
                    'notification_count' => count($notifications),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send digest notification', [
                'user_id' => $user->id,
                'category' => $category,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
