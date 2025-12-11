<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PerformanceMonitorService;
use App\Services\Performance\PerformanceDatabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class PerformanceController extends Controller
{
    public function __construct(
        private PerformanceMonitorService $performanceMonitor,
        private PerformanceDatabaseService $databaseService
    ) {
        $this->middleware('permission:manage_system_settings');
    }

    /**
     * Show performance monitoring dashboard
     */
    public function index()
    {
        $summary = $this->performanceMonitor->getPerformanceSummary(24);
        $alerts = $this->performanceMonitor->getPerformanceAlerts();
        $systemInfo = $this->databaseService->getSystemInfo();

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
        $chartData = $this->databaseService->prepareChartData($recentMetrics);

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
            $stats = $this->databaseService->getDatabaseStats();
            return response()->json($stats);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
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
            $result = $this->databaseService->optimizeDatabase();
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
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

}
