<?php

namespace App\Http\Controllers\Api;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Leave;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportsApiController extends BaseApiController
{
    public function data(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        $data = [
            'attendance_summary' => $this->getAttendanceSummary($startDate, $endDate),
            'monthly_trend' => $this->getMonthlyTrend($startDate, $endDate),
            'department_breakdown' => $this->getDepartmentBreakdown($startDate, $endDate),
            'leave_distribution' => $this->getLeaveDistribution($startDate, $endDate),
        ];

        return $this->apiResponse($data, 'Report data retrieved');
    }

    public function summary(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        $totalEmployees = Employee::where('is_active', true)->count();
        $workDays = $this->calculateWorkDays($startDate, $endDate);

        // SQLite compatible timestamp diff
        $avgHoursQuery = "AVG((strftime('%s', check_out_time) - strftime('%s', check_in_time)) / 3600)";
        if (DB::connection()->getDriverName() !== 'sqlite') {
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

        $summary = [
            'period' => ['start' => $startDate, 'end' => $endDate],
            'total_employees' => $totalEmployees,
            'work_days' => $workDays,
            'attendance' => [
                'total_records' => $attendanceStats->total_records ?? 0,
                'present' => $attendanceStats->present ?? 0,
                'late' => $attendanceStats->late ?? 0,
                'absent' => $attendanceStats->absent ?? 0,
                'rate' => $totalEmployees > 0 && $workDays > 0
                    ? round(($attendanceStats->present + $attendanceStats->late) / ($totalEmployees * $workDays) * 100, 1)
                    : 0,
            ],
            'avg_work_hours' => round($attendanceStats->avg_hours ?? 0, 1),
        ];

        return $this->apiResponse($summary, 'Summary retrieved');
    }

    public function monthlyAttendance(Request $request)
    {
        $year = $request->get('year', now()->year);
        $months = range(1, 12);

        $data = [];

        foreach ($months as $month) {
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();

            $stats = Attendance::whereBetween('date', [$startDate, $endDate])
                ->selectRaw("
                    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                    SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late,
                    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent
                ")
                ->first();

            $data[] = [
                'month' => $startDate->format('M'),
                'month_num' => $month,
                'present' => $stats->present ?? 0,
                'late' => $stats->late ?? 0,
                'absent' => $stats->absent ?? 0,
            ];
        }

        return $this->apiResponse($data, 'Monthly attendance retrieved');
    }

    public function weeklyTrend(Request $request)
    {
        $weeks = $request->get('weeks', 4);
        $data = [];

        for ($i = $weeks - 1; $i >= 0; $i--) {
            $weekStart = now()->subWeeks($i)->startOfWeek();
            $weekEnd = $weekStart->copy()->endOfWeek();

            $stats = Attendance::whereBetween('date', [$weekStart, $weekEnd])
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                    SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late,
                    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent
                ")
                ->first();

            $total = ($stats->present ?? 0) + ($stats->late ?? 0) + ($stats->absent ?? 0);

            $data[] = [
                'week' => 'W' . $weekStart->weekOfYear,
                'week_start' => $weekStart->format('Y-m-d'),
                'attendance_rate' => $total > 0
                    ? round((($stats->present ?? 0) + ($stats->late ?? 0)) / $total * 100, 1)
                    : 0,
            ];
        }

        return $this->apiResponse($data, 'Weekly trend retrieved');
    }

    public function departmentStats(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        // Check if department column exists, otherwise return empty or mock
        try {
            $departments = Employee::select('department')
                ->distinct()
                ->whereNotNull('department')
                ->pluck('department');
        } catch (\Exception $e) {
            // Fallback if department column doesn't exist
            return $this->apiResponse([], 'Department stats not available (column missing)');
        }

        $data = [];

        foreach ($departments as $dept) {
            $employeeIds = Employee::where('department', $dept)->pluck('id');

            $stats = Attendance::whereIn('employee_id', $employeeIds)
                ->whereBetween('date', [$startDate, $endDate])
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                    SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late
                ")
                ->first();

            $total = $stats->total ?? 1;

            $data[] = [
                'department' => $dept,
                'employee_count' => $employeeIds->count(),
                'attendance_rate' => round((($stats->present ?? 0) + ($stats->late ?? 0)) / max($total, 1) * 100, 1),
                'present' => $stats->present ?? 0,
                'late' => $stats->late ?? 0,
            ];
        }

        return $this->apiResponse($data, 'Department stats retrieved');
    }

    public function leaveStats(Request $request)
    {
        $year = $request->get('year', now()->year);

        // SQLite compatible datediff
        $dateDiffQuery = "(julianday(end_date) - julianday(start_date) + 1)";
        if (DB::connection()->getDriverName() !== 'sqlite') {
            $dateDiffQuery = "DATEDIFF(end_date, start_date) + 1";
        }

        $stats = Leave::whereYear('start_date', $year)
            ->where('status', 'approved')
            ->select('leave_type_id') // Use leave_type_id
            ->selectRaw('COUNT(*) as count')
            ->selectRaw("SUM({$dateDiffQuery}) as total_days")
            ->groupBy('leave_type_id')
            ->with('leaveType') // Load relationship to get name
            ->get()
            ->map(function ($item) {
                return [
                    'leave_type' => $item->leaveType->name ?? 'Unknown',
                    'count' => $item->count,
                    'total_days' => $item->total_days
                ];
            });

        return $this->apiResponse($stats, 'Leave stats retrieved');
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:attendance,leave,payroll,summary',
            'format' => 'required|in:pdf,excel',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'filters' => 'nullable|array',
        ]);

        // For now, return a placeholder - actual report generation would be implemented
        $report = [
            'id' => uniqid('report_'),
            'type' => $validated['type'],
            'format' => $validated['format'],
            'status' => 'generating',
            'created_at' => now(),
        ];

        return $this->apiResponse($report, 'Report generation started', 201);
    }

