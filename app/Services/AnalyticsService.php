<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\Payroll;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class AnalyticsService
{
    /**
     * Get comprehensive dashboard analytics
     */
    public function getDashboardAnalytics($dateRange = null)
    {
        $startDate = $dateRange['start'] ?? Carbon::now()->startOfMonth();
        $endDate = $dateRange['end'] ?? Carbon::now()->endOfMonth();

        return [
            'kpis' => $this->getKPIs($startDate, $endDate),
            'attendance_overview' => $this->getAttendanceOverview($startDate, $endDate),
            'leave_overview' => $this->getLeaveOverview($startDate, $endDate),
            'payroll_overview' => $this->getPayrollOverview($startDate, $endDate),
            'employee_performance' => $this->getEmployeePerformance($startDate, $endDate),
            'trends' => $this->getTrends($startDate, $endDate),
        ];
    }

    /**
     * Get Key Performance Indicators
     */
    public function getKPIs($startDate, $endDate)
    {
        $totalEmployees = Employee::where('is_active', true)->count();
        $workingDays = $this->getWorkingDays($startDate, $endDate);

        // Attendance KPIs
        $attendanceRecords = Attendance::whereBetween('date', [$startDate, $endDate])->get();
        $totalAttendanceRecords = $attendanceRecords->count();
        $presentRecords = $attendanceRecords->whereIn('status', ['present', 'late'])->count();
        $lateRecords = $attendanceRecords->where('status', 'late')->count();
        $absentRecords = $attendanceRecords->where('status', 'absent')->count();
        
        $attendanceRate = $totalAttendanceRecords > 0 ? ($presentRecords / $totalAttendanceRecords) * 100 : 0;
        $punctualityRate = $presentRecords > 0 ? (($presentRecords - $lateRecords) / $presentRecords) * 100 : 0;
        $absenteeismRate = $totalAttendanceRecords > 0 ? ($absentRecords / $totalAttendanceRecords) * 100 : 0;

        // Productivity KPIs
        $totalHours = $attendanceRecords->sum('total_hours');
        $averageHoursPerEmployee = $totalEmployees > 0 ? $totalHours / $totalEmployees : 0;
        $averageHoursPerDay = $workingDays > 0 ? $totalHours / ($workingDays * $totalEmployees) : 0;

        // Leave KPIs
        $leaveRequests = Leave::whereBetween('start_date', [$startDate, $endDate])->get();
        $approvedLeaves = $leaveRequests->where('status', 'approved')->count();
        $pendingLeaves = $leaveRequests->where('status', 'pending')->count();
        $rejectedLeaves = $leaveRequests->where('status', 'rejected')->count();
        $leaveApprovalRate = $leaveRequests->count() > 0 ? ($approvedLeaves / $leaveRequests->count()) * 100 : 0;

        // Payroll KPIs
        $payrolls = Payroll::whereBetween('payroll_period_start', [$startDate, $endDate])->get();
        $totalPayrollAmount = $payrolls->sum('net_salary');
        $averageSalary = $payrolls->count() > 0 ? $payrolls->avg('net_salary') : 0;
        $processedPayrolls = $payrolls->whereIn('status', ['processed', 'paid'])->count();
        $payrollProcessingRate = $payrolls->count() > 0 ? ($processedPayrolls / $payrolls->count()) * 100 : 0;

        return [
            'attendance' => [
                'rate' => round($attendanceRate, 2),
                'punctuality_rate' => round($punctualityRate, 2),
                'absenteeism_rate' => round($absenteeismRate, 2),
                'average_hours_per_employee' => round($averageHoursPerEmployee, 2),
                'average_hours_per_day' => round($averageHoursPerDay, 2),
            ],
            'leave' => [
                'total_requests' => $leaveRequests->count(),
                'approved' => $approvedLeaves,
                'pending' => $pendingLeaves,
                'rejected' => $rejectedLeaves,
                'approval_rate' => round($leaveApprovalRate, 2),
            ],
            'payroll' => [
                'total_amount' => $totalPayrollAmount,
                'average_salary' => round($averageSalary, 2),
                'processing_rate' => round($payrollProcessingRate, 2),
                'total_processed' => $processedPayrolls,
            ],
            'workforce' => [
                'total_employees' => $totalEmployees,
                'active_employees' => $totalEmployees,
                'working_days' => $workingDays,
            ],
        ];
    }

    /**
     * Get attendance overview analytics
     */
    public function getAttendanceOverview($startDate, $endDate)
    {
        $attendanceData = Attendance::whereBetween('date', [$startDate, $endDate])
            ->selectRaw('
                DATE(date) as date,
                status,
                COUNT(*) as count,
                SUM(total_hours) as total_hours,
                AVG(total_hours) as avg_hours
            ')
            ->groupBy('date', 'status')
            ->orderBy('date')
            ->get();

        // Daily breakdown
        $dailyStats = [];
        $currentDate = Carbon::parse($startDate);
        while ($currentDate <= Carbon::parse($endDate)) {
            $dayData = $attendanceData->where('date', $currentDate->format('Y-m-d'));
            
            $dailyStats[] = [
                'date' => $currentDate->format('Y-m-d'),
                'day_name' => $currentDate->format('l'),
                'present' => $dayData->where('status', 'present')->sum('count'),
                'late' => $dayData->where('status', 'late')->sum('count'),
                'absent' => $dayData->where('status', 'absent')->sum('count'),
                'incomplete' => $dayData->where('status', 'incomplete')->sum('count'),
                'total_hours' => $dayData->sum('total_hours'),
                'avg_hours' => $dayData->avg('avg_hours') ?: 0,
            ];
            
            $currentDate->addDay();
        }

        // Status distribution
        $statusDistribution = Attendance::whereBetween('date', [$startDate, $endDate])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Department-wise analysis (if departments exist)
        $departmentAnalysis = $this->getDepartmentAttendanceAnalysis($startDate, $endDate);

        return [
            'daily_stats' => $dailyStats,
            'status_distribution' => $statusDistribution,
            'department_analysis' => $departmentAnalysis,
            'peak_hours' => $this->getPeakHoursAnalysis($startDate, $endDate),
        ];
    }

    /**
     * Get leave overview analytics
     */
    public function getLeaveOverview($startDate, $endDate)
    {
        $leaveData = Leave::with(['leaveType', 'employee'])
            ->whereBetween('start_date', [$startDate, $endDate])
            ->get();

        // Leave type distribution
        $leaveTypeDistribution = $leaveData->groupBy('leaveType.name')
            ->map(function ($leaves) {
                return [
                    'count' => $leaves->count(),
                    'total_days' => $leaves->sum('days_requested'),
                    'approved' => $leaves->where('status', 'approved')->count(),
                    'pending' => $leaves->where('status', 'pending')->count(),
                    'rejected' => $leaves->where('status', 'rejected')->count(),
                ];
            });

        // Monthly trends
        $monthlyTrends = $leaveData->groupBy(function ($leave) {
            return Carbon::parse($leave->start_date)->format('Y-m');
        })->map(function ($leaves) {
            return [
                'total_requests' => $leaves->count(),
                'total_days' => $leaves->sum('days_requested'),
                'approved_requests' => $leaves->where('status', 'approved')->count(),
                'approved_days' => $leaves->where('status', 'approved')->sum('days_requested'),
            ];
        });

        // Leave patterns
        $leavePatterns = $this->getLeavePatterns($leaveData);

        return [
            'type_distribution' => $leaveTypeDistribution,
            'monthly_trends' => $monthlyTrends,
            'patterns' => $leavePatterns,
            'approval_metrics' => $this->getLeaveApprovalMetrics($leaveData),
        ];
    }

    /**
     * Get payroll overview analytics
     */
    public function getPayrollOverview($startDate, $endDate)
    {
        $payrollData = Payroll::with('employee')
            ->whereBetween('payroll_period_start', [$startDate, $endDate])
            ->get();

        // Salary distribution
        $salaryRanges = [
            '0-2000' => $payrollData->where('net_salary', '<=', 2000)->count(),
            '2001-4000' => $payrollData->whereBetween('net_salary', [2001, 4000])->count(),
            '4001-6000' => $payrollData->whereBetween('net_salary', [4001, 6000])->count(),
            '6001-8000' => $payrollData->whereBetween('net_salary', [6001, 8000])->count(),
            '8000+' => $payrollData->where('net_salary', '>', 8000)->count(),
        ];

        // Department-wise payroll (if departments exist)
        $departmentPayroll = $this->getDepartmentPayrollAnalysis($payrollData);

        // Monthly payroll trends
        $monthlyPayroll = $payrollData->groupBy(function ($payroll) {
            return Carbon::parse($payroll->payroll_period_start)->format('Y-m');
        })->map(function ($payrolls) {
            return [
                'total_amount' => $payrolls->sum('net_salary'),
                'average_amount' => $payrolls->avg('net_salary'),
                'total_employees' => $payrolls->count(),
                'total_hours' => $payrolls->sum('worked_hours'),
                'total_overtime' => $payrolls->sum('overtime_hours'),
            ];
        });

        return [
            'salary_distribution' => $salaryRanges,
            'department_analysis' => $departmentPayroll,
            'monthly_trends' => $monthlyPayroll,
            'cost_analysis' => $this->getPayrollCostAnalysis($payrollData),
        ];
    }

    /**
     * Get employee performance analytics
     */
    public function getEmployeePerformance($startDate, $endDate)
    {
        $employees = Employee::with(['attendances', 'leaves', 'payrolls'])
            ->where('is_active', true)
            ->get();

        $performanceData = $employees->map(function ($employee) use ($startDate, $endDate) {
            $attendances = $employee->attendances()
                ->whereBetween('date', [$startDate, $endDate])
                ->get();

            $totalDays = $attendances->count();
            $presentDays = $attendances->whereIn('status', ['present', 'late'])->count();
            $lateDays = $attendances->where('status', 'late')->count();
            $totalHours = $attendances->sum('total_hours');

            $leaves = $employee->leaves()
                ->whereBetween('start_date', [$startDate, $endDate])
                ->where('status', 'approved')
                ->get();

            return [
                'employee_id' => $employee->id,
                'employee_name' => $employee->full_name,
                'employee_code' => $employee->employee_id,
                'attendance_rate' => $totalDays > 0 ? ($presentDays / $totalDays) * 100 : 0,
                'punctuality_rate' => $presentDays > 0 ? (($presentDays - $lateDays) / $presentDays) * 100 : 0,
                'total_hours' => $totalHours,
                'average_hours_per_day' => $totalDays > 0 ? $totalHours / $totalDays : 0,
                'leave_days_taken' => $leaves->sum('days_requested'),
                'leave_requests' => $leaves->count(),
                'performance_score' => $this->calculatePerformanceScore($attendances, $leaves),
            ];
        });

        // Sort by performance score
        $topPerformers = $performanceData->sortByDesc('performance_score')->take(10);
        $bottomPerformers = $performanceData->sortBy('performance_score')->take(5);

        return [
            'all_employees' => $performanceData,
            'top_performers' => $topPerformers,
            'bottom_performers' => $bottomPerformers,
            'performance_distribution' => $this->getPerformanceDistribution($performanceData),
        ];
    }

    /**
     * Get trends and forecasting data
     */
    public function getTrends($startDate, $endDate)
    {
        // Attendance trends
        $attendanceTrends = $this->getAttendanceTrends($startDate, $endDate);
        
        // Leave trends
        $leaveTrends = $this->getLeaveTrends($startDate, $endDate);
        
        // Payroll trends
        $payrollTrends = $this->getPayrollTrends($startDate, $endDate);

        return [
            'attendance' => $attendanceTrends,
            'leave' => $leaveTrends,
            'payroll' => $payrollTrends,
        ];
    }

    /**
     * Get real-time analytics data
     */
    public function getRealTimeAnalytics()
    {
        $today = Carbon::today();
        
        // Today's attendance status
        $todayAttendance = Attendance::whereDate('date', $today)->get();
        $checkedIn = $todayAttendance->whereNotNull('check_in_time')->whereNull('check_out_time')->count();
        $checkedOut = $todayAttendance->whereNotNull('check_out_time')->count();
        $notCheckedIn = Employee::where('is_active', true)->count() - $todayAttendance->count();

        // Live check-ins/check-outs (last 30 minutes)
        $recentActivity = Attendance::with('employee')
            ->where(function ($query) {
                $query->where('check_in_time', '>=', Carbon::now()->subMinutes(30))
                      ->orWhere('check_out_time', '>=', Carbon::now()->subMinutes(30));
            })
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();

        // Current week progress
        $weekStart = Carbon::now()->startOfWeek();
        $weekAttendance = Attendance::whereBetween('date', [$weekStart, $today])->get();
        
        return [
            'current_status' => [
                'checked_in' => $checkedIn,
                'checked_out' => $checkedOut,
                'not_checked_in' => $notCheckedIn,
                'total_employees' => Employee::where('is_active', true)->count(),
            ],
            'recent_activity' => $recentActivity->map(function ($attendance) {
                return [
                    'employee_name' => $attendance->employee->full_name,
                    'action' => $attendance->check_out_time ? 'check_out' : 'check_in',
                    'time' => $attendance->check_out_time ?: $attendance->check_in_time,
                    'status' => $attendance->status,
                ];
            }),
            'week_progress' => [
                'total_hours' => $weekAttendance->sum('total_hours'),
                'average_hours' => $weekAttendance->avg('total_hours'),
                'attendance_rate' => $this->calculateWeekAttendanceRate($weekAttendance),
            ],
        ];
    }

    // Helper methods

    private function getWorkingDays($startDate, $endDate)
    {
        $count = 0;
        $current = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        while ($current <= $end) {
            if (!$current->isWeekend()) {
                $count++;
            }
            $current->addDay();
        }

        return $count;
    }

    private function getDepartmentAttendanceAnalysis($startDate, $endDate)
    {
        // This would be enhanced if department functionality is added
        return [];
    }

    private function getPeakHoursAnalysis($startDate, $endDate)
    {
        $checkIns = Attendance::whereBetween('date', [$startDate, $endDate])
            ->whereNotNull('check_in_time')
            ->selectRaw('HOUR(check_in_time) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour')
            ->toArray();

        $checkOuts = Attendance::whereBetween('date', [$startDate, $endDate])
            ->whereNotNull('check_out_time')
            ->selectRaw('HOUR(check_out_time) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour')
            ->toArray();

        return [
            'check_ins' => $checkIns,
            'check_outs' => $checkOuts,
        ];
    }

    private function getLeavePatterns($leaveData)
    {
        // Day of week patterns
        $dayPatterns = $leaveData->groupBy(function ($leave) {
            return Carbon::parse($leave->start_date)->format('l');
        })->map->count();

        // Month patterns
        $monthPatterns = $leaveData->groupBy(function ($leave) {
            return Carbon::parse($leave->start_date)->format('F');
        })->map->count();

        return [
            'day_of_week' => $dayPatterns,
            'month' => $monthPatterns,
        ];
    }

    private function getLeaveApprovalMetrics($leaveData)
    {
        $totalRequests = $leaveData->count();
        $approved = $leaveData->where('status', 'approved')->count();
        $rejected = $leaveData->where('status', 'rejected')->count();
        $pending = $leaveData->where('status', 'pending')->count();

        // Average approval time
        $approvedLeaves = $leaveData->where('status', 'approved')->whereNotNull('approved_at');
        $avgApprovalTime = $approvedLeaves->avg(function ($leave) {
            return Carbon::parse($leave->created_at)->diffInHours($leave->approved_at);
        });

        return [
            'total_requests' => $totalRequests,
            'approved' => $approved,
            'rejected' => $rejected,
            'pending' => $pending,
            'approval_rate' => $totalRequests > 0 ? ($approved / $totalRequests) * 100 : 0,
            'rejection_rate' => $totalRequests > 0 ? ($rejected / $totalRequests) * 100 : 0,
            'avg_approval_time_hours' => round($avgApprovalTime ?: 0, 2),
        ];
    }

    private function getDepartmentPayrollAnalysis($payrollData)
    {
        // This would be enhanced if department functionality is added
        return [];
    }

    private function getPayrollCostAnalysis($payrollData)
    {
        $totalCost = $payrollData->sum('net_salary');
        $totalHours = $payrollData->sum('worked_hours');
        $totalOvertime = $payrollData->sum('overtime_hours');
        $totalDeductions = $payrollData->sum('total_deductions');
        $totalBonuses = $payrollData->sum('total_bonuses');

        return [
            'total_cost' => $totalCost,
            'total_hours' => $totalHours,
            'total_overtime' => $totalOvertime,
            'total_deductions' => $totalDeductions,
            'total_bonuses' => $totalBonuses,
            'cost_per_hour' => $totalHours > 0 ? $totalCost / $totalHours : 0,
            'overtime_cost' => $totalOvertime * 1.5, // Assuming 1.5x overtime rate
        ];
    }

    private function calculatePerformanceScore($attendances, $leaves)
    {
        $totalDays = $attendances->count();
        if ($totalDays === 0) return 0;

        $presentDays = $attendances->whereIn('status', ['present', 'late'])->count();
        $lateDays = $attendances->where('status', 'late')->count();
        $totalHours = $attendances->sum('total_hours');
        $leavesDays = $leaves->sum('days_requested');

        // Simple performance scoring algorithm
        $attendanceScore = $totalDays > 0 ? ($presentDays / $totalDays) * 40 : 0;
        $punctualityScore = $presentDays > 0 ? (($presentDays - $lateDays) / $presentDays) * 30 : 0;
        $productivityScore = $totalDays > 0 ? min(($totalHours / ($totalDays * 8)) * 20, 20) : 0;
        $reliabilityScore = max(10 - ($leavesDays * 0.5), 0);

        return round($attendanceScore + $punctualityScore + $productivityScore + $reliabilityScore, 2);
    }

    private function getPerformanceDistribution($performanceData)
    {
        $scores = $performanceData->pluck('performance_score');
        
        return [
            'excellent' => $scores->filter(fn($score) => $score >= 90)->count(),
            'good' => $scores->filter(fn($score) => $score >= 75 && $score < 90)->count(),
            'average' => $scores->filter(fn($score) => $score >= 60 && $score < 75)->count(),
            'below_average' => $scores->filter(fn($score) => $score >= 40 && $score < 60)->count(),
            'poor' => $scores->filter(fn($score) => $score < 40)->count(),
        ];
    }

    private function getAttendanceTrends($startDate, $endDate)
    {
        // Weekly attendance trends
        $weeks = [];
        $current = Carbon::parse($startDate)->startOfWeek();
        $end = Carbon::parse($endDate)->endOfWeek();

        while ($current <= $end) {
            $weekEnd = $current->copy()->endOfWeek();
            $weekAttendance = Attendance::whereBetween('date', [$current, $weekEnd])->get();
            
            $weeks[] = [
                'week_start' => $current->format('Y-m-d'),
                'week_end' => $weekEnd->format('Y-m-d'),
                'total_records' => $weekAttendance->count(),
                'present_count' => $weekAttendance->whereIn('status', ['present', 'late'])->count(),
                'late_count' => $weekAttendance->where('status', 'late')->count(),
                'total_hours' => $weekAttendance->sum('total_hours'),
                'avg_hours' => $weekAttendance->avg('total_hours') ?: 0,
            ];
            
            $current->addWeek();
        }

        return $weeks;
    }

    private function getLeaveTrends($startDate, $endDate)
    {
        $months = [];
        $current = Carbon::parse($startDate)->startOfMonth();
        $end = Carbon::parse($endDate)->endOfMonth();

        while ($current <= $end) {
            $monthEnd = $current->copy()->endOfMonth();
            $monthLeaves = Leave::whereBetween('start_date', [$current, $monthEnd])->get();
            
            $months[] = [
                'month' => $current->format('Y-m'),
                'month_name' => $current->format('F Y'),
                'total_requests' => $monthLeaves->count(),
                'approved_requests' => $monthLeaves->where('status', 'approved')->count(),
                'total_days' => $monthLeaves->sum('days_requested'),
                'approved_days' => $monthLeaves->where('status', 'approved')->sum('days_requested'),
            ];
            
            $current->addMonth();
        }

        return $months;
    }

    private function getPayrollTrends($startDate, $endDate)
    {
        $months = [];
        $current = Carbon::parse($startDate)->startOfMonth();
        $end = Carbon::parse($endDate)->endOfMonth();

        while ($current <= $end) {
            $monthEnd = $current->copy()->endOfMonth();
            $monthPayrolls = Payroll::whereBetween('payroll_period_start', [$current, $monthEnd])->get();
            
            $months[] = [
                'month' => $current->format('Y-m'),
                'month_name' => $current->format('F Y'),
                'total_amount' => $monthPayrolls->sum('net_salary'),
                'average_salary' => $monthPayrolls->avg('net_salary') ?: 0,
                'total_employees' => $monthPayrolls->count(),
                'total_hours' => $monthPayrolls->sum('worked_hours'),
                'overtime_hours' => $monthPayrolls->sum('overtime_hours'),
            ];
            
            $current->addMonth();
        }

        return $months;
    }

    private function calculateWeekAttendanceRate($weekAttendance)
    {
        $totalRecords = $weekAttendance->count();
        $presentRecords = $weekAttendance->whereIn('status', ['present', 'late'])->count();
        
        return $totalRecords > 0 ? ($presentRecords / $totalRecords) * 100 : 0;
    }
}