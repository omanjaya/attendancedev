<?php

namespace App\Services\Reports;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\Holiday;
use Carbon\Carbon;

class DashboardReportService
{
    /**
     * Get dashboard statistics
     */
    public function getDashboard(): array
    {
        $today = now()->format('Y-m-d');

        // 1. Summary
        $totalEmployees = Employee::count();
        $activeEmployees = Employee::where('is_active', true)->count();
        $inactiveEmployees = Employee::where('is_active', false)->count();

        // Attendance today
        $attendanceToday = Attendance::forDate($today)->get();
        $present = $attendanceToday->where('status', 'present')->count();
        $late = $attendanceToday->where('status', 'late')->count();
        $absent = $attendanceToday->where('status', 'absent')->count();

        // On leave today
        $onLeaveToday = Leave::where('status', 'approved')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->count();

        // Pending leaves
        $pendingLeaves = Leave::where('status', 'pending')->count();

        // Upcoming holidays (next 30 days)
        $upcomingHolidays = Holiday::where('date', '>=', $today)
            ->where('date', '<=', now()->addDays(30))
            ->count();

        // Department stats for summary
        $departmentStats = $this->getDepartmentBreakdown(now()->startOfMonth(), now());
        $byDepartment = [];
        foreach($departmentStats as $stat) {
            $byDepartment[$stat['department']] = $stat['count'];
        }

        // Leave stats
        $leaveStats = [
            'pending' => $pendingLeaves,
            'approved' => Leave::where('status', 'approved')->count(),
            'rejected' => Leave::where('status', 'rejected')->count(),
        ];

        // Schedule stats
        $scheduleStats = [
            'active' => \App\Models\MonthlySchedule::where('is_active', true)->count(),
            'draft' => \App\Models\MonthlySchedule::where('is_active', false)->count(),
            'upcoming' => \App\Models\MonthlySchedule::where('month', '>', now()->month)->count(),
        ];

        // Payroll stats (Real data from payroll_records table if exists, otherwise null)
        $payrollStats = null;
        try {
            if (class_exists(\App\Models\PayrollRecord::class)) {
                $payrollStats = [
                    'pending' => \App\Models\PayrollRecord::where('status', 'pending')->count(),
                    'processed' => \App\Models\PayrollRecord::where('status', 'processed')->count(),
                    'total' => \App\Models\PayrollRecord::where('status', 'processed')->sum('net_salary'),
                ];
            }
        } catch (\Exception $e) {
            $payrollStats = null;
        }

        $summary = [
            'attendance' => [
                'total_employees' => $activeEmployees,
                'present' => $present,
                'late' => $late,
                'absent' => $absent,
                'on_leave' => $onLeaveToday,
                'today' => $present + $late + $absent,
                'attendance_rate' => $activeEmployees > 0 ? min(100, round(($present + $late) / $activeEmployees * 100, 1)) : 0,
            ],
            'employees' => [
                'total' => $totalEmployees,
                'active' => $activeEmployees,
                'inactive' => $inactiveEmployees,
                'on_leave' => $onLeaveToday,
                'by_department' => $byDepartment,
            ],
            'leave' => $leaveStats,
            'schedules' => $scheduleStats,
            'payroll' => $payrollStats,
            'pending_leaves' => $pendingLeaves,
            'upcoming_holidays' => $upcomingHolidays,
        ];

        // 2. Recent Activity
        $recentActivity = $this->getRecentActivity($today);

        // 3. Attendance Trends (Last 7 days)
        $attendanceTrends = $this->getAttendanceTrends();

        return [
            'summary' => $summary,
            'recent_activity' => $recentActivity,
            'attendance_trends' => $attendanceTrends,
            'today_schedule' => null,
        ];
    }

