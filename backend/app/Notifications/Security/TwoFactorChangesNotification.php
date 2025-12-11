<?php

namespace App\Notifications\Security;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TwoFactorChangesNotification extends Notification implements ShouldQueue
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
        $changeType = $this->data['change_type'];
        $location = $this->data['location'] ?? 'Unknown location';
        $timestamp = $this->data['timestamp']->format('M j, Y \a\t g:i A');

        $mailMessage = (new MailMessage)
            ->subject('🔐 Two-Factor Authentication Settings Changed')
            ->line('Important changes have been made to your two-factor authentication settings.')
            ->line('')
            ->line('**Change Details:**')
            ->line('• Action: '.$this->getChangeDescription($changeType))
            ->line("• IP Address: {$this->data['ip_address']}")
            ->line("• Location: {$location}")
            ->line("• Time: {$timestamp}")
            ->line('');

        // Add specific guidance based on change type
        switch ($changeType) {
            case 'enabled':
                $mailMessage
                    ->line('✅ **Two-factor authentication has been enabled on your account.**')
                    ->line('')
                    ->line('This significantly improves your account security. Make sure to:')
                    ->line('• Save your backup recovery codes in a safe place')
                    ->line('• Test your authenticator app to ensure it works correctly')
                    ->line('• Keep your recovery codes accessible but secure');
                break;

            case 'disabled':
                $mailMessage
                    ->line('⚠️ **Two-factor authentication has been disabled on your account.**')
                    ->line('')
                    ->line('Your account is now less secure. We strongly recommend:')
                    ->line('• Re-enabling two-factor authentication as soon as possible')
                    ->line('• Using a strong, unique password')
                    ->line('• Monitoring your account for unusual activity');
                break;

            case 'recovery_codes_regenerated':
                $mailMessage
                    ->line('🔄 **New recovery codes have been generated.**')
                    ->line('')
                    ->line('Your old recovery codes are no longer valid. Make sure to:')
                    ->line('• Download and securely store your new recovery codes')
                    ->line('• Delete or destroy your old recovery codes')
                    ->line('• Test one of the new codes to ensure they work');
                break;

            case 'secret_regenerated':
                $mailMessage
                    ->line('🔄 **Your authenticator secret has been regenerated.**')
                    ->line('')
                    ->line('You\'ll need to set up your authenticator app again:')
                    ->line('• Remove the old entry from your authenticator app')
                    ->line('• Scan the new QR code or enter the new secret manually')
                    ->line('• Test the new setup to ensure it generates correct codes');
                break;
        }

        return $mailMessage
            ->line('')
            ->line('If you didn\'t make this change, please:')
            ->line('• Change your password immediately')
            ->line('• Check your account for any unauthorized changes')
            ->line('• Contact support if you need assistance')
            ->action('Review Security Settings', url('/settings/security'))
            ->salutation('Security Team, '.config('app.name'));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'two_factor_changes',
            'title' => '2FA Settings Changed',
            'message' => $this->getChangeDescription($this->data['change_type']),
            'data' => [
                'change_type' => $this->data['change_type'],
                'ip_address' => $this->data['ip_address'],
                'location' => $this->data['location'],
                'timestamp' => $this->data['timestamp']->toISOString(),
            ],
            'action_url' => url('/settings/security'),
            'action_text' => 'Review Settings',
            'priority' => 'high',
        ];
    }

    /**
     * Get human-readable description of the change.
     */
    private function getChangeDescription(string $changeType): string
    {
        return match ($changeType) {
            'enabled' => 'Two-factor authentication enabled',
            'disabled' => 'Two-factor authentication disabled',
            'recovery_codes_regenerated' => 'Recovery codes regenerated',
            'secret_regenerated' => 'Authenticator secret regenerated',
            default => 'Two-factor authentication settings modified',
        };
    }
}
