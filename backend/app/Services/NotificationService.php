<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use App\Notifications\AttendanceNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class NotificationService implements \App\Contracts\Services\NotificationServiceInterface
{
    /**
     * Send attendance notification to user and administrators
     */
    public function sendAttendanceNotification(Attendance $attendance, string $action): void
    {
        try {
            $employee = $attendance->employee;
            $user = $employee->user;

            // Prepare notification data
            $notificationData = [
                'type' => 'attendance',
                'action' => $action,
                'employee_name' => $employee->full_name,
                'employee_id' => $employee->employee_id,
                'timestamp' => $attendance->{$action === 'check-in' ? 'check_in_time' : 'check_out_time'},
                'status' => $attendance->status,
                'total_hours' => $attendance->total_hours,
                'confidence_score' => $attendance->{$action === 'check-in' ? 'check_in_confidence' : 'check_out_confidence'},
                'location_verified' => $attendance->location_verified,
                'message' => $this->generateNotificationMessage($action, $employee, $attendance),
            ];

            // Send to employee
            if ($user) {
                $user->notify(new AttendanceNotification($notificationData));
                $this->logNotification('employee', $user->id, $notificationData);
            }

            // Send to administrators
            $this->notifyAdministrators($notificationData);

            // Send real-time browser notification
            $this->sendBrowserNotification($notificationData);

            // Cache notification for recent activity
            $this->cacheRecentActivity($notificationData);

        } catch (\Exception $e) {
            Log::error('Failed to send attendance notification', [
                'attendance_id' => $attendance->id,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send face recognition notification
     */
    public function sendFaceRecognitionNotification(User $user, array $data): void
    {
        try {
            $notificationData = [
                'type' => 'face_recognition',
                'action' => $data['action'] ?? 'verification',
                'employee_name' => $user->employee->full_name ?? $user->name,
                'confidence_score' => $data['confidence'] ?? 0,
                'algorithm' => $data['algorithm'] ?? 'unknown',
                'processing_time' => $data['processing_time'] ?? 0,
                'timestamp' => now(),
                'message' => $this->generateFaceRecognitionMessage($data),
            ];

            // Send to user
            $user->notify(new AttendanceNotification($notificationData));

            // Log for security audit
            $this->logSecurityEvent('face_recognition', $user->id, $notificationData);

        } catch (\Exception $e) {
            Log::error('Failed to send face recognition notification', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send location verification notification
     */
    public function sendLocationNotification(User $user, array $locationData): void
    {
        try {
            $notificationData = [
                'type' => 'location',
                'action' => 'verification',
                'employee_name' => $user->employee->full_name ?? $user->name,
                'latitude' => $locationData['latitude'] ?? null,
                'longitude' => $locationData['longitude'] ?? null,
                'accuracy' => $locationData['accuracy'] ?? null,
                'verified' => $locationData['verified'] ?? false,
                'distance_from_office' => $locationData['distance'] ?? null,
                'timestamp' => now(),
                'message' => $this->generateLocationMessage($locationData),
            ];

            // Send notification
            $user->notify(new AttendanceNotification($notificationData));

            // Log location event
            $this->logLocationEvent($user->id, $notificationData);

        } catch (\Exception $e) {
            Log::error('Failed to send location notification', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send system notification
     */
    public function sendSystemNotification(string $type, string $message, array $recipients = []): void
    {
        try {
            $notificationData = [
                'type' => 'system',
                'action' => $type,
                'message' => $message,
                'timestamp' => now(),
                'priority' => $this->getNotificationPriority($type),
            ];

            // If no specific recipients, send to all admins
            if (empty($recipients)) {
                $recipients = User::whereHas('roles', function ($query) {
                    $query->whereIn('name', ['super_admin', 'admin']);
                })->get();
            }

            foreach ($recipients as $recipient) {
                if ($recipient instanceof User) {
                    $recipient->notify(new AttendanceNotification($notificationData));
                }
            }

            // Log system notification
            Log::info('System notification sent', $notificationData);

        } catch (\Exception $e) {
            Log::error('Failed to send system notification', [
                'type' => $type,
                'message' => $message,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send bulk notifications for attendance reminders
     */
    public function sendAttendanceReminders(): void
    {
        try {
            $currentTime = now();
            $workingHours = config('attendance.working_hours', ['start' => '08:00', 'end' => '17:00']);

            // Get employees who haven't checked in by 30 minutes after start time
            $lateThreshold = $currentTime->copy()->setTimeFromTimeString($workingHours['start'])->addMinutes(30);

            if ($currentTime->gte($lateThreshold)) {
                $absentEmployees = Employee::whereDoesntHave('attendances', function ($query) {
                    $query->whereDate('date', today());
                })->with('user')->get();

                foreach ($absentEmployees as $employee) {
                    if ($employee->user) {
                        $notificationData = [
                            'type' => 'reminder',
                            'action' => 'check_in_reminder',
                            'employee_name' => $employee->full_name,
                            'message' => 'Reminder: Please check in for today\'s attendance',
                            'timestamp' => now(),
                        ];

                        $employee->user->notify(new AttendanceNotification($notificationData));
                    }
                }

                Log::info('Attendance reminders sent', ['count' => $absentEmployees->count()]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to send attendance reminders', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Generate notification message for attendance
     */
    private function generateNotificationMessage(string $action, Employee $employee, Attendance $attendance): string
    {
        $time = $attendance->{$action === 'check-in' ? 'check_in_time' : 'check_out_time'};
        $formattedTime = $time ? $time->format('H:i') : 'Unknown';

        $messages = [
            'check-in' => [
                'present' => "✅ Check-in successful at {$formattedTime}. Have a productive day, {$employee->first_name}!",
                'late' => "⏰ Late check-in recorded at {$formattedTime}. Please try to arrive on time.",
                'early' => "🌅 Early check-in at {$formattedTime}. Great to see your enthusiasm!",
            ],
            'check-out' => [
                'present' => "🏁 Check-out successful at {$formattedTime}. Great work today, {$employee->first_name}!",
                'early_departure' => "⏰ Early departure recorded at {$formattedTime}. Hope everything is okay.",
                'overtime' => "💪 Overtime check-out at {$formattedTime}. Thank you for your dedication!",
            ],
        ];

        $status = $attendance->status;
        if ($action === 'check-out' && $attendance->total_hours > 8) {
            $status = 'overtime';
        }

        return $messages[$action][$status] ?? "Attendance {$action} recorded at {$formattedTime}.";
    }

    /**
     * Generate face recognition message
     */
    private function generateFaceRecognitionMessage(array $data): string
    {
        $confidence = $data['confidence'] ?? 0;
        $action = $data['action'] ?? 'verification';

        if ($confidence >= 0.9) {
            return "🎯 Face recognition successful with {$confidence}% confidence. Identity verified!";
        } elseif ($confidence >= 0.7) {
            return "✅ Face recognized with {$confidence}% confidence. Verification complete.";
        } else {
            return "⚠️ Face recognition failed. Confidence score: {$confidence}%. Please try again.";
        }
    }

    /**
     * Generate location message
     */
    private function generateLocationMessage(array $data): string
    {
        if ($data['verified'] ?? false) {
            $accuracy = $data['accuracy'] ?? 0;

            return "📍 Location verified successfully. You are within the permitted area (±{$accuracy}m accuracy).";
        } else {
            $distance = $data['distance'] ?? 'unknown';

            return "⚠️ Location verification failed. You appear to be {$distance} from the office.";
        }
    }

    /**
     * Notify administrators about attendance events
     */
    private function notifyAdministrators(array $notificationData): void
    {
        $admins = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['super_admin', 'admin', 'kepala_sekolah']);
        })->get();

        foreach ($admins as $admin) {
            $adminNotificationData = array_merge($notificationData, [
                'is_admin_notification' => true,
                'message' => "📊 {$notificationData['employee_name']} has {$notificationData['action']} at {$notificationData['timestamp']->format('H:i')}",
            ]);

            $admin->notify(new AttendanceNotification($adminNotificationData));
        }
    }

    /**
     * Send browser notification via WebPush
     */
    private function sendBrowserNotification(array $data): void
    {
        // Implementation for browser push notifications
        // This would integrate with your frontend notification system

        $browserNotification = [
            'title' => "Attendance {$data['action']} - {$data['employee_name']}",
            'body' => $data['message'],
            'icon' => '/images/notification-icon.png',
            'badge' => '/images/badge-icon.png',
            'tag' => "attendance-{$data['action']}",
            'data' => [
                'type' => $data['type'],
                'action' => $data['action'],
                'timestamp' => $data['timestamp']->toISOString(),
            ],
        ];

        // Store in cache for frontend to pickup
        Cache::put("browser_notification_{$data['employee_name']}", $browserNotification, now()->addMinutes(5));
    }

    /**
     * Cache recent activity for dashboard
     */
    private function cacheRecentActivity(array $data): void
    {
        $cacheKey = 'recent_attendance_activity';
        $recentActivity = Cache::get($cacheKey, []);

        // Add new activity to the beginning
        array_unshift($recentActivity, [
            'type' => $data['type'],
            'action' => $data['action'],
            'employee_name' => $data['employee_name'],
            'timestamp' => $data['timestamp'],
            'message' => $data['message'],
            'status' => $data['status'] ?? null,
        ]);

        // Keep only the last 50 activities
        $recentActivity = array_slice($recentActivity, 0, 50);

        // Cache for 24 hours
        Cache::put($cacheKey, $recentActivity, now()->addDay());
    }

    /**
     * Log notification for audit trail
     */
    private function logNotification(string $recipientType, int $recipientId, array $data): void
    {
        Log::info('Attendance notification sent', [
            'recipient_type' => $recipientType,
            'recipient_id' => $recipientId,
            'notification_type' => $data['type'],
            'action' => $data['action'],
            'employee_name' => $data['employee_name'],
            'timestamp' => $data['timestamp'],
        ]);
    }

    /**
     * Log security event
     */
    private function logSecurityEvent(string $eventType, int $userId, array $data): void
    {
        Log::channel('security')->info("Security event: {$eventType}", [
            'user_id' => $userId,
            'event_type' => $eventType,
            'data' => $data,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Log location event
     */
    private function logLocationEvent(int $userId, array $data): void
    {
        Log::info('Location verification event', [
            'user_id' => $userId,
            'verified' => $data['verified'],
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'accuracy' => $data['accuracy'],
            'distance_from_office' => $data['distance_from_office'],
        ]);
    }

    /**
     * Get notification priority based on type
     */
    private function getNotificationPriority(string $type): string
    {
        return match ($type) {
            'security_alert', 'system_error' => 'high',
            'late_arrival', 'early_departure' => 'medium',
            'check_in', 'check_out', 'system_info' => 'normal',
            default => 'normal'
        };
    }

    /**
     * Get recent activity from cache
     */
    public function getRecentActivity(int $limit = 20): array
    {
        return array_slice(
            Cache::get('recent_attendance_activity', []),
            0,
            $limit
        );
    }

    /**
     * Clear old notifications and cache
     */
    public function cleanup(): void
    {
        try {
            // Clear old browser notifications
            $cacheKeys = Cache::getRedis()->keys('*browser_notification_*');
            foreach ($cacheKeys as $key) {
                if (Cache::get($key) && now()->subMinutes(10)->gt(Cache::get($key)['timestamp'] ?? now())) {
                    Cache::forget($key);
                }
            }

            Log::info('Notification cleanup completed');

        } catch (\Exception $e) {
            Log::error('Notification cleanup failed', ['error' => $e->getMessage()]);
        }
    }

    // Interface implementation methods

    /**
     * Send notification to a user
     */
    public function send(User $user, string $type, array $data): bool
    {
        try {
            switch ($type) {
                case 'attendance.checked_in':
                case 'attendance.checked_out':
                    $attendance = $data['attendance'] ?? null;
                    if ($attendance) {
                        $this->sendAttendanceNotification($attendance, $data['action']);
                    }
                    break;
                
                case 'face_recognition.verified':
                case 'face_recognition.failed':
                    $this->sendFaceRecognitionNotification($user, $data);
                    break;
                
                case 'location.verified':
                case 'location.failed':
                    $this->sendLocationNotification($user, $data);
                    break;
                
                default:
                    // Generic notification
                    $notificationData = array_merge($data, [
                        'type' => $type,
                        'timestamp' => now(),
                    ]);
                    $user->notify(new AttendanceNotification($notificationData));
                    break;
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send notification', [
                'user_id' => $user->id,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send notification to multiple users
     */
    public function sendBulk(Collection $users, string $type, array $data): array
    {
        $results = [];
        
        foreach ($users as $user) {
            $results[$user->id] = $this->send($user, $type, $data);
        }
        
        return $results;
    }

    /**
     * Send notification via specific channel
     */
    public function sendVia(User $user, string $channel, string $type, array $data): bool
    {
        try {
            switch ($channel) {
                case 'database':
                    $user->notify(new AttendanceNotification($data));
                    break;
                
                case 'browser':
                    $this->sendBrowserNotification($data);
                    break;
                
                case 'email':
                    // Email notification implementation
                    break;
                
                default:
                    return $this->send($user, $type, $data);
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send notification via channel', [
                'user_id' => $user->id,
                'channel' => $channel,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Queue notification for later delivery
     */
    public function queue(User $user, string $type, array $data, ?Carbon $sendAt = null): bool
    {
        try {
            // Implementation would use Laravel queues
            // For now, we'll just log it
            Log::info('Notification queued', [
                'user_id' => $user->id,
                'type' => $type,
                'send_at' => $sendAt?->toDateTimeString(),
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to queue notification', [
                'user_id' => $user->id,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get user notifications
     */
    public function getUserNotifications(User $user, array $filters = []): Collection
    {
        $query = $user->notifications();
        
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        
        if (isset($filters['read'])) {
            if ($filters['read']) {
                $query->whereNotNull('read_at');
            } else {
                $query->whereNull('read_at');
            }
        }
        
        if (isset($filters['limit'])) {
            $query->limit($filters['limit']);
        }
        
        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(string $notificationId): bool
    {
        try {
            $notification = \Illuminate\Notifications\DatabaseNotification::find($notificationId);
            
            if ($notification) {
                $notification->markAsRead();
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            Log::error('Failed to mark notification as read', [
                'notification_id' => $notificationId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(User $user): bool
    {
        try {
            $user->unreadNotifications->markAsRead();
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to mark all notifications as read', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Delete notification
     */
    public function delete(string $notificationId): bool
    {
        try {
            $notification = \Illuminate\Notifications\DatabaseNotification::find($notificationId);
            
            if ($notification) {
                $notification->delete();
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            Log::error('Failed to delete notification', [
                'notification_id' => $notificationId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get notification preferences
     */
    public function getPreferences(User $user): array
    {
        return $user->notification_preferences ?? [
            'attendance' => true,
            'face_recognition' => true,
            'location' => true,
            'email' => false,
            'push' => true,
        ];
    }

    /**
     * Update notification preferences
     */
    public function updatePreferences(User $user, array $preferences): bool
    {
        try {
            $user->update(['notification_preferences' => $preferences]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to update notification preferences', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get unread count
     */
    public function getUnreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }
}
