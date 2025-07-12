<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class HealthController extends Controller
{
    /**
     * Health check endpoint for monitoring
     */
    public function check(): JsonResponse
    {
        $checks = [
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'version' => config('app.version', '1.0.0'),
            'environment' => app()->environment(),
            'checks' => []
        ];

        // Database check
        try {
            DB::connection()->getPdo();
            $checks['checks']['database'] = [
                'status' => 'healthy',
                'message' => 'Database connection successful'
            ];
        } catch (\Exception $e) {
            $checks['status'] = 'unhealthy';
            $checks['checks']['database'] = [
                'status' => 'unhealthy',
                'message' => 'Database connection failed',
                'error' => app()->environment('production') ? 'Connection failed' : $e->getMessage()
            ];
        }

        // Cache check
        try {
            Cache::put('health_check', true, 10);
            $value = Cache::get('health_check');
            $checks['checks']['cache'] = [
                'status' => $value ? 'healthy' : 'unhealthy',
                'message' => $value ? 'Cache working properly' : 'Cache not working'
            ];
        } catch (\Exception $e) {
            $checks['status'] = 'unhealthy';
            $checks['checks']['cache'] = [
                'status' => 'unhealthy',
                'message' => 'Cache connection failed',
                'error' => app()->environment('production') ? 'Connection failed' : $e->getMessage()
            ];
        }

        // Redis check (if using Redis)
        if (config('cache.default') === 'redis' || config('queue.default') === 'redis') {
            try {
                Redis::ping();
                $checks['checks']['redis'] = [
                    'status' => 'healthy',
                    'message' => 'Redis connection successful'
                ];
            } catch (\Exception $e) {
                $checks['status'] = 'unhealthy';
                $checks['checks']['redis'] = [
                    'status' => 'unhealthy',
                    'message' => 'Redis connection failed',
                    'error' => app()->environment('production') ? 'Connection failed' : $e->getMessage()
                ];
            }
        }

        // Storage check
        try {
            $testFile = storage_path('app/health_check.txt');
            file_put_contents($testFile, 'test');
            $content = file_get_contents($testFile);
            unlink($testFile);
            
            $checks['checks']['storage'] = [
                'status' => $content === 'test' ? 'healthy' : 'unhealthy',
                'message' => $content === 'test' ? 'Storage writable' : 'Storage not writable'
            ];
        } catch (\Exception $e) {
            $checks['status'] = 'unhealthy';
            $checks['checks']['storage'] = [
                'status' => 'unhealthy',
                'message' => 'Storage not accessible',
                'error' => app()->environment('production') ? 'Storage error' : $e->getMessage()
            ];
        }

        // Queue check
        try {
            $queueSize = DB::table('jobs')->count();
            $failedJobs = DB::table('failed_jobs')->count();
            
            $checks['checks']['queue'] = [
                'status' => 'healthy',
                'message' => 'Queue system operational',
                'pending_jobs' => $queueSize,
                'failed_jobs' => $failedJobs
            ];
        } catch (\Exception $e) {
            $checks['checks']['queue'] = [
                'status' => 'warning',
                'message' => 'Queue status unknown'
            ];
        }

        // Memory usage
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = $this->getMemoryLimit();
        $memoryPercentage = $memoryLimit > 0 ? round(($memoryUsage / $memoryLimit) * 100, 2) : 0;
        
        $checks['checks']['memory'] = [
            'status' => $memoryPercentage < 80 ? 'healthy' : 'warning',
            'message' => 'Memory usage',
            'usage' => $this->formatBytes($memoryUsage),
            'limit' => $this->formatBytes($memoryLimit),
            'percentage' => $memoryPercentage . '%'
        ];

        // Disk space
        $freeSpace = disk_free_space('/');
        $totalSpace = disk_total_space('/');
        $usedPercentage = round((($totalSpace - $freeSpace) / $totalSpace) * 100, 2);
        
        $checks['checks']['disk'] = [
            'status' => $usedPercentage < 80 ? 'healthy' : ($usedPercentage < 90 ? 'warning' : 'critical'),
            'message' => 'Disk usage',
            'free' => $this->formatBytes($freeSpace),
            'total' => $this->formatBytes($totalSpace),
            'percentage_used' => $usedPercentage . '%'
        ];

        $statusCode = $checks['status'] === 'healthy' ? 200 : 503;

        return response()->json($checks, $statusCode);
    }

    /**
     * Simple ping endpoint
     */
    public function ping(): JsonResponse
    {
        return response()->json([
            'status' => 'pong',
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Get memory limit in bytes
     */
    private function getMemoryLimit(): int
    {
        $limit = ini_get('memory_limit');
        
        if ($limit == -1) {
            return PHP_INT_MAX;
        }
        
        $unit = strtolower(substr($limit, -1));
        $value = (int) $limit;
        
        switch ($unit) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}