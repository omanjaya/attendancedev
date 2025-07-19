<?php

namespace App\Services;

use App\Contracts\Services\FaceRecognitionServiceInterface;
use App\Models\Employee;
use App\Repositories\AttendanceRepository;
use App\Repositories\EmployeeRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Face Recognition Service
 *
 * Handles all face recognition operations with advanced features
 */
class FaceRecognitionService implements FaceRecognitionServiceInterface
{
    protected $employeeRepository;

    protected $attendanceRepository;

    // Configuration constants
    const FACE_SIMILARITY_THRESHOLD = 0.6;

    const MIN_CONFIDENCE_SCORE = 0.7;

    const MAX_LIVENESS_ATTEMPTS = 3;

    const FACE_CACHE_TTL = 3600; // 1 hour

    const ANTI_SPOOFING_THRESHOLD = 0.8;

    public function __construct(
        EmployeeRepository $employeeRepository,
        AttendanceRepository $attendanceRepository
    ) {
        $this->employeeRepository = $employeeRepository;
        $this->attendanceRepository = $attendanceRepository;
    }

    /**
     * Register a new face for an employee
     */
    public function registerFace(
        Employee $employee,
        array $descriptor,
        ?UploadedFile $image = null,
        array $metadata = []
    ): array
    {
        return DB::transaction(function () use ($employee, $descriptor, $image, $metadata) {
            // Validate face descriptor
            $this->validateDescriptor($descriptor);

            // Check for duplicate registration
            if ($this->hasFaceRegistered($employee)) {
                throw new \Exception('Employee already has a registered face. Use update instead.');
            }

            // Process and store face image
            $imagePath = null;
            if ($image) {
                $imagePath = $this->storeFaceImage($image, $employee->id);
            }

            // Prepare face recognition data
            $faceRecognitionData = [
                'descriptor' => $descriptor,
                'confidence' => $metadata['confidence'] ?? 0.95,
                'algorithm' => $metadata['algorithm'] ?? 'face-api.js',
                'model_version' => $metadata['model_version'] ?? '1.0',
                'registered_at' => now()->toISOString(),
                'image_path' => $imagePath,
                'device_info' => $metadata['device_info'] ?? null,
                'quality_score' => $this->calculateQualityScore(['descriptor' => $descriptor]),
                'features' => $this->extractAdditionalFeatures(['descriptor' => $descriptor]),
            ];

            // Store in employee metadata
            $metadata = $employee->metadata ?? [];
            $metadata['face_recognition'] = $faceRecognitionData;

            $employee->update(['metadata' => $metadata]);

            // Clear face cache
            $this->clearFaceCache();

            // Log the registration
            $this->logFaceActivity('register', $employee->id, [
                'confidence' => $faceRecognitionData['confidence'],
                'quality_score' => $faceRecognitionData['quality_score'],
            ]);

            return [
                'success' => true,
                'employee_id' => $employee->id,
                'confidence' => $faceRecognitionData['confidence'],
                'quality_score' => $faceRecognitionData['quality_score'],
                'image_stored' => ! is_null($imagePath),
            ];
        });
    }

    /**
     * Verify a face against registered employees (Interface implementation)
     */
    public function verifyFace(
        array $descriptor,
        ?Employee $employee = null,
        float $threshold = 0.6
    ): array {
        // Convert to internal format
        $faceData = [
            'descriptor' => $descriptor,
            'confidence' => 0.95 // Default confidence
        ];
        
        $options = [
            'threshold' => $threshold,
            'target_employee' => $employee
        ];
        
        return $this->verifyFaceInternal($faceData, $options);
    }

