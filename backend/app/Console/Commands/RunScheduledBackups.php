<?php

namespace App\Console\Commands;

use App\Http\Controllers\BackupController;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class RunScheduledBackups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:run-scheduled {--type=all : Backup type to run (database, files, full, or all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run scheduled backups based on configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $typeFilter = $this->option('type');
        $schedule = $this->getBackupSchedule();

        $this->info('Running scheduled backups...');

        foreach ($schedule as $type => $config) {
            if ($typeFilter !== 'all' && $typeFilter !== $type) {
                continue;
            }

            if (! $config['enabled']) {
                $this->line("Skipping {$type} backup (disabled)");

                continue;
            }

            if ($this->shouldRunBackup($type, $config)) {
                $this->info("Running {$type} backup...");
                $this->runBackup($type, $config);
            } else {
                $this->line("Skipping {$type} backup (not scheduled for now)");
            }
        }

        // Cleanup old backups
        $this->info('Cleaning up old backups...');
        $this->cleanupOldBackups($schedule);

        $this->info('Scheduled backup run completed.');
    }

    /**
     * Get backup schedule configuration
     */
    private function getBackupSchedule()
    {
        $schedulePath = storage_path('app/backup_schedule.json');

        if (File::exists($schedulePath)) {
            return json_decode(File::get($schedulePath), true);
        }

        // Default schedule
        return [
            'database' => [
                'enabled' => true,
                'frequency' => 'daily',
                'time' => '02:00',
                'retention_days' => 30,
            ],
            'files' => [
                'enabled' => true,
                'frequency' => 'weekly',
                'day' => 'sunday',
                'time' => '03:00',
                'retention_days' => 90,
            ],
            'full' => [
                'enabled' => false,
                'frequency' => 'monthly',
                'day' => 1,
                'time' => '01:00',
                'retention_days' => 365,
            ],
        ];
    }

    /**
     * Check if backup should run based on schedule
     */
    private function shouldRunBackup($type, $config)
    {
        $now = Carbon::now();
        $scheduledTime = Carbon::createFromFormat('H:i', $config['time']);

        // Check if we're within the scheduled time window (within 1 hour)
        $timeWindow = $now->diffInMinutes($scheduledTime) <= 60;

        switch ($config['frequency']) {
            case 'daily':
                return $timeWindow;

            case 'weekly':
                $scheduledDay = strtolower($config['day'] ?? 'sunday');
                $currentDay = strtolower($now->format('l'));

                return $currentDay === $scheduledDay && $timeWindow;

            case 'monthly':
                $scheduledDay = $config['day'] ?? 1;

                return $now->day === $scheduledDay && $timeWindow;

            case 'quarterly':
                $scheduledDay = $config['day'] ?? 1;
                $isQuarterStart = in_array($now->month, [1, 4, 7, 10]);

                return $isQuarterStart && $now->day === $scheduledDay && $timeWindow;

            default:
                return false;
        }
    }

    /**
     * Run backup for specified type
     */
    private function runBackup($type, $config)
    {
        try {
            $backupId = Str::uuid();
            $options = [
                'type' => $type,
                'description' => "Scheduled {$type} backup - ".now()->format('Y-m-d H:i:s'),
                'include_uploads' => true,
                'include_logs' => false,
            ];

            // Create instance of backup controller and call performBackup method
            $backupController = new BackupController;
            $reflection = new \ReflectionClass($backupController);
            $performBackupMethod = $reflection->getMethod('performBackup');
            $performBackupMethod->setAccessible(true);

            $result = $performBackupMethod->invoke($backupController, $backupId, $options);

            $this->info("✓ {$type} backup completed successfully");
            $this->line("  Backup ID: {$backupId}");
            $this->line('  Size: '.$this->formatBytes($result['size']));
        } catch (\Exception $e) {
            $this->error("✗ {$type} backup failed: ".$e->getMessage());
        }
    }

    /**
     * Cleanup old backups based on retention policy
     */
    private function cleanupOldBackups($schedule)
    {
        $metadataDir = storage_path('app/backup_metadata');

        if (! File::exists($metadataDir)) {
            return;
        }

        $metadataFiles = File::files($metadataDir);
        $deletedCount = 0;

        foreach ($metadataFiles as $file) {
            if ($file->getExtension() === 'json') {
                $metadata = json_decode(File::get($file->getPathname()), true);
                $backupType = $metadata['type'];
                $backupDate = Carbon::parse($metadata['created_at']);

                if (isset($schedule[$backupType])) {
                    $retentionDays = $schedule[$backupType]['retention_days'];
                    $cutoffDate = Carbon::now()->subDays($retentionDays);

                    if ($backupDate->lt($cutoffDate)) {
                        $this->deleteBackup($metadata['backup_id']);
                        $deletedCount++;
                        $this->line("  Deleted old {$backupType} backup: {$metadata['backup_id']}");
                    }
                }
            }
        }

        if ($deletedCount > 0) {
            $this->info("✓ Cleaned up {$deletedCount} old backup(s)");
        } else {
            $this->line('  No old backups to clean up');
        }
    }

    /**
     * Delete backup by ID
     */
    private function deleteBackup($backupId)
    {
        // Delete backup file
        $backupFiles = File::files(storage_path('app/backups'));
        foreach ($backupFiles as $file) {
            if (Str::contains($file->getFilename(), $backupId)) {
                File::delete($file->getPathname());
                break;
            }
        }

        // Delete metadata
        $metadataPath = storage_path("app/backup_metadata/{$backupId}.json");
        if (File::exists($metadataPath)) {
            File::delete($metadataPath);
        }
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision).' '.$units[$i];
    }
}
