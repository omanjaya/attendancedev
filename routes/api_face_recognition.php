<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FaceRecognitionController;
use App\Http\Controllers\Api\AttendanceController;

/*
|--------------------------------------------------------------------------
| Face Recognition API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for face recognition and
| attendance with face verification. These routes are protected by auth.
|
*/

Route::middleware(['auth:sanctum'])->group(function () {
    
    // Face Recognition Management
    Route::prefix('face-recognition')->group(function () {
        Route::post('/register', [FaceRecognitionController::class, 'registerFace']);
        Route::post('/verify', [FaceRecognitionController::class, 'verifyFace']);
        Route::post('/update', [FaceRecognitionController::class, 'updateFaceData']);
        Route::post('/delete', [FaceRecognitionController::class, 'deleteFaceData']);
        Route::post('/get-data', [FaceRecognitionController::class, 'getFaceData']);
        Route::post('/batch-verify', [FaceRecognitionController::class, 'batchVerify']);
        Route::post('/check-liveness', [FaceRecognitionController::class, 'checkLiveness']);
        Route::get('/statistics', [FaceRecognitionController::class, 'getStatistics']);
    });

    // Attendance with Face Recognition
    Route::prefix('attendance')->group(function () {
        Route::post('/check-in', [AttendanceController::class, 'checkIn']);
        Route::post('/check-out', [AttendanceController::class, 'checkOut']);
        Route::post('/status', [AttendanceController::class, 'getStatus']);
        Route::post('/statistics', [AttendanceController::class, 'getStatistics']);
        Route::post('/validate', [AttendanceController::class, 'validateAttendance']);
    });
});

// Public routes for testing (should be removed in production)
Route::middleware(['auth:sanctum'])->prefix('test')->group(function () {
    Route::get('/face-recognition/health', function () {
        return response()->json([
            'success' => true,
            'message' => 'Face recognition API is healthy',
            'timestamp' => now()->toISOString(),
            'version' => '1.0.0'
        ]);
    });
});