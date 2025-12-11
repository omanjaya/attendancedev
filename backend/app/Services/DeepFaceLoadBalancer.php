<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Load Balancer for DeepFace Instances
 * Distributes requests across multiple DeepFace service instances
 */
class DeepFaceLoadBalancer
{
    /**
     * DeepFace service instances (ports)
     */
    private array $instances;

    /**
     * Current instance index for round-robin
     */
    private int $currentIndex = 0;

    /**
     * Health check cache duration (seconds)
     */
    private const HEALTH_CACHE_TTL = 30;

    /**
     * Request timeout (seconds)
     */
    private const REQUEST_TIMEOUT = 30;

    /**
     * Initialize load balancer with instances
     */
    public function __construct()
    {
        // Get DeepFace base URL from config (e.g., http://deepface:8001)
        $baseUrl = config('deepface.base_url', 'http://deepface:8001');

        // Parse the base URL
        $parsedUrl = parse_url($baseUrl);
        $scheme = $parsedUrl['scheme'] ?? 'http';
        $host = $parsedUrl['host'] ?? 'deepface';
        $urlPort = $parsedUrl['port'] ?? 8001;

        // For Docker setup, use the single service URL directly
        // Docker's internal load balancing handles multiple replicas via the service name
        $this->instances = [
            [
                'url' => "{$scheme}://{$host}:{$urlPort}",
                'port' => $urlPort,
                'healthy' => true,
                'last_check' => null,
            ]
        ];

        Log::info('DeepFace Load Balancer initialized', [
            'instances' => count($this->instances),
            'urls' => array_column($this->instances, 'url')
        ]);
    }

    /**
     * Get next available healthy instance (round-robin with health check)
     */
    public function getHealthyInstance(): ?array
    {
        $maxAttempts = count($this->instances);
        $attempts = 0;

        while ($attempts < $maxAttempts) {
            $instance = $this->instances[$this->currentIndex];

            // Move to next instance for next request
            $this->currentIndex = ($this->currentIndex + 1) % count($this->instances);

            // Check if instance is healthy
            if ($this->isHealthy($instance)) {
                return $instance;
            }

            $attempts++;
        }

        Log::error('No healthy DeepFace instances available');
        return null;
    }

    /**
     * Check if instance is healthy (cached)
     */
    private function isHealthy(array $instance): bool
    {
        $cacheKey = "deepface_health_{$instance['port']}";

        // Check cached health status
        $cachedHealth = Cache::get($cacheKey);
        if ($cachedHealth !== null) {
            return $cachedHealth;
        }

        // Perform actual health check
        try {
            $response = Http::timeout(3)->get("{$instance['url']}/health");

            $isHealthy = $response->successful() &&
                         $response->json('status') === 'healthy';

            // Cache result
            Cache::put($cacheKey, $isHealthy, self::HEALTH_CACHE_TTL);

            if (!$isHealthy) {
                Log::warning('DeepFace instance unhealthy', [
                    'port' => $instance['port'],
                    'response' => $response->json()
                ]);
            }

            return $isHealthy;

        } catch (\Exception $e) {
            Log::error('DeepFace health check failed', [
                'port' => $instance['port'],
                'error' => $e->getMessage()
            ]);

            // Cache as unhealthy
            Cache::put($cacheKey, false, 10); // Shorter cache for failed instances

            return false;
        }
    }

    /**
     * Extract face embedding (load balanced)
     */
    public function extractEmbedding($imageFile, int $maxRetries = 2): array
    {
        $attempt = 0;

        while ($attempt < $maxRetries) {
            $instance = $this->getHealthyInstance();

            if (!$instance) {
                throw new \RuntimeException('No healthy DeepFace instances available');
            }

            try {
                Log::info('Attempting embedding extraction', [
                    'port' => $instance['port'],
                    'attempt' => $attempt + 1
                ]);

                $response = Http::timeout(self::REQUEST_TIMEOUT)
                    ->attach('image', file_get_contents($imageFile->getRealPath()), $imageFile->getClientOriginalName())
                    ->post("{$instance['url']}/extract-embedding");

                if ($response->successful()) {
                    $data = $response->json();

                    Log::info('Embedding extraction successful', [
                        'port' => $instance['port'],
                        'success' => $data['success'] ?? false
                    ]);

                    return $data;
                }

                // Mark instance as unhealthy on failure
                Cache::put("deepface_health_{$instance['port']}", false, 10);

                Log::warning('Embedding extraction failed, trying next instance', [
                    'port' => $instance['port'],
                    'status' => $response->status()
                ]);

            } catch (\Exception $e) {
                Log::error('Embedding extraction error', [
                    'port' => $instance['port'] ?? 'unknown',
                    'error' => $e->getMessage()
                ]);

                // Mark instance as unhealthy
                if (isset($instance['port'])) {
                    Cache::put("deepface_health_{$instance['port']}", false, 10);
                }
            }

            $attempt++;
        }

        throw new \RuntimeException('All DeepFace instances failed to process request');
    }

