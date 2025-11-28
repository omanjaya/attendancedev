---
name: database-performance
description: Database optimization, query performance, indexing strategies, and caching patterns. Use when optimizing queries, designing database schemas, or improving application performance.
allowed-tools: Read, Grep, Glob, Edit, Write, Bash
---

# Database & Performance Skill

This skill provides guidance for optimizing database performance, queries, and overall application speed.

## Database Schema Design

### Primary Keys - Use UUIDs
```php
// Migration
Schema::create('employees', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('name');
    $table->string('email')->unique();
    $table->uuid('department_id');
    $table->timestamps();
    $table->softDeletes();

    // Foreign key with UUID
    $table->foreign('department_id')
        ->references('id')
        ->on('departments')
        ->onDelete('cascade');
});

// Model
class Employee extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;
}
```

### Index Strategies

```php
// Single column index - for WHERE, ORDER BY
$table->index('status');
$table->index('created_at');

// Composite index - order matters!
// Good for: WHERE department_id = ? AND status = ?
$table->index(['department_id', 'status']);

// Covering index - includes all queried columns
$table->index(['department_id', 'status', 'name']);

// Unique constraint with index
$table->unique('email');
$table->unique(['employee_id', 'date']); // Compound unique

// Full-text search (MySQL/PostgreSQL)
$table->fullText(['name', 'description']);
```

### When to Add Indexes

| Add Index | Don't Add Index |
|-----------|-----------------|
| Columns in WHERE clauses | Low cardinality columns (boolean) |
| Columns in JOIN conditions | Frequently updated columns |
| Columns in ORDER BY | Small tables (<1000 rows) |
| Foreign key columns | Columns rarely queried |
| Columns with high cardinality | |

### Index Analysis
```sql
-- Check existing indexes
SHOW INDEX FROM employees;

-- Explain query to see index usage
EXPLAIN SELECT * FROM employees WHERE department_id = 'uuid-here';

-- Check slow queries
SHOW FULL PROCESSLIST;
```

## Query Optimization

### N+1 Query Problem

```php
// ❌ BAD - N+1 queries
$employees = Employee::all();
foreach ($employees as $employee) {
    echo $employee->department->name; // Query per employee!
}

// ✅ GOOD - Eager loading
$employees = Employee::with('department')->get();

// ✅ GOOD - Nested eager loading
$employees = Employee::with([
    'department',
    'schedules.period',
    'attendances' => fn($q) => $q->whereMonth('date', now()->month)
])->get();

// ✅ GOOD - Conditional eager loading
$employees = Employee::with([
    'attendances' => fn($q) => $q->latest()->limit(5)
])->get();
```

### Select Only Needed Columns

```php
// ❌ BAD - Select all columns
$employees = Employee::all();

// ✅ GOOD - Select specific columns
$employees = Employee::select(['id', 'name', 'email', 'department_id'])
    ->with('department:id,name')
    ->get();

// ✅ GOOD - Exclude heavy columns
$employees = Employee::without('metadata', 'face_data')->get();
```

### Aggregate Queries

```php
// ❌ BAD - Load all records to count
$count = Employee::all()->count();

// ✅ GOOD - Database count
$count = Employee::count();

// ✅ GOOD - Conditional counts
$activeCount = Employee::where('status', 'active')->count();

// ✅ GOOD - Multiple aggregates in one query
$stats = Employee::selectRaw('
    COUNT(*) as total,
    SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active_count,
    AVG(salary) as avg_salary
')->first();

// ✅ GOOD - Count with relation
$employees = Employee::withCount(['attendances', 'leaves'])->get();
// Access: $employee->attendances_count
```

### Chunking Large Datasets

```php
// ❌ BAD - Load all into memory
$employees = Employee::all();
foreach ($employees as $employee) {
    // Process
}

// ✅ GOOD - Process in chunks
Employee::chunk(100, function ($employees) {
    foreach ($employees as $employee) {
        // Process each employee
    }
});

// ✅ GOOD - Lazy collection for memory efficiency
Employee::lazy()->each(function ($employee) {
    // Process - memory efficient
});

// ✅ GOOD - Cursor for read-only iteration
foreach (Employee::cursor() as $employee) {
    // Minimal memory usage
}
```

### Efficient Updates

