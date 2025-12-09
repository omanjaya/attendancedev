<?php

namespace App\Mail;

use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AttendanceCheckInMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Employee $employee;
    public Attendance $attendance;
    public string $checkInTime;
    public string $status;
    public bool $isLate;

    /**
     * Create a new message instance.
     */
    public function __construct(Employee $employee, Attendance $attendance)
    {
        $this->employee = $employee;
        $this->attendance = $attendance;
        $this->checkInTime = $attendance->check_in_time?->format('H:i') ?? '-';
        $this->status = $attendance->status;
        $this->isLate = $attendance->status === 'late';
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->isLate 
            ? '⏰ Notifikasi Keterlambatan - ' . $this->checkInTime
            : '✅ Check-In Berhasil - ' . $this->checkInTime;
            
        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.attendance.check-in',
            with: [
                'employeeName' => $this->employee->full_name,
                'employeeId' => $this->employee->employee_id,
                'checkInTime' => $this->checkInTime,
                'date' => $this->attendance->date->format('d F Y'),
                'status' => $this->status,
                'isLate' => $this->isLate,
                'locationVerified' => $this->attendance->location_verified,
                'faceVerified' => $this->attendance->check_in_confidence >= 0.7,
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
