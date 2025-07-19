<?php

namespace App\Repositories;

use App\Models\Employee;
use App\Models\Location;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Location Repository
 *
 * Handles all location-related database operations
 */
class LocationRepository extends BaseRepository
{
    public function __construct(Location $location)
    {
        parent::__construct($location);
    }

    /**
     * Get active locations
     */
    public function getActiveLocations(): Collection
    {
        $cacheKey = $this->getCacheKey('active_locations');

        return cache()->remember($cacheKey, 3600, function () {
            return $this->model
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        });
    }

    /**
     * Get locations with employee count
     */
    public function getLocationsWithEmployeeCount(): Collection
    {
        $cacheKey = $this->getCacheKey('locations_with_employee_count');

        return cache()->remember($cacheKey, 1800, function () {
            return $this->model
                ->withCount(['employees' => function ($query) {
                    $query->where('is_active', true);
                }])
                ->orderBy('name')
                ->get();
        });
    }

    /**
     * Get location by name
     */
    public function getByName(string $name): ?Location
    {
        $cacheKey = $this->getCacheKey('by_name', [$name]);

        return cache()->remember($cacheKey, 1800, function () use ($name) {
            return $this->model
                ->where('name', $name)
                ->first();
        });
    }

    /**
     * Get location by code
     */
    public function getByCode(string $code): ?Location
    {
        $cacheKey = $this->getCacheKey('by_code', [$code]);

        return cache()->remember($cacheKey, 1800, function () use ($code) {
            return $this->model
                ->where('code', $code)
                ->first();
        });
    }

    /**
     * Get location employees
     */
    public function getLocationEmployees(string $locationId, bool $activeOnly = true): Collection
    {
        $cacheKey = $this->getCacheKey('location_employees', [$locationId, $activeOnly]);

        return cache()->remember($cacheKey, 1800, function () use ($locationId, $activeOnly) {
            $query = Employee::where('location_id', $locationId)
                ->with(['user']);

            if ($activeOnly) {
                $query->where('is_active', true);
            }

            return $query->orderBy('full_name')->get();
        });
    }

    /**
     * Get location statistics
     */
    public function getLocationStatistics(string $locationId): array
    {
        $cacheKey = $this->getCacheKey('location_statistics', [$locationId]);

        return cache()->remember($cacheKey, 3600, function () use ($locationId) {
            $location = $this->findOrFail($locationId);

            $totalEmployees = $location->employees()->count();
            $activeEmployees = $location->employees()->where('is_active', true)->count();
            $inactiveEmployees = $totalEmployees - $activeEmployees;

            // Employee types distribution
            $employeeTypes = $location->employees()
                ->where('is_active', true)
                ->select('employee_type', DB::raw('COUNT(*) as count'))
                ->groupBy('employee_type')
                ->pluck('count', 'employee_type')
                ->toArray();

            // Today's attendance
            $todayAttendance = DB::table('attendances')
                ->join('employees', 'attendances.employee_id', '=', 'employees.id')
                ->where('employees.location_id', $locationId)
                ->where('employees.is_active', true)
                ->whereDate('attendances.date', today())
                ->count();

            // This month's leaves
            $thisMonthLeaves = DB::table('leaves')
                ->join('employees', 'leaves.employee_id', '=', 'employees.id')
                ->where('employees.location_id', $locationId)
                ->where('leaves.status', 'approved')
                ->whereMonth('leaves.start_date', now()->month)
                ->whereYear('leaves.start_date', now()->year)
                ->count();

            return [
                'total_employees' => $totalEmployees,
                'active_employees' => $activeEmployees,
                'inactive_employees' => $inactiveEmployees,
                'employee_types' => $employeeTypes,
                'today_attendance' => $todayAttendance,
                'attendance_rate' => $activeEmployees > 0 ? round(($todayAttendance / $activeEmployees) * 100, 1) : 0,
                'this_month_leaves' => $thisMonthLeaves,
            ];
        });
    }

