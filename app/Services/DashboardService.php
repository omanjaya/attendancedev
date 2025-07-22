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
            'total_records' => Attendance::count(),
            'security_alerts' => $this->getSecurityAlertsCount(),
            'holidays_this_month' => $this->getHolidaysThisMonth(),
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

        $presentCount = Attendance::whereDate('date', $today)
            ->where('status', 'present')
            ->count();
        $lateCount = Attendance::whereDate('date', $today)
            ->where('status', 'late')
            ->count();
        $totalEmployees = Employee::active()->count();
        $absentCount = $totalEmployees - Attendance::whereDate('date', $today)
            ->whereNotNull('check_in_time')
            ->distinct('employee_id')
            ->count();

        return [
            // Match JavaScript expectations exactly
            'present_count' => $presentCount,
            'late_count' => $lateCount,
            'incomplete_count' => $absentCount,
            'total_records' => $totalEmployees,
            
            // Also keep original fields for compatibility
            'present_today' => $presentCount,
            'late_today' => $lateCount,
            'absent_today' => $absentCount,
            'total_employees' => $totalEmployees,
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
        $today = Carbon::today();
        $dayOfWeek = $today->dayOfWeek;
        
        try {
            $hasSchedule = DB::table('employee_schedules')
                ->where('employee_id', $teacher->id)
                ->where('day_of_week', $dayOfWeek)
                ->exists();
                
            return $hasSchedule;
        } catch (\Exception $e) {
            // If schedule table doesn't exist, check if employee is a teacher
            return $teacher->employee_type === 'teacher';
        }
    }

    private function getTeachingCoverageRate(): float
    {
        try {
            $totalTeachers = Employee::where('employee_type', 'teacher')->active()->count();
            
            if ($totalTeachers === 0) {
                return 100.0;
            }
            
            $today = Carbon::today();
            $presentTeachers = Employee::where('employee_type', 'teacher')
                ->active()
                ->whereHas('attendances', function ($query) use ($today) {
                    $query->whereDate('date', $today)
                          ->whereNotNull('check_in_time');
                })
                ->count();
                
            return round(($presentTeachers / $totalTeachers) * 100, 1);
        } catch (\Exception $e) {
            return 0.0;
        }
    }

    private function getSubstituteTeacherNeeds(): array
    {
        $today = Carbon::today();
        $substitutesNeeded = [];
        
        try {
            // Find teachers who are absent but have scheduled classes
            $absentTeachers = Employee::where('employee_type', 'teacher')
                ->active()
                ->whereDoesntHave('attendances', function ($query) use ($today) {
                    $query->whereDate('date', $today);
                })
                ->with(['leaves' => function ($query) use ($today) {
                    $query->where('status', 'approved')
                          ->where('start_date', '<=', $today)
                          ->where('end_date', '>=', $today);
                }])
                ->get();

            foreach ($absentTeachers as $teacher) {
                // Check if they have scheduled classes today
                $schedules = DB::table('employee_schedules')
                    ->join('periods', 'employee_schedules.period_id', '=', 'periods.id')
                    ->join('subjects', 'employee_schedules.subject_id', '=', 'subjects.id')
                    ->where('employee_schedules.employee_id', $teacher->id)
                    ->where('employee_schedules.day_of_week', $today->dayOfWeek)
                    ->select(['periods.name as period', 'subjects.name as subject', 'employee_schedules.class_name'])
                    ->get();

                if ($schedules->isNotEmpty()) {
                    $substitutesNeeded[] = [
                        'teacher_name' => $teacher->first_name . ' ' . $teacher->last_name,
                        'teacher_id' => $teacher->id,
                        'reason' => $teacher->leaves->isNotEmpty() ? 'On Leave' : 'Absent',
                        'classes' => $schedules->map(function ($schedule) {
                            return [
                                'period' => $schedule->period,
                                'subject' => $schedule->subject,
                                'class' => $schedule->class_name,
                            ];
                        })->toArray(),
                    ];
                }
            }
        } catch (\Exception $e) {
            // Return empty if tables don't exist
        }

        return $substitutesNeeded;
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
        $thisWeek = Carbon::now()->startOfWeek();
        $thisMonth = Carbon::now()->startOfMonth();
        
        try {
            // Get teaching schedule for today
            $todaySchedules = DB::table('employee_schedules')
                ->join('periods', 'employee_schedules.period_id', '=', 'periods.id')
                ->where('employee_schedules.employee_id', $employee->id)
                ->where('employee_schedules.day_of_week', Carbon::today()->dayOfWeek)
                ->count();

            // Get total weekly teaching periods
            $weeklySchedules = DB::table('employee_schedules')
                ->where('employee_id', $employee->id)
                ->count();

            // Calculate monthly hours (assuming each period is 45 minutes)
            $monthlyHours = $weeklySchedules * 4 * 0.75; // 4 weeks * 45 minutes

            return [
                'total_classes' => $weeklySchedules,
                'classes_today' => $todaySchedules,
                'upcoming_classes' => $this->getUpcomingClasses($employee),
                'completed_classes_this_week' => $this->getCompletedClassesThisWeek($employee),
                'weekly_hours' => round($weeklySchedules * 0.75, 1),
                'monthly_hours' => round($monthlyHours, 1),
            ];
        } catch (\Exception $e) {
            return [
                'total_classes' => 0,
                'classes_today' => 0,
                'upcoming_classes' => [],
                'completed_classes_this_week' => 0,
                'weekly_hours' => 0,
                'monthly_hours' => 0,
            ];
        }
    }

    private function getLeaveBalance(Employee $employee): array
    {
        try {
            // Get leave balances from leave_balances table or calculate from leave types
            $currentYear = Carbon::now()->year;
            
            $usedLeaves = Leave::where('employee_id', $employee->id)
                ->where('status', 'approved')
                ->whereYear('start_date', $currentYear)
                ->selectRaw('leave_type, SUM(DATEDIFF(end_date, start_date) + 1) as used_days')
                ->groupBy('leave_type')
                ->pluck('used_days', 'leave_type');

            // Default annual leave allocation (can be customized per employee)
            $annualLeave = 12;
            $sickLeave = 12;
            $personalLeave = 3;

            return [
                'annual_leave' => $annualLeave,
                'sick_leave' => $sickLeave,
                'personal_leave' => $personalLeave,
                'used_annual' => $usedLeaves['annual'] ?? 0,
                'used_sick' => $usedLeaves['sick'] ?? 0,
                'used_personal' => $usedLeaves['personal'] ?? 0,
                'remaining_days' => $annualLeave - ($usedLeaves['annual'] ?? 0),
                'used_days' => array_sum($usedLeaves->toArray()),
            ];
        } catch (\Exception $e) {
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
    }

    private function getPersonalPerformance(Employee $employee): array
    {
        $thisMonth = Carbon::now()->startOfMonth();
        
        $attendances = $employee->attendances()
            ->where('date', '>=', $thisMonth)
            ->get();

        $totalDays = $attendances->count();
        $presentDays = $attendances->where('status', '!=', 'absent')->count();
        $onTimeDays = $attendances->whereIn('status', ['present', 'early'])->count();

        $attendanceRate = $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 1) : 0;
        $punctualityScore = $totalDays > 0 ? round(($onTimeDays / $totalDays) * 100, 1) : 0;

        // Generate weekly attendance data for chart
        $weeklyAttendance = [];
        $weeklyPunctuality = [];
        
        for ($i = 3; $i >= 0; $i--) {
            $weekStart = Carbon::now()->subWeeks($i)->startOfWeek();
            $weekEnd = Carbon::now()->subWeeks($i)->endOfWeek();
            
            $weekAttendances = $employee->attendances()
                ->whereBetween('date', [$weekStart, $weekEnd])
                ->get();
                
            $weekTotal = $weekAttendances->count();
            $weekPresent = $weekAttendances->where('status', '!=', 'absent')->count();
            $weekOnTime = $weekAttendances->whereIn('status', ['present', 'early'])->count();
            
            $weeklyAttendance[] = $weekTotal > 0 ? round(($weekPresent / $weekTotal) * 100) : 0;
            $weeklyPunctuality[] = $weekTotal > 0 ? round(($weekOnTime / $weekTotal) * 100) : 0;
        }

        return [
            'attendance_rate' => $attendanceRate,
            'punctuality_score' => $punctualityScore,
            'performance_rating' => $this->calculatePerformanceRating($attendanceRate, $punctualityScore),
            'last_evaluation' => null, // Could be linked to performance reviews table
            'weekly_attendance' => $weeklyAttendance,
            'weekly_punctuality' => $weeklyPunctuality,
        ];
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

    /**
     * Get security alerts count for the last 24 hours
     */
    private function getSecurityAlertsCount(): int
    {
        // Check for suspicious attendance patterns
        $yesterday = Carbon::yesterday();
        
        $suspiciousActivities = 0;
        
        // Check for multiple logins from different locations
        $multipleLocationLogins = DB::table('audit_logs')
            ->where('event', 'login')
            ->where('created_at', '>=', $yesterday)
            ->whereNotNull('ip_address')
            ->groupBy('user_id')
            ->havingRaw('COUNT(DISTINCT ip_address) > 2')
            ->count();
        
        $suspiciousActivities += $multipleLocationLogins;
        
        // Check for failed face recognition attempts
        $failedFaceAttempts = Attendance::where('created_at', '>=', $yesterday)
            ->whereNotNull('metadata')
            ->whereRaw("JSON_EXTRACT(metadata, '$.face_confidence') < 0.5")
            ->count();
        
        if ($failedFaceAttempts > 10) {
            $suspiciousActivities += 1;
        }
        
        return $suspiciousActivities;
    }

    /**
     * Get holidays count for current month
     */
    private function getHolidaysThisMonth(): int
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        
        // Check if holidays table exists
        if (!DB::getSchemaBuilder()->hasTable('holidays')) {
            // Calculate based on known Indonesian holidays for current month
            return $this->calculateNationalHolidays($currentMonth, $currentYear);
        }
        
        return DB::table('holidays')
            ->whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->count();
    }

    /**
     * Calculate national holidays for Indonesia
     */
    private function calculateNationalHolidays(int $month, int $year): int
    {
        $holidays = [
            1 => 1, // New Year
            2 => 0, // Usually no national holidays
            3 => 0, // Usually no national holidays
            4 => 0, // Usually no national holidays
            5 => 1, // Labor Day
            6 => 1, // Pancasila Day
            7 => 0, // Usually no national holidays
            8 => 1, // Independence Day
            9 => 0, // Usually no national holidays
            10 => 0, // Usually no national holidays
            11 => 0, // Usually no national holidays
            12 => 1, // Christmas
        ];
        
        return $holidays[$month] ?? 0;
    }

    /**
     * Get upcoming classes for employee today
     */
    private function getUpcomingClasses(Employee $employee): array
    {
        try {
            $currentTime = Carbon::now()->format('H:i:s');
            $today = Carbon::today()->dayOfWeek;

            return DB::table('employee_schedules')
                ->join('periods', 'employee_schedules.period_id', '=', 'periods.id')
                ->join('subjects', 'employee_schedules.subject_id', '=', 'subjects.id')
                ->where('employee_schedules.employee_id', $employee->id)
                ->where('employee_schedules.day_of_week', $today)
                ->where('periods.start_time', '>', $currentTime)
                ->select([
                    'subjects.name as subject',
                    'periods.start_time',
                    'periods.end_time',
                    'employee_schedules.class_name'
                ])
                ->orderBy('periods.start_time')
                ->limit(3)
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get completed classes this week for employee
     */
    private function getCompletedClassesThisWeek(Employee $employee): int
    {
        try {
            $weekStart = Carbon::now()->startOfWeek();
            $now = Carbon::now();

            return DB::table('employee_schedules')
                ->join('periods', 'employee_schedules.period_id', '=', 'periods.id')
                ->where('employee_schedules.employee_id', $employee->id)
                ->where('employee_schedules.day_of_week', '>=', $weekStart->dayOfWeek)
                ->where('employee_schedules.day_of_week', '<=', $now->dayOfWeek)
                ->where('periods.end_time', '<', $now->format('H:i:s'))
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Calculate performance rating based on attendance and punctuality
     */
    private function calculatePerformanceRating(float $attendanceRate, float $punctualityScore): string
    {
        $averageScore = ($attendanceRate + $punctualityScore) / 2;

        if ($averageScore >= 95) {
            return 'Excellent';
        } elseif ($averageScore >= 85) {
            return 'Good';
        } elseif ($averageScore >= 75) {
            return 'Satisfactory';
        } elseif ($averageScore >= 60) {
            return 'Needs Improvement';
        } else {
            return 'Poor';
        }
    }

    /**
     * Get weekly attendance chart data
     */
    public function getWeeklyAttendanceChart(): array
    {
        $startDate = Carbon::now()->startOfWeek();
        $labels = [];
        $presentData = [];
        $lateData = [];
        
        for ($i = 0; $i < 7; $i++) {
            $date = $startDate->copy()->addDays($i);
            $labels[] = $date->format('D');
            
            $dayAttendance = Attendance::whereDate('date', $date)
                ->selectRaw('
                    COUNT(CASE WHEN status = "present" OR status = "early" THEN 1 END) as present_count,
                    COUNT(CASE WHEN status = "late" THEN 1 END) as late_count
                ')
                ->first();
            
            $presentData[] = $dayAttendance->present_count ?? 0;
            $lateData[] = $dayAttendance->late_count ?? 0;
        }
        
        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Present',
                    'data' => $presentData,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'borderColor' => 'rgb(16, 185, 129)',
                    'borderWidth' => 2,
                    'fill' => true
                ],
                [
                    'label' => 'Late',
                    'data' => $lateData,
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'borderColor' => 'rgb(245, 158, 11)',
                    'borderWidth' => 2,
                    'fill' => false
                ]
            ]
        ];
    }

    /**
     * Get monthly attendance chart data
     */
    public function getMonthlyAttendanceChart(): array
    {
        $startDate = Carbon::now()->startOfMonth()->subMonths(3);
        $labels = [];
        $presentData = [];
        $absentData = [];
        
        for ($i = 0; $i < 4; $i++) {
            $monthStart = $startDate->copy()->addMonths($i);
            $monthEnd = $monthStart->copy()->endOfMonth();
            $labels[] = $monthStart->format('M Y');
            
            $monthlyStats = DB::table('attendances')
                ->whereBetween('date', [$monthStart, $monthEnd])
                ->selectRaw('
                    COUNT(CASE WHEN status IN ("present", "early") THEN 1 END) as present_count,
                    COUNT(CASE WHEN status = "absent" THEN 1 END) as absent_count
                ')
                ->first();
            
            $presentData[] = $monthlyStats->present_count ?? 0;
            $absentData[] = $monthlyStats->absent_count ?? 0;
        }
        
        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Present Days',
                    'data' => $presentData,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 2
                ],
                [
                    'label' => 'Absent Days',
                    'data' => $absentData,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'borderColor' => 'rgb(239, 68, 68)',
                    'borderWidth' => 2
                ]
            ]
        ];
    }

    /**
     * Get quarterly attendance chart data
     */
    public function getQuarterlyAttendanceChart(): array
    {
        $currentYear = Carbon::now()->year;
        $labels = ['Q1', 'Q2', 'Q3', 'Q4'];
        $attendanceRates = [];
        
        for ($quarter = 1; $quarter <= 4; $quarter++) {
            $startMonth = ($quarter - 1) * 3 + 1;
            $endMonth = $quarter * 3;
            
            $quarterStart = Carbon::create($currentYear, $startMonth, 1)->startOfMonth();
            $quarterEnd = Carbon::create($currentYear, $endMonth, 1)->endOfMonth();
            
            $totalEmployees = Employee::active()->count();
            $totalWorkDays = $this->getWorkDaysInPeriod($quarterStart, $quarterEnd);
            $totalPossibleAttendance = $totalEmployees * $totalWorkDays;
            
            if ($totalPossibleAttendance > 0) {
                $actualAttendance = Attendance::whereBetween('date', [$quarterStart, $quarterEnd])
                    ->whereNotNull('check_in_time')
                    ->count();
                    
                $attendanceRates[] = round(($actualAttendance / $totalPossibleAttendance) * 100, 1);
            } else {
                $attendanceRates[] = 0;
            }
        }
        
        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Attendance Rate (%)',
                    'data' => $attendanceRates,
                    'backgroundColor' => 'rgba(139, 92, 246, 0.1)',
                    'borderColor' => 'rgb(139, 92, 246)',
                    'borderWidth' => 3,
                    'fill' => true,
                    'tension' => 0.4
                ]
            ]
        ];
    }

    /**
     * Calculate work days in a period
     */
    private function getWorkDaysInPeriod(Carbon $startDate, Carbon $endDate): int
    {
        $workDays = 0;
        $current = $startDate->copy();
        
        while ($current->lte($endDate)) {
            if ($current->isWeekday()) {
                $workDays++;
            }
            $current->addDay();
        }
        
        return $workDays;
    }

    /**
     * Get real system alerts for dashboard
     */
    public function getSystemAlerts(): array
    {
        $alerts = [];
        $today = Carbon::today();
        
        // Check for failed face recognition attempts (critical)
        $failedCheckins = $this->getFailedCheckinAttempts();
        if ($failedCheckins['count'] > 0) {
            $alerts[] = [
                'type' => 'critical',
                'icon' => 'info',
                'title' => $failedCheckins['count'] . ' Gagal Check-in',
                'description' => 'Masalah pengenalan wajah • ' . $failedCheckins['last_time'],
                'color' => 'red',
                'priority' => 1
            ];
        }
        
        // Check for excessive late arrivals (warning)
        $lateArrivals = $this->getExcessiveLateArrivals();
        if ($lateArrivals['count'] > $lateArrivals['threshold']) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'clock',
                'title' => $lateArrivals['count'] . ' Kedatangan Terlambat',
                'description' => 'Di atas ambang normal (' . $lateArrivals['threshold'] . ') • ' . $lateArrivals['period'],
                'color' => 'amber',
                'priority' => 2
            ];
        }
        
        // Check for recent system activities (info)
        $recentBackup = $this->getLastBackupInfo();
        if ($recentBackup) {
            $alerts[] = [
                'type' => 'success',
                'icon' => 'check',
                'title' => 'Backup Selesai',
                'description' => 'Backup database berhasil • ' . $recentBackup['time_ago'],
                'color' => 'green',
                'priority' => 4
            ];
        }
        
        // Check for recent holiday imports (info)
        $holidayImport = $this->getRecentHolidayImport();
        if ($holidayImport) {
            $alerts[] = [
                'type' => 'info',
                'icon' => 'calendar',
                'title' => 'Impor Hari Libur Selesai',
                'description' => $holidayImport['count'] . ' hari libur ditambahkan untuk ' . Carbon::now()->year . ' • ' . $holidayImport['time_ago'],
                'color' => 'blue',
                'priority' => 3
            ];
        }
        
        // Check for system performance issues
        $systemIssues = $this->getSystemPerformanceIssues();
        foreach ($systemIssues as $issue) {
            $alerts[] = $issue;
        }
        
        // Sort by priority and return latest 5
        usort($alerts, function($a, $b) {
            return $a['priority'] - $b['priority'];
        });
        
        return array_slice($alerts, 0, 5);
    }

    /**
     * Get failed check-in attempts in the last hour
     */
    private function getFailedCheckinAttempts(): array
    {
        $oneHourAgo = Carbon::now()->subHour();
        
        // Look for attendances with failed face recognition
        $failedAttempts = Attendance::where('created_at', '>=', $oneHourAgo)
            ->whereNotNull('metadata')
            ->get()
            ->filter(function($attendance) {
                $metadata = json_decode($attendance->metadata, true);
                return isset($metadata['face_confidence']) && $metadata['face_confidence'] < 0.5;
            });

        $count = $failedAttempts->count();
        $lastAttempt = $failedAttempts->sortByDesc('created_at')->first();
        
        return [
            'count' => $count,
            'last_time' => $lastAttempt ? $lastAttempt->created_at->diffForHumans() : null
        ];
    }

    /**
     * Get excessive late arrivals for today
     */
    private function getExcessiveLateArrivals(): array
    {
        $today = Carbon::today();
        $normalThreshold = 3; // Normal threshold for late arrivals per day
        
        $lateCount = Attendance::whereDate('date', $today)
            ->where('status', 'late')
            ->count();
        
        return [
            'count' => $lateCount,
            'threshold' => $normalThreshold,
            'period' => 'hari ini'
        ];
    }

    /**
     * Get last backup information
     */
    private function getLastBackupInfo(): ?array
    {
        try {
            // Check if there's a backup log table or file
            $backupFile = storage_path('app/backup/latest.sql');
            
            if (file_exists($backupFile)) {
                $lastModified = Carbon::createFromTimestamp(filemtime($backupFile));
                
                // Only show if backup was in last 24 hours
                if ($lastModified->isToday() || $lastModified->isYesterday()) {
                    return [
                        'time_ago' => $lastModified->diffForHumans(),
                        'size' => round(filesize($backupFile) / 1024 / 1024, 1) . ' MB'
                    ];
                }
            }
            
            // Alternative: Check database backup logs if they exist
            if (DB::getSchemaBuilder()->hasTable('backup_logs')) {
                $lastBackup = DB::table('backup_logs')
                    ->where('status', 'success')
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                if ($lastBackup && Carbon::parse($lastBackup->created_at)->isToday()) {
                    return [
                        'time_ago' => Carbon::parse($lastBackup->created_at)->diffForHumans()
                    ];
                }
            }
        } catch (\Exception $e) {
            // If backup checking fails, don't show backup alert
        }
        
        return null;
    }

    /**
     * Get recent holiday import information
     */
    private function getRecentHolidayImport(): ?array
    {
        try {
            if (DB::getSchemaBuilder()->hasTable('holidays')) {
                // Check for holidays created in the last 7 days for current year
                $recentHolidays = DB::table('holidays')
                    ->whereYear('date', Carbon::now()->year)
                    ->where('created_at', '>=', Carbon::now()->subWeek())
                    ->get();
                
                if ($recentHolidays->count() > 0) {
                    $latestImport = $recentHolidays->sortByDesc('created_at')->first();
                    
                    return [
                        'count' => $recentHolidays->count(),
                        'time_ago' => Carbon::parse($latestImport->created_at)->diffForHumans()
                    ];
                }
            }
        } catch (\Exception $e) {
            // If holidays table doesn't exist, don't show holiday alert
        }
        
        return null;
    }

    /**
     * Get system performance issues
     */
    private function getSystemPerformanceIssues(): array
    {
        $issues = [];
        
        try {
            // Check database response time
            $start = microtime(true);
            DB::connection()->getPdo();
            $dbResponseTime = (microtime(true) - $start) * 1000;
            
            if ($dbResponseTime > 1000) { // > 1 second
                $issues[] = [
                    'type' => 'warning',
                    'icon' => 'info',
                    'title' => 'Database Lambat',
                    'description' => 'Waktu respon: ' . round($dbResponseTime) . 'ms • Kinerja menurun',
                    'color' => 'amber',
                    'priority' => 2
                ];
            }
            
            // Check for high error rates
            if (DB::getSchemaBuilder()->hasTable('audit_logs')) {
                $recentErrors = DB::table('audit_logs')
                    ->where('level', 'error')
                    ->where('created_at', '>=', Carbon::now()->subHour())
                    ->count();
                
                if ($recentErrors > 10) {
                    $issues[] = [
                        'type' => 'critical',
                        'icon' => 'info',
                        'title' => $recentErrors . ' Error Sistem',
                        'description' => 'Tingkat error tinggi • Perlu investigasi',
                        'color' => 'red',
                        'priority' => 1
                    ];
                }
            }
        } catch (\Exception $e) {
            // System checks failed
        }
        
        return $issues;
    }
}
