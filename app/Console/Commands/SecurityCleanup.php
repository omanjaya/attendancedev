<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SecurityCleanup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:cleanup 
                           {--audit-logs : Clean up old audit logs}
                           {--failed-attempts : Reset expired failed login attempts}
                           {--cache : Clear security-related cache entries}
                           {--expired-locks : Remove expired account locks}
                           {--all : Run all cleanup operations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up security-related data and reset expired security measures';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting security cleanup...');

        $operations = [];

        if ($this->option('all')) {
            $operations = ['audit-logs', 'failed-attempts', 'cache', 'expired-locks'];
        } else {
            if ($this->option('audit-logs')) $operations[] = 'audit-logs';
            if ($this->option('failed-attempts')) $operations[] = 'failed-attempts';
            if ($this->option('cache')) $operations[] = 'cache';
            if ($this->option('expired-locks')) $operations[] = 'expired-locks';
        }

        if (empty($operations)) {
            $this->error('No cleanup operations specified. Use --all or specify individual operations.');
            return 1;
        }

        $totalCleaned = 0;

        foreach ($operations as $operation) {
            $cleaned = $this->runCleanupOperation($operation);
            $totalCleaned += $cleaned;
        }

        $this->info("Security cleanup completed. Total items processed: {$totalCleaned}");
        return 0;
    }

    /**
     * Run a specific cleanup operation.
     */
    private function runCleanupOperation(string $operation): int
    {
        switch ($operation) {
            case 'audit-logs':
                return $this->cleanupAuditLogs();
            case 'failed-attempts':
                return $this->resetFailedAttempts();
            case 'cache':
                return $this->clearSecurityCache();
            case 'expired-locks':
                return $this->removeExpiredLocks();
            default:
                $this->warn("Unknown operation: {$operation}");
                return 0;
        }
    }

    /**
     * Clean up old audit logs based on retention policy.
     */
    private function cleanupAuditLogs(): int
    {
        $this->line('🧹 Cleaning up old audit logs...');

        $retentionDays = config('security.audit.retention_days', 90);
        $cutoffDate = Carbon::now()->subDays($retentionDays);

        $deletedCount = AuditLog::where('created_at', '<', $cutoffDate)->delete();

        $this->info("  ✓ Deleted {$deletedCount} audit log entries older than {$retentionDays} days");

        return $deletedCount;
    }

    /**
     * Reset expired failed login attempts.
     */
    private function resetFailedAttempts(): int
    {
        $this->line('🔄 Resetting expired failed login attempts...');

        $windowMinutes = config('security.rate_limiting.login.window_minutes', 15);
        $resetCount = 0;

        // Get users with failed login attempts that should be reset
        $users = User::where('failed_login_attempts', '>', 0)
            ->where(function ($query) use ($windowMinutes) {
                $query->whereNull('locked_until')
                      ->orWhere('locked_until', '<=', Carbon::now()->subMinutes($windowMinutes));
            })
            ->get();

        foreach ($users as $user) {
            // Check if the lockout period has expired
            if (!$user->locked_until || $user->locked_until->isPast()) {
                $user->failed_login_attempts = 0;
                $user->locked_until = null;
                $user->save();
                $resetCount++;
            }
        }

        $this->info("  ✓ Reset failed login attempts for {$resetCount} users");

        return $resetCount;
    }

    /**
     * Clear security-related cache entries.
     */
    private function clearSecurityCache(): int
    {
        $this->line('🗑️  Clearing security cache entries...');

        $patterns = [
            'failed_login_*',
            'rate_limit_*',
            'user_devices_*',
            'user_login_hours_*',
            'user_recent_logins_*',
            'security_settings'
        ];

        $clearedCount = 0;

        foreach ($patterns as $pattern) {
            try {
                // For Redis, we can use pattern matching
                if (config('cache.default') === 'redis') {
                    $keys = Cache::getRedis()->keys($pattern);
                    if (!empty($keys)) {
                        Cache::getRedis()->del($keys);
                        $clearedCount += count($keys);
                    }
                } else {
                    // For other cache drivers, we'll clear specific known keys
                    $this->clearPatternFromCache($pattern);
                    $clearedCount++;
                }
            } catch (\Exception $e) {
                $this->warn("  Failed to clear cache pattern: {$pattern}");
            }
        }

        $this->info("  ✓ Cleared {$clearedCount} security cache entries");

        return $clearedCount;
    }

    /**
     * Remove expired account locks.
     */
    private function removeExpiredLocks(): int
    {
        $this->line('🔓 Removing expired account locks...');

        $unlockedCount = User::where('locked_until', '<=', Carbon::now())
            ->where('locked_until', '!=', null)
            ->update([
                'locked_until' => null,
                'account_locked' => false
            ]);

        $this->info("  ✓ Unlocked {$unlockedCount} expired account locks");

        return $unlockedCount;
    }

    /**
     * Clear cache entries matching a pattern (for non-Redis cache drivers).
     */
    private function clearPatternFromCache(string $pattern): void
    {
        // Convert glob pattern to regex-like matching
        $basePattern = str_replace('*', '', $pattern);
        
        // Try to clear some common cache keys
        $commonKeys = [
            $basePattern,
            $basePattern . '1',
            $basePattern . '2',
            $basePattern . '3'
        ];

        foreach ($commonKeys as $key) {
            Cache::forget($key);
        }
    }
}