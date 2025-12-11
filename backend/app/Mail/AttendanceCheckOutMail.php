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

class AttendanceCheckOutMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Employee $employee;
    public Attendance $attendance;
    public string $checkInTime;
    public string $checkOutTime;
    public ?float $totalHours;
    public bool $isOvertime;

    /**
     * Create a new message instance.
     */
    public function __construct(Employee $employee, Attendance $attendance)
    {
        $this->employee = $employee;
        $this->attendance = $attendance;
        $this->checkInTime = $attendance->check_in_time?->format('H:i') ?? '-';
        $this->checkOutTime = $attendance->check_out_time?->format('H:i') ?? '-';
        $this->totalHours = $attendance->total_hours;
        $this->isOvertime = ($attendance->total_hours ?? 0) > 8;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $hours = number_format($this->totalHours ?? 0, 1);
        $subject = $this->isOvertime 
            ? "💪 Check-Out Lembur - {$hours} jam"
            : "🏁 Check-Out Berhasil - {$hours} jam";
            
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
            view: 'emails.attendance.check-out',
            with: [
                'employeeName' => $this->employee->full_name,
                'employeeId' => $this->employee->employee_id,
                'checkInTime' => $this->checkInTime,
                'checkOutTime' => $this->checkOutTime,
                'date' => $this->attendance->date->format('d F Y'),
                'totalHours' => number_format($this->totalHours ?? 0, 1),
                'isOvertime' => $this->isOvertime,
                'status' => $this->attendance->status,
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
