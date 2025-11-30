<?php

namespace App\Repositories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Face Recognition Repository
 *
 * Handles all face recognition-related database operations
 */
class FaceRecognitionRepository extends BaseRepository
{
    public function __construct(Employee $employee)
    {
        parent::__construct($employee);
    }

    /**
     * Get employees with face recognition data
     */
    public function getEmployeesWithFaceData(): Collection
    {
        $cacheKey = $this->getCacheKey('employees_with_face_data');

        return cache()->remember($cacheKey, 3600, function () {
            return $this->model
                ->whereNotNull('metadata->face_recognition->descriptor')
                ->where('is_active', true)
                ->with(['location', 'user'])
                ->get();
        });
    }

    /**
     * Get face recognition statistics by location
     */
    public function getFaceRecognitionStatsByLocation(): array
    {
        $cacheKey = $this->getCacheKey('face_recognition_stats_by_location');

        return cache()->remember($cacheKey, 1800, function () {
            $locations = DB::table('employees')
                ->join('locations', 'employees.location_id', '=', 'locations.id')
                ->select(
                    'locations.id',
                    'locations.name',
                    DB::raw('COUNT(*) as total_employees'),
                    DB::raw('COUNT(CASE WHEN employees.metadata->>"$.face_recognition.descriptor" IS NOT NULL THEN 1 END) as registered_faces')
                )
                ->where('employees.is_active', true)
                ->groupBy('locations.id', 'locations.name')
                ->get();

            return $locations->map(function ($location) {
                return [
                    'location_id' => $location->id,
                    'location_name' => $location->name,
                    'total_employees' => $location->total_employees,
                    'registered_faces' => $location->registered_faces,
                    'registration_percentage' => $location->total_employees > 0
                        ? round(($location->registered_faces / $location->total_employees) * 100, 2)
                        : 0,
                ];
            })->toArray();
        });
    }

    /**
     * Get face recognition logs for an employee
     */
    public function getEmployeeFaceRecognitionLogs(string $employeeId, int $days = 30): Collection
    {
        $cacheKey = $this->getCacheKey('employee_face_logs', [$employeeId, $days]);

        return cache()->remember($cacheKey, 300, function () use ($employeeId, $days) {
            return DB::table('face_recognition_logs')
                ->where('employee_id', $employeeId)
                ->where('created_at', '>=', now()->subDays($days))
                ->orderBy('created_at', 'desc')
                ->get();
        });
    }

    /**
     * Get employees with low quality face data
     */
    public function getEmployeesWithLowQualityFaces(float $threshold = 0.7): Collection
    {
        $cacheKey = $this->getCacheKey('low_quality_faces', [$threshold]);

        return cache()->remember($cacheKey, 1800, function () use ($threshold) {
            return $this->model
                ->whereNotNull('metadata->face_recognition->quality_score')
                ->whereRaw('JSON_EXTRACT(metadata, "$.face_recognition.quality_score") < ?', [$threshold])
                ->where('is_active', true)
                ->with(['location', 'user'])
                ->get();
        });
    }

    /**
     * Get face recognition activity summary
     */
    public function getFaceRecognitionActivitySummary(string $startDate, string $endDate): array
    {
        $cacheKey = $this->getCacheKey('face_activity_summary', [$startDate, $endDate]);

        return cache()->remember($cacheKey, 1800, function () use ($startDate, $endDate) {
            $logs = DB::table('face_recognition_logs')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->select(
                    'action',
                    DB::raw('COUNT(*) as count'),
                    DB::raw('DATE(created_at) as date')
                )
                ->groupBy('action', DB::raw('DATE(created_at)'))
                ->get();

            $summary = [];
            foreach ($logs as $log) {
                if (! isset($summary[$log->date])) {
                    $summary[$log->date] = [
                        'date' => $log->date,
                        'registrations' => 0,
                        'updates' => 0,
                        'deletions' => 0,
                        'successful_verifications' => 0,
                        'failed_verifications' => 0,
                    ];
                }

                switch ($log->action) {
                    case 'register':
                        $summary[$log->date]['registrations'] = $log->count;
                        break;
                    case 'update':
                        $summary[$log->date]['updates'] = $log->count;
                        break;
                    case 'delete':
                        $summary[$log->date]['deletions'] = $log->count;
                        break;
                    case 'verify_success':
                        $summary[$log->date]['successful_verifications'] = $log->count;
                        break;
                    case 'verify_failed':
                        $summary[$log->date]['failed_verifications'] = $log->count;
                        break;
                }
            }

            return array_values($summary);
        });
    }