    public function templates()
    {
        $templates = [
            ['id' => '1', 'name' => 'Laporan Kehadiran Bulanan', 'type' => 'attendance', 'description' => 'Rekap kehadiran per bulan'],
            ['id' => '2', 'name' => 'Laporan Cuti', 'type' => 'leave', 'description' => 'Rekap pengajuan cuti'],
            ['id' => '3', 'name' => 'Laporan Ringkasan', 'type' => 'summary', 'description' => 'Ringkasan keseluruhan'],
        ];

        return $this->apiResponse($templates, 'Templates retrieved');
    }

    public function generatedReports()
    {
        // For now return empty - would fetch from a reports table
        return $this->apiResponse([], 'Generated reports retrieved');
    }

    private function getAttendanceSummary($startDate, $endDate)
    {
        return Attendance::whereBetween('date', [$startDate, $endDate])
            ->selectRaw("
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
                SUM(CASE WHEN status = 'leave' THEN 1 ELSE 0 END) as on_leave
            ")
            ->first();
    }

    private function getMonthlyTrend($startDate, $endDate)
    {
        $monthQuery = "strftime('%Y-%m', date)";
        if (DB::connection()->getDriverName() !== 'sqlite') {
            $monthQuery = "DATE_FORMAT(date, '%Y-%m')";
        }

        return Attendance::whereBetween('date', [$startDate, $endDate])
            ->selectRaw("{$monthQuery} as month, COUNT(*) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->get();
    }

    private function getDepartmentBreakdown($startDate, $endDate)
    {
        try {
            return Employee::select('department')
                ->selectRaw('COUNT(*) as count')
                ->whereNotNull('department')
                ->groupBy('department')
                ->get();
        } catch (\Exception $e) {
            return [];
        }
    }

    private function getLeaveDistribution($startDate, $endDate)
    {
        return Leave::whereBetween('start_date', [$startDate, $endDate])
            ->select('leave_type_id')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('leave_type_id')
            ->with('leaveType')
            ->get()
            ->map(function ($item) {
                return [
                    'leave_type' => $item->leaveType->name ?? 'Unknown',
                    'count' => $item->count
                ];
            });
    }

    private function calculateWorkDays($startDate, $endDate)
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
