<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\SecurityService;
use App\Services\TwoFactorService;
use App\Services\Security\SecurityReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SecurityManagementController extends Controller
{
    public function __construct(
        private SecurityService $securityService,
        private TwoFactorService $twoFactorService,
        private SecurityReportService $reportService
    ) {
        $this->middleware(['auth', 'permission:view security dashboard']);
    }

    /**
     * Display security dashboard.
     */
    public function index()
    {
        $metrics = $this->securityService->getSecurityMetrics();
        $twoFactorStats = $this->twoFactorService->getStatistics();
        $recentEvents = $this->reportService->getRecentSecurityEvents();
        $threatLevels = $this->reportService->getThreatLevels();
        $recommendations = $this->reportService->getSecurityRecommendations();

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
            return $this->reportService->generatePdfReport($report);
        } elseif ($format === 'csv') {
            return $this->reportService->generateCsvReport($report);
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
