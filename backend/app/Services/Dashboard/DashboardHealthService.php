<?php

namespace App\Services\Dashboard;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Leave;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardHealthService
{
    /**
     * Get system status information
     */
    public function getSystemStatus(): array
    {
        $services = [];

        // Check database
        $dbStatus = $this->checkDatabaseHealth();
        $services[] = [
            'id' => 'database',
            'name' => 'Database',
            'status' => $dbStatus['status'],
            'uptime' => $dbStatus['uptime'],
            'responseTime' => $dbStatus['responseTime'],
        ];

        // Check file storage
        $storageStatus = $this->checkStorageHealth();
        $services[] = [
            'id' => 'storage',
            'name' => 'File Storage',
            'status' => $storageStatus['status'],
            'uptime' => $storageStatus['uptime'],
            'responseTime' => $storageStatus['responseTime'],
        ];

        // Check face recognition (if enabled)
        $faceStatus = $this->checkFaceRecognitionHealth();
        $services[] = [
            'id' => 'face-recognition',
            'name' => 'Face Recognition',
            'status' => $faceStatus['status'],
            'uptime' => $faceStatus['uptime'],
            'responseTime' => $faceStatus['responseTime'],
        ];

        // Determine overall status
        $operationalCount = collect($services)->where('status', 'operational')->count();
        $overall = $operationalCount === count($services) ? 'healthy' :
            ($operationalCount > count($services) / 2 ? 'degraded' : 'critical');

        return [
            'overall' => $overall,
            'services' => $services,
            'lastUpdated' => now()->toISOString(),
        ];
    }

    public function checkDatabaseHealth(): array
    {
        $start = microtime(true);
        try {
            DB::connection()->getPdo();
            $responseTime = round((microtime(true) - $start) * 1000);

            return [
                'status' => 'operational',
                'uptime' => 99.9,
                'responseTime' => $responseTime,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'uptime' => 0,
                'responseTime' => 0,
            ];
        }
    }

    public function checkStorageHealth(): array
    {
        $start = microtime(true);
        try {
            // Check if storage directory is writable
            $storagePath = storage_path('app');
            if (is_writable($storagePath)) {
                $responseTime = round((microtime(true) - $start) * 1000);

                return [
                    'status' => 'operational',
                    'uptime' => 99.5,
                    'responseTime' => $responseTime,
                ];
            }
        } catch (\Exception $e) {
            // Fall through to error case
        }

        return [
            'status' => 'degraded',
            'uptime' => 85.0,
            'responseTime' => 1000,
        ];
    }

    public function checkFaceRecognitionHealth(): array
    {
        // Simple check - count recent face recognition attempts
        $recentAttempts = Attendance::where('created_at', '>=', now()->subHour())
            ->whereNotNull('metadata')
            ->count();

        return [
            'status' => $recentAttempts > 0 ? 'operational' : 'idle',
            'uptime' => 95.0,
            'responseTime' => 250,
        ];
    }

    /**
     * Calculate system health score based on actual system metrics
     */
    public function calculateSystemHealthScore(): int
    {
        $score = 100;

        // Check database health
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            $score -= 30;
        }

        // Check if storage is writable
        if (! is_writable(storage_path('app'))) {
            $score -= 20;
        }

        // Check for recent errors in logs
        $recentErrors = DB::table('audit_logs')
            ->where('level', 'error')
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        if ($recentErrors > 10) {
            $score -= 15;
        } elseif ($recentErrors > 5) {
            $score -= 10;
        }

        return max(0, $score);
    }

    /**
     * Calculate change in present employees from yesterday
     */
    public function calculatePresentTodayChange(): int
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        $presentToday = Attendance::whereDate('date', $today)
            ->whereNotNull('check_in_time')
            ->distinct('employee_id')
            ->count();

        $presentYesterday = Attendance::whereDate('date', $yesterday)
            ->whereNotNull('check_in_time')
            ->distinct('employee_id')
            ->count();

        return $presentToday - $presentYesterday;
    }

    /**
     * Calculate attendance rate change from last week
     */
    public function calculateAttendanceRateChange(): float
    {
        $thisWeek = Carbon::now()->startOfWeek();
        $lastWeek = Carbon::now()->subWeek()->startOfWeek();
        $lastWeekEnd = $lastWeek->copy()->endOfWeek();

        $totalEmployees = Employee::active()->count();

        if ($totalEmployees === 0) {
            return 0;
        }

        // This week's rate
        $thisWeekAttendance = Attendance::whereBetween('date', [$thisWeek, now()])
            ->whereNotNull('check_in_time')
            ->count();
        $thisWeekDays = $thisWeek->diffInDays(now()) + 1;
        $thisWeekRate = ($thisWeekAttendance / ($totalEmployees * $thisWeekDays)) * 100;

        // Last week's rate
        $lastWeekAttendance = Attendance::whereBetween('date', [$lastWeek, $lastWeekEnd])
            ->whereNotNull('check_in_time')
            ->count();
        $lastWeekRate = ($lastWeekAttendance / ($totalEmployees * 7)) * 100;

        return round($thisWeekRate - $lastWeekRate, 1);
    }

    /**
     * Calculate change in pending leaves from last week
     */
    public function calculatePendingLeavesChange(): int
    {
        $thisWeek = Leave::where('status', 'pending')
            ->where('created_at', '>=', Carbon::now()->startOfWeek())
            ->count();

        $lastWeek = Leave::where('status', 'pending')
            ->whereBetween('created_at', [
                Carbon::now()->subWeek()->startOfWeek(),
                Carbon::now()->subWeek()->endOfWeek(),
            ])
            ->count();

        return $thisWeek - $lastWeek;
    }
}