```php
// ❌ BAD - Load then update
$employees = Employee::where('department_id', $oldId)->get();
foreach ($employees as $employee) {
    $employee->department_id = $newId;
    $employee->save();
}

// ✅ GOOD - Mass update
Employee::where('department_id', $oldId)
    ->update(['department_id' => $newId]);

// ✅ GOOD - Upsert (insert or update)
Employee::upsert([
    ['id' => $id1, 'name' => 'John', 'salary' => 5000],
    ['id' => $id2, 'name' => 'Jane', 'salary' => 6000],
], ['id'], ['name', 'salary']);

// ✅ GOOD - Increment/Decrement
Employee::where('id', $id)->increment('leave_balance', 5);
```

### Raw Queries When Needed

```php
// Complex aggregation
$report = DB::select("
    SELECT
        d.name as department,
        COUNT(e.id) as employee_count,
        AVG(TIMESTAMPDIFF(YEAR, e.hire_date, NOW())) as avg_tenure,
        SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_days
    FROM departments d
    LEFT JOIN employees e ON e.department_id = d.id
    LEFT JOIN attendances a ON a.employee_id = e.id
        AND a.date BETWEEN ? AND ?
    GROUP BY d.id, d.name
    ORDER BY employee_count DESC
", [$startDate, $endDate]);

// With bindings for safety
DB::statement("
    UPDATE employees
    SET status = ?
    WHERE last_active_at < ?
", ['inactive', now()->subMonths(6)]);
```

## Caching Strategies

### Query Caching

```php
// Cache expensive queries
$departments = Cache::remember('departments.all', 3600, function () {
    return Department::with('manager')->get();
});

// Cache with tags (Redis required)
$employees = Cache::tags(['employees', 'department-' . $deptId])
    ->remember("employees.dept.{$deptId}", 3600, function () use ($deptId) {
        return Employee::where('department_id', $deptId)->get();
    });

// Invalidate cache on update
public function updated(Employee $employee)
{
    Cache::tags(['employees'])->flush();
    Cache::forget("employee.{$employee->id}");
}
```

### Cache Patterns

```php
// Cache-aside pattern
public function getEmployee(string $id): Employee
{
    return Cache::remember("employee.{$id}", 3600, function () use ($id) {
        return Employee::with('department')->findOrFail($id);
    });
}

// Write-through pattern
public function updateEmployee(string $id, array $data): Employee
{
    $employee = Employee::findOrFail($id);
    $employee->update($data);

    // Update cache immediately
    Cache::put("employee.{$id}", $employee->fresh(), 3600);

    return $employee;
}

// Cache invalidation
public function deleteEmployee(string $id): void
{
    Employee::destroy($id);
    Cache::forget("employee.{$id}");
    Cache::tags(['employees'])->flush();
}
```

### Dashboard Caching

```php
// Cache dashboard stats
public function getDashboardStats(): array
{
    return Cache::remember('dashboard.stats', 900, function () { // 15 minutes
        return [
            'total_employees' => Employee::count(),
            'present_today' => Attendance::whereDate('date', today())
                ->where('status', 'present')
                ->count(),
            'on_leave' => Leave::where('status', 'approved')
                ->whereDate('start_date', '<=', today())
                ->whereDate('end_date', '>=', today())
                ->count(),
            'pending_requests' => Leave::where('status', 'pending')->count(),
        ];
    });
}

// Refresh on events
protected $listen = [
    AttendanceRecorded::class => [RefreshDashboardCache::class],
    LeaveApproved::class => [RefreshDashboardCache::class],
];
```

## Database Connection Optimization

### Connection Pooling (config/database.php)

```php
'mysql' => [
    'driver' => 'mysql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'database' => env('DB_DATABASE', 'forge'),
    'username' => env('DB_USERNAME', 'forge'),
    'password' => env('DB_PASSWORD', ''),

    // Connection pool settings
    'pool' => [
        'min_connections' => 1,
        'max_connections' => 10,
        'connect_timeout' => 10.0,
        'wait_timeout' => 3.0,
        'heartbeat' => -1,
        'max_idle_time' => 60,
    ],

    // Performance options
    'options' => [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
    ],
],
```

### Read/Write Splitting

```php
'mysql' => [
    'read' => [
        'host' => [
            env('DB_READ_HOST_1', '192.168.1.1'),
            env('DB_READ_HOST_2', '192.168.1.2'),
        ],
    ],
    'write' => [
        'host' => [env('DB_WRITE_HOST', '192.168.1.3')],
    ],
    'sticky' => true, // Use write connection after write operation
    // ... other options
],
```

## Performance Monitoring

### Query Logging

