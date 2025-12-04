<?php

namespace App\Http\Controllers\Api;

use App\Models\Leave;
use App\Models\LeaveBalance;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;

class LeaveApiController extends BaseApiController
{
    protected $leaveService;

    public function __construct(\App\Services\LeaveService $leaveService)
    {
        $this->leaveService = $leaveService;
    }

    public function index(Request $request)
    {
        $query = Leave::query()
            ->with(['employee:id,employee_id,full_name', 'leaveType']);

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($type = $request->get('type')) {
            if (\Illuminate\Support\Str::isUuid($type)) {
                $query->where('leave_type_id', $type);
            }
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
        $request = Leave::with(['employee', 'approver', 'leaveType'])->find($id);

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

        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return $this->errorResponse('Employee not found', 404);
        }

        try {
            $leaveRequest = $this->leaveService->submitRequest($employee, $validated);
            return $this->apiResponse($leaveRequest, 'Leave request created', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function approve(Request $request, $id)
    {
        $leaveRequest = Leave::find($id);

        if (!$leaveRequest) {
            return $this->errorResponse('Leave request not found', 404);
        }

        $approver = Auth::user()->employee;
        if (!$approver) {
            return $this->errorResponse('You must be an employee to approve leave requests', 403);
        }

        try {
            $approvedLeave = $this->leaveService->approveRequest($leaveRequest, $approver->id, $request->get('notes'));
            return $this->apiResponse($approvedLeave, 'Leave request approved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function reject(Request $request, $id)
    {
        $leaveRequest = Leave::find($id);

        if (!$leaveRequest) {
            return $this->errorResponse('Leave request not found', 404);
        }

        $rejector = Auth::user()->employee;
        if (!$rejector) {
            return $this->errorResponse('You must be an employee to reject leave requests', 403);
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string',
        ]);

        try {
            $rejectedLeave = $this->leaveService->rejectRequest($leaveRequest, $rejector->id, $validated['rejection_reason']);
            return $this->apiResponse($rejectedLeave, 'Leave request rejected');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function cancel($id)
    {
        $leaveRequest = Leave::find($id);

        if (!$leaveRequest) {
            return $this->errorResponse('Leave request not found', 404);
        }

        // TODO: Add logic to restore balance if cancelling an approved leave (if allowed)
        // For now, just cancel
        $leaveRequest->update(['status' => 'cancelled']);

        return $this->apiResponse($leaveRequest->fresh(), 'Leave request cancelled');
    }

    public function balance()
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return $this->errorResponse('Employee not found', 404);
        }

        $balance = $this->leaveService->getAggregatedBalance($employee);
        return $this->apiResponse($balance, 'Leave balance retrieved');
    }

    public function balanceByEmployee($employeeId)
    {
        $employee = \App\Models\Employee::find($employeeId);

        if (!$employee) {
            return $this->errorResponse('Employee not found', 404);
        }

        $balance = $this->leaveService->getAggregatedBalance($employee);
        return $this->apiResponse($balance, 'Leave balance retrieved');
    }

    public function statistics()
    {
        $stats = [
            'total_requests' => Leave::count(),
            'pending_requests' => Leave::where('status', 'pending')->count(),
            'approved_requests' => Leave::where('status', 'approved')->count(),
            'rejected_requests' => Leave::where('status', 'rejected')->count(),
            'total_days_this_month' => (int) Leave::where('status', 'approved')
                ->whereMonth('start_date', now()->month)
                ->whereYear('start_date', now()->year)
                ->sum('days_requested'),
        ];

        return $this->apiResponse($stats, 'Statistics retrieved');
    }

    public function pending()
    {
        $requests = Leave::with(['employee:id,employee_id,full_name', 'leaveType'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->apiResponse($requests, 'Pending requests retrieved');
    }
}
