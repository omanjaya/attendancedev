<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FaceVerificationController extends Controller
{
    /**
     * Get user's profile photo and face descriptor for verification
     */
    public function getProfileData(Request $request)
    {
        try {
            \Log::info('Face verification profile data request', [
                'user_authenticated' => auth()->check(),
                'user_id' => auth()->id(),
                'request_headers' => $request->headers->all(),
            ]);

            $user = $request->user();
            $employee = $user->employee;

            if (! $employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee profile not found',
                ], 404);
            }

            // Check if profile photo exists
            if (! $employee->photo_path) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'profile_photo_url' => null,
                        'face_descriptor' => null,
                        'face_registered' => false,
                        'employee_name' => $employee->full_name,
                        'needs_photo_upload' => true,
                    ],
                    'message' => 'No profile photo uploaded. Please upload a profile photo first.',
                ], 200);
            }

            // Get face descriptor if exists
            $faceDescriptor = $user->face_descriptor ?? null;

            return response()->json([
                'success' => true,
                'data' => [
                    'profile_photo_url' => $employee->photo_url,
                    'face_descriptor' => $faceDescriptor,
                    'face_registered' => ! empty($faceDescriptor),
                    'employee_name' => $employee->full_name,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Face verification error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to load profile data',
            ], 500);
        }
    }

    /**
     * Save face descriptor from profile photo
     */
    public function saveFaceDescriptor(Request $request)
    {
        Log::info('Face descriptor save request', [
            'user_authenticated' => auth()->check(),
            'user_id' => auth()->id(),
            'request_data' => $request->all(),
            'headers' => $request->headers->all(),
        ]);

        try {
            $request->validate([
                'face_descriptor' => 'required|array',
                'confidence' => 'nullable|numeric|min:0|max:100',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Face descriptor validation failed', [
                'errors' => $e->errors(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }

        try {
            $user = $request->user();

            // Save face descriptor
            $user->face_descriptor = $request->face_descriptor;
            $user->face_registered_at = now();
            $user->save();

            // Update employee metadata
            $employee = $user->employee;
            if ($employee) {
                // Ensure metadata is an array - handle case where it might be null or string
                $metadata = [];
                if ($employee->metadata) {
                    $metadata = is_array($employee->metadata) ? $employee->metadata : [];
                }

                $metadata['face_recognition'] = [
                    'descriptor' => $request->face_descriptor,
                    'confidence' => $request->confidence ?? 85,
                    'registered_at' => now()->toISOString(),
                    'model_version' => 'face-api-js-1.0',
                ];
                $employee->metadata = $metadata;
                $employee->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Face descriptor saved successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Save face descriptor error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to save face descriptor',
            ], 500);
        }
    }

    /**
     * Verify face against stored descriptor
     */
    public function verifyFace(Request $request)
    {
        $request->validate([
            'face_descriptor' => 'required|array',
            'liveness_check' => 'required|array',
            'liveness_check.gesture' => 'required|string|in:smile,shake_head,nod',
            'liveness_check.completed' => 'required|boolean',
            'liveness_check.confidence' => 'required|numeric|min:0.7',
        ]);

        try {
            $user = $request->user();

            if (! $user->face_descriptor) {
                return response()->json([
                    'success' => false,
                    'message' => 'No face descriptor registered. Please register your face first.',
                ], 400);
            }

            // Here you would typically compare face descriptors
            // For now, we'll simulate the verification
            $similarity = $this->calculateSimilarity(
                $user->face_descriptor,
                $request->face_descriptor
            );

            $verified = $similarity >= 0.7 && $request->liveness_check['completed'];

            // Log verification attempt
            Log::info('Face verification attempt', [
                'user_id' => $user->id,
                'similarity' => $similarity,
                'liveness_gesture' => $request->liveness_check['gesture'],
                'liveness_completed' => $request->liveness_check['completed'],
                'verified' => $verified,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'verified' => $verified,
                    'similarity' => $similarity,
                    'liveness_passed' => $request->liveness_check['completed'],
                    'message' => $verified
                        ? 'Face verified successfully!'
                        : 'Face verification failed. Please try again.',
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Face verification error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Face verification failed',
            ], 500);
        }
    }

    /**
     * Calculate similarity between two face descriptors
     */
    private function calculateSimilarity(array $descriptor1, array $descriptor2): float
    {
        // Simple Euclidean distance calculation
        // In production, you might use more sophisticated algorithms
        $distance = 0;
        $length = min(count($descriptor1), count($descriptor2));

        for ($i = 0; $i < $length; $i++) {
            $distance += pow($descriptor1[$i] - $descriptor2[$i], 2);
        }

        $distance = sqrt($distance);

        // Convert distance to similarity score (0-1)
        // Lower distance means higher similarity
        $similarity = 1 / (1 + $distance);

        return $similarity;
    }
}