```php
// Enable query log (development only)
DB::enableQueryLog();

// Your queries here

// Get logged queries
$queries = DB::getQueryLog();
foreach ($queries as $query) {
    Log::debug('Query', [
        'sql' => $query['query'],
        'bindings' => $query['bindings'],
        'time' => $query['time'] . 'ms'
    ]);
}

// Disable after use
DB::disableQueryLog();
```

### Slow Query Detection

```php
// AppServiceProvider.php
public function boot()
{
    // Log slow queries (> 100ms)
    DB::listen(function ($query) {
        if ($query->time > 100) {
            Log::warning('Slow query detected', [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time' => $query->time . 'ms',
                'connection' => $query->connectionName,
            ]);
        }
    });
}
```

### Database Health Check

```php
// HealthCheckController.php
public function database(): JsonResponse
{
    try {
        $start = microtime(true);
        DB::select('SELECT 1');
        $latency = (microtime(true) - $start) * 1000;

        return response()->json([
            'status' => 'healthy',
            'latency_ms' => round($latency, 2),
            'connections' => [
                'active' => DB::connection()->getDatabaseName(),
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'unhealthy',
            'error' => $e->getMessage()
        ], 503);
    }
}
```

## Application Performance

### Route Caching

```bash
# Production only - cache routes
php artisan route:cache

# Clear route cache
php artisan route:clear
```

### Config Caching

```bash
# Production only - cache config
php artisan config:cache

# Clear config cache
php artisan config:clear
```

### View Caching

```bash
# Compile all Blade templates
php artisan view:cache

# Clear view cache
php artisan view:clear
```

### Optimized Autoloader

```bash
# Production deployment
composer install --optimize-autoloader --no-dev

# Dump optimized autoload
composer dump-autoload --optimize
```

### Complete Optimization Command

```bash
# Run all optimizations
php artisan optimize

# Clear all caches
php artisan optimize:clear
```

## Queue Optimization

### Async Processing

```php
// Move heavy operations to queue
class ProcessPayroll implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Employee $employee,
        public string $month
    ) {}

    public function handle(): void
    {
        // Heavy payroll calculation
    }
}

// Dispatch
ProcessPayroll::dispatch($employee, '2024-01')
    ->onQueue('payroll')
    ->delay(now()->addMinutes(5));
```

### Batch Processing

```php
// Process multiple jobs in batch
$batch = Bus::batch([
    new ProcessPayroll($employee1, $month),
    new ProcessPayroll($employee2, $month),
    new ProcessPayroll($employee3, $month),
])
->then(function (Batch $batch) {
    // All jobs completed
})
->catch(function (Batch $batch, Throwable $e) {
    // First job failure
})
->finally(function (Batch $batch) {
    // Batch finished
})
->dispatch();
```

## API Response Optimization

### Pagination

```php
// Always paginate large datasets
public function index(): JsonResponse
{
    $employees = Employee::with('department')
        ->paginate(15);

    return EmployeeResource::collection($employees);
}
```

### Response Compression

```php
// Middleware for gzip compression
class CompressResponse
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if ($this->shouldCompress($request, $response)) {
            $content = gzencode($response->getContent(), 9);
            $response->setContent($content);
            $response->header('Content-Encoding', 'gzip');
        }

        return $response;
    }
}
```

### Conditional Responses (ETags)

```php
public function show(Employee $employee): JsonResponse
{
    $etag = md5($employee->updated_at->toISOString());

    if (request()->header('If-None-Match') === $etag) {
        return response()->json(null, 304);
    }

    return response()->json(new EmployeeResource($employee))
        ->header('ETag', $etag)
        ->header('Cache-Control', 'private, max-age=3600');
}
```

## Performance Checklist

### Database
- [ ] Indexes on foreign keys
- [ ] Indexes on WHERE/ORDER BY columns
- [ ] Composite indexes for common queries
- [ ] Eager loading for relationships
- [ ] Select only needed columns
- [ ] Use chunking for large datasets

### Caching
- [ ] Cache expensive queries
- [ ] Cache dashboard/report data
- [ ] Implement cache invalidation
- [ ] Use Redis for session/cache

### Application
- [ ] Route caching in production
- [ ] Config caching in production
- [ ] View caching in production
- [ ] Optimized autoloader
- [ ] Queue heavy operations

### API
- [ ] Paginate list endpoints
- [ ] Enable response compression
- [ ] Implement ETags
- [ ] Use API resources
