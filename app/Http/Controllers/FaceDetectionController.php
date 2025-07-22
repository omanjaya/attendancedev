<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Repositories\FaceRecognitionRepository;
use App\Services\FaceRecognitionService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FaceDetectionController extends Controller
{
    use ApiResponseTrait;

    protected $faceRecognitionService;

    protected $faceRecognitionRepository;

    public function __construct(
        FaceRecognitionService $faceRecognitionService,
        FaceRecognitionRepository $faceRecognitionRepository
    ) {
        $this->middleware('auth:sanctum');
        $this->faceRecognitionService = $faceRecognitionService;
        $this->faceRecognitionRepository = $faceRecognitionRepository;
    }

    /**
     * Register a face for an employee.
     */
    public function registerFace(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'face_data' => 'required|array',
            'face_data.descriptor' => 'required|array|size:128',
            'face_data.confidence' => 'required|numeric|min:0.7|max:1',
            'face_data.algorithm' => 'nullable|string',
            'face_data.model_version' => 'nullable|string',
            'face_data.device_info' => 'nullable|array',
            'face_data.landmarks' => 'nullable|array',
            'face_data.pose' => 'nullable|array',
            'face_data.expressions' => 'nullable|array',
            'face_image' => 'nullable|image|mimes:jpeg,png|max:2048',
            'require_liveness' => 'boolean',
            'liveness_data' => 'nullable|array',
        ]);

        try {
            $employee = Employee::findOrFail($validated['employee_id']);
            $result = $this->faceRecognitionService->registerFace(
                $employee,
                $validated['face_data']['descriptor'],
                $request->file('face_image'),
                $validated['face_data']
            );

            return $this->successResponse(
                $result,
                'Face registered successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Face registration failed: '.$e->getMessage(),
                422
            );
        }
    }

    /**
     * Verify a face against registered employees.
     */
    public function verifyFace(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'face_data' => 'required|array',
            'face_data.descriptor' => 'required|array|size:128',
            'face_data.confidence' => 'required|numeric|min:0.5|max:1',
            'face_data.landmarks' => 'nullable|array',
            'face_data.pose' => 'nullable|array',
            'face_data.expressions' => 'nullable|array',
            'location' => 'nullable|array',
            'location.latitude' => 'nullable|numeric',
            'location.longitude' => 'nullable|numeric',
            'require_liveness' => 'boolean',
            'liveness_data' => 'nullable|array',
            'enforce_schedule' => 'boolean',
        ]);

        try {
            $options = [
                'location' => $validated['location'] ?? null,
                'require_liveness' => $validated['require_liveness'] ?? true,
                'enforce_schedule' => $validated['enforce_schedule'] ?? false,
            ];

            $result = $this->faceRecognitionService->verifyFaceInternal(
                $validated['face_data'],
                $options
            );

            if ($result['success']) {
                return $this->successResponse(
                    $result,
                    'Face verified successfully'
                );
            } else {
                return $this->errorResponse(
                    $result['message'],
                    404,
                    $result
                );
            }
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Face verification failed: '.$e->getMessage(),
                500
            );
        }
    }

    /**
     * Get all registered faces for face recognition training.
     */
    public function getRegisteredFaces(): JsonResponse
    {
        try {
            $employees = $this->faceRecognitionRepository->getEmployeesWithFaceData();

            $faces = $employees->map(function ($employee) {
                $faceData = $employee->metadata['face_recognition'] ?? null;

                if (! $faceData) {
                    return null;
                }

                return [
                    'employee_id' => $employee->id,
                    'name' => $employee->full_name,
                    'employee_code' => $employee->employee_id,
                    'descriptor' => $faceData['descriptor'],
                    'confidence' => $faceData['confidence'],
                    'quality_score' => $faceData['quality_score'] ?? null,
                    'algorithm' => $faceData['algorithm'] ?? 'face-api.js',
                    'model_version' => $faceData['model_version'] ?? '1.0',
                    'registered_at' => $faceData['registered_at'] ?? null,
                ];
            })->filter()->values();

            return $this->successResponse([
                'faces' => $faces,
                'count' => $faces->count(),
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve faces: '.$e->getMessage(),
                500
            );
        }
    }

    /**
     * Update face data for an employee.
     */
    public function updateFace(Request $request, Employee $employee): JsonResponse
    {
        $validated = $request->validate([
            'face_data' => 'required|array',
            'face_data.descriptor' => 'required|array|size:128',
            'face_data.confidence' => 'required|numeric|min:0.7|max:1',
            'face_data.algorithm' => 'nullable|string',
            'face_data.model_version' => 'nullable|string',
            'face_data.device_info' => 'nullable|array',
            'face_image' => 'nullable|image|mimes:jpeg,png|max:2048',
        ]);

        try {
            $result = $this->faceRecognitionService->updateFaceInternal(
                $employee->id,
                $validated['face_data'],
                $request->file('face_image')
            );

            return $this->successResponse(
                $result,
                'Face data updated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Face update failed: '.$e->getMessage(),
                422
            );
        }
    }

    /**
     * Delete face data for an employee.
     */
    public function deleteFace(Employee $employee): JsonResponse
    {
        try {
            $result = $this->faceRecognitionService->deleteFaceInternal($employee->id);

            return $this->successResponse(
                ['deleted' => $result],
                'Face data deleted successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Face deletion failed: '.$e->getMessage(),
                422
            );
        }
    }

    /**
     * Calculate cosine similarity between two face descriptors.
     */
    private function calculateCosineSimilarity(array $descriptor1, array $descriptor2)
    {
        if (count($descriptor1) !== count($descriptor2)) {
            return 0;
        }

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
     * Verify if the current location is within acceptable range.
     */
    private function verifyLocation(array $currentLocation, $employeeLocation)
    {
        // This is a simplified location verification
        // In production, you'd want more sophisticated geofencing

        if (! isset($currentLocation['latitude']) || ! isset($currentLocation['longitude'])) {
            return false;
        }

        // For now, always return true - implement proper geofencing based on location model
        return true;
    }

    /**
     * Get face detection statistics.
     */
    public function getStatistics(): JsonResponse
    {
        try {
            $statistics = $this->faceRecognitionService->getStatistics();

            return response()->json([
                'success' => true,
                'statistics' => $statistics, // JavaScript expects 'statistics' not 'data'
                'data' => $statistics, // Keep both for compatibility
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve statistics: '.$e->getMessage(),
                500
            );
        }
    }

    /**
     * Get employees without face data
     */
    public function getEmployeesWithoutFace(Request $request): JsonResponse
    {
        try {
            $locationId = $request->query('location_id');
            $employees = $this->faceRecognitionRepository->getEmployeesWithoutFaceData($locationId);

            return $this->successResponse([
                'employees' => $employees,
                'count' => $employees->count(),
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve employees: '.$e->getMessage(),
                500
            );
        }
    }

    /**
     * Get employees with low quality faces
     */
    public function getLowQualityFaces(Request $request): JsonResponse
    {
        try {
            $threshold = $request->query('threshold', 0.7);
            $employees = $this->faceRecognitionRepository->getEmployeesWithLowQualityFaces($threshold);

            return $this->successResponse([
                'employees' => $employees,
                'count' => $employees->count(),
                'threshold' => $threshold,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve employees: '.$e->getMessage(),
                500
            );
        }
    }

    /**
     * Get face recognition performance metrics
     */
    public function getPerformanceMetrics(Request $request): JsonResponse
    {
        try {
            $days = $request->query('days', 30);
            $metrics = $this->faceRecognitionRepository->getFaceRecognitionPerformance($days);

            return $this->successResponse($metrics);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve performance metrics: '.$e->getMessage(),
                500
            );
        }
    }

    /**
     * Batch verify faces
     */
    public function batchVerify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'faces' => 'required|array|max:50',
            'faces.*.descriptor' => 'required|array|size:128',
            'faces.*.confidence' => 'required|numeric|min:0.5|max:1',
        ]);

        try {
            $results = $this->faceRecognitionService->batchVerify($validated['faces']);

            return $this->successResponse([
                'results' => $results,
                'total_processed' => count($results),
                'successful_matches' => count(array_filter($results, fn ($r) => $r['success'])),
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Batch verification failed: '.$e->getMessage(),
                500
            );
        }
    }

    /**
     * Search employees by face status
     */
    public function searchByFaceStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:registered,not_registered,low_quality',
            'query' => 'nullable|string|max:255',
        ]);

        try {
            $employees = $this->faceRecognitionRepository->searchByFaceStatus(
                $validated['status'],
                $validated['query'] ?? null
            );

            return $this->successResponse([
                'employees' => $employees,
                'count' => $employees->count(),
                'status' => $validated['status'],
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Search failed: '.$e->getMessage(),
                500
            );
        }
    }

    /**
     * Get algorithm usage statistics.
     */
    private function getAlgorithmUsage()
    {
        $employees = Employee::whereNotNull('metadata->face_recognition->algorithm')
            ->where('is_active', true)
            ->get(['metadata']);

        $algorithms = [];
        foreach ($employees as $employee) {
            $algorithm = $employee->metadata['face_recognition']['algorithm'] ?? 'unknown';
            $algorithms[$algorithm] = ($algorithms[$algorithm] ?? 0) + 1;
        }

        return $algorithms;
    }
}
