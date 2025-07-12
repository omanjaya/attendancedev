<?php

namespace App\Console\Commands;

use App\Services\SecurityLogger;
use App\Services\SecurityService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class SecurityReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:report 
                            {--date= : Specific date for report (Y-m-d format)}
                            {--days=7 : Number of days to include in report}
                            {--type=daily : Report type (daily, weekly, monthly)}
                            {--format=console : Output format (console, json, file)}
                            {--save : Save report to storage}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate comprehensive security reports';

    private SecurityLogger $securityLogger;
    private SecurityService $securityService;

    public function __construct(SecurityLogger $securityLogger, SecurityService $securityService)
    {
        parent::__construct();
        $this->securityLogger = $securityLogger;
        $this->securityService = $securityService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔒 Generating Security Report...');
        $this->newLine();

        $date = $this->option('date') ? Carbon::parse($this->option('date')) : now();
        $days = (int) $this->option('days');
        $type = $this->option('type');
        $format = $this->option('format');
        $save = $this->option('save');

        switch ($type) {
            case 'daily':
                $report = $this->generateDailyReport($date);
                break;
            case 'weekly':
                $report = $this->generateWeeklyReport($date, 7);
                break;
            case 'monthly':
                $report = $this->generateMonthlyReport($date, 30);
                break;
            default:
                $report = $this->generateCustomReport($date, $days);
        }

        switch ($format) {
            case 'json':
                $this->outputJson($report);
                break;
            case 'file':
                $this->saveToFile($report, $type, $date);
                break;
            default:
                $this->outputConsole($report);
        }

        if ($save) {
            $this->saveToFile($report, $type, $date);
        }

        $this->newLine();
        $this->info('✅ Security report generated successfully!');
    }

    private function generateDailyReport(Carbon $date): array
    {
        $summary = $this->securityLogger->generateDailySummary($date);
        $metrics = $this->securityService->getSecurityMetrics();
        $twoFAReport = $this->securityService->generate2FASecurityReport(1);

        return [
            'type' => 'daily',
            'date' => $date->format('Y-m-d'),
            'summary' => $summary,
            'metrics' => $metrics,
            '2fa_report' => $twoFAReport
        ];
    }

    private function generateWeeklyReport(Carbon $date, int $days): array
    {
        $weeklyReports = [];
        for ($i = 0; $i < $days; $i++) {
            $reportDate = $date->copy()->subDays($i);
            $weeklyReports[] = $this->securityLogger->generateDailySummary($reportDate);
        }

        $metrics = $this->securityService->getSecurityMetrics();
        $twoFAReport = $this->securityService->generate2FASecurityReport($days);

        return [
            'type' => 'weekly',
            'period' => $date->copy()->subDays($days-1)->format('Y-m-d') . ' to ' . $date->format('Y-m-d'),
            'daily_reports' => $weeklyReports,
            'metrics' => $metrics,
            '2fa_report' => $twoFAReport,
            'trends' => $this->analyzeTrends($weeklyReports)
        ];
    }

    private function generateMonthlyReport(Carbon $date, int $days): array
    {
        $metrics = $this->securityService->getSecurityMetrics();
        $securityReport = $this->securityService->generateSecurityReport($days);
        $twoFAReport = $this->securityService->generate2FASecurityReport($days);

        return [
            'type' => 'monthly',
            'period' => $date->copy()->subDays($days-1)->format('Y-m-d') . ' to ' . $date->format('Y-m-d'),
            'metrics' => $metrics,
            'security_report' => $securityReport,
            '2fa_report' => $twoFAReport,
            'recommendations' => $this->generateRecommendations($metrics, $twoFAReport)
        ];
    }

    private function generateCustomReport(Carbon $date, int $days): array
    {
        return $this->generateWeeklyReport($date, $days);
    }

    private function outputConsole(array $report): void
    {
        $this->info("📊 Security Report ({$report['type']})");
        $this->info("📅 Period: " . ($report['date'] ?? $report['period']));
        $this->newLine();

        if (isset($report['summary'])) {
            $this->displayDailySummary($report['summary']);
        }

        if (isset($report['metrics'])) {
            $this->displayMetrics($report['metrics']);
        }

        if (isset($report['2fa_report'])) {
            $this->display2FAReport($report['2fa_report']);
        }

        if (isset($report['recommendations'])) {
            $this->displayRecommendations($report['recommendations']);
        }
    }

    private function displayDailySummary(array $summary): void
    {
        $this->warn('📈 Daily Summary');
        $this->table(
            ['Metric', 'Successful', 'Failed'],
            [
                ['Login Attempts', $summary['login_attempts']['successful'], $summary['login_attempts']['failed']],
                ['2FA Attempts', $summary['2fa_attempts']['successful'], $summary['2fa_attempts']['failed']]
            ]
        );

        $this->warn('🚨 Security Events');
        $this->table(
            ['Event Type', 'Count'],
            [
                ['Lockdowns', $summary['security_events']['lockdowns']],
                ['Rate Limits', $summary['security_events']['rate_limits']],
                ['Admin Interventions', $summary['security_events']['admin_interventions']],
                ['Emergency Recoveries', $summary['security_events']['emergency_recoveries']]
            ]
        );

        $this->warn('⚠️ Risk Distribution');
        $this->table(
            ['Risk Level', 'Count'],
            [
                ['Critical', $summary['risk_distribution']['critical']],
                ['High', $summary['risk_distribution']['high']],
                ['Medium', $summary['risk_distribution']['medium']],
                ['Low', $summary['risk_distribution']['low']]
            ]
        );
        $this->newLine();
    }

    private function displayMetrics(array $metrics): void
    {
        $this->warn('📊 Security Metrics (30 days)');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Failed Logins', $metrics['failed_logins']],
                ['Suspicious Activities', $metrics['suspicious_activities']],
                ['High Risk Events', $metrics['high_risk_events']],
                ['Unique IPs', $metrics['unique_ips']],
                ['Password Changes', $metrics['password_changes']],
                ['2FA Enabled Users', $metrics['2fa_enabled_users']],
                ['Active Sessions', $metrics['active_sessions']]
            ]
        );
        $this->newLine();
    }

    private function display2FAReport(array $report): void
    {
        $this->warn('🔐 2FA Security Report');
        
        if (isset($report['summary'])) {
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total 2FA Attempts', $report['summary']['total_2fa_attempts']],
                    ['Failed 2FA Attempts', $report['summary']['failed_2fa_attempts']],
                    ['Success Rate', $report['summary']['success_rate'] . '%'],
                    ['Unique IPs with Failures', $report['summary']['unique_ips_with_failures']],
                    ['Locked Accounts', $report['summary']['locked_accounts']],
                    ['Emergency Recoveries', $report['summary']['emergency_recovery_requests']]
                ]
            );
        }

        if (isset($report['threats']) && !empty($report['threats'])) {
            $this->error('🚨 THREATS DETECTED:');
            foreach ($report['threats'] as $threat) {
                $this->error("- {$threat['type']}: {$threat['severity']} threat from {$threat['ip_address']}");
                $this->line("  Affected users: {$threat['affected_users']}, Total attempts: {$threat['total_attempts']}");
                $this->line("  Recommended action: {$threat['recommended_action']}");
            }
        }

        $this->newLine();
    }

    private function displayRecommendations(array $recommendations): void
    {
        if (empty($recommendations)) {
            $this->info('✅ No security recommendations at this time.');
            return;
        }

        $this->warn('💡 Security Recommendations');
        foreach ($recommendations as $rec) {
            $icon = match($rec['type']) {
                'critical' => '🔴',
                'warning' => '🟡',
                'alert' => '🟠',
                default => '🔵'
            };
            
            $this->line("{$icon} {$rec['title']}");
            $this->line("   {$rec['description']}");
            $this->line("   Action: {$rec['action']}");
            $this->newLine();
        }
    }

    private function outputJson(array $report): void
    {
        $this->line(json_encode($report, JSON_PRETTY_PRINT));
    }

    private function saveToFile(array $report, string $type, Carbon $date): void
    {
        $filename = "security_report_{$type}_{$date->format('Y-m-d')}.json";
        $path = storage_path("app/reports/{$filename}");
        
        // Ensure directory exists
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, json_encode($report, JSON_PRETTY_PRINT));
        
        $this->info("📁 Report saved to: {$path}");
    }

    private function analyzeTrends(array $reports): array
    {
        $loginTrends = array_column($reports, 'login_attempts');
        $twoFATrends = array_column($reports, '2fa_attempts');
        
        return [
            'login_success_trend' => $this->calculateTrend(array_column($loginTrends, 'successful')),
            'login_failure_trend' => $this->calculateTrend(array_column($loginTrends, 'failed')),
            '2fa_success_trend' => $this->calculateTrend(array_column($twoFATrends, 'successful')),
            '2fa_failure_trend' => $this->calculateTrend(array_column($twoFATrends, 'failed'))
        ];
    }

    private function calculateTrend(array $values): string
    {
        if (count($values) < 2) return 'stable';
        
        $first = array_slice($values, 0, ceil(count($values) / 2));
        $second = array_slice($values, ceil(count($values) / 2));
        
        $firstAvg = array_sum($first) / count($first);
        $secondAvg = array_sum($second) / count($second);
        
        $change = $secondAvg - $firstAvg;
        $percentChange = $firstAvg > 0 ? ($change / $firstAvg) * 100 : 0;
        
        if (abs($percentChange) < 10) return 'stable';
        return $percentChange > 0 ? 'increasing' : 'decreasing';
    }

    private function generateRecommendations(array $metrics, array $twoFAReport): array
    {
        $recommendations = [];

        // High failed login rate
        if ($metrics['failed_logins'] > 100) {
            $recommendations[] = [
                'type' => 'warning',
                'title' => 'High Failed Login Rate',
                'description' => "Detected {$metrics['failed_logins']} failed login attempts in the last 30 days.",
                'action' => 'Review rate limiting settings and consider IP whitelisting'
            ];
        }

        // Low 2FA success rate
        if (isset($twoFAReport['summary']['success_rate']) && $twoFAReport['summary']['success_rate'] < 85) {
            $recommendations[] = [
                'type' => 'alert',
                'title' => 'Low 2FA Success Rate',
                'description' => "2FA success rate is {$twoFAReport['summary']['success_rate']}%.",
                'action' => 'Provide user training on 2FA usage and verify setup instructions'
            ];
        }

        // Multiple emergency recoveries
        if (isset($twoFAReport['summary']['emergency_recovery_requests']) && $twoFAReport['summary']['emergency_recovery_requests'] > 5) {
            $recommendations[] = [
                'type' => 'alert',
                'title' => 'High Emergency Recovery Requests',
                'description' => "Received {$twoFAReport['summary']['emergency_recovery_requests']} emergency recovery requests.",
                'action' => 'Review 2FA setup process and user documentation'
            ];
        }

        return $recommendations;
    }
}
