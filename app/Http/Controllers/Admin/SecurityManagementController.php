<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\SecurityService;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SecurityManagementController extends Controller
{
    private SecurityService $securityService;

    private TwoFactorService $twoFactorService;

    public function __construct(SecurityService $securityService, TwoFactorService $twoFactorService)
    {
        $this->securityService = $securityService;
        $this->twoFactorService = $twoFactorService;
        $this->middleware(['auth', 'permission:view security dashboard']);
    }

    /**
     * Display security dashboard.
     */
    public function index()
    {
        $metrics = $this->securityService->getSecurityMetrics();
        $twoFactorStats = $this->twoFactorService->getStatistics();
        $recentEvents = $this->getRecentSecurityEvents();
        $threatLevels = $this->getThreatLevels();
        $recommendations = $this->getSecurityRecommendations();

        return view(
            'pages.admin.security.index',
            compact('metrics', 'twoFactorStats', 'recentEvents', 'threatLevels', 'recommendations'),
        );
    }

    /**
     * Display detailed security report.
     */
    public function report(Request $request)
    {
        $days = $request->get('days', 30);
        $report = $this->securityService->generateSecurityReport($days);

        return view('pages.admin.security.report', compact('report'));
    }

    /**
     * Export security report.
     */
    public function exportReport(Request $request)
    {
        $days = $request->get('days', 30);
        $format = $request->get('format', 'pdf');
        $report = $this->securityService->generateSecurityReport($days);

        if ($format === 'pdf') {
            return $this->generatePdfReport($report);
        } elseif ($format === 'csv') {
            return $this->generateCsvReport($report);
        } elseif ($format === 'json') {
            return response()->json($report);
        }

        return response()->json(['error' => 'Invalid format'], 400);
    }

    /**
     * Display user security management.
     */
    public function users(Request $request)
    {
        $query = User::with('roles')->select([
            'id',
            'name',
            'email',
            'two_factor_enabled',
            'last_login_at',
            'failed_login_attempts',
            'account_locked',
            'locked_until',
        ]);

        // Filter by security status
        if ($request->has('filter')) {
            switch ($request->get('filter')) {
                case 'locked':
                    $query->locked();
                    break;
                case 'no_2fa':
                    $query->where('two_factor_enabled', false);
                    break;
                case 'failed_logins':
                    $query->where('failed_login_attempts', '>', 0);
                    break;
                case 'inactive':
                    $query->where('last_login_at', '<', now()->subDays(30));
                    break;
            }
        }

        // Search by name or email
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        $users = $query->paginate(50);

        return view('pages.admin.security.users', compact('users'));
    }

    /**
     * Lock/unlock user account.
     */
    public function toggleUserLock(Request $request, User $user)
    {
        $this->authorize('manage user security');

        if ($user->isLocked()) {
            $user->unlockAccount();
            $message = 'User account has been unlocked.';
        } else {
            $duration = $request->get('duration', 60); // minutes
            $user->lockAccount(now()->addMinutes($duration));
            $message = "User account has been locked for {$duration} minutes.";
        }

        // Log security action
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $user->isLocked() ? 'user_unlocked' : 'user_locked',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'old_values' => [],
            'new_values' => ['locked' => $user->isLocked()],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'risk_level' => 'medium',
        ]);

        return response()->json(['success' => true, 'message' => $message]);
    }

    /**
     * Force password change for user.
     */
    public function forcePasswordChange(Request $request, User $user)
    {
        $this->authorize('manage user security');

        $user->forcePasswordChange();

        // Log security action
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'force_password_change',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'old_values' => [],
            'new_values' => ['force_password_change' => true],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'risk_level' => 'medium',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User will be required to change password on next login.',
        ]);
    }

    /**
     * Reset user's failed login attempts.
     */
    public function resetFailedLogins(Request $request, User $user)
    {
        $this->authorize('manage user security');

        $user->resetFailedLogins();

        // Log security action
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'reset_failed_logins',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'old_values' => ['failed_login_attempts' => $user->failed_login_attempts],
            'new_values' => ['failed_login_attempts' => 0],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'risk_level' => 'low',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Failed login attempts have been reset.',
        ]);
    }

    /**
     * Disable 2FA for user (admin only).
     */
    public function disable2FA(Request $request, User $user)
    {
        $this->authorize('manage user security');

        if ($this->twoFactorService->disableTwoFactorAdmin($user)) {
            // Log security action
            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'admin_disable_2fa',
                'auditable_type' => User::class,
                'auditable_id' => $user->id,
                'old_values' => ['two_factor_enabled' => true],
                'new_values' => ['two_factor_enabled' => false],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'risk_level' => 'high',
            ]);

            return response()->json([
                'success' => true,
                'message' => '2FA has been disabled for this user.',
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Failed to disable 2FA.'], 400);
    }

    /**
     * Get recent security events.
     */
    private function getRecentSecurityEvents(): array
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
     * Get current threat levels.
     */
    private function getThreatLevels(): array
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
     * Get security recommendations.
     */
    private function getSecurityRecommendations(): array
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
     * Generate PDF report.
     */
    private function generatePdfReport(array $report)
    {
        // This would use a PDF generation library like dompdf
        // For now, return JSON
        return response()->json($report);
    }

    /**
     * Generate CSV report.
     */
    private function generateCsvReport(array $report)
    {
        $csv = "Security Report - {$report['period']}\n";
        $csv .= "Generated: {$report['generated_at']}\n\n";

        $csv .= "Metrics:\n";
        foreach ($report['metrics'] as $key => $value) {
            $csv .= ucfirst(str_replace('_', ' ', $key)).": {$value}\n";
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

    /**
     * Get security settings.
     */
    public function settings()
    {
        $this->authorize('manage security settings');

        $settings = [
            'password_policy' => config('security.password'),
            'session_settings' => config('security.session'),
            'rate_limiting' => config('security.rate_limiting'),
            'ip_whitelist' => config('security.ip_whitelist'),
            'monitoring' => config('security.monitoring'),
            'audit' => config('security.audit'),
        ];

        return view('pages.admin.security.settings', compact('settings'));
    }

    /**
     * Update security settings.
     */
    public function updateSettings(Request $request)
    {
        $this->authorize('manage security settings');

        $request->validate([
            'password_min_length' => 'required|integer|min:6|max:128',
            'password_expiry_days' => 'required|integer|min:0|max:365',
            'session_lifetime' => 'required|integer|min:5|max:1440',
            'max_login_attempts' => 'required|integer|min:3|max:20',
            'lockout_minutes' => 'required|integer|min:5|max:1440',
        ]);

        // Update security configuration (this would typically update a database table)
        // For now, we'll just cache the settings
        Cache::put('security_settings', $request->all(), 86400);

        return response()->json([
            'success' => true,
            'message' => 'Security settings have been updated.',
        ]);
    }
}
