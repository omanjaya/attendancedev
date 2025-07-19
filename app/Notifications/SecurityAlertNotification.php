<?php

namespace App\Notifications;

use App\Events\SecurityEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Security Alert Notification
 *
 * Sends security alerts to administrators via multiple channels.
 */
class SecurityAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public SecurityEvent $securityEvent) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        $channels = ['database'];

        // Add email for high severity events
        if (in_array($this->securityEvent->severity, ['high', 'critical'])) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject($this->getEmailSubject())
            ->greeting('Security Alert')
            ->line($this->getSecurityMessage())
            ->line('Event Type: '.str_replace('_', ' ', ucwords($this->securityEvent->eventType)))
            ->line('Severity: '.ucfirst($this->securityEvent->severity))
            ->line('Time: '.now()->format('Y-m-d H:i:s'))
            ->line('IP Address: '.$this->securityEvent->ipAddress);

        if ($this->securityEvent->user) {
            $message->line(
                'User: '.
                  $this->securityEvent->user->name.
                  ' ('.
                  $this->securityEvent->user->email.
                  ')',
            );
        }

        $message
            ->action('View Security Dashboard', url('/admin/security'))
            ->line('Please review this security event and take appropriate action if necessary.');

        if ($this->securityEvent->severity === 'critical') {
            $message->error();
        }

        return $message;
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'security_alert',
            'title' => $this->getNotificationTitle(),
            'message' => $this->getSecurityMessage(),
            'severity' => $this->securityEvent->severity,
            'event_type' => $this->securityEvent->eventType,
            'user_id' => $this->securityEvent->user?->id,
            'ip_address' => $this->securityEvent->ipAddress,
            'metadata' => $this->securityEvent->metadata,
            'action_url' => url('/admin/security'),
            'action_text' => 'View Details',
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Get email subject based on severity.
     */
    private function getEmailSubject(): string
    {
        $prefix = match ($this->securityEvent->severity) {
            'critical' => '🚨 CRITICAL SECURITY ALERT',
            'high' => '⚠️ Security Alert',
            'medium' => 'Security Notice',
            default => 'Security Event',
        };

        return $prefix.' - '.config('app.name');
    }

    /**
     * Get notification title for database storage.
     */
    private function getNotificationTitle(): string
    {
        return match ($this->securityEvent->eventType) {
            'failed_login' => 'Failed Login Attempt',
            'account_locked' => 'User Account Locked',
            'multiple_failed_logins' => 'Multiple Failed Login Attempts',
            'suspicious_login_pattern' => 'Suspicious Login Pattern Detected',
            'suspicious_attendance' => 'Suspicious Attendance Activity',
            '2fa_disabled' => 'Two-Factor Authentication Disabled',
            'password_changed' => 'Password Changed',
            'privilege_escalation' => 'Privilege Escalation Attempt',
            'data_breach_attempt' => 'Potential Data Breach Attempt',
            default => 'Security Event: '.
              str_replace('_', ' ', ucwords($this->securityEvent->eventType)),
        };
    }

    /**
     * Get detailed security message.
     */
    private function getSecurityMessage(): string
    {
        $baseMessage = $this->getNotificationTitle();

        if ($this->securityEvent->user) {
            $baseMessage .= ' for user '.$this->securityEvent->user->name;
        }

        if ($this->securityEvent->ipAddress) {
            $baseMessage .= ' from IP '.$this->securityEvent->ipAddress;
        }

        // Add specific details based on event type
        $details = match ($this->securityEvent->eventType) {
            'multiple_failed_logins' => $this->getFailedLoginDetails(),
            'suspicious_attendance' => $this->getSuspiciousAttendanceDetails(),
            'account_locked' => 'The user account has been automatically locked due to security policy violations.',
            default => 'A security event has been detected and requires attention.',
        };

        return $baseMessage.'. '.$details;
    }

    /**
     * Get details for failed login events.
     */
    private function getFailedLoginDetails(): string
    {
        $count = $this->securityEvent->metadata['failed_count'] ?? 'multiple';

        return "There have been {$count} failed login attempts in a short period.";
    }

    /**
     * Get details for suspicious attendance events.
     */
    private function getSuspiciousAttendanceDetails(): string
    {
        $metadata = $this->securityEvent->metadata;
        $issues = [];

        if (! ($metadata['location_verified'] ?? true)) {
            $issues[] = 'location verification failed';
        }

        if (! ($metadata['face_verified'] ?? true)) {
            $issues[] = 'face recognition failed';
        }

        if (empty($issues)) {
            return 'Unusual attendance pattern detected.';
        }

        return 'Attendance recorded with issues: '.implode(' and ', $issues).'.';
    }
}
