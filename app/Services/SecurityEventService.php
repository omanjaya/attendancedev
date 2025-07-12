<?php

namespace App\Services;

use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SecurityEventService
{
    /**
     * Log 2FA-related security events with enhanced context.
     */
    public function log2FAEvent(string $event, User $user, Request $request, array $additionalData = []): void
    {
        $eventData = array_merge([
            'user_id' => $user->id,
            'user_email' => $user->email,
            'event_type' => $event,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_id' => session()->getId(),
            'timestamp' => now()->toISOString(),
            'device_fingerprint' => $this->generateDeviceFingerprint($request),
            'geolocation' => $this->getApproximateLocation($request->ip()),
            'security_context' => $this->buildSecurityContext($user, $request)
        ], $additionalData);

        // Log to security channel
        Log::channel('security')->info("2FA Event: {$event}", $eventData);

        // Create audit log entry
        AuditLog::create([
            'user_id' => $user->id,
            'action' => "2fa_{$event}",
            'auditable_type' => 'App\\Models\\User',
            'auditable_id' => $user->id,
            'old_values' => [],
            'new_values' => $eventData,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'risk_level' => $this->calculateEventRiskLevel($event, $eventData),
            'metadata' => json_encode($eventData)
        ]);

        // Track user activity patterns
        $this->trackUserActivity($user, $request, $event);

        // Check for suspicious patterns and trigger alerts if needed
        $this->analyzeSuspiciousPatterns($user, $event, $eventData);
    }

    /**
     * Log authentication events.
     */
    public function logAuthEvent(string $event, ?User $user, Request $request, array $additionalData = []): void
    {
        $eventData = array_merge([
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'event_type' => $event,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_id' => session()->getId(),
            'timestamp' => now()->toISOString(),
            'device_fingerprint' => $this->generateDeviceFingerprint($request),
            'geolocation' => $this->getApproximateLocation($request->ip()),
            'login_method' => $this->detectLoginMethod($request)
        ], $additionalData);

        // Determine log level based on event
        $logLevel = $this->getLogLevel($event);
        
        Log::channel('security')->{$logLevel}("Auth Event: {$event}", $eventData);

        // Create audit log for trackable events
        if ($user || $event === 'login_failed') {
            AuditLog::create([
                'user_id' => $user?->id,
                'action' => $event,
                'auditable_type' => $user ? 'App\\Models\\User' : null,
                'auditable_id' => $user?->id,
                'old_values' => [],
                'new_values' => $eventData,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'risk_level' => $this->calculateEventRiskLevel($event, $eventData),
                'metadata' => json_encode($eventData)
            ]);
        }

        // Handle specific auth events
        $this->handleSpecificAuthEvent($event, $user, $request, $eventData);
    }

    /**
     * Log security violations and potential attacks.
     */
    public function logSecurityViolation(string $violationType, Request $request, array $details = []): void
    {
        $eventData = array_merge([
            'violation_type' => $violationType,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'timestamp' => now()->toISOString(),
            'session_id' => session()->getId(),
            'user_id' => auth()->id(),
            'device_fingerprint' => $this->generateDeviceFingerprint($request),
            'request_headers' => $this->sanitizeHeaders($request->headers->all()),
            'severity' => $this->calculateViolationSeverity($violationType, $details)
        ], $details);

        // Log with appropriate level
        $logLevel = $eventData['severity'] === 'critical' ? 'critical' : 'warning';
        Log::channel('security')->{$logLevel}("Security Violation: {$violationType}", $eventData);

        // Create audit log
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => "security_violation_{$violationType}",
            'auditable_type' => null,
            'auditable_id' => null,
            'old_values' => [],
            'new_values' => $eventData,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'risk_level' => $eventData['severity'],
            'metadata' => json_encode($eventData)
        ]);

        // Track violation patterns
        $this->trackViolationPatterns($request->ip(), $violationType);

        // Trigger immediate response for critical violations
        if ($eventData['severity'] === 'critical') {
            $this->handleCriticalViolation($violationType, $eventData);
        }
    }

    /**
     * Log admin actions with enhanced tracking.
     */
    public function logAdminAction(string $action, User $admin, array $targetData = [], array $changes = []): void
    {
        $eventData = [
            'admin_id' => $admin->id,
            'admin_email' => $admin->email,
            'admin_roles' => $admin->getRoleNames()->toArray(),
            'action' => $action,
            'target_data' => $targetData,
            'changes' => $changes,
            'timestamp' => now()->toISOString(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId(),
            'request_id' => uniqid()
        ];

        Log::channel('security')->info("Admin Action: {$action}", $eventData);

        // Create detailed audit log
        AuditLog::create([
            'user_id' => $admin->id,
            'action' => "admin_{$action}",
            'auditable_type' => $targetData['type'] ?? null,
            'auditable_id' => $targetData['id'] ?? null,
            'old_values' => $changes['old'] ?? [],
            'new_values' => $changes['new'] ?? [],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'risk_level' => $this->calculateAdminActionRisk($action),
            'metadata' => json_encode($eventData)
        ]);

        // Track admin activity patterns
        $this->trackAdminActivity($admin, $action);
    }

    /**
     * Log performance anomalies and system events.
     */
    public function logSystemEvent(string $event, array $metrics = [], string $severity = 'info'): void
    {
        $eventData = array_merge([
            'event_type' => $event,
            'timestamp' => now()->toISOString(),
            'server_info' => [
                'memory_usage' => memory_get_usage(true),
                'peak_memory' => memory_get_peak_usage(true),
                'execution_time' => microtime(true) - LARAVEL_START
            ]
        ], $metrics);

        Log::channel('security')->{$severity}("System Event: {$event}", $eventData);

        // Create audit log for significant system events
        if (in_array($severity, ['warning', 'error', 'critical'])) {
            AuditLog::create([
                'user_id' => null,
                'action' => "system_{$event}",
                'auditable_type' => null,
                'auditable_id' => null,
                'old_values' => [],
                'new_values' => $eventData,
                'ip_address' => request()->ip() ?? 'system',
                'user_agent' => 'system',
                'risk_level' => $severity === 'critical' ? 'critical' : 'medium',
                'metadata' => json_encode($eventData)
            ]);
        }
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
            $request->header('Accept-Encoding'),
            $request->ip()
        ];

        return hash('sha256', implode('|', array_filter($components)));
    }

    /**
     * Get approximate location from IP (privacy-aware).
     */
    private function getApproximateLocation(string $ip): ?array
    {
        // Return general region only for privacy
        // This would integrate with a geolocation service
        return [
            'country' => 'Unknown',
            'region' => 'Unknown',
            'timezone' => date_default_timezone_get()
        ];
    }

    /**
     * Build security context for the user and request.
     */
    private function buildSecurityContext(User $user, Request $request): array
    {
        return [
            'user_roles' => $user->getRoleNames()->toArray(),
            'user_permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
            'account_age_days' => $user->created_at->diffInDays(now()),
            '2fa_enabled' => $user->two_factor_enabled,
            'last_login' => $user->last_login_at?->toISOString(),
            'login_count' => $this->getUserLoginCount($user),
            'is_new_device' => $this->isNewDevice($user, $request),
            'session_age_minutes' => $this->getSessionAge()
        ];
    }

    /**
     * Calculate risk level for security events.
     */
    private function calculateEventRiskLevel(string $event, array $eventData): string
    {
        $riskScores = [
            'login_failed' => 2,
            '2fa_verification_failed' => 3,
            '2fa_lockdown_triggered' => 5,
            '2fa_emergency_recovery' => 4,
            'password_changed' => 2,
            'account_locked' => 4,
            'suspicious_activity' => 3,
            'admin_action' => 2
        ];

        $baseScore = $riskScores[$event] ?? 1;

        // Adjust based on context
        if (isset($eventData['is_new_device']) && $eventData['is_new_device']) {
            $baseScore += 1;
        }

        if (isset($eventData['unusual_time']) && $eventData['unusual_time']) {
            $baseScore += 1;
        }

        return match (true) {
            $baseScore >= 5 => 'critical',
            $baseScore >= 3 => 'high',
            $baseScore >= 2 => 'medium',
            default => 'low'
        };
    }

    /**
     * Track user activity patterns for anomaly detection.
     */
    private function trackUserActivity(User $user, Request $request, string $event): void
    {
        $activityKey = "user_activity_{$user->id}";
        $activities = Cache::get($activityKey, []);

        $activities[] = [
            'event' => $event,
            'timestamp' => time(),
            'ip' => $request->ip(),
            'device' => $this->generateDeviceFingerprint($request)
        ];

        // Keep only last 100 activities
        if (count($activities) > 100) {
            $activities = array_slice($activities, -100);
        }

        Cache::put($activityKey, $activities, 86400); // 24 hours
    }

    /**
     * Analyze patterns for suspicious activity.
     */
    private function analyzeSuspiciousPatterns(User $user, string $event, array $eventData): void
    {
        // Check for rapid successive failures
        if (str_contains($event, 'failed') || str_contains($event, 'violation')) {
            $this->checkRapidFailures($user, $event);
        }

        // Check for coordinated attacks
        if (str_contains($event, '2fa')) {
            $this->checkCoordinatedAttacks($eventData['ip_address'], $user->id);
        }

        // Check for unusual access patterns
        $this->checkUnusualPatterns($user, $eventData);
    }

    /**
     * Handle specific authentication events.
     */
    private function handleSpecificAuthEvent(string $event, ?User $user, Request $request, array $eventData): void
    {
        switch ($event) {
            case 'login_success':
                if ($user) {
                    $this->recordSuccessfulLogin($user, $request);
                }
                break;

            case 'login_failed':
                $this->recordFailedLogin($request, $eventData);
                break;

            case 'logout':
                if ($user) {
                    $this->recordLogout($user);
                }
                break;
        }
    }

    /**
     * Calculate log level based on event type.
     */
    private function getLogLevel(string $event): string
    {
        $criticalEvents = ['account_locked', 'security_violation', 'coordinated_attack'];
        $warningEvents = ['login_failed', '2fa_failed', 'unusual_activity'];

        if (in_array($event, $criticalEvents)) {
            return 'critical';
        }

        if (in_array($event, $warningEvents)) {
            return 'warning';
        }

        return 'info';
    }

    /**
     * Detect login method used.
     */
    private function detectLoginMethod(Request $request): string
    {
        if ($request->is('api/*')) {
            return 'api';
        }

        if ($request->ajax()) {
            return 'ajax';
        }

        return 'web';
    }

    /**
     * Calculate violation severity.
     */
    private function calculateViolationSeverity(string $type, array $details): string
    {
        $criticalViolations = [
            'sql_injection_attempt',
            'xss_attempt',
            'privilege_escalation',
            'brute_force_attack',
            'coordinated_attack'
        ];

        if (in_array($type, $criticalViolations)) {
            return 'critical';
        }

        if (isset($details['repeat_offender']) && $details['repeat_offender']) {
            return 'high';
        }

        return 'medium';
    }

    /**
     * Sanitize headers for logging (remove sensitive data).
     */
    private function sanitizeHeaders(array $headers): array
    {
        $sensitiveHeaders = ['authorization', 'cookie', 'x-api-key', 'x-auth-token'];
        
        foreach ($sensitiveHeaders as $header) {
            if (isset($headers[$header])) {
                $headers[$header] = ['***REDACTED***'];
            }
        }

        return $headers;
    }

    /**
     * Track violation patterns by IP.
     */
    private function trackViolationPatterns(string $ip, string $violationType): void
    {
        $violationKey = "violations_{$ip}";
        $violations = Cache::get($violationKey, []);

        $violations[] = [
            'type' => $violationType,
            'timestamp' => time()
        ];

        // Keep only last 24 hours of violations
        $oneDayAgo = time() - 86400;
        $violations = array_filter($violations, fn($v) => $v['timestamp'] > $oneDayAgo);

        Cache::put($violationKey, $violations, 86400);

        // Check if IP should be flagged for blocking
        if (count($violations) >= 10) {
            $this->flagIPForBlocking($ip, count($violations));
        }
    }

    /**
     * Handle critical security violations.
     */
    private function handleCriticalViolation(string $type, array $data): void
    {
        // Log critical alert
        Log::channel('security')->critical("CRITICAL SECURITY VIOLATION: {$type}", $data);

        // Cache critical event for immediate admin notification
        Cache::put("critical_violation_" . time(), [
            'type' => $type,
            'data' => $data,
            'timestamp' => now()->toISOString()
        ], 3600); // 1 hour

        // You could integrate with notification services here
        // For example: Slack, email, SMS alerts to administrators
    }

    /**
     * Calculate risk level for admin actions.
     */
    private function calculateAdminActionRisk(string $action): string
    {
        $highRiskActions = [
            'user_delete',
            'role_change',
            'permission_grant',
            'system_backup',
            'data_export',
            '2fa_unlock'
        ];

        return in_array($action, $highRiskActions) ? 'high' : 'medium';
    }

    /**
     * Track admin activity patterns.
     */
    private function trackAdminActivity(User $admin, string $action): void
    {
        $activityKey = "admin_activity_{$admin->id}";
        $activities = Cache::get($activityKey, []);

        $activities[] = [
            'action' => $action,
            'timestamp' => time()
        ];

        // Keep only last 100 admin actions
        if (count($activities) > 100) {
            $activities = array_slice($activities, -100);
        }

        Cache::put($activityKey, $activities, 86400 * 7); // 7 days
    }

    /**
     * Helper methods for activity tracking.
     */
    private function getUserLoginCount(User $user): int
    {
        return Cache::get("user_login_count_{$user->id}", 0);
    }

    private function isNewDevice(User $user, Request $request): bool
    {
        $deviceFingerprint = $this->generateDeviceFingerprint($request);
        $knownDevices = Cache::get("user_devices_{$user->id}", []);
        
        return !in_array($deviceFingerprint, $knownDevices);
    }

    private function getSessionAge(): int
    {
        $sessionStart = session('started_at', time());
        return (time() - $sessionStart) / 60; // minutes
    }

    private function checkRapidFailures(User $user, string $event): void
    {
        $failureKey = "rapid_failures_{$user->id}";
        $failures = Cache::get($failureKey, []);
        
        $failures[] = time();
        
        // Keep only last 5 minutes of failures
        $fiveMinutesAgo = time() - 300;
        $failures = array_filter($failures, fn($timestamp) => $timestamp > $fiveMinutesAgo);
        
        Cache::put($failureKey, $failures, 300);
        
        if (count($failures) >= 5) {
            $this->logSecurityViolation('rapid_failures', request(), [
                'user_id' => $user->id,
                'failure_count' => count($failures),
                'event_type' => $event
            ]);
        }
    }

    private function checkCoordinatedAttacks(string $ip, int $userId): void
    {
        $attackKey = "coordinated_attack_{$ip}";
        $attempts = Cache::get($attackKey, []);
        
        $attempts[] = ['user_id' => $userId, 'timestamp' => time()];
        
        // Keep only last hour
        $oneHourAgo = time() - 3600;
        $attempts = array_filter($attempts, fn($attempt) => $attempt['timestamp'] > $oneHourAgo);
        
        Cache::put($attackKey, $attempts, 3600);
        
        $uniqueUsers = count(array_unique(array_column($attempts, 'user_id')));
        
        if ($uniqueUsers >= 3 && count($attempts) >= 10) {
            $this->logSecurityViolation('coordinated_attack', request(), [
                'ip_address' => $ip,
                'affected_users' => $uniqueUsers,
                'total_attempts' => count($attempts)
            ]);
        }
    }

    private function checkUnusualPatterns(User $user, array $eventData): void
    {
        // Check for unusual login times
        $currentHour = now()->hour;
        $userHours = Cache::get("user_hours_{$user->id}", []);
        
        if (!empty($userHours)) {
            $averageHour = array_sum($userHours) / count($userHours);
            if (abs($currentHour - $averageHour) > 6) {
                $eventData['unusual_time'] = true;
            }
        }
        
        $userHours[] = $currentHour;
        if (count($userHours) > 30) {
            $userHours = array_slice($userHours, -30);
        }
        
        Cache::put("user_hours_{$user->id}", $userHours, 86400 * 30);
    }

    private function recordSuccessfulLogin(User $user, Request $request): void
    {
        $user->update(['last_login_at' => now()]);
        
        $loginCount = Cache::get("user_login_count_{$user->id}", 0) + 1;
        Cache::put("user_login_count_{$user->id}", $loginCount, 86400 * 30);
        
        // Remember device
        $deviceFingerprint = $this->generateDeviceFingerprint($request);
        $knownDevices = Cache::get("user_devices_{$user->id}", []);
        
        if (!in_array($deviceFingerprint, $knownDevices)) {
            $knownDevices[] = $deviceFingerprint;
            if (count($knownDevices) > 5) {
                $knownDevices = array_slice($knownDevices, -5);
            }
            Cache::put("user_devices_{$user->id}", $knownDevices, 86400 * 30);
        }
    }

    private function recordFailedLogin(Request $request, array $eventData): void
    {
        $ip = $request->ip();
        $failedKey = "failed_login_attempts_{$ip}";
        $attempts = Cache::get($failedKey, 0) + 1;
        
        Cache::put($failedKey, $attempts, 900); // 15 minutes
        
        if ($attempts >= 5) {
            $this->logSecurityViolation('brute_force_login', $request, [
                'attempt_count' => $attempts,
                'event_data' => $eventData
            ]);
        }
    }

    private function recordLogout(User $user): void
    {
        // Clear session-specific tracking
        session()->forget('started_at');
    }

    private function flagIPForBlocking(string $ip, int $violationCount): void
    {
        Cache::put("flagged_ip_{$ip}", [
            'flagged_at' => now()->toISOString(),
            'violation_count' => $violationCount,
            'auto_flagged' => true
        ], 86400); // 24 hours

        Log::channel('security')->critical("IP Flagged for Blocking", [
            'ip_address' => $ip,
            'violation_count' => $violationCount,
            'action_required' => 'Review and consider blocking this IP'
        ]);
    }
}