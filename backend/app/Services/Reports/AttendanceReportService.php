<?php

namespace App\Services\Reports;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\Holiday;
use Carbon\Carbon;

class AttendanceReportService
{
    /**
     * Get monthly attendance recap with A/I/S/D/C breakdown
     *
     * H = Hadir (Present on time)
     * T = Terlambat (Late)
     * A = Alpha (Absent without notice)
     * I = Izin (Permission)
     * S = Sakit (Sick)
     * D = Dinas (Official duty)
     * C = Cuti (Leave/Vacation)
     */
    public function getMonthlyRecap(int $month, int $year, ?string $departmentFilter = null): array
    {
        // Calculate date range
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Only count up to today if current month
        if ($endDate->isFuture()) {
            $endDate = Carbon::today();
        }

        // Calculate working days (exclude weekends and holidays)
        $holidays = Holiday::whereBetween('date', [$startDate, $endDate])
            ->pluck('date')
            ->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))
            ->toArray();

        $workingDays = $this->calculateWorkingDays($startDate, $endDate, $holidays);

        // Get employees
        $employeesQuery = Employee::where('is_active', true)->orderBy('full_name');

        if ($departmentFilter) {
            $employeesQuery->where('department', $departmentFilter);
        }

        $employees = $employeesQuery->get();

        // Get leave type categories mapping
        $leaveTypeCategories = $this->getLeaveTypeCategories();

        // Process each employee
        $recapData = [];
        $totals = [
            'hadir' => 0,
            'terlambat' => 0,
            'alpha' => 0,
            'izin' => 0,
            'sakit' => 0,
            'dinas' => 0,
            'cuti' => 0,
        ];

        foreach ($employees as $employee) {
            $employeeData = $this->calculateEmployeeMonthlyStats(
                $employee,
                $startDate,
                $endDate,
                $holidays,
                $workingDays,
                $leaveTypeCategories
            );

            $recapData[] = $employeeData;

            // Add to totals
            $totals['hadir'] += $employeeData['hadir'];
            $totals['terlambat'] += $employeeData['terlambat'];
            $totals['alpha'] += $employeeData['alpha'];
            $totals['izin'] += $employeeData['izin'];
            $totals['sakit'] += $employeeData['sakit'];
            $totals['dinas'] += $employeeData['dinas'];
            $totals['cuti'] += $employeeData['cuti'];
        }

        // Calculate overall attendance rate (max 100%)
        $totalEmployees = count($employees);
        $totalPossibleDays = $workingDays * $totalEmployees;
        $totalAttendedDays = $totals['hadir'] + $totals['terlambat'] + $totals['dinas'];
        $overallRate = $totalPossibleDays > 0
            ? min(100, round(($totalAttendedDays / $totalPossibleDays) * 100, 1))
            : 0;

        return [
            'period' => [
                'month' => (int) $month,
                'year' => (int) $year,
                'month_name' => Carbon::create($year, $month, 1)->translatedFormat('F'),
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
            'working_days' => $workingDays,
            'total_employees' => $totalEmployees,
            'holidays_count' => count($holidays),
            'data' => $recapData,
            'totals' => [
                'hadir' => $totals['hadir'],
                'terlambat' => $totals['terlambat'],
                'alpha' => $totals['alpha'],
                'izin' => $totals['izin'],
                'sakit' => $totals['sakit'],
                'dinas' => $totals['dinas'],
                'cuti' => $totals['cuti'],
                'overall_attendance_rate' => $overallRate,
            ],
            'legend' => [
                'H' => 'Hadir (Tepat Waktu)',
                'T' => 'Terlambat',
                'A' => 'Alpha (Tanpa Keterangan)',
                'I' => 'Izin',
                'S' => 'Sakit',
                'D' => 'Dinas Luar',
                'C' => 'Cuti',
            ],
        ];
    }

    /**
     * Get weekly attendance trend
     */
    public function getWeeklyTrend(int $weeks = 4): array
    {
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
                    ? min(100, round((($stats->present ?? 0) + ($stats->late ?? 0)) / $total * 100, 1))
                    : 0,
            ];
        }

        return $data;
    }

    /**
     * Get monthly attendance data for the year
     */
    public function getMonthlyAttendance(int $year): array
    {
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

        return $data;
    }

    /**
     * Calculate individual employee monthly statistics
     */
    private function calculateEmployeeMonthlyStats(
        Employee $employee,
        Carbon $startDate,
        Carbon $endDate,
        array $holidays,
        int $workingDays,
        array $leaveTypeCategories
    ): array {
        // Get attendance records
        $attendances = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        // Count by status
        $hadir = $attendances->where('status', 'present')->count();
        $terlambat = $attendances->where('status', 'late')->count();
        $absent = $attendances->where('status', 'absent')->count();

        // Get approved leaves
        $leaves = Leave::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                  ->orWhereBetween('end_date', [$startDate, $endDate])
                  ->orWhere(function ($q2) use ($startDate, $endDate) {
                      $q2->where('start_date', '<=', $startDate)
                         ->where('end_date', '>=', $endDate);
                  });
            })
            ->with('leaveType')
            ->get();

        // Calculate leave days by category
        $leaveStats = $this->calculateLeaveDaysByCategory(
            $leaves,
            $startDate,
            $endDate,
            $holidays,
            $leaveTypeCategories
        );

        // Calculate alpha (days not accounted for)
        $accountedDays = $hadir + $terlambat + $absent +
                        $leaveStats['izin'] + $leaveStats['sakit'] +
                        $leaveStats['dinas'] + $leaveStats['cuti'];
        $alpha = max(0, $workingDays - $accountedDays);

        // Calculate attendance percentage (max 100%)
        $attendanceRate = $workingDays > 0
            ? min(100, round((($hadir + $terlambat + $leaveStats['dinas']) / $workingDays) * 100, 1))
            : 0;

        return [
            'employee_id' => $employee->id,
            'employee_code' => $employee->employee_code,
            'employee_name' => $employee->full_name,
            'department' => $employee->department ?? '-',
            'hadir' => $hadir,
            'terlambat' => $terlambat,
            'alpha' => $alpha,
            'izin' => $leaveStats['izin'],
            'sakit' => $leaveStats['sakit'],
            'dinas' => $leaveStats['dinas'],
            'cuti' => $leaveStats['cuti'],
            'working_days' => $workingDays,
            'attendance_rate' => $attendanceRate,
        ];
    }

    /**
     * Calculate leave days by category (I/S/D/C)
     */
    private function calculateLeaveDaysByCategory(
        $leaves,
        Carbon $startDate,
        Carbon $endDate,
        array $holidays,
        array $leaveTypeCategories
    ): array {
        $izin = 0;
        $sakit = 0;
        $dinas = 0;
        $cuti = 0;

        foreach ($leaves as $leave) {
            // Calculate overlap days within the month
            $leaveStart = Carbon::parse($leave->start_date);
            $leaveEnd = Carbon::parse($leave->end_date);

            $overlapStart = $leaveStart->lt($startDate) ? $startDate->copy() : $leaveStart->copy();
            $overlapEnd = $leaveEnd->gt($endDate) ? $endDate->copy() : $leaveEnd->copy();

            // Count working days in overlap period
            $leaveDays = $this->calculateWorkingDays($overlapStart, $overlapEnd, $holidays);

            // Categorize
            $category = $leaveTypeCategories[$leave->leave_type_id] ?? 'C';
            switch ($category) {
                case 'I':
                    $izin += $leaveDays;
                    break;
                case 'S':
                    $sakit += $leaveDays;
                    break;
                case 'D':
                    $dinas += $leaveDays;
                    break;
                case 'C':
                default:
                    $cuti += $leaveDays;
                    break;
            }
        }

        return [
            'izin' => $izin,
            'sakit' => $sakit,
            'dinas' => $dinas,
            'cuti' => $cuti,
        ];
    }

    /**
     * Get leave type categories mapping
     */
    private function getLeaveTypeCategories(): array
    {
        $leaveTypeMapping = [
            'izin' => 'I',
            'permission' => 'I',
            'sakit' => 'S',
            'sick' => 'S',
            'dinas' => 'D',
            'duty' => 'D',
            'official' => 'D',
            'cuti' => 'C',
            'leave' => 'C',
            'annual' => 'C',
            'vacation' => 'C',
        ];

        $leaveTypes = \App\Models\LeaveType::all();
        $leaveTypeCategories = [];

        foreach ($leaveTypes as $lt) {
            $code = strtolower($lt->code ?? '');
            $name = strtolower($lt->name ?? '');

            // Try to match to category
            foreach ($leaveTypeMapping as $keyword => $category) {
                if (str_contains($code, $keyword) || str_contains($name, $keyword)) {
                    $leaveTypeCategories[$lt->id] = $category;
                    break;
                }
            }
            // Default to C (Cuti) if no match
            if (!isset($leaveTypeCategories[$lt->id])) {
                $leaveTypeCategories[$lt->id] = 'C';
            }
        }

        return $leaveTypeCategories;
    }

    /**
     * Calculate working days (exclude weekends and holidays)
     */
    private function calculateWorkingDays(Carbon $startDate, Carbon $endDate, array $holidays): int
    {
        $workingDays = 0;
        $current = $startDate->copy();

        while ($current <= $endDate) {
            if (!$current->isWeekend() && !in_array($current->format('Y-m-d'), $holidays)) {
                $workingDays++;
            }
            $current->addDay();
        }

        return $workingDays;
    }
}
