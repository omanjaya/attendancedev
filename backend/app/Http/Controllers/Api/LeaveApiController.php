<?php

namespace App\Http\Controllers\Api;

use App\Models\Leave;
use App\Models\LeaveBalance;
use Illuminate\Http\Request;

class LeaveApiController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = Leave::query()
            ->with(['employee:id,employee_id,full_name']);

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($type = $request->get('type')) {
            // Check if type is UUID (leave_type_id) or string (leave_type name)
            // For now assuming it might be passed as leave_type_id or we need to join
            // If the frontend sends 'sick', 'annual' etc, we might need to adjust logic
            // But based on Leave model, it has leave_type_id.
            // Let's assume for now the frontend might send ID or we filter by relation if needed.
            // If the frontend sends a string like 'sick', we might need to look up the ID.
            // However, looking at the previous code, it was 'leave_type'.
            // The Leave model has 'leave_type_id'.
            // Let's assume strict filtering for now or relation filtering.
            // If the frontend sends UUID, use leave_type_id.
            if (\Illuminate\Support\Str::isUuid($type)) {
                $query->where('leave_type_id', $type);
            } else {
                // Fallback or maybe ignore if not UUID, or try to find by name if we had LeaveType model loaded
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
        $request = Leave::with(['employee', 'approver'])->find($id);

        if (!$request) {
            return $this->errorResponse('Leave request not found', 404);
        }

        return $this->apiResponse($request, 'Leave request retrieved');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string', // This might need to be leave_type_id if it's a UUID
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

        // We need to handle 'type' mapping to 'leave_type_id' if necessary
        // For now, let's assume the frontend sends the ID in 'type' or we need to find it.
        // If 'type' is a string like 'annual', we need to find the LeaveType.
        // Let's try to find a LeaveType by name if it's not a UUID.
        $leaveTypeId = $validated['type'];
        if (!\Illuminate\Support\Str::isUuid($leaveTypeId)) {
            $leaveType = \App\Models\LeaveType::where('name', $leaveTypeId)->orWhere('code', $leaveTypeId)->first();
            if ($leaveType) {
                $leaveTypeId = $leaveType->id;
            }
            // If not found and not UUID, this might fail if foreign key is enforced.
        }

        $leaveRequest = Leave::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveTypeId,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            // 'duration_type' => $validated['duration_type'], // Leave model doesn't seem to have duration_type in fillable, check migration/model
            'days_requested' => \App\Models\Leave::calculateWorkingDays($validated['start_date'], $validated['end_date']), // Auto calc
            'reason' => $validated['reason'],
            // 'emergency_contact' => $validated['emergency_contact'] ?? null, // Not in fillable
            // 'emergency_phone' => $validated['emergency_phone'] ?? null, // Not in fillable
            'status' => 'pending',
            'metadata' => [
                'duration_type' => $validated['duration_type'],
                'emergency_contact' => $validated['emergency_contact'] ?? null,
                'emergency_phone' => $validated['emergency_phone'] ?? null,
            ]
        ]);

        return $this->apiResponse($leaveRequest, 'Leave request created', 201);
    }

    public function approve(Request $request, $id)
    {
        $leaveRequest = Leave::find($id);

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
        $leaveRequest = Leave::find($id);

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
        $leaveRequest = Leave::find($id);

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

        return $this->getAggregatedBalance($employee);
    }

    public function balanceByEmployee($employeeId)
    {
        $employee = \App\Models\Employee::find($employeeId);

        if (!$employee) {
            return $this->errorResponse('Employee not found', 404);
        }

        return $this->getAggregatedBalance($employee);
    }

    private function getAggregatedBalance($employee)
    {
        $balances = LeaveBalance::with('leaveType')
            ->where('employee_id', $employee->id)
            ->where('year', now()->year)
            ->get();

        // Initialize default structure
        $summary = [
            'id' => $employee->id,
            'employee_id' => $employee->id,
            'employee_name' => $employee->full_name,
            'year' => now()->year,
            'annual_total' => 0,
            'annual_used' => 0,
            'annual_remaining' => 0,
            'sick_total' => 0,
            'sick_used' => 0,
            'sick_remaining' => 0,
            'special_total' => 0,
            'special_used' => 0,
            'special_remaining' => 0,
            'carry_forward' => 0,
            'updated_at' => now()->toISOString(),
        ];

        foreach ($balances as $balance) {
            $code = strtolower($balance->leaveType->code ?? '');

            if (str_contains($code, 'annual') || $code === 'al' || $code === 'cuti_tahunan') {
                $summary['annual_total'] += $balance->allocated_days;
                $summary['annual_used'] += $balance->used_days;
                $summary['annual_remaining'] += $balance->remaining_days;
                $summary['carry_forward'] += $balance->carried_forward;
            } elseif (str_contains($code, 'sick') || $code === 'sl' || $code === 'sakit') {
                $summary['sick_total'] += $balance->allocated_days;
                $summary['sick_used'] += $balance->used_days;
                $summary['sick_remaining'] += $balance->remaining_days;
            } elseif (str_contains($code, 'special') || $code === 'spl' || $code === 'cuti_khusus') {
                $summary['special_total'] += $balance->allocated_days;
                $summary['special_used'] += $balance->used_days;
                $summary['special_remaining'] += $balance->remaining_days;
            }
        }

        // If no balances found, try to set defaults based on policy (optional)
        if ($balances->isEmpty()) {
            $summary['annual_total'] = 12;
            $summary['annual_remaining'] = 12;
            $summary['sick_total'] = 14;
            $summary['sick_remaining'] = 14;
        }

        return $this->apiResponse($summary, 'Leave balance retrieved');
    }

    public function statistics()
    {
        $stats = [
            'total_requests' => Leave::count(),
            'pending' => Leave::where('status', 'pending')->count(),
            'approved' => Leave::where('status', 'approved')->count(),
            'rejected' => Leave::where('status', 'rejected')->count(),
            // 'by_type' => Leave::select('leave_type_id') // Group by leave_type_id
            //     ->selectRaw('count(*) as count')
            //     ->groupBy('leave_type_id')
            //     ->pluck('count', 'leave_type_id'),
        ];

        return $this->apiResponse($stats, 'Statistics retrieved');
    }

    public function pending()
    {
        $requests = Leave::with(['employee:id,employee_id,full_name'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->apiResponse($requests, 'Pending requests retrieved');
    }
}
