<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FaceRecognitionController extends Controller
{
    public function enroll(Request $request)
    {
        $request->validate([
            'face_descriptor' => 'required|array',
        ]);

        $user = Auth::user();

        try {
            $user->face_descriptor = $request->input('face_descriptor');
            $user->face_registered_at = now();
            $user->save();

            Log::info('Face descriptor enrolled for user', ['user_id' => $user->id]);

            return response()->json([
                'success' => true,
                'message' => 'Face data enrolled successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to enroll face data', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to enroll face data.',
            ], 500);
        }
    }

    public function verify(Request $request)
    {
        $request->validate([
            'face_descriptor' => 'required|array',
        ]);

        $user = Auth::user();

        if (! $user->face_descriptor) {
            return response()->json([
                'success' => false,
                'message' => 'No face data registered for this user.',
            ], 400);
        }

        try {
            $liveDescriptor = $request->input('face_descriptor');
            $registeredDescriptor = $user->face_descriptor;

            // Calculate Euclidean distance between descriptors
            $distance = $this->euclideanDistance($liveDescriptor, $registeredDescriptor);

            // Threshold for face matching (adjust as needed)
            $threshold = 0.6; // Common threshold for face-api.js

            if ($distance < $threshold) {
                Log::info('Face verification successful', ['user_id' => $user->id, 'distance' => $distance]);

                return response()->json([
                    'success' => true,
                    'message' => 'Face verified successfully.',
                    'distance' => $distance,
                ]);
            } else {
                Log::warning('Face verification failed', ['user_id' => $user->id, 'distance' => $distance]);

                return response()->json([
                    'success' => false,
                    'message' => 'Face verification failed. Please try again.',
                    'distance' => $distance,
                ], 401);
            }
        } catch (\Exception $e) {
            Log::error('Error during face verification', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error during face verification.',
            ], 500);
        }
    }

    private function euclideanDistance(array $descriptor1, array $descriptor2): float
    {
        if (count($descriptor1) !== count($descriptor2)) {
            throw new \InvalidArgumentException('Descriptors must have the same dimension.');
        }

        $sum = 0;
        for ($i = 0; $i < count($descriptor1); $i++) {
            $sum += pow($descriptor1[$i] - $descriptor2[$i], 2);
        }

        return sqrt($sum);
    }
}
