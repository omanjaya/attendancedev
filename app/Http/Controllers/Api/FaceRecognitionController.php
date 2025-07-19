<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FaceRecognitionRequest;
use App\Services\FaceRecognitionService;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class FaceRecognitionController extends Controller
{
    public function __construct(
        private readonly FaceRecognitionService $faceRecognitionService
    ) {}

    /**
     * Register face for an employee
     */
    public function registerFace(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'descriptor' => 'required|array|size:128',
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
            'descriptor' => 'required|array|size:128',
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
}