<?php

namespace App\Http\Middleware;

use App\Services\PerformanceMonitorService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PerformanceMonitor
{
    private PerformanceMonitorService $performanceMonitor;

    public function __construct(PerformanceMonitorService $performanceMonitor)
    {
        $this->performanceMonitor = $performanceMonitor;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip monitoring for certain routes
        if ($this->shouldSkipMonitoring($request)) {
            return $next($request);
        }

        // Start monitoring
        $this->performanceMonitor->startRequest($request);

        $response = $next($request);

        // End monitoring and collect metrics
        $metrics = $this->performanceMonitor->endRequest();

        // Add performance headers for debugging (only in development)
        if (app()->environment('local', 'development')) {
            $response->headers->set('X-Response-Time', $metrics['performance']['execution_time'] . 'ms');
            $response->headers->set('X-Memory-Usage', $metrics['performance']['memory_usage']);
            $response->headers->set('X-Query-Count', $metrics['performance']['queries_count']);
        }

        return $response;
    }

    /**
     * Determine if monitoring should be skipped for this request
     */
    private function shouldSkipMonitoring(Request $request): bool
    {
        $skipRoutes = [
            'performance/monitor',
            'api/performance',
            'telescope*',
            'horizon*',
            '_ignition*',
            '_debugbar*',
        ];

        $path = $request->path();

        foreach ($skipRoutes as $skipRoute) {
            if (str_contains($skipRoute, '*')) {
                $pattern = str_replace('*', '.*', $skipRoute);
                if (preg_match('/^' . $pattern . '/', $path)) {
                    return true;
                }
            } elseif ($path === $skipRoute) {
                return true;
            }
        }

        // Skip static asset requests
        if (preg_match('/\.(css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|eot)$/', $path)) {
            return true;
        }

        return false;
    }
}