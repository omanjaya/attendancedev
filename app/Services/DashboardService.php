<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Get comprehensive dashboard data based on user role
     */
    public function getDashboardData(User $user): array
    {
        $role = $user->roles->first()->name ?? 'guru';

        return match ($role) {
            'Super Admin', 'super_admin' => $this->getSuperAdminDashboard(),
            'Admin', 'admin' => $this->getAdminDashboard(),
            'kepala_sekolah' => $this->getKepalaSekolahDashboard(),
            'guru' => $this->getGuruDashboard($user),
            default => $this->getGuruDashboard($user),
        };
    }

    /**
     * Super Admin Dashboard - Complete school overview
     */
    private function getSuperAdminDashboard(): array
    {
        $today = Carbon::today();
        $cacheKey = "dashboard_superadmin_{$today->format('Y-m-d')}";

        return cache()->remember($cacheKey, 900, function () { // 15 minutes
            return [
                'realtime_status' => $this->getRealtimeAttendanceStatus(),
                'school_overview' => $this->getSchoolOverview(),
                'attendance_trends' => $this->getAttendanceTrends(30),
                'leave_management' => $this->getLeaveManagement(),
                'system_health' => $this->getSystemHealth(),
                'payroll_overview' => $this->getPayrollOverview(),
                'performance_metrics' => $this->getPerformanceMetrics(),
                'critical_alerts' => $this->getCriticalAlerts(),
            ];
        });
    }

    /**
     * Admin Dashboard - Daily operations focus
     */
    private function getAdminDashboard(): array
    {
        return [
            'daily_operations' => $this->getDailyOperations(),
            'teacher_status' => $this->getTeacherAttendanceStatus(),
            'leave_processing' => $this->getPendingLeaveRequests(),
            'schedule_management' => $this->getScheduleStatus(),
            'attendance_reports' => $this->getAttendanceReports(),
            'quick_actions' => $this->getQuickActions(),
        ];
    }

    /**
     * Kepala Sekolah Dashboard - Strategic overview
     */
    private function getKepalaSekolahDashboard(): array
    {
        return [
            'school_performance' => $this->getSchoolPerformanceSummary(),
            'teacher_performance' => $this->getTeacherPerformanceSummary(),
            'academic_status' => $this->getAcademicScheduleStatus(),
            'strategic_metrics' => $this->getStrategicMetrics(),
            'monthly_trends' => $this->getAttendanceTrends(90),
            'budget_overview' => $this->getBudgetOverview(),
        ];
    }

    /**
     * Guru Dashboard - Personal teaching focus
     */
    private function getGuruDashboard(User $user): array
    {
        $employee = $user->employee;

        // If user doesn't have employee record, return basic dashboard
        if (! $employee) {
            return [
                'personal_status' => $this->getDefaultPersonalStatus(),
                'today_schedule' => [],
                'teaching_summary' => $this->getDefaultTeachingSummary(),
                'leave_balance' => $this->getDefaultLeaveBalance(),
                'performance_summary' => $this->getDefaultPerformanceSummary(),
            ];
        }

        return [
            'personal_status' => $this->getPersonalAttendanceStatus($employee),
            'today_schedule' => $this->getTodayTeachingSchedule($employee),
            'teaching_summary' => $this->getTeachingSummary($employee),
            'leave_balance' => $this->getLeaveBalance($employee),
            'performance_summary' => $this->getPersonalPerformance($employee),
        ];
    }

    /**
     * Real-time attendance status for the school
     */
    private function getRealtimeAttendanceStatus(): array
    {
        $today = Carbon::today();

        $totalEmployees = Employee::active()->count();
        $checkedInToday = Attendance::whereDate('date', $today)
            ->whereNotNull('check_in_time')
            ->distinct('employee_id')
            ->count();

        $lateArrivals = Attendance::whereDate('date', $today)
            ->where('status', 'late')
            ->count();

        $earlyDepartures = Attendance::whereDate('date', $today)
            ->where('status', 'early_departure')
            ->count();

        $incompleteCheckouts = Attendance::whereDate('date', $today)
            ->whereNotNull('check_in_time')
            ->whereNull('check_out_time')
            ->count();

        return [
            'total_employees' => $totalEmployees,
            'checked_in_today' => $checkedInToday,
            'attendance_rate' => $totalEmployees > 0 ? round(($checkedInToday / $totalEmployees) * 100, 1) : 0,
            'late_arrivals' => $lateArrivals,
            'early_departures' => $earlyDepartures,
            'incomplete_checkouts' => $incompleteCheckouts,
            'present_employees' => $this->getPresentEmployeesToday(),
            'absent_employees' => $this->getAbsentEmployeesToday(),
        ];
    }

    /**
     * School overview statistics
     */
    private function getSchoolOverview(): array
    {
        return [
            'total_teachers' => Employee::where('employee_type', 'teacher')->active()->count(),
            'total_staff' => Employee::where('employee_type', 'staff')->active()->count(),
            'active_employees' => Employee::active()->count(),
            'on_leave_today' => $this->getEmployeesOnLeaveToday()->count(),
            'new_hires_this_month' => Employee::whereMonth('hire_date', Carbon::now()->month)->count(),
        ];
    }

    /**
     * Get attendance trends for specified days
     */
    private function getAttendanceTrends(int $days): array
    {
        $startDate = Carbon::today()->subDays($days);

        $trends = DB::table('attendances')
            ->selectRaw('DATE(date) as date, COUNT(*) as total_attendance, 
                        SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present_count,
                        SUM(CASE WHEN status = "late" THEN 1 ELSE 0 END) as late_count,
                        SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent_count')
            ->where('date', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'daily_trends' => $trends,
            'average_attendance_rate' => $this->calculateAverageAttendanceRate($trends),
            'best_day' => $this->getBestAttendanceDay($trends),
            'worst_day' => $this->getWorstAttendanceDay($trends),
        ];
    }

    /**
     * Leave management overview
     */
    private function getLeaveManagement(): array
    {
        return [
            'pending_requests' => Leave::pending()->count(),
            'approved_today' => Leave::where('status', 'approved')
                ->whereDate('updated_at', Carbon::today())
                ->count(),
            'emergency_requests' => Leave::pending()
                ->where('start_date', '<=', Carbon::today()->addDays(2))
                ->count(),
            'upcoming_leaves' => $this->getUpcomingLeaves(),
            'leave_types_usage' => $this->getLeaveTypesUsage(),
        ];
    }

    /**
     * System health monitoring
     */
    private function getSystemHealth(): array
    {
        return [
            'database_status' => $this->checkDatabaseConnection(),
            'face_recognition_status' => $this->checkFaceRecognitionSystem(),
            'backup_status' => $this->getLastBackupStatus(),
            'active_sessions' => $this->getActiveSessionsCount(),
            'enrollment_rate' => $this->getFaceEnrollmentRate(),
        ];
    }

    /**
     * Teacher attendance status for today
     */
    private function getTeacherAttendanceStatus(): array
    {
        $today = Carbon::today();

        $teachers = Employee::where('employee_type', 'teacher')->active()->get();
        $teacherAttendance = [];

        foreach ($teachers as $teacher) {
            $attendance = $teacher->attendances()->whereDate('date', $today)->first();
            $teacherAttendance[] = [
                'id' => $teacher->id,
                'name' => $teacher->first_name.' '.$teacher->last_name,
                'employee_id' => $teacher->employee_id,
                'status' => $attendance ? $attendance->status : 'absent',
                'check_in_time' => $attendance ? $attendance->check_in_time : null,
                'check_out_time' => $attendance ? $attendance->check_out_time : null,
                'is_teaching_today' => $this->isTeachingToday($teacher),
            ];
        }

        return [
            'teachers' => $teacherAttendance,
            'teaching_coverage' => $this->getTeachingCoverageRate(),
            'substitute_needed' => $this->getSubstituteTeacherNeeds(),
        ];
    }

    /**
     * Personal attendance status for individual teacher
     */
    private function getPersonalAttendanceStatus(Employee $employee): array
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();

        $todayAttendance = $employee->attendances()->whereDate('date', $today)->first();
        $monthlyAttendance = $employee->attendances()
            ->where('date', '>=', $thisMonth)
            ->get();

        $totalWorkDays = $this->getWorkDaysInMonth($thisMonth);
        $attendedDays = $monthlyAttendance->where('status', '!=', 'absent')->count();

        return [
            'today_status' => [
                'checked_in' => $todayAttendance && $todayAttendance->check_in_time,
                'check_in_time' => $todayAttendance?->check_in_time,
                'check_out_time' => $todayAttendance?->check_out_time,
                'total_hours' => $todayAttendance?->total_hours ?? 0,
                'status' => $todayAttendance?->status ?? 'not_checked_in',
            ],
            'monthly_summary' => [
                'attendance_rate' => $totalWorkDays > 0 ? round(($attendedDays / $totalWorkDays) * 100, 1) : 0,
                'total_work_days' => $totalWorkDays,
                'attended_days' => $attendedDays,
                'late_days' => $monthlyAttendance->where('status', 'late')->count(),
                'early_departures' => $monthlyAttendance->where('status', 'early_departure')->count(),
            ],
            'punctuality_score' => $this->calculatePunctualityScore($employee),
        ];
    }

    /**
     * Get today's teaching schedule for a teacher
     */
    private function getTodayTeachingSchedule(Employee $employee): array
    {
        // This would integrate with your school's schedule system
        // For now, returning a structure that can be populated
        return [
            'classes_today' => [],
            'total_periods' => 0,
            'completed_periods' => 0,
            'next_class' => null,
            'schedule_conflicts' => [],
        ];
    }

    /**
     * Get present employees for today
     */
    private function getPresentEmployeesToday(): Collection
    {
        $today = Carbon::today();

        return Employee::whereHas('attendances', function ($query) use ($today) {
            $query->whereDate('date', $today)
                ->whereNotNull('check_in_time');
        })->with(['attendances' => function ($query) use ($today) {
            $query->whereDate('date', $today);
        }])->get();
    }

    /**
     * Get absent employees for today
     */
    private function getAbsentEmployeesToday(): Collection
    {
        $today = Carbon::today();

        return Employee::active()
            ->whereDoesntHave('attendances', function ($query) use ($today) {
                $query->whereDate('date', $today);
            })
            ->get();
    }

    /**
     * Get employees on leave today
     */
    private function getEmployeesOnLeaveToday(): Collection
    {
        $today = Carbon::today();

        return Employee::whereHas('leaves', function ($query) use ($today) {
            $query->where('status', 'approved')
                ->where('start_date', '<=', $today)
                ->where('end_date', '>=', $today);
        })->get();
    }

    /**
     * Helper methods for calculations
     */
    private function calculateAverageAttendanceRate($trends): float
    {
        if ($trends->isEmpty()) {
            return 0;
        }

        $totalEmployees = Employee::active()->count();
        if ($totalEmployees === 0) {
            return 0;
        }

        $averagePresent = $trends->avg('present_count');

        return round(($averagePresent / $totalEmployees) * 100, 1);
    }

    private function getBestAttendanceDay($trends)
    {
        return $trends->sortByDesc('present_count')->first();
    }

    private function getWorstAttendanceDay($trends)
    {
        return $trends->sortBy('present_count')->first();
    }

    private function getUpcomingLeaves(): Collection
    {
        return Leave::approved()
            ->where('start_date', '>', Carbon::today())
            ->where('start_date', '<=', Carbon::today()->addDays(7))
            ->with('employee')
            ->orderBy('start_date')
            ->get();
    }

    private function getLeaveTypesUsage(): array
    {
        return DB::table('leaves')
            ->join('leave_types', 'leaves.leave_type_id', '=', 'leave_types.id')
            ->selectRaw('leave_types.name, COUNT(*) as usage_count')
            ->where('leaves.status', 'approved')
            ->whereYear('leaves.start_date', Carbon::now()->year)
            ->groupBy('leave_types.name')
            ->get()
            ->toArray();
    }

    private function checkDatabaseConnection(): string
    {
        try {
            DB::connection()->getPdo();

            return 'healthy';
        } catch (\Exception $e) {
            return 'error';
        }
    }

    private function checkFaceRecognitionSystem(): string
    {
        // Implement face recognition system health check
        return 'healthy';
    }

    private function getLastBackupStatus(): array
    {
        // Implement backup status check
        return [
            'last_backup' => Carbon::yesterday(),
            'status' => 'success',
            'size' => '2.5 MB',
        ];
    }

    private function getActiveSessionsCount(): int
    {
        return DB::table('sessions')->count();
    }

    /**
     * Get attendance stats for Vue components
     */
    public function getAttendanceStats(User $user): array
    {
        $today = Carbon::today();
        $thisWeek = Carbon::now()->startOfWeek();
        $thisMonth = Carbon::now()->startOfMonth();

        return [
            'present_today' => Attendance::whereDate('date', $today)
                ->where('status', 'present')
                ->count(),
            'late_today' => Attendance::whereDate('date', $today)
                ->where('status', 'late')
                ->count(),
            'absent_today' => Employee::active()->count() - Attendance::whereDate('date', $today)
                ->whereNotNull('check_in_time')
                ->distinct('employee_id')
                ->count(),
            'total_employees' => Employee::active()->count(),
            'weekly_rate' => $this->calculateAttendanceRate($thisWeek, $today),
            'monthly_rate' => $this->calculateAttendanceRate($thisMonth, $today),
            'face_registration_rate' => $this->calculateFaceRegistrationRate(),
        ];
    }

    /**
     * Calculate attendance rate for a period
     */
    private function calculateAttendanceRate(Carbon $startDate, Carbon $endDate): float
    {
        $totalDays = $startDate->diffInDays($endDate) + 1;
        $totalEmployees = Employee::active()->count();
        $totalPossibleAttendance = $totalDays * $totalEmployees;

        if ($totalPossibleAttendance === 0) {
            return 0;
        }

        $actualAttendance = Attendance::whereBetween('date', [$startDate, $endDate])
            ->whereNotNull('check_in_time')
            ->count();

        return round(($actualAttendance / $totalPossibleAttendance) * 100, 1);
    }

    /**
     * Calculate face registration rate
     */
    private function calculateFaceRegistrationRate(): float
    {
        $totalEmployees = Employee::active()->count();
        if ($totalEmployees === 0) {
            return 0;
        }

        $employeesWithFace = Employee::active()
            ->whereNotNull('face_encoding')
            ->count();

        return round(($employeesWithFace / $totalEmployees) * 100, 1);
    }

    private function getFaceEnrollmentRate(): float
    {
        $totalEmployees = Employee::active()->count();
        $enrolledEmployees = Employee::active()->whereNotNull('face_embedding')->count();

        return $totalEmployees > 0 ? round(($enrolledEmployees / $totalEmployees) * 100, 1) : 0;
    }

    private function isTeachingToday(Employee $teacher): bool
    {
        // Implement check for teaching schedule
        return true; // Placeholder
    }

    private function getTeachingCoverageRate(): float
    {
        // Implement teaching coverage calculation
        return 95.0; // Placeholder
    }

    private function getSubstituteTeacherNeeds(): array
    {
        // Implement substitute teacher needs calculation
        return []; // Placeholder
    }

    private function getWorkDaysInMonth(Carbon $month): int
    {
        $workDays = 0;
        $current = $month->copy();
        $endOfMonth = $month->copy()->endOfMonth();

        while ($current->lte($endOfMonth)) {
            if ($current->isWeekday()) {
                $workDays++;
            }
            $current->addDay();
        }

        return $workDays;
    }

    private function calculatePunctualityScore(Employee $employee): float
    {
        $thisMonth = Carbon::now()->startOfMonth();
        $attendances = $employee->attendances()
            ->where('date', '>=', $thisMonth)
            ->get();

        if ($attendances->isEmpty()) {
            return 0;
        }

        $onTimeCount = $attendances->whereIn('status', ['present', 'early'])->count();

        return round(($onTimeCount / $attendances->count()) * 100, 1);
    }

    // Additional placeholder methods for completeness
    private function getPayrollOverview(): array
    {
        return [];
    }

    private function getPerformanceMetrics(): array
    {
        return [];
    }

    private function getCriticalAlerts(): array
    {
        return [];
    }

    private function getDailyOperations(): array
    {
        return [];
    }

    private function getPendingLeaveRequests(): Collection
    {
        return collect([]);
    }

    private function getScheduleStatus(): array
    {
        return [];
    }

    private function getAttendanceReports(): array
    {
        return [];
    }

    private function getQuickActions(): array
    {
        return [];
    }

    private function getSchoolPerformanceSummary(): array
    {
        return [];
    }

    private function getTeacherPerformanceSummary(): array
    {
        return [];
    }

    private function getAcademicScheduleStatus(): array
    {
        return [];
    }

    private function getStrategicMetrics(): array
    {
        return [];
    }

    private function getBudgetOverview(): array
    {
        return [];
    }

    private function getTeachingSummary(Employee $employee): array
    {
        return [];
    }

    private function getLeaveBalance(Employee $employee): array
    {
        return [];
    }

    private function getPersonalPerformance(Employee $employee): array
    {
        return [];
    }

    // Default methods for users without employee records
    private function getDefaultPersonalStatus(): array
    {
        return [
            'status' => 'not_checked_in',
            'last_attendance' => null,
            'working_hours_today' => '0h 0m',
            'attendance_rate' => 0,
            'on_time_rate' => 0,
            'today_status' => [
                'checked_in' => false,
                'check_in_time' => null,
                'check_out_time' => null,
                'status' => 'not_checked_in',
                'total_hours' => 0,
            ],
            'monthly_summary' => [
                'attendance_rate' => 0,
                'attended_days' => 0,
                'total_work_days' => 22,
                'late_days' => 0,
            ],
            'punctuality_score' => 0,
        ];
    }

    private function getDefaultTeachingSummary(): array
    {
        return [
            'total_classes' => 0,
            'classes_today' => 0,
            'upcoming_classes' => [],
            'completed_classes_this_week' => 0,
            'weekly_hours' => 0,
            'monthly_hours' => 0,
        ];
    }

    private function getDefaultLeaveBalance(): array
    {
        return [
            'annual_leave' => 12,
            'sick_leave' => 12,
            'personal_leave' => 3,
            'used_annual' => 0,
            'used_sick' => 0,
            'used_personal' => 0,
            'remaining_days' => 12,
            'used_days' => 0,
        ];
    }

    private function getDefaultPerformanceSummary(): array
    {
        return [
            'attendance_rate' => 0,
            'punctuality_score' => 0,
            'performance_rating' => 'N/A',
            'last_evaluation' => null,
            'weekly_attendance' => [95, 100, 90, 100],
            'weekly_punctuality' => [90, 95, 85, 95],
        ];
    }
}
