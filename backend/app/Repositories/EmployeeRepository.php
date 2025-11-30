<?php

namespace App\Repositories;

use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Employee Repository
 *
 * Handles all employee-related database operations
 */
class EmployeeRepository extends BaseRepository
{
    public function __construct(Employee $employee)
    {
        parent::__construct($employee);
    }

    /**
     * Get active employees
     */
    public function getActiveEmployees(): Collection
    {
        $cacheKey = $this->getCacheKey('active_employees');

        return cache()->remember($cacheKey, 3600, function () {
            return $this->model
                ->where('is_active', true)
                ->with(['user', 'location'])
                ->orderBy('full_name')
                ->get();
        });
    }

    /**
     * Get employees with minimal data for dropdowns
     */
    public function getEmployeesMinimal(): Collection
    {
        $cacheKey = $this->getCacheKey('employees_minimal');

        return cache()->remember($cacheKey, 3600, function () {
            return $this->model
                ->select(['id', 'employee_id', 'full_name', 'is_active'])
                ->where('is_active', true)
                ->orderBy('full_name')
                ->get();
        });
    }

    /**
     * Get employees for DataTables with optimized queries
     */
    public function getEmployeesForDataTable(): Collection
    {
        return $this->model
            ->select([
                'id', 'employee_id', 'full_name', 'phone',
                'employee_type', 'hire_date', 'salary_type',
                'salary_amount', 'hourly_rate', 'is_active',
                'user_id', 'location_id',
            ])
            ->with([
                'user:id,name,email',
                'location:id,name',
                'user.roles:id,name',
            ])
            ->orderBy('full_name')
            ->get();
    }

    /**
     * Get employees with today's attendance
     */
    public function getEmployeesWithTodayAttendance(): Collection
    {
        $cacheKey = $this->getCacheKey('employees_today_attendance', [today()->format('Y-m-d')]);

        return cache()->remember($cacheKey, 600, function () {
            return $this->model
                ->with([
                    'user',
                    'location',
                    'attendances' => function ($query) {
                        $query->whereDate('date', today());
                    },
                ])
                ->where('is_active', true)
                ->orderBy('full_name')
                ->get();
        });
    }

    /**
     * Get employee by employee ID
     */
    public function getByEmployeeId(string $employeeId): ?Employee
    {
        $cacheKey = $this->getCacheKey('by_employee_id', [$employeeId]);

        return cache()->remember($cacheKey, 1800, function () use ($employeeId) {
            return $this->model
                ->where('employee_id', $employeeId)
                ->with(['user', 'location'])
                ->first();
        });
    }

    /**
     * Get employee by user ID
     */
    public function getByUserId(string $userId): ?Employee
    {
        $cacheKey = $this->getCacheKey('by_user_id', [$userId]);

        return cache()->remember($cacheKey, 1800, function () use ($userId) {
            return $this->model
                ->where('user_id', $userId)
                ->with(['user', 'location'])
                ->first();
        });
    }

    /**
     * Get employees by location
     */
    public function getByLocation(string $locationId): Collection
    {
        $cacheKey = $this->getCacheKey('by_location', [$locationId]);

        return cache()->remember($cacheKey, 1800, function () use ($locationId) {
            return $this->model
                ->where('location_id', $locationId)
                ->where('is_active', true)
                ->with(['user', 'location'])
                ->orderBy('full_name')
                ->get();
        });
    }

    /**
     * Get employees by type
     */
    public function getByType(string $employeeType): Collection
    {
        $cacheKey = $this->getCacheKey('by_type', [$employeeType]);

        return cache()->remember($cacheKey, 1800, function () use ($employeeType) {
            return $this->model
                ->where('employee_type', $employeeType)
                ->where('is_active', true)
                ->with(['user', 'location'])
                ->orderBy('full_name')
                ->get();
        });
    }