    /**
     * Internal verify face method with full options
     */
    public function verifyFaceInternal(array $faceData, array $options = []): array
    {
        try {
            // Validate input face data
            $this->validateFaceData($faceData);

            // Check liveness if required
            if ($options['require_liveness'] ?? true) {
                $livenessResult = $this->checkLiveness($faceData);
                if (! $livenessResult['is_live']) {
                    return [
                        'success' => false,
                        'message' => 'Liveness check failed',
                        'liveness_score' => $livenessResult['score'],
                    ];
                }
            }

            // Get all registered faces from cache or database
            $registeredFaces = $this->getRegisteredFaces();

            // Determine threshold
            $threshold = $options['threshold'] ?? self::FACE_SIMILARITY_THRESHOLD;

            // Find best match
            $match = $this->findBestMatch($faceData['descriptor'], $registeredFaces);

            if ($match && $match['similarity'] >= $threshold) {
                $employee = $this->employeeRepository->find($match['employee_id']);

                // Verify additional constraints
                $constraints = $this->verifyConstraints($employee, $options);
                if (! $constraints['valid']) {
                    return [
                        'success' => false,
                        'message' => $constraints['message'],
                        'employee_id' => $employee->id,
                    ];
                }

                // Update face statistics
                $this->updateFaceStatistics($employee->id, $match['similarity']);

                // Log successful recognition
                $this->logFaceActivity('verify_success', $employee->id, [
                    'similarity' => $match['similarity'],
                    'liveness_score' => $livenessResult['score'] ?? null,
                ]);

                return [
                    'success' => true,
                    'employee' => [
                        'id' => $employee->id,
                        'name' => $employee->full_name,
                        'employee_id' => $employee->employee_id,
                        'employee_type' => $employee->employee_type,
                        'department' => $employee->location->name ?? null,
                    ],
                    'confidence' => $match['similarity'],
                    'liveness_score' => $livenessResult['score'] ?? null,
                    'quality_score' => $this->calculateQualityScore($faceData),
                    'timestamp' => now()->toISOString(),
                ];
            }

            // Log failed recognition
            $this->logFaceActivity('verify_failed', null, [
                'best_similarity' => $match['similarity'] ?? 0,
            ]);

            return [
                'success' => false,
                'message' => 'Face not recognized',
                'confidence' => $match['similarity'] ?? 0,
                'timestamp' => now()->toISOString(),
            ];

        } catch (\Exception $e) {
            Log::error('Face verification error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Update face data for an employee (Interface implementation)
     */
    public function updateFaceData(
        Employee $employee,
        array $descriptor,
        array $metadata = []
    ): bool {
        $faceData = [
            'descriptor' => $descriptor,
            'confidence' => $metadata['confidence'] ?? 0.95,
            'algorithm' => $metadata['algorithm'] ?? 'face-api.js',
            'model_version' => $metadata['model_version'] ?? '1.0',
            'device_info' => $metadata['device_info'] ?? null,
        ];
        
        $result = $this->updateFaceInternal($employee->id, $faceData);
        return $result['success'] ?? false;
    }

    /**
     * Internal update face method
     */
    public function updateFaceInternal(string $employeeId, array $faceData, ?UploadedFile $image = null): array
    {
        return DB::transaction(function () use ($employeeId, $faceData, $image) {
            $employee = $this->employeeRepository->findOrFail($employeeId);

            // Validate face data
            $this->validateFaceData($faceData);

            // Backup existing face data
            $this->backupFaceData($employee);

            // Delete old face image if exists
            if ($image && isset($employee->metadata['face_recognition']['image_path'])) {
                $this->deleteFaceImage($employee->metadata['face_recognition']['image_path']);
            }

            // Store new image
            $imagePath = null;
            if ($image) {
                $imagePath = $this->storeFaceImage($image, $employeeId);
            } else {
                $imagePath = $employee->metadata['face_recognition']['image_path'] ?? null;
            }

            // Update face recognition data
            $faceRecognitionData = [
                'descriptor' => $faceData['descriptor'],
                'confidence' => $faceData['confidence'],
                'algorithm' => $faceData['algorithm'] ?? 'face-api.js',
                'model_version' => $faceData['model_version'] ?? '1.0',
                'updated_at' => now()->toISOString(),
                'image_path' => $imagePath,
                'device_info' => $faceData['device_info'] ?? null,
                'quality_score' => $this->calculateQualityScore($faceData),
                'features' => $this->extractAdditionalFeatures($faceData),
                'update_count' => ($employee->metadata['face_recognition']['update_count'] ?? 0) + 1,
            ];

            // Update metadata
            $metadata = $employee->metadata ?? [];
            $metadata['face_recognition'] = $faceRecognitionData;

            $employee->update(['metadata' => $metadata]);

            // Clear face cache
            $this->clearFaceCache();

            // Log the update
            $this->logFaceActivity('update', $employeeId, [
                'confidence' => $faceData['confidence'],
                'quality_score' => $faceRecognitionData['quality_score'],
            ]);

            return [
                'success' => true,
                'employee_id' => $employeeId,
                'confidence' => $faceData['confidence'],
                'quality_score' => $faceRecognitionData['quality_score'],
                'update_count' => $faceRecognitionData['update_count'],
            ];
        });
    }

    /**
     * Delete face data for an employee (Interface implementation)
     */
    public function deleteFaceData(Employee $employee): bool
    {
        return $this->deleteFaceInternal($employee->id);
    }

    /**
     * Internal delete face method
     */
    public function deleteFaceInternal(string $employeeId): bool
    {
        return DB::transaction(function () use ($employeeId) {
            $employee = $this->employeeRepository->findOrFail($employeeId);

            // Backup before deletion
            $this->backupFaceData($employee);

            // Delete face image
            if (isset($employee->metadata['face_recognition']['image_path'])) {
                $this->deleteFaceImage($employee->metadata['face_recognition']['image_path']);
            }

            // Remove face data from metadata
            $metadata = $employee->metadata ?? [];
            unset($metadata['face_recognition']);

            $employee->update(['metadata' => $metadata]);

            // Clear face cache
            $this->clearFaceCache();

            // Log deletion
            $this->logFaceActivity('delete', $employeeId, []);

            return true;
        });
    }

    /**
     * Perform batch face verification
     */
    public function batchVerify(array $faceDataList): array
    {
        $results = [];
        $registeredFaces = $this->getRegisteredFaces();

        foreach ($faceDataList as $index => $faceData) {
            try {
                $match = $this->findBestMatch($faceData['descriptor'], $registeredFaces);

                if ($match && $match['similarity'] >= self::FACE_SIMILARITY_THRESHOLD) {
                    $results[] = [
                        'index' => $index,
                        'success' => true,
                        'employee_id' => $match['employee_id'],
                        'confidence' => $match['similarity'],
                    ];
                } else {
                    $results[] = [
                        'index' => $index,
                        'success' => false,
                        'confidence' => $match['similarity'] ?? 0,
                    ];
                }
            } catch (\Exception $e) {
                $results[] = [
                    'index' => $index,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Get face recognition statistics
     */
    public function getStatistics(): array
    {
        $cacheKey = 'face_recognition_statistics';

        return Cache::remember($cacheKey, 300, function () {
            $totalEmployees = $this->employeeRepository->count();
            $registeredFaces = $this->employeeRepository->getEmployeesWithFaceData()->count();

            // Get recognition accuracy from recent logs
            $recentLogs = DB::table('face_recognition_logs')
                ->where('created_at', '>=', now()->subDays(30))
                ->get();

            $successCount = $recentLogs->where('action', 'verify_success')->count();
            $totalAttempts = $recentLogs->whereIn('action', ['verify_success', 'verify_failed'])->count();

            return [
                'total_employees' => $totalEmployees,
                'registered_faces' => $registeredFaces,
                'registration_percentage' => $totalEmployees > 0 ? round(($registeredFaces / $totalEmployees) * 100, 2) : 0,
                'recognition_accuracy' => $totalAttempts > 0 ? round(($successCount / $totalAttempts) * 100, 2) : 0,
                'total_verifications' => $totalAttempts,
                'successful_verifications' => $successCount,
                'algorithms_used' => $this->getAlgorithmStatistics(),
                'average_confidence' => $this->getAverageConfidence(),
                'quality_distribution' => $this->getQualityDistribution(),
            ];
        });
    }

    /**
     * Check liveness of face
     */
    protected function checkLiveness(array $faceData): array
    {
        // Basic liveness checks
        $livenessScore = 1.0;

        // Check for blink detection
        if (isset($faceData['blink_detected'])) {
            $livenessScore *= $faceData['blink_detected'] ? 1.0 : 0.7;
        }

        // Check for head movement
        if (isset($faceData['head_movement'])) {
            $livenessScore *= $faceData['head_movement'] > 0.1 ? 1.0 : 0.8;
        }

        // Check for facial expressions
        if (isset($faceData['expressions'])) {
            $hasExpressions = count(array_filter($faceData['expressions'], fn ($val) => $val > 0.5)) > 0;
            $livenessScore *= $hasExpressions ? 1.0 : 0.9;
        }

        // Check for texture analysis
        if (isset($faceData['texture_score'])) {
            $livenessScore *= min($faceData['texture_score'], 1.0);
        }

        return [
            'is_live' => $livenessScore >= self::ANTI_SPOOFING_THRESHOLD,
            'score' => $livenessScore,
            'checks_performed' => [
                'blink' => isset($faceData['blink_detected']),
                'movement' => isset($faceData['head_movement']),
                'expressions' => isset($faceData['expressions']),
                'texture' => isset($faceData['texture_score']),
            ],
        ];
    }

    /**
     * Calculate quality score for face data
     */
    protected function calculateQualityScore(array $faceData): float
    {
        $score = 0;
        $weights = [
            'confidence' => 0.3,
            'face_size' => 0.2,
            'pose_quality' => 0.2,
            'lighting' => 0.15,
            'blur' => 0.15,
        ];

        // Confidence score
        if (isset($faceData['confidence'])) {
            $score += $faceData['confidence'] * $weights['confidence'];
        }

        // Face size (larger is better)
        if (isset($faceData['face_bounds'])) {
            $faceArea = $faceData['face_bounds']['width'] * $faceData['face_bounds']['height'];
            $sizeScore = min($faceArea / 10000, 1.0); // Normalize to 0-1
            $score += $sizeScore * $weights['face_size'];
        }

        // Pose quality (frontal is best)
        if (isset($faceData['pose'])) {
            $poseScore = 1.0 - (abs($faceData['pose']['yaw']) + abs($faceData['pose']['pitch'])) / 180;
            $score += $poseScore * $weights['pose_quality'];
        }

        // Lighting quality
        if (isset($faceData['lighting_score'])) {
            $score += $faceData['lighting_score'] * $weights['lighting'];
        }

        // Blur detection (sharp is better)
        if (isset($faceData['blur_score'])) {
            $score += (1.0 - $faceData['blur_score']) * $weights['blur'];
        }

        return round($score, 3);
    }

    /**
     * Extract additional features from face data
     */
    protected function extractAdditionalFeatures(array $faceData): array
    {
        return [
            'descriptor_length' => count($faceData['descriptor'] ?? []),
            'landmarks_count' => count($faceData['landmarks'] ?? []),
            'has_expressions' => isset($faceData['expressions']),
            'pose_data' => $faceData['pose'] ?? null,
            'age_estimation' => $faceData['age'] ?? null,
            'gender_estimation' => $faceData['gender'] ?? null,
            'emotion_data' => $faceData['emotions'] ?? null,
        ];
    }

    /**
     * Find best match among registered faces
     */
    protected function findBestMatch(array $descriptor, array $registeredFaces): ?array
    {
        $bestMatch = null;
        $bestSimilarity = 0;

        foreach ($registeredFaces as $registered) {
            $similarity = $this->calculateSimilarity($descriptor, $registered['descriptor']);

            if ($similarity > $bestSimilarity) {
                $bestSimilarity = $similarity;
                $bestMatch = [
                    'employee_id' => $registered['employee_id'],
                    'similarity' => $similarity,
                ];
            }
        }

        return $bestMatch;
    }

    /**
     * Calculate similarity between two face descriptors
     */
    protected function calculateSimilarity(array $descriptor1, array $descriptor2): float
    {
        if (count($descriptor1) !== count($descriptor2)) {
            return 0;
        }

        // Use cosine similarity
        $dotProduct = 0;
        $norm1 = 0;
        $norm2 = 0;

        for ($i = 0; $i < count($descriptor1); $i++) {
            $dotProduct += $descriptor1[$i] * $descriptor2[$i];
            $norm1 += $descriptor1[$i] * $descriptor1[$i];
            $norm2 += $descriptor2[$i] * $descriptor2[$i];
        }

        $magnitude = sqrt($norm1) * sqrt($norm2);

        return $magnitude == 0 ? 0 : $dotProduct / $magnitude;
    }

    /**
     * Get all registered faces with caching
     */
    protected function getRegisteredFaces(): array
    {
        return Cache::remember('registered_faces', self::FACE_CACHE_TTL, function () {
            $employees = $this->employeeRepository->getEmployeesWithFaceData();

            return $employees->map(function ($employee) {
                $faceData = $employee->metadata['face_recognition'] ?? null;

                if (! $faceData || ! isset($faceData['descriptor'])) {
                    return null;
                }

                return [
                    'employee_id' => $employee->id,
                    'descriptor' => $faceData['descriptor'],
                    'algorithm' => $faceData['algorithm'] ?? 'face-api.js',
                ];
            })->filter()->values()->toArray();
        });
    }

    /**
     * Verify additional constraints
     */
    protected function verifyConstraints(Employee $employee, array $options): array
    {
        // Check if employee is active
        if (! $employee->is_active) {
            return [
                'valid' => false,
                'message' => 'Employee is not active',
            ];
        }

        // Check location constraints
        if (isset($options['location']) && $employee->location) {
            $locationValid = $this->verifyLocation($options['location'], $employee->location);
            if (! $locationValid) {
                return [
                    'valid' => false,
                    'message' => 'Location verification failed',
                ];
            }
        }

        // Check time constraints
        if (isset($options['enforce_schedule']) && $options['enforce_schedule']) {
            $scheduleValid = $this->verifySchedule($employee);
            if (! $scheduleValid) {
                return [
                    'valid' => false,
                    'message' => 'Outside of scheduled hours',
                ];
            }
        }

        return ['valid' => true];
    }

    /**
     * Verify location constraints
     */
    protected function verifyLocation(array $currentLocation, $employeeLocation): bool
    {
        if (! isset($currentLocation['latitude']) || ! isset($currentLocation['longitude'])) {
            return false;
        }

        // Use location repository for geofencing check
        // For now, return true - implement proper geofencing
        return true;
    }

    /**
     * Verify schedule constraints
     */
    protected function verifySchedule(Employee $employee): bool
    {
        // Check if current time is within employee's schedule
        // This would integrate with schedule service
        return true;
    }

    /**
     * Store face image securely
     */
    protected function storeFaceImage(UploadedFile $image, string $employeeId): string
    {
        $filename = sprintf(
            '%s_%s.%s',
            $employeeId,
            now()->format('YmdHis'),
            $image->getClientOriginalExtension()
        );

        $path = $image->storeAs(
            'face-images/'.$employeeId,
            $filename,
            'private'
        );

        return $path;
    }

    /**
     * Delete face image
     */
    protected function deleteFaceImage(string $path): void
    {
        if (Storage::disk('private')->exists($path)) {
            Storage::disk('private')->delete($path);
        }
    }

    /**
     * Backup face data before modification
     */
    protected function backupFaceData(Employee $employee): void
    {
        if (! isset($employee->metadata['face_recognition'])) {
            return;
        }

        $backupData = [
            'employee_id' => $employee->id,
            'face_data' => $employee->metadata['face_recognition'],
            'backed_up_at' => now()->toISOString(),
        ];

        // Store backup (could be in separate table or storage)
        Storage::disk('private')->put(
            sprintf('face-backups/%s/%s.json', $employee->id, now()->format('YmdHis')),
            json_encode($backupData)
        );
    }

    /**
     * Clear face recognition cache
     */
    protected function clearFaceCache(): void
    {
        Cache::forget('registered_faces');
        Cache::forget('face_recognition_statistics');
    }

    /**
     * Log face recognition activity
     */
    protected function logFaceActivity(string $action, ?string $employeeId, array $data): void
    {
        DB::table('face_recognition_logs')->insert([
            'id' => \Illuminate\Support\Str::uuid(),
            'action' => $action,
            'employee_id' => $employeeId,
            'data' => json_encode($data),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Update face recognition statistics
     */
    protected function updateFaceStatistics(string $employeeId, float $similarity): void
    {
        $employee = $this->employeeRepository->find($employeeId);
        $metadata = $employee->metadata ?? [];

        $stats = $metadata['face_recognition_stats'] ?? [
            'total_verifications' => 0,
            'successful_verifications' => 0,
            'average_similarity' => 0,
            'last_verified_at' => null,
        ];

        $stats['total_verifications']++;
        $stats['successful_verifications']++;
        $stats['average_similarity'] = (
            ($stats['average_similarity'] * ($stats['successful_verifications'] - 1)) + $similarity
        ) / $stats['successful_verifications'];
        $stats['last_verified_at'] = now()->toISOString();

        $metadata['face_recognition_stats'] = $stats;
        $employee->update(['metadata' => $metadata]);
    }

    /**
     * Validate face data structure
     */
    protected function validateFaceData(array $faceData): void
    {
        $required = ['descriptor', 'confidence'];

        foreach ($required as $field) {
            if (! isset($faceData[$field])) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }

        if (! is_array($faceData['descriptor']) || count($faceData['descriptor']) !== 128) {
            throw new \InvalidArgumentException('Invalid descriptor format');
        }

        if ($faceData['confidence'] < self::MIN_CONFIDENCE_SCORE) {
            throw new \InvalidArgumentException(
                "Confidence score too low: {$faceData['confidence']}. Minimum required: ".self::MIN_CONFIDENCE_SCORE
            );
        }
    }

    /**
     * Check if employee has registered face
     */
    protected function hasFaceRegistered(Employee $employee): bool
    {
        return isset($employee->metadata['face_recognition']['descriptor']);
    }

    /**
     * Get algorithm usage statistics
     */
    protected function getAlgorithmStatistics(): array
    {
        $employees = $this->employeeRepository->getEmployeesWithFaceData();
        $algorithms = [];

        foreach ($employees as $employee) {
            $algorithm = $employee->metadata['face_recognition']['algorithm'] ?? 'unknown';
            $algorithms[$algorithm] = ($algorithms[$algorithm] ?? 0) + 1;
        }

        return $algorithms;
    }

    /**
     * Get average confidence score
     */
    protected function getAverageConfidence(): float
    {
        $employees = $this->employeeRepository->getEmployeesWithFaceData();
        $totalConfidence = 0;
        $count = 0;

        foreach ($employees as $employee) {
            $confidence = $employee->metadata['face_recognition']['confidence'] ?? 0;
            $totalConfidence += $confidence;
            $count++;
        }

        return $count > 0 ? round($totalConfidence / $count, 3) : 0;
    }

    /**
     * Get quality score distribution
     */
    protected function getQualityDistribution(): array
    {
        $employees = $this->employeeRepository->getEmployeesWithFaceData();
        $distribution = [
            'excellent' => 0, // > 0.9
            'good' => 0,      // 0.7 - 0.9
            'fair' => 0,      // 0.5 - 0.7
            'poor' => 0,      // < 0.5
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
    }

    /**
     * Get face data for an employee (Interface implementation)
     */
    public function getFaceData(Employee $employee): ?array
    {
        return $employee->metadata['face_recognition'] ?? null;
    }

    /**
     * Validate face descriptor (Interface implementation)
     */
    public function validateDescriptor(array $descriptor): bool
    {
        // Check if descriptor is array and has correct length
        if (!is_array($descriptor) || count($descriptor) !== 128) {
            return false;
        }

        // Check if all values are numeric
        foreach ($descriptor as $value) {
            if (!is_numeric($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check liveness of face (Interface implementation)
     */
    public function checkLiveness(array $faceData): bool
    {
        $result = $this->checkLivenessInternal($faceData);
        return $result['is_live'] ?? false;
    }

    /**
     * Get all registered faces (Interface implementation)
     */
    public function getAllRegisteredFaces(array $filters = []): array
    {
        return $this->getRegisteredFaces();
    }

    /**
     * Batch verify multiple faces (Interface implementation)
     */
    public function batchVerify(array $faces, float $threshold = 0.6): array
    {
        $results = [];
        $registeredFaces = $this->getRegisteredFaces();

        foreach ($faces as $index => $descriptor) {
            try {
                $match = $this->findBestMatch($descriptor, $registeredFaces);

                if ($match && $match['similarity'] >= $threshold) {
                    $results[] = [
                        'index' => $index,
                        'success' => true,
                        'employee_id' => $match['employee_id'],
                        'confidence' => $match['similarity'],
                    ];
                } else {
                    $results[] = [
                        'index' => $index,
                        'success' => false,
                        'confidence' => $match['similarity'] ?? 0,
                    ];
                }
            } catch (\Exception $e) {
                $results[] = [
                    'index' => $index,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Internal check liveness method
     */
    protected function checkLivenessInternal(array $faceData): array
    {
        // Basic liveness checks
        $livenessScore = 1.0;

        // Check for blink detection
        if (isset($faceData['blink_detected'])) {
            $livenessScore *= $faceData['blink_detected'] ? 1.0 : 0.7;
        }

        // Check for head movement
        if (isset($faceData['head_movement'])) {
            $livenessScore *= $faceData['head_movement'] > 0.1 ? 1.0 : 0.8;
        }

        // Check for facial expressions
        if (isset($faceData['expressions'])) {
            $hasExpressions = count(array_filter($faceData['expressions'], fn ($val) => $val > 0.5)) > 0;
            $livenessScore *= $hasExpressions ? 1.0 : 0.9;
        }

        // Check for texture analysis
        if (isset($faceData['texture_score'])) {
            $livenessScore *= min($faceData['texture_score'], 1.0);
        }

        return [
            'is_live' => $livenessScore >= self::ANTI_SPOOFING_THRESHOLD,
            'score' => $livenessScore,
            'checks_performed' => [
                'blink' => isset($faceData['blink_detected']),
                'movement' => isset($faceData['head_movement']),
                'expressions' => isset($faceData['expressions']),
                'texture' => isset($faceData['texture_score']),
            ],
        ];
    }
}
