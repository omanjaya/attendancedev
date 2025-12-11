<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class PerformanceMonitorService
{
    private array $metrics = [];

    private float $startTime;

    private int $startMemory;

    private array $queryLog = [];

    public function __construct()
    {
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage(true);
    }

    /**
     * Start monitoring a request
     */
    public function startRequest(Request $request): void
    {
        $this->metrics['request'] = [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'start_time' => $this->startTime,
            'start_memory' => $this->startMemory,
        ];

        // Enable query logging
        DB::enableQueryLog();
    }

    /**
     * End monitoring and collect metrics
     */
    public function endRequest(): array
    {
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);

        $this->metrics['performance'] = [
            'execution_time' => round(($endTime - $this->startTime) * 1000, 2), // milliseconds
            'memory_usage' => $this->formatBytes($endMemory - $this->startMemory),
            'peak_memory' => $this->formatBytes($peakMemory),
            'queries_count' => count(DB::getQueryLog()),
            'queries' => $this->analyzeQueries(),
        ];

        $this->metrics['system'] = $this->getSystemMetrics();
        $this->metrics['cache'] = $this->getCacheMetrics();

        // Log performance data
        $this->logPerformanceData();

        // Store metrics for dashboard
        $this->storeMetrics();

        return $this->metrics;
    }

    /**
     * Analyze database queries for performance issues
     */
    private function analyzeQueries(): array
    {
        $queries = DB::getQueryLog();
        $analysis = [
            'total_time' => 0,
            'slow_queries' => [],
            'duplicate_queries' => [],
            'n_plus_one_potential' => [],
        ];

        $queryHashes = [];

        foreach ($queries as $query) {
            $time = $query['time'];
            $analysis['total_time'] += $time;

            // Identify slow queries (>100ms)
            if ($time > 100) {
                $analysis['slow_queries'][] = [
                    'sql' => $query['query'],
                    'time' => $time,
                    'bindings' => $query['bindings'],
                ];
            }

            // Identify duplicate queries
            $hash = md5($query['query']);
            if (isset($queryHashes[$hash])) {
                $queryHashes[$hash]['count']++;
                if ($queryHashes[$hash]['count'] === 2) {
                    $analysis['duplicate_queries'][] = [
                        'sql' => $query['query'],
                        'count' => $queryHashes[$hash]['count'],
                    ];
                }
            } else {
                $queryHashes[$hash] = ['count' => 1, 'query' => $query['query']];
            }

            // Detect potential N+1 problems
            if (
                str_contains(strtolower($query['query']), 'select') &&
                str_contains(strtolower($query['query']), 'where') &&
                ! str_contains(strtolower($query['query']), 'in (')
            ) {
                $analysis['n_plus_one_potential'][] = [
                    'sql' => $query['query'],
                    'time' => $time,
                ];
            }
        }

        return $analysis;
    }

    /**
     * Get system performance metrics
     */
    private function getSystemMetrics(): array
    {
        $loadAverage = function_exists('sys_getloadavg') ? sys_getloadavg() : [0, 0, 0];

        return [
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'load_average' => $loadAverage,
            'disk_free_space' => $this->formatBytes(disk_free_space('.')),
            'disk_total_space' => $this->formatBytes(disk_total_space('.')),
            'cpu_count' => function_exists('shell_exec') ? (int) shell_exec('nproc') : 'N/A',
        ];
    }

    /**
     * Get cache performance metrics
     */
    private function getCacheMetrics(): array
    {
        $metrics = [
            'cache_hits' => 0,
            'cache_misses' => 0,
            'cache_writes' => 0,
        ];

        try {
            if (config('cache.default') === 'redis') {
                $redis = Redis::connection();
                $info = $redis->info('stats');

                $metrics['cache_hits'] = $info['keyspace_hits'] ?? 0;
                $metrics['cache_misses'] = $info['keyspace_misses'] ?? 0;
                $metrics['connected_clients'] = $info['connected_clients'] ?? 0;
                $metrics['used_memory'] = $this->formatBytes($info['used_memory'] ?? 0);
                $metrics['hit_rate'] =
                  $metrics['cache_hits'] > 0
                    ? round(
                        ($metrics['cache_hits'] / ($metrics['cache_hits'] + $metrics['cache_misses'])) * 100,
                        2,
                    )
                    : 0;
            }
        } catch (\Exception $e) {
            Log::warning('Failed to get cache metrics: '.$e->getMessage());
        }

        return $metrics;
    }

    /**
     * Monitor specific operation performance
     */
    public function monitorOperation(string $operation, callable $callback)
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        try {
            $result = $callback();
            $success = true;
            $error = null;
        } catch (\Exception $e) {
            $result = null;
            $success = false;
            $error = $e->getMessage();
        }

        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);

        $metrics = [
            'operation' => $operation,
            'execution_time' => round(($endTime - $startTime) * 1000, 2),
            'memory_used' => $this->formatBytes($endMemory - $startMemory),
            'success' => $success,
            'error' => $error,
            'timestamp' => now()->toISOString(),
        ];

        // Store operation metrics
        $this->storeOperationMetrics($operation, $metrics);

        if (! $success) {
            Log::error("Operation '{$operation}' failed", $metrics);
        }

        return $result;
    }

    /**
     * Get performance summary for dashboard
     */
    public function getPerformanceSummary(int $hours = 24): array
    {
        $key = "performance_summary_{$hours}h";

        return Cache::remember($key, 300, function () use ($hours) {
            $since = now()->subHours($hours);

            // Get cached metrics
            $metrics = Cache::get('performance_metrics', []);
            $recentMetrics = array_filter($metrics, function ($metric) use ($since) {
                return \Carbon\Carbon::parse($metric['timestamp'])->gte($since);
            });

            if (empty($recentMetrics)) {
                return $this->getDefaultSummary();
            }

            $executionTimes = array_column($recentMetrics, 'execution_time');
            $memoryUsages = array_column($recentMetrics, 'memory_usage_bytes');
            $queryCounts = array_column($recentMetrics, 'queries_count');

            return [
                'requests_count' => count($recentMetrics),
                'avg_response_time' => round(array_sum($executionTimes) / count($executionTimes), 2),
                'max_response_time' => max($executionTimes),
                'min_response_time' => min($executionTimes),
                'avg_memory_usage' => $this->formatBytes(array_sum($memoryUsages) / count($memoryUsages)),
                'avg_queries_per_request' => round(array_sum($queryCounts) / count($queryCounts), 1),
                'slow_requests' => count(array_filter($executionTimes, fn ($time) => $time > 1000)),
                'error_rate' => $this->calculateErrorRate($recentMetrics),
                'period_hours' => $hours,
                'last_updated' => now()->toISOString(),
            ];
        });
    }

    /**
     * Get real-time performance alerts
     */
    public function getPerformanceAlerts(): array
    {
        $alerts = [];
        $summary = $this->getPerformanceSummary(1); // Last hour

        // High response time alert
        if ($summary['avg_response_time'] > 2000) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'High Response Time',
                'message' => "Average response time is {$summary['avg_response_time']}ms",
                'severity' => 'medium',
                'timestamp' => now(),
            ];
        }

        // High error rate alert
        if ($summary['error_rate'] > 5) {
            $alerts[] = [
                'type' => 'error',
                'title' => 'High Error Rate',
                'message' => "Error rate is {$summary['error_rate']}%",
                'severity' => 'high',
                'timestamp' => now(),
            ];
        }

        // Memory usage alert
        $currentMemory = memory_get_usage(true);
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));
        $memoryUsagePercent = ($currentMemory / $memoryLimit) * 100;

        if ($memoryUsagePercent > 80) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'High Memory Usage',
                'message' => "Memory usage is {$memoryUsagePercent}% of limit",
                'severity' => 'medium',
                'timestamp' => now(),
            ];
        }

        return $alerts;
    }

    /**
     * Store performance metrics
     */
    private function storeMetrics(): void
    {
        $timestamp = now()->toISOString();
        $metrics = array_merge($this->metrics, [
            'timestamp' => $timestamp,
            'memory_usage_bytes' => memory_get_usage(true),
            'execution_time' => $this->metrics['performance']['execution_time'] ?? 0,
            'queries_count' => $this->metrics['performance']['queries_count'] ?? 0,
        ]);

        // Store in cache (keep last 1000 entries)
        $allMetrics = Cache::get('performance_metrics', []);
        $allMetrics[] = $metrics;

        // Keep only last 1000 entries
        if (count($allMetrics) > 1000) {
            $allMetrics = array_slice($allMetrics, -1000);
        }

        Cache::put('performance_metrics', $allMetrics, 86400); // 24 hours
    }

    /**
     * Store operation-specific metrics
     */
    private function storeOperationMetrics(string $operation, array $metrics): void
    {
        $key = "operation_metrics_{$operation}";
        $operationMetrics = Cache::get($key, []);
        $operationMetrics[] = $metrics;

        // Keep only last 100 entries per operation
        if (count($operationMetrics) > 100) {
            $operationMetrics = array_slice($operationMetrics, -100);
        }

        Cache::put($key, $operationMetrics, 3600); // 1 hour
    }

    /**
     * Log performance data
     */
    private function logPerformanceData(): void
    {
        $performance = $this->metrics['performance'] ?? [];

        if (($performance['execution_time'] ?? 0) > 2000) {
            Log::warning('Slow request detected', [
                'url' => $this->metrics['request']['url'] ?? 'unknown',
                'execution_time' => $performance['execution_time'],
                'queries_count' => $performance['queries_count'],
                'memory_usage' => $performance['memory_usage'],
            ]);
        }

        if (($performance['queries_count'] ?? 0) > 50) {
            Log::warning('High query count detected', [
                'url' => $this->metrics['request']['url'] ?? 'unknown',
                'queries_count' => $performance['queries_count'],
                'slow_queries' => count($performance['queries']['slow_queries'] ?? []),
            ]);
        }
    }

    /**
     * Helper methods
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2).' '.$units[$pow];
    }

    private function parseMemoryLimit(string $memoryLimit): int
    {
        $memoryLimit = strtolower($memoryLimit);
        $multipliers = ['k' => 1024, 'm' => 1024 * 1024, 'g' => 1024 * 1024 * 1024];

        foreach ($multipliers as $suffix => $multiplier) {
            if (str_ends_with($memoryLimit, $suffix)) {
                return (int) substr($memoryLimit, 0, -1) * $multiplier;
            }
        }

        return (int) $memoryLimit;
    }

    private function calculateErrorRate(array $metrics): float
    {
        if (empty($metrics)) {
            return 0;
        }

        $errorCount = 0;
        foreach ($metrics as $metric) {
            if (isset($metric['error']) && $metric['error']) {
                $errorCount++;
            }
        }

        return round(($errorCount / count($metrics)) * 100, 2);
    }

    private function getDefaultSummary(): array
    {
        return [
            'requests_count' => 0,
            'avg_response_time' => 0,
            'max_response_time' => 0,
            'min_response_time' => 0,
            'avg_memory_usage' => '0 B',
            'avg_queries_per_request' => 0,
            'slow_requests' => 0,
            'error_rate' => 0,
            'period_hours' => 24,
            'last_updated' => now()->toISOString(),
        ];
    }
}