    /**
     * Verify face against known faces (load balanced)
     */
    public function verifyFace($imageFile, array $knownFaces, int $maxRetries = 2): array
    {
        $attempt = 0;

        while ($attempt < $maxRetries) {
            $instance = $this->getHealthyInstance();

            if (!$instance) {
                throw new \RuntimeException('No healthy DeepFace instances available');
            }

            try {
                Log::info('Attempting face verification', [
                    'port' => $instance['port'],
                    'attempt' => $attempt + 1,
                    'known_faces_count' => count($knownFaces)
                ]);

                $response = Http::timeout(self::REQUEST_TIMEOUT)
                    ->attach('image', file_get_contents($imageFile->getRealPath()), $imageFile->getClientOriginalName())
                    ->post("{$instance['url']}/verify-face", [
                        'known_faces_json' => json_encode($knownFaces)
                    ]);

                if ($response->successful()) {
                    $data = $response->json();

                    Log::info('Face verification complete', [
                        'port' => $instance['port'],
                        'matched' => $data['matched'] ?? false
                    ]);

                    return $data;
                }

                // Mark instance as unhealthy on failure
                Cache::put("deepface_health_{$instance['port']}", false, 10);

                Log::warning('Face verification failed, trying next instance', [
                    'port' => $instance['port'],
                    'status' => $response->status()
                ]);

            } catch (\Exception $e) {
                Log::error('Face verification error', [
                    'port' => $instance['port'] ?? 'unknown',
                    'error' => $e->getMessage()
                ]);

                if (isset($instance['port'])) {
                    Cache::put("deepface_health_{$instance['port']}", false, 10);
                }
            }

            $attempt++;
        }

        throw new \RuntimeException('All DeepFace instances failed to verify face');
    }

    /**
     * Check liveness (load balanced)
     */
    public function checkLiveness($imageFile, int $maxRetries = 2): array
    {
        $attempt = 0;

        while ($attempt < $maxRetries) {
            $instance = $this->getHealthyInstance();

            if (!$instance) {
                throw new \RuntimeException('No healthy DeepFace instances available');
            }

            try {
                $response = Http::timeout(15)
                    ->attach('image', file_get_contents($imageFile->getRealPath()), $imageFile->getClientOriginalName())
                    ->post("{$instance['url']}/check-liveness");

                if ($response->successful()) {
                    return $response->json();
                }

                Cache::put("deepface_health_{$instance['port']}", false, 10);

            } catch (\Exception $e) {
                Log::error('Liveness check error', [
                    'port' => $instance['port'] ?? 'unknown',
                    'error' => $e->getMessage()
                ]);

                if (isset($instance['port'])) {
                    Cache::put("deepface_health_{$instance['port']}", false, 10);
                }
            }

            $attempt++;
        }

        // Fail-open for liveness check
        return [
            'success' => true,
            'is_live' => true,
            'message' => 'Liveness check unavailable, proceeding'
        ];
    }

    /**
     * Analyze facial emotion (load balanced)
     */
    public function analyzeEmotion($imageFile, string $expectedEmotion = 'happy', int $maxRetries = 2): array
    {
        $attempt = 0;

        while ($attempt < $maxRetries) {
            $instance = $this->getHealthyInstance();

            if (!$instance) {
                throw new \RuntimeException('No healthy DeepFace instances available');
            }

            try {
                Log::info('Attempting emotion analysis', [
                    'port' => $instance['port'],
                    'attempt' => $attempt + 1,
                    'expected_emotion' => $expectedEmotion
                ]);

                $response = Http::timeout(self::REQUEST_TIMEOUT)
                    ->attach('image', file_get_contents($imageFile->getRealPath()), $imageFile->getClientOriginalName())
                    ->post("{$instance['url']}/analyze-emotion", [
                        'expected_emotion' => $expectedEmotion
                    ]);

                if ($response->successful()) {
                    $data = $response->json();

                    Log::info('Emotion analysis successful', [
                        'port' => $instance['port'],
                        'dominant_emotion' => $data['dominant_emotion'] ?? null,
                        'is_match' => $data['is_match'] ?? false
                    ]);

                    return $data;
                }

                // Mark instance as unhealthy on failure
                Cache::put("deepface_health_{$instance['port']}", false, 10);

                Log::warning('Emotion analysis failed, trying next instance', [
                    'port' => $instance['port'],
                    'status' => $response->status()
                ]);

            } catch (\Exception $e) {
                Log::error('Emotion analysis error', [
                    'port' => $instance['port'] ?? 'unknown',
                    'error' => $e->getMessage()
                ]);

                if (isset($instance['port'])) {
                    Cache::put("deepface_health_{$instance['port']}", false, 10);
                }
            }

            $attempt++;
        }

        throw new \RuntimeException('All DeepFace instances failed to analyze emotion');
    }

    /**
     * Get cluster status
     */
    public function getClusterStatus(): array
    {
        $status = [];

        foreach ($this->instances as $instance) {
            try {
                $response = Http::timeout(3)->get("{$instance['url']}/health");

                $status[] = [
                    'port' => $instance['port'],
                    'url' => $instance['url'],
                    'healthy' => $response->successful(),
                    'response' => $response->json(),
                    'response_time' => $response->transferStats?->getTransferTime() ?? null
                ];
            } catch (\Exception $e) {
                $status[] = [
                    'port' => $instance['port'],
                    'url' => $instance['url'],
                    'healthy' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        return [
            'total_instances' => count($this->instances),
            'healthy_instances' => count(array_filter($status, fn($s) => $s['healthy'])),
            'instances' => $status
        ];
    }
}