    /**
     * Get employees with face recognition registered
     */
    public function getWithFaceRegistered(): Collection
    {
        $cacheKey = $this->getCacheKey('with_face_registered');

        return cache()->remember($cacheKey, 1800, function () {
            return $this->model
                ->whereJsonContains('metadata->face_recognition->descriptor', true)
                ->where('is_active', true)
                ->with(['user', 'location'])
                ->orderBy('full_name')
                ->get();
        });
    }

    /**
     * Get employees without face recognition
     */
    public function getWithoutFaceRegistration(): Collection
    {
        $cacheKey = $this->getCacheKey('without_face_registration');

        return cache()->remember($cacheKey, 600, function () {
            return $this->model
                ->where(function ($query) {
                    $query->whereNull('metadata->face_recognition->descriptor')
                        ->orWhereJsonMissing('metadata->face_recognition->descriptor');
                })
                ->where('is_active', true)
                ->with(['user', 'location'])
                ->orderBy('full_name')
                ->get();
        });
    }

    /**
     * Get employee statistics
     */
    public function getStatistics(): array
    {
        $cacheKey = $this->getCacheKey('statistics');

        return cache()->remember($cacheKey, 3600, function () {
            $total = $this->model->count();
            $active = $this->model->where('is_active', true)->count();
            $inactive = $total - $active;

            $byType = $this->model
                ->select('employee_type', DB::raw('COUNT(*) as count'))
                ->where('is_active', true)
                ->groupBy('employee_type')
                ->pluck('count', 'employee_type')
                ->toArray();

            $byLocation = $this->model
                ->select('location_id', DB::raw('COUNT(*) as count'))
                ->where('is_active', true)
                ->whereNotNull('location_id')
                ->groupBy('location_id')
                ->with('location:id,name')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->location->name ?? 'Unknown' => $item->count];
                })
                ->toArray();

            $withFaceRegistration = $this->model
                ->whereJsonContains('metadata->face_recognition->descriptor', true)
                ->where('is_active', true)
                ->count();

            $recentHires = $this->model
                ->where('hire_date', '>=', now()->subDays(30))
                ->where('is_active', true)
                ->count();

