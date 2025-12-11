<?php

namespace App\Console\Commands;

use App\Models\Report;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanupExpiredReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:cleanup
                            {--days=7 : Delete reports older than this many days}
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired report files and database records';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $dryRun = $this->option('dry-run');

        $this->info("🗑️  Cleaning up expired reports (older than {$days} days)...");

        if ($dryRun) {
            $this->warn('⚠️  DRY RUN MODE - No files will be deleted');
        }

        // Find expired reports
        $expiredReports = Report::where('expires_at', '<', now())
            ->orWhere('created_at', '<', now()->subDays($days))
            ->get();

        $this->info("Found {$expiredReports->count()} expired reports");

        $deletedFiles = 0;
        $deletedRecords = 0;
        $errors = 0;

        foreach ($expiredReports as $report) {
            try {
                // Extract file path
                $filePath = str_replace('storage/', '', $report->file_path);
                
                if ($dryRun) {
                    $this->line("Would delete: {$report->filename} (ID: {$report->id})");
                    if (file_exists(storage_path('app/public/' . $filePath))) {
                        $this->line("  └─ File exists: {$filePath}");
                    } else {
                        $this->line("  └─ File not found: {$filePath}");
                    }
                    continue;
                }

                // Delete physical file
                if (file_exists(storage_path('app/public/' . $filePath))) {
                    unlink(storage_path('app/public/' . $filePath));
                    $deletedFiles++;
                    $this->line("✓ Deleted file: {$report->filename}");
                }

                // Delete database record
                $report->delete();
                $deletedRecords++;

                Log::info('Expired report cleaned up', [
                    'report_id' => $report->id,
                    'filename' => $report->filename,
                ]);

            } catch (\Exception $e) {
                $errors++;
                $this->error("✗ Failed to delete {$report->filename}: {$e->getMessage()}");
                
                Log::error('Failed to cleanup report', [
                    'report_id' => $report->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($dryRun) {
            $this->info("\n📊 DRY RUN Summary:");
            $this->info("Would delete {$expiredReports->count()} reports");
        } else {
            $this->info("\n✅ Cleanup completed:");
            $this->info("Files deleted: {$deletedFiles}");
            $this->info("Records deleted: {$deletedRecords}");
            if ($errors > 0) {
                $this->error("Errors: {$errors}");
            }
        }

        return Command::SUCCESS;
    }
}
