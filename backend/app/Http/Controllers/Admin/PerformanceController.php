<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PerformanceMonitorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PerformanceController extends Controller
{
    private PerformanceMonitorService $performanceMonitor;

    public function __construct(PerformanceMonitorService $performanceMonitor)
    {
        $this->performanceMonitor = $performanceMonitor;
        $this->middleware('permission:manage_system_settings');
    }

    /**
     * Show performance monitoring dashboard
     */
    public function index()
    {
        $summary = $this->performanceMonitor->getPerformanceSummary(24);
        $alerts = $this->performanceMonitor->getPerformanceAlerts();
        $systemInfo = $this->getSystemInfo();

        return view('pages.admin.performance.index', compact('summary', 'alerts', 'systemInfo'));
    }

    /**
     * Get real-time performance data for charts
     */
    public function getData(Request $request)
    {
        $hours = $request->get('hours', 24);
        $summary = $this->performanceMonitor->getPerformanceSummary($hours);
        $alerts = $this->performanceMonitor->getPerformanceAlerts();

        // Get detailed metrics for charts
        $metrics = Cache::get('performance_metrics', []);
        $since = now()->subHours($hours);

        $recentMetrics = array_filter($metrics, function ($metric) use ($since) {
            return \Carbon\Carbon::parse($metric['timestamp'])->gte($since);
        });

        // Prepare chart data
        $chartData = $this->prepareChartData($recentMetrics);

        return response()->json([
            'summary' => $summary,
            'alerts' => $alerts,
            'charts' => $chartData,
        ]);
    }

    /**
     * Get database performance statistics
     */
    public function getDatabaseStats()
    {
        try {
            // PostgreSQL specific queries
            $stats = [
                'connections' => DB::select('SELECT count(*) as count FROM pg_stat_activity')[0]->count ?? 0,
                'database_size' => DB::select('SELECT pg_size_pretty(pg_database_size(current_database())) as size')[0]
                    ->size ?? 'N/A',
                'slow_queries' => $this->getSlowQueries(),
                'table_sizes' => $this->getTableSizes(),
                'index_usage' => $this->getIndexUsage(),
            ];

            return response()->json($stats);
        } catch (\Exception $e) {
            return response()->json(
                ['error' => 'Failed to fetch database stats: '.$e->getMessage()],
                500,
            );
        }
    }

    /**
     * Clear performance cache
     */
    public function clearCache()
    {
        try {
            Cache::forget('performance_metrics');
            Cache::forget('performance_summary_24h');
            Cache::forget('performance_summary_1h');

            // Clear operation metrics
            $keys = Cache::get('operation_metrics_keys', []);
            foreach ($keys as $key) {
                Cache::forget($key);
            }

            return response()->json(['message' => 'Performance cache cleared successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to clear cache: '.$e->getMessage()], 500);
        }
    }

    /**
     * Optimize database
     */
    public function optimizeDatabase()
    {
        try {
            // Run database optimization commands
            $results = [];

            // Analyze tables
            $results[] = 'Running ANALYZE...';
            DB::statement('ANALYZE');

            // Vacuum (if PostgreSQL)
            if (DB::getDriverName() === 'pgsql') {
                $results[] = 'Running VACUUM...';
                DB::statement('VACUUM');
            }

            $results[] = 'Database optimization completed';

            return response()->json([
                'message' => 'Database optimized successfully',
                'details' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to optimize database: '.$e->getMessage()], 500);
        }
    }

    /**
     * Clear application cache
     */
    public function clearApplicationCache()
    {
        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');

            return response()->json(['message' => 'Application cache cleared successfully']);
        } catch (\Exception $e) {
            return response()->json(
                ['error' => 'Failed to clear application cache: '.$e->getMessage()],
                500,
            );
        }
    }

    /**
     * Get system information
     */
    private function getSystemInfo(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'opcache_enabled' => function_exists('opcache_get_status') && opcache_get_status() !== false,
            'redis_connected' => $this->isRedisConnected(),
            'database_driver' => DB::getDriverName(),
            'queue_driver' => config('queue.default'),
            'cache_driver' => config('cache.default'),
        ];
    }

    /**
     * Check if Redis is connected
     */
    private function isRedisConnected(): bool
    {
        try {
            if (config('cache.default') === 'redis') {
                Cache::store('redis')->put('test', 'value', 1);

                return true;
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Prepare chart data from metrics
     */
    private function prepareChartData(array $metrics): array
    {
        if (empty($metrics)) {
            return [
                'response_times' => [],
                'memory_usage' => [],
                'query_counts' => [],
                'timestamps' => [],
            ];
        }

        $responseTime = [];
        $memoryUsage = [];
        $queryCounts = [];
        $timestamps = [];

        foreach ($metrics as $metric) {
            $timestamps[] = \Carbon\Carbon::parse($metric['timestamp'])->format('H:i');
            $responseTime[] = $metric['performance']['execution_time'] ?? 0;
            $memoryUsage[] = $metric['memory_usage_bytes'] ?? 0;
            $queryCounts[] = $metric['performance']['queries_count'] ?? 0;
        }

        return [
            'response_times' => $responseTime,
            'memory_usage' => $memoryUsage,
            'query_counts' => $queryCounts,
            'timestamps' => $timestamps,
        ];
    }

    /**
     * Get slow queries from PostgreSQL
     */
    private function getSlowQueries(): array
    {
        try {
            if (DB::getDriverName() !== 'pgsql') {
                return [];
            }

            return DB::select('
                SELECT query, mean_time, calls, total_time
                FROM pg_stat_statements 
                WHERE mean_time > 100 
                ORDER BY mean_time DESC 
                LIMIT 10
            ');
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get table sizes
     */
    private function getTableSizes(): array
    {
        try {
            if (DB::getDriverName() === 'pgsql') {
                return DB::select("
                    SELECT 
                        schemaname,
                        tablename,
                        pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) AS size,
                        pg_total_relation_size(schemaname||'.'||tablename) AS size_bytes
                    FROM pg_tables 
                    WHERE schemaname = 'public'
                    ORDER BY pg_total_relation_size(schemaname||'.'||tablename) DESC
                    LIMIT 10
                ");
            }

            return [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get index usage statistics
     */
    private function getIndexUsage(): array
    {
        try {
            if (DB::getDriverName() === 'pgsql') {
                return DB::select("
                    SELECT 
                        schemaname,
                        tablename,
                        attname,
                        n_distinct,
                        correlation
                    FROM pg_stats 
                    WHERE schemaname = 'public'
                    ORDER BY n_distinct DESC
                    LIMIT 10
                ");
            }

            return [];
        } catch (\Exception $e) {
            return [];
        }
    }
}
