<?php

namespace App\Services;

use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SecurityLogger
{
    /**
     * Log 2FA verification attempt
     */
    public function log2FAAttempt(
        ?User $user, 
        string $type, 
        bool $success, 
        Request $request, 
        array $context = []
    ): void {
        $logData = [
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'attempt_type' => $type,
            'success' => $success,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_id' => session()->getId(),
            'timestamp' => now()->toISOString(),
            'context' => $context
        ];

        $level = $success ? 'info' : 'warning';
        $message = $success 
            ? "2FA {$type} verification successful" 
            : "2FA {$type} verification failed";

        Log::channel('2fa')->{$level}($message, $logData);

        // Also log to security channel for failed attempts
        if (!$success) {
            Log::channel('security')->warning("Failed 2FA Verification", $logData);
        }

        // Create audit log entry
        if ($user) {
            AuditLog::create([
                'user_id' => $user->id,
                'action' => $success ? "2fa_success_{$type}" : "2fa_failed_{$type}",
                'auditable_type' => 'App\Models\User',
                'auditable_id' => $user->id,
                'old_values' => [],
                'new_values' => $logData,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'risk_level' => $success ? 'low' : 'medium'
            ]);
        }
    }

    /**
     * Log 2FA setup events
     */
    public function log2FASetup(User $user, string $action, Request $request, array $context = []): void
    {
        $logData = [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'action' => $action,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_id' => session()->getId(),
            'timestamp' => now()->toISOString(),
            'context' => $context
        ];

        Log::channel('2fa')->info("2FA Setup: {$action}", $logData);
        Log::channel('security')->info("2FA Configuration Change", $logData);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => "2fa_setup_{$action}",
            'auditable_type' => 'App\Models\User',
            'auditable_id' => $user->id,
            'old_values' => [],
            'new_values' => $logData,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'risk_level' => 'low'
        ]);
    }

    /**
     * Log security lockdown events
     */
    public function logSecurityLockdown(
        string $identifier, 
        string $type, 
        string $reason, 
        Request $request,
        array $context = []
    ): void {
        $logData = [
            'identifier' => $this->sanitizeIdentifier($identifier),
            'lockdown_type' => $type,
            'reason' => $reason,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_id' => session()->getId(),
            'timestamp' => now()->toISOString(),
            'context' => $context
        ];

        Log::channel('security')->critical("Security Lockdown Triggered", $logData);
        Log::channel('2fa')->critical("2FA Security Lockdown", $logData);

        // Create critical audit log
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'security_lockdown_triggered',
            'auditable_type' => 'App\Models\User',
            'auditable_id' => auth()->id(),
            'old_values' => [],
            'new_values' => $logData,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'risk_level' => 'critical'
        ]);
    }

    /**
     * Log admin security interventions
     */
    public function logAdminIntervention(
        int $adminUserId, 
        string $action, 
        string $target, 
        Request $request,
        array $context = []
    ): void {
        $admin = User::find($adminUserId);
        
        $logData = [
            'admin_user_id' => $adminUserId,
            'admin_email' => $admin?->email,
            'intervention_action' => $action,
            'target' => $this->sanitizeIdentifier($target),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_id' => session()->getId(),
            'timestamp' => now()->toISOString(),
            'context' => $context
        ];

        Log::channel('security')->info("Admin Security Intervention", $logData);
        Log::channel('audit')->info("Admin Action: {$action}", $logData);

        AuditLog::create([
            'user_id' => $adminUserId,
            'action' => "admin_intervention_{$action}",
            'auditable_type' => 'App\Models\User',
            'auditable_id' => $adminUserId,
            'old_values' => [],
            'new_values' => $logData,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'risk_level' => 'medium'
        ]);
    }

    /**
     * Log emergency recovery requests
     */
    public function logEmergencyRecovery(
        User $user, 
        array $recoveryData, 
        Request $request
    ): void {
        $logData = [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'recovery_reason' => $recoveryData['reason'] ?? 'not_specified',
            'contact_method' => $recoveryData['contact_method'] ?? 'not_specified',
            'emergency_contact' => $this->sanitizeContactInfo($recoveryData['emergency_contact'] ?? ''),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_id' => session()->getId(),
            'timestamp' => now()->toISOString(),
            'status' => 'pending'
        ];

        Log::channel('security')->warning("Emergency 2FA Recovery Requested", $logData);
        Log::channel('2fa')->warning("Emergency Recovery Request", $logData);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'emergency_2fa_recovery_requested',
            'auditable_type' => 'App\Models\User',
            'auditable_id' => $user->id,
            'old_values' => [],
            'new_values' => $logData,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'risk_level' => 'high'
        ]);
    }

    /**
     * Log coordinated attack detection
     */
    public function logCoordinatedAttack(array $attackData): void
    {
        $logData = [
            'attack_type' => $attackData['type'] ?? 'unknown',
            'severity' => $attackData['severity'] ?? 'medium',
            'affected_users' => $attackData['affected_users'] ?? 0,
            'total_attempts' => $attackData['total_attempts'] ?? 0,
            'source_ip' => $this->sanitizeIdentifier($attackData['ip_address'] ?? ''),
            'time_window' => $attackData['time_window'] ?? 'unknown',
            'recommended_action' => $attackData['recommended_action'] ?? 'review',
            'detected_at' => now()->toISOString()
        ];

        Log::channel('security')->critical("Coordinated Attack Detected", $logData);
        Log::channel('2fa')->critical("Mass Attack Pattern", $logData);

        // Create critical system audit log
        AuditLog::create([
            'user_id' => null, // System detected
            'action' => 'coordinated_attack_detected',
            'auditable_type' => 'System',
            'auditable_id' => null,
            'old_values' => [],
            'new_values' => $logData,
            'ip_address' => $attackData['ip_address'] ?? null,
            'user_agent' => 'System Detection',
            'risk_level' => 'critical'
        ]);
    }

    /**
     * Log rate limiting events
     */
    public function logRateLimit(
        string $ip, 
        string $action, 
        int $attempts, 
        int $limit,
        Request $request
    ): void {
        $logData = [
            'ip_address' => $this->sanitizeIdentifier($ip),
            'action' => $action,
            'current_attempts' => $attempts,
            'rate_limit' => $limit,
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString(),
            'status' => $attempts >= $limit ? 'blocked' : 'tracked'
        ];

        $level = $attempts >= $limit ? 'warning' : 'info';
        $message = $attempts >= $limit 
            ? "Rate limit exceeded for {$action}" 
            : "Rate limit tracking for {$action}";

        Log::channel('security')->{$level}($message, $logData);

        if ($attempts >= $limit) {
            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'rate_limit_exceeded',
                'auditable_type' => 'App\Models\User',
                'auditable_id' => auth()->id(),
                'old_values' => [],
                'new_values' => $logData,
                'ip_address' => $ip,
                'user_agent' => $request->userAgent(),
                'risk_level' => 'medium'
            ]);
        }
    }

    /**
     * Log device tracking events
     */
    public function logDeviceTracking(
        User $user, 
        string $action, 
        string $deviceFingerprint, 
        Request $request
    ): void {
        $logData = [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'action' => $action,
            'device_fingerprint' => substr($deviceFingerprint, 0, 16) . '...',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString()
        ];

        Log::channel('security')->info("Device Tracking: {$action}", $logData);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => "device_{$action}",
            'auditable_type' => 'App\Models\User',
            'auditable_id' => $user->id,
            'old_values' => [],
            'new_values' => $logData,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'risk_level' => $action === 'new_device_detected' ? 'medium' : 'low'
        ]);
    }

    /**
     * Log login security events
     */
    public function logLoginSecurity(
        ?User $user, 
        string $event, 
        bool $success, 
        Request $request,
        array $riskFactors = []
    ): void {
        $logData = [
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'event' => $event,
            'success' => $success,
            'risk_factors' => $riskFactors,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_id' => session()->getId(),
            'timestamp' => now()->toISOString()
        ];

        $level = $success ? 'info' : 'warning';
        $riskLevel = empty($riskFactors) ? 'low' : (count($riskFactors) > 2 ? 'high' : 'medium');

        Log::channel('security')->{$level}("Login Security: {$event}", $logData);

        if ($user) {
            AuditLog::create([
                'user_id' => $user->id,
                'action' => $success ? "login_success_{$event}" : "login_failed_{$event}",
                'auditable_type' => 'App\Models\User',
                'auditable_id' => $user->id,
                'old_values' => [],
                'new_values' => $logData,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'risk_level' => $riskLevel
            ]);
        }
    }

    /**
     * Log security configuration changes
     */
    public function logSecurityConfigChange(
        User $user, 
        string $setting, 
        $oldValue, 
        $newValue, 
        Request $request
    ): void {
        $logData = [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'setting' => $setting,
            'old_value' => $this->sanitizeValue($oldValue),
            'new_value' => $this->sanitizeValue($newValue),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_id' => session()->getId(),
            'timestamp' => now()->toISOString()
        ];

        Log::channel('security')->info("Security Configuration Change", $logData);
        Log::channel('audit')->info("Config Change: {$setting}", $logData);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => "security_config_changed_{$setting}",
            'auditable_type' => 'App\Models\User',
            'auditable_id' => $user->id,
            'old_values' => [$setting => $oldValue],
            'new_values' => [$setting => $newValue],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'risk_level' => 'medium'
        ]);
    }

    /**
     * Generate daily security summary
     */
    public function generateDailySummary(Carbon $date = null): array
    {
        $date = $date ?? now();
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        $summary = [
            'date' => $date->format('Y-m-d'),
            'generated_at' => now()->toISOString(),
            'login_attempts' => [
                'successful' => $this->countAuditEvents($startOfDay, $endOfDay, 'login_success_%'),
                'failed' => $this->countAuditEvents($startOfDay, $endOfDay, 'login_failed_%'),
            ],
            '2fa_attempts' => [
                'successful' => $this->countAuditEvents($startOfDay, $endOfDay, '2fa_success_%'),
                'failed' => $this->countAuditEvents($startOfDay, $endOfDay, '2fa_failed_%'),
            ],
            'security_events' => [
                'lockdowns' => $this->countAuditEvents($startOfDay, $endOfDay, 'security_lockdown_%'),
                'rate_limits' => $this->countAuditEvents($startOfDay, $endOfDay, 'rate_limit_%'),
                'admin_interventions' => $this->countAuditEvents($startOfDay, $endOfDay, 'admin_intervention_%'),
                'emergency_recoveries' => $this->countAuditEvents($startOfDay, $endOfDay, 'emergency_%'),
            ],
            'risk_distribution' => [
                'critical' => $this->countRiskLevel($startOfDay, $endOfDay, 'critical'),
                'high' => $this->countRiskLevel($startOfDay, $endOfDay, 'high'),
                'medium' => $this->countRiskLevel($startOfDay, $endOfDay, 'medium'),
                'low' => $this->countRiskLevel($startOfDay, $endOfDay, 'low'),
            ]
        ];

        Log::channel('security')->info("Daily Security Summary Generated", $summary);

        return $summary;
    }

    /**
     * Helper methods
     */
    private function sanitizeIdentifier(string $identifier): string
    {
        if (filter_var($identifier, FILTER_VALIDATE_IP)) {
            $parts = explode('.', $identifier);
            if (count($parts) === 4) {
                return $parts[0] . '.' . $parts[1] . '.***.**';
            }
        }
        
        return strlen($identifier) > 4 ? substr($identifier, 0, 4) . '***' : '***';
    }

    private function sanitizeContactInfo(string $contact): string
    {
        if (filter_var($contact, FILTER_VALIDATE_EMAIL)) {
            $parts = explode('@', $contact);
            return substr($parts[0], 0, 2) . '***@' . $parts[1];
        }
        
        return strlen($contact) > 4 ? substr($contact, 0, 4) . '***' : '***';
    }

    private function sanitizeValue($value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        
        if (is_string($value) && strlen($value) > 20) {
            return substr($value, 0, 20) . '...';
        }
        
        return (string) $value;
    }

    private function countAuditEvents(Carbon $start, Carbon $end, string $actionPattern): int
    {
        return AuditLog::whereBetween('created_at', [$start, $end])
                      ->where('action', 'LIKE', $actionPattern)
                      ->count();
    }

    private function countRiskLevel(Carbon $start, Carbon $end, string $level): int
    {
        return AuditLog::whereBetween('created_at', [$start, $end])
                      ->where('risk_level', $level)
                      ->count();
    }
}