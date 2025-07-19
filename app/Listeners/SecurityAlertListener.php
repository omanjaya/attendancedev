<?php

namespace App\Listeners;

use App\Events\AttendanceEvent;
use App\Events\SecurityEvent;
use App\Models\User;
use App\Notifications\SecurityAlertNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Security Alert Listener
 *
 * Handles sending real-time notifications for security events.
 */
class SecurityAlertListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle security events that may require immediate attention.
     */
    public function handleSecurityEvent(SecurityEvent $event): void
    {
        if (! $event->shouldAlert()) {
            return;
        }

        $recipients = $this->getNotificationRecipients($event->getNotificationRecipients());

        if ($recipients->isEmpty()) {
            Log::warning('No recipients found for security alert', [
                'event_type' => $event->eventType,
                'severity' => $event->severity,
            ]);

            return;
        }

        // Send notifications
        Notification::send($recipients, new SecurityAlertNotification($event));

        // Log the alert
        Log::channel('security')->info('Security alert sent', [
            'event_type' => $event->eventType,
            'severity' => $event->severity,
            'recipients_count' => $recipients->count(),
            'user_id' => $event->user?->id,
        ]);
    }

    /**
     * Handle high-risk attendance events.
     */
    public function handleHighRiskAttendance(AttendanceEvent $event): void
    {
        if ($event->getRiskLevel() !== 'high') {
            return;
        }

        // Create security event for high-risk attendance
        $securityEvent = new SecurityEvent(
            eventType: 'suspicious_attendance',
            user: $event->employee->user,
            severity: 'high',
            ipAddress: request()->ip() ?? '',
            userAgent: request()->userAgent() ?? '',
            metadata: [
                'employee_id' => $event->employee->id,
                'attendance_id' => $event->attendance?->id,
                'location_verified' => ! empty($event->locationData['verified']),
                'face_verified' => ! empty($event->faceData['verified']),
                'action' => $event->action,
            ],
        );

        $this->handleSecurityEvent($securityEvent);
    }

    /**
     * Get users who should receive notifications based on role requirements.
     */
    private function getNotificationRecipients(
        array $roleRequirements,
    ): \Illuminate\Database\Eloquent\Collection {
        $roles = [];

        foreach ($roleRequirements as $requirement) {
            switch ($requirement) {
                case 'security_team':
                    $roles[] = 'admin';
                    $roles[] = 'superadmin';
                    break;
                case 'administrators':
                    $roles[] = 'admin';
                    $roles[] = 'superadmin';
                    break;
                case 'system_managers':
                    $roles[] = 'manager';
                    $roles[] = 'admin';
                    break;
            }
        }

        return User::whereHas('roles', function ($query) use ($roles) {
            $query->whereIn('name', array_unique($roles));
        })
            ->where('is_active', true)
            ->get();
    }

    /**
     * Handle failed notification jobs.
     */
    public function failed($event, $exception): void
    {
        Log::error('Security alert notification failed', [
            'event' => get_class($event),
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Fallback: Log critical alerts to file if notification fails
        if ($event instanceof SecurityEvent && $event->severity === 'critical') {
            Log::critical('CRITICAL SECURITY ALERT - Notification failed', [
                'event_type' => $event->eventType,
                'user_id' => $event->user?->id,
                'ip_address' => $event->ipAddress,
                'metadata' => $event->metadata,
            ]);
        }
    }
}
