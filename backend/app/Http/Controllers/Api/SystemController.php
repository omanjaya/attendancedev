<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class SystemController extends Controller
{
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
                $services[$key] = $this->getServiceStatus($key, $config);
            }

            // Get overall system info
            $systemInfo = $this->getSystemInfo();

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
            ], 500);
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
            $status = $this->getServiceStatus($service, $config);

            return response()->json([
                'success' => true,
                'service' => $status,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get service status: ' . $e->getMessage(),
            ], 500);
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
            $result = $this->executeCommand($command);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => self::SERVICES[$service]['name'] . ' restarted successfully',
                    'output' => $result['output'],
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to restart service: ' . $result['error'],
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Service restart failed', [
                'service' => $service,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to restart service: ' . $e->getMessage(),
            ], 500);
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
            $result = $this->executeCommand($command);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => self::SERVICES[$service]['name'] . ' started successfully',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to start service: ' . $result['error'],
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start service: ' . $e->getMessage(),
            ], 500);
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
            $result = $this->executeCommand($command);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => self::SERVICES[$service]['name'] . ' stopped successfully',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to stop service: ' . $result['error'],
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to stop service: ' . $e->getMessage(),
            ], 500);
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
            $result = $this->executeCommand($command, 30);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'logs' => $result['output'],
                    'lines' => $lines,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to get logs: ' . ($result['error'] ?: $result['output']),
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get logs: ' . $e->getMessage(),
            ], 500);
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
            $result = $this->executeCommand($command);

            if ($result['success']) {
                $stats = json_decode($result['output'], true);

                return response()->json([
                    'success' => true,
                    'metrics' => $stats,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to get metrics',
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get metrics: ' . $e->getMessage(),
            ], 500);
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
                $result = $this->executeCommand($command, 60);

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
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to restart all services: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get service status details
     */
    private function getServiceStatus($key, $config)
    {
        $container = $config['container'];

        // Get container inspect
        $inspectCommand = "docker inspect {$container} --format json";
        $inspectResult = $this->executeCommand($inspectCommand);

        if (!$inspectResult['success']) {
            return [
                'key' => $key,
                'name' => $config['name'],
                'container' => $container,
                'icon' => $config['icon'],
                'description' => $config['description'],
                'status' => 'unknown',
                'health' => 'unknown',
                'uptime' => null,
                'error' => 'Container not found',
            ];
        }

        $inspect = json_decode($inspectResult['output'], true)[0] ?? [];

        // Parse status
        $state = $inspect['State'] ?? [];
        $isRunning = $state['Running'] ?? false;
        $status = $isRunning ? 'running' : 'stopped';

        // Calculate uptime
        $uptime = null;
        if ($isRunning && isset($state['StartedAt'])) {
            $startTime = new \DateTime($state['StartedAt']);
            $now = new \DateTime();
            $uptime = $now->getTimestamp() - $startTime->getTimestamp();
        }

        // Get stats
        $statsCommand = "docker stats {$container} --no-stream --format \"{{.CPUPerc}}|{{.MemUsage}}|{{.MemPerc}}\"";
        $statsResult = $this->executeCommand($statsCommand);

        $cpu = null;
        $memory = null;
        $memoryPercent = null;

        if ($statsResult['success'] && $isRunning) {
            $parts = explode('|', trim($statsResult['output']));
            $cpu = str_replace('%', '', $parts[0] ?? '0');
            $memory = $parts[1] ?? 'N/A';
            $memoryPercent = str_replace('%', '', $parts[2] ?? '0');
        }

        return [
            'key' => $key,
            'name' => $config['name'],
            'container' => $container,
            'icon' => $config['icon'],
            'description' => $config['description'],
            'status' => $status,
            'health' => $isRunning ? 'healthy' : 'down',
            'uptime' => $uptime,
            'cpu' => $cpu ? floatval($cpu) : null,
            'memory' => $memory,
            'memory_percent' => $memoryPercent ? floatval($memoryPercent) : null,
            'restart_count' => $inspect['RestartCount'] ?? 0,
        ];
    }

    /**
     * Get system information
     */
    private function getSystemInfo()
    {
        // Docker version
        $dockerVersion = $this->executeCommand('docker --version');

        // Docker compose version
        $composeVersion = $this->executeCommand('docker compose version');

        // Disk usage
        $diskUsage = $this->executeCommand('df -h / | tail -1');

        // Running containers count
        $containersCommand = "docker ps --filter \"name=attendancedev\" --format \"{{.Names}}\" | wc -l";
        $runningContainers = $this->executeCommand($containersCommand);

        return [
            'docker_version' => trim($dockerVersion['output'] ?? 'Unknown'),
            'compose_version' => trim($composeVersion['output'] ?? 'Unknown'),
            'disk_usage' => trim($diskUsage['output'] ?? 'Unknown'),
            'running_containers' => intval(trim($runningContainers['output'] ?? 0)),
            'total_containers' => count(self::SERVICES),
        ];
    }

    /**
     * Execute shell command safely
     */
    private function executeCommand($command, $timeout = 10)
    {
        try {
            $process = Process::fromShellCommandline($command);
            $process->setTimeout($timeout);
            $process->run();

            return [
                'success' => $process->isSuccessful(),
                'output' => $process->getOutput(),
                'error' => $process->getErrorOutput(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'output' => '',
                'error' => $e->getMessage(),
            ];
        }
    }
}
