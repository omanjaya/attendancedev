<?php

namespace App\Services\Security;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\TwoFactorService;

class SecurityReportService
{
    public function __construct(
        private TwoFactorService $twoFactorService
    ) {}

    /**
     * Get recent security events
     */
    public function getRecentSecurityEvents(): array
    {
        $events = AuditLog::where('created_at', '>=', now()->subDays(7))
            ->whereIn('action', [
                'login_failed',
                'suspicious_activity_multiple_failed_logins',
                'suspicious_activity_new_device',
                'user_locked',
                'admin_disable_2fa',
            ])
            ->with('user:id,name,email')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return $events
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'timestamp' => $event->created_at->format('Y-m-d H:i:s'),
                    'action' => $event->action,
                    'user' => $event->user?->name ?? 'Unknown',
                    'email' => $event->user?->email ?? 'Unknown',
                    'ip_address' => $event->ip_address,
                    'risk_level' => $event->risk_level,
                    'details' => $event->new_values,
                ];
            })
            ->toArray();
    }

    /**
     * Get current threat levels
     */
    public function getThreatLevels(): array
    {
        $today = now()->startOfDay();

        $failedLogins = AuditLog::where('action', 'login_failed')
            ->where('created_at', '>=', $today)
            ->count();

        $suspiciousActivity = AuditLog::where('action', 'LIKE', 'suspicious_activity_%')
            ->where('created_at', '>=', $today)
            ->count();

        $lockedUsers = User::locked()->count();

        // Calculate threat level (0-10)
        $threatLevel = min(10, floor($failedLogins / 10 + $suspiciousActivity / 5 + $lockedUsers / 2));

        $threatDescription = match (true) {
            $threatLevel >= 8 => 'Critical',
            $threatLevel >= 6 => 'High',
            $threatLevel >= 4 => 'Medium',
            $threatLevel >= 2 => 'Low',
            default => 'Minimal',
        };

        return [
            'level' => $threatLevel,
            'description' => $threatDescription,
            'failed_logins' => $failedLogins,
            'suspicious_activity' => $suspiciousActivity,
            'locked_users' => $lockedUsers,
        ];
    }

    /**
     * Get security recommendations
     */
    public function getSecurityRecommendations(): array
    {
        $recommendations = [];

        // Check 2FA compliance
        $twoFactorStats = $this->twoFactorService->getStatistics();
        if ($twoFactorStats['compliance_rate'] < 80) {
            $recommendations[] = [
                'type' => 'warning',
                'title' => 'Low 2FA Compliance',
                'description' => "Only {$twoFactorStats['compliance_rate']}% of required users have 2FA enabled.",
                'action' => 'Enforce 2FA for all administrative users',
                'priority' => 'high',
            ];
        }

        // Check failed logins
        $recentFailedLogins = AuditLog::where('action', 'login_failed')
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        if ($recentFailedLogins > 50) {
            $recommendations[] = [
                'type' => 'alert',
                'title' => 'High Failed Login Rate',
                'description' => "{$recentFailedLogins} failed login attempts in the last 7 days.",
                'action' => 'Review IP whitelist and consider additional rate limiting',
                'priority' => 'high',
            ];
        }

        // Check inactive accounts
        $inactiveUsers = User::where('last_login_at', '<', now()->subDays(90))
            ->where('is_active', true)
            ->count();

        if ($inactiveUsers > 10) {
            $recommendations[] = [
                'type' => 'info',
                'title' => 'Inactive User Accounts',
                'description' => "{$inactiveUsers} users haven't logged in for 90+ days.",
                'action' => 'Review and deactivate unused accounts',
                'priority' => 'medium',
            ];
        }

        // Check password changes
        $oldPasswords = User::where('password_changed_at', '<', now()->subDays(90))
            ->orWhereNull('password_changed_at')
            ->count();

        if ($oldPasswords > 5) {
            $recommendations[] = [
                'type' => 'warning',
                'title' => 'Old Passwords Detected',
                'description' => "{$oldPasswords} users have passwords older than 90 days.",
                'action' => 'Enforce password rotation policy',
                'priority' => 'medium',
            ];
        }

        return $recommendations;
    }

    /**
     * Generate PDF report
     */
    public function generatePdfReport(array $report)
    {
        // This would use a PDF generation library like dompdf
        // For now, return JSON
        return response()->json($report);
    }

    /**
     * Generate CSV report
     */
    public function generateCsvReport(array $report)
    {
        $csv = "Security Report - {$report['period']}\n";
        $csv .= "Generated: {$report['generated_at']}\n\n";

        $csv .= "Metrics:\n";
        foreach ($report['metrics'] as $key => $value) {
            $csv .= ucfirst(str_replace('_', ' ', $key)) . ": {$value}\n";
        }

        $csv .= "\nFailed Login Trends:\n";
        $csv .= "Date,Count\n";
        foreach ($report['failed_login_trends'] as $trend) {
            $csv .= "{$trend['date']},{$trend['count']}\n";
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="security-report.csv"');
    }
}
