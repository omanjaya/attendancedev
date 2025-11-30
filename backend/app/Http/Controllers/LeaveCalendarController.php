<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LeaveCalendarController extends Controller
{
    /**
     * Display the leave calendar for employees (own leaves only).
     */
    public function index()
    {
        $leaveTypes = LeaveType::active()->get();
        $employees = collect(); // Empty for employee view

        return view('pages.leave.calendar', compact('leaveTypes', 'employees'));
    }

    /**
     * Display the leave calendar for managers (team/all leaves).
     */
    public function manager()
    {
        // Check if user has permission to view all leaves
        if (! auth()->user()->can('approve_leave')) {
            return redirect()
                ->route('leave.calendar')
                ->with('error', 'You do not have permission to view team leaves.');
        }

        $leaveTypes = LeaveType::active()->get();
        $employees = Employee::with('user')->where('is_active', true)->orderBy('first_name')->get();

        return view('pages.leave.calendar-manager', compact('leaveTypes', 'employees'));
    }

    /**
     * Get leave calendar data for employees (own leaves only).
     */
    public function getCalendarData(Request $request)
    {
        $employeeId = auth()->user()->employee?->id;

        if (! $employeeId) {
            return response()->json([]);
        }

        $query = Leave::with(['leaveType', 'employee.user'])->where('employee_id', $employeeId);

        return $this->buildCalendarResponse($query, $request);
    }

    /**
     * Get leave calendar data for managers (team/all leaves).
     */
    public function getManagerCalendarData(Request $request)
    {
        // Check if user has permission to view all leaves
        if (! auth()->user()->can('approve_leave')) {
            return response()->json([]);
        }

        $query = Leave::with(['leaveType', 'employee.user']);

        return $this->buildCalendarResponse($query, $request);
    }

    /**
     * Build calendar response with filters applied.
     */
    private function buildCalendarResponse($query, Request $request)
    {
        // Apply filters
        if ($request->has('employee_id') && $request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->has('leave_type_id') && $request->leave_type_id) {
            $query->where('leave_type_id', $request->leave_type_id);
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Get date range from calendar view
        if ($request->has('start') && $request->has('end')) {
            $startDate = Carbon::parse($request->start)->startOfDay();
            $endDate = Carbon::parse($request->end)->endOfDay();

            $query->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($subQuery) use ($startDate, $endDate) {
                        $subQuery->where('start_date', '<=', $startDate)->where('end_date', '>=', $endDate);
                    });
            });
        }

        $leaves = $query->get();
        $events = [];

        foreach ($leaves as $leave) {
            // Create a single event for the entire leave period
            $events[] = [
                'id' => $leave->id,
                'title' => $leave->employee->full_name.' - '.$leave->leaveType->name,
                'start' => $leave->start_date->format('Y-m-d'),
                'end' => $leave->end_date->addDay()->format('Y-m-d'), // FullCalendar expects end date to be exclusive
                'allDay' => true,
                'backgroundColor' => $this->getLeaveEventColor($leave),
                'borderColor' => $this->getLeaveEventColor($leave),
                'textColor' => '#ffffff',
                'extendedProps' => [
                    'leave_id' => $leave->id,
                    'employee_id' => $leave->employee_id,
                    'employee_name' => $leave->employee->full_name,
                    'leave_type_id' => $leave->leave_type_id,
                    'leave_type_name' => $leave->leaveType->name,
                    'leave_type_code' => $leave->leaveType->code,
                    'status' => $leave->status,
                    'status_color' => $leave->status_color,
                    'days_requested' => $leave->days_requested,
                    'reason' => $leave->reason,
                    'start_date' => $leave->start_date->format('Y-m-d'),
                    'end_date' => $leave->end_date->format('Y-m-d'),
                    'date_range' => $leave->date_range,
                    'duration' => $leave->duration,
                    'is_emergency' => $leave->is_emergency,
                    'approved_by' => $leave->approver ? $leave->approver->full_name : null,
                    'approved_at' => $leave->approved_at ? $leave->approved_at->format('Y-m-d H:i:s') : null,
                    'can_be_cancelled' => $leave->canBeCancelled(),
                ],
            ];
        }

        return response()->json($events);
    }

    /**
     * Get color for leave event based on status and type.
     */
    private function getLeaveEventColor(Leave $leave)
    {
        // Priority 1: Status-based colors
        $statusColors = [
            Leave::STATUS_PENDING => '#ffc107', // Warning yellow
            Leave::STATUS_APPROVED => '#28a745', // Success green
            Leave::STATUS_REJECTED => '#dc3545', // Danger red
            Leave::STATUS_CANCELLED => '#6c757d', // Secondary gray
        ];

        if (isset($statusColors[$leave->status])) {
            return $statusColors[$leave->status];
        }

        // Priority 2: Leave type-based colors (fallback)
        $typeColors = [
            'annual' => '#007bff', // Blue
            'sick' => '#fd7e14', // Orange
            'personal' => '#6f42c1', // Purple
            'maternity' => '#e83e8c', // Pink
            'paternity' => '#20c997', // Teal
            'emergency' => '#dc3545', // Red
            'bereavement' => '#343a40', // Dark
        ];

        $typeCode = strtolower($leave->leaveType->code);

        return $typeColors[$typeCode] ?? '#17a2b8'; // Default info color
    }

    /**
     * Get leave details for modal display.
     */
    public function getLeaveDetails(Leave $leave)
    {
        // Check permissions
        $user = auth()->user();
        $canView = false;

        // Employee can view their own leaves
        if ($user->employee && $user->employee->id === $leave->employee_id) {
            $canView = true;
        }

        // Manager can view all leaves
        if ($user->can('approve_leave')) {
            $canView = true;
        }

        if (! $canView) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $leave->load(['leaveType', 'employee.user', 'approver']);

        return response()->json([
            'success' => true,
            'leave' => [
                'id' => $leave->id,
                'employee_name' => $leave->employee->full_name,
                'leave_type' => $leave->leaveType->name,
                'leave_type_code' => $leave->leaveType->code,
                'status' => $leave->status,
                'status_color' => $leave->status_color,
                'start_date' => $leave->start_date->format('M j, Y'),
                'end_date' => $leave->end_date->format('M j, Y'),
                'date_range' => $leave->date_range,
                'days_requested' => $leave->days_requested,
                'duration' => $leave->duration,
                'reason' => $leave->reason,
                'is_emergency' => $leave->is_emergency,
                'approved_by' => $leave->approver ? $leave->approver->full_name : null,
                'approved_at' => $leave->approved_at
                  ? $leave->approved_at->format('M j, Y \a\t H:i')
                  : null,
                'approval_notes' => $leave->approval_notes,
                'rejection_reason' => $leave->rejection_reason,
                'can_be_cancelled' => $leave->canBeCancelled(),
                'created_at' => $leave->created_at->format('M j, Y \a\t H:i'),
            ],
        ]);
    }

    /**
     * Get leave statistics for dashboard widgets.
     */
    public function getLeaveStats(Request $request)
    {
        $user = auth()->user();

        // For employees, show their own stats
        if (! $user->can('approve_leave') && $user->employee) {
            $employeeId = $user->employee->id;
            $query = Leave::where('employee_id', $employeeId);
        }
        // For managers, show team/all stats
        elseif ($user->can('approve_leave')) {
            $query = Leave::query();
        } else {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Apply date filter (default to current year)
        $year = $request->get('year', date('Y'));
        $query->whereYear('start_date', $year);

        $stats = [
            'total_leaves' => $query->count(),
            'pending_leaves' => $query->clone()->where('status', Leave::STATUS_PENDING)->count(),
            'approved_leaves' => $query->clone()->where('status', Leave::STATUS_APPROVED)->count(),
            'rejected_leaves' => $query->clone()->where('status', Leave::STATUS_REJECTED)->count(),
            'cancelled_leaves' => $query->clone()->where('status', Leave::STATUS_CANCELLED)->count(),
            'active_leaves' => $query
                ->clone()
                ->where('status', Leave::STATUS_APPROVED)
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now())
                ->count(),
            'upcoming_leaves' => $query
                ->clone()
                ->where('status', Leave::STATUS_APPROVED)
                ->where('start_date', '>', now())
                ->count(),
        ];

        return response()->json($stats);
    }
}
