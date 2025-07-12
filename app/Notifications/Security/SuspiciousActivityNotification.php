<?php

namespace App\Notifications\Security;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SuspiciousActivityNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $activityType = $this->data['activity_type'];
        $riskLevel = strtoupper($this->data['risk_level']);
        $timestamp = $this->data['timestamp']->format('M j, Y \a\t g:i A');
        
        $emoji = match ($this->data['risk_level']) {
            'high' => '🚨',
            'medium' => '⚠️',
            default => '🔍',
        };

        $mailMessage = (new MailMessage)
            ->subject("{$emoji} Suspicious Activity Alert - {$riskLevel} Risk")
            ->line("We've detected suspicious activity on your account that requires your attention.")
            ->line('')
            ->line('**Activity Details:**')
            ->line("• Type: " . ucwords(str_replace('_', ' ', $activityType)))
            ->line("• Risk Level: {$riskLevel}")
            ->line("• Time: {$timestamp}");

        if (isset($this->data['details']['ip_address'])) {
            $mailMessage->line("• IP Address: {$this->data['details']['ip_address']}");
        }

        if (isset($this->data['details']['location'])) {
            $mailMessage->line("• Location: {$this->data['details']['location']}");
        }

        $mailMessage->line('')
            ->line('**What happened:**');

        // Add specific details based on activity type
        switch ($activityType) {
            case 'rapid_login_attempts':
                $mailMessage->line('Multiple rapid login attempts were detected from different locations.');
                break;
            case 'unusual_login_time':
                $mailMessage->line('A login occurred at an unusual time compared to your normal patterns.');
                break;
            case 'multiple_failed_2fa':
                $mailMessage->line('Multiple failed two-factor authentication attempts were detected.');
                break;
            case 'privilege_escalation_attempt':
                $mailMessage->line('An attempt to access elevated privileges was detected.');
                break;
            default:
                $mailMessage->line('Unusual account activity was detected by our security systems.');
        }

        return $mailMessage
            ->line('')
            ->line('**Immediate Actions Required:**')
            ->line('• Review your recent account activity')
            ->line('• Change your password if you suspect unauthorized access')
            ->line('• Check your trusted devices and remove any you don\'t recognize')
            ->line('• Contact support if you need assistance')
            ->action('Review Security Activity', url('/settings/security'))
            ->line('')
            ->line('If you believe this alert was triggered by your normal activity, you can safely ignore this message.')
            ->salutation('Stay vigilant, ' . config('app.name') . ' Security Team');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $activityTitle = ucwords(str_replace('_', ' ', $this->data['activity_type']));
        
        return [
            'type' => 'suspicious_activity',
            'title' => 'Suspicious Activity Detected',
            'message' => "{$activityTitle} - {$this->data['risk_level']} risk level",
            'data' => [
                'activity_type' => $this->data['activity_type'],
                'risk_level' => $this->data['risk_level'],
                'details' => $this->data['details'],
                'timestamp' => $this->data['timestamp']->toISOString(),
            ],
            'action_url' => url('/settings/security'),
            'action_text' => 'Review Activity',
            'priority' => $this->data['risk_level'] === 'high' ? 'high' : 'medium',
        ];
    }
}