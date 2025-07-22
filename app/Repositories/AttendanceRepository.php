<?php

namespace App\Repositories;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Attendance Repository
 *
 * Handles all attendance-related database operations
 */
class AttendanceRepository extends BaseRepository
{
    public function __construct(Attendance $attendance)
    {
        parent::__construct($attendance);
    }

    /**
     * Get today's attendance for an employee
     */
    public function getTodayAttendance(string $employeeId): ?Attendance
    {
        $today = now('Asia/Makassar')->startOfDay();
        $todayDate = $today->format('Y-m-d');
        $cacheKey = $this->getCacheKey('today_attendance', [$employeeId, $todayDate]);

        return cache()->remember($cacheKey, 300, function () use ($employeeId, $todayDate) {
            return $this->model
                ->where('employee_id', $employeeId)
                ->whereDate('date', $todayDate)
                ->with(['employee.user', 'employee.location'])
                ->first();
        });
    }

    /**
     * Get or create today's attendance record
     */
    public function getOrCreateToday(string $employeeId): Attendance
    {
        $today = now('Asia/Makassar')->startOfDay();
        $todayDate = $today->format('Y-m-d');
        
        return $this->firstOrCreate(
            [
                'employee_id' => $employeeId,
                'date' => $todayDate,
            ],
            [
                'status' => 'incomplete',
            ]
        );
    }

    /**
     * Get attendance for date range
     */
    public function getAttendanceForDateRange(string $startDate, string $endDate, ?string $employeeId = null): Collection
    {
        $cacheKey = $this->getCacheKey('date_range', [$startDate, $endDate, $employeeId]);

        return cache()->remember($cacheKey, 1800, function () use ($startDate, $endDate, $employeeId) {
            $query = $this->model
                ->whereBetween('date', [$startDate, $endDate])
                ->with(['employee.user', 'employee.location']);

            if ($employeeId) {
                $query->where('employee_id', $employeeId);
            }

            return $query->orderBy('date', 'desc')
                ->orderBy('check_in_time', 'desc')
                ->get();
        });
    }

    /**
     * Get attendance statistics for date range
     */
    public function getAttendanceStatistics(string $startDate, string $endDate, ?string $employeeId = null): array
    {
        $cacheKey = $this->getCacheKey('statistics', [$startDate, $endDate, $employeeId]);

        return cache()->remember($cacheKey, 1800, function () use ($startDate, $endDate, $employeeId) {
            $query = $this->model->whereBetween('date', [$startDate, $endDate]);

            if ($employeeId) {
                $query->where('employee_id', $employeeId);
            }

            $attendances = $query->get();

            return [
                'total_records' => $attendances->count(),
                'present_count' => $attendances->where('status', 'present')->count(),
                'late_count' => $attendances->where('status', 'late')->count(),
                'absent_count' => $attendances->where('status', 'absent')->count(),
                'incomplete_count' => $attendances->where('status', 'incomplete')->count(),
                'early_departure_count' => $attendances->where('status', 'early_departure')->count(),
                'average_hours' => round($attendances->avg('total_hours') ?? 0, 2),
                'total_hours' => round($attendances->sum('total_hours') ?? 0, 2),
            ];
        });
    }

    /**
     * Get incomplete attendance records
     */
    public function getIncompleteAttendances(?string $employeeId = null): Collection
    {
        $cacheKey = $this->getCacheKey('incomplete', [$employeeId]);

        return cache()->remember($cacheKey, 600, function () use ($employeeId) {
            $query = $this->model
                ->where('status', 'incomplete')
                ->with(['employee.user', 'employee.location']);

            if ($employeeId) {
                $query->where('employee_id', $employeeId);
            }

            return $query->orderBy('date', 'desc')->get();
        });
    }

    /**
     * Get attendance summary for employee
     */
    public function getEmployeeAttendanceSummary(string $employeeId, ?string $month = null): array
    {
        $month = $month ?? now()->format('Y-m');
        $cacheKey = $this->getCacheKey('employee_summary', [$employeeId, $month]);

        return cache()->remember($cacheKey, 3600, function () use ($employeeId, $month) {
            $startDate = Carbon::parse($month.'-01');
            $endDate = $startDate->copy()->endOfMonth();

            $attendances = $this->model
                ->where('employee_id', $employeeId)
                ->whereBetween('date', [$startDate, $endDate])
                ->get();

            $workingDays = $startDate->diffInDaysFiltered(function (Carbon $date) use ($endDate) {
                return $date->isWeekday() && $date->lte($endDate);
            });

            return [
                'total_working_days' => $workingDays,
                'total_present' => $attendances->whereIn('status', ['present', 'late', 'early_departure'])->count(),
                'total_absent' => $attendances->where('status', 'absent')->count(),
                'total_late' => $attendances->where('status', 'late')->count(),
                'total_incomplete' => $attendances->where('status', 'incomplete')->count(),
                'total_hours' => round($attendances->sum('total_hours') ?? 0, 2),
                'average_hours' => round($attendances->avg('total_hours') ?? 0, 2),
                'attendance_rate' => $workingDays > 0 ? round(($attendances->whereIn('status', ['present', 'late', 'early_departure'])->count() / $workingDays) * 100, 1) : 0,
            ];
        });
    }

