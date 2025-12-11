<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\TeachingSchedule;
use App\Models\User;
use App\Notifications\LeaveNotification;
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
        $leave = Leave::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveTypeId,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'days_requested' => $daysRequested,
            'reason' => $data['reason'],
            'status' => Leave::STATUS_PENDING,
            'is_emergency' => $data['is_emergency'] ?? false,
            'metadata' => [
                'duration_type' => $data['duration_type'] ?? 'full_day',
                'emergency_contact' => $data['emergency_contact'] ?? null,
                'emergency_phone' => $data['emergency_phone'] ?? null,
            ]
        ]);

        // 6. Send notification to approvers (Admin & Kepala Sekolah)
        $this->notifyApprovers($leave, 'submitted');

        return $leave;
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
                // Skip weekends and holidays
                if ($date->isWeekend() || Holiday::isHoliday($date)) {
                    continue;
                }

                Attendance::updateOrCreate(
                    [
                        'employee_id' => $leave->employee_id,
                        'date' => $date->format('Y-m-d'),
                    ],
                    [
                        'status' => 'leave',
                        'check_in_time' => null,
                        'check_out_time' => null,
                        'metadata' => [
                            'leave_id' => $leave->id,
                            'leave_type' => $leave->leaveType->name ?? 'Leave',
                            'auto_created' => true,
                        ]
                    ]
                );
            }

            // 4. Handle Teaching Schedule Integration (for teachers)
            $this->handleTeachingScheduleOnLeave($leave);

            // 5. Send notification to employee
            $this->notifyEmployee($leave, 'approved', $notes);

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
            'status' => Leave::STATUS_REJECTED,
            'approved_by' => $rejectorId, // Using approved_by for rejector as per schema
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);

        // Send notification to employee
        $this->notifyEmployee($leave, 'rejected', $reason);

        return $leave->fresh();
    }

    /**
     * Handle teaching schedule when leave is approved.
     * Marks affected teaching schedules as on leave.
     */
    protected function handleTeachingScheduleOnLeave(Leave $leave): array
    {
        $employee = $leave->employee;

        // Check if employee is a teacher (guru or guru_honorer)
        if (!in_array($employee->employee_type, ['guru', 'guru_honorer'])) {
            return ['affected_schedules' => 0, 'message' => 'Employee is not a teacher'];
        }

        $affectedSchedules = [];
        $period = CarbonPeriod::create($leave->start_date, $leave->end_date);

        foreach ($period as $date) {
            // Skip weekends
            if ($date->isWeekend()) {
                continue;
            }

            // Skip holidays
            if (Holiday::isHoliday($date)) {
                continue;
            }

            $dayOfWeek = strtolower($date->format('l'));

            // Find teaching schedules for this teacher on this day
            $schedules = TeachingSchedule::where('teacher_id', $employee->id)
                ->where('day_of_week', $dayOfWeek)
                ->where('is_active', true)
                ->where('effective_from', '<=', $date)
                ->where(function ($query) use ($date) {
                    $query->whereNull('effective_until')
                        ->orWhere('effective_until', '>=', $date);
                })
                ->get();

            foreach ($schedules as $schedule) {
                // Store original status in metadata for restoration if leave cancelled
                $metadata = $schedule->metadata ?? [];
                $metadata['leave_affected'] = [
                    'leave_id' => $leave->id,
                    'leave_type' => $leave->leaveType->name ?? 'Cuti',
                    'original_status' => $schedule->status,
                    'affected_date' => $date->format('Y-m-d'),
                ];

                // Update schedule status
                $schedule->update([
                    'substitution_start_date' => $leave->start_date,
                    'substitution_end_date' => $leave->end_date,
                    'substitution_reason' => 'Cuti: ' . $leave->reason,
                    'metadata' => $metadata,
                ]);

                $affectedSchedules[] = [
                    'schedule_id' => $schedule->id,
                    'date' => $date->format('Y-m-d'),
                    'day' => $dayOfWeek,
                    'subject' => $schedule->subject->name ?? 'Unknown',
                    'class' => $schedule->class_name,
                    'time' => $schedule->formatted_time,
                ];
            }
        }

        // Log affected schedules
        if (count($affectedSchedules) > 0) {
            Log::info('Teaching schedules affected by leave approval', [
                'leave_id' => $leave->id,
                'employee_id' => $employee->id,
                'employee_name' => $employee->full_name,
                'affected_count' => count($affectedSchedules),
                'schedules' => $affectedSchedules,
            ]);

            // Store affected schedules info in leave metadata
            $leaveMetadata = $leave->metadata ?? [];
            $leaveMetadata['affected_teaching_schedules'] = $affectedSchedules;
            $leave->update(['metadata' => $leaveMetadata]);
        }

        return [
            'affected_schedules' => count($affectedSchedules),
            'schedules' => $affectedSchedules,
        ];
    }

    /**
     * Cancel a leave request.
     */
    public function cancelRequest(Leave $leave): Leave
    {
        if (!$leave->canBeCancelled()) {
            throw new \Exception('This leave request cannot be cancelled.');
        }

        return DB::transaction(function () use ($leave) {
            // If the leave was approved, we need to restore the balance
            if ($leave->isApproved()) {
                $year = $leave->start_date->year;
                $balance = LeaveBalance::where('employee_id', $leave->employee_id)
                    ->where('leave_type_id', $leave->leave_type_id)
                    ->where('year', $year)
                    ->lockForUpdate()
                    ->first();

                if ($balance) {
                    $balance->addDays($leave->days_requested);
                }

                // Delete auto-created attendance records for this leave
                Attendance::where('employee_id', $leave->employee_id)
                    ->whereDate('date', '>=', $leave->start_date)
                    ->whereDate('date', '<=', $leave->end_date)
                    ->where('status', 'leave')
                    ->where('metadata->leave_id', $leave->id)
                    ->delete();

                // Restore teaching schedules
                $this->restoreTeachingSchedulesOnCancel($leave);
            }

            $leave->update(['status' => Leave::STATUS_CANCELLED]);

            return $leave->fresh();
        });
    }

    /**
     * Restore teaching schedules when leave is cancelled.
     */
    protected function restoreTeachingSchedulesOnCancel(Leave $leave): int
    {
        $restoredCount = 0;

        // Find schedules that were affected by this leave
        $schedules = TeachingSchedule::where('teacher_id', $leave->employee_id)
            ->whereJsonContains('metadata->leave_affected->leave_id', $leave->id)
            ->get();

        foreach ($schedules as $schedule) {
            $metadata = $schedule->metadata ?? [];
            $originalStatus = $metadata['leave_affected']['original_status'] ?? 'scheduled';

            // Remove leave_affected from metadata
            unset($metadata['leave_affected']);

            $schedule->update([
                'status' => $originalStatus,
                'substitute_teacher_id' => null,
                'substitution_start_date' => null,
                'substitution_end_date' => null,
                'substitution_reason' => null,
                'metadata' => $metadata,
            ]);

            $restoredCount++;
        }

        Log::info('Teaching schedules restored after leave cancellation', [
            'leave_id' => $leave->id,
            'restored_count' => $restoredCount,
        ]);

        return $restoredCount;
    }

    /**
     * Get affected teaching schedules for a leave request (preview before approval).
     */
    public function getAffectedTeachingSchedules(Leave $leave): array
    {
        $employee = $leave->employee;

        if (!in_array($employee->employee_type, ['guru', 'guru_honorer'])) {
            return [];
        }

        $affectedSchedules = [];
        $period = CarbonPeriod::create($leave->start_date, $leave->end_date);

        foreach ($period as $date) {
            if ($date->isWeekend()) {
                continue;
            }

            if (Holiday::isHoliday($date)) {
                continue;
            }

            $dayOfWeek = strtolower($date->format('l'));

            $schedules = TeachingSchedule::with(['subject'])
                ->where('teacher_id', $employee->id)
                ->where('day_of_week', $dayOfWeek)
                ->where('is_active', true)
                ->where('effective_from', '<=', $date)
                ->where(function ($query) use ($date) {
                    $query->whereNull('effective_until')
                        ->orWhere('effective_until', '>=', $date);
                })
                ->get();

            foreach ($schedules as $schedule) {
                $affectedSchedules[] = [
                    'schedule_id' => $schedule->id,
                    'date' => $date->format('Y-m-d'),
                    'day' => $schedule->day_label,
                    'subject' => $schedule->subject->name ?? 'Unknown',
                    'class' => $schedule->class_name,
                    'time' => $schedule->formatted_time,
                    'room' => $schedule->room,
                ];
            }
        }

        return $affectedSchedules;
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

    /**
     * Notify approvers (Admin & Kepala Sekolah) about new leave request.
     */
    protected function notifyApprovers(Leave $leave, string $action): void
    {
        try {
            // Load relationships
            $leave->load(['employee', 'leaveType']);

            // Get users with approve_leave permission
            $approvers = User::whereHas('roles', function ($query) {
                $query->whereIn('name', ['super-admin', 'admin', 'kepala-sekolah']);
            })->get();

            foreach ($approvers as $approver) {
                $approver->notify(new LeaveNotification($leave, $action));
            }

            Log::info('Leave notification sent to approvers', [
                'leave_id' => $leave->id,
                'action' => $action,
                'approvers_count' => $approvers->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send leave notification to approvers', [
                'leave_id' => $leave->id,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify employee about leave status change.
     */
    protected function notifyEmployee(Leave $leave, string $action, ?string $notes = null): void
    {
        try {
            // Load relationships
            $leave->load(['employee.user', 'leaveType']);

            $user = $leave->employee->user ?? null;

            if ($user) {
                $user->notify(new LeaveNotification($leave, $action, $notes));

                Log::info('Leave notification sent to employee', [
                    'leave_id' => $leave->id,
                    'action' => $action,
                    'user_id' => $user->id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send leave notification to employee', [
                'leave_id' => $leave->id,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send reminder notification for pending leave requests.
     */
    public function sendPendingReminders(): int
    {
        $reminderCount = 0;

        // Get pending leaves older than 2 days
        $pendingLeaves = Leave::where('status', Leave::STATUS_PENDING)
            ->where('created_at', '<=', now()->subDays(2))
            ->whereNull('metadata->reminder_sent')
            ->get();

        foreach ($pendingLeaves as $leave) {
            $this->notifyApprovers($leave, 'pending_reminder');

            // Mark as reminded
            $metadata = $leave->metadata ?? [];
            $metadata['reminder_sent'] = now()->toISOString();
            $leave->update(['metadata' => $metadata]);

            $reminderCount++;
        }

        Log::info('Pending leave reminders sent', ['count' => $reminderCount]);

        return $reminderCount;
    }
}
