<?php

namespace Tests\Unit;

use App\Services\PerformanceMonitorService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PerformanceMonitorServiceTest extends TestCase
{
    use RefreshDatabase;

    private PerformanceMonitorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PerformanceMonitorService();
    }

    public function test_can_start_request_monitoring(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('User-Agent', 'Test Browser');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        $this->service->startRequest($request);

        // Test that DB query logging is enabled
        $this->assertTrue(DB::logging());
    }

    public function test_can_end_request_monitoring(): void
    {
        $request = Request::create('/test', 'GET');
        $this->service->startRequest($request);

        // Simulate some work
        usleep(10000); // 10ms

        $metrics = $this->service->endRequest();

        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('request', $metrics);
        $this->assertArrayHasKey('performance', $metrics);
        $this->assertArrayHasKey('system', $metrics);
        $this->assertArrayHasKey('cache', $metrics);

        // Check performance metrics
        $performance = $metrics['performance'];
        $this->assertArrayHasKey('execution_time', $performance);
        $this->assertArrayHasKey('memory_usage', $performance);
        $this->assertArrayHasKey('queries_count', $performance);
        $this->assertArrayHasKey('queries', $performance);

        // Execution time should be positive
        $this->assertGreaterThan(0, $performance['execution_time']);
    }

    public function test_can_monitor_operation(): void
    {
        $result = $this->service->monitorOperation('test_operation', function () {
            usleep(5000); // 5ms
            return 'test_result';
        });

        $this->assertEquals('test_result', $result);
    }

    public function test_monitors_operation_failure(): void
    {
        $result = $this->service->monitorOperation('failing_operation', function () {
            throw new \Exception('Test error');
        });

        $this->assertNull($result);
    }

    public function test_can_get_performance_summary(): void
    {
        // Create some test metrics
        $testMetrics = [
            [
                'timestamp' => now()->subHours(1)->toISOString(),
                'execution_time' => 150,
                'memory_usage_bytes' => 1024 * 1024, // 1MB
                'queries_count' => 5,
            ],
            [
                'timestamp' => now()->subMinutes(30)->toISOString(),
                'execution_time' => 200,
                'memory_usage_bytes' => 2 * 1024 * 1024, // 2MB
                'queries_count' => 8,
            ]
        ];

        Cache::put('performance_metrics', $testMetrics, 3600);

        $summary = $this->service->getPerformanceSummary(2);

        $this->assertIsArray($summary);
        $this->assertArrayHasKey('requests_count', $summary);
        $this->assertArrayHasKey('avg_response_time', $summary);
        $this->assertArrayHasKey('max_response_time', $summary);
        $this->assertArrayHasKey('avg_memory_usage', $summary);
        $this->assertArrayHasKey('avg_queries_per_request', $summary);

        $this->assertEquals(2, $summary['requests_count']);
        $this->assertEquals(175, $summary['avg_response_time']); // (150 + 200) / 2
        $this->assertEquals(200, $summary['max_response_time']);
        $this->assertEquals(6.5, $summary['avg_queries_per_request']); // (5 + 8) / 2
    }

    public function test_returns_default_summary_when_no_data(): void
    {
        Cache::forget('performance_metrics');

        $summary = $this->service->getPerformanceSummary(24);

        $this->assertEquals(0, $summary['requests_count']);
        $this->assertEquals(0, $summary['avg_response_time']);
        $this->assertEquals(0, $summary['max_response_time']);
        $this->assertEquals('0 B', $summary['avg_memory_usage']);
    }

    public function test_can_get_performance_alerts(): void
    {
        // Create metrics that should trigger alerts
        $highResponseTimeMetrics = [
            [
                'timestamp' => now()->subMinutes(10)->toISOString(),
                'execution_time' => 3000, // 3 seconds - should trigger alert
                'memory_usage_bytes' => 1024 * 1024,
                'queries_count' => 5,
            ]
        ];

        Cache::put('performance_metrics', $highResponseTimeMetrics, 3600);

        $alerts = $this->service->getPerformanceAlerts();

        $this->assertIsArray($alerts);
        
        // Should have at least one alert for high response time
        $this->assertGreaterThan(0, count($alerts));
        
        $highResponseAlert = collect($alerts)->firstWhere('title', 'High Response Time');
        $this->assertNotNull($highResponseAlert);
        $this->assertEquals('warning', $highResponseAlert['type']);
    }

    public function test_formats_bytes_correctly(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $formatBytesMethod = $reflection->getMethod('formatBytes');
        $formatBytesMethod->setAccessible(true);

        $testCases = [
            [0, '0 B'],
            [1024, '1 KB'],
            [1024 * 1024, '1 MB'],
            [1024 * 1024 * 1024, '1 GB'],
            [1536, '1.5 KB'], // 1.5 KB
        ];

        foreach ($testCases as [$input, $expected]) {
            $result = $formatBytesMethod->invoke($this->service, $input);
            $this->assertEquals($expected, $result, "Failed for input: {$input}");
        }
    }

    public function test_analyzes_queries_correctly(): void
    {
        // Enable query logging
        DB::enableQueryLog();

        // Execute some test queries
        DB::select('SELECT 1'); // Fast query
        DB::select('SELECT pg_sleep(0.15)'); // Slow query (150ms)
        DB::select('SELECT 1'); // Duplicate query

        $request = Request::create('/test', 'GET');
        $this->service->startRequest($request);
        $metrics = $this->service->endRequest();

        $queryAnalysis = $metrics['performance']['queries'];

        $this->assertArrayHasKey('total_time', $queryAnalysis);
        $this->assertArrayHasKey('slow_queries', $queryAnalysis);
        $this->assertArrayHasKey('duplicate_queries', $queryAnalysis);
        $this->assertArrayHasKey('n_plus_one_potential', $queryAnalysis);

        // Should detect at least one slow query
        $this->assertGreaterThan(0, count($queryAnalysis['slow_queries']));
        
        // Should detect duplicate queries
        $this->assertGreaterThan(0, count($queryAnalysis['duplicate_queries']));
    }

    public function test_caches_performance_summary(): void
    {
        // First call should cache the result
        $summary1 = $this->service->getPerformanceSummary(24);
        
        // Second call should return cached result
        $summary2 = $this->service->getPerformanceSummary(24);
        
        $this->assertEquals($summary1, $summary2);
        
        // Verify it's actually cached
        $cacheKey = 'performance_summary_24h';
        $this->assertTrue(Cache::has($cacheKey));
    }

    public function test_handles_memory_limit_parsing(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $parseMemoryMethod = $reflection->getMethod('parseMemoryLimit');
        $parseMemoryMethod->setAccessible(true);

        $testCases = [
            ['128M', 128 * 1024 * 1024],
            ['1G', 1024 * 1024 * 1024],
            ['512K', 512 * 1024],
            ['1073741824', 1073741824], // Raw bytes
        ];

        foreach ($testCases as [$input, $expected]) {
            $result = $parseMemoryMethod->invoke($this->service, $input);
            $this->assertEquals($expected, $result, "Failed for input: {$input}");
        }
    }

    public function test_calculates_error_rate(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $calculateErrorRateMethod = $reflection->getMethod('calculateErrorRate');
        $calculateErrorRateMethod->setAccessible(true);

        $metrics = [
            ['error' => null],
            ['error' => 'Some error'],
            ['error' => null],
            ['error' => 'Another error'],
        ];

        $errorRate = $calculateErrorRateMethod->invoke($this->service, $metrics);
        
        $this->assertEquals(50.0, $errorRate); // 2 errors out of 4 requests = 50%
    }

    public function test_system_metrics_collection(): void
    {
        $request = Request::create('/test', 'GET');
        $this->service->startRequest($request);
        $metrics = $this->service->endRequest();

        $systemMetrics = $metrics['system'];

        $this->assertArrayHasKey('php_version', $systemMetrics);
        $this->assertArrayHasKey('memory_limit', $systemMetrics);
        $this->assertArrayHasKey('max_execution_time', $systemMetrics);
        $this->assertArrayHasKey('load_average', $systemMetrics);
        $this->assertArrayHasKey('disk_free_space', $systemMetrics);

        $this->assertEquals(PHP_VERSION, $systemMetrics['php_version']);
        $this->assertIsArray($systemMetrics['load_average']);
    }

    public function test_performance_metrics_storage(): void
    {
        $request = Request::create('/test', 'GET');
        $this->service->startRequest($request);
        
        // Simulate some work
        usleep(5000);
        
        $this->service->endRequest();

        // Check that metrics were stored in cache
        $storedMetrics = Cache::get('performance_metrics', []);
        $this->assertNotEmpty($storedMetrics);
        
        $lastMetric = end($storedMetrics);
        $this->assertArrayHasKey('timestamp', $lastMetric);
        $this->assertArrayHasKey('execution_time', $lastMetric);
        $this->assertArrayHasKey('memory_usage_bytes', $lastMetric);
    }
}