    /**
     * Get attendance records with location verification issues
     */
    public function getLocationVerificationIssues(?string $startDate = null, ?string $endDate = null): Collection
    {
        $startDate = $startDate ?? today()->subDays(7)->format('Y-m-d');
        $endDate = $endDate ?? today()->format('Y-m-d');

        $cacheKey = $this->getCacheKey('location_issues', [$startDate, $endDate]);

        return cache()->remember($cacheKey, 1800, function () use ($startDate, $endDate) {
            return $this->model
                ->where('location_verified', false)
                ->whereBetween('date', [$startDate, $endDate])
                ->whereNotNull('check_in_time')
                ->with(['employee.user', 'employee.location'])
                ->orderBy('date', 'desc')
                ->get();
        });
    }

    /**
     * Get attendance records with low confidence scores
     */
    public function getLowConfidenceAttendances(float $threshold = 0.7, ?string $startDate = null, ?string $endDate = null): Collection
    {
        $startDate = $startDate ?? today()->subDays(7)->format('Y-m-d');
        $endDate = $endDate ?? today()->format('Y-m-d');

        $cacheKey = $this->getCacheKey('low_confidence', [$threshold, $startDate, $endDate]);

        return cache()->remember($cacheKey, 1800, function () use ($threshold, $startDate, $endDate) {
            return $this->model
                ->where(function ($query) use ($threshold) {
                    $query->where('check_in_confidence', '<', $threshold)
                        ->orWhere('check_out_confidence', '<', $threshold);
                })
                ->whereBetween('date', [$startDate, $endDate])
                ->with(['employee.user', 'employee.location'])
                ->orderBy('date', 'desc')
                ->get();
        });
    }

    /**
     * Check if employee is currently checked in
     */
    public function isEmployeeCheckedIn(string $employeeId): bool
    {
        $today = now('Asia/Makassar')->startOfDay();
        $todayDate = $today->format('Y-m-d');
        $cacheKey = $this->getCacheKey('is_checked_in', [$employeeId, $todayDate]);

        return cache()->remember($cacheKey, 300, function () use ($employeeId, $todayDate) {
            return $this->model
                ->where('employee_id', $employeeId)
                ->whereDate('date', $todayDate)
                ->whereNotNull('check_in_time')
                ->whereNull('check_out_time')
                ->exists();
        });
    }

    /**
     * Get daily attendance summary
     */
    public function getDailyAttendanceSummary(?string $date = null): array
    {
        if (!$date) {
            $today = now('Asia/Makassar')->startOfDay();
            $date = $today->format('Y-m-d');
        }
        $cacheKey = $this->getCacheKey('daily_summary', [$date]);

        return cache()->remember($cacheKey, 1800, function () use ($date) {
            $attendances = $this->model
                ->whereDate('date', $date)
                ->get();

            $totalEmployees = Employee::where('is_active', true)->count();
            $checkedIn = $attendances->whereNotNull('check_in_time')->count();
            $checkedOut = $attendances->whereNotNull('check_out_time')->count();

            return [
                'date' => $date,
                'total_employees' => $totalEmployees,
                'checked_in' => $checkedIn,
                'checked_out' => $checkedOut,
                'not_checked_in' => $totalEmployees - $checkedIn,
                'incomplete' => $checkedIn - $checkedOut,
                'present' => $attendances->where('status', 'present')->count(),
                'late' => $attendances->where('status', 'late')->count(),
                'absent' => $attendances->where('status', 'absent')->count(),
                'early_departure' => $attendances->where('status', 'early_departure')->count(),
            ];
        });
    }

    /**
     * Bulk update attendance status
     */
    public function bulkUpdateStatus(array $attendanceIds, string $status): int
    {
        $updated = $this->model->whereIn('id', $attendanceIds)->update(['status' => $status]);

        $this->clearCache();

        return $updated;
    }

    /**
     * Get attendance trends for dashboard
     */
    public function getAttendanceTrends(int $days = 30): array
    {
        $cacheKey = $this->getCacheKey('trends', [$days]);

        return cache()->remember($cacheKey, 3600, function () use ($days) {
            $startDate = today()->subDays($days);
            $endDate = today();

            $trends = DB::table('attendances')
                ->selectRaw('DATE(date) as date, COUNT(*) as total, 
                           SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present,
                           SUM(CASE WHEN status = "late" THEN 1 ELSE 0 END) as late,
                           SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent,
                           AVG(total_hours) as avg_hours')
                ->whereBetween('date', [$startDate, $endDate])
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            return $trends->map(function ($trend) {
                return [
                    'date' => $trend->date,
                    'total' => $trend->total,
                    'present' => $trend->present,
                    'late' => $trend->late,
                    'absent' => $trend->absent,
                    'avg_hours' => round($trend->avg_hours ?? 0, 2),
                    'attendance_rate' => $trend->total > 0 ? round(($trend->present / $trend->total) * 100, 1) : 0,
                ];
            })->toArray();
        });
    }
}