    /**
     * Get employees with recent face updates
     */
    public function getEmployeesWithRecentFaceUpdates(int $days = 7): Collection
    {
        $cacheKey = $this->getCacheKey('recent_face_updates', [$days]);
        $cutoffDate = now()->subDays($days)->toISOString();

        return cache()->remember($cacheKey, 3600, function () use ($cutoffDate) {
            return $this->model
                ->whereNotNull('metadata->face_recognition->updated_at')
                ->whereRaw('JSON_EXTRACT(metadata, "$.face_recognition.updated_at") > ?', [$cutoffDate])
                ->where('is_active', true)
                ->with(['location', 'user'])
                ->orderByRaw('JSON_EXTRACT(metadata, "$.face_recognition.updated_at") DESC')
                ->get();
        });
    }

    /**
     * Get employees without face recognition data
     */
    public function getEmployeesWithoutFaceData(?string $locationId = null): Collection
    {
        $cacheKey = $this->getCacheKey('employees_without_face_data', [$locationId]);

        return cache()->remember($cacheKey, 3600, function () use ($locationId) {
            $query = $this->model
                ->where(function ($q) {
                    $q->whereNull('metadata->face_recognition->descriptor')
                        ->orWhereNull('metadata->face_recognition');
                })
                ->where('is_active', true)
                ->with(['location', 'user']);

            if ($locationId) {
                $query->where('location_id', $locationId);
            }

            return $query->orderBy('full_name')->get();
        });
    }

    /**
     * Get face recognition performance metrics
     */
    public function getFaceRecognitionPerformance(int $days = 30): array
    {
        $cacheKey = $this->getCacheKey('face_recognition_performance', [$days]);

        return cache()->remember($cacheKey, 3600, function () use ($days) {
            $startDate = now()->subDays($days);

            // Get verification logs
            $logs = DB::table('face_recognition_logs')
                ->whereIn('action', ['verify_success', 'verify_failed'])
                ->where('created_at', '>=', $startDate)
                ->get();

            $totalAttempts = $logs->count();
            $successfulAttempts = $logs->where('action', 'verify_success')->count();

            // Calculate average confidence for successful verifications
            $avgConfidence = DB::table('face_recognition_logs')
                ->where('action', 'verify_success')
                ->where('created_at', '>=', $startDate)
                ->whereNotNull('data->similarity')
                ->avg(DB::raw('JSON_EXTRACT(data, "$.similarity")'));

            // Get hourly distribution
            $hourlyDistribution = DB::table('face_recognition_logs')
                ->whereIn('action', ['verify_success', 'verify_failed'])
                ->where('created_at', '>=', $startDate)
                ->select(
                    DB::raw('HOUR(created_at) as hour'),
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(CASE WHEN action = "verify_success" THEN 1 ELSE 0 END) as success_count')
                )
                ->groupBy(DB::raw('HOUR(created_at)'))
                ->get();

            return [
                'total_attempts' => $totalAttempts,
                'successful_attempts' => $successfulAttempts,
                'failed_attempts' => $totalAttempts - $successfulAttempts,
                'success_rate' => $totalAttempts > 0 ? round(($successfulAttempts / $totalAttempts) * 100, 2) : 0,
                'average_confidence' => round($avgConfidence ?? 0, 3),
                'hourly_distribution' => $hourlyDistribution->map(function ($item) {
                    return [
                        'hour' => $item->hour,
                        'total' => $item->count,
                        'successful' => $item->success_count,
                        'success_rate' => $item->count > 0 ? round(($item->success_count / $item->count) * 100, 2) : 0,
                    ];
                })->toArray(),
            ];
        });
    }

