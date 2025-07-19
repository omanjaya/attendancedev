<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AttendanceNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private array $data
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        // Add broadcast for real-time notifications
        if ($this->shouldBroadcast()) {
            $channels[] = 'broadcast';
        }

        // Add mail for important notifications
        if ($this->shouldMail()) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->getEmailSubject();

        return (new MailMessage)
            ->subject($subject)
            ->greeting("Hello {$notifiable->name}!")
            ->line($this->data['message'])
            ->line($this->getAdditionalInfo())
            ->action('View Attendance', url('/attendance'))
            ->line('Thank you for using our attendance system!');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'id' => $this->id,
            'type' => $this->data['type'],
            'action' => $this->data['action'],
            'title' => $this->getNotificationTitle(),
            'message' => $this->data['message'],
            'employee_name' => $this->data['employee_name'] ?? null,
            'timestamp' => $this->data['timestamp'] ?? now(),
            'metadata' => $this->getMetadata(),
            'priority' => $this->data['priority'] ?? 'normal',
            'icon' => $this->getNotificationIcon(),
            'color' => $this->getNotificationColor(),
            'read_at' => null,
            'created_at' => now(),
        ];
    }

    /**
     * Get the broadcast representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'id' => $this->id,
            'type' => $this->data['type'],
            'action' => $this->data['action'],
            'title' => $this->getNotificationTitle(),
            'message' => $this->data['message'],
            'timestamp' => $this->data['timestamp'] ?? now(),
            'employee_name' => $this->data['employee_name'] ?? null,
            'icon' => $this->getNotificationIcon(),
            'color' => $this->getNotificationColor(),
            'sound' => $this->getNotificationSound(),
            'priority' => $this->data['priority'] ?? 'normal',
            'metadata' => $this->getMetadata(),
        ]);
    }

    /**
     * Get notification title based on type and action
     */
    private function getNotificationTitle(): string
    {
        $type = $this->data['type'];
        $action = $this->data['action'];

        return match ($type) {
            'attendance' => match ($action) {
                'check-in' => '✅ Check-in Successful',
                'check-out' => '🏁 Check-out Complete',
                'late_arrival' => '⏰ Late Arrival Recorded',
                'early_departure' => '⏰ Early Departure Recorded',
                default => '📊 Attendance Update'
            },
            'face_recognition' => match ($action) {
                'verification' => '🎯 Face Verification',
                'enrollment' => '📷 Face Registration',
                'failed' => '❌ Face Recognition Failed',
                default => '👤 Face Recognition'
            },
            'location' => match ($action) {
                'verification' => '📍 Location Verified',
                'failed' => '⚠️ Location Check Failed',
                default => '🗺️ Location Update'
            },
            'system' => match ($action) {
                'security_alert' => '🚨 Security Alert',
                'system_error' => '❌ System Error',
                'maintenance' => '🔧 System Maintenance',
                'backup' => '💾 System Backup',
                default => 'ℹ️ System Notification'
            },
            'reminder' => '🔔 Attendance Reminder',
            default => '📢 Notification'
        };
    }

    /**
     * Get notification icon based on type and action
     */
    private function getNotificationIcon(): string
    {
        $type = $this->data['type'];
        $action = $this->data['action'];

        return match ($type) {
            'attendance' => match ($action) {
                'check-in' => '✅',
                'check-out' => '🏁',
                'late_arrival' => '⏰',
                'early_departure' => '⏰',
                default => '📊'
            },
            'face_recognition' => '👤',
            'location' => '📍',
            'system' => '⚙️',
            'reminder' => '🔔',
            default => 'ℹ️'
        };
    }

    /**
     * Get notification color based on type and priority
     */
    private function getNotificationColor(): string
    {
        $priority = $this->data['priority'] ?? 'normal';
        $type = $this->data['type'];
        $action = $this->data['action'] ?? '';

        // Priority-based colors
        if ($priority === 'high') {
            return 'red';
        } elseif ($priority === 'medium') {
            return 'orange';
        }

        // Type-based colors
        return match ($type) {
            'attendance' => str_contains($action, 'late') || str_contains($action, 'early') ? 'yellow' : 'green',
            'face_recognition' => 'purple',
            'location' => 'blue',
            'system' => 'gray',
            'reminder' => 'blue',
            default => 'gray'
        };
    }

    /**
     * Get notification sound based on type and priority
     */
    private function getNotificationSound(): string
    {
        $priority = $this->data['priority'] ?? 'normal';
        $type = $this->data['type'];

        return match ($priority) {
            'high' => 'urgent',
            'medium' => 'important',
            default => match ($type) {
                'attendance' => 'success',
                'system' => 'system',
                'reminder' => 'reminder',
                default => 'default'
            }
        };
    }

    /**
     * Get additional metadata for the notification
     */
    private function getMetadata(): array
    {
        $metadata = [
            'created_at' => now()->toISOString(),
            'timezone' => config('app.timezone'),
        ];

        // Add type-specific metadata
        match ($this->data['type']) {
            'attendance' => $metadata = array_merge($metadata, [
                'confidence_score' => $this->data['confidence_score'] ?? null,
                'location_verified' => $this->data['location_verified'] ?? false,
                'total_hours' => $this->data['total_hours'] ?? null,
                'status' => $this->data['status'] ?? null,
            ]),
            'face_recognition' => $metadata = array_merge($metadata, [
                'algorithm' => $this->data['algorithm'] ?? null,
                'processing_time' => $this->data['processing_time'] ?? null,
                'confidence_score' => $this->data['confidence_score'] ?? null,
            ]),
            'location' => $metadata = array_merge($metadata, [
                'latitude' => $this->data['latitude'] ?? null,
                'longitude' => $this->data['longitude'] ?? null,
                'accuracy' => $this->data['accuracy'] ?? null,
                'distance_from_office' => $this->data['distance_from_office'] ?? null,
            ]),
            default => null
        };

        return array_filter($metadata, fn ($value) => $value !== null);
    }

    /**
     * Get email subject
     */
    private function getEmailSubject(): string
    {
        $employeeName = $this->data['employee_name'] ?? 'Employee';
        $action = ucfirst(str_replace(['-', '_'], ' ', $this->data['action']));

        return "Attendance System: {$action} - {$employeeName}";
    }

    /**
     * Get additional info for email
     */
    private function getAdditionalInfo(): string
    {
        $info = [];

        if (isset($this->data['timestamp'])) {
            $info[] = 'Time: '.Carbon::parse($this->data['timestamp'])->format('Y-m-d H:i:s');
        }

        if (isset($this->data['confidence_score'])) {
            $info[] = 'Confidence Score: '.($this->data['confidence_score'] * 100).'%';
        }

        if (isset($this->data['location_verified']) && $this->data['location_verified']) {
            $info[] = 'Location: Verified';
        }

        if (isset($this->data['total_hours'])) {
            $info[] = 'Total Hours: '.$this->data['total_hours'];
        }

        return empty($info) ? '' : 'Details: '.implode(', ', $info);
    }

    /**
     * Determine if notification should be broadcasted
     */
    private function shouldBroadcast(): bool
    {
        $type = $this->data['type'];
        $priority = $this->data['priority'] ?? 'normal';

        // Always broadcast high priority notifications
        if ($priority === 'high') {
            return true;
        }

        // Broadcast real-time events
        return in_array($type, ['attendance', 'face_recognition', 'system']);
    }

    /**
     * Determine if notification should be emailed
     */
    private function shouldMail(): bool
    {
        $priority = $this->data['priority'] ?? 'normal';
        $type = $this->data['type'];

        // Send email for high priority notifications
        if ($priority === 'high') {
            return true;
        }

        // Send email for security-related notifications
        if ($type === 'system' && in_array($this->data['action'], ['security_alert', 'unauthorized_access'])) {
            return true;
        }

        return false;
    }

    /**
     * Get the notification's broadcast channel name
     */
    public function broadcastOn(): array
    {
        return [
            'attendance-notifications',
            'user.'.$this->data['employee_name'] ?? 'general',
        ];
    }

    /**
     * Customize the notification's queue connection
     */
    public function viaConnections(): array
    {
        return [
            'mail' => 'redis',
            'database' => 'sync',
            'broadcast' => 'redis',
        ];
    }

    /**
     * Determine the time at which the notification should be sent
     */
    public function delay(object $notifiable): array
    {
        return [
            'mail' => now()->addSeconds(10), // Slight delay for email
            'database' => now(), // Immediate for database
            'broadcast' => now(), // Immediate for real-time
        ];
    }
}