            return [
                'total_employees' => $total,
                'active_employees' => $active,
                'inactive_employees' => $inactive,
                'by_type' => $byType,
                'by_location' => $byLocation,
                'with_face_registration' => $withFaceRegistration,
                'without_face_registration' => $active - $withFaceRegistration,
                'recent_hires' => $recentHires,
                'face_registration_rate' => $active > 0 ? round(($withFaceRegistration / $active) * 100, 1) : 0,
            ];
        });
    }

    /**
     * Get employees hired in a specific period
     */
    public function getHiredInPeriod(string $startDate, string $endDate): Collection
    {
        $cacheKey = $this->getCacheKey('hired_in_period', [$startDate, $endDate]);

        return cache()->remember($cacheKey, 1800, function () use ($startDate, $endDate) {
            return $this->model
                ->whereBetween('hire_date', [$startDate, $endDate])
                ->with(['user', 'location'])
                ->orderBy('hire_date', 'desc')
                ->get();
        });
    }

    /**
     * Get employees with birthdays in current month
     */
    public function getBirthdaysThisMonth(): Collection
    {
        $cacheKey = $this->getCacheKey('birthdays_this_month', [now()->format('Y-m')]);

        return cache()->remember($cacheKey, 86400, function () {
            return $this->model
                ->whereRaw('MONTH(hire_date) = ?', [now()->month])
                ->where('is_active', true)
                ->with(['user', 'location'])
                ->orderByRaw('DAY(hire_date)')
                ->get();
        });
    }

    /**
     * Search employees by name or employee ID
     */
    public function search(string $query): Collection
    {
        $cacheKey = $this->getCacheKey('search', [$query]);

        return cache()->remember($cacheKey, 600, function () use ($query) {
            return $this->model
                ->where(function ($q) use ($query) {
                    $q->where('full_name', 'LIKE', "%{$query}%")
                        ->orWhere('employee_id', 'LIKE', "%{$query}%");
                })
                ->where('is_active', true)
                ->with(['user', 'location'])
                ->orderBy('full_name')
                ->limit(20)
                ->get();
        });
    }

    /**
     * Get employees with salary in range
     */
    public function getBySalaryRange(float $minSalary, float $maxSalary): Collection
    {
        $cacheKey = $this->getCacheKey('salary_range', [$minSalary, $maxSalary]);

        return cache()->remember($cacheKey, 1800, function () use ($minSalary, $maxSalary) {
            return $this->model
                ->whereBetween('salary_amount', [$minSalary, $maxSalary])
                ->where('is_active', true)
                ->with(['user', 'location'])
                ->orderBy('salary_amount', 'desc')
                ->get();
        });
    }

    /**
     * Get employees with perfect attendance for a month
     */
    public function getPerfectAttendanceEmployees(?string $month = null): Collection
    {
        $month = $month ?? now()->format('Y-m');
        $cacheKey = $this->getCacheKey('perfect_attendance', [$month]);

        return cache()->remember($cacheKey, 7200, function () use ($month) {
            $startDate = Carbon::parse($month.'-01');
            $endDate = $startDate->copy()->endOfMonth();

            return $this->model
                ->whereHas('attendances', function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('date', [$startDate, $endDate])
                        ->where('status', 'present');
                })
                ->whereDoesntHave('attendances', function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('date', [$startDate, $endDate])
                        ->whereIn('status', ['absent', 'late']);
                })
                ->where('is_active', true)
                ->with(['user', 'location'])
                ->orderBy('full_name')
                ->get();
        });
    }

    /**
     * Create employee with user
     */
    public function createWithUser(array $employeeData, array $userData): Employee
    {
        return DB::transaction(function () use ($employeeData, $userData) {
            $user = User::create($userData);

            $employeeData['user_id'] = $user->id;
            $employee = $this->create($employeeData);

            return $employee->load('user');
        });
    }

    /**
     * Update employee with user
     */
    public function updateWithUser(string $employeeId, array $employeeData, array $userData = []): Employee
    {
        return DB::transaction(function () use ($employeeId, $employeeData, $userData) {
            $employee = $this->findOrFail($employeeId);

            if (! empty($userData) && $employee->user) {
                $employee->user->update($userData);
            }

            $employee->update($employeeData);

            return $employee->fresh(['user', 'location']);
        });
    }

    /**
     * Deactivate employee
     */
    public function deactivate(string $employeeId): bool
    {
        $employee = $this->findOrFail($employeeId);
        $result = $employee->update(['is_active' => false]);

        $this->clearCache();

        return $result;
    }

    /**
     * Reactivate employee
     */
    public function reactivate(string $employeeId): bool
    {
        $employee = $this->findOrFail($employeeId);
        $result = $employee->update(['is_active' => true]);

        $this->clearCache();

        return $result;
    }

    /**
     * Update face recognition data
     */
    public function updateFaceRecognition(string $employeeId, array $faceData): bool
    {
        $employee = $this->findOrFail($employeeId);

        $metadata = $employee->metadata ?? [];
        $metadata['face_recognition'] = array_merge(
            $metadata['face_recognition'] ?? [],
            $faceData
        );

        $result = $employee->update(['metadata' => $metadata]);

        $this->clearCache();

        return $result;
    }

    /**
     * Get employees needing face registration
     */
    public function getNeedingFaceRegistration(): Collection
    {
        return $this->getWithoutFaceRegistration();
    }

    /**
     * Get employee performance summary
     */
    public function getPerformanceSummary(string $employeeId, ?string $month = null): array
    {
        $month = $month ?? now()->format('Y-m');
        $cacheKey = $this->getCacheKey('performance_summary', [$employeeId, $month]);

        return cache()->remember($cacheKey, 3600, function () use ($employeeId, $month) {
            $startDate = Carbon::parse($month.'-01');
            $endDate = $startDate->copy()->endOfMonth();

            $attendances = DB::table('attendances')
                ->where('employee_id', $employeeId)
                ->whereBetween('date', [$startDate, $endDate])
                ->get();

            $workingDays = $startDate->diffInDaysFiltered(function (Carbon $date) use ($endDate) {
                return $date->isWeekday() && $date->lte($endDate);
            });

            return [
                'total_working_days' => $workingDays,
                'total_present' => $attendances->whereIn('status', ['present', 'late'])->count(),
                'total_absent' => $attendances->where('status', 'absent')->count(),
                'total_late' => $attendances->where('status', 'late')->count(),
                'total_hours' => round($attendances->sum('total_hours'), 2),
                'average_hours' => round($attendances->avg('total_hours'), 2),
                'attendance_rate' => $workingDays > 0 ? round(($attendances->whereIn('status', ['present', 'late'])->count() / $workingDays) * 100, 1) : 0,
                'punctuality_rate' => $attendances->count() > 0 ? round(($attendances->where('status', 'present')->count() / $attendances->count()) * 100, 1) : 0,
            ];
        });
    }

    /**
     * Get filtered employees for DataTables
     */
    public function getFilteredEmployees($request = null)
    {
        $query = $this->model
            ->with(['user', 'location', 'attendances' => function ($q) {
                $q->latest()->limit(1);
            }])
            ->whereHas('user') // Only show employees with user accounts
            ->select('employees.*');

        // Apply filters if request is provided
        if ($request) {
            if ($request->filled('department')) {
                $query->where('location_id', $request->department);
            }

            if ($request->filled('status')) {
                $query->where('is_active', $request->status === 'active');
            }

            if ($request->filled('type')) {
                $query->where('employee_type', $request->type);
            }

            if ($request->filled('search.value')) {
                $searchValue = $request->input('search.value');
                $query->where(function ($q) use ($searchValue) {
                    $q->where('full_name', 'like', "%{$searchValue}%")
                        ->orWhere('employee_id', 'like', "%{$searchValue}%")
                        ->orWhereHas('user', function ($userQuery) use ($searchValue) {
                            $userQuery->where('email', 'like', "%{$searchValue}%");
                        });
                });
            }
        }

        return $query;
    }

    /**
     * Find multiple employees by IDs
     */
    public function findMultiple(array $ids): Collection
    {
        return $this->model
            ->whereIn('id', $ids)
            ->with(['user', 'location'])
            ->get();
    }

    /**
     * Delete multiple employees by IDs
     */
    public function deleteMultiple(array $ids): int
    {
        return DB::transaction(function () use ($ids) {
            // Get employees before deletion for logging
            $employees = $this->findMultiple($ids);
            
            // Delete related user accounts if they exist (hard delete)
            $userIds = $employees->pluck('user_id')->filter();
            if ($userIds->isNotEmpty()) {
                User::whereIn('id', $userIds)->forceDelete();
            }
            
            // Delete employees permanently (hard delete)
            $deletedCount = $this->model->whereIn('id', $ids)->forceDelete();
            
            // Clear cache after bulk operation
            $this->clearCache();
            
            return $deletedCount;
        });
    }

    /**
     * Get employee statistics formatted for dashboard cards
     */
    public function getEmployeeStatistics(): array
    {
        $baseStats = $this->getStatistics();

        // Get today's active employees (who checked in today)
        $activeToday = DB::table('attendances')
            ->join('employees', 'attendances.employee_id', '=', 'employees.id')
            ->whereDate('attendances.date', today())
            ->where('employees.is_active', true)
            ->distinct('attendances.employee_id')
            ->count('attendances.employee_id');

        return [
            'total' => $baseStats['total_employees'],
            'active_today' => $activeToday,
            'permanent' => $baseStats['by_type']['permanent'] ?? 0,
            'honorary' => $baseStats['by_type']['honorary'] ?? 0,
            'staff' => $baseStats['by_type']['staff'] ?? 0,
            'with_face' => $baseStats['with_face_registration'],
            'without_face' => $baseStats['without_face_registration'],
            'recent_hires' => $baseStats['recent_hires'],
            'departments' => $baseStats['by_location'],
        ];
    }
}
