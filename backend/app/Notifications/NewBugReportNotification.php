<?php

namespace App\Notifications;

use App\Models\BugReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewBugReportNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public BugReport $report
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $severityEmoji = match ($this->report->severity) {
            'critical' => '🔴',
            'high' => '🟠',
            'medium' => '🟡',
            'low' => '🟢',
            default => '⚪',
        };

        $typeLabel = match ($this->report->type) {
            'bug' => 'Bug',
            'error' => 'Error',
            'suggestion' => 'Saran',
            'question' => 'Pertanyaan',
            default => $this->report->type,
        };

        return (new MailMessage)
            ->subject("{$severityEmoji} [{$typeLabel}] {$this->report->title}")
            ->greeting('Laporan Bug Baru!')
            ->line("**Judul:** {$this->report->title}")
            ->line("**Tipe:** {$typeLabel}")
            ->line("**Severity:** " . ucfirst($this->report->severity))
            ->line("**Dilaporkan oleh:** " . ($this->report->user?->name ?? 'Anonymous'))
            ->line("**Deskripsi:**")
            ->line($this->report->description)
            ->when($this->report->error_message, function ($message) {
                return $message->line("**Error Message:**")->line($this->report->error_message);
            })
            ->when($this->report->page_url, function ($message) {
                return $message->line("**Halaman:** {$this->report->page_url}");
            })
            ->action('Lihat Detail', url("/admin/bug-reports/{$this->report->id}"))
            ->line('Segera tindak lanjuti laporan ini.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'bug_report',
            'bug_report_id' => $this->report->id,
            'title' => $this->report->title,
            'report_type' => $this->report->type,
            'severity' => $this->report->severity,
            'reporter_name' => $this->report->user?->name ?? 'Anonymous',
            'message' => "Laporan bug baru: {$this->report->title}",
        ];
    }
}
