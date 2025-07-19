<?php

namespace App\Console\Commands;

use App\Services\SecurityNotificationService;
use Illuminate\Console\Command;

class ProcessNotificationDigests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:process-digests
                           {--force : Force processing regardless of frequency}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process and send digest notifications to users based on their preferences';

    /**
     * Execute the console command.
     */
    public function handle(SecurityNotificationService $notificationService)
    {
        $this->info('Starting notification digest processing...');

        try {
            $notificationService->processDigestNotifications();

            $this->info('✅ Notification digests processed successfully');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Failed to process notification digests: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
