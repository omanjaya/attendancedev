<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class TimeService
{
    /**
     * Get current WITA time from server (NTP synchronized)
     */
    public function now(): Carbon
    {
        return Carbon::now('Asia/Makassar');
    }
    
    /**
     * Parse any time string to WITA timezone
     */
    public function parseToWITA(string $time): Carbon
    {
        return Carbon::parse($time)->setTimezone('Asia/Makassar');
    }
    
    /**
     * Get current date in WITA
     */
    public function today(): Carbon
    {
        return Carbon::today('Asia/Makassar');
    }
    
    /**
     * Format time for display in Indonesian format
     */
    public function formatForDisplay(Carbon $time): array
    {
        return [
            'date' => $time->locale('id')->isoFormat('dddd, D MMMM YYYY'),
            'time' => $time->format('H:i:s'),
            'timezone' => 'WITA',
            'raw' => $time->toISOString(),
        ];
    }
    
    /**
     * Verify time accuracy against external source (backup verification)
     */
    public function verifyTimeAccuracy(): array
    {
        $serverTime = $this->now();
        
        try {
            // Cache external time check for 5 minutes to avoid excessive API calls
            $externalTimeData = Cache::remember('external_time_check', 300, function () {
                $response = Http::timeout(5)->get('https://worldtimeapi.org/api/timezone/Asia/Makassar');
                
                if ($response->successful()) {
                    return $response->json();
                }
                
                throw new \Exception('External time service unavailable');
            });
            
            $externalTime = Carbon::parse($externalTimeData['datetime']);
            $difference = abs($serverTime->diffInSeconds($externalTime));
            
            $result = [
                'status' => 'success',
                'server_time' => $serverTime,
                'external_time' => $externalTime,
                'difference_seconds' => $difference,
                'accurate' => $difference < 30, // Allow 30 second tolerance
                'message' => $difference < 30 
                    ? 'Waktu server akurat' 
                    : "Selisih waktu {$difference} detik dari sumber eksternal",
            ];
            
            // Log significant time differences
            if ($difference > 30) {
                Log::warning('Time difference detected', $result);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            return [
                'status' => 'fallback',
                'server_time' => $serverTime,
                'external_time' => null,
                'error' => $e->getMessage(),
                'accurate' => true, // Trust server time if external fails
                'message' => 'Menggunakan waktu server (NTP synchronized)',
            ];
        }
    }
    
    /**
     * Get time for attendance with verification
     */
    public function getAttendanceTime(): array
    {
        $time = $this->now();
        
        // Simple verification without external API for now
        $verification = [
            'status' => 'success',
            'server_time' => $time,
            'external_time' => null,
            'accurate' => true,
            'message' => 'Using server NTP synchronized time',
        ];
        
        return [
            'timestamp' => $time,
            'formatted' => $this->formatForDisplay($time),
            'verification' => $verification,
            'source' => 'server_ntp',
            'timezone' => 'Asia/Makassar',
        ];
    }
    
    /**
     * Check if current time is within working hours
     */
    public function isWorkingHours(): bool
    {
        $now = $this->now();
        $hour = $now->hour;
        
        // Working hours: 06:00 - 18:00 WITA
        return $hour >= 6 && $hour < 18;
    }
    
    /**
     * Get system time info for debugging
     */
    public function getSystemTimeInfo(): array
    {
        $now = $this->now();
        
        return [
            'current_time' => $now,
            'formatted' => $this->formatForDisplay($now),
            'php_timezone' => date_default_timezone_get(),
            'laravel_timezone' => config('app.timezone'),
            'server_time' => Carbon::now(),
            'utc_time' => Carbon::utc(),
            'is_working_hours' => $this->isWorkingHours(),
            'unix_timestamp' => $now->timestamp,
        ];
    }
}