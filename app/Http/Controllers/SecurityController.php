<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\SecurityService;
use App\Services\SecurityEventService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PDF;

class SecurityController extends Controller
{
    private SecurityService $securityService;
    private SecurityEventService $securityEventService;

    public function __construct(SecurityService $securityService, SecurityEventService $securityEventService)
    {
        $this->securityService = $securityService;
        $this->securityEventService = $securityEventService;
        $this->middleware(['auth', 'permission:view_security_dashboard']);
    }

    /**
     * Show security monitoring dashboard.
     */
    public function dashboard()
    {
        return view('pages.security.dashboard');
    }

    /**
     * Get security metrics for the dashboard.
     */
    public function getMetrics(Request $request): JsonResponse
    {
        $range = $request->get('range', '24h');
        $period = $this->parsePeriod($range);

        $metrics = [
            'failed_logins' => $this->getFailedLoginCount($period),
            'failed_logins_change' => $this->getMetricChange('failed_logins', $period),
            'two_factor_verifications' => $this->get2FAVerificationCount($period),
            'two_factor_change' => $this->getMetricChange('2fa_verifications', $period),
            'locked_accounts' => $this->getLockedAccountCount(),
            'locked_accounts_change' => $this->getMetricChange('locked_accounts', $period),
            'active_sessions' => $this->getActiveSessionCount(),
            'active_sessions_change' => $this->getMetricChange('active_sessions', $period),
            'chart_labels' => $this->getChartLabels($period, $range),
            'failed_logins_trend' => $this->getFailedLoginsTrend($period, $range),
            'two_factor_success' => $this->get2FASuccessTrend($period, $range),
            'two_factor_failed' => $this->get2FAFailedTrend($period, $range)
        ];

        return response()->json($metrics);
    }

    /**
     * Get security events for the dashboard.
     */
    public function getEvents(Request $request): JsonResponse
    {
        $range = $request->get('range', '24h');
        $period = $this->parsePeriod($range);

        $events = [
            'recent_events' => $this->getRecentSecurityEvents($period),
            'critical_alerts' => $this->getCriticalAlerts($period),
            'top_risk_ips' => $this->getTopRiskIPs($period)
        ];

        return response()->json($events);
    }

    /**
     * Get 2FA security report.
     */
    public function get2FAReport(Request $request): JsonResponse
    {
        $range = $request->get('range', '7d');
        $days = $this->parseDays($range);

        $report = $this->securityService->generate2FASecurityReport($days);

        return response()->json($report);
    }

