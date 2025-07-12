<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\AuditLog;
use App\Services\SecurityService;
use App\Services\TwoFactorService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SecurityAudit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:audit 
                           {--users : Audit user accounts}
                           {--passwords : Check password policies}
                           {--2fa : Audit 2FA compliance}
                           {--sessions : Check active sessions}
                           {--permissions : Audit permissions}
                           {--report : Generate detailed report}
                           {--all : Run all audits}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform security audit of the system';

    private SecurityService $securityService;
    private TwoFactorService $twoFactorService;

    public function __construct(SecurityService $securityService, TwoFactorService $twoFactorService)
    {
        parent::__construct();
        $this->securityService = $securityService;
        $this->twoFactorService = $twoFactorService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting security audit...');
        $this->newLine();

        $audits = [];

        if ($this->option('all')) {
            $audits = ['users', 'passwords', '2fa', 'sessions', 'permissions'];
        } else {
            if ($this->option('users')) $audits[] = 'users';
            if ($this->option('passwords')) $audits[] = 'passwords';
            if ($this->option('2fa')) $audits[] = '2fa';
            if ($this->option('sessions')) $audits[] = 'sessions';
            if ($this->option('permissions')) $audits[] = 'permissions';
        }

        if (empty($audits)) {
            $this->error('No audit types specified. Use --all or specify individual audits.');
            return 1;
        }

        $results = [];

        foreach ($audits as $audit) {
            $results[$audit] = $this->runAudit($audit);
        }

        $this->newLine();
        $this->displaySummary($results);

        if ($this->option('report')) {
            $this->generateReport($results);
        }

        return 0;
    }

    /**
     * Run a specific audit.
     */
    private function runAudit(string $auditType): array
    {
        switch ($auditType) {
            case 'users':
                return $this->auditUsers();
            case 'passwords':
                return $this->auditPasswords();
            case '2fa':
                return $this->audit2FA();
            case 'sessions':
                return $this->auditSessions();
            case 'permissions':
                return $this->auditPermissions();
            default:
                return ['error' => "Unknown audit type: {$auditType}"];
        }
    }

    /**
     * Audit user accounts.
     */
    private function auditUsers(): array
    {
        $this->line('👥 Auditing user accounts...');

        $totalUsers = User::count();
        $activeUsers = User::active()->count();
        $inactiveUsers = User::where('is_active', false)->count();
        $lockedUsers = User::locked()->count();
        $usersWithFailedLogins = User::where('failed_login_attempts', '>', 0)->count();
        
        // Users who haven't logged in for 90+ days
        $dormantUsers = User::where('last_login_at', '<', Carbon::now()->subDays(90))
            ->orWhereNull('last_login_at')
            ->count();

        // Users with administrative roles
        $adminUsers = User::role(['admin', 'manager'])->count();

        $results = [
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'inactive_users' => $inactiveUsers,
            'locked_users' => $lockedUsers,
            'users_with_failed_logins' => $usersWithFailedLogins,
            'dormant_users' => $dormantUsers,
            'admin_users' => $adminUsers,
            'issues' => []
        ];

        // Identify issues
        if ($lockedUsers > 0) {
            $results['issues'][] = "⚠️  {$lockedUsers} users are currently locked";
        }

        if ($usersWithFailedLogins > 5) {
            $results['issues'][] = "⚠️  {$usersWithFailedLogins} users have failed login attempts";
        }

        if ($dormantUsers > 10) {
            $results['issues'][] = "⚠️  {$dormantUsers} users haven't logged in for 90+ days";
        }

        $this->info("  ✓ Total users: {$totalUsers} (Active: {$activeUsers}, Inactive: {$inactiveUsers})");
        $this->info("  ✓ Locked users: {$lockedUsers}");
        $this->info("  ✓ Dormant users: {$dormantUsers}");

        return $results;
    }

    /**
     * Audit password policies.
     */
    private function auditPasswords(): array
    {
        $this->line('🔐 Auditing password policies...');

        $expiryDays = config('security.password.expiry_days', 0);
        $minLength = config('security.password.min_length', 8);

        $results = [
            'policy_expiry_days' => $expiryDays,
            'policy_min_length' => $minLength,
            'expired_passwords' => 0,
            'force_change_users' => 0,
            'never_changed_passwords' => 0,
            'issues' => []
        ];

        // Users who need to change passwords
        $forceChangeUsers = User::where('force_password_change', true)->count();
        $results['force_change_users'] = $forceChangeUsers;

        // Users who never changed their password
        $neverChangedUsers = User::whereNull('password_changed_at')->count();
        $results['never_changed_passwords'] = $neverChangedUsers;

        // Expired passwords (if policy is enabled)
        if ($expiryDays > 0) {
            $expiredCount = User::where('password_changed_at', '<', Carbon::now()->subDays($expiryDays))
                ->orWhereNull('password_changed_at')
                ->count();
            $results['expired_passwords'] = $expiredCount;

            if ($expiredCount > 0) {
                $results['issues'][] = "⚠️  {$expiredCount} users have expired passwords";
            }
        }

        if ($forceChangeUsers > 0) {
            $results['issues'][] = "⚠️  {$forceChangeUsers} users are forced to change passwords";
        }

        if ($neverChangedUsers > 5) {
            $results['issues'][] = "⚠️  {$neverChangedUsers} users never changed their passwords";
        }

        $this->info("  ✓ Password expiry policy: " . ($expiryDays > 0 ? "{$expiryDays} days" : "Disabled"));
        $this->info("  ✓ Users requiring password change: {$forceChangeUsers}");
        $this->info("  ✓ Users with expired passwords: {$results['expired_passwords']}");

        return $results;
    }

    /**
     * Audit 2FA compliance.
     */
    private function audit2FA(): array
    {
        $this->line('🛡️  Auditing 2FA compliance...');

        $stats = $this->twoFactorService->getStatistics();
        
        $results = [
            'total_users' => $stats['total_users'],
            'enabled_2fa' => $stats['enabled_users'],
            'required_2fa' => $stats['required_users'],
            'compliance_rate' => $stats['compliance_rate'],
            'non_compliant' => $stats['required_users'] - $stats['enabled_users'],
            'issues' => []
        ];

        // Check compliance
        if ($stats['compliance_rate'] < 80) {
            $results['issues'][] = "⚠️  Low 2FA compliance rate: {$stats['compliance_rate']}%";
        }

        if ($results['non_compliant'] > 0) {
            $results['issues'][] = "⚠️  {$results['non_compliant']} required users don't have 2FA enabled";
        }

        $this->info("  ✓ 2FA enabled users: {$stats['enabled_users']}/{$stats['total_users']}");
        $this->info("  ✓ Compliance rate: {$stats['compliance_rate']}%");
        $this->info("  ✓ Non-compliant users: {$results['non_compliant']}");

        return $results;
    }

    /**
     * Audit active sessions.
     */
    private function auditSessions(): array
    {
        $this->line('🔄 Auditing active sessions...');

        // This is a simplified audit - in a real implementation,
        // you'd query your session storage directly
        $activeSessions = DB::table('sessions')
            ->where('last_activity', '>', time() - 1800) // Active in last 30 minutes
            ->count();

        $totalSessions = DB::table('sessions')->count();
        $oldSessions = $totalSessions - $activeSessions;

        $results = [
            'total_sessions' => $totalSessions,
            'active_sessions' => $activeSessions,
            'old_sessions' => $oldSessions,
            'issues' => []
        ];

        if ($oldSessions > 100) {
            $results['issues'][] = "⚠️  {$oldSessions} old sessions should be cleaned up";
        }

        $this->info("  ✓ Total sessions: {$totalSessions}");
        $this->info("  ✓ Active sessions: {$activeSessions}");
        $this->info("  ✓ Old sessions: {$oldSessions}");

        return $results;
    }

    /**
     * Audit permissions and roles.
     */
    private function auditPermissions(): array
    {
        $this->line('🔑 Auditing permissions and roles...');

        $adminUsers = User::role('admin')->count();
        $managerUsers = User::role('manager')->count();
        $teacherUsers = User::role('teacher')->count();
        $staffUsers = User::role('staff')->count();
        $usersWithoutRoles = User::doesntHave('roles')->count();

        $results = [
            'admin_users' => $adminUsers,
            'manager_users' => $managerUsers,
            'teacher_users' => $teacherUsers,
            'staff_users' => $staffUsers,
            'users_without_roles' => $usersWithoutRoles,
            'issues' => []
        ];

        if ($adminUsers > 5) {
            $results['issues'][] = "⚠️  High number of admin users: {$adminUsers}";
        }

        if ($usersWithoutRoles > 0) {
            $results['issues'][] = "⚠️  {$usersWithoutRoles} users don't have any roles assigned";
        }

        $this->info("  ✓ Admin users: {$adminUsers}");
        $this->info("  ✓ Manager users: {$managerUsers}");
        $this->info("  ✓ Teacher users: {$teacherUsers}");
        $this->info("  ✓ Staff users: {$staffUsers}");
        $this->info("  ✓ Users without roles: {$usersWithoutRoles}");

        return $results;
    }

    /**
     * Display audit summary.
     */
    private function displaySummary(array $results): void
    {
        $this->info('📊 Security Audit Summary');
        $this->info('========================');

        $totalIssues = 0;
        foreach ($results as $auditType => $result) {
            if (isset($result['issues']) && !empty($result['issues'])) {
                $issueCount = count($result['issues']);
                $totalIssues += $issueCount;
                
                $this->warn("{$auditType}: {$issueCount} issues found");
                foreach ($result['issues'] as $issue) {
                    $this->line("  {$issue}");
                }
            } else {
                $this->info("{$auditType}: No issues found ✅");
            }
        }

        $this->newLine();
        if ($totalIssues > 0) {
            $this->error("Total security issues found: {$totalIssues}");
        } else {
            $this->info("🎉 No security issues found! System appears secure.");
        }
    }

    /**
     * Generate detailed audit report.
     */
    private function generateReport(array $results): void
    {
        $this->line('📄 Generating detailed security audit report...');

        $reportData = [
            'timestamp' => Carbon::now()->toISOString(),
            'audit_results' => $results,
            'system_info' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'environment' => app()->environment()
            ]
        ];

        $filename = 'security-audit-' . date('Y-m-d-H-i-s') . '.json';
        $filepath = storage_path('logs/' . $filename);

        file_put_contents($filepath, json_encode($reportData, JSON_PRETTY_PRINT));

        $this->info("  ✓ Report saved to: {$filepath}");
    }
}