<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FaceRecognitionRequest;
use App\Services\FaceRecognitionService;
use App\Services\DeepFaceLoadBalancer;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;

class FaceRecognitionController extends Controller
{
    public function __construct(
        private readonly FaceRecognitionService $faceRecognitionService,
        private readonly DeepFaceLoadBalancer $deepFaceLoadBalancer
    ) {
    }

    /**
     * Register face for an employee
     */
    public function registerFace(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'descriptor' => 'required|array|min:128',  // Support 128-d (face-api.js) OR 512-d (DeepFace ArcFace)
            'descriptor.*' => 'required|numeric',
            'confidence' => 'required|numeric|min:0|max:1',
            'image' => 'nullable|image|max:2048',
            'algorithm' => 'nullable|string|max:50',
            'model_version' => 'nullable|string|max:20',
            'device_info' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Authorization check
        $user = $request->user();
        $isOwnProfile = $user->employee && $user->employee->id == $request->employee_id;

        if (!$isOwnProfile && !$user->can('manage_employees')) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You do not have the required permission.',
                'required_permission' => 'manage_employees'
            ], 403);
        }

        try {
            $employee = Employee::findOrFail($request->employee_id);

            $metadata = [
                'confidence' => $request->confidence,
                'algorithm' => $request->algorithm ?? 'face-api.js',
                'model_version' => $request->model_version ?? '1.0',
                'device_info' => $request->device_info ?? [],
            ];

            $result = $this->faceRecognitionService->registerFace(
                $employee,
                $request->descriptor,
                $request->file('image'),
                $metadata
            );

            return response()->json([
                'success' => true,
                'message' => 'Face registered successfully',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Face registration failed', [
                'employee_id' => $request->employee_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Face registration failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify face against registered employees
     */
    public function verifyFace(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'descriptor' => 'required|array|size:128',
            'descriptor.*' => 'required|numeric',
            'confidence' => 'required|numeric|min:0|max:1',
            'employee_id' => 'nullable|exists:employees,id',
            'threshold' => 'nullable|numeric|min:0|max:1',
            'require_liveness' => 'nullable|boolean',
            'liveness_data' => 'nullable|array',
            'location' => 'nullable|array',
            'location.latitude' => 'required_with:location|numeric',
            'location.longitude' => 'required_with:location|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $employee = $request->employee_id ? Employee::find($request->employee_id) : null;
            $threshold = $request->threshold ?? 0.6;

            $result = $this->faceRecognitionService->verifyFace(
                $request->descriptor,
                $employee,
                $threshold
            );

            return response()->json([
                'success' => true,
                'message' => $result['success'] ? 'Face verified successfully' : 'Face not recognized',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Face verification failed', [
                'employee_id' => $request->employee_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Face verification failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update face data for an employee
     */
    public function updateFaceData(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'descriptor' => 'required|array|min:128',  // Support 128-d (face-api.js) OR 512-d (DeepFace ArcFace)
            'descriptor.*' => 'required|numeric',
            'confidence' => 'required|numeric|min:0|max:1',
            'image' => 'nullable|image|max:2048',
            'algorithm' => 'nullable|string|max:50',
            'model_version' => 'nullable|string|max:20',
            'device_info' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Authorization check
        $user = $request->user();
        $isOwnProfile = $user->employee && $user->employee->id == $request->employee_id;

        if (!$isOwnProfile && !$user->can('manage_employees')) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You do not have the required permission.',
                'required_permission' => 'manage_employees'
            ], 403);
        }

        try {
            $employee = Employee::findOrFail($request->employee_id);

            $metadata = [
                'confidence' => $request->confidence,
                'algorithm' => $request->algorithm ?? 'face-api.js',
                'model_version' => $request->model_version ?? '1.0',
                'device_info' => $request->device_info ?? [],
            ];

            $result = $this->faceRecognitionService->updateFaceData(
                $employee,
                $request->descriptor,
                $metadata
            );

            return response()->json([
                'success' => true,
                'message' => 'Face data updated successfully',
                'data' => ['updated' => $result]
            ]);

        } catch (\Exception $e) {
            Log::error('Face data update failed', [
                'employee_id' => $request->employee_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Face data update failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete face data for an employee
     */
    public function deleteFaceData(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Authorization check
        $user = $request->user();
        $isOwnProfile = $user->employee && $user->employee->id == $request->employee_id;

        if (!$isOwnProfile && !$user->can('manage_employees')) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You do not have the required permission.',
                'required_permission' => 'manage_employees'
            ], 403);
        }

        try {
            $employee = Employee::findOrFail($request->employee_id);

            $result = $this->faceRecognitionService->deleteFaceData($employee);

            return response()->json([
                'success' => true,
                'message' => 'Face data deleted successfully',
                'data' => ['deleted' => $result]
            ]);

        } catch (\Exception $e) {
            Log::error('Face data deletion failed', [
                'employee_id' => $request->employee_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Face data deletion failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get face data for an employee
     */
    public function getFaceData(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Authorization check
        $user = $request->user();
        $isOwnProfile = $user->employee && $user->employee->id == $request->employee_id;

        if (!$isOwnProfile && !$user->can('view_employees')) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You do not have the required permission.',
                'required_permission' => 'view_employees'
            ], 403);
        }

        try {
            $employee = Employee::findOrFail($request->employee_id);

            $faceData = $this->faceRecognitionService->getFaceData($employee);

            return response()->json([
                'success' => true,
                'message' => 'Face data retrieved successfully',
                'data' => [
                    'has_face_data' => !is_null($faceData),
                    'face_data' => $faceData ? [
                        'algorithm' => $faceData['algorithm'] ?? null,
                        'model_version' => $faceData['model_version'] ?? null,
                        'confidence' => $faceData['confidence'] ?? null,
                        'quality_score' => $faceData['quality_score'] ?? null,
                        'registered_at' => $faceData['registered_at'] ?? null,
                        'updated_at' => $faceData['updated_at'] ?? null,
                    ] : null
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Face data retrieval failed', [
                'employee_id' => $request->employee_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Face data retrieval failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Batch verify multiple faces
     */
    public function batchVerify(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'faces' => 'required|array|min:1|max:10',
            'faces.*' => 'required|array|size:128',
            'faces.*.*' => 'required|numeric',
            'threshold' => 'nullable|numeric|min:0|max:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $threshold = $request->threshold ?? 0.6;

            $results = $this->faceRecognitionService->batchVerify(
                $request->faces,
                $threshold
            );

            return response()->json([
                'success' => true,
                'message' => 'Batch verification completed',
                'data' => [
                    'results' => $results,
                    'total_faces' => count($request->faces),
                    'successful_matches' => collect($results)->where('success', true)->count(),
                    'threshold_used' => $threshold,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Batch face verification failed', [
                'faces_count' => count($request->faces ?? []),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Batch verification failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check liveness of face
     */
    public function checkLiveness(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'face_data' => 'required|array',
            'blink_detected' => 'nullable|boolean',
            'head_movement' => 'nullable|numeric|min:0|max:1',
            'expressions' => 'nullable|array',
            'texture_score' => 'nullable|numeric|min:0|max:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $faceData = array_merge($request->face_data, [
                'blink_detected' => $request->blink_detected,
                'head_movement' => $request->head_movement,
                'expressions' => $request->expressions,
                'texture_score' => $request->texture_score,
            ]);

            $result = $this->faceRecognitionService->checkLiveness($faceData);

            return response()->json([
                'success' => true,
                'message' => 'Liveness check completed',
                'data' => [
                    'is_live' => $result,
                    'score' => $faceData['texture_score'] ?? 0,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Liveness check failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Liveness check failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get face recognition statistics
     */
    public function getStatistics(): JsonResponse
    {
        try {
            $statistics = $this->faceRecognitionService->getStatistics();

            return response()->json([
                'success' => true,
                'message' => 'Statistics retrieved successfully',
                'data' => $statistics
            ]);

        } catch (\Exception $e) {
            Log::error('Statistics retrieval failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Statistics retrieval failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Extract face encoding using Python service (server-side processing)
     *
     * This endpoint uploads the image to the Python face recognition service
     * which extracts the 128-d face descriptor using dlib.
     */
    public function extractEncodingServer(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|string', // Base64 encoded image
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $serviceUrl = config('services.face_recognition.url', env('FACE_RECOGNITION_SERVICE_URL', 'http://127.0.0.1:5000'));
            $timeout = config('services.face_recognition.timeout', env('FACE_RECOGNITION_SERVICE_TIMEOUT', 30));

            // Forward request to Python service
            $response = Http::timeout($timeout)
                ->post("{$serviceUrl}/extract-encoding", [
                    'image' => $request->image,
                ]);

            if (!$response->successful()) {
                throw new \Exception('Python service request failed: ' . $response->body());
            }

            $result = $response->json();

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Face encoding extraction failed',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Face encoding extracted successfully',
                'data' => [
                    'encoding' => $result['encoding'],
                    'confidence' => $result['confidence'],
                    'algorithm' => 'dlib',
                    'model_version' => '19.24',
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Server-side face encoding extraction failed', [
                'error' => $e->getMessage(),
                'service_url' => $serviceUrl ?? 'not set',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Server-side face encoding extraction failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify face using Python service (server-side processing)
     *
     * This endpoint uploads the image to the Python face recognition service
     * which verifies it against all registered employee face encodings.
     */
    public function verifyFaceServer(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|string', // Base64 encoded image
            'tolerance' => 'nullable|numeric|min:0.3|max:0.9',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $serviceUrl = config('services.face_recognition.url', env('FACE_RECOGNITION_SERVICE_URL', 'http://127.0.0.1:5000'));
            $timeout = config('services.face_recognition.timeout', env('FACE_RECOGNITION_SERVICE_TIMEOUT', 30));
            $tolerance = $request->tolerance ?? config('services.face_recognition.tolerance', env('FACE_RECOGNITION_TOLERANCE', 0.6));

            // Get all employees with face encodings
            $employees = Employee::whereNotNull('face_descriptor')
                ->whereNotNull('face_image_path')
                ->select('id', 'employee_code', 'full_name', 'face_descriptor')
                ->get();

            if ($employees->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'matched' => false,
                    'message' => 'No registered employees with face data found',
                ], 404);
            }

            // Prepare known encodings for Python service
            $knownEncodings = $employees->map(function ($employee) {
                return [
                    'employee_id' => $employee->id,
                    'employee_code' => $employee->employee_code,
                    'name' => $employee->full_name,
                    'encoding' => json_decode($employee->face_descriptor, true),
                ];
            })->toArray();

            // Forward request to Python service
            $response = Http::timeout($timeout)
                ->post("{$serviceUrl}/verify-face", [
                    'image' => $request->image,
                    'known_encodings' => $knownEncodings,
                    'tolerance' => $tolerance,
                ]);

            if (!$response->successful()) {
                throw new \Exception('Python service request failed: ' . $response->body());
            }

            $result = $response->json();

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'matched' => false,
                    'message' => $result['message'] ?? 'Face verification failed',
                ], 422);
            }

            // Return matched result
            return response()->json([
                'success' => true,
                'matched' => $result['matched'],
                'message' => $result['message'],
                'data' => $result['matched'] ? [
                    'employee' => $result['employee'],
                    'distance' => $result['distance'],
                    'similarity' => $result['similarity'],
                    'confidence' => $result['confidence'],
                    'tolerance_used' => $tolerance,
                    'algorithm' => 'dlib',
                ] : [
                    'confidence' => $result['confidence'] ?? null,
                    'tolerance_used' => $tolerance,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Server-side face verification failed', [
                'error' => $e->getMessage(),
                'service_url' => $serviceUrl ?? 'not set',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Server-side face verification failed: ' . $e->getMessage()
            ], 500);
        }
    }

    // ========================================
    // DeepFace Service Methods (ArcFace 512-d)
    // ========================================

    /**
     * Health check for DeepFace service
     *
     * GET /api/v1/face/deepface/health
     */
    public function healthDeepFace(): JsonResponse
    {
        try {
            // Use load balancer to get cluster status
            $clusterStatus = $this->deepFaceLoadBalancer->getClusterStatus();

            $healthyCount = $clusterStatus['healthy_instances'];
            $totalCount = $clusterStatus['total_instances'];

            return response()->json([
                'success' => true,
                'service' => 'DeepFace Face Recognition Cluster',
                'status' => $healthyCount > 0 ? 'operational' : 'down',
                'healthy_instances' => $healthyCount,
                'total_instances' => $totalCount,
                'cluster_health_percentage' => ($healthyCount / max($totalCount, 1)) * 100,
            ]);

        } catch (\Exception $e) {
            Log::error('DeepFace cluster health check failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'DeepFace cluster unavailable',
                'error' => $e->getMessage(),
            ], 503);
        }
    }

    /**
     * Get detailed DeepFace cluster status
     *
     * GET /api/v1/face/deepface/cluster-status
     */
    public function clusterStatusDeepFace(): JsonResponse
    {
        try {
            $clusterStatus = $this->deepFaceLoadBalancer->getClusterStatus();

            return response()->json([
                'success' => true,
                'cluster' => $clusterStatus,
                'recommendation' => $this->getClusterRecommendation($clusterStatus),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get cluster status', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve cluster status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get cluster recommendations based on health status
     */
    private function getClusterRecommendation(array $status): array
    {
        $healthy = $status['healthy_instances'];
        $total = $status['total_instances'];
        $percentage = ($healthy / max($total, 1)) * 100;

        $recommendations = [];

        if ($percentage < 50) {
            $recommendations[] = '⚠️ CRITICAL: Less than 50% of instances healthy - immediate action required!';
            $recommendations[] = 'Check logs: tail -f python-services/face-recognition/logs/*.log';
            $recommendations[] = 'Restart unhealthy instances: cd python-services/face-recognition && ./start-cluster.sh';
        } elseif ($percentage < 75) {
            $recommendations[] = '⚠️ WARNING: Less than 75% of instances healthy';
            $recommendations[] = 'Consider restarting unhealthy instances';
        } elseif ($percentage === 100.0) {
            $recommendations[] = '✅ All instances healthy - cluster operating optimally';
        } else {
            $recommendations[] = '✅ Cluster healthy but some instances down';
            $recommendations[] = 'Monitor for recurring failures';
        }

        // Check response times
        $avgResponseTime = 0;
        $responseCount = 0;
        foreach ($status['instances'] as $instance) {
            if (isset($instance['response_time']) && $instance['healthy']) {
                $avgResponseTime += $instance['response_time'];
                $responseCount++;
            }
        }

        if ($responseCount > 0) {
            $avgResponseTime /= $responseCount;
            if ($avgResponseTime > 3.0) {
                $recommendations[] = "⚠️ High average response time: {$avgResponseTime}s (target: <2s)";
                $recommendations[] = 'Consider adding more instances or using GPU acceleration';
            }
        }

        return [
            'health_percentage' => round($percentage, 1),
            'status' => $percentage >= 75 ? 'healthy' : ($percentage >= 50 ? 'degraded' : 'critical'),
            'recommendations' => $recommendations,
        ];
    }

    /**
     * Extract 512-d face embedding using DeepFace (ArcFace model)
     *
     * POST /api/v1/face/deepface/extract-embedding
     *
     * Request: image file
     * Response: 512-d embedding array + confidence + quality metrics
     */
    public function extractEmbeddingDeepFace(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|file|mimes:jpg,jpeg,png|max:10240', // 10MB max
        ]);

        try {
            $image = $request->file('image');

            // Use load balancer to extract embedding (with automatic failover)
            $data = $this->deepFaceLoadBalancer->extractEmbedding($image);

            Log::info('DeepFace embedding extracted (load balanced)', [
                'dimension' => $data['dimension'] ?? null,
                'confidence' => $data['confidence'] ?? null,
                'model' => $data['model'] ?? 'ArcFace',
            ]);

            return response()->json($data);

        } catch (\Exception $e) {
            Log::error('DeepFace embedding extraction error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process image: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check liveness using DeepFace (anti-spoofing)
     *
     * POST /api/v1/face/deepface/check-liveness
     *
     * Request: image file
     * Response: is_live boolean + confidence
     */
    public function checkLivenessDeepFace(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|file|mimes:jpg,jpeg,png|max:10240',
        ]);

        try {
            $image = $request->file('image');

            // Use load balancer for liveness check (with automatic failover)
            $data = $this->deepFaceLoadBalancer->checkLiveness($image);

            Log::info('DeepFace liveness check completed (load balanced)', [
                'is_live' => $data['is_live'] ?? null,
                'result' => $data['result'] ?? null,
            ]);

            return response()->json($data);

        } catch (\Exception $e) {
            Log::error('DeepFace liveness check error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to check liveness: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify face using DeepFace service (ArcFace 512-d embeddings)
     *
     * POST /api/v1/face/deepface/verify
     *
     * Request:
     * - image: file
     * - employee_id: optional - if provided, verifies against specific employee
     *               if not provided, verifies against all registered employees
     *
     * Response:
     * {
     *   "success": true,
     *   "matched": true,
     *   "employee": {...},
     *   "distance": 0.45,
     *   "similarity": 0.55,
     *   "confidence": 0.92,
     *   "is_live": true
     * }
     */
    public function verifyFaceDeepFace(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|file|mimes:jpg,jpeg,png|max:10240',
            'employee_id' => 'nullable|exists:employees,id',
        ]);

        try {
            $serviceUrl = env('DEEPFACE_SERVICE_URL', 'http://127.0.0.1:8001');
            $image = $request->file('image');

            // Get employees with face embeddings (512-d) from metadata
            $query = Employee::whereRaw('json_extract(metadata, "$.face_recognition.descriptor") IS NOT NULL')
                ->select('id', 'employee_id', 'full_name', 'metadata');

            // If specific employee ID provided, filter to that employee
            if ($request->has('employee_id')) {
                $query->where('id', $request->employee_id);
            }

            $employees = $query->get();

            if ($employees->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'matched' => false,
                    'message' => 'No registered employees with face data found',
                ], 404);
            }

            // Prepare known faces with embeddings for DeepFace service
            $knownFaces = $employees->map(function ($employee) {
                $metadata = $employee->metadata;
                // Check if face_recognition exists and has descriptor
                if (!isset($metadata['face_recognition']['descriptor'])) {
                    return null;
                }

                $embedding = $metadata['face_recognition']['descriptor'];

                return [
                    'employee_id' => $employee->id,
                    'employee_code' => $employee->employee_id,
                    'name' => $employee->full_name,
                    'embedding' => $embedding,
                ];
            })->filter()->values()->toArray();

            if (empty($knownFaces)) {
                return response()->json([
                    'success' => false,
                    'matched' => false,
                    'message' => 'No valid face embeddings found',
                ], 404);
            }
            // Forward to DeepFace service
            $response = Http::timeout(30)
                ->attach('image', file_get_contents($image->getRealPath()), $image->getClientOriginalName())
                ->post("{$serviceUrl}/verify-face", [
                    'known_faces_json' => json_encode($knownFaces),
                ]);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('DeepFace verification completed', [
                    'matched' => $data['matched'] ?? null,
                    'employee_id' => $data['employee']['id'] ?? null,
                    'distance' => $data['distance'] ?? null,
                    'is_live' => $data['is_live'] ?? null,
                ]);

                return response()->json($data);
            }

            $error = $response->json();
            Log::warning('DeepFace verification failed', [
                'status' => $response->status(),
                'error' => $error,
            ]);

            return response()->json([
                'success' => false,
                'message' => $error['message'] ?? 'Failed to verify face',
                'error' => $error,
            ], $response->status());

        } catch (\Exception $e) {
            Log::error('DeepFace verification error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to verify face',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}