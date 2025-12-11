<?php

namespace App\Services\System;

use Symfony\Component\Process\Process;

class SystemMonitoringService
{
    /**
     * Get service status details
     */
    public function getServiceStatus($key, $config)
    {
        $container = $config['container'];

        // Get container inspect
        $inspectCommand = "docker inspect {$container} --format json";
        $inspectResult = $this->executeCommand($inspectCommand);

        if (! $inspectResult['success']) {
            // Detect permission error
            $errorMessage = 'Container not found';
            if (str_contains($inspectResult['error'], 'permission denied')) {
                $errorMessage = 'Docker permission denied';
            } elseif (str_contains($inspectResult['error'], 'Cannot connect')) {
                $errorMessage = 'Docker daemon not accessible';
            }

            return [
                'key' => $key,
                'name' => $config['name'],
                'container' => $container,
                'icon' => $config['icon'],
                'description' => $config['description'],
                'status' => 'unavailable',
                'health' => 'unavailable',
                'uptime' => null,
                'error' => $errorMessage,
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
    public function getSystemInfo(int $totalServicesCount)
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
            'total_containers' => $totalServicesCount,
        ];
    }

    /**
     * Execute shell command safely
     */
    public function executeCommand($command, $timeout = 10)
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
