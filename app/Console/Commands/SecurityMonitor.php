<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\AuditLog;
use App\Services\SecurityService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SecurityMonitor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:monitor 
                           {--threshold=7 : Suspicious activity threshold}
                           {--period=1 : Time period in hours to check}
                           {--notify : Send notifications for high-risk events}
                           {--report : Generate monitoring report}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor system for suspicious security activities';

    private SecurityService $securityService;

    public function __construct(SecurityService $securityService)
    {
        parent::__construct();
        $this->securityService = $securityService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $threshold = (int) $this->option('threshold');
        $periodHours = (int) $this->option('period');
        $notify = $this->option('notify');
        $generateReport = $this->option('report');

        $this->info("🔍 Security Monitoring - Threshold: {$threshold}, Period: {$periodHours}h");
        $this->newLine();

        $timeframe = Carbon::now()->subHours($periodHours);
        $alerts = [];

        // Monitor failed logins
        $failedLogins = $this->monitorFailedLogins($timeframe, $threshold);
        if (!empty($failedLogins)) {
            $alerts = array_merge($alerts, $failedLogins);
        }

        // Monitor suspicious activities
        $suspiciousActivities = $this->monitorSuspiciousActivities($timeframe, $threshold);
        if (!empty($suspiciousActivities)) {
            $alerts = array_merge($alerts, $suspiciousActivities);
        }

        // Monitor account lockouts
        $accountLockouts = $this->monitorAccountLockouts($timeframe);
        if (!empty($accountLockouts)) {
            $alerts = array_merge($alerts, $accountLockouts);
        }

        // Monitor permission changes
        $permissionChanges = $this->monitorPermissionChanges($timeframe);
        if (!empty($permissionChanges)) {
            $alerts = array_merge($alerts, $permissionChanges);
        }

        // Monitor unusual login patterns
        $unusualLogins = $this->monitorUnusualLogins($timeframe);
        if (!empty($unusualLogins)) {
            $alerts = array_merge($alerts, $unusualLogins);
        }

        // Display results
        $this->displayMonitoringResults($alerts, $periodHours);

        // Send notifications if requested and there are high-risk alerts
        if ($notify && !empty($alerts)) {
            $this->sendSecurityNotifications($alerts);
        }

        // Generate report if requested
        if ($generateReport) {
            $this->generateMonitoringReport($alerts, $periodHours);
        }

        return empty($alerts) ? 0 : 1;
    }

    /**
     * Monitor failed login attempts.
     */
    private function monitorFailedLogins(Carbon $timeframe, int $threshold): array
    {
        $this->line('🔐 Monitoring failed login attempts...');

        $failedLogins = AuditLog::where('action', 'login_failed')
            ->where('created_at', '>=', $timeframe)
            ->get()
            ->groupBy('ip_address');

        $alerts = [];

        foreach ($failedLogins as $ip => $attempts) {
            $count = $attempts->count();
            if ($count >= $threshold) {
                $alerts[] = [
                    'type' => 'failed_logins',
                    'severity' => $count >= $threshold * 2 ? 'critical' : 'high',
                    'ip_address' => $ip,
                    'count' => $count,
                    'message' => "High number of failed login attempts from IP: {$ip} ({$count} attempts)",
                    'first_attempt' => $attempts->first()->created_at,
                    'last_attempt' => $attempts->last()->created_at,
                ];

                $this->warn("  ⚠️  {$count} failed login attempts from {$ip}");
            }
        }

        if (empty($alerts)) {
            $this->info('  ✅ No suspicious failed login patterns detected');
        }

        return $alerts;
    }

    /**
     * Monitor suspicious activities.
     */
    private function monitorSuspiciousActivities(Carbon $timeframe, int $threshold): array
    {
        $this->line('🔍 Monitoring suspicious activities...');

        $suspiciousEvents = AuditLog::where('action', 'LIKE', 'suspicious_activity_%')
            ->where('created_at', '>=', $timeframe)
            ->where('risk_level', 'high')
            ->get();

        $alerts = [];

        if ($suspiciousEvents->count() >= $threshold) {
            $groupedEvents = $suspiciousEvents->groupBy('action');

            foreach ($groupedEvents as $action => $events) {
                $count = $events->count();
                if ($count >= 3) { // Multiple occurrences of same suspicious activity
                    $alerts[] = [
                        'type' => 'suspicious_activity',
                        'severity' => 'high',
                        'action' => $action,
                        'count' => $count,
                        'message' => "Multiple {$action} events detected ({$count} occurrences)",
                        'affected_users' => $events->pluck('user_id')->unique()->count(),
                    ];

                    $this->warn("  ⚠️  {$count} {$action} events detected");
                }
            }
        }

        if (empty($alerts)) {
            $this->info('  ✅ No high-risk suspicious activities detected');
        }

        return $alerts;
    }

    /**
     * Monitor account lockouts.
     */
    private function monitorAccountLockouts(Carbon $timeframe): array
    {
        $this->line('🔒 Monitoring account lockouts...');

        $lockoutEvents = AuditLog::where('action', 'user_locked')
            ->where('created_at', '>=', $timeframe)
            ->get();

        $alerts = [];

        if ($lockoutEvents->count() > 5) { // More than 5 lockouts in the period
            $alerts[] = [
                'type' => 'mass_lockouts',
                'severity' => 'high',
                'count' => $lockoutEvents->count(),
                'message' => "High number of account lockouts ({$lockoutEvents->count()} accounts)",
                'affected_users' => $lockoutEvents->pluck('auditable_id')->unique()->count(),
            ];

            $this->warn("  ⚠️  {$lockoutEvents->count()} account lockouts detected");
        } else {
            $this->info("  ✅ Normal lockout activity ({$lockoutEvents->count()} lockouts)");
        }

        return $alerts;
    }

    /**
     * Monitor permission changes.
     */
    private function monitorPermissionChanges(Carbon $timeframe): array
    {
        $this->line('🔑 Monitoring permission changes...');

        $permissionChanges = AuditLog::where('action', 'LIKE', '%permission%')
            ->orWhere('action', 'LIKE', '%role%')
            ->where('created_at', '>=', $timeframe)
            ->get();

        $alerts = [];

        foreach ($permissionChanges as $change) {
            // High-risk permission changes
            if (str_contains($change->action, 'admin') || str_contains($change->action, 'manager')) {
                $alerts[] = [
                    'type' => 'permission_escalation',
                    'severity' => 'critical',
                    'action' => $change->action,
                    'user_id' => $change->user_id,
                    'target_user' => $change->auditable_id,
                    'message' => "Administrative permission change: {$change->action}",
                    'timestamp' => $change->created_at,
                ];

                $this->error("  🚨 Critical permission change: {$change->action}");
            }
        }

        if (empty($alerts)) {
            $this->info('  ✅ No critical permission changes detected');
        }

        return $alerts;
    }

    /**
     * Monitor unusual login patterns.
     */
    private function monitorUnusualLogins(Carbon $timeframe): array
    {
        $this->line('⏰ Monitoring unusual login patterns...');

        // Check for logins outside normal hours (assuming 6 AM - 10 PM)
        // SQLite compatible query
        $unusualHourLogins = AuditLog::where('action', 'login_successful')
            ->where('created_at', '>=', $timeframe)
            ->whereRaw("strftime('%H', created_at) NOT BETWEEN '06' AND '22'")
            ->get();

        $alerts = [];

        if ($unusualHourLogins->count() > 10) {
            $alerts[] = [
                'type' => 'unusual_hours',
                'severity' => 'medium',
                'count' => $unusualHourLogins->count(),
                'message' => "High number of logins outside normal hours ({$unusualHourLogins->count()} logins)",
                'users' => $unusualHourLogins->pluck('user_id')->unique()->count(),
            ];

            $this->warn("  ⚠️  {$unusualHourLogins->count()} logins outside normal hours");
        } else {
            $this->info("  ✅ Normal login hour patterns ({$unusualHourLogins->count()} after-hours logins)");
        }

        return $alerts;
    }

    /**
     * Display monitoring results.
     */
    private function displayMonitoringResults(array $alerts, int $periodHours): void
    {
        $this->newLine();
        $this->info('📊 Security Monitoring Results');
        $this->info('=============================');

        if (empty($alerts)) {
            $this->info('🎉 No security alerts detected in the last ' . $periodHours . ' hour(s)');
            return;
        }

        $criticalCount = count(array_filter($alerts, fn($alert) => $alert['severity'] === 'critical'));
        $highCount = count(array_filter($alerts, fn($alert) => $alert['severity'] === 'high'));
        $mediumCount = count(array_filter($alerts, fn($alert) => $alert['severity'] === 'medium'));

        $this->error("🚨 {$criticalCount} Critical alerts");
        $this->warn("⚠️  {$highCount} High-severity alerts");
        $this->line("📋 {$mediumCount} Medium-severity alerts");

        $this->newLine();
        $this->line('Alert Details:');
        $this->line('==============');

        foreach ($alerts as $alert) {
            $severityIcon = match($alert['severity']) {
                'critical' => '🚨',
                'high' => '⚠️',
                'medium' => '📋',
                default => 'ℹ️'
            };

            $this->line("{$severityIcon} [{$alert['severity']}] {$alert['message']}");
        }
    }

    /**
     * Send security notifications.
     */
    private function sendSecurityNotifications(array $alerts): void
    {
        $this->line('📧 Sending security notifications...');

        $criticalAlerts = array_filter($alerts, fn($alert) => $alert['severity'] === 'critical');
        $highAlerts = array_filter($alerts, fn($alert) => $alert['severity'] === 'high');

        if (empty($criticalAlerts) && empty($highAlerts)) {
            $this->info('  ℹ️  No high-priority alerts to notify about');
            return;
        }

        $adminEmail = config('security.monitoring.admin_notification_email');
        
        if (!$adminEmail) {
            $this->warn('  ⚠️  No admin notification email configured');
            return;
        }

        // Log the alert instead of sending email (to avoid actual email sending in demo)
        Log::warning('Security Alert Notification', [
            'critical_alerts' => count($criticalAlerts),
            'high_alerts' => count($highAlerts),
            'alerts' => array_merge($criticalAlerts, $highAlerts)
        ]);

        $this->info("  ✅ Security notifications logged (would be sent to {$adminEmail})");
    }

    /**
     * Generate monitoring report.
     */
    private function generateMonitoringReport(array $alerts, int $periodHours): void
    {
        $this->line('📄 Generating monitoring report...');

        $reportData = [
            'timestamp' => Carbon::now()->toISOString(),
            'monitoring_period_hours' => $periodHours,
            'total_alerts' => count($alerts),
            'alert_breakdown' => [
                'critical' => count(array_filter($alerts, fn($alert) => $alert['severity'] === 'critical')),
                'high' => count(array_filter($alerts, fn($alert) => $alert['severity'] === 'high')),
                'medium' => count(array_filter($alerts, fn($alert) => $alert['severity'] === 'medium')),
            ],
            'alerts' => $alerts,
            'system_metrics' => $this->securityService->getSecurityMetrics()
        ];

        $filename = 'security-monitor-' . date('Y-m-d-H-i-s') . '.json';
        $filepath = storage_path('logs/' . $filename);

        file_put_contents($filepath, json_encode($reportData, JSON_PRETTY_PRINT));

        $this->info("  ✅ Monitoring report saved to: {$filepath}");
    }
}