<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceCorrection;
use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class AttendanceCorrectionController extends Controller
{
    /**
     * List correction requests
     * - Employees see only their own
     * - Admins see all
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $query = AttendanceCorrection::with(['employee', 'attendance', 'reviewer']);

        // Filter by role
        if (!$user->hasRole(['admin', 'super-admin', 'Super Admin', 'Admin'])) {
            // Regular employees can only see their own corrections
            $employee = $user->employee;
            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee profile not found',
                ], 404);
            }
            $query->where('employee_id', $employee->id);
        }

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('correction_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('correction_date', '<=', $request->date_to);
        }

        // Filter by employee (admin only)
        if ($request->has('employee_id') && $user->hasRole(['admin', 'super-admin', 'Super Admin', 'Admin'])) {
            $query->where('employee_id', $request->employee_id);
        }

        // Sorting
        $sortField = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        // Pagination
        $perPage = $request->input('per_page', 15);
        $corrections = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $corrections,
        ]);
    }

    /**
     * Get correction statistics (admin only)
     */
    public function statistics(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user->hasRole(['admin', 'super-admin', 'Super Admin', 'Admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $stats = [
            'pending' => AttendanceCorrection::pending()->count(),
            'approved' => AttendanceCorrection::approved()->count(),
            'rejected' => AttendanceCorrection::rejected()->count(),
            'total' => AttendanceCorrection::count(),
            'this_month' => AttendanceCorrection::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Create a new correction request
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee profile not found',
            ], 404);
        }

        $validated = $request->validate([
            'attendance_id' => 'nullable|uuid|exists:attendances,id',
            'correction_date' => 'required|date|before_or_equal:today',
            'correction_type' => 'required|in:check_in,check_out,both,add_missing,delete',
            'requested_check_in' => 'nullable|date_format:H:i',
            'requested_check_out' => 'nullable|date_format:H:i',
            'reason' => 'required|string|min:10|max:1000',
            'supporting_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        // Check for existing pending correction for same date
        $existing = AttendanceCorrection::where('employee_id', $employee->id)
            ->where('correction_date', $validated['correction_date'])
            ->where('status', AttendanceCorrection::STATUS_PENDING)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'You already have a pending correction request for this date',
            ], 422);
        }

        // Get original attendance if exists
        $attendance = null;
        if (!empty($validated['attendance_id'])) {
            $attendance = Attendance::find($validated['attendance_id']);
        } else {
            // Try to find attendance for the date
            $attendance = Attendance::where('employee_id', $employee->id)
                ->whereDate('date', $validated['correction_date'])
                ->first();
        }

        // Validate based on correction type
        if ($validated['correction_type'] === 'add_missing') {
            if ($attendance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Attendance record already exists for this date',
                ], 422);
            }
            if (empty($validated['requested_check_in'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Check-in time is required for adding missing attendance',
                ], 422);
            }
        } elseif ($validated['correction_type'] !== 'add_missing') {
            if (!$attendance) {
                return response()->json([
                    'success' => false,
                    'message' => 'No attendance record found for this date',
                ], 422);
            }
        }

        // Handle file upload
        $documentPath = null;
        if ($request->hasFile('supporting_document')) {
            $documentPath = $request->file('supporting_document')->store(
                'correction-documents/' . $employee->id,
                'public'
            );
        }

        $correction = AttendanceCorrection::create([
            'employee_id' => $employee->id,
            'attendance_id' => $attendance?->id,
            'correction_date' => $validated['correction_date'],
            'correction_type' => $validated['correction_type'],
            'original_check_in' => $attendance?->check_in_time?->format('H:i:s'),
            'original_check_out' => $attendance?->check_out_time?->format('H:i:s'),
            'requested_check_in' => $validated['requested_check_in'] ?? null,
            'requested_check_out' => $validated['requested_check_out'] ?? null,
            'reason' => $validated['reason'],
            'supporting_document' => $documentPath,
            'status' => AttendanceCorrection::STATUS_PENDING,
        ]);

        $correction->load(['employee', 'attendance']);

        Log::info('Attendance correction request created', [
            'correction_id' => $correction->id,
            'employee_id' => $employee->id,
            'type' => $correction->correction_type,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Correction request submitted successfully',
            'data' => $correction,
        ], 201);
    }

    /**
     * Get a specific correction request
     */
    public function show(string $id): JsonResponse
    {
        $user = Auth::user();
        $correction = AttendanceCorrection::with(['employee', 'attendance', 'reviewer'])->find($id);

        if (!$correction) {
            return response()->json([
                'success' => false,
                'message' => 'Correction request not found',
            ], 404);
        }

        // Check permission
        if (!$user->hasRole(['admin', 'super-admin', 'Super Admin', 'Admin'])) {
            if ($user->employee?->id !== $correction->employee_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $correction,
        ]);
    }

    /**
     * Cancel a correction request (by employee)
     */
    public function cancel(string $id): JsonResponse
    {
        $user = Auth::user();
        $correction = AttendanceCorrection::find($id);

        if (!$correction) {
            return response()->json([
                'success' => false,
                'message' => 'Correction request not found',
            ], 404);
        }

        // Only the owner can cancel
        if ($user->employee?->id !== $correction->employee_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        if (!$correction->canBeCancelled()) {
            return response()->json([
                'success' => false,
                'message' => 'This correction request cannot be cancelled',
            ], 422);
        }

        $correction->cancel();

        Log::info('Attendance correction cancelled', [
            'correction_id' => $correction->id,
            'employee_id' => $correction->employee_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Correction request cancelled',
            'data' => $correction->fresh(),
        ]);
    }

    /**
     * Approve a correction request (admin only)
     */
    public function approve(Request $request, string $id): JsonResponse
    {
        $user = Auth::user();

        if (!$user->hasRole(['admin', 'super-admin', 'Super Admin', 'Admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $correction = AttendanceCorrection::find($id);

        if (!$correction) {
            return response()->json([
                'success' => false,
                'message' => 'Correction request not found',
            ], 404);
        }

        if (!$correction->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'This correction request has already been processed',
            ], 422);
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        $correction->approve($user->id, $validated['notes'] ?? null);

        Log::info('Attendance correction approved', [
            'correction_id' => $correction->id,
            'approved_by' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Correction request approved',
            'data' => $correction->fresh(['employee', 'attendance', 'reviewer']),
        ]);
    }

    /**
     * Reject a correction request (admin only)
     */
    public function reject(Request $request, string $id): JsonResponse
    {
        $user = Auth::user();

        if (!$user->hasRole(['admin', 'super-admin', 'Super Admin', 'Admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $correction = AttendanceCorrection::find($id);

        if (!$correction) {
            return response()->json([
                'success' => false,
                'message' => 'Correction request not found',
            ], 404);
        }

        if (!$correction->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'This correction request has already been processed',
            ], 422);
        }

        $validated = $request->validate([
            'notes' => 'required|string|min:10|max:500',
        ]);

        $correction->reject($user->id, $validated['notes']);

        Log::info('Attendance correction rejected', [
            'correction_id' => $correction->id,
            'rejected_by' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Correction request rejected',
            'data' => $correction->fresh(['employee', 'attendance', 'reviewer']),
        ]);
    }

    /**
     * Download supporting document
     */
    public function downloadDocument(string $id): mixed
    {
        $user = Auth::user();
        $correction = AttendanceCorrection::find($id);

        if (!$correction) {
            return response()->json([
                'success' => false,
                'message' => 'Correction request not found',
            ], 404);
        }

        // Check permission
        if (!$user->hasRole(['admin', 'super-admin', 'Super Admin', 'Admin'])) {
            if ($user->employee?->id !== $correction->employee_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }
        }

        if (!$correction->supporting_document) {
            return response()->json([
                'success' => false,
                'message' => 'No document attached',
            ], 404);
        }

        if (!Storage::disk('public')->exists($correction->supporting_document)) {
            return response()->json([
                'success' => false,
                'message' => 'Document file not found',
            ], 404);
        }

        return Storage::disk('public')->download($correction->supporting_document);
    }
}
