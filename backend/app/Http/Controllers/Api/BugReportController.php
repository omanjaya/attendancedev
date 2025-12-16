<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BugReport;
use App\Models\User;
use App\Notifications\NewBugReportNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BugReportController extends Controller
{
    /**
     * Submit a new bug report (any authenticated user)
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'type' => 'required|in:bug,error,suggestion,question',
            'severity' => 'nullable|in:low,medium,high,critical',
            'page_url' => 'nullable|string|max:500',
            'error_message' => 'nullable|string|max:2000',
            'error_stack' => 'nullable|string|max:10000',
            'browser_info' => 'nullable|array',
            'screenshots' => 'nullable|array|max:3',
            'screenshots.*' => 'string', // Base64 encoded images
        ]);

        $user = Auth::user();

        // Process screenshots (base64 to file)
        $screenshotPaths = [];
        if ($request->has('screenshots')) {
            foreach ($request->screenshots as $index => $base64Image) {
                if (empty($base64Image)) continue;

                try {
                    // Extract image data from base64
                    if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $matches)) {
                        $extension = $matches[1];
                        $base64Data = substr($base64Image, strpos($base64Image, ',') + 1);
                        $imageData = base64_decode($base64Data);

                        // Generate filename
                        $filename = 'bug-reports/' . date('Y/m') . '/' . Str::uuid() . '.' . $extension;

                        // Store file
                        Storage::disk('public')->put($filename, $imageData);
                        $screenshotPaths[] = $filename;
                    }
                } catch (\Exception $e) {
                    // Skip invalid images
                    continue;
                }
            }
        }

        // Create bug report
        $bugReport = BugReport::create([
            'user_id' => $user?->id,
            'title' => $request->title,
            'description' => $request->description,
            'type' => $request->type,
            'severity' => $request->severity ?? 'medium',
            'page_url' => $request->page_url,
            'error_message' => $request->error_message,
            'error_stack' => $request->error_stack,
            'browser_info' => $request->browser_info,
            'screenshots' => $screenshotPaths,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Send notification to admins/developers
        $this->notifyDevelopers($bugReport);

        return response()->json([
            'success' => true,
            'message' => 'Laporan bug berhasil dikirim. Terima kasih atas feedback Anda!',
            'data' => [
                'id' => $bugReport->id,
                'title' => $bugReport->title,
                'status' => $bugReport->status,
            ],
        ], 201);
    }

    /**
     * Get my submitted bug reports
     */
    public function myReports(Request $request)
    {
        $user = Auth::user();

        $reports = BugReport::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 10));

        return response()->json([
            'success' => true,
            'data' => $reports,
        ]);
    }

    /**
     * Get all bug reports (Admin only)
     */
    public function index(Request $request)
    {
        $query = BugReport::with(['user:id,name,email']);

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        // Filter by severity
        if ($request->has('severity') && $request->severity) {
            $query->where('severity', $request->severity);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%")
                    ->orWhere('error_message', 'ilike', "%{$search}%");
            });
        }

        $reports = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $reports,
        ]);
    }

    /**
     * Get a specific bug report (Admin only)
     */
    public function show($id)
    {
        $report = BugReport::with(['user:id,name,email', 'resolver:id,name,email'])
            ->findOrFail($id);

        // Add screenshot URLs
        $report->screenshot_urls = collect($report->screenshots ?? [])->map(function ($path) {
            return Storage::url($path);
        });

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Update bug report status (Admin only)
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:open,in_progress,resolved,closed,wont_fix',
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        $report = BugReport::findOrFail($id);
        $user = Auth::user();

        $updateData = [
            'status' => $request->status,
            'admin_notes' => $request->admin_notes ?? $report->admin_notes,
        ];

        // If resolved, set resolver info
        if (in_array($request->status, ['resolved', 'closed', 'wont_fix'])) {
            $updateData['resolved_at'] = now();
            $updateData['resolved_by'] = $user->id;
        }

        $report->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Status laporan berhasil diperbarui',
            'data' => $report->fresh(['user:id,name,email', 'resolver:id,name,email']),
        ]);
    }

    /**
     * Get bug report statistics (Admin only)
     */
    public function statistics()
    {
        $stats = [
            'total' => BugReport::count(),
            'open' => BugReport::where('status', 'open')->count(),
            'in_progress' => BugReport::where('status', 'in_progress')->count(),
            'resolved' => BugReport::where('status', 'resolved')->count(),
            'closed' => BugReport::where('status', 'closed')->count(),
            'by_type' => [
                'bug' => BugReport::where('type', 'bug')->count(),
                'error' => BugReport::where('type', 'error')->count(),
                'suggestion' => BugReport::where('type', 'suggestion')->count(),
                'question' => BugReport::where('type', 'question')->count(),
            ],
            'by_severity' => [
                'critical' => BugReport::where('severity', 'critical')->count(),
                'high' => BugReport::where('severity', 'high')->count(),
                'medium' => BugReport::where('severity', 'medium')->count(),
                'low' => BugReport::where('severity', 'low')->count(),
            ],
            'recent_7_days' => BugReport::where('created_at', '>=', now()->subDays(7))->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Delete a bug report (Admin only)
     */
    public function destroy($id)
    {
        $report = BugReport::findOrFail($id);

        // Delete screenshots
        foreach ($report->screenshots ?? [] as $path) {
            Storage::disk('public')->delete($path);
        }

        $report->delete();

        return response()->json([
            'success' => true,
            'message' => 'Laporan bug berhasil dihapus',
        ]);
    }

    /**
     * Notify developers about new bug report
     */
    private function notifyDevelopers(BugReport $report): void
    {
        try {
            // Get admin/super-admin users to notify
            $admins = User::role(['super-admin', 'admin'])->get();

            // Send notification
            Notification::send($admins, new NewBugReportNotification($report));
        } catch (\Exception $e) {
            // Log error but don't fail the request
            \Log::warning('Failed to notify developers about bug report: ' . $e->getMessage());
        }
    }
}
