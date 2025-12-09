<?php

namespace App\Console\Commands;

use App\Services\EmailNotificationService;
use Illuminate\Console\Command;

class SendAttendanceReminders extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'attendance:send-reminders 
                            {type=check-in : Type of reminder (check-in, check-out)}
                            {--dry-run : Show what would be sent without actually sending}';

    /**
     * The console command description.
     */
    protected $description = 'Send attendance reminder emails to employees who haven\'t checked in/out';

    public function __construct(
        private EmailNotificationService $emailService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type = $this->argument('type');
        $dryRun = $this->option('dry-run');

        $this->info("Sending {$type} reminders..." . ($dryRun ? ' (DRY RUN)' : ''));

        try {
            if ($type === 'check-in') {
                $count = $dryRun 
                    ? $this->countCheckInReminders()
                    : $this->emailService->sendBulkCheckInReminders();
            } elseif ($type === 'check-out') {
                $count = $dryRun 
                    ? $this->countCheckOutReminders()
                    : $this->emailService->sendBulkCheckOutReminders();
            } else {
                $this->error("Unknown reminder type: {$type}");
                return self::FAILURE;
            }

            $this->info("✅ {$count} reminder(s) " . ($dryRun ? 'would be' : 'have been') . " sent.");
            
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to send reminders: " . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Count employees who need check-in reminders
     */
    private function countCheckInReminders(): int
    {
        return \App\Models\Employee::where('is_active', true)
            ->whereDoesntHave('attendances', function ($query) {
                $query->whereDate('date', today());
            })
            ->whereHas('user')
            ->count();
    }

    /**
     * Count employees who need check-out reminders
     */
    private function countCheckOutReminders(): int
    {
        return \App\Models\Employee::where('is_active', true)
            ->whereHas('attendances', function ($query) {
                $query->whereDate('date', today())
                    ->whereNotNull('check_in_time')
                    ->whereNull('check_out_time');
            })
            ->whereHas('user')
            ->count();
    }
}
