<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * BackupService
 *
 * Handles all backup-related business logic
 */
class BackupService
{
    private string $backupDisk;

    private string $backupPath;

    public function __construct()
    {
        $this->backupDisk = config('backup.backup.destination.disks')[0] ?? 'local';
        $this->backupPath = config('backup.backup.name', config('app.name'));
    }

    /**
     * Get available backups
     */
    public function getAvailableBackups(): array
    {
        try {
            $backups = [];
            $files = Storage::disk($this->backupDisk)->files($this->backupPath);

            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'zip') {
                    $backups[] = [
                        'filename' => basename($file),
                        'path' => $file,
                        'size' => $this->formatBytes(Storage::disk($this->backupDisk)->size($file)),
                        'created_at' => Carbon::createFromTimestamp(
                            Storage::disk($this->backupDisk)->lastModified($file)
                        )->format('Y-m-d H:i:s'),
                        'is_healthy' => $this->isBackupHealthy($file),
                    ];
                }
            }

            return collect($backups)->sortByDesc('created_at')->values()->all();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Create a new backup
     */
    public function createBackup(array $options = []): array
    {
        try {
            $type = $options['type'] ?? 'full';
            $compress = $options['compress'] ?? true;

            // Generate backup filename
            $timestamp = Carbon::now()->format('Y-m-d-H-i-s');
            $filename = "{$this->backupPath}-{$type}-{$timestamp}.zip";

            // Create backup directory if it doesn't exist
            $backupDir = storage_path('app/backups/temp');
            if (! File::exists($backupDir)) {
                File::makeDirectory($backupDir, 0755, true);
            }

            // Create backup based on type
            switch ($type) {
                case 'database':
                    $result = $this->createDatabaseBackup($filename, $compress);
                    break;
                case 'files':
                    $result = $this->createFilesBackup($filename, $compress);
                    break;
                case 'full':
                default:
                    $result = $this->createFullBackup($filename, $compress);
                    break;
            }

            return [
                'success' => true,
                'filename' => $filename,
                'size' => $result['size'] ?? 0,
                'type' => $type,
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Delete a backup
     */
    public function deleteBackup(string $filename): bool
    {
        try {
            $filePath = $this->backupPath.'/'.$filename;

            if (Storage::disk($this->backupDisk)->exists($filePath)) {
                Storage::disk($this->backupDisk)->delete($filePath);

                return true;
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Download a backup
     */
    public function downloadBackup(string $filename): ?string
    {
        try {
            $filePath = $this->backupPath.'/'.$filename;

            if (Storage::disk($this->backupDisk)->exists($filePath)) {
                return Storage::disk($this->backupDisk)->path($filePath);
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get backup schedule information
     */
    public function getBackupSchedule(): array
    {
        return [
            'enabled' => config('backup.backup.destination.disks') !== null,
            'frequency' => config('backup.backup.source.files.include', []),
            'retention' => config('backup.cleanup.defaultStrategy', 'keep_last_backups'),
            'next_run' => $this->getNextScheduledRun(),
        ];
    }

    /**
     * Get storage information
     */
    public function getStorageInfo(): array
    {
        try {
            $disk = Storage::disk($this->backupDisk);
            $backupFiles = $disk->files($this->backupPath);

            $totalSize = 0;
            foreach ($backupFiles as $file) {
                $totalSize += $disk->size($file);
            }

            return [
                'total_backups' => count($backupFiles),
                'total_size' => $this->formatBytes($totalSize),
                'available_space' => $this->formatBytes(disk_free_space(storage_path('app'))),
                'disk_usage' => $this->formatBytes(disk_total_space(storage_path('app')) - disk_free_space(storage_path('app'))),
            ];
        } catch (\Exception $e) {
            return [
                'total_backups' => 0,
                'total_size' => '0 B',
                'available_space' => 'Unknown',
                'disk_usage' => 'Unknown',
            ];
        }
    }

    /**
     * Restore from backup
     */
    public function restoreBackup(string $filename): array
    {
        try {
            $filePath = $this->backupPath.'/'.$filename;

            if (! Storage::disk($this->backupDisk)->exists($filePath)) {
                return [
                    'success' => false,
                    'error' => 'Backup file not found',
                ];
            }

            // Extract backup
            $extractPath = storage_path('app/backups/restore');
            if (! File::exists($extractPath)) {
                File::makeDirectory($extractPath, 0755, true);
            }

            $zip = new ZipArchive;
            $backupFile = Storage::disk($this->backupDisk)->path($filePath);

            if ($zip->open($backupFile) === true) {
                $zip->extractTo($extractPath);
                $zip->close();

                // Restore database if exists
                if (File::exists($extractPath.'/database.sql')) {
                    $this->restoreDatabase($extractPath.'/database.sql');
                }

                // Restore files if exists
                if (File::exists($extractPath.'/files')) {
                    $this->restoreFiles($extractPath.'/files');
                }

                // Cleanup
                File::deleteDirectory($extractPath);

                return [
                    'success' => true,
                    'message' => 'Backup restored successfully',
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to extract backup file',
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Private helper methods
     */
    private function createDatabaseBackup(string $filename, bool $compress): array
    {
        $tempFile = storage_path('app/backups/temp/database.sql');

        // Create database dump
        Artisan::call('backup:run', [
            '--only-db' => true,
            '--filename' => $filename,
        ]);

        return [
            'size' => File::exists($tempFile) ? File::size($tempFile) : 0,
        ];
    }

    private function createFilesBackup(string $filename, bool $compress): array
    {
        Artisan::call('backup:run', [
            '--only-files' => true,
            '--filename' => $filename,
        ]);

        return [
            'size' => 0, // Will be calculated after creation
        ];
    }

    private function createFullBackup(string $filename, bool $compress): array
    {
        Artisan::call('backup:run', [
            '--filename' => $filename,
        ]);

        return [
            'size' => 0, // Will be calculated after creation
        ];
    }

    private function restoreDatabase(string $sqlFile): void
    {
        $sql = File::get($sqlFile);
        DB::unprepared($sql);
    }

    private function restoreFiles(string $filesDir): void
    {
        // Copy files back to their original locations
        File::copyDirectory($filesDir, base_path());
    }

    private function isBackupHealthy(string $file): bool
    {
        try {
            $zip = new ZipArchive;
            $backupFile = Storage::disk($this->backupDisk)->path($file);

            if ($zip->open($backupFile) === true) {
                $isHealthy = $zip->numFiles > 0;
                $zip->close();

                return $isHealthy;
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function getNextScheduledRun(): string
    {
        // This would integrate with the task scheduler
        // For now, return a placeholder
        return 'Next run: Daily at 2:00 AM';
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        } else {
            return $bytes.' B';
        }
    }
}
