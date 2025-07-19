<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Health Check Controller
 *
 * Provides comprehensive system health monitoring endpoints
 */
class HealthCheckController extends Controller
{
    /**
     * Basic health check
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'uptime' => $this->getUptime(),
            'version' => config('app.version', '1.0.0'),
            'environment' => config('app.env'),
        ]);
    }

    /**
     * Detailed health check
     */
    public function detailed(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
            'queue' => $this->checkQueue(),
            'memory' => $this->checkMemory(),
            'disk' => $this->checkDisk(),
        ];

        $overallStatus = $this->determineOverallStatus($checks);

        return response()->json([
            'status' => $overallStatus,
            'timestamp' => now()->toISOString(),
            'checks' => $checks,
            'system_info' => $this->getSystemInfo(),
        ], $overallStatus === 'healthy' ? 200 : 503);
    }

    /**
     * Application-specific health check
     */
    public function application(): JsonResponse
    {
        $checks = [
            'users' => $this->checkUsers(),
            'employees' => $this->checkEmployees(),
            'attendance' => $this->checkAttendance(),
            'authentication' => $this->checkAuthentication(),
            'permissions' => $this->checkPermissions(),
        ];

        $overallStatus = $this->determineOverallStatus($checks);

        return response()->json([
            'status' => $overallStatus,
            'timestamp' => now()->toISOString(),
            'checks' => $checks,
            'statistics' => $this->getApplicationStatistics(),
        ], $overallStatus === 'healthy' ? 200 : 503);
    }

    /**
     * Database connectivity check
     */
    protected function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            $responseTime = round((microtime(true) - $start) * 1000, 2);

            // Test a simple query
            $userCount = DB::table('users')->count();

            return [
                'status' => 'healthy',
                'response_time_ms' => $responseTime,
                'connection' => DB::connection()->getName(),
                'test_query_result' => $userCount,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Cache system check
     */
    protected function checkCache(): array
    {
        try {
            $start = microtime(true);
            $testKey = 'health_check_'.time();
            $testValue = 'test_'.random_int(1000, 9999);

            Cache::put($testKey, $testValue, 60);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);

            $responseTime = round((microtime(true) - $start) * 1000, 2);

            if ($retrieved === $testValue) {
                return [
                    'status' => 'healthy',
                    'response_time_ms' => $responseTime,
                    'driver' => config('cache.default'),
                ];
            } else {
                return [
                    'status' => 'unhealthy',
                    'error' => 'Cache read/write test failed',
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Storage system check
     */
    protected function checkStorage(): array
    {
        try {
            $start = microtime(true);
            $testFile = 'health_check_'.time().'.txt';
            $testContent = 'health check test content';

            Storage::put($testFile, $testContent);
            $retrieved = Storage::get($testFile);
            Storage::delete($testFile);

            $responseTime = round((microtime(true) - $start) * 1000, 2);

            if ($retrieved === $testContent) {
                return [
                    'status' => 'healthy',
                    'response_time_ms' => $responseTime,
                    'driver' => config('filesystems.default'),
                ];
            } else {
                return [
                    'status' => 'unhealthy',
                    'error' => 'Storage read/write test failed',
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Queue system check
     */
    protected function checkQueue(): array
    {
        try {
            $connection = config('queue.default');
            $driver = config("queue.connections.{$connection}.driver");

            return [
                'status' => 'healthy',
                'connection' => $connection,
                'driver' => $driver,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Memory usage check
     */
    protected function checkMemory(): array
    {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = ini_get('memory_limit');
        $memoryLimitBytes = $this->convertToBytes($memoryLimit);
        $memoryUsagePercent = round(($memoryUsage / $memoryLimitBytes) * 100, 2);

        $status = $memoryUsagePercent > 90 ? 'unhealthy' : 'healthy';

        return [
            'status' => $status,
            'usage_bytes' => $memoryUsage,
            'usage_mb' => round($memoryUsage / 1024 / 1024, 2),
            'limit' => $memoryLimit,
            'usage_percent' => $memoryUsagePercent,
        ];
    }

    /**
     * Disk space check
     */
    protected function checkDisk(): array
    {
        $path = storage_path();
        $freeBytes = disk_free_space($path);
        $totalBytes = disk_total_space($path);
        $usedBytes = $totalBytes - $freeBytes;
        $usedPercent = round(($usedBytes / $totalBytes) * 100, 2);

        $status = $usedPercent > 90 ? 'unhealthy' : 'healthy';

        return [
            'status' => $status,
            'path' => $path,
            'free_bytes' => $freeBytes,
            'free_mb' => round($freeBytes / 1024 / 1024, 2),
            'total_bytes' => $totalBytes,
            'total_mb' => round($totalBytes / 1024 / 1024, 2),
            'used_percent' => $usedPercent,
        ];
    }

    /**
     * Users system check
     */
    protected function checkUsers(): array
    {
        try {
            $start = microtime(true);
            $userCount = User::count();
            $activeUsers = User::where('is_active', true)->count();
            $recentUsers = User::where('created_at', '>=', now()->subDays(7))->count();
            $responseTime = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'healthy',
                'response_time_ms' => $responseTime,
                'total_users' => $userCount,
                'active_users' => $activeUsers,
                'recent_users' => $recentUsers,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Employees system check
     */
    protected function checkEmployees(): array
    {
        try {
            $start = microtime(true);
            $employeeCount = Employee::count();
            $activeEmployees = Employee::where('is_active', true)->count();
            $recentEmployees = Employee::where('created_at', '>=', now()->subDays(7))->count();
            $responseTime = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'healthy',
                'response_time_ms' => $responseTime,
                'total_employees' => $employeeCount,
                'active_employees' => $activeEmployees,
                'recent_employees' => $recentEmployees,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Attendance system check
     */
    protected function checkAttendance(): array
    {
        try {
            $start = microtime(true);
            $todayAttendance = Attendance::whereDate('date', today())->count();
            $weeklyAttendance = Attendance::whereBetween('date', [
                now()->startOfWeek(),
                now()->endOfWeek(),
            ])->count();
            $incompleteAttendance = Attendance::where('status', 'incomplete')
                ->whereDate('date', today())
                ->count();
            $responseTime = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'healthy',
                'response_time_ms' => $responseTime,
                'today_attendance' => $todayAttendance,
                'weekly_attendance' => $weeklyAttendance,
                'incomplete_attendance' => $incompleteAttendance,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Authentication system check
     */
    protected function checkAuthentication(): array
    {
        try {
            $start = microtime(true);
            $loggedInUsers = User::whereNotNull('last_login_at')
                ->where('last_login_at', '>=', now()->subDays(1))
                ->count();
            $responseTime = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'healthy',
                'response_time_ms' => $responseTime,
                'recent_logins' => $loggedInUsers,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Permissions system check
     */
    protected function checkPermissions(): array
    {
        try {
            $start = microtime(true);
            $rolesCount = DB::table('roles')->count();
            $permissionsCount = DB::table('permissions')->count();
            $responseTime = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'healthy',
                'response_time_ms' => $responseTime,
                'roles_count' => $rolesCount,
                'permissions_count' => $permissionsCount,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get system information
     */
    protected function getSystemInfo(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
            'operating_system' => PHP_OS,
            'timezone' => config('app.timezone'),
            'locale' => config('app.locale'),
        ];
    }

    /**
     * Get application statistics
     */
    protected function getApplicationStatistics(): array
    {
        return [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'total_employees' => Employee::count(),
            'active_employees' => Employee::where('is_active', true)->count(),
            'today_attendance' => Attendance::whereDate('date', today())->count(),
            'this_week_attendance' => Attendance::whereBetween('date', [
                now()->startOfWeek(),
                now()->endOfWeek(),
            ])->count(),
        ];
    }

    /**
     * Determine overall status from checks
     */
    protected function determineOverallStatus(array $checks): string
    {
        foreach ($checks as $check) {
            if ($check['status'] === 'unhealthy') {
                return 'unhealthy';
            }
        }

        return 'healthy';
    }

    /**
     * Get system uptime
     */
    protected function getUptime(): string
    {
        if (function_exists('sys_getloadavg')) {
            $uptime = file_get_contents('/proc/uptime');
            $uptimeSeconds = floatval(explode(' ', $uptime)[0]);

            return gmdate('H:i:s', $uptimeSeconds);
        }

        return 'unknown';
    }

    /**
     * Convert memory limit string to bytes
     */
    protected function convertToBytes(string $value): int
    {
        $unit = strtolower($value[strlen($value) - 1]);
        $value = (int) $value;

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
}
