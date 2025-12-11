<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

/**
 * Base Repository
 *
 * Provides common CRUD operations and caching functionality
 */
abstract class BaseRepository
{
    protected Model $model;

    protected int $cacheTtl = 3600; // 1 hour default

    protected string $cachePrefix;

    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->cachePrefix = strtolower(class_basename($model));
    }

    /**
     * Get all records
     */
    public function all(array $columns = ['*']): Collection
    {
        $cacheKey = $this->getCacheKey('all', $columns);

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($columns) {
            return $this->model->select($columns)->get();
        });
    }

    /**
     * Find record by ID
     */
    public function find($id, array $columns = ['*']): ?Model
    {
        $cacheKey = $this->getCacheKey('find', [$id, $columns]);

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($id, $columns) {
            return $this->model->select($columns)->find($id);
        });
    }

    /**
     * Find record by ID or fail
     */
    public function findOrFail($id, array $columns = ['*']): Model
    {
        $cacheKey = $this->getCacheKey('findOrFail', [$id, $columns]);

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($id, $columns) {
            return $this->model->select($columns)->findOrFail($id);
        });
    }

    /**
     * Find records by criteria
     */
    public function findBy(array $criteria, array $columns = ['*']): Collection
    {
        $cacheKey = $this->getCacheKey('findBy', [$criteria, $columns]);

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($criteria, $columns) {
            $query = $this->model->select($columns);

            foreach ($criteria as $key => $value) {
                $query->where($key, $value);
            }

            return $query->get();
        });
    }

    /**
     * Find first record by criteria
     */
    public function findOneBy(array $criteria, array $columns = ['*']): ?Model
    {
        $cacheKey = $this->getCacheKey('findOneBy', [$criteria, $columns]);

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($criteria, $columns) {
            $query = $this->model->select($columns);

            foreach ($criteria as $key => $value) {
                $query->where($key, $value);
            }

            return $query->first();
        });
    }

    /**
     * Create a new record
     */
    public function create(array $data): Model
    {
        $model = $this->model->create($data);

        $this->clearCache();

        return $model;
    }

    /**
     * Update a record
     */
    public function update($id, array $data): Model
    {
        $model = $this->findOrFail($id);
        $model->update($data);

        $this->clearCache();

        return $model->fresh();
    }

    /**
     * Delete a record
     */
    public function delete($id): bool
    {
        $model = $this->findOrFail($id);
        $result = $model->delete();

        $this->clearCache();

        return $result;
    }

    /**
     * Get paginated records
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->model->select($columns)->paginate($perPage);
    }

    /**
     * Count records
     */
    public function count(array $criteria = []): int
    {
        $cacheKey = $this->getCacheKey('count', $criteria);

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($criteria) {
            $query = $this->model->query();

            foreach ($criteria as $key => $value) {
                $query->where($key, $value);
            }

            return $query->count();
        });
    }

    /**
     * Check if record exists
     */
    public function exists(array $criteria): bool
    {
        $cacheKey = $this->getCacheKey('exists', $criteria);

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($criteria) {
            $query = $this->model->query();

            foreach ($criteria as $key => $value) {
                $query->where($key, $value);
            }

            return $query->exists();
        });
    }

    /**
     * Get first or create record
     */
    public function firstOrCreate(array $attributes, array $values = []): Model
    {
        $model = $this->model->firstOrCreate($attributes, $values);

        if ($model->wasRecentlyCreated) {
            $this->clearCache();
        }

        return $model;
    }

    /**
     * Update or create record
     */
    public function updateOrCreate(array $attributes, array $values = []): Model
    {
        $model = $this->model->updateOrCreate($attributes, $values);

        $this->clearCache();

        return $model;
    }

    /**
     * Get fresh model instance
     */
    public function getModel(): Model
    {
        return $this->model;
    }

    /**
     * Set cache TTL
     */
    public function setCacheTtl(int $ttl): self
    {
        $this->cacheTtl = $ttl;

        return $this;
    }

    /**
     * Generate cache key
     */
    protected function getCacheKey(string $method, array $params = []): string
    {
        $key = $this->cachePrefix.'_'.$method;

        if (! empty($params)) {
            $key .= '_'.md5(serialize($params));
        }

        return $key;
    }

    /**
     * Clear all cache for this repository
     */
    protected function clearCache(): void
    {
        Cache::tags([$this->cachePrefix])->flush();
    }

    /**
     * Clear specific cache key
     */
    protected function forgetCache(string $key): void
    {
        Cache::forget($key);
    }
}
