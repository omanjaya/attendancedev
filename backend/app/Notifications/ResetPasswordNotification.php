<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    /**
     * The password reset token.
     */
    public string $token;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $resetUrl = $this->getResetUrl($notifiable);

        return (new MailMessage)
            ->subject('Reset Password - ' . config('app.name'))
            ->view('emails.auth.reset-password-link', [
                'resetUrl' => $resetUrl,
                'email' => $notifiable->email,
                'ipAddress' => request()->ip(),
            ]);
    }

    /**
     * Get the reset URL for the given notifiable.
     */
    protected function getResetUrl(object $notifiable): string
    {
        $frontendUrl = config('app.frontend_url', config('app.url'));

        return $frontendUrl . '/auth/reset-password?' . http_build_query([
            'token' => $this->token,
            'email' => $notifiable->email,
        ]);
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'token' => $this->token,
            'email' => $notifiable->email,
        ];
    }
}
