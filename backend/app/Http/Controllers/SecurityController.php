<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserDevice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SecurityController extends Controller
{
    /**
     * Display the security dashboard.
     */
    public function dashboard()
    {
        $user = Auth::user();

        // Security metrics
        $metrics = [
            'total_devices' => UserDevice::where('user_id', $user->id)->count(),
            'trusted_devices' => UserDevice::where('user_id', $user->id)->where('is_trusted', true)->count(),
            'recent_logins' => AuditLog::where('user_id', $user->id)
                ->where('event_type', 'auth')
                ->where('created_at', '>=', Carbon::now()->subDays(30))
                ->count(),
            'security_events' => AuditLog::where('user_id', $user->id)
                ->where('risk_level', 'high')
                ->where('created_at', '>=', Carbon::now()->subDays(7))
                ->count(),
        ];

        // Recent security events
        $recentEvents = AuditLog::where('user_id', $user->id)
            ->whereIn('event_type', ['auth', 'security', 'access'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('pages.security.dashboard', compact('metrics', 'recentEvents'));
    }

    /**
     * Display user devices.
     */
    public function devices()
    {
        $user = Auth::user();

        $devices = UserDevice::where('user_id', $user->id)
            ->orderBy('last_seen_at', 'desc')
            ->get();

        return view('pages.security.devices', compact('devices'));
    }

    /**
     * Display security notifications.
     */
    public function notifications()
    {
        $user = Auth::user();

        $notifications = $user->notifications()
            ->whereIn('type', [
                'App\Notifications\NewDeviceLogin',
                'App\Notifications\SecurityAlert',
                'App\Notifications\SuspiciousActivity',
            ])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('pages.security.notifications', compact('notifications'));
    }

    /**
     * Display security events.
     */
    public function events()
    {
        $user = Auth::user();

        $events = AuditLog::where('user_id', $user->id)
            ->whereIn('event_type', ['auth', 'security', 'access', 'data'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('pages.security.events', compact('events'));
    }

    /**
     * Display two-factor authentication settings.
     */
    public function twoFactor()
    {
        $user = Auth::user();

        return view('pages.security.two-factor', compact('user'));
    }

    /**
     * Trust a device.
     */
    public function trustDevice(UserDevice $device)
    {
        $this->authorize('update', $device);

        $device->update([
            'is_trusted' => true,
            'trusted_at' => Carbon::now(),
        ]);

        return response()->json(['message' => 'Device trusted successfully']);
    }

    /**
     * Remove a device.
     */
    public function removeDevice(UserDevice $device)
    {
        $this->authorize('delete', $device);

        $device->delete();

        return response()->json(['message' => 'Device removed successfully']);
    }

    /**
     * Mark notification as read.
     */
    public function markNotificationRead(Request $request)
    {
        $user = Auth::user();

        if ($request->has('notification_id')) {
            $user->notifications()
                ->where('id', $request->notification_id)
                ->update(['read_at' => Carbon::now()]);
        } else {
            $user->unreadNotifications->markAsRead();
        }

        return response()->json(['message' => 'Notification marked as read']);
    }

    // ============================================
    // API Methods for Frontend Security Page
    // ============================================

    /**
     * Get security overview/metrics (API)
     */
    public function getOverview()
    {
        $totalUsers = User::count();
        $usersWith2fa = User::where('two_factor_enabled', true)->count();
        $lockedAccounts = User::where('account_locked', true)->count();
        $activeSessions = \DB::table('sessions')->count();

        $failedLoginsToday = AuditLog::where('event_type', 'login_failed')
            ->whereDate('created_at', Carbon::today())
            ->count();

        // Get high risk events (deleted, login_failed, permission/role changes)
        $suspiciousActivities = AuditLog::whereIn('event_type', ['deleted', 'login_failed', 'permission_changed', 'role_changed'])
            ->whereDate('created_at', Carbon::today())
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_users' => $totalUsers,
                'users_with_2fa' => $usersWith2fa,
                'users_without_2fa' => $totalUsers - $usersWith2fa,
                'locked_accounts' => $lockedAccounts,
                'active_sessions' => $activeSessions,
                'failed_logins_today' => $failedLoginsToday,
                'suspicious_activities' => $suspiciousActivities,
                'last_security_scan' => Carbon::now()->subDay()->toISOString(),
            ],
        ]);
    }

    /**
     * Get security metrics (API)
     */
    public function getMetrics()
    {
        return $this->getOverview();
    }

    /**
     * Get user devices (API)
     */
    public function getDevices()
    {
        $user = Auth::user();
        $currentDeviceId = session('device_id');

        $devices = UserDevice::where('user_id', $user->id)
            ->orderBy('last_seen_at', 'desc')
            ->get()
            ->map(function ($device) use ($currentDeviceId) {
                return [
                    'id' => (string) $device->id,
                    'name' => $device->device_name ?? 'Unknown Device',
                    'type' => $this->detectDeviceType($device->user_agent),
                    'browser' => $this->detectBrowser($device->user_agent),
                    'os' => $this->detectOS($device->user_agent),
                    'ip_address' => $device->ip_address,
                    'location' => $device->location ?? 'Unknown',
                    'last_used_at' => $device->last_seen_at?->toISOString(),
                    'is_current' => $device->id === $currentDeviceId,
                    'is_trusted' => (bool) $device->is_trusted,
                    'created_at' => $device->created_at->toISOString(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $devices,
        ]);
    }

    /**
     * Toggle device trust status (API)
     */
    public function toggleDeviceTrust(Request $request, $deviceId)
    {
        $user = Auth::user();
        $device = UserDevice::where('id', $deviceId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $device->update([
            'is_trusted' => !$device->is_trusted,
            'trusted_at' => !$device->is_trusted ? Carbon::now() : null,
        ]);

        return response()->json([
            'success' => true,
            'message' => $device->is_trusted ? 'Device trusted' : 'Device untrusted',
            'data' => [
                'is_trusted' => $device->is_trusted,
            ],
        ]);
    }

    /**
     * Remove device (API)
     */
    public function deleteDevice($deviceId)
    {
        $user = Auth::user();
        $device = UserDevice::where('id', $deviceId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $device->delete();

        return response()->json([
            'success' => true,
            'message' => 'Device removed successfully',
        ]);
    }

    /**
     * Get active sessions (API)
     */
    public function getSessions()
    {
        $user = Auth::user();
        $currentSessionId = session()->getId();

        $sessions = \DB::table('sessions')
            ->where('user_id', $user->id)
            ->orderBy('last_activity', 'desc')
            ->get()
            ->map(function ($session) use ($currentSessionId) {
                $payload = @unserialize(base64_decode($session->payload));

                return [
                    'id' => $session->id,
                    'device_name' => $this->detectBrowser($session->user_agent) . ' - ' . $this->detectOS($session->user_agent),
                    'ip_address' => $session->ip_address,
                    'location' => 'Indonesia',
                    'started_at' => Carbon::createFromTimestamp($session->last_activity)->subHours(1)->toISOString(),
                    'last_activity' => Carbon::createFromTimestamp($session->last_activity)->toISOString(),
                    'is_current' => $session->id === $currentSessionId,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $sessions,
        ]);
    }

    /**
     * Terminate a session (API)
     */
    public function terminateSession($sessionId)
    {
        $user = Auth::user();

        $deleted = \DB::table('sessions')
            ->where('id', $sessionId)
            ->where('user_id', $user->id)
            ->delete();

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Session terminated',
        ]);
    }

    /**
     * Terminate all sessions except current (API)
     */
    public function terminateAllSessions()
    {
        $user = Auth::user();
        $currentSessionId = session()->getId();

        \DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $currentSessionId)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'All other sessions terminated',
        ]);
    }

    /**
     * Get audit logs (API)
     */
    public function getAuditLogs(Request $request)
    {
        $query = AuditLog::query();

        // Filter by action/event_type
        if ($request->has('action') && $request->action) {
            $query->where('event_type', $request->action);
        }

        // Filter by user
        if ($request->has('user_id') && $request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $logs = $query->with('user:id,name,email')
            ->orderBy('created_at', 'desc')
            ->limit($request->get('limit', 50))
            ->get()
            ->map(function ($log) {
                // Get description from new_values or generate from event_type
                $description = '';
                if (isset($log->new_values['description'])) {
                    $description = $log->new_values['description'];
                } elseif (isset($log->new_values['message'])) {
                    $description = $log->new_values['message'];
                } else {
                    $description = ucfirst(str_replace('_', ' ', $log->event_type));
                    if ($log->auditable_type && $log->auditable_type !== 'Security') {
                        $description .= ' ' . class_basename($log->auditable_type);
                    }
                }

                return [
                    'id' => (string) $log->id,
                    'user_id' => (string) $log->user_id,
                    'user_name' => $log->user?->name ?? 'System',
                    'user_email' => $log->user?->email ?? 'system@local',
                    'action' => $log->event_type,
                    'resource_type' => class_basename($log->auditable_type ?? 'system'),
                    'resource_id' => $log->auditable_id,
                    'description' => $description,
                    'ip_address' => $log->ip_address,
                    'user_agent' => $log->user_agent,
                    'created_at' => $log->created_at->toISOString(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    /**
     * Get security events (API)
     */
    public function getEvents(Request $request)
    {
        return $this->getAuditLogs($request);
    }

    /**
     * Get 2FA report (API)
     */
    public function get2FAReport()
    {
        $totalUsers = User::count();
        $enabledCount = User::where('two_factor_enabled', true)->count();
        $disabledCount = $totalUsers - $enabledCount;

        $recentEnablements = AuditLog::where('event_type', '2fa_enabled')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_users' => $totalUsers,
                'enabled_count' => $enabledCount,
                'disabled_count' => $disabledCount,
                'adoption_rate' => $totalUsers > 0 ? round(($enabledCount / $totalUsers) * 100, 1) : 0,
                'recent_enablements' => $recentEnablements,
            ],
        ]);
    }

    /**
     * Get security alerts (API)
     */
    public function getAlerts()
    {
        $alerts = [];

        // Check for multiple failed logins
        $failedLogins = AuditLog::where('event_type', 'login_failed')
            ->where('created_at', '>=', Carbon::now()->subMinutes(10))
            ->count();

        if ($failedLogins >= 5) {
            $alerts[] = [
                'id' => 'alert_failed_logins',
                'type' => 'warning',
                'title' => 'Multiple Failed Logins',
                'message' => "{$failedLogins} failed login attempts in the last 10 minutes",
                'action_required' => true,
                'created_at' => Carbon::now()->toISOString(),
            ];
        }

        // Check for new device logins today
        $newDevices = UserDevice::whereDate('created_at', Carbon::today())->count();
        if ($newDevices > 0) {
            $alerts[] = [
                'id' => 'alert_new_devices',
                'type' => 'info',
                'title' => 'New Device Logins',
                'message' => "{$newDevices} new device(s) logged in today",
                'action_required' => false,
                'created_at' => Carbon::now()->toISOString(),
            ];
        }

        // Check for high risk events (deleted, permission/role changes)
        $highRiskEvents = AuditLog::whereIn('event_type', ['deleted', 'permission_changed', 'role_changed'])
            ->whereDate('created_at', Carbon::today())
            ->count();

        if ($highRiskEvents > 0) {
            $alerts[] = [
                'id' => 'alert_high_risk',
                'type' => 'error',
                'title' => 'High Risk Activity',
                'message' => "{$highRiskEvents} high risk event(s) detected today",
                'action_required' => true,
                'created_at' => Carbon::now()->toISOString(),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $alerts,
        ]);
    }

    /**
     * Get security statistics (API)
     */
    public function getStatistics()
    {
        $last30Days = Carbon::now()->subDays(30);

        $loginAttempts = AuditLog::where('event_type', 'login')
            ->where('created_at', '>=', $last30Days)
            ->count();

        $failedAttempts = AuditLog::where('event_type', 'login_failed')
            ->where('created_at', '>=', $last30Days)
            ->count();

        $passwordChanges = AuditLog::where('event_type', 'password_change')
            ->where('created_at', '>=', $last30Days)
            ->count();

        $twoFactorChanges = AuditLog::whereIn('event_type', ['2fa_enabled', '2fa_disabled'])
            ->where('created_at', '>=', $last30Days)
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'login_attempts' => $loginAttempts,
                'failed_attempts' => $failedAttempts,
                'success_rate' => $loginAttempts > 0
                    ? round((($loginAttempts - $failedAttempts) / $loginAttempts) * 100, 1)
                    : 100,
                'password_changes' => $passwordChanges,
                'two_factor_changes' => $twoFactorChanges,
                'period' => '30 days',
            ],
        ]);
    }

    /**
     * Acknowledge an alert (API)
     */
    public function acknowledgeAlert($alertId)
    {
        return response()->json([
            'success' => true,
            'message' => 'Alert acknowledged',
        ]);
    }

    /**
     * Download security report (API)
     */
    public function downloadReport(Request $request)
    {
        // For now, return JSON. Can be extended to PDF/Excel
        $data = [
            'generated_at' => Carbon::now()->toISOString(),
            'overview' => $this->getOverview()->getData()->data,
            'statistics' => $this->getStatistics()->getData()->data,
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get audit logs for current user (Personal activity log)
     */
    public function getMyActivityLog(Request $request)
    {
        $user = Auth::user();

        $logs = AuditLog::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($request->get('limit', 50))
            ->get()
            ->map(function ($log) {
                return $this->formatAuditLog($log);
            });

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    /**
     * Get audit logs for a specific employee (Admin only)
     */
    public function getEmployeeActivityLog(Request $request, $employeeId)
    {
        // Find employee and get their user
        $employee = \App\Models\Employee::with('user')->findOrFail($employeeId);

        if (!$employee->user) {
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'Employee has no linked user account',
            ]);
        }

        $query = AuditLog::where('user_id', $employee->user->id);

        // Filter by event type
        if ($request->has('event_type') && $request->event_type) {
            $query->where('event_type', $request->event_type);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $logs = $query->orderBy('created_at', 'desc')
            ->limit($request->get('limit', 100))
            ->get()
            ->map(function ($log) {
                return $this->formatAuditLog($log);
            });

        // Get activity summary
        $summary = $this->getActivitySummary($employee->user->id);

        return response()->json([
            'success' => true,
            'data' => [
                'employee' => [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'employee_code' => $employee->employee_code,
                ],
                'summary' => $summary,
                'logs' => $logs,
            ],
        ]);
    }

    /**
     * Get activity summary for a user
     */
    private function getActivitySummary(string $userId): array
    {
        $last30Days = Carbon::now()->subDays(30);

        return [
            'total_logins' => AuditLog::where('user_id', $userId)
                ->where('event_type', 'login')
                ->where('created_at', '>=', $last30Days)
                ->count(),
            'total_check_ins' => AuditLog::where('user_id', $userId)
                ->where('event_type', 'check_in')
                ->where('created_at', '>=', $last30Days)
                ->count(),
            'total_check_outs' => AuditLog::where('user_id', $userId)
                ->where('event_type', 'check_out')
                ->where('created_at', '>=', $last30Days)
                ->count(),
            'failed_logins' => AuditLog::where('user_id', $userId)
                ->where('event_type', 'login_failed')
                ->where('created_at', '>=', $last30Days)
                ->count(),
            'last_login' => AuditLog::where('user_id', $userId)
                ->where('event_type', 'login')
                ->orderBy('created_at', 'desc')
                ->first()?->created_at?->toISOString(),
            'last_activity' => AuditLog::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->first()?->created_at?->toISOString(),
            'period' => '30 days',
        ];
    }

    /**
     * Format audit log for API response
     */
    private function formatAuditLog(AuditLog $log): array
    {
        // Get description from new_values or generate from event_type
        $description = '';
        if (isset($log->new_values['description'])) {
            $description = $log->new_values['description'];
        } elseif (isset($log->new_values['message'])) {
            $description = $log->new_values['message'];
        } else {
            $description = $this->getEventDescription($log->event_type, $log->new_values ?? []);
        }

        return [
            'id' => (string) $log->id,
            'event_type' => $log->event_type,
            'action' => $log->event_type,
            'description' => $description,
            'resource_type' => class_basename($log->auditable_type ?? 'system'),
            'resource_id' => $log->auditable_id,
            'ip_address' => $log->ip_address ?? ($log->new_values['ip_address'] ?? null),
            'user_agent' => $log->user_agent,
            'metadata' => $log->new_values,
            'created_at' => $log->created_at->toISOString(),
            'formatted_time' => $log->created_at->format('d M Y H:i'),
        ];
    }

    /**
     * Get human-readable description for event type
     */
    private function getEventDescription(string $eventType, array $metadata = []): string
    {
        $descriptions = [
            'login' => 'Login ke sistem',
            'logout' => 'Logout dari sistem',
            'login_failed' => 'Gagal login',
            'password_change' => 'Mengubah password',
            'password_reset' => 'Reset password',
            'check_in' => 'Absen masuk' . (isset($metadata['time']) ? ' pukul ' . $metadata['time'] : ''),
            'check_out' => 'Absen pulang' . (isset($metadata['time']) ? ' pukul ' . $metadata['time'] : ''),
            '2fa_enabled' => 'Mengaktifkan Two-Factor Authentication',
            '2fa_disabled' => 'Menonaktifkan Two-Factor Authentication',
            'profile_update' => 'Mengupdate profil',
            'created' => 'Membuat data ' . (isset($metadata['model']) ? $metadata['model'] : ''),
            'updated' => 'Mengupdate data ' . (isset($metadata['model']) ? $metadata['model'] : ''),
            'deleted' => 'Menghapus data ' . (isset($metadata['model']) ? $metadata['model'] : ''),
        ];

        return $descriptions[$eventType] ?? ucfirst(str_replace('_', ' ', $eventType));
    }

    // ============================================
    // Helper Methods
    // ============================================

    private function detectDeviceType(?string $userAgent): string
    {
        if (!$userAgent) return 'desktop';

        $userAgent = strtolower($userAgent);

        if (str_contains($userAgent, 'mobile') || str_contains($userAgent, 'android') || str_contains($userAgent, 'iphone')) {
            return 'mobile';
        }
        if (str_contains($userAgent, 'tablet') || str_contains($userAgent, 'ipad')) {
            return 'tablet';
        }

        return 'desktop';
    }

    private function detectBrowser(?string $userAgent): string
    {
        if (!$userAgent) return 'Unknown';

        if (str_contains($userAgent, 'Chrome')) return 'Chrome';
        if (str_contains($userAgent, 'Firefox')) return 'Firefox';
        if (str_contains($userAgent, 'Safari') && !str_contains($userAgent, 'Chrome')) return 'Safari';
        if (str_contains($userAgent, 'Edge')) return 'Edge';
        if (str_contains($userAgent, 'Opera')) return 'Opera';

        return 'Unknown';
    }

    private function detectOS(?string $userAgent): string
    {
        if (!$userAgent) return 'Unknown';

        if (str_contains($userAgent, 'Windows')) return 'Windows';
        if (str_contains($userAgent, 'Mac OS')) return 'macOS';
        if (str_contains($userAgent, 'Linux')) return 'Linux';
        if (str_contains($userAgent, 'Android')) return 'Android';
        if (str_contains($userAgent, 'iOS') || str_contains($userAgent, 'iPhone')) return 'iOS';

        return 'Unknown';
    }
}
