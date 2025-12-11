<?php

namespace App\Notifications;

use App\Models\Leave;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Leave $leave,
        private string $action,
        private ?string $notes = null
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        // Add broadcast for real-time notifications
        $channels[] = 'broadcast';

        // Add mail for approvals/rejections
        if (in_array($this->action, ['approved', 'rejected'])) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mailMessage = (new MailMessage)
            ->subject($this->getEmailSubject());

        if ($this->action === 'approved') {
            $mailMessage
                ->greeting("Selamat, {$notifiable->name}!")
                ->line('Pengajuan cuti Anda telah **disetujui**.')
                ->line("**Jenis Cuti:** {$this->leave->leaveType->name}")
                ->line("**Tanggal:** {$this->leave->date_range}")
                ->line("**Durasi:** {$this->leave->duration}");

            if ($this->notes) {
                $mailMessage->line("**Catatan:** {$this->notes}");
            }

            $mailMessage
                ->action('Lihat Detail', url('/employee/leave'))
                ->line('Selamat beristirahat!');
        } elseif ($this->action === 'rejected') {
            $mailMessage
                ->greeting("Halo, {$notifiable->name}")
                ->line('Pengajuan cuti Anda **ditolak**.')
                ->line("**Jenis Cuti:** {$this->leave->leaveType->name}")
                ->line("**Tanggal:** {$this->leave->date_range}");

            if ($this->notes) {
                $mailMessage->line("**Alasan Penolakan:** {$this->notes}");
            }

            $mailMessage
                ->action('Ajukan Ulang', url('/employee/leave'))
                ->line('Silakan hubungi atasan Anda untuk informasi lebih lanjut.');
        } elseif ($this->action === 'submitted') {
            $mailMessage
                ->greeting("Halo!")
                ->line("Ada pengajuan cuti baru dari **{$this->leave->employee->full_name}**.")
                ->line("**Jenis Cuti:** {$this->leave->leaveType->name}")
                ->line("**Tanggal:** {$this->leave->date_range}")
                ->line("**Durasi:** {$this->leave->duration}")
                ->line("**Alasan:** {$this->leave->reason}")
                ->action('Review Pengajuan', url('/admin/leave/approvals'))
                ->line('Mohon segera ditindaklanjuti.');
        } elseif ($this->action === 'cancelled') {
            $mailMessage
                ->greeting("Info")
                ->line("Pengajuan cuti dari **{$this->leave->employee->full_name}** telah dibatalkan.")
                ->line("**Jenis Cuti:** {$this->leave->leaveType->name}")
                ->line("**Tanggal:** {$this->leave->date_range}");
        }

        return $mailMessage;
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'id' => $this->id,
            'type' => 'leave',
            'action' => $this->action,
            'title' => $this->getNotificationTitle(),
            'message' => $this->getNotificationMessage(),
            'leave_id' => $this->leave->id,
            'leave_type' => $this->leave->leaveType->name ?? 'Cuti',
            'employee_id' => $this->leave->employee_id,
            'employee_name' => $this->leave->employee->full_name ?? 'Unknown',
            'date_range' => $this->leave->date_range,
            'days_requested' => $this->leave->days_requested,
            'notes' => $this->notes,
            'priority' => $this->getPriority(),
            'icon' => $this->getNotificationIcon(),
            'color' => $this->getNotificationColor(),
            'url' => $this->getActionUrl(),
            'timestamp' => now(),
            'read_at' => null,
            'created_at' => now(),
        ];
    }

    /**
     * Get the broadcast representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'id' => $this->id,
            'type' => 'leave',
            'action' => $this->action,
            'title' => $this->getNotificationTitle(),
            'message' => $this->getNotificationMessage(),
            'leave_id' => $this->leave->id,
            'employee_name' => $this->leave->employee->full_name ?? 'Unknown',
            'date_range' => $this->leave->date_range,
            'icon' => $this->getNotificationIcon(),
            'color' => $this->getNotificationColor(),
            'priority' => $this->getPriority(),
            'timestamp' => now(),
            'sound' => 'notification',
        ]);
    }

    /**
     * Get email subject
     */
    private function getEmailSubject(): string
    {
        $employeeName = $this->leave->employee->full_name ?? 'Karyawan';

        return match ($this->action) {
            'submitted' => "Pengajuan Cuti Baru: {$employeeName}",
            'approved' => 'Pengajuan Cuti Anda Disetujui',
            'rejected' => 'Pengajuan Cuti Anda Ditolak',
            'cancelled' => "Pengajuan Cuti Dibatalkan: {$employeeName}",
            default => 'Update Pengajuan Cuti',
        };
    }

    /**
     * Get notification title based on action
     */
    private function getNotificationTitle(): string
    {
        return match ($this->action) {
            'submitted' => '📝 Pengajuan Cuti Baru',
            'approved' => '✅ Cuti Disetujui',
            'rejected' => '❌ Cuti Ditolak',
            'cancelled' => '🚫 Cuti Dibatalkan',
            'pending_reminder' => '🔔 Reminder: Cuti Menunggu Persetujuan',
            default => '📋 Update Cuti',
        };
    }

    /**
     * Get notification message
     */
    private function getNotificationMessage(): string
    {
        $employeeName = $this->leave->employee->full_name ?? 'Karyawan';
        $leaveType = $this->leave->leaveType->name ?? 'Cuti';
        $dateRange = $this->leave->date_range;

        return match ($this->action) {
            'submitted' => "{$employeeName} mengajukan {$leaveType} untuk {$dateRange}",
            'approved' => "Pengajuan {$leaveType} Anda untuk {$dateRange} telah disetujui",
            'rejected' => "Pengajuan {$leaveType} Anda untuk {$dateRange} ditolak" . ($this->notes ? ": {$this->notes}" : ""),
            'cancelled' => "Pengajuan {$leaveType} dari {$employeeName} untuk {$dateRange} dibatalkan",
            'pending_reminder' => "Pengajuan {$leaveType} dari {$employeeName} menunggu persetujuan Anda",
            default => "Update pengajuan cuti: {$employeeName}",
        };
    }

    /**
     * Get notification icon
     */
    private function getNotificationIcon(): string
    {
        return match ($this->action) {
            'submitted' => '📝',
            'approved' => '✅',
            'rejected' => '❌',
            'cancelled' => '🚫',
            'pending_reminder' => '🔔',
            default => '📋',
        };
    }

    /**
     * Get notification color
     */
    private function getNotificationColor(): string
    {
        return match ($this->action) {
            'submitted' => 'blue',
            'approved' => 'green',
            'rejected' => 'red',
            'cancelled' => 'gray',
            'pending_reminder' => 'yellow',
            default => 'gray',
        };
    }

    /**
     * Get notification priority
     */
    private function getPriority(): string
    {
        if ($this->leave->is_emergency) {
            return 'high';
        }

        return match ($this->action) {
            'submitted' => 'medium',
            'approved', 'rejected' => 'normal',
            'pending_reminder' => 'medium',
            default => 'normal',
        };
    }

    /**
     * Get action URL
     */
    private function getActionUrl(): string
    {
        return match ($this->action) {
            'submitted', 'pending_reminder' => '/admin/leave/approvals',
            'approved', 'rejected', 'cancelled' => '/employee/leave',
            default => '/leave',
        };
    }

    /**
     * Get the notification's broadcast channel name
     */
    public function broadcastOn(): array
    {
        return [
            'leave-notifications',
        ];
    }
}