    /**
     * Get nearby locations
     */
    public function getNearbyLocations(float $latitude, float $longitude, float $radiusKm = 10): Collection
    {
        $cacheKey = $this->getCacheKey('nearby_locations', [$latitude, $longitude, $radiusKm]);

        return cache()->remember($cacheKey, 1800, function () use ($latitude, $longitude, $radiusKm) {
            // Using Haversine formula for distance calculation
            return $this->model
                ->selectRaw('*, 
                    (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * 
                    cos(radians(longitude) - radians(?)) + 
                    sin(radians(?)) * sin(radians(latitude)))) AS distance',
                    [$latitude, $longitude, $latitude]
                )
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->where('is_active', true)
                ->havingRaw('distance <= ?', [$radiusKm])
                ->orderBy('distance')
                ->get();
        });
    }

    /**
     * Check if coordinates are within location radius
     */
    public function isWithinLocationRadius(string $locationId, float $latitude, float $longitude, float $toleranceMeters = 100): bool
    {
        $location = $this->findOrFail($locationId);

        if (! $location->latitude || ! $location->longitude) {
            return true; // No location restriction if coordinates not set
        }

        // Calculate distance using Haversine formula
        $earthRadius = 6371000; // Earth's radius in meters

        $latRad1 = deg2rad($location->latitude);
        $lonRad1 = deg2rad($location->longitude);
        $latRad2 = deg2rad($latitude);
        $lonRad2 = deg2rad($longitude);

        $deltaLatRad = $latRad2 - $latRad1;
        $deltaLonRad = $lonRad2 - $lonRad1;

        $a = sin($deltaLatRad / 2) * sin($deltaLatRad / 2) +
             cos($latRad1) * cos($latRad2) *
             sin($deltaLonRad / 2) * sin($deltaLonRad / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $distance = $earthRadius * $c;

        return $distance <= $toleranceMeters;
    }

    /**
     * Get location attendance summary
     */
    public function getLocationAttendanceSummary(string $locationId, ?string $date = null): array
    {
        $date = $date ?? today()->format('Y-m-d');
        $cacheKey = $this->getCacheKey('location_attendance_summary', [$locationId, $date]);

        return cache()->remember($cacheKey, 1800, function () use ($locationId, $date) {
            $location = $this->findOrFail($locationId);

            $totalEmployees = $location->employees()->where('is_active', true)->count();

            $attendanceData = DB::table('attendances')
                ->join('employees', 'attendances.employee_id', '=', 'employees.id')
                ->where('employees.location_id', $locationId)
                ->where('employees.is_active', true)
                ->whereDate('attendances.date', $date)
                ->select(
                    DB::raw('COUNT(*) as total_attendance'),
                    DB::raw('SUM(CASE WHEN attendances.status = "present" THEN 1 ELSE 0 END) as present_count'),
                    DB::raw('SUM(CASE WHEN attendances.status = "late" THEN 1 ELSE 0 END) as late_count'),
                    DB::raw('SUM(CASE WHEN attendances.status = "absent" THEN 1 ELSE 0 END) as absent_count'),
                    DB::raw('SUM(CASE WHEN attendances.status = "incomplete" THEN 1 ELSE 0 END) as incomplete_count'),
                    DB::raw('SUM(CASE WHEN attendances.check_in_time IS NOT NULL THEN 1 ELSE 0 END) as checked_in'),
                    DB::raw('SUM(CASE WHEN attendances.check_out_time IS NOT NULL THEN 1 ELSE 0 END) as checked_out')
                )
                ->first();

            return [
                'date' => $date,
                'total_employees' => $totalEmployees,
                'total_attendance' => $attendanceData->total_attendance ?? 0,
                'present_count' => $attendanceData->present_count ?? 0,
                'late_count' => $attendanceData->late_count ?? 0,
                'absent_count' => $attendanceData->absent_count ?? 0,
                'incomplete_count' => $attendanceData->incomplete_count ?? 0,
                'checked_in' => $attendanceData->checked_in ?? 0,
                'checked_out' => $attendanceData->checked_out ?? 0,
                'not_recorded' => $totalEmployees - ($attendanceData->total_attendance ?? 0),
                'attendance_rate' => $totalEmployees > 0 ? round((($attendanceData->total_attendance ?? 0) / $totalEmployees) * 100, 1) : 0,
            ];
        });
    }

    /**
     * Get location leave summary
     */
    public function getLocationLeaveSummary(string $locationId, ?string $month = null, ?string $year = null): array
    {
        $month = $month ?? now()->month;
        $year = $year ?? now()->year;
        $cacheKey = $this->getCacheKey('location_leave_summary', [$locationId, $month, $year]);

        return cache()->remember($cacheKey, 3600, function () use ($locationId, $month, $year) {
            $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

            $leaveData = DB::table('leaves')
                ->join('employees', 'leaves.employee_id', '=', 'employees.id')
                ->join('leave_types', 'leaves.leave_type_id', '=', 'leave_types.id')
                ->where('employees.location_id', $locationId)
                ->where('employees.is_active', true)
                ->whereBetween('leaves.start_date', [$startDate, $endDate])
                ->select(
                    DB::raw('COUNT(*) as total_leaves'),
                    DB::raw('SUM(CASE WHEN leaves.status = "approved" THEN 1 ELSE 0 END) as approved_leaves'),
                    DB::raw('SUM(CASE WHEN leaves.status = "pending" THEN 1 ELSE 0 END) as pending_leaves'),
                    DB::raw('SUM(CASE WHEN leaves.status = "rejected" THEN 1 ELSE 0 END) as rejected_leaves'),
                    DB::raw('SUM(leaves.days_requested) as total_days_requested'),
                    DB::raw('SUM(CASE WHEN leaves.status = "approved" THEN leaves.days_requested ELSE 0 END) as approved_days'),
                    'leave_types.name as leave_type',
                    DB::raw('COUNT(CASE WHEN leave_types.id = leaves.leave_type_id THEN 1 END) as type_count')
                )
                ->groupBy('leave_types.id', 'leave_types.name')
                ->get();

            $summary = [
                'month' => $startDate->format('F Y'),
                'total_leaves' => $leaveData->sum('total_leaves'),
                'approved_leaves' => $leaveData->sum('approved_leaves'),
                'pending_leaves' => $leaveData->sum('pending_leaves'),
                'rejected_leaves' => $leaveData->sum('rejected_leaves'),
                'total_days_requested' => $leaveData->sum('total_days_requested'),
                'approved_days' => $leaveData->sum('approved_days'),
                'by_type' => $leaveData->map(function ($item) {
                    return [
                        'leave_type' => $item->leave_type,
                        'count' => $item->type_count,
                        'days' => $item->total_days_requested,
                    ];
                })->toArray(),
            ];

            return $summary;
        });
    }

    /**
     * Search locations
     */
    public function searchLocations(string $query): Collection
    {
        $cacheKey = $this->getCacheKey('search_locations', [$query]);

        return cache()->remember($cacheKey, 600, function () use ($query) {
            return $this->model
                ->where(function ($q) use ($query) {
                    $q->where('name', 'LIKE', "%{$query}%")
                        ->orWhere('code', 'LIKE', "%{$query}%")
                        ->orWhere('address', 'LIKE', "%{$query}%");
                })
                ->where('is_active', true)
                ->orderBy('name')
                ->limit(10)
                ->get();
        });
    }

    /**
     * Get locations for dropdown
     */
    public function getLocationsForDropdown(): Collection
    {
        $cacheKey = $this->getCacheKey('locations_dropdown');

        return cache()->remember($cacheKey, 3600, function () {
            return $this->model
                ->select(['id', 'name', 'code'])
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(function ($location) {
                    return [
                        'id' => $location->id,
                        'text' => $location->name.($location->code ? " ({$location->code})" : ''),
                        'name' => $location->name,
                        'code' => $location->code,
                    ];
                });
        });
    }

    /**
     * Get location hierarchy
     */
    public function getLocationHierarchy(): array
    {
        $cacheKey = $this->getCacheKey('location_hierarchy');

        return cache()->remember($cacheKey, 3600, function () {
            $locations = $this->model
                ->where('is_active', true)
                ->orderBy('name')
                ->get();

            // Group by parent if you have parent_id field
            // For now, just return flat structure
            return $locations->map(function ($location) {
                return [
                    'id' => $location->id,
                    'name' => $location->name,
                    'code' => $location->code,
                    'type' => $location->type,
                    'employee_count' => $location->employees()->where('is_active', true)->count(),
                ];
            })->toArray();
        });
    }

    /**
     * Create location with validation
     */
    public function createLocation(array $data): Location
    {
        return DB::transaction(function () use ($data) {
            // Check for duplicate name
            if ($this->getByName($data['name'])) {
                throw new \Exception('Location with this name already exists');
            }

            // Check for duplicate code if provided
            if (! empty($data['code']) && $this->getByCode($data['code'])) {
                throw new \Exception('Location with this code already exists');
            }

            $location = $this->create($data);

            return $location;
        });
    }

    /**
     * Update location with validation
     */
    public function updateLocation(string $locationId, array $data): Location
    {
        return DB::transaction(function () use ($locationId, $data) {
            $location = $this->findOrFail($locationId);

            // Check for duplicate name (excluding current location)
            if (isset($data['name'])) {
                $existing = $this->getByName($data['name']);
                if ($existing && $existing->id !== $locationId) {
                    throw new \Exception('Location with this name already exists');
                }
            }

            // Check for duplicate code (excluding current location)
            if (isset($data['code']) && ! empty($data['code'])) {
                $existing = $this->getByCode($data['code']);
                if ($existing && $existing->id !== $locationId) {
                    throw new \Exception('Location with this code already exists');
                }
            }

            $location->update($data);

            return $location->fresh();
        });
    }

    /**
     * Deactivate location
     */
    public function deactivateLocation(string $locationId): bool
    {
        return DB::transaction(function () use ($locationId) {
            $location = $this->findOrFail($locationId);

            // Check if location has active employees
            $activeEmployees = $location->employees()->where('is_active', true)->count();
            if ($activeEmployees > 0) {
                throw new \Exception("Cannot deactivate location with {$activeEmployees} active employees");
            }

            $result = $location->update(['is_active' => false]);

            $this->clearCache();

            return $result;
        });
    }

    /**
     * Get location coordinates
     */
    public function getLocationCoordinates(string $locationId): ?array
    {
        $location = $this->findOrFail($locationId);

        if (! $location->latitude || ! $location->longitude) {
            return null;
        }

        return [
            'latitude' => $location->latitude,
            'longitude' => $location->longitude,
            'radius' => $location->radius ?? 100, // Default 100 meters
        ];
    }
}
