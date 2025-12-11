<?php

namespace App\Http\Controllers;

use App\Services\BackupService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class BackupController extends Controller
{
    use ApiResponseTrait;

    private BackupService $backupService;

    public function __construct(BackupService $backupService)
    {
        $this->backupService = $backupService;
        $this->middleware('auth');
        $this->middleware('permission:manage_system_settings');
    }

    /**
     * Display backup management interface
     */
    public function index()
    {
        $backups = $this->backupService->getAvailableBackups();
        $backupSchedule = $this->backupService->getBackupSchedule();
        $storageInfo = $this->backupService->getStorageInfo();

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
            $result = $this->backupService->createBackup($validated);

            if ($result['success']) {
                return $this->createdResponse($result, 'Backup created successfully');
            } else {
                return $this->errorResponse($result['error'] ?? 'Backup creation failed');
            }
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Backup creation failed: '.$e->getMessage());
        }
    }

    /**
     * Delete a backup
     */
    public function delete(Request $request)
    {
        $validated = $request->validate([
            'filename' => 'required|string',
        ]);

        try {
            $result = $this->backupService->deleteBackup($validated['filename']);

            if ($result) {
                return $this->deletedResponse('Backup deleted successfully');
            } else {
                return $this->errorResponse('Backup not found or could not be deleted');
            }
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Backup deletion failed: '.$e->getMessage());
        }
    }

    /**
     * Download a backup
     */
    public function download(Request $request)
    {
        $validated = $request->validate([
            'filename' => 'required|string',
        ]);

        try {
            $filePath = $this->backupService->downloadBackup($validated['filename']);

            if ($filePath && file_exists($filePath)) {
                return response()->download($filePath);
            } else {
                return $this->notFoundResponse('Backup file not found');
            }
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Backup download failed: '.$e->getMessage());
        }
    }

    /**
     * Restore from backup
     */
    public function restore(Request $request)
    {
        $validated = $request->validate([
            'filename' => 'required|string',
            'confirm' => 'required|boolean|accepted',
        ]);

        try {
            $result = $this->backupService->restoreBackup($validated['filename']);

            if ($result['success']) {
                return $this->successResponse(null, $result['message']);
            } else {
                return $this->errorResponse($result['error'] ?? 'Backup restore failed');
            }
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Backup restore failed: '.$e->getMessage());
        }
    }
}