    /**
     * Get summary statistics for a date range
     */
    public function getSummary(string $startDate, string $endDate): array
    {
        $totalEmployees = Employee::where('is_active', true)->count();
        $workDays = $this->calculateWorkDays($startDate, $endDate);

        // PostgreSQL compatible timestamp diff
        $avgHoursQuery = "AVG(EXTRACT(EPOCH FROM (check_out_time::timestamp - check_in_time::timestamp)) / 3600)";
        if (\DB::connection()->getDriverName() === 'sqlite') {
            $avgHoursQuery = "AVG((strftime('%s', check_out_time) - strftime('%s', check_in_time)) / 3600)";
        } elseif (\DB::connection()->getDriverName() === 'mysql') {
            $avgHoursQuery = "AVG(TIMESTAMPDIFF(HOUR, check_in_time, check_out_time))";
        }

        $attendanceStats = Attendance::whereBetween('date', [$startDate, $endDate])
            ->selectRaw("
                COUNT(*) as total_records,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
                {$avgHoursQuery} as avg_hours
            ")
            ->first();

        return [
            'period' => ['start' => $startDate, 'end' => $endDate],
            'total_employees' => $totalEmployees,
            'work_days' => $workDays,
            'attendance' => [
                'total_records' => $attendanceStats->total_records ?? 0,
                'present' => $attendanceStats->present ?? 0,
                'late' => $attendanceStats->late ?? 0,
                'absent' => $attendanceStats->absent ?? 0,
                'rate' => $totalEmployees > 0 && $workDays > 0
                    ? min(100, round(($attendanceStats->present + $attendanceStats->late) / ($totalEmployees * $workDays) * 100, 1))
                    : 0,
            ],
            'avg_work_hours' => round($attendanceStats->avg_hours ?? 0, 1),
        ];
    }

    /**
     * Get recent activity (check-ins, check-outs, leave requests)
     */
    private function getRecentActivity(string $today): array
    {
        $recentActivity = [];

        // Recent check-ins/outs
        $recentAttendances = Attendance::with('employee')
            ->forDate($today)
            ->orderBy('updated_at', 'desc')
            ->take(5)
            ->get();

        foreach($recentAttendances as $att) {
            if ($att->check_in_time && $att->updated_at->diffInMinutes($att->check_in_time) < 5) {
                 $recentActivity[] = [
                    'id' => $att->id,
                    'type' => 'check_in',
                    'employee_id' => $att->employee_id,
                    'employee_name' => $att->employee->full_name ?? 'Unknown',
                    'description' => 'Check-in' . ($att->status == 'late' ? ' (Late)' : ''),
                    'timestamp' => $att->check_in_time->toIsoString(),
                 ];
            } elseif ($att->check_out_time) {
                 $recentActivity[] = [
                    'id' => $att->id,
                    'type' => 'check_out',
                    'employee_id' => $att->employee_id,
                    'employee_name' => $att->employee->full_name ?? 'Unknown',
                    'description' => 'Check-out',
                    'timestamp' => $att->check_out_time->toIsoString(),
                 ];
            }
        }

        // Recent leaves
        $recentLeaves = Leave::with('employee')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        foreach($recentLeaves as $leave) {
             $recentActivity[] = [
                'id' => $leave->id,
                'type' => 'leave_request',
                'employee_id' => $leave->employee_id,
                'employee_name' => $leave->employee->full_name ?? 'Unknown',
                'description' => 'Leave Request: ' . $leave->reason,
                'timestamp' => $leave->created_at->toIsoString(),
             ];
        }

        // Sort and slice
        usort($recentActivity, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });

        return array_slice($recentActivity, 0, 10);
    }

    /**
     * Get attendance trends for last 7 days
     */
    private function getAttendanceTrends(): array
    {
        $attendanceTrends = [];
        for($i=6; $i>=0; $i--) {
            $date = now()->subDays($i);
            $dateStr = $date->format('Y-m-d');
            $stats = Attendance::forDate($dateStr)
                ->selectRaw("
                    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                    SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late,
                    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent
                ")->first();

            $onLeave = Leave::where('status', 'approved')
                ->whereDate('start_date', '<=', $dateStr)
                ->whereDate('end_date', '>=', $dateStr)
                ->count();

            $attendanceTrends[] = [
                'date' => $dateStr,
                'present' => (int)($stats->present ?? 0),
                'late' => (int)($stats->late ?? 0),
                'absent' => (int)($stats->absent ?? 0),
                'on_leave' => $onLeave,
            ];
        }

        return $attendanceTrends;
    }

    /**
     * Get department breakdown
     */
    private function getDepartmentBreakdown($startDate, $endDate): array
    {
        try {
            return Employee::select('department')
                ->selectRaw('COUNT(*) as count')
                ->whereNotNull('department')
                ->groupBy('department')
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
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
