<?php

namespace App\Services\Reports;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Leave;
use Carbon\Carbon;

class EmployeeReportService
{
    /**
     * Get attendance summary for a specific employee
     */
    public function getMyAttendanceSummary(Employee $employee, string $startDate, string $endDate): array
    {
        // Calculate work days
        $workDays = $this->calculateWorkDays($startDate, $endDate);

        // Get employee's attendance records
        $attendances = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        // Calculate statistics
        $present = $attendances->where('status', 'present')->count();
        $late = $attendances->where('status', 'late')->count();
        $absent = $attendances->where('status', 'absent')->count();
        $totalRecords = $attendances->count();

        // Calculate average work hours
        $totalHours = $attendances->where('total_hours', '>', 0)->sum('total_hours');
        $avgHours = $totalRecords > 0 ? round($totalHours / $totalRecords, 1) : 0;

        // Attendance rate (max 100%)
        $attendanceRate = $workDays > 0
            ? min(100, round((($present + $late) / $workDays) * 100, 1))
            : 0;

        // Get recent attendance (last 7 days)
        $recentAttendance = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [now()->subDays(6), now()])
            ->orderBy('date', 'desc')
            ->get()
            ->map(function ($att) {
                return [
                    'date' => $att->date,
                    'check_in' => $att->check_in_time ? Carbon::parse($att->check_in_time)->format('H:i') : null,
                    'check_out' => $att->check_out_time ? Carbon::parse($att->check_out_time)->format('H:i') : null,
                    'status' => $att->status,
                    'work_hours' => $att->total_hours ? round($att->total_hours, 1) : 0,
                    'late_duration' => $att->late_duration_minutes ?? 0,
                ];
            });

        // Get current month attendance by date
        $monthlyAttendance = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()])
            ->orderBy('date')
            ->get()
            ->map(function ($att) {
                return [
                    'date' => $att->date,
                    'day' => Carbon::parse($att->date)->format('D'),
                    'status' => $att->status,
                    'check_in' => $att->check_in_time ? Carbon::parse($att->check_in_time)->format('H:i') : null,
                    'check_out' => $att->check_out_time ? Carbon::parse($att->check_out_time)->format('H:i') : null,
                ];
            });

        return [
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
                'work_days' => $workDays,
            ],
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->full_name,
                'employee_code' => $employee->employee_code,
            ],
            'statistics' => [
                'total_records' => $totalRecords,
                'present' => $present,
                'late' => $late,
                'absent' => $absent,
                'attendance_rate' => $attendanceRate,
                'avg_work_hours' => $avgHours,
            ],
            'recent_attendance' => $recentAttendance,
            'monthly_calendar' => $monthlyAttendance,
        ];
    }

    /**
     * Calculate work days between two dates
     */
    private function calculateWorkDays(string $startDate, string $endDate): int
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $workDays = 0;

        while ($start <= $end) {
            if (!$start->isWeekend()) {
                $workDays++;
            }
            $start->addDay();
        }

        return $workDays;
    }
}
