<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

/**
 * Query Optimization Service
 * 
 * Provides utilities for optimizing database queries
 */
class QueryOptimizationService
{
    /**
     * Add database indexes if they don't exist
     */
    public static function ensureIndexes(): void
    {
        $indexes = [
            'attendances' => [
                ['employee_id', 'date'],
                ['date', 'check_in'],
                ['status'],
                ['created_at'],
            ],
            'employees' => [
                ['user_id'],
                ['employee_id'],
                ['status'],
                ['location_id'],
                ['department'],
            ],
            'payrolls' => [
                ['employee_id', 'payroll_period_start', 'payroll_period_end'],
                ['status'],
                ['pay_date'],
            ],
            'leaves' => [
                ['employee_id', 'start_date', 'end_date'],
                ['status'],
                ['leave_type_id'],
            ],
            'notifications' => [
                ['notifiable_type', 'notifiable_id'],
                ['read_at'],
                ['created_at'],
            ],
        ];

        foreach ($indexes as $table => $tableIndexes) {
            foreach ($tableIndexes as $columns) {
                self::createIndexIfNotExists($table, $columns);
            }
        }
    }

    /**
     * Create index if it doesn't exist
     */
    private static function createIndexIfNotExists(string $table, array $columns): void
    {
        $indexName = $table . '_' . implode('_', $columns) . '_index';
        
        if (!self::indexExists($table, $indexName)) {
            $columnsString = implode(', ', $columns);
            DB::statement("CREATE INDEX {$indexName} ON {$table} ({$columnsString})");
        }
    }

