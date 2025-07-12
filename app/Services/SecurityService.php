<?php

namespace App\Services;

use App\Models\User;
use App\Models\AuditLog;
use App\Services\SecurityLogger;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SecurityService
{
    private SecurityLogger $securityLogger;

    public function __construct(SecurityLogger $securityLogger)
    {
        $this->securityLogger = $securityLogger;
    }
    /**
     * Check if user's password meets security requirements.
     */
    public function validatePasswordStrength(string $password): array
    {
        $requirements = [
            'length' => strlen($password) >= 8,
            'uppercase' => preg_match('/[A-Z]/', $password),
            'lowercase' => preg_match('/[a-z]/', $password),
            'numbers' => preg_match('/[0-9]/', $password),
            'special' => preg_match('/[^A-Za-z0-9]/', $password),
            'common' => !$this->isCommonPassword($password)
        ];

        $score = array_sum($requirements);
        $strength = match (true) {
            $score <= 2 => 'weak',
            $score <= 4 => 'medium',
            $score === 5 => 'strong',
            default => 'very_strong'
        };

        return [
            'requirements' => $requirements,
            'score' => $score,
            'strength' => $strength,
            'valid' => $score >= 4
        ];
    }

    /**
     * Check if password is in common passwords list.
     */
    private function isCommonPassword(string $password): bool
    {
        $commonPasswords = [
            'password', '123456', '123456789', 'qwerty', 'abc123',
            'password123', 'admin', 'letmein', 'welcome', 'monkey',
            'dragon', 'master', 'shadow', 'superman', 'michael'
        ];

        return in_array(strtolower($password), $commonPasswords);
    }

    /**
     * Track failed login attempts and implement rate limiting.
     */
    public function trackFailedLogin(string $identifier, Request $request): array
    {
        $key = "failed_login_{$identifier}";
        $attempts = Cache::get($key, 0) + 1;
        $lockoutTime = $this->calculateLockoutTime($attempts);

        Cache::put($key, $attempts, $lockoutTime);

        // Log suspicious activity
        if ($attempts >= 3) {
            $this->logSuspiciousActivity('multiple_failed_logins', [
                'identifier' => $identifier,
                'attempts' => $attempts,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
        }

        return [
            'attempts' => $attempts,
            'remaining_attempts' => max(0, 5 - $attempts),
            'locked_until' => $attempts >= 5 ? now()->addMinutes($lockoutTime) : null,
            'is_locked' => $attempts >= 5
        ];
    }

    /**
     * Calculate lockout time based on failed attempts.
     */
    private function calculateLockoutTime(int $attempts): int
    {
        return match (true) {
            $attempts <= 3 => 5,      // 5 minutes
            $attempts <= 5 => 15,     // 15 minutes
            $attempts <= 7 => 60,     // 1 hour
            default => 1440           // 24 hours
        };
    }

    /**
     * Clear failed login attempts after successful login.
     */
    public function clearFailedLoginAttempts(string $identifier): void
    {
        Cache::forget("failed_login_{$identifier}");
    }

    /**
     * Check if IP address is rate limited.
     */
    public function isRateLimited(string $ip, string $action = 'general'): bool
    {
        $key = "rate_limit_{$action}_{$ip}";
        $attempts = Cache::get($key, 0);
        $limit = $this->getRateLimit($action);

        return $attempts >= $limit['max_attempts'];
    }

    /**
     * Track rate limit attempts.
     */
    public function trackRateLimit(string $ip, string $action = 'general'): void
    {
        $key = "rate_limit_{$action}_{$ip}";
        $limit = $this->getRateLimit($action);
        $attempts = Cache::get($key, 0) + 1;

        Cache::put($key, $attempts, $limit['window_minutes'] * 60);
    }

    /**
     * Get rate limit configuration for action.
     */
    private function getRateLimit(string $action): array
    {
        $limits = [
            'login' => ['max_attempts' => 5, 'window_minutes' => 15],
            'api' => ['max_attempts' => 100, 'window_minutes' => 60],
            'password_reset' => ['max_attempts' => 3, 'window_minutes' => 60],
            'general' => ['max_attempts' => 60, 'window_minutes' => 60],
            // Enhanced 2FA rate limiting
            '2fa_verification' => ['max_attempts' => 5, 'window_minutes' => 15],
            '2fa_recovery_code' => ['max_attempts' => 3, 'window_minutes' => 60],
            '2fa_sms_request' => ['max_attempts' => 3, 'window_minutes' => 60],
            '2fa_emergency_recovery' => ['max_attempts' => 2, 'window_minutes' => 1440], // 24 hours
            '2fa_setup_attempt' => ['max_attempts' => 10, 'window_minutes' => 60]
        ];

        return $limits[$action] ?? $limits['general'];
    }

    /**
     * Detect suspicious user behavior patterns.
     */
    public function detectSuspiciousActivity(User $user, Request $request): array
    {
        $suspiciousIndicators = [];

        // Check for unusual login times
        if ($this->isUnusualLoginTime($user)) {
            $suspiciousIndicators[] = 'unusual_login_time';
        }

        // Check for new device/location
        if ($this->isNewDevice($user, $request)) {
            $suspiciousIndicators[] = 'new_device';
        }

        // Check for rapid successive logins
        if ($this->hasRapidLogins($user)) {
            $suspiciousIndicators[] = 'rapid_logins';
        }

        // Check for privilege escalation attempts
        if ($this->hasPrivilegeEscalationAttempts($user)) {
            $suspiciousIndicators[] = 'privilege_escalation';
        }

        $riskLevel = $this->calculateRiskLevel($suspiciousIndicators);

        return [
            'indicators' => $suspiciousIndicators,
            'risk_level' => $riskLevel,
            'requires_additional_verification' => $riskLevel >= 7
        ];
    }

    /**
     * Check if login time is unusual for user.
     */
    private function isUnusualLoginTime(User $user): bool
    {
        $currentHour = now()->hour;
        $recentLogins = Cache::get("user_login_hours_{$user->id}", []);

        if (empty($recentLogins)) {
            return false;
        }

        $averageHour = array_sum($recentLogins) / count($recentLogins);
        $deviation = abs($currentHour - $averageHour);

        return $deviation > 6; // More than 6 hours difference
    }

    /**
     * Check if this is a new device for the user.
     */
    private function isNewDevice(User $user, Request $request): bool
    {
        $deviceFingerprint = $this->generateDeviceFingerprint($request);
        $knownDevices = Cache::get("user_devices_{$user->id}", []);

        return !in_array($deviceFingerprint, $knownDevices);
    }

    /**
     * Generate device fingerprint for tracking.
     */
    private function generateDeviceFingerprint(Request $request): string
    {
        $components = [
            $request->userAgent(),
            $request->header('Accept'),
            $request->header('Accept-Language'),
            $request->header('Accept-Encoding')
        ];

        return hash('sha256', implode('|', $components));
    }

    /**
     * Check for rapid successive logins.
     */
    private function hasRapidLogins(User $user): bool
    {
        $recentLogins = Cache::get("user_recent_logins_{$user->id}", []);
        
        if (count($recentLogins) < 3) {
            return false;
        }

        $timeDiffs = [];
        for ($i = 1; $i < count($recentLogins); $i++) {
            $timeDiffs[] = $recentLogins[$i] - $recentLogins[$i - 1];
        }

        $averageTimeBetweenLogins = array_sum($timeDiffs) / count($timeDiffs);
        
        return $averageTimeBetweenLogins < 300; // Less than 5 minutes between logins
    }

    /**
     * Check for privilege escalation attempts.
     */
    private function hasPrivilegeEscalationAttempts(User $user): bool
    {
        $recentAudits = AuditLog::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subHours(24))
            ->where('action', 'LIKE', '%permission%')
            ->count();

        return $recentAudits > 5;
    }

    /**
     * Calculate risk level based on suspicious indicators.
     */
    private function calculateRiskLevel(array $indicators): int
    {
        $weights = [
            'unusual_login_time' => 2,
            'new_device' => 3,
            'rapid_logins' => 4,
            'privilege_escalation' => 5,
            'multiple_failed_logins' => 3
        ];

        $totalRisk = 0;
        foreach ($indicators as $indicator) {
            $totalRisk += $weights[$indicator] ?? 1;
        }

        return min($totalRisk, 10); // Cap at 10
    }

    /**
     * Log suspicious activity.
     */
    private function logSuspiciousActivity(string $type, array $data): void
    {
        Log::warning("Suspicious activity detected: {$type}", $data);

        // Also create audit log if user is identified
        if (isset($data['user_id'])) {
            AuditLog::create([
                'user_id' => $data['user_id'],
                'action' => "suspicious_activity_{$type}",
                'auditable_type' => 'App\Models\User',
                'auditable_id' => $data['user_id'],
                'old_values' => [],
                'new_values' => $data,
                'ip_address' => $data['ip_address'] ?? null,
                'user_agent' => $data['user_agent'] ?? null,
                'risk_level' => 'high'
            ]);
        }
    }

    /**
     * Store user device for future reference.
     */
    public function rememberDevice(User $user, Request $request): void
    {
        $deviceFingerprint = $this->generateDeviceFingerprint($request);
        $knownDevices = Cache::get("user_devices_{$user->id}", []);
        
        if (!in_array($deviceFingerprint, $knownDevices)) {
            $knownDevices[] = $deviceFingerprint;
            
            // Keep only last 5 devices
            if (count($knownDevices) > 5) {
                $knownDevices = array_slice($knownDevices, -5);
            }
            
            Cache::put("user_devices_{$user->id}", $knownDevices, 86400 * 30); // 30 days
        }
    }

    /**
     * Track user login time patterns.
     */
    public function trackLoginTime(User $user): void
    {
        $currentHour = now()->hour;
        $recentHours = Cache::get("user_login_hours_{$user->id}", []);
        
        $recentHours[] = $currentHour;
        
        // Keep only last 10 login hours
        if (count($recentHours) > 10) {
            $recentHours = array_slice($recentHours, -10);
        }
        
        Cache::put("user_login_hours_{$user->id}", $recentHours, 86400 * 30); // 30 days
    }

    /**
     * Track user login timestamps.
     */
    public function trackLoginTimestamp(User $user): void
    {
        $timestamp = time();
        $recentLogins = Cache::get("user_recent_logins_{$user->id}", []);
        
        $recentLogins[] = $timestamp;
        
        // Keep only last 5 login timestamps
        if (count($recentLogins) > 5) {
            $recentLogins = array_slice($recentLogins, -5);
        }
        
        Cache::put("user_recent_logins_{$user->id}", $recentLogins, 3600); // 1 hour
    }

    /**
     * Get security metrics for dashboard.
     */
    public function getSecurityMetrics(): array
    {
        $timeframe = now()->subDays(30);

        return [
            'failed_logins' => AuditLog::where('action', 'login_failed')
                ->where('created_at', '>=', $timeframe)
                ->count(),
            'suspicious_activities' => AuditLog::where('action', 'LIKE', 'suspicious_activity_%')
                ->where('created_at', '>=', $timeframe)
                ->count(),
            'high_risk_events' => AuditLog::where('risk_level', 'high')
                ->where('created_at', '>=', $timeframe)
                ->count(),
            'unique_ips' => AuditLog::where('created_at', '>=', $timeframe)
                ->distinct('ip_address')
                ->count(),
            'password_changes' => AuditLog::where('action', 'password_changed')
                ->where('created_at', '>=', $timeframe)
                ->count(),
            '2fa_enabled_users' => User::where('two_factor_enabled', true)->count(),
            'active_sessions' => $this->getActiveSessionsCount()
        ];
    }

    /**
     * Get count of active user sessions.
     */
    private function getActiveSessionsCount(): int
    {
        // This would depend on your session storage implementation
        // For database sessions:
        // return DB::table('sessions')->where('last_activity', '>', time() - 1800)->count();
        
        // For cache-based sessions, you might need a different approach
        return 0; // Placeholder
    }

    /**
     * Generate security report.
     */
    public function generateSecurityReport(int $days = 30): array
    {
        $startDate = now()->subDays($days);
        
        $report = [
            'period' => "{$days} days",
            'generated_at' => now()->toISOString(),
            'metrics' => $this->getSecurityMetrics(),
            'top_risk_events' => AuditLog::where('risk_level', 'high')
                ->where('created_at', '>=', $startDate)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get(),
            'failed_login_trends' => $this->getFailedLoginTrends($days),
            'recommendations' => $this->getSecurityRecommendations()
        ];

        return $report;
    }

    /**
     * Get failed login trends.
     */
    private function getFailedLoginTrends(int $days): array
    {
        $trends = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $count = AuditLog::where('action', 'login_failed')
                ->whereDate('created_at', $date)
                ->count();
            
            $trends[] = ['date' => $date, 'count' => $count];
        }
        
        return $trends;
    }

    /**
     * Get security recommendations based on current state.
     */
    private function getSecurityRecommendations(): array
    {
        $recommendations = [];
        
        $twoFactorStats = app(TwoFactorService::class)->getStatistics();
        
        if ($twoFactorStats['compliance_rate'] < 80) {
            $recommendations[] = [
                'type' => 'warning',
                'title' => 'Low 2FA Compliance',
                'description' => "Only {$twoFactorStats['compliance_rate']}% of required users have 2FA enabled.",
                'action' => 'Enforce 2FA for all administrative users'
            ];
        }

        $recentFailedLogins = AuditLog::where('action', 'login_failed')
            ->where('created_at', '>=', now()->subDays(7))
            ->count();
            
        if ($recentFailedLogins > 50) {
            $recommendations[] = [
                'type' => 'alert',
                'title' => 'High Failed Login Rate',
                'description' => "{$recentFailedLogins} failed login attempts in the last 7 days.",
                'action' => 'Review IP whitelist and consider additional rate limiting'
            ];
        }

        return $recommendations;
    }

    /**
     * Enhanced 2FA-specific rate limiting and brute force protection
     */
    
    /**
     * Track 2FA verification attempts with progressive penalties
     */
    public function track2FAAttempt(string $identifier, string $type = 'totp', bool $success = false): array
    {
        $key = "2fa_attempts_{$type}_{$identifier}";
        $attempts = Cache::get($key, 0);
        
        if (!$success) {
            $attempts++;
            $lockoutTime = $this->calculate2FALockoutTime($attempts, $type);
            Cache::put($key, $attempts, $lockoutTime);

            // Log suspicious activity for multiple failures
            if ($attempts >= 3) {
                $this->logSuspiciousActivity('multiple_2fa_failures', [
                    'identifier' => $this->sanitizeIdentifier($identifier),
                    'type' => $type,
                    'attempts' => $attempts,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'user_id' => auth()->id()
                ]);
            }

            // Trigger progressive security measures
            if ($attempts >= 5) {
                $this->trigger2FASecurityLockdown($identifier, $type);
            }
        } else {
            // Clear attempts on success
            Cache::forget($key);
        }

        $limit = $this->getRateLimit("2fa_{$type}");
        
        return [
            'attempts' => $attempts,
            'remaining_attempts' => max(0, $limit['max_attempts'] - $attempts),
            'locked_until' => $attempts >= $limit['max_attempts'] ? now()->addMinutes($this->calculate2FALockoutTime($attempts, $type)) : null,
            'is_locked' => $attempts >= $limit['max_attempts'],
            'requires_admin_intervention' => $attempts >= 10
        ];
    }

    /**
     * Calculate progressive 2FA lockout time
     */
    private function calculate2FALockoutTime(int $attempts, string $type): int
    {
        $baseTime = match ($type) {
            'emergency_recovery' => 1440, // 24 hours for emergency recovery
            'recovery_code' => 60,         // 1 hour for recovery codes
            'sms_request' => 60,           // 1 hour for SMS requests
            default => 15                  // 15 minutes for TOTP
        };

        // Progressive penalty: exponential backoff
        return match (true) {
            $attempts <= 3 => $baseTime,
            $attempts <= 5 => $baseTime * 2,
            $attempts <= 7 => $baseTime * 4,
            $attempts <= 10 => $baseTime * 8,
            default => $baseTime * 16      // Maximum penalty
        };
    }

    /**
     * Trigger security lockdown for repeated 2FA failures
     */
    private function trigger2FASecurityLockdown(string $identifier, string $type): void
    {
        $lockdownKey = "2fa_lockdown_{$identifier}";
        $lockdownData = [
            'triggered_at' => now()->toISOString(),
            'type' => $type,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => auth()->id(),
            'requires_admin_unlock' => true
        ];

        Cache::put($lockdownKey, $lockdownData, 86400 * 7); // 7 days

        // Log critical security event
        Log::critical('2FA Security Lockdown Triggered', $lockdownData);

        // Notify administrators immediately
        $this->notifyAdminsOfSecurityLockdown($lockdownData);
    }

    /**
     * Check if identifier is under 2FA security lockdown
     */
    public function is2FALockedDown(string $identifier): bool
    {
        return Cache::has("2fa_lockdown_{$identifier}");
    }

    /**
     * Admin method to unlock 2FA lockdown
     */
    public function unlock2FALockdown(string $identifier, int $adminUserId): bool
    {
        $lockdownKey = "2fa_lockdown_{$identifier}";
        $lockdownData = Cache::get($lockdownKey);

        if (!$lockdownData) {
            return false;
        }

        // Log admin intervention
        Log::info('2FA Lockdown Manually Unlocked', [
            'identifier' => $this->sanitizeIdentifier($identifier),
            'admin_user_id' => $adminUserId,
            'original_lockdown' => $lockdownData
        ]);

        Cache::forget($lockdownKey);

        // Also clear related attempt counters
        $patterns = ['2fa_attempts_totp_', '2fa_attempts_recovery_code_', '2fa_attempts_sms_'];
        foreach ($patterns as $pattern) {
            Cache::forget($pattern . $identifier);
        }

        return true;
    }

    /**
     * Detect coordinated 2FA attacks across multiple accounts
     */
    public function detectCoordinated2FAAttack(): array
    {
        $recentFailures = Cache::get('global_2fa_failures', []);
        $now = time();
        
        // Clean old entries (older than 1 hour)
        $recentFailures = array_filter($recentFailures, fn($failure) => ($now - $failure['timestamp']) < 3600);
        
        // Group by IP address
        $ipGroups = [];
        foreach ($recentFailures as $failure) {
            $ip = $failure['ip'];
            if (!isset($ipGroups[$ip])) {
                $ipGroups[$ip] = [];
            }
            $ipGroups[$ip][] = $failure;
        }

        $threats = [];
        foreach ($ipGroups as $ip => $failures) {
            $userCount = count(array_unique(array_column($failures, 'user_id')));
            $attemptCount = count($failures);
            
            // Detect suspicious patterns
            if ($userCount >= 3 && $attemptCount >= 10) {
                $threats[] = [
                    'type' => 'coordinated_2fa_attack',
                    'severity' => 'critical',
                    'ip_address' => $this->sanitizeIdentifier($ip),
                    'affected_users' => $userCount,
                    'total_attempts' => $attemptCount,
                    'time_window' => '1 hour',
                    'recommended_action' => 'immediate_ip_block'
                ];
            }
        }

        return $threats;
    }

    /**
     * Record global 2FA failure for attack detection
     */
    public function recordGlobal2FAFailure(string $ip, ?int $userId): void
    {
        $recentFailures = Cache::get('global_2fa_failures', []);
        
        $recentFailures[] = [
            'timestamp' => time(),
            'ip' => $ip,
            'user_id' => $userId
        ];
        
        // Keep only last 1000 failures
        if (count($recentFailures) > 1000) {
            $recentFailures = array_slice($recentFailures, -1000);
        }
        
        Cache::put('global_2fa_failures', $recentFailures, 3600); // 1 hour
    }

    /**
     * Generate 2FA security report
     */
    public function generate2FASecurityReport(int $days = 7): array
    {
        $startDate = now()->subDays($days);
        
        return [
            'period' => "{$days} days",
            'generated_at' => now()->toISOString(),
            'summary' => [
                'total_2fa_attempts' => $this->count2FAAttempts($startDate),
                'failed_2fa_attempts' => $this->count2FAAttempts($startDate, false),
                'success_rate' => $this->calculate2FASuccessRate($startDate),
                'unique_ips_with_failures' => $this->countUnique2FAFailureIPs($startDate),
                'locked_accounts' => $this->countLocked2FAAccounts(),
                'emergency_recovery_requests' => $this->countEmergencyRecoveryRequests($startDate)
            ],
            'threats' => $this->detectCoordinated2FAAttack(),
            'top_failing_ips' => $this->getTop2FAFailingIPs($startDate),
            'failure_patterns' => $this->analyze2FAFailurePatterns($startDate),
            'recommendations' => $this->get2FASecurityRecommendations()
        ];
    }

    /**
     * Helper methods for 2FA security reporting
     */
    private function count2FAAttempts(Carbon $since, ?bool $success = null): int
    {
        $query = AuditLog::where('action', 'LIKE', '2fa_%')
                         ->where('created_at', '>=', $since);
        
        if ($success !== null) {
            $pattern = $success ? '2fa_success%' : '2fa_failed%';
            $query->where('action', 'LIKE', $pattern);
        }
        
        return $query->count();
    }

    private function calculate2FASuccessRate(Carbon $since): float
    {
        $total = $this->count2FAAttempts($since);
        $successful = $this->count2FAAttempts($since, true);
        
        return $total > 0 ? round(($successful / $total) * 100, 2) : 0;
    }

    private function countUnique2FAFailureIPs(Carbon $since): int
    {
        return AuditLog::where('action', 'LIKE', '2fa_failed%')
                      ->where('created_at', '>=', $since)
                      ->distinct('ip_address')
                      ->count();
    }

    private function countLocked2FAAccounts(): int
    {
        // Count cache entries matching 2fa_lockdown pattern
        // This would need cache implementation specific logic
        return 0; // Placeholder
    }

    private function countEmergencyRecoveryRequests(Carbon $since): int
    {
        return AuditLog::where('action', 'emergency_2fa_recovery_requested')
                      ->where('created_at', '>=', $since)
                      ->count();
    }

    private function getTop2FAFailingIPs(Carbon $since, int $limit = 10): array
    {
        return AuditLog::where('action', 'LIKE', '2fa_failed%')
                      ->where('created_at', '>=', $since)
                      ->select('ip_address')
                      ->selectRaw('COUNT(*) as failure_count')
                      ->groupBy('ip_address')
                      ->orderBy('failure_count', 'desc')
                      ->limit($limit)
                      ->get()
                      ->map(function ($item) {
                          return [
                              'ip' => $this->sanitizeIdentifier($item->ip_address),
                              'failures' => $item->failure_count
                          ];
                      })
                      ->toArray();
    }

    private function analyze2FAFailurePatterns(Carbon $since): array
    {
        // Analyze time-based patterns
        $hourlyFailures = AuditLog::where('action', 'LIKE', '2fa_failed%')
                                ->where('created_at', '>=', $since)
                                ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
                                ->groupBy('hour')
                                ->get()
                                ->keyBy('hour')
                                ->map(fn($item) => $item->count)
                                ->toArray();

        return [
            'peak_failure_hours' => $this->getPeakHours($hourlyFailures),
            'failure_distribution' => $hourlyFailures,
            'weekend_vs_weekday' => $this->getWeekendWeekdayComparison($since)
        ];
    }

    private function getPeakHours(array $hourlyData): array
    {
        arsort($hourlyData);
        return array_slice($hourlyData, 0, 3, true);
    }

    private function getWeekendWeekdayComparison(Carbon $since): array
    {
        $weekdayFailures = AuditLog::where('action', 'LIKE', '2fa_failed%')
                                 ->where('created_at', '>=', $since)
                                 ->whereRaw('WEEKDAY(created_at) < 5')
                                 ->count();

        $weekendFailures = AuditLog::where('action', 'LIKE', '2fa_failed%')
                                 ->where('created_at', '>=', $since)
                                 ->whereRaw('WEEKDAY(created_at) >= 5')
                                 ->count();

        return [
            'weekday_failures' => $weekdayFailures,
            'weekend_failures' => $weekendFailures,
            'weekend_ratio' => $weekdayFailures > 0 ? round($weekendFailures / $weekdayFailures, 2) : 0
        ];
    }

    private function get2FASecurityRecommendations(): array
    {
        $recommendations = [];
        
        // Check recent coordinated attacks
        $threats = $this->detectCoordinated2FAAttack();
        if (!empty($threats)) {
            $recommendations[] = [
                'type' => 'critical',
                'title' => 'Coordinated 2FA Attack Detected',
                'description' => 'Multiple IP addresses are attempting to breach 2FA security.',
                'action' => 'Implement immediate IP blocking and review access logs'
            ];
        }

        // Check 2FA success rate
        $successRate = $this->calculate2FASuccessRate(now()->subDays(7));
        if ($successRate < 90) {
            $recommendations[] = [
                'type' => 'warning',
                'title' => 'Low 2FA Success Rate',
                'description' => "2FA success rate is {$successRate}% over the last 7 days.",
                'action' => 'Review user training and 2FA implementation'
            ];
        }

        // Check for locked accounts
        $lockedCount = $this->countLocked2FAAccounts();
        if ($lockedCount > 5) {
            $recommendations[] = [
                'type' => 'alert',
                'title' => 'Multiple Locked 2FA Accounts',
                'description' => "{$lockedCount} accounts are currently locked due to 2FA failures.",
                'action' => 'Review lockdown reasons and consider user support'
            ];
        }

        return $recommendations;
    }

    /**
     * Sanitize identifier for logging (privacy protection)
     */
    private function sanitizeIdentifier(string $identifier): string
    {
        if (filter_var($identifier, FILTER_VALIDATE_IP)) {
            $parts = explode('.', $identifier);
            return $parts[0] . '.' . $parts[1] . '.***.**';
        }
        
        return substr($identifier, 0, 4) . '***';
    }

    /**
     * Notify administrators of security lockdown
     */
    private function notifyAdminsOfSecurityLockdown(array $lockdownData): void
    {
        // This would integrate with your notification system
        // For now, just log the critical event
        Log::critical('URGENT: 2FA Security Lockdown Triggered - Admin Intervention Required', $lockdownData);
    }
}