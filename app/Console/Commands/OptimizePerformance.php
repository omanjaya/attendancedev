<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class OptimizePerformance extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'performance:optimize 
                            {--cache : Clear and rebuild cache}
                            {--config : Clear config cache}
                            {--routes : Clear route cache}
                            {--views : Clear view cache}
                            {--database : Optimize database}
                            {--all : Run all optimizations}';

    /**
     * The console command description.
     */
    protected $description = 'Optimize application performance by clearing caches and optimizing database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting performance optimization...');

        $cache = $this->option('cache') || $this->option('all');
        $config = $this->option('config') || $this->option('all');
        $routes = $this->option('routes') || $this->option('all');
        $views = $this->option('views') || $this->option('all');
        $database = $this->option('database') || $this->option('all');

        try {
            // Clear application cache
            if ($cache) {
                $this->optimizeApplicationCache();
            }

            // Clear config cache
            if ($config) {
                $this->optimizeConfig();
            }

            // Clear route cache
            if ($routes) {
                $this->optimizeRoutes();
            }

            // Clear view cache
            if ($views) {
                $this->optimizeViews();
            }

            // Optimize database
            if ($database) {
                $this->optimizeDatabase();
            }

            // If no specific options, show help
            if (!$cache && !$config && !$routes && !$views && !$database && !$this->option('all')) {
                $this->info('Use --all to run all optimizations, or specify individual options:');
                $this->info('--cache    Clear application cache');
                $this->info('--config   Clear config cache');
                $this->info('--routes   Clear route cache');
                $this->info('--views    Clear view cache');
                $this->info('--database Optimize database');
                return Command::SUCCESS;
            }

            $this->info('Performance optimization completed successfully!');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Performance optimization failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Optimize application cache
     */
    private function optimizeApplicationCache(): void
    {
        $this->info('Optimizing application cache...');

        // Clear application cache
        Artisan::call('cache:clear');
        $this->line('✓ Application cache cleared');

        // Clear performance monitoring cache
        Cache::forget('performance_metrics');
        Cache::forget('performance_summary_24h');
        Cache::forget('performance_summary_1h');
        $this->line('✓ Performance monitoring cache cleared');

        // Rebuild essential cache
        try {
            // Cache configuration if in production
            if (app()->environment('production')) {
                Artisan::call('config:cache');
                $this->line('✓ Configuration cached');
            }
        } catch (\Exception $e) {
            $this->warn('Failed to cache configuration: ' . $e->getMessage());
        }
    }

    /**
     * Optimize configuration
     */
    private function optimizeConfig(): void
    {
        $this->info('Optimizing configuration...');

        Artisan::call('config:clear');
        $this->line('✓ Configuration cache cleared');

        if (app()->environment('production')) {
            Artisan::call('config:cache');
            $this->line('✓ Configuration cached for production');
        }
    }

    /**
     * Optimize routes
     */
    private function optimizeRoutes(): void
    {
        $this->info('Optimizing routes...');

        Artisan::call('route:clear');
        $this->line('✓ Route cache cleared');

        if (app()->environment('production')) {
            Artisan::call('route:cache');
            $this->line('✓ Routes cached for production');
        }
    }

    /**
     * Optimize views
     */
    private function optimizeViews(): void
    {
        $this->info('Optimizing views...');

        Artisan::call('view:clear');
        $this->line('✓ View cache cleared');

        // Precompile views
        Artisan::call('view:cache');
        $this->line('✓ Views cached');
    }

    /**
     * Optimize database
     */
    private function optimizeDatabase(): void
    {
        $this->info('Optimizing database...');

        try {
            // Get database driver
            $driver = DB::getDriverName();

            if ($driver === 'pgsql') {
                $this->optimizePostgreSQL();
            } elseif ($driver === 'mysql') {
                $this->optimizeMySQL();
            } else {
                $this->warn("Database optimization not implemented for {$driver} driver");
                return;
            }

            $this->line('✓ Database optimization completed');

        } catch (\Exception $e) {
            $this->error('Database optimization failed: ' . $e->getMessage());
        }
    }

    /**
     * Optimize PostgreSQL database
     */
    private function optimizePostgreSQL(): void
    {
        // Analyze all tables
        DB::statement('ANALYZE');
        $this->line('✓ PostgreSQL: Tables analyzed');

        // Vacuum database
        try {
            DB::statement('VACUUM');
            $this->line('✓ PostgreSQL: Database vacuumed');
        } catch (\Exception $e) {
            $this->warn('PostgreSQL VACUUM failed (this is normal if not superuser): ' . $e->getMessage());
        }

        // Get table statistics
        $tables = DB::select("
            SELECT 
                schemaname,
                tablename,
                pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) AS size
            FROM pg_tables 
            WHERE schemaname = 'public'
            ORDER BY pg_total_relation_size(schemaname||'.'||tablename) DESC
            LIMIT 5
        ");

        $this->line('✓ Top 5 largest tables:');
        foreach ($tables as $table) {
            $this->line("  - {$table->tablename}: {$table->size}");
        }
    }

    /**
     * Optimize MySQL database
     */
    private function optimizeMySQL(): void
    {
        // Get all tables
        $tables = DB::select('SHOW TABLES');
        $tableColumn = 'Tables_in_' . config('database.connections.mysql.database');

        foreach ($tables as $table) {
            $tableName = $table->$tableColumn;
            
            try {
                // Optimize table
                DB::statement("OPTIMIZE TABLE `{$tableName}`");
                $this->line("✓ MySQL: Optimized table {$tableName}");
            } catch (\Exception $e) {
                $this->warn("Failed to optimize table {$tableName}: " . $e->getMessage());
            }
        }

        // Analyze tables
        DB::statement('ANALYZE TABLE ' . implode(', ', array_map(function($table) use ($tableColumn) {
            return '`' . $table->$tableColumn . '`';
        }, $tables)));
        $this->line('✓ MySQL: Tables analyzed');
    }

    /**
     * Clean up temporary files
     */
    private function cleanupTemporaryFiles(): void
    {
        $this->info('Cleaning up temporary files...');

        $paths = [
            storage_path('logs'),
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
        ];

        foreach ($paths as $path) {
            if (File::exists($path)) {
                $files = File::glob($path . '/*');
                $count = 0;
                
                foreach ($files as $file) {
                    if (File::isFile($file) && File::lastModified($file) < now()->subDays(7)->timestamp) {
                        File::delete($file);
                        $count++;
                    }
                }
                
                if ($count > 0) {
                    $this->line("✓ Cleaned {$count} old files from " . basename($path));
                }
            }
        }
    }

    /**
     * Show optimization recommendations
     */
    private function showRecommendations(): void
    {
        $this->info('Performance Recommendations:');
        
        // Check OPcache
        if (!function_exists('opcache_get_status') || !opcache_get_status()) {
            $this->warn('• Enable OPcache for better PHP performance');
        } else {
            $this->line('✓ OPcache is enabled');
        }

        // Check Redis
        try {
            if (config('cache.default') !== 'redis') {
                $this->warn('• Consider using Redis for caching in production');
            } else {
                Cache::store('redis')->put('test', 'value', 1);
                $this->line('✓ Redis cache is working');
            }
        } catch (\Exception $e) {
            $this->warn('• Redis connection issue: ' . $e->getMessage());
        }

        // Check queue driver
        if (config('queue.default') === 'sync') {
            $this->warn('• Use a proper queue driver (Redis, database) in production');
        } else {
            $this->line('✓ Queue driver is configured');
        }

        // Check session driver
        if (config('session.driver') === 'file') {
            $this->warn('• Consider using Redis or database for session storage in production');
        } else {
            $this->line('✓ Session driver is optimized');
        }
    }
}