    /**
     * Check if index exists
     */
    private static function indexExists(string $table, string $indexName): bool
    {
        $indexes = DB::select("PRAGMA index_list({$table})");
        
        foreach ($indexes as $index) {
            if ($index->name === $indexName) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Optimize query with pagination
     */
    public static function optimizePagination(
        EloquentBuilder|Builder $query,
        int $perPage = 15,
        int $page = 1
    ): array {
        // Use cursor pagination for large datasets
        if ($query instanceof EloquentBuilder) {
            $total = $query->count();
            
            if ($total > 10000) {
                // Use cursor pagination for large datasets
                return self::cursorPagination($query, $perPage, $page);
            }
        }

        // Regular pagination for smaller datasets
        return $query->paginate($perPage, ['*'], 'page', $page)->toArray();
    }

    /**
     * Implement cursor-based pagination for large datasets
     */
    private static function cursorPagination(
        EloquentBuilder $query,
        int $perPage,
        int $page
    ): array {
        $offset = ($page - 1) * $perPage;
        
        // Get the cursor value (usually an ID or timestamp)
        $cursor = null;
        if ($page > 1) {
            $cursor = $query->clone()
                ->orderBy('id')
                ->offset($offset - 1)
                ->limit(1)
                ->value('id');
        }

        // Apply cursor condition
        if ($cursor) {
            $query->where('id', '>', $cursor);
        }

        $items = $query->orderBy('id')->limit($perPage)->get();

        return [
            'data' => $items,
            'has_more' => $items->count() === $perPage,
            'next_cursor' => $items->last()?->id,
        ];
    }

    /**
     * Optimize query with proper eager loading
     */
    public static function optimizeEagerLoading(
        EloquentBuilder $query,
        array $relations = []
    ): EloquentBuilder {
        // Analyze and optimize relationships
        $optimizedRelations = [];
        
        foreach ($relations as $relation) {
            if (is_string($relation)) {
                $optimizedRelations[] = $relation;
            } elseif (is_array($relation)) {
                // Handle nested relations with constraints
                foreach ($relation as $rel => $constraint) {
                    if (is_callable($constraint)) {
                        $optimizedRelations[$rel] = $constraint;
                    }
                }
            }
        }

        return $query->with($optimizedRelations);
    }

    /**
     * Cache expensive queries
     */
    public static function cacheQuery(
        string $cacheKey,
        callable $queryCallback,
        int $ttl = 3600
    ): mixed {
        return Cache::remember($cacheKey, $ttl, $queryCallback);
    }

    /**
     * Batch load related data to prevent N+1 queries
     */
    public static function batchLoad(
        string $model,
        array $ids,
        string $relation,
        array $columns = ['*']
    ): array {
        $modelClass = "App\\Models\\{$model}";
        
        if (!class_exists($modelClass)) {
            throw new \InvalidArgumentException("Model {$model} not found");
        }

        return $modelClass::whereIn('id', $ids)
            ->with($relation)
            ->get($columns)
            ->keyBy('id')
            ->toArray();
    }

    /**
     * Optimize aggregate queries
     */
    public static function optimizeAggregates(
        EloquentBuilder $query,
        array $aggregates
    ): array {
        $results = [];
        
        // Batch multiple aggregates in a single query
        $selectStatements = [];
        
        foreach ($aggregates as $alias => $aggregate) {
            if (is_string($aggregate)) {
                $selectStatements[] = DB::raw("COUNT(*) as {$alias}");
            } elseif (is_array($aggregate)) {
                $function = $aggregate['function'] ?? 'COUNT';
                $column = $aggregate['column'] ?? '*';
                $selectStatements[] = DB::raw("{$function}({$column}) as {$alias}");
            }
        }

        $result = $query->selectRaw(implode(', ', $selectStatements))->first();
        
        if ($result) {
            $results = $result->toArray();
        }

        return $results;
    }

    /**
     * Optimize date range queries
     */
    public static function optimizeDateRange(
        EloquentBuilder $query,
        string $column,
        string $startDate,
        string $endDate
    ): EloquentBuilder {
        // Use index-friendly date comparisons
        return $query->where($column, '>=', $startDate)
                    ->where($column, '<=', $endDate);
    }

    /**
     * Optimize full-text search
     */
    public static function optimizeSearch(
        EloquentBuilder $query,
        string $searchTerm,
        array $columns
    ): EloquentBuilder {
        if (empty($searchTerm)) {
            return $query;
        }

        // Use MATCH AGAINST for full-text search on MySQL
        if (DB::connection()->getDriverName() === 'mysql') {
            $columnsString = implode(', ', $columns);
            return $query->whereRaw(
                "MATCH ({$columnsString}) AGAINST (? IN BOOLEAN MODE)",
                [$searchTerm . '*']
            );
        }

        // Fallback to LIKE for other databases
        return $query->where(function ($q) use ($searchTerm, $columns) {
            foreach ($columns as $column) {
                $q->orWhere($column, 'LIKE', "%{$searchTerm}%");
            }
        });
    }

    /**
     * Analyze query performance
     */
    public static function analyzeQueryPerformance(string $sql): array
    {
        // Enable query log
        DB::enableQueryLog();
        
        // Execute query
        $start = microtime(true);
        $result = DB::select($sql);
        $end = microtime(true);
        
        // Get query log
        $queries = DB::getQueryLog();
        $lastQuery = end($queries);
        
        // Analyze performance
        $executionTime = ($end - $start) * 1000; // Convert to milliseconds
        
        return [
            'execution_time_ms' => round($executionTime, 2),
            'query' => $lastQuery['query'] ?? $sql,
            'bindings' => $lastQuery['bindings'] ?? [],
            'result_count' => count($result),
            'memory_usage' => memory_get_usage(true),
            'recommendations' => self::getPerformanceRecommendations($executionTime, count($result)),
        ];
    }

    /**
     * Get performance recommendations
     */
    private static function getPerformanceRecommendations(float $executionTime, int $resultCount): array
    {
        $recommendations = [];
        
        if ($executionTime > 100) {
            $recommendations[] = 'Query is slow (>100ms). Consider adding indexes or optimizing the query.';
        }
        
        if ($resultCount > 1000) {
            $recommendations[] = 'Large result set. Consider pagination or limiting results.';
        }
        
        if ($executionTime > 50 && $resultCount < 100) {
            $recommendations[] = 'Slow query with few results. Check for N+1 queries or missing indexes.';
        }
        
        return $recommendations;
    }

    /**
     * Get query optimization suggestions
     */
    public static function getOptimizationSuggestions(string $sql): array
    {
        $suggestions = [];
        
        // Check for SELECT *
        if (preg_match('/SELECT\s+\*\s+FROM/i', $sql)) {
            $suggestions[] = 'Avoid SELECT *. Specify only needed columns.';
        }
        
        // Check for subqueries
        if (preg_match('/SELECT.*SELECT/i', $sql)) {
            $suggestions[] = 'Consider using JOINs instead of subqueries for better performance.';
        }
        
        // Check for ORDER BY without LIMIT
        if (preg_match('/ORDER BY.*(?!LIMIT)/i', $sql)) {
            $suggestions[] = 'ORDER BY without LIMIT can be expensive. Consider adding LIMIT.';
        }
        
        // Check for LIKE with leading wildcard
        if (preg_match('/LIKE\s+["\']%/i', $sql)) {
            $suggestions[] = 'LIKE with leading wildcard cannot use indexes. Consider full-text search.';
        }
        
        return $suggestions;
    }
}