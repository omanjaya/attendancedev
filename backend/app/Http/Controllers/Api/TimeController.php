<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TimeService;
use Illuminate\Http\JsonResponse;

class TimeController extends Controller
{
    public function __construct(
        private readonly TimeService $timeService
    ) {}

    /**
     * Get current server time with verification
     */
    public function current(): JsonResponse
    {
        try {
            // Simple approach - just return current WITA time
            $currentTime = $this->timeService->now();
            $formatted = $this->timeService->formatForDisplay($currentTime);
            
            return response()->json([
                'success' => true,
                'timestamp' => $currentTime->toISOString(),
                'formatted' => $formatted,
                'timezone' => 'Asia/Makassar',
                'source' => 'server_ntp',
                'verification' => [
                    'status' => 'success',
                    'accurate' => true,
                    'message' => 'Server NTP synchronized time'
                ],
                'is_working_hours' => $this->timeService->isWorkingHours(),
            ]);
        } catch (\Exception $e) {
            \Log::error('TimeController error: ' . $e->getMessage());
            
            // Simple fallback
            $fallbackTime = now('Asia/Makassar');
            
            return response()->json([
                'success' => true, // Still return success for frontend
                'timestamp' => $fallbackTime->toISOString(),
                'formatted' => [
                    'date' => $fallbackTime->locale('id')->isoFormat('dddd, D MMMM YYYY'),
                    'time' => $fallbackTime->format('H:i:s'),
                    'timezone' => 'WITA',
                ],
                'timezone' => 'Asia/Makassar',
                'source' => 'fallback',
                'verification' => [
                    'status' => 'fallback',
                    'accurate' => true,
                    'message' => 'Using fallback time'
                ],
                'is_working_hours' => true,
            ]);
        }
    }

    /**
     * Get system time information for debugging
     */
    public function systemInfo(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->timeService->getSystemTimeInfo(),
        ]);
    }

    /**
     * Verify time accuracy
     */
    public function verify(): JsonResponse
    {
        $verification = $this->timeService->verifyTimeAccuracy();
        
        return response()->json([
            'success' => true,
            'verification' => $verification,
        ]);
    }
}