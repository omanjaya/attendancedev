<?php

namespace App\Mail;

use App\Models\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AttendanceReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Employee $employee;
    public string $reminderType;
    public string $message;

    /**
     * Create a new message instance.
     * 
     * @param Employee $employee
     * @param string $reminderType - 'check_in', 'check_out', or 'absent'
     * @param string|null $message
     */
    public function __construct(Employee $employee, string $reminderType = 'check_in', ?string $message = null)
    {
        $this->employee = $employee;
        $this->reminderType = $reminderType;
        $this->message = $message ?? $this->getDefaultMessage($reminderType);
    }

    /**
     * Get default message based on reminder type
     */
    private function getDefaultMessage(string $type): string
    {
        return match($type) {
            'check_in' => 'Anda belum melakukan check-in hari ini. Mohon segera lakukan absensi.',
            'check_out' => 'Jangan lupa untuk melakukan check-out sebelum pulang.',
            'absent' => 'Anda tidak hadir hari ini. Jika ada kendala, silakan hubungi HRD.',
            default => 'Reminder untuk absensi Anda.'
        };
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subjects = [
            'check_in' => '⏰ Reminder: Belum Check-In',
            'check_out' => '🔔 Reminder: Jangan Lupa Check-Out',
            'absent' => '⚠️ Notifikasi Ketidakhadiran',
        ];
        
        return new Envelope(
            subject: $subjects[$this->reminderType] ?? '🔔 Reminder Absensi',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.attendance.reminder',
            with: [
                'employeeName' => $this->employee->full_name,
                'employeeId' => $this->employee->employee_id,
                'reminderType' => $this->reminderType,
                'message' => $this->message,
                'date' => now()->format('d F Y'),
                'time' => now()->format('H:i'),
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
