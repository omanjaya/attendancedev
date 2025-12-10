<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\System\SystemMonitoringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class SystemController extends Controller
{
    public function __construct(
        private SystemMonitoringService $monitoringService
    ) {
    }

    /**
     * Docker compose project name
     */
    private const PROJECT_NAME = 'attendancedev';

    /**
     * Service names mapping
     */
    private const SERVICES = [
        'backend' => [
            'name' => 'Backend API',
            'container' => 'attendancedev-backend',
            'icon' => 'server',
            'description' => 'Laravel PHP Application',
            'health_check' => '/api/health',
        ],
        'frontend' => [
            'name' => 'Frontend',
            'container' => 'attendancedev-frontend',
            'icon' => 'layout',
            'description' => 'React Vite Application',
            'health_check' => '/',
        ],
        'deepface' => [
            'name' => 'Face Recognition',
            'container' => 'attendancedev-deepface',
            'icon' => 'scan-face',
            'description' => 'DeepFace Service',
            'health_check' => '/health',
        ],
        'postgres' => [
            'name' => 'PostgreSQL',
            'container' => 'attendancedev-postgres',
            'icon' => 'database',
            'description' => 'PostgreSQL 16 Database',
            'health_check' => null,
        ],
        'redis' => [
            'name' => 'Redis',
            'container' => 'attendancedev-redis',
            'icon' => 'layers',
            'description' => 'Redis Cache Server',
            'health_check' => null,
        ],
        'nginx' => [
            'name' => 'Nginx',
            'container' => 'attendancedev-nginx',
            'icon' => 'globe',
            'description' => 'Web Server',
            'health_check' => '/',
        ],
    ];

    /**
     * Get all services status
     */
    public function index()
    {
        try {
            $services = [];

            foreach (self::SERVICES as $key => $config) {
                $services[$key] = $this->monitoringService->getServiceStatus($key, $config);
            }

            // Get overall system info
            $systemInfo = $this->monitoringService->getSystemInfo(count(self::SERVICES));

            return response()->json([
                'success' => true,
                'services' => $services,
                'system' => $systemInfo,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get services status', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get services status: ' . $e->getMessage(),
            ], 200);
        }
    }

    /**
     * Get single service status
     */
    public function show($service)
    {
        if (!isset(self::SERVICES[$service])) {
            return response()->json([
                'success' => false,
                'message' => 'Service not found',
            ], 404);
        }

        try {
            $config = self::SERVICES[$service];
            $status = $this->monitoringService->getServiceStatus($service, $config);

            return response()->json([
                'success' => true,
                'service' => $status,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get service status: ' . $e->getMessage(),
            ], 200);
        }
    }

    /**
     * Restart a service
     */
    public function restart($service)
    {
        if (!isset(self::SERVICES[$service])) {
            return response()->json([
                'success' => false,
                'message' => 'Service not found',
            ], 404);
        }

        try {
            $container = self::SERVICES[$service]['container'];

            // Audit log
            Log::info('Service restart requested', [
                'service' => $service,
                'container' => $container,
                'user_id' => auth()->id(),
                'user_name' => auth()->user()->name,
            ]);

            $command = "docker restart {$container}";
            $result = $this->monitoringService->executeCommand($command);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => self::SERVICES[$service]['name'] . ' restarted successfully',
                    'output' => $result['output'],
                ]);
            } else {
                // Detect permission error
                $errorMessage = 'Failed to restart service';
                if (str_contains($result['error'], 'permission denied')) {
                    $errorMessage = 'Docker permission denied. See DOCKER_PERMISSION_FIX.md for solution.';
                } elseif (str_contains($result['error'], 'Cannot connect')) {
                    $errorMessage = 'Docker daemon not accessible';
                } elseif ($result['error']) {
                    $errorMessage = 'Failed to restart service: ' . $result['error'];
                }

                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                ], 200);
            }
        } catch (\Exception $e) {
            Log::error('Service restart failed', [
                'service' => $service,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to restart service: ' . $e->getMessage(),
            ], 200);
        }
    }

    /**
     * Start a service
     */
    public function start($service)
    {
        if (!isset(self::SERVICES[$service])) {
            return response()->json([
                'success' => false,
                'message' => 'Service not found',
            ], 404);
        }

        try {
            $container = self::SERVICES[$service]['container'];

            Log::info('Service start requested', [
                'service' => $service,
                'user_id' => auth()->id(),
            ]);

            $command = "docker start {$container}";
            $result = $this->monitoringService->executeCommand($command);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => self::SERVICES[$service]['name'] . ' started successfully',
                ]);
            } else {
                // Detect permission error
                $errorMessage = 'Failed to start service';
                if (str_contains($result['error'], 'permission denied')) {
                    $errorMessage = 'Docker permission denied. See DOCKER_PERMISSION_FIX.md for solution.';
                } elseif (str_contains($result['error'], 'Cannot connect')) {
                    $errorMessage = 'Docker daemon not accessible';
                } elseif ($result['error']) {
                    $errorMessage = 'Failed to start service: ' . $result['error'];
                }

                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start service: ' . $e->getMessage(),
            ], 200);
        }
    }

    /**
     * Stop a service
     */
    public function stop($service)
    {
        if (!isset(self::SERVICES[$service])) {
            return response()->json([
                'success' => false,
                'message' => 'Service not found',
            ], 404);
        }

        try {
            $container = self::SERVICES[$service]['container'];

            Log::warning('Service stop requested', [
                'service' => $service,
                'user_id' => auth()->id(),
            ]);

            $command = "docker stop {$container}";
            $result = $this->monitoringService->executeCommand($command);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => self::SERVICES[$service]['name'] . ' stopped successfully',
                ]);
            } else {
                // Detect permission error
                $errorMessage = 'Failed to stop service';
                if (str_contains($result['error'], 'permission denied')) {
                    $errorMessage = 'Docker permission denied. See DOCKER_PERMISSION_FIX.md for solution.';
                } elseif (str_contains($result['error'], 'Cannot connect')) {
                    $errorMessage = 'Docker daemon not accessible';
                } elseif ($result['error']) {
                    $errorMessage = 'Failed to stop service: ' . $result['error'];
                }

                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to stop service: ' . $e->getMessage(),
            ], 200);
        }
    }

    /**
     * Get service logs
     */
    public function logs($service, Request $request)
    {
        if (!isset(self::SERVICES[$service])) {
            return response()->json([
                'success' => false,
                'message' => 'Service not found',
            ], 404);
        }

        try {
            $container = self::SERVICES[$service]['container'];
            $lines = $request->input('lines', 100);

            $command = "docker logs --tail {$lines} {$container} 2>&1";
            $result = $this->monitoringService->executeCommand($command, 30);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'logs' => $result['output'],
                    'lines' => $lines,
                ]);
            } else {
                // Detect permission error
                $errorMessage = 'Failed to get logs';
                if (str_contains($result['error'], 'permission denied')) {
                    $errorMessage = 'Docker permission denied. Container logs unavailable. See DOCKER_PERMISSION_FIX.md for solution.';
                } elseif (str_contains($result['error'], 'Cannot connect')) {
                    $errorMessage = 'Docker daemon not accessible';
                } elseif ($result['error']) {
                    $errorMessage = 'Failed to get logs: ' . $result['error'];
                }

                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'logs' => '',
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get logs: ' . $e->getMessage(),
                'logs' => '',
            ], 200);
        }
    }

    /**
     * Get service metrics
     */
    public function metrics($service)
    {
        if (!isset(self::SERVICES[$service])) {
            return response()->json([
                'success' => false,
                'message' => 'Service not found',
            ], 404);
        }

        try {
            $container = self::SERVICES[$service]['container'];

            // Get container stats
            $command = "docker stats {$container} --no-stream --format json";
            $result = $this->monitoringService->executeCommand($command);

            if ($result['success']) {
                $stats = json_decode($result['output'], true);

                return response()->json([
                    'success' => true,
                    'metrics' => $stats,
                ]);
            } else {
                // Detect permission error
                $errorMessage = 'Failed to get metrics';
                if (str_contains($result['error'], 'permission denied')) {
                    $errorMessage = 'Docker permission denied. See DOCKER_PERMISSION_FIX.md for solution.';
                } elseif (str_contains($result['error'], 'Cannot connect')) {
                    $errorMessage = 'Docker daemon not accessible';
                }

                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'metrics' => null,
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get metrics: ' . $e->getMessage(),
                'metrics' => null,
            ], 200);
        }
    }

    /**
     * Restart all services
     */
    /**
     * Restart all services
     */
    public function restartAll()
    {
        try {
            Log::warning('Restart all services requested', [
                'user_id' => auth()->id(),
                'user_name' => auth()->user()->name,
            ]);

            $results = [];
            $failures = [];

            foreach (self::SERVICES as $key => $config) {
                // Skip postgres to prevent database connection issues during restart
                if ($key === 'postgres') {
                    continue;
                }

                $container = $config['container'];
                $command = "docker restart {$container}";
                $result = $this->monitoringService->executeCommand($command, 60);

                if ($result['success']) {
                    $results[] = "{$config['name']} restarted";
                } else {
                    $failures[] = "{$config['name']}: " . ($result['error'] ?: 'Unknown error');
                }
            }

            if (empty($failures)) {
                return response()->json([
                    'success' => true,
                    'message' => 'All services triggered restart successfully',
                    'details' => $results
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Some services failed to restart: ' . implode(', ', $failures),
                    'details' => $results
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to restart all services: ' . $e->getMessage(),
            ], 200);
        }
    }

}
