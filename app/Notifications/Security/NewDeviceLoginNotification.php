<?php

namespace App\Notifications\Security;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewDeviceLoginNotification extends Notification implements ShouldQueue
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
        $device = $this->data['device'];
        $location = $this->data['location'] ?? 'Unknown location';
        $timestamp = $this->data['timestamp']->format('M j, Y \a\t g:i A');

        return (new MailMessage)
            ->subject('🔐 New Device Login Detected')
            ->line('We detected a login to your account from a new device.')
            ->line('')
            ->line('**Device Details:**')
            ->line("• Device: {$device->display_name}")
            ->line('• Type: '.ucfirst($device->device_type))
            ->line("• Browser: {$device->browser_name}")
            ->line("• Operating System: {$device->os_name}")
            ->line("• IP Address: {$this->data['ip_address']}")
            ->line("• Location: {$location}")
            ->line("• Time: {$timestamp}")
            ->line('')
            ->line(
                'If this was you, you can ignore this email. If you don\'t recognize this login, please secure your account immediately.',
            )
            ->action('Review Device Activity', url('/settings/security'))
            ->line('')
            ->line('For your security, consider:')
            ->line('• Changing your password if you don\'t recognize this activity')
            ->line('• Enabling two-factor authentication if not already enabled')
            ->line('• Reviewing your trusted devices regularly')
            ->salutation('Stay secure, '.config('app.name').' Security Team');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $device = $this->data['device'];

        return [
            'type' => 'new_device_login',
            'title' => 'New Device Login',
            'message' => "Login detected from {$device->display_name} at {$this->data['ip_address']}",
            'data' => [
                'device_id' => $device->id,
                'device_name' => $device->display_name,
                'device_type' => $device->device_type,
                'ip_address' => $this->data['ip_address'],
                'location' => $this->data['location'],
                'timestamp' => $this->data['timestamp']->toISOString(),
            ],
            'action_url' => url('/settings/security'),
            'action_text' => 'Review Devices',
            'priority' => 'medium',
        ];
    }
}
