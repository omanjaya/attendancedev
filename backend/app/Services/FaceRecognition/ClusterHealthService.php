<?php

namespace App\Services\FaceRecognition;

class ClusterHealthService
{
    /**
     * Get cluster recommendations based on health status
     */
    public function getClusterRecommendation(array $status): array
    {
        $healthy = $status['healthy_instances'];
        $total = $status['total_instances'];
        $percentage = min(100, round(($healthy / max($total, 1)) * 100, 1));

        $recommendations = [];

        if ($percentage < 50) {
            $recommendations[] = '⚠️ CRITICAL: Less than 50% of instances healthy - immediate action required!';
            $recommendations[] = 'Check logs: tail -f python-services/face-recognition/logs/*.log';
            $recommendations[] = 'Restart unhealthy instances: cd python-services/face-recognition && ./start-cluster.sh';
        } elseif ($percentage < 75) {
            $recommendations[] = '⚠️ WARNING: Less than 75% of instances healthy';
            $recommendations[] = 'Consider restarting unhealthy instances';
        } elseif ($percentage === 100.0) {
            $recommendations[] = '✅ All instances healthy - cluster operating optimally';
        } else {
            $recommendations[] = '✅ Cluster healthy but some instances down';
            $recommendations[] = 'Monitor for recurring failures';
        }

        // Check response times
        $avgResponseTime = 0;
        $responseCount = 0;
        foreach ($status['instances'] as $instance) {
            if (isset($instance['response_time']) && $instance['healthy']) {
                $avgResponseTime += $instance['response_time'];
                $responseCount++;
            }
        }

        if ($responseCount > 0) {
            $avgResponseTime /= $responseCount;
            if ($avgResponseTime > 3.0) {
                $recommendations[] = "⚠️ High average response time: {$avgResponseTime}s (target: <2s)";
                $recommendations[] = 'Consider adding more instances or using GPU acceleration';
            }
        }

        return [
            'health_percentage' => round($percentage, 1),
            'status' => $percentage >= 75 ? 'healthy' : ($percentage >= 50 ? 'degraded' : 'critical'),
            'recommendations' => $recommendations,
        ];
    }
}
