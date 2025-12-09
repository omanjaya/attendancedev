<?php

namespace App\Mail;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeEmployeeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Employee $employee;
    public User $user;
    public string $temporaryPassword;

    /**
     * Create a new message instance.
     */
    public function __construct(Employee $employee, User $user, string $temporaryPassword)
    {
        $this->employee = $employee;
        $this->user = $user;
        $this->temporaryPassword = $temporaryPassword;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🎉 Selamat Datang di ' . config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.welcome',
            with: [
                'employeeName' => $this->employee->full_name,
                'employeeId' => $this->employee->employee_id,
                'userEmail' => $this->user->email,
                'temporaryPassword' => $this->temporaryPassword,
                'loginUrl' => config('app.frontend_url', config('app.url')) . '/auth/login',
                'appName' => config('app.name'),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
