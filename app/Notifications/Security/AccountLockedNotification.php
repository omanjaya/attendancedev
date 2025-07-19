<?php

namespace App\Notifications\Security;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountLockedNotification extends Notification implements ShouldQueue
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
        $reason = $this->data['reason'];
        $timestamp = $this->data['timestamp']->format('M j, Y \a\t g:i A');

        $mailMessage = (new MailMessage)
            ->subject('🔒 Account Security Lock - Immediate Action Required')
            ->line('Your account has been temporarily locked for security reasons.')
            ->line('')
            ->line('**Lock Details:**')
            ->line("• Reason: {$reason}")
            ->line("• Time: {$timestamp}");

        if ($this->data['unlock_time']) {
            $unlockTime = $this->data['unlock_time']->format('M j, Y \a\t g:i A');
            $mailMessage->line("• Automatic unlock: {$unlockTime}");
        }

        $mailMessage->line('')->line('**Why was my account locked?**');

        // Provide context based on lock reason
        switch (strtolower($reason)) {
            case 'too many failed login attempts':
                $mailMessage->line(
                    'Multiple failed login attempts were detected from your account, which could indicate someone is trying to guess your password.',
                );
                break;
            case 'suspicious activity detected':
                $mailMessage->line(
                    'Our security systems detected unusual activity patterns that suggest potential unauthorized access attempts.',
                );
                break;
            case 'multiple 2fa failures':
                $mailMessage->line(
                    'Multiple failed two-factor authentication attempts triggered our security protocols.',
                );
                break;
            case 'admin security intervention':
                $mailMessage->line(
                    'A system administrator has locked your account as a security precaution.',
                );
                break;
            default:
                $mailMessage->line(
                    'Security protocols were triggered based on recent account activity patterns.',
                );
        }

        $mailMessage->line('')->line('**What should I do?**');

        if ($this->data['unlock_time']) {
            $mailMessage
                ->line("• Wait for automatic unlock at {$this->data['unlock_time']->format('g:i A')}")
                ->line('• Ensure you\'re using the correct login credentials');
        } else {
            $mailMessage
                ->line('• Contact support immediately for account recovery')
                ->line('• Prepare to verify your identity');
        }

        return $mailMessage
            ->line('• Change your password once access is restored')
            ->line('• Review your account for any unauthorized changes')
            ->line('• Consider enabling additional security measures')
            ->line('')
            ->action('Contact Support', url('/support'))
            ->line('')
            ->line(
                '**Important:** Do not share your login credentials with anyone. Our team will never ask for your password.',
            )
            ->salutation('Security Team, '.config('app.name'));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'account_locked',
            'title' => 'Account Locked',
            'message' => "Your account has been locked: {$this->data['reason']}",
            'data' => [
                'reason' => $this->data['reason'],
                'lock_duration' => $this->data['lock_duration'],
                'unlock_time' => $this->data['unlock_time']?->toISOString(),
                'timestamp' => $this->data['timestamp']->toISOString(),
            ],
            'action_url' => url('/support'),
            'action_text' => 'Contact Support',
            'priority' => 'high',
        ];
    }
}
