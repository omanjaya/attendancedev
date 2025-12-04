<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeaveService
{
    /**
     * Submit a new leave request.
     */
    public function submitRequest(Employee $employee, array $data)
    {
        // 1. Resolve Leave Type
        $leaveTypeId = $data['type'];
        if (!\Illuminate\Support\Str::isUuid($leaveTypeId)) {
            $leaveType = LeaveType::where('name', $leaveTypeId)
                ->orWhere('code', $leaveTypeId)
                ->firstOrFail();
            $leaveTypeId = $leaveType->id;
        } else {
            $leaveType = LeaveType::findOrFail($leaveTypeId);
        }

        // 2. Calculate Working Days
        $daysRequested = Leave::calculateWorkingDays($data['start_date'], $data['end_date']);
        if ($daysRequested <= 0) {
            throw new \Exception('Leave request must contain at least one working day.');
        }

        // 3. Check Balance
        $year = Carbon::parse($data['start_date'])->year;
        $balance = LeaveBalance::where('employee_id', $employee->id)
            ->where('leave_type_id', $leaveTypeId)
            ->where('year', $year)
            ->first();

        if (!$balance) {
            // Optional: Create default balance if not exists, or throw error
            // For now, let's assume balance must exist
             throw new \Exception('Leave balance not found for this leave type.');
        }

        if (!$balance->canTakeDays($daysRequested)) {
            throw new \Exception("Insufficient leave balance. Remaining: {$balance->remaining_days}, Requested: {$daysRequested}");
        }

        // 4. Check for Conflicts
        $hasConflict = Leave::where('employee_id', $employee->id)
            ->where('status', '!=', Leave::STATUS_REJECTED)
            ->where('status', '!=', Leave::STATUS_CANCELLED)
            ->where(function ($query) use ($data) {
                $query->whereBetween('start_date', [$data['start_date'], $data['end_date']])
                    ->orWhereBetween('end_date', [$data['start_date'], $data['end_date']])
                    ->orWhere(function ($q) use ($data) {
                        $q->where('start_date', '<=', $data['start_date'])
                            ->where('end_date', '>=', $data['end_date']);
                    });
            })
            ->exists();

        if ($hasConflict) {
            throw new \Exception('Leave dates conflict with an existing leave request.');
        }

        // 5. Create Leave Request
        return Leave::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveTypeId,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'days_requested' => $daysRequested,
            'reason' => $data['reason'],
            'status' => Leave::STATUS_PENDING,
            'metadata' => [
                'duration_type' => $data['duration_type'] ?? 'full_day',
                'emergency_contact' => $data['emergency_contact'] ?? null,
                'emergency_phone' => $data['emergency_phone'] ?? null,
            ]
        ]);
    }

    /**
     * Approve a leave request.
     */
    public function approveRequest(Leave $leave, $approverId, $notes = null)
    {
        if (!$leave->isPending()) {
            throw new \Exception('Only pending leave requests can be approved.');
        }

        return DB::transaction(function () use ($leave, $approverId, $notes) {
            // 1. Deduct Balance
            $year = $leave->start_date->year;
            $balance = LeaveBalance::where('employee_id', $leave->employee_id)
                ->where('leave_type_id', $leave->leave_type_id)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if (!$balance) {
                throw new \Exception('Leave balance record not found.');
            }

            if (!$balance->canTakeDays($leave->days_requested)) {
                throw new \Exception('Insufficient leave balance at time of approval.');
            }

            $balance->deductDays($leave->days_requested);

            // 2. Update Leave Status
            $leave->update([
                'status' => Leave::STATUS_APPROVED,
                'approved_by' => $approverId,
                'approved_at' => now(),
                'approval_notes' => $notes,
            ]);

            // 3. Create Attendance Records
            $period = CarbonPeriod::create($leave->start_date, $leave->end_date);
            foreach ($period as $date) {
                if (!$date->isWeekend()) {
                    Attendance::updateOrCreate(
                        [
                            'employee_id' => $leave->employee_id,
                            'date' => $date->format('Y-m-d'),
                        ],
                        [
                            'status' => 'leave',
                            'check_in' => null,
                            'check_out' => null,
                            'metadata' => [
                                'leave_id' => $leave->id,
                                'leave_type' => $leave->leaveType->name ?? 'Leave',
                                'auto_created' => true,
                            ]
                        ]
                    );
                }
            }

            // TODO: Send Notification

            return $leave->fresh();
        });
    }

    /**
     * Reject a leave request.
     */
    public function rejectRequest(Leave $leave, $rejectorId, $reason = null)
    {
        if (! $leave->isPending()) {
            throw new \Exception('Only pending leave requests can be rejected.');
        }

        $leave->update([
            'status' => 'rejected',
            'rejected_by' => $rejectorId,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);

        return $leave->fresh();
    }

    /**
     * Get aggregated leave balances for an employee.
     */
    public function getAggregatedBalance(Employee $employee)
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

        // Fallback defaults if empty (optional, keeping consistent with controller)
        if ($balances->isEmpty()) {
            $summary['annual_total'] = 12;
            $summary['annual_remaining'] = 12;
            $summary['sick_total'] = 14;
            $summary['sick_remaining'] = 14;
        }

        return $summary;
    }
}
