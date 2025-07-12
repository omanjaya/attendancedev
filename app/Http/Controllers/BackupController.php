<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;
use Illuminate\Support\Str;
use ZipArchive;

class BackupController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:manage_system');
    }

    /**
     * Display backup management interface
     */
    public function index()
    {
        $backups = $this->getAvailableBackups();
        $backupSchedule = $this->getBackupSchedule();
        $storageInfo = $this->getStorageInfo();

        return view('pages.admin.backup.index', compact('backups', 'backupSchedule', 'storageInfo'));
    }

    /**
     * Create a new backup
     */
    public function create(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:database,files,full',
            'description' => 'nullable|string|max:255',
            'include_uploads' => 'boolean',
            'include_logs' => 'boolean',
        ]);

        try {
            $backupId = Str::uuid();
            $timestamp = now()->format('Y-m-d_H-i-s');
            $backupName = "backup_{$validated['type']}_{$timestamp}";
            
            $result = $this->performBackup($backupId, $validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Backup created successfully',
                'backup_id' => $backupId,
                'backup_name' => $backupName,
                'size' => $result['size'],
                'created_at' => now()->toISOString()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Backup failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download a backup file
     */
    public function download($backupId)
    {
        $backupPath = $this->getBackupPath($backupId);
        
        if (!Storage::disk('backups')->exists($backupPath)) {
            abort(404, 'Backup file not found');
        }

        $metadata = $this->getBackupMetadata($backupId);
        $filename = $metadata['original_name'] ?? "backup_{$backupId}.zip";

        return Storage::disk('backups')->download($backupPath, $filename);
    }

    /**
     * Delete a backup
     */
    public function destroy($backupId)
    {
        try {
            $this->deleteBackup($backupId);
            
            return response()->json([
                'success' => true,
                'message' => 'Backup deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete backup: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restore from backup
     */
    public function restore(Request $request, $backupId)
    {
        $validated = $request->validate([
            'confirm_restore' => 'required|accepted',
            'backup_current' => 'boolean',
        ]);

        try {
            // Create a backup of current state if requested
            if ($validated['backup_current']) {
                $this->performBackup(
                    Str::uuid(),
                    [
                        'type' => 'full',
                        'description' => 'Auto-backup before restore',
                        'include_uploads' => true,
                        'include_logs' => false,
                    ]
                );
            }

            $result = $this->performRestore($backupId);
            
            return response()->json([
                'success' => true,
                'message' => 'System restored successfully',
                'restored_items' => $result['restored_items']
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Restore failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get backup schedule configuration
     */
    public function getSchedule()
    {
        $schedule = config('backup.schedule', [
            'database' => [
                'enabled' => true,
                'frequency' => 'daily',
                'time' => '02:00',
                'retention_days' => 30
            ],
            'files' => [
                'enabled' => true,
                'frequency' => 'weekly',
                'day' => 'sunday',
                'time' => '03:00',
                'retention_days' => 90
            ],
            'full' => [
                'enabled' => true,
                'frequency' => 'monthly',
                'day' => 1,
                'time' => '01:00',
                'retention_days' => 365
            ]
        ]);

        return response()->json(['schedule' => $schedule]);
    }

    /**
     * Update backup schedule
     */
    public function updateSchedule(Request $request)
    {
        $validated = $request->validate([
            'database.enabled' => 'boolean',
            'database.frequency' => 'in:daily,weekly',
            'database.time' => 'date_format:H:i',
            'database.retention_days' => 'integer|min:1|max:365',
            'files.enabled' => 'boolean',
            'files.frequency' => 'in:weekly,monthly',
            'files.day' => 'string',
            'files.time' => 'date_format:H:i',
            'files.retention_days' => 'integer|min:1|max:365',
            'full.enabled' => 'boolean',
            'full.frequency' => 'in:monthly,quarterly',
            'full.day' => 'integer|min:1|max:31',
            'full.time' => 'date_format:H:i',
            'full.retention_days' => 'integer|min:1|max:365',
        ]);

        // Update configuration (in a real implementation, this would update config files or database)
        // For now, we'll store in a temporary location
        file_put_contents(
            storage_path('app/backup_schedule.json'),
            json_encode($validated, JSON_PRETTY_PRINT)
        );

        return response()->json([
            'success' => true,
            'message' => 'Backup schedule updated successfully'
        ]);
    }

    /**
     * Cleanup old backups
     */
    public function cleanup(Request $request)
    {
        $validated = $request->validate([
            'older_than_days' => 'required|integer|min:1',
            'keep_minimum' => 'integer|min:1',
        ]);

        try {
            $deleted = $this->cleanupOldBackups(
                $validated['older_than_days'],
                $validated['keep_minimum'] ?? 5
            );
            
            return response()->json([
                'success' => true,
                'message' => "Cleaned up {$deleted} old backup(s)",
                'deleted_count' => $deleted
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cleanup failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Perform backup operation
     */
    private function performBackup($backupId, $options)
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $type = $options['type'];
        $tempDir = storage_path("app/temp/backup_{$backupId}");
        
        // Create temporary directory
        if (!File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        $items = [];
        
        // Database backup
        if (in_array($type, ['database', 'full'])) {
            $dbBackupPath = $this->createDatabaseBackup($tempDir);
            $items[] = 'database';
        }

        // Files backup
        if (in_array($type, ['files', 'full'])) {
            $this->createFilesBackup($tempDir, $options);
            $items[] = 'files';
        }

        // Create metadata file
        $metadata = [
            'backup_id' => $backupId,
            'type' => $type,
            'description' => $options['description'] ?? '',
            'created_at' => now()->toISOString(),
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'database_driver' => config('database.default'),
            'items' => $items,
            'options' => $options,
        ];
        
        file_put_contents(
            $tempDir . '/metadata.json',
            json_encode($metadata, JSON_PRETTY_PRINT)
        );

        // Create ZIP archive
        $zipPath = storage_path("app/backups/backup_{$backupId}_{$timestamp}.zip");
        $this->createZipArchive($tempDir, $zipPath);

        // Store metadata separately for quick access
        $this->storeBackupMetadata($backupId, $metadata);

        // Cleanup temporary directory
        File::deleteDirectory($tempDir);

        return [
            'backup_id' => $backupId,
            'size' => File::size($zipPath),
            'items' => $items
        ];
    }

    /**
     * Create database backup
     */
    private function createDatabaseBackup($tempDir)
    {
        $dbPath = $tempDir . '/database.sql';
        
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");
        
        if ($config['driver'] === 'pgsql') {
            // PostgreSQL backup
            $command = sprintf(
                'pg_dump --host=%s --port=%s --username=%s --dbname=%s --no-password > %s',
                $config['host'],
                $config['port'],
                $config['username'],
                $config['database'],
                $dbPath
            );
            
            // Set PGPASSWORD environment variable
            putenv("PGPASSWORD={$config['password']}");
            exec($command);
            putenv("PGPASSWORD");
            
        } else {
            // MySQL backup
            $command = sprintf(
                'mysqldump --host=%s --port=%s --user=%s --password=%s %s > %s',
                $config['host'],
                $config['port'],
                $config['username'],
                $config['password'],
                $config['database'],
                $dbPath
            );
            exec($command);
        }

        return $dbPath;
    }

    /**
     * Create files backup
     */
    private function createFilesBackup($tempDir, $options)
    {
        $filesDir = $tempDir . '/files';
        File::makeDirectory($filesDir, 0755, true);

        // Core application files
        $this->copyDirectory(app_path(), $filesDir . '/app');
        $this->copyDirectory(config_path(), $filesDir . '/config');
        $this->copyDirectory(database_path(), $filesDir . '/database');
        $this->copyDirectory(resource_path(), $filesDir . '/resources');
        $this->copyDirectory(public_path(), $filesDir . '/public');

        // Copy important root files
        $rootFiles = ['.env', 'composer.json', 'composer.lock', 'package.json', 'package-lock.json'];
        foreach ($rootFiles as $file) {
            $sourcePath = base_path($file);
            if (File::exists($sourcePath)) {
                File::copy($sourcePath, $filesDir . '/' . $file);
            }
        }

        // Storage files (uploads, etc.)
        if ($options['include_uploads'] ?? false) {
            $this->copyDirectory(
                storage_path('app/public'),
                $filesDir . '/storage/app/public'
            );
        }

        // Logs
        if ($options['include_logs'] ?? false) {
            $this->copyDirectory(
                storage_path('logs'),
                $filesDir . '/storage/logs'
            );
        }
    }

    /**
     * Copy directory recursively
     */
    private function copyDirectory($source, $destination)
    {
        if (!File::exists($source)) {
            return;
        }

        if (!File::exists($destination)) {
            File::makeDirectory($destination, 0755, true);
        }

        $files = File::allFiles($source);
        
        foreach ($files as $file) {
            $relativePath = $file->getRelativePathname();
            $destFile = $destination . '/' . $relativePath;
            $destDir = dirname($destFile);
            
            if (!File::exists($destDir)) {
                File::makeDirectory($destDir, 0755, true);
            }
            
            File::copy($file->getPathname(), $destFile);
        }
    }

    /**
     * Create ZIP archive
     */
    private function createZipArchive($sourceDir, $zipPath)
    {
        $zip = new ZipArchive();
        
        if (!File::exists(dirname($zipPath))) {
            File::makeDirectory(dirname($zipPath), 0755, true);
        }
        
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            throw new \Exception('Cannot create ZIP archive');
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($sourceDir) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();
    }

    /**
     * Perform restore operation
     */
    private function performRestore($backupId)
    {
        $backupPath = $this->getBackupPath($backupId);
        
        if (!Storage::disk('backups')->exists($backupPath)) {
            throw new \Exception('Backup file not found');
        }

        $tempDir = storage_path("app/temp/restore_{$backupId}");
        $zipPath = Storage::disk('backups')->path($backupPath);
        
        // Extract backup
        $this->extractZipArchive($zipPath, $tempDir);
        
        // Read metadata
        $metadataPath = $tempDir . '/metadata.json';
        if (!File::exists($metadataPath)) {
            throw new \Exception('Backup metadata not found');
        }
        
        $metadata = json_decode(File::get($metadataPath), true);
        $restoredItems = [];

        // Restore database
        if (in_array('database', $metadata['items'])) {
            $this->restoreDatabase($tempDir . '/database.sql');
            $restoredItems[] = 'database';
        }

        // Restore files
        if (in_array('files', $metadata['items'])) {
            $this->restoreFiles($tempDir . '/files');
            $restoredItems[] = 'files';
        }

        // Cleanup
        File::deleteDirectory($tempDir);

        return ['restored_items' => $restoredItems];
    }

    /**
     * Extract ZIP archive
     */
    private function extractZipArchive($zipPath, $extractPath)
    {
        $zip = new ZipArchive();
        
        if ($zip->open($zipPath) !== TRUE) {
            throw new \Exception('Cannot open backup archive');
        }

        if (!File::exists($extractPath)) {
            File::makeDirectory($extractPath, 0755, true);
        }

        $zip->extractTo($extractPath);
        $zip->close();
    }

    /**
     * Restore database
     */
    private function restoreDatabase($sqlPath)
    {
        if (!File::exists($sqlPath)) {
            throw new \Exception('Database backup file not found');
        }

        $connection = config('database.default');
        $config = config("database.connections.{$connection}");
        
        if ($config['driver'] === 'pgsql') {
            // PostgreSQL restore
            $command = sprintf(
                'psql --host=%s --port=%s --username=%s --dbname=%s --no-password < %s',
                $config['host'],
                $config['port'],
                $config['username'],
                $config['database'],
                $sqlPath
            );
            
            putenv("PGPASSWORD={$config['password']}");
            exec($command);
            putenv("PGPASSWORD");
            
        } else {
            // MySQL restore
            $command = sprintf(
                'mysql --host=%s --port=%s --user=%s --password=%s %s < %s',
                $config['host'],
                $config['port'],
                $config['username'],
                $config['password'],
                $config['database'],
                $sqlPath
            );
            exec($command);
        }
    }

    /**
     * Restore files
     */
    private function restoreFiles($filesDir)
    {
        if (!File::exists($filesDir)) {
            throw new \Exception('Files backup not found');
        }

        // Restore application files
        $directories = ['app', 'config', 'database', 'resources', 'public'];
        
        foreach ($directories as $dir) {
            $sourcePath = $filesDir . '/' . $dir;
            $destPath = base_path($dir);
            
            if (File::exists($sourcePath)) {
                // Backup current directory
                if (File::exists($destPath)) {
                    File::moveDirectory($destPath, $destPath . '_backup_' . time());
                }
                
                // Restore from backup
                File::copyDirectory($sourcePath, $destPath);
            }
        }

        // Restore root files
        $rootFiles = ['.env', 'composer.json', 'composer.lock', 'package.json', 'package-lock.json'];
        foreach ($rootFiles as $file) {
            $sourcePath = $filesDir . '/' . $file;
            $destPath = base_path($file);
            
            if (File::exists($sourcePath)) {
                File::copy($sourcePath, $destPath);
            }
        }
    }

    /**
     * Get available backups
     */
    private function getAvailableBackups()
    {
        $backups = [];
        $metadataDir = storage_path('app/backup_metadata');
        
        if (!File::exists($metadataDir)) {
            return $backups;
        }

        $metadataFiles = File::files($metadataDir);
        
        foreach ($metadataFiles as $file) {
            if ($file->getExtension() === 'json') {
                $metadata = json_decode(File::get($file->getPathname()), true);
                $backupPath = $this->getBackupPath($metadata['backup_id']);
                
                if (Storage::disk('backups')->exists($backupPath)) {
                    $metadata['size'] = Storage::disk('backups')->size($backupPath);
                    $metadata['size_human'] = $this->formatBytes($metadata['size']);
                    $backups[] = $metadata;
                }
            }
        }

        // Sort by creation date (newest first)
        usort($backups, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        return $backups;
    }

    /**
     * Store backup metadata
     */
    private function storeBackupMetadata($backupId, $metadata)
    {
        $metadataDir = storage_path('app/backup_metadata');
        
        if (!File::exists($metadataDir)) {
            File::makeDirectory($metadataDir, 0755, true);
        }

        file_put_contents(
            $metadataDir . '/' . $backupId . '.json',
            json_encode($metadata, JSON_PRETTY_PRINT)
        );
    }

    /**
     * Get backup metadata
     */
    private function getBackupMetadata($backupId)
    {
        $metadataPath = storage_path("app/backup_metadata/{$backupId}.json");
        
        if (!File::exists($metadataPath)) {
            return null;
        }

        return json_decode(File::get($metadataPath), true);
    }

    /**
     * Get backup file path
     */
    private function getBackupPath($backupId)
    {
        $files = Storage::disk('backups')->files();
        
        foreach ($files as $file) {
            if (Str::contains($file, $backupId)) {
                return $file;
            }
        }

        return null;
    }

    /**
     * Delete backup
     */
    private function deleteBackup($backupId)
    {
        // Delete backup file
        $backupPath = $this->getBackupPath($backupId);
        if ($backupPath && Storage::disk('backups')->exists($backupPath)) {
            Storage::disk('backups')->delete($backupPath);
        }

        // Delete metadata
        $metadataPath = storage_path("app/backup_metadata/{$backupId}.json");
        if (File::exists($metadataPath)) {
            File::delete($metadataPath);
        }
    }

    /**
     * Cleanup old backups
     */
    private function cleanupOldBackups($olderThanDays, $keepMinimum = 5)
    {
        $backups = $this->getAvailableBackups();
        $cutoffDate = Carbon::now()->subDays($olderThanDays);
        $deleted = 0;

        // Sort by date (oldest first)
        usort($backups, function($a, $b) {
            return strtotime($a['created_at']) - strtotime($b['created_at']);
        });

        $totalBackups = count($backups);
        
        foreach ($backups as $backup) {
            $backupDate = Carbon::parse($backup['created_at']);
            
            // Delete if older than cutoff and we have more than minimum backups
            if ($backupDate->lt($cutoffDate) && $totalBackups > $keepMinimum) {
                $this->deleteBackup($backup['backup_id']);
                $deleted++;
                $totalBackups--;
            }
        }

        return $deleted;
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
                'retention_days' => 30
            ],
            'files' => [
                'enabled' => true,
                'frequency' => 'weekly',
                'day' => 'sunday',
                'time' => '03:00',
                'retention_days' => 90
            ],
            'full' => [
                'enabled' => false,
                'frequency' => 'monthly',
                'day' => 1,
                'time' => '01:00',
                'retention_days' => 365
            ]
        ];
    }

    /**
     * Get storage information
     */
    private function getStorageInfo()
    {
        $backupDisk = Storage::disk('backups');
        $files = $backupDisk->files();
        
        $totalSize = 0;
        foreach ($files as $file) {
            $totalSize += $backupDisk->size($file);
        }

        return [
            'total_backups' => count($files),
            'total_size' => $totalSize,
            'total_size_human' => $this->formatBytes($totalSize),
            'available_space' => disk_free_space(storage_path('app/backups')),
            'available_space_human' => $this->formatBytes(disk_free_space(storage_path('app/backups'))),
        ];
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}