    /**
     * Download comprehensive security report.
     */
    public function downloadReport(Request $request)
    {
        $range = $request->get('range', '7d');
        $days = $this->parseDays($range);

        $report = $this->generateComprehensiveReport($days);

        $pdf = PDF::loadView('reports.security-report', compact('report'))
                  ->setPaper('a4', 'portrait');

        $filename = "security-report-{$range}-" . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Get real-time security alerts.
     */
    public function getAlerts(): JsonResponse
    {
        $alerts = [
            'critical' => $this->getCriticalAlerts(now()->subHours(1)),
            'warnings' => $this->getSecurityWarnings(now()->subHours(1)),
            'info' => $this->getSecurityInfo(now()->subHours(1))
        ];

        return response()->json($alerts);
    }

    /**
     * Acknowledge security alert.
     */
    public function acknowledgeAlert(Request $request): JsonResponse
    {
        $request->validate([
            'alert_id' => 'required|string',
            'note' => 'sometimes|string|max:500'
        ]);

        $alertId = $request->input('alert_id');
        $note = $request->input('note', '');

        // Mark alert as acknowledged
        $acknowledgedKey = "alert_acknowledged_{$alertId}";
        Cache::put($acknowledgedKey, [
            'acknowledged_by' => auth()->id(),
            'acknowledged_at' => now()->toISOString(),
            'note' => $note
        ], 86400 * 7); // 7 days

        // Log the acknowledgment
        $this->securityEventService->logAdminAction('alert_acknowledged', auth()->user(), [
            'type' => 'security_alert',
            'id' => $alertId
        ], [
            'old' => ['status' => 'pending'],
            'new' => ['status' => 'acknowledged', 'note' => $note]
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Get security statistics for admin dashboard.
     */
    public function getStatistics(): JsonResponse
    {
        $stats = [
            'overview' => $this->securityService->getSecurityMetrics(),
            'trends' => $this->getSecurityTrends(),
            'compliance' => $this->getComplianceMetrics(),
            'threats' => $this->getThreatAnalysis()
        ];

        return response()->json($stats);
    }

    /**
     * Parse time period from request.
     */
    private function parsePeriod(string $range): Carbon
    {
        return match ($range) {
            '1h' => now()->subHour(),
            '24h' => now()->subDay(),
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            default => now()->subDay()
        };
    }

    /**
     * Parse days from range.
     */
    private function parseDays(string $range): int
    {
        return match ($range) {
            '1h' => 1,
            '24h' => 1,
            '7d' => 7,
            '30d' => 30,
            default => 7
        };
    }

    /**
     * Get failed login count for period.
     */
    private function getFailedLoginCount(Carbon $since): int
    {
        return AuditLog::where('action', 'LIKE', '%login_failed%')
                      ->where('created_at', '>=', $since)
                      ->count();
    }

    /**
     * Get 2FA verification count for period.
     */
    private function get2FAVerificationCount(Carbon $since): int
    {
        return AuditLog::where('action', 'LIKE', '2fa_%')
                      ->where('created_at', '>=', $since)
                      ->count();
    }

    /**
     * Get locked account count.
     */
    private function getLockedAccountCount(): int
    {
        // Count cache entries with lockdown patterns
        // This is a simplified implementation
        return 0; // Placeholder - would need cache scanning
    }

    /**
     * Get active session count.
     */
    private function getActiveSessionCount(): int
    {
        // This would depend on your session storage
        return DB::table('sessions')
                 ->where('last_activity', '>', time() - 1800) // 30 minutes
                 ->count();
    }

    /**
     * Get metric change percentage.
     */
    private function getMetricChange(string $metric, Carbon $period): float
    {
        $currentPeriodDuration = now()->diffInMinutes($period);
        $previousPeriod = $period->copy()->subMinutes($currentPeriodDuration);
        
        $currentValue = match ($metric) {
            'failed_logins' => $this->getFailedLoginCount($period),
            '2fa_verifications' => $this->get2FAVerificationCount($period),
            'locked_accounts' => $this->getLockedAccountCount(),
            'active_sessions' => $this->getActiveSessionCount(),
            default => 0
        };

        $previousValue = match ($metric) {
            'failed_logins' => $this->getFailedLoginCount($previousPeriod),
            '2fa_verifications' => $this->get2FAVerificationCount($previousPeriod),
            'locked_accounts' => $this->getLockedAccountCount(), // Assume same for now
            'active_sessions' => $this->getActiveSessionCount(), // Assume same for now
            default => 0
        };

        if ($previousValue == 0) {
            return $currentValue > 0 ? 100 : 0;
        }

        return round((($currentValue - $previousValue) / $previousValue) * 100, 1);
    }

    /**
     * Get chart labels based on period and range.
     */
    private function getChartLabels(Carbon $period, string $range): array
    {
        $labels = [];
        $now = now();

        if ($range === '1h') {
            // 10-minute intervals for 1 hour
            for ($i = 5; $i >= 0; $i--) {
                $labels[] = $now->copy()->subMinutes($i * 10)->format('H:i');
            }
        } elseif ($range === '24h') {
            // 4-hour intervals for 24 hours
            for ($i = 5; $i >= 0; $i--) {
                $labels[] = $now->copy()->subHours($i * 4)->format('H:00');
            }
        } elseif ($range === '7d') {
            // Daily intervals for 7 days
            for ($i = 6; $i >= 0; $i--) {
                $labels[] = $now->copy()->subDays($i)->format('M j');
            }
        } else {
            // Weekly intervals for 30 days
            for ($i = 3; $i >= 0; $i--) {
                $labels[] = $now->copy()->subWeeks($i)->format('M j');
            }
        }

        return $labels;
    }

    /**
     * Get failed logins trend data.
     */
    private function getFailedLoginsTrend(Carbon $period, string $range): array
    {
        $data = [];
        $now = now();

        if ($range === '1h') {
            for ($i = 5; $i >= 0; $i--) {
                $start = $now->copy()->subMinutes(($i + 1) * 10);
                $end = $now->copy()->subMinutes($i * 10);
                $count = AuditLog::where('action', 'LIKE', '%login_failed%')
                               ->whereBetween('created_at', [$start, $end])
                               ->count();
                $data[] = $count;
            }
        } elseif ($range === '24h') {
            for ($i = 5; $i >= 0; $i--) {
                $start = $now->copy()->subHours(($i + 1) * 4);
                $end = $now->copy()->subHours($i * 4);
                $count = AuditLog::where('action', 'LIKE', '%login_failed%')
                               ->whereBetween('created_at', [$start, $end])
                               ->count();
                $data[] = $count;
            }
        } elseif ($range === '7d') {
            for ($i = 6; $i >= 0; $i--) {
                $date = $now->copy()->subDays($i)->startOfDay();
                $count = AuditLog::where('action', 'LIKE', '%login_failed%')
                               ->whereDate('created_at', $date)
                               ->count();
                $data[] = $count;
            }
        } else {
            for ($i = 3; $i >= 0; $i--) {
                $start = $now->copy()->subWeeks($i + 1)->startOfWeek();
                $end = $now->copy()->subWeeks($i)->endOfWeek();
                $count = AuditLog::where('action', 'LIKE', '%login_failed%')
                               ->whereBetween('created_at', [$start, $end])
                               ->count();
                $data[] = $count;
            }
        }

        return $data;
    }

    /**
     * Get 2FA success trend data.
     */
    private function get2FASuccessTrend(Carbon $period, string $range): array
    {
        // Similar logic to failed logins but for successful 2FA
        // Implementation would be similar to getFailedLoginsTrend
        return [0, 0, 0, 0, 0, 0]; // Placeholder
    }

    /**
     * Get 2FA failed trend data.
     */
    private function get2FAFailedTrend(Carbon $period, string $range): array
    {
        // Similar logic to failed logins but for failed 2FA
        // Implementation would be similar to getFailedLoginsTrend
        return [0, 0, 0, 0, 0, 0]; // Placeholder
    }

    /**
     * Get recent security events.
     */
    private function getRecentSecurityEvents(Carbon $since): array
    {
        return AuditLog::where('created_at', '>=', $since)
                      ->where('risk_level', '!=', 'low')
                      ->orderBy('created_at', 'desc')
                      ->limit(20)
                      ->get()
                      ->map(function ($log) {
                          return [
                              'id' => $log->id,
                              'action' => $this->formatActionName($log->action),
                              'description' => $this->generateEventDescription($log),
                              'risk_level' => $log->risk_level,
                              'created_at' => $log->created_at->toISOString(),
                              'user_id' => $log->user_id,
                              'ip_address' => $this->sanitizeIP($log->ip_address)
                          ];
                      })
                      ->toArray();
    }

    /**
     * Get critical security alerts.
     */
    private function getCriticalAlerts(Carbon $since): array
    {
        $alerts = [];

        // Check for coordinated attacks
        $coordinated = $this->securityService->detectCoordinated2FAAttack();
        foreach ($coordinated as $threat) {
            $alerts[] = [
                'id' => 'coordinated_' . hash('md5', json_encode($threat)),
                'message' => "Coordinated 2FA attack detected from {$threat['ip_address']} affecting {$threat['affected_users']} users",
                'timestamp' => now()->toISOString(),
                'severity' => $threat['severity'],
                'type' => 'coordinated_attack'
            ];
        }

        // Check for high-risk audit logs
        $criticalLogs = AuditLog::where('risk_level', 'critical')
                              ->where('created_at', '>=', $since)
                              ->orderBy('created_at', 'desc')
                              ->limit(10)
                              ->get();

        foreach ($criticalLogs as $log) {
            $alerts[] = [
                'id' => 'audit_' . $log->id,
                'message' => $this->formatCriticalAlert($log),
                'timestamp' => $log->created_at->toISOString(),
                'severity' => 'critical',
                'type' => 'audit_event'
            ];
        }

        return $alerts;
    }

    /**
     * Get top risk IP addresses.
     */
    private function getTopRiskIPs(Carbon $since): array
    {
        return AuditLog::where('created_at', '>=', $since)
                      ->where('risk_level', '!=', 'low')
                      ->select('ip_address')
                      ->selectRaw('COUNT(*) as failures')
                      ->groupBy('ip_address')
                      ->orderBy('failures', 'desc')
                      ->limit(10)
                      ->get()
                      ->map(function ($item) {
                          return [
                              'address' => $this->sanitizeIP($item->ip_address),
                              'failures' => $item->failures,
                              'location' => $this->getIPLocation($item->ip_address)
                          ];
                      })
                      ->toArray();
    }

    /**
     * Helper methods for formatting and processing.
     */
    private function formatActionName(string $action): string
    {
        return ucwords(str_replace('_', ' ', $action));
    }

    private function generateEventDescription(AuditLog $log): string
    {
        $descriptions = [
            'login_failed' => 'Failed login attempt from ' . $this->sanitizeIP($log->ip_address),
            '2fa_verification_failed' => 'Failed 2FA verification',
            '2fa_lockdown_triggered' => 'Account locked due to repeated 2FA failures',
            'security_violation_brute_force_attack' => 'Brute force attack detected',
            'security_violation_coordinated_attack' => 'Coordinated attack detected'
        ];

        return $descriptions[$log->action] ?? 'Security event occurred';
    }

    private function formatCriticalAlert(AuditLog $log): string
    {
        return match ($log->action) {
            '2fa_lockdown_triggered' => 'Account lockdown triggered for repeated 2FA failures',
            'security_violation_brute_force_attack' => 'Brute force attack detected from ' . $this->sanitizeIP($log->ip_address),
            'security_violation_coordinated_attack' => 'Coordinated attack detected across multiple accounts',
            default => 'Critical security event: ' . $this->formatActionName($log->action)
        };
    }

    private function sanitizeIP(string $ip): string
    {
        $parts = explode('.', $ip);
        if (count($parts) === 4) {
            return $parts[0] . '.' . $parts[1] . '.***.**';
        }
        return substr($ip, 0, 8) . '***';
    }

    private function getIPLocation(string $ip): ?string
    {
        // Placeholder for IP geolocation
        return 'Unknown';
    }

    private function generateComprehensiveReport(int $days): array
    {
        return [
            'period' => "{$days} days",
            'generated_at' => now()->toISOString(),
            'generated_by' => auth()->user()->name,
            'overview' => $this->securityService->getSecurityMetrics(),
            '2fa_report' => $this->securityService->generate2FASecurityReport($days),
            'threat_analysis' => $this->getThreatAnalysis(),
            'compliance' => $this->getComplianceMetrics(),
            'recommendations' => $this->getActionableRecommendations()
        ];
    }

    private function getSecurityTrends(): array
    {
        // Implementation for security trends over time
        return [];
    }

    private function getComplianceMetrics(): array
    {
        // Implementation for compliance metrics
        return [];
    }

    private function getThreatAnalysis(): array
    {
        // Implementation for threat analysis
        return [];
    }

    private function getSecurityWarnings(Carbon $since): array
    {
        // Implementation for security warnings
        return [];
    }

    private function getSecurityInfo(Carbon $since): array
    {
        // Implementation for security info
        return [];
    }

    private function getActionableRecommendations(): array
    {
        // Implementation for actionable recommendations
        return [];
    }
}