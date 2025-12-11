<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Face Embedding Cache Service
 * Caches face embeddings in Redis for fast verification
 */
class FaceEmbeddingCache
{
    /**
     * Cache key prefix
     */
    private const PREFIX = 'face_embedding:';

    /**
     * Cache TTL (1 hour)
     */
    private const TTL = 3600;

    /**
     * All employees cache key
     */
    private const ALL_KEY = 'face_embeddings:all';

    /**
     * Get cached embedding for an employee
     */
    public function getEmbedding(string $employeeId): ?array
    {
        try {
            $cacheKey = self::PREFIX . $employeeId;
            $cached = Cache::get($cacheKey);

            if ($cached) {
                Log::debug("Face embedding cache HIT for employee {$employeeId}");
                return json_decode($cached, true);
            }

            Log::debug("Face embedding cache MISS for employee {$employeeId}");
            return null;

        } catch (\Exception $e) {
            Log::error('Failed to get cached embedding', [
                'employee_id' => $employeeId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Cache embedding for an employee
     */
    public function setEmbedding(string $employeeId, array $embeddingData): bool
    {
        try {
            $cacheKey = self::PREFIX . $employeeId;

            // Store in cache
            $success = Cache::put($cacheKey, json_encode($embeddingData), self::TTL);

            if ($success) {
                Log::info("Cached face embedding for employee {$employeeId}");

                // Invalidate "all" cache
                Cache::forget(self::ALL_KEY);
            }

            return $success;

        } catch (\Exception $e) {
            Log::error('Failed to cache embedding', [
                'employee_id' => $employeeId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get all employees with face embeddings (cached)
     */
    public function getAllEmbeddings(): ?array
    {
        try {
            // Check cache first
            $cached = Cache::get(self::ALL_KEY);

            if ($cached) {
                Log::debug("All embeddings cache HIT");
                return json_decode($cached, true);
            }

            Log::debug("All embeddings cache MISS - will be populated on next query");
            return null;

        } catch (\Exception $e) {
            Log::error('Failed to get all cached embeddings', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Cache all employees' embeddings
     */
    public function setAllEmbeddings(array $embeddings): bool
    {
        try {
            $success = Cache::put(self::ALL_KEY, json_encode($embeddings), self::TTL);

            if ($success) {
                Log::info("Cached all face embeddings", [
                    'count' => count($embeddings)
                ]);
            }

            return $success;

        } catch (\Exception $e) {
            Log::error('Failed to cache all embeddings', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Invalidate cache for an employee
     */
    public function invalidate(string $employeeId): bool
    {
        try {
            $cacheKey = self::PREFIX . $employeeId;

            Cache::forget($cacheKey);
            Cache::forget(self::ALL_KEY);

            Log::info("Invalidated cache for employee {$employeeId}");
            return true;

        } catch (\Exception $e) {
            Log::error('Failed to invalidate cache', [
                'employee_id' => $employeeId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Clear all face embedding caches
     */
    public function flush(): bool
    {
        try {
            // Get all keys matching our prefix
            $pattern = self::PREFIX . '*';

            // Delete using Cache facade (works with Redis, file, database drivers)
            Cache::forget(self::ALL_KEY);

            // For Redis, we can use pattern matching
            if (config('cache.default') === 'redis') {
                $keys = Redis::keys($pattern);
                foreach ($keys as $key) {
                    // Remove prefix that Redis adds
                    $cleanKey = str_replace(config('cache.prefix') . ':', '', $key);
                    Cache::forget($cleanKey);
                }
            }

            Log::info("Flushed all face embedding caches");
            return true;

        } catch (\Exception $e) {
            Log::error('Failed to flush caches', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get cache statistics
     */
    public function getStats(): array
    {
        try {
            $stats = [
                'cache_driver' => config('cache.default'),
                'ttl' => self::TTL,
                'enabled' => config('deepface.cache_embeddings', true),
            ];

            // Count cached embeddings (approximation)
            if (config('cache.default') === 'redis') {
                $pattern = self::PREFIX . '*';
                $keys = Redis::keys($pattern);
                $stats['cached_count'] = count($keys);
            }

            return $stats;

        } catch (\Exception $e) {
            Log::error('Failed to get cache stats', [
                'error' => $e->getMessage()
            ]);
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Warm up cache with all employees
     */
    public function warmUp(): int
    {
        try {
            // This will be called from a command or service
            // to pre-populate cache with all employee embeddings

            $employees = \App\Models\Employee::whereNotNull('metadata->face_recognition->descriptor')
                ->get(['id', 'employee_code', 'full_name', 'metadata']);

            $count = 0;
            foreach ($employees as $employee) {
                $faceData = $employee->metadata['face_recognition'] ?? null;

                if ($faceData && isset($faceData['descriptor'])) {
                    $embeddingData = [
                        'employee_id' => $employee->id,
                        'employee_code' => $employee->employee_code,
                        'name' => $employee->full_name,
                        'embedding' => $faceData['descriptor'],
                        'algorithm' => $faceData['algorithm'] ?? 'unknown',
                        'confidence' => $faceData['confidence'] ?? 0.0,
                    ];

                    if ($this->setEmbedding($employee->id, $embeddingData)) {
                        $count++;
                    }
                }
            }

            Log::info("Cache warm-up completed", ['cached' => $count]);
            return $count;

        } catch (\Exception $e) {
            Log::error('Failed to warm up cache', [
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }
}
