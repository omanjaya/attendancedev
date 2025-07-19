<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AttendanceService;
use App\Services\FaceRecognitionService;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceService $attendanceService,
        private readonly FaceRecognitionService $faceRecognitionService
    ) {}

    /**
     * Process check-in with face recognition and GPS verification
     */
    public function checkIn(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'face_descriptor' => 'required|array|size:128',
            'face_descriptor.*' => 'required|numeric',
            'face_confidence' => 'required|numeric|min:0|max:1',
            'location' => 'required|array',
            'location.latitude' => 'required|numeric',
            'location.longitude' => 'required|numeric',
            'location.accuracy' => 'nullable|numeric',
            'location.address' => 'nullable|string',
            'photo' => 'nullable|image|max:2048',
            'liveness_data' => 'nullable|array',
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
            
            // Check if user has permission to check in for this employee
            if (!$this->canAccessEmployee($employee)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to employee data'
                ], 403);
            }

            // Verify face recognition
            $faceData = [
                'descriptor' => $request->face_descriptor,
                'confidence' => $request->face_confidence,
            ];

            // Add liveness data if provided
            if ($request->liveness_data) {
                $faceData = array_merge($faceData, $request->liveness_data);
            }

            $faceVerification = $this->faceRecognitionService->verifyFace(
                $request->face_descriptor,
                $employee,
                0.6 // threshold
            );

            if (!$faceVerification['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Face verification failed',
                    'data' => [
                        'confidence' => $faceVerification['confidence'] ?? 0,
                        'threshold' => 0.6,
                        'reason' => $faceVerification['message'] ?? 'Unknown error'
                    ]
                ], 400);
            }

            // Process attendance check-in
            $attendance = $this->attendanceService->checkIn(
                $employee,
                $request->location,
                $faceData,
                $request->file('photo')
            );

            return response()->json([
                'success' => true,
                'message' => 'Check-in successful',
                'data' => [
                    'attendance' => [
                        'id' => $attendance->id,
                        'employee_id' => $attendance->employee_id,
                        'date' => $attendance->date,
                        'check_in' => $attendance->check_in,
                        'status' => $attendance->status,
                        'location' => $attendance->check_in_location,
                    ],
                    'face_verification' => [
                        'confidence' => $faceVerification['confidence'],
                        'quality_score' => $faceVerification['quality_score'] ?? null,
                    ],
                    'employee' => [
                        'id' => $employee->id,
                        'name' => $employee->full_name,
                        'employee_id' => $employee->employee_id,
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Check-in failed', [
                'employee_id' => $request->employee_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Check-in failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process check-out with face recognition and GPS verification
     */
    public function checkOut(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'face_descriptor' => 'required|array|size:128',
            'face_descriptor.*' => 'required|numeric',
            'face_confidence' => 'required|numeric|min:0|max:1',
            'location' => 'required|array',
            'location.latitude' => 'required|numeric',
            'location.longitude' => 'required|numeric',
            'location.accuracy' => 'nullable|numeric',
            'location.address' => 'nullable|string',
            'photo' => 'nullable|image|max:2048',
            'liveness_data' => 'nullable|array',
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
            
            // Check if user has permission to check out for this employee
            if (!$this->canAccessEmployee($employee)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to employee data'
                ], 403);
            }

            // Verify face recognition
            $faceData = [
                'descriptor' => $request->face_descriptor,
                'confidence' => $request->face_confidence,
            ];

            // Add liveness data if provided
            if ($request->liveness_data) {
                $faceData = array_merge($faceData, $request->liveness_data);
            }

            $faceVerification = $this->faceRecognitionService->verifyFace(
                $request->face_descriptor,
                $employee,
                0.6 // threshold
            );

            if (!$faceVerification['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Face verification failed',
                    'data' => [
                        'confidence' => $faceVerification['confidence'] ?? 0,
                        'threshold' => 0.6,
                        'reason' => $faceVerification['message'] ?? 'Unknown error'
                    ]
                ], 400);
            }

            // Process attendance check-out
            $attendance = $this->attendanceService->checkOut(
                $employee,
                $request->location,
                $faceData,
                $request->file('photo')
            );

            return response()->json([
                'success' => true,
                'message' => 'Check-out successful',
                'data' => [
                    'attendance' => [
                        'id' => $attendance->id,
                        'employee_id' => $attendance->employee_id,
                        'date' => $attendance->date,
                        'check_in' => $attendance->check_in,
                        'check_out' => $attendance->check_out,
                        'working_hours' => $attendance->working_hours,
                        'overtime_hours' => $attendance->overtime_hours,
                        'status' => $attendance->status,
                        'location' => $attendance->check_out_location,
                    ],
                    'face_verification' => [
                        'confidence' => $faceVerification['confidence'],
                        'quality_score' => $faceVerification['quality_score'] ?? null,
                    ],
                    'employee' => [
                        'id' => $employee->id,
                        'name' => $employee->full_name,
                        'employee_id' => $employee->employee_id,
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Check-out failed', [
                'employee_id' => $request->employee_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Check-out failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get attendance status for an employee
     */
    public function getStatus(Request $request): JsonResponse
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
            
            // Check if user has permission to view this employee's data
            if (!$this->canAccessEmployee($employee)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to employee data'
                ], 403);
            }

            $status = $this->attendanceService->getAttendanceStatus($employee);

            return response()->json([
                'success' => true,
                'message' => 'Status retrieved successfully',
                'data' => $status
            ]);

        } catch (\Exception $e) {
            Log::error('Status retrieval failed', [
                'employee_id' => $request->employee_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Status retrieval failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get attendance statistics
     */
    public function getStatistics(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'nullable|exists:employees,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $filters = array_filter([
                'employee_id' => $request->employee_id,
                'date_from' => $request->date_from,
                'date_to' => $request->date_to,
            ]);

            // Check if user has permission to view this data
            if ($request->employee_id) {
                $employee = Employee::findOrFail($request->employee_id);
                if (!$this->canAccessEmployee($employee)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized access to employee data'
                    ], 403);
                }
            }

            $statistics = $this->attendanceService->getStatistics($filters);

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
     * Validate attendance data before processing
     */
    public function validateAttendance(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'face_descriptor' => 'required|array|size:128',
            'face_descriptor.*' => 'required|numeric',
            'location' => 'required|array',
            'location.latitude' => 'required|numeric',
            'location.longitude' => 'required|numeric',
            'action' => 'required|in:check_in,check_out',
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
            
            // Check if user has permission to access this employee
            if (!$this->canAccessEmployee($employee)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to employee data'
                ], 403);
            }

            $validationResults = [];

            // Validate face recognition
            $faceVerification = $this->faceRecognitionService->verifyFace(
                $request->face_descriptor,
                $employee,
                0.6
            );

            $validationResults['face_verification'] = [
                'valid' => $faceVerification['success'],
                'confidence' => $faceVerification['confidence'] ?? 0,
                'threshold' => 0.6,
                'message' => $faceVerification['message'] ?? ''
            ];

            // Validate location (basic validation)
            $locationValid = isset($request->location['latitude']) && 
                           isset($request->location['longitude']) &&
                           is_numeric($request->location['latitude']) &&
                           is_numeric($request->location['longitude']);

            $validationResults['location_validation'] = [
                'valid' => $locationValid,
                'message' => $locationValid ? 'Location is valid' : 'Invalid location coordinates'
            ];

            // Validate attendance rules
            $attendanceStatus = $this->attendanceService->getAttendanceStatus($employee);
            $attendanceValid = true;
            $attendanceMessage = '';

            if ($request->action === 'check_in') {
                if ($attendanceStatus['checked_in']) {
                    $attendanceValid = false;
                    $attendanceMessage = 'Already checked in today';
                }
            } elseif ($request->action === 'check_out') {
                if (!$attendanceStatus['checked_in']) {
                    $attendanceValid = false;
                    $attendanceMessage = 'Must check in first';
                } elseif ($attendanceStatus['checked_out']) {
                    $attendanceValid = false;
                    $attendanceMessage = 'Already checked out today';
                }
            }

            $validationResults['attendance_validation'] = [
                'valid' => $attendanceValid,
                'message' => $attendanceValid ? 'Attendance rules satisfied' : $attendanceMessage,
                'current_status' => $attendanceStatus
            ];

            $overallValid = $validationResults['face_verification']['valid'] &&
                           $validationResults['location_validation']['valid'] &&
                           $validationResults['attendance_validation']['valid'];

            return response()->json([
                'success' => true,
                'message' => 'Validation completed',
                'data' => [
                    'valid' => $overallValid,
                    'can_proceed' => $overallValid,
                    'validation_results' => $validationResults,
                    'employee' => [
                        'id' => $employee->id,
                        'name' => $employee->full_name,
                        'employee_id' => $employee->employee_id,
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Attendance validation failed', [
                'employee_id' => $request->employee_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if the current user can access the employee data
     */
    private function canAccessEmployee(Employee $employee): bool
    {
        $user = Auth::user();
        
        if (!$user) {
            return false;
        }

        // Super admin can access all employees
        if ($user->hasRole('superadmin')) {
            return true;
        }

        // Admin can access employees in their location
        if ($user->hasRole('admin')) {
            return $user->employee && 
                   $user->employee->location_id === $employee->location_id;
        }

        // Users can only access their own employee data
        return $user->employee && $user->employee->id === $employee->id;
    }
}