    /**
     * Get employees with multiple face registration attempts
     */
    public function getEmployeesWithMultipleRegistrationAttempts(): Collection
    {
        $cacheKey = $this->getCacheKey('multiple_registration_attempts');

        return cache()->remember($cacheKey, 3600, function () {
            return DB::table('face_recognition_logs')
                ->where('action', 'register')
                ->select('employee_id', DB::raw('COUNT(*) as attempt_count'))
                ->groupBy('employee_id')
                ->having('attempt_count', '>', 1)
                ->get()
                ->map(function ($record) {
                    $employee = $this->model->find($record->employee_id);

                    return [
                        'employee' => $employee,
                        'attempt_count' => $record->attempt_count,
                    ];
                })
                ->filter(function ($item) {
                    return $item['employee'] !== null;
                });
        });
    }

    /**
     * Get face recognition usage by employee type
     */
    public function getFaceRecognitionUsageByType(): array
    {
        $cacheKey = $this->getCacheKey('face_recognition_usage_by_type');

        return cache()->remember($cacheKey, 3600, function () {
            $types = DB::table('employees')
                ->select(
                    'employee_type',
                    DB::raw('COUNT(*) as total'),
                    DB::raw('COUNT(CASE WHEN metadata->>"$.face_recognition.descriptor" IS NOT NULL THEN 1 END) as with_face')
                )
                ->where('is_active', true)
                ->groupBy('employee_type')
                ->get();

            return $types->map(function ($type) {
                return [
                    'employee_type' => $type->employee_type,
                    'total_employees' => $type->total,
                    'with_face_recognition' => $type->with_face,
                    'percentage' => $type->total > 0 ? round(($type->with_face / $type->total) * 100, 2) : 0,
                ];
            })->toArray();
        });
    }

    /**
     * Update face recognition metadata
     */
    public function updateFaceMetadata(string $employeeId, array $faceData): bool
    {
        $employee = $this->findOrFail($employeeId);
        $metadata = $employee->metadata ?? [];
        $metadata['face_recognition'] = $faceData;

        $result = $employee->update(['metadata' => $metadata]);

        $this->clearCache();

        return $result;
    }

    /**
     * Remove face recognition data
     */
    public function removeFaceData(string $employeeId): bool
    {
        $employee = $this->findOrFail($employeeId);
        $metadata = $employee->metadata ?? [];
        unset($metadata['face_recognition']);

        $result = $employee->update(['metadata' => $metadata]);

        $this->clearCache();

        return $result;
    }

    /**
     * Get face quality distribution
     */
    public function getFaceQualityDistribution(): array
    {
        $cacheKey = $this->getCacheKey('face_quality_distribution');

        return cache()->remember($cacheKey, 3600, function () {
            $employees = $this->getEmployeesWithFaceData();

            $distribution = [
                'excellent' => 0,  // > 0.9
                'good' => 0,       // 0.7 - 0.9
                'fair' => 0,       // 0.5 - 0.7
                'poor' => 0,       // < 0.5
            ];

            foreach ($employees as $employee) {
                $quality = $employee->metadata['face_recognition']['quality_score'] ?? 0;

                if ($quality > 0.9) {
                    $distribution['excellent']++;
                } elseif ($quality > 0.7) {
                    $distribution['good']++;
                } elseif ($quality > 0.5) {
                    $distribution['fair']++;
                } else {
                    $distribution['poor']++;
                }
            }

            return $distribution;
        });
    }

    /**
     * Search employees by face recognition status
     */
    public function searchByFaceStatus(string $status, ?string $query = null): Collection
    {
        $cacheKey = $this->getCacheKey('search_by_face_status', [$status, $query]);

        return cache()->remember($cacheKey, 600, function () use ($status, $query) {
            $q = $this->model->where('is_active', true);

            switch ($status) {
                case 'registered':
                    $q->whereNotNull('metadata->face_recognition->descriptor');
                    break;
                case 'not_registered':
                    $q->where(function ($q) {
                        $q->whereNull('metadata->face_recognition->descriptor')
                            ->orWhereNull('metadata->face_recognition');
                    });
                    break;
                case 'low_quality':
                    $q->whereNotNull('metadata->face_recognition->quality_score')
                        ->whereRaw('JSON_EXTRACT(metadata, "$.face_recognition.quality_score") < ?', [0.7]);
                    break;
            }

            if ($query) {
                $q->where(function ($q) use ($query) {
                    $q->where('full_name', 'LIKE', "%{$query}%")
                        ->orWhere('employee_id', 'LIKE', "%{$query}%");
                });
            }

            return $q->with(['location', 'user'])
                ->orderBy('full_name')
                ->get();
        });
    }
}
