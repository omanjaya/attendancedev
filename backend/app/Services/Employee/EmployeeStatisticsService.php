<?php

namespace App\Services\Employee;

use App\Models\Employee;
use App\Models\Attendance;
use App\Models\Leave;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EmployeeStatisticsService
{
    /**
     * Get employee statistics
     */
    public function getStatistics(): array
    {
        return [
            'total' => Employee::count(),
            'active' => Employee::where('is_active', true)->count(),
            'inactive' => Employee::where('is_active', false)->count(),
            'by_type' => Employee::select('employee_type', DB::raw('count(*) as count'))
                ->groupBy('employee_type')
                ->pluck('count', 'employee_type')
                ->toArray(),
        ];
    }

    /**
     * Get employee dashboard data
     */
    public function getDashboard(Employee $employee): array
    {
        $today = now();
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        return [
            'attendance' => $this->getAttendanceStats($employee, $startOfMonth, $endOfMonth, $today),
            'leave' => $this->getLeaveStats($employee, $today),
            'schedule' => $this->getScheduleData($employee, $today),
            'payroll' => $this->getPayrollData($employee, $today),
        ];
    }

    /**
     * Get attendance statistics for employee
     */
    private function getAttendanceStats(Employee $employee, Carbon $startOfMonth, Carbon $endOfMonth, Carbon $today): array
    {
        $stats = [
            'thisMonth' => Attendance::where('employee_id', $employee->id)
                ->whereBetween('date', [$startOfMonth, $endOfMonth])
                ->count(),
            'present' => Attendance::where('employee_id', $employee->id)
                ->whereBetween('date', [$startOfMonth, $endOfMonth])
                ->where('status', 'present')
                ->count(),
            'late' => Attendance::where('employee_id', $employee->id)
                ->whereBetween('date', [$startOfMonth, $endOfMonth])
                ->where('status', 'late')
                ->count(),
            'absent' => Attendance::where('employee_id', $employee->id)
                ->whereBetween('date', [$startOfMonth, $endOfMonth])
                ->where('status', 'absent')
                ->count(),
            'todayStatus' => null,
            'checkIn' => null,
            'checkOut' => null,
        ];

        $todayAttendance = Attendance::where('employee_id', $employee->id)
            ->forDate($today)
            ->first();

        if ($todayAttendance) {
            $stats['todayStatus'] = $todayAttendance->check_out_time ? 'checked-out' : 'checked-in';
            $stats['checkIn'] = $todayAttendance->check_in_time ? $todayAttendance->check_in_time->format('H:i') : null;
            $stats['checkOut'] = $todayAttendance->check_out_time ? $todayAttendance->check_out_time->format('H:i') : null;
        }

        return $stats;
    }

    /**
     * Get leave statistics for employee
     */
    private function getLeaveStats(Employee $employee, Carbon $today): array
    {
        $annualLeave = 12; // Default
        $usedLeave = Leave::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereYear('start_date', $today->year)
            ->count();

        $pendingLeave = Leave::where('employee_id', $employee->id)
            ->where('status', 'pending')
            ->count();

        return [
            'balance' => $annualLeave - $usedLeave,
            'used' => $usedLeave,
            'pending' => $pendingLeave,
        ];
    }

    /**
     * Get schedule data for employee
     */
    private function getScheduleData(Employee $employee, Carbon $today): array
    {
        $todaySchedule = $employee->getEffectiveScheduleForDate($today);

        $todayShift = 'Tidak Ada Jadwal';
        $todayTime = '-';
        $canAttend = $todaySchedule['can_attend'] ?? false;

        if ($todaySchedule['schedule_type'] === 'holiday') {
            $todayShift = 'Libur: ' . ($todaySchedule['holiday_name'] ?? 'Hari Libur');
            $todayTime = 'Libur';
        } elseif ($todaySchedule['schedule_type'] === 'teaching_override') {
            $todayShift = 'Mengajar';
            $todayTime = ($todaySchedule['start_time'] ? $todaySchedule['start_time']->format('H:i') : '-') . ' - ' . ($todaySchedule['end_time'] ? $todaySchedule['end_time']->format('H:i') : '-');
        } elseif ($todaySchedule['schedule_type'] === 'base_schedule') {
            $todayShift = 'Regular';
            $todayTime = ($todaySchedule['start_time'] ? $todaySchedule['start_time']->format('H:i') : '-') . ' - ' . ($todaySchedule['end_time'] ? $todaySchedule['end_time']->format('H:i') : '-');
        } elseif ($todaySchedule['schedule_type'] === 'no_teaching') {
            $todayShift = 'Tidak Ada Jadwal Mengajar';
            $todayTime = '-';
        }

        $nextShift = null;

        // Find next working day
        for ($i = 1; $i <= 7; $i++) {
            $nextDate = $today->copy()->addDays($i);
            $schedule = $employee->getEffectiveScheduleForDate($nextDate);

            if (($schedule['can_attend'] ?? false) && ($schedule['working_hours'] > 0 || $schedule['schedule_type'] === 'teaching_override')) {
                $shiftName = $schedule['schedule_type'] === 'teaching_override' ? 'Mengajar' : 'Regular';

                $nextShift = [
                    'date' => $nextDate->isoFormat('dddd, D MMMM'),
                    'shift' => $shiftName,
                    'time' => ($schedule['start_time'] ? $schedule['start_time']->format('H:i') : '-') . ' - ' . ($schedule['end_time'] ? $schedule['end_time']->format('H:i') : '-'),
                ];
                break;
            }
        }

        return [
            'today' => [
                'shift' => $todayShift,
                'time' => $todayTime,
                'location' => $employee->location->name ?? 'Office',
                'can_attend' => $canAttend,
                'message' => $todaySchedule['message'] ?? '',
                'schedule_type' => $todaySchedule['schedule_type'] ?? 'none',
            ],
            'nextShift' => $nextShift,
        ];
    }

    /**
     * Get payroll data for employee
     */
    private function getPayrollData(Employee $employee, Carbon $today): array
    {
        return [
            'lastPayment' => [
                'amount' => $employee->salary_amount ?? 0,
                'date' => $today->copy()->subMonth()->endOfMonth()->format('Y-m-d'),
                'status' => 'paid'
            ],
            'nextPayment' => [
                'date' => $today->copy()->endOfMonth()->format('Y-m-d'),
                'estimated' => $employee->salary_amount ?? 0
            ]
        ];
    }
}
