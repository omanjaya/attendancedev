<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FaceDetectionController extends Controller
{
    /**
     * Register a face for an employee.
     */
    public function registerFace(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'face_data' => 'required|array',
            'face_data.descriptor' => 'required|array',
            'face_data.confidence' => 'required|numeric|min:0|max:1',
            'face_image' => 'nullable|image|max:2048', // 2MB max
        ]);

        try {
            DB::beginTransaction();

            $employee = Employee::findOrFail($validated['employee_id']);
            
            // Store face image if provided
            $imagePath = null;
            if ($request->hasFile('face_image')) {
                $imagePath = $request->file('face_image')->store(
                    'face-images/' . $employee->id, 
                    'private'
                );
            }

            // Prepare face data for storage
            $faceData = [
                'descriptor' => $validated['face_data']['descriptor'],
                'confidence' => $validated['face_data']['confidence'],
                'registered_at' => now()->toISOString(),
                'image_path' => $imagePath,
                'algorithm' => $request->input('algorithm', 'face-api.js'),
            ];

            // Store in employee metadata
            $metadata = $employee->metadata ?? [];
            $metadata['face_recognition'] = $faceData;
            
            $employee->update(['metadata' => $metadata]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Face registered successfully',
                'data' => [
                    'employee_id' => $employee->id,
                    'confidence' => $validated['face_data']['confidence'],
                    'image_stored' => !is_null($imagePath)
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Face registration failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify a face against registered employees.
     */
    public function verifyFace(Request $request)
    {
        $validated = $request->validate([
            'face_data' => 'required|array',
            'face_data.descriptor' => 'required|array',
            'face_data.confidence' => 'required|numeric|min:0|max:1',
            'location' => 'nullable|array',
            'location.latitude' => 'nullable|numeric',
            'location.longitude' => 'nullable|numeric',
        ]);

        try {
            $inputDescriptor = $validated['face_data']['descriptor'];
            $threshold = 0.6; // Similarity threshold
            
            // Get all employees with face data
            $employees = Employee::whereNotNull('metadata->face_recognition->descriptor')
                               ->where('is_active', true)
                               ->get();

            $bestMatch = null;
            $bestSimilarity = 0;

            foreach ($employees as $employee) {
                $storedDescriptor = $employee->metadata['face_recognition']['descriptor'] ?? null;
                
                if (!$storedDescriptor) continue;

                $similarity = $this->calculateCosineSimilarity($inputDescriptor, $storedDescriptor);
                
                if ($similarity > $threshold && $similarity > $bestSimilarity) {
                    $bestMatch = $employee;
                    $bestSimilarity = $similarity;
                }
            }

            if ($bestMatch) {
                // Verify location if provided
                $locationVerified = true;
                if (isset($validated['location']) && $bestMatch->location) {
                    $locationVerified = $this->verifyLocation(
                        $validated['location'],
                        $bestMatch->location
                    );
                }

                return response()->json([
                    'success' => true,
                    'employee' => [
                        'id' => $bestMatch->id,
                        'name' => $bestMatch->full_name,
                        'employee_id' => $bestMatch->employee_id,
                        'employee_type' => $bestMatch->employee_type,
                    ],
                    'confidence' => $bestSimilarity,
                    'location_verified' => $locationVerified,
                    'timestamp' => now()->toISOString()
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Face not recognized',
                'confidence' => $bestSimilarity,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Face verification failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all registered faces for face recognition training.
     */
    public function getRegisteredFaces()
    {
        try {
            $employees = Employee::whereNotNull('metadata->face_recognition->descriptor')
                               ->where('is_active', true)
                               ->get(['id', 'first_name', 'last_name', 'employee_id', 'metadata']);

            $faces = $employees->map(function ($employee) {
                $faceData = $employee->metadata['face_recognition'] ?? null;
                
                if (!$faceData) return null;

                return [
                    'employee_id' => $employee->id,
                    'name' => $employee->full_name,
                    'employee_code' => $employee->employee_id,
                    'descriptor' => $faceData['descriptor'],
                    'confidence' => $faceData['confidence'],
                    'algorithm' => $faceData['algorithm'] ?? 'face-api.js',
                ];
            })->filter()->values();

            return response()->json([
                'success' => true,
                'faces' => $faces,
                'count' => $faces->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve faces: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update face data for an employee.
     */
    public function updateFace(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'face_data' => 'required|array',
            'face_data.descriptor' => 'required|array',
            'face_data.confidence' => 'required|numeric|min:0|max:1',
            'face_image' => 'nullable|image|max:2048',
        ]);

        try {
            DB::beginTransaction();

            // Delete old face image if exists
            $oldImagePath = $employee->metadata['face_recognition']['image_path'] ?? null;
            if ($oldImagePath && Storage::disk('private')->exists($oldImagePath)) {
                Storage::disk('private')->delete($oldImagePath);
            }

            // Store new face image if provided
            $imagePath = null;
            if ($request->hasFile('face_image')) {
                $imagePath = $request->file('face_image')->store(
                    'face-images/' . $employee->id, 
                    'private'
                );
            }

            // Update face data
            $faceData = [
                'descriptor' => $validated['face_data']['descriptor'],
                'confidence' => $validated['face_data']['confidence'],
                'updated_at' => now()->toISOString(),
                'image_path' => $imagePath,
                'algorithm' => $request->input('algorithm', 'face-api.js'),
            ];

            $metadata = $employee->metadata ?? [];
            $metadata['face_recognition'] = $faceData;
            
            $employee->update(['metadata' => $metadata]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Face data updated successfully',
                'data' => [
                    'employee_id' => $employee->id,
                    'confidence' => $validated['face_data']['confidence'],
                    'image_updated' => !is_null($imagePath)
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Face update failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete face data for an employee.
     */
    public function deleteFace(Employee $employee)
    {
        try {
            DB::beginTransaction();

            // Delete face image if exists
            $imagePath = $employee->metadata['face_recognition']['image_path'] ?? null;
            if ($imagePath && Storage::disk('private')->exists($imagePath)) {
                Storage::disk('private')->delete($imagePath);
            }

            // Remove face data from metadata
            $metadata = $employee->metadata ?? [];
            unset($metadata['face_recognition']);
            
            $employee->update(['metadata' => $metadata]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Face data deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Face deletion failed: ' . $e->getMessage()
            ], 500);
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
        
        if (!isset($currentLocation['latitude']) || !isset($currentLocation['longitude'])) {
            return false;
        }

        // For now, always return true - implement proper geofencing based on location model
        return true;
    }

    /**
     * Get face detection statistics.
     */
    public function getStatistics()
    {
        try {
            $totalEmployees = Employee::where('is_active', true)->count();
            $registeredFaces = Employee::whereNotNull('metadata->face_recognition->descriptor')
                                     ->where('is_active', true)
                                     ->count();
            
            $statistics = [
                'total_employees' => $totalEmployees,
                'registered_faces' => $registeredFaces,
                'registration_percentage' => $totalEmployees > 0 ? round(($registeredFaces / $totalEmployees) * 100, 2) : 0,
                'algorithms_used' => $this->getAlgorithmUsage(),
            ];

            return response()->json([
                'success' => true,
                'statistics' => $statistics
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics: ' . $e->getMessage()
            ], 500);
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