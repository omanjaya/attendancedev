<?php

namespace App\Http\Controllers\Api;

use App\Models\LeaveRequest;
use App\Models\LeaveBalance;
use Illuminate\Http\Request;

class LeaveApiController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = LeaveRequest::query()
            ->with(['employee:id,employee_id,full_name']);

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($type = $request->get('type')) {
            $query->where('leave_type', $type);
        }

        if ($employeeId = $request->get('employee_id')) {
            $query->where('employee_id', $employeeId);
        }

        if ($startDate = $request->get('start_date')) {
            $query->whereDate('start_date', '>=', $startDate);
        }

        if ($endDate = $request->get('end_date')) {
            $query->whereDate('end_date', '<=', $endDate);
        }

        $query->orderBy('created_at', 'desc');

        $perPage = $request->get('per_page', 15);
        $requests = $query->paginate($perPage);

        return $this->paginatedResponse($requests, 'Leave requests retrieved');
    }

    public function show($id)
    {
        $request = LeaveRequest::with(['employee', 'approver'])->find($id);

        if (!$request) {
            return $this->errorResponse('Leave request not found', 404);
        }

        return $this->apiResponse($request, 'Leave request retrieved');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'duration_type' => 'required|in:full_day,half_day,hours',
            'reason' => 'required|string',
            'emergency_contact' => 'nullable|string',
            'emergency_phone' => 'nullable|string',
        ]);

        $user = auth()->user();
        $employee = $user->employee;

        if (!$employee) {
            return $this->errorResponse('Employee not found', 404);
        }

        $leaveRequest = LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type' => $validated['type'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'duration_type' => $validated['duration_type'],
            'reason' => $validated['reason'],
            'emergency_contact' => $validated['emergency_contact'] ?? null,
            'emergency_phone' => $validated['emergency_phone'] ?? null,
            'status' => 'pending',
        ]);

        return $this->apiResponse($leaveRequest, 'Leave request created', 201);
    }

    public function approve(Request $request, $id)
    {
        $leaveRequest = LeaveRequest::find($id);

        if (!$leaveRequest) {
            return $this->errorResponse('Leave request not found', 404);
        }

        $leaveRequest->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'approval_notes' => $request->get('notes'),
        ]);

        return $this->apiResponse($leaveRequest->fresh(), 'Leave request approved');
    }

    public function reject(Request $request, $id)
    {
        $leaveRequest = LeaveRequest::find($id);

        if (!$leaveRequest) {
            return $this->errorResponse('Leave request not found', 404);
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string',
        ]);

        $leaveRequest->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return $this->apiResponse($leaveRequest->fresh(), 'Leave request rejected');
    }

    public function cancel($id)
    {
        $leaveRequest = LeaveRequest::find($id);

        if (!$leaveRequest) {
            return $this->errorResponse('Leave request not found', 404);
        }

        $leaveRequest->update(['status' => 'cancelled']);

        return $this->apiResponse($leaveRequest->fresh(), 'Leave request cancelled');
    }

    public function balance()
    {
        $user = auth()->user();
        $employee = $user->employee;

        if (!$employee) {
            return $this->errorResponse('Employee not found', 404);
        }

        $balance = LeaveBalance::where('employee_id', $employee->id)
            ->where('year', now()->year)
            ->first();

        if (!$balance) {
            $balance = [
                'annual_leave' => 12,
                'sick_leave' => 14,
                'used_annual' => 0,
                'used_sick' => 0,
                'remaining_annual' => 12,
                'remaining_sick' => 14,
            ];
        }

        return $this->apiResponse($balance, 'Leave balance retrieved');
    }

    public function balanceByEmployee($employeeId)
    {
        $balance = LeaveBalance::where('employee_id', $employeeId)
            ->where('year', now()->year)
            ->first();

        if (!$balance) {
            $balance = [
                'annual_leave' => 12,
                'sick_leave' => 14,
                'used_annual' => 0,
                'used_sick' => 0,
            ];
        }

        return $this->apiResponse($balance, 'Leave balance retrieved');
    }

    public function statistics()
    {
        $stats = [
            'total_requests' => LeaveRequest::count(),
            'pending' => LeaveRequest::where('status', 'pending')->count(),
            'approved' => LeaveRequest::where('status', 'approved')->count(),
            'rejected' => LeaveRequest::where('status', 'rejected')->count(),
            'by_type' => LeaveRequest::select('leave_type')
                ->selectRaw('count(*) as count')
                ->groupBy('leave_type')
                ->pluck('count', 'leave_type'),
        ];

        return $this->apiResponse($stats, 'Statistics retrieved');
    }

    public function pending()
    {
        $requests = LeaveRequest::with(['employee:id,employee_id,full_name'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->apiResponse($requests, 'Pending requests retrieved');
    }
}
