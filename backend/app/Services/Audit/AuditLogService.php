<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AuditLogService
{
    /**
     * Get audit log statistics
     */
    public function getAuditStats($startDate = null, $endDate = null): array
    {
        $startDate = $startDate ?: Carbon::now()->subDays(30);
        $endDate = $endDate ?: Carbon::now();

        $baseQuery = AuditLog::whereBetween('created_at', [$startDate, $endDate]);

        return [
            'total_events' => (clone $baseQuery)->count(),
            'unique_users' => (clone $baseQuery)->distinct('user_id')->count('user_id'),
            'high_risk_events' => (clone $baseQuery)
                ->whereIn('event_type', ['deleted', 'login_failed', 'permission_changed', 'role_changed'])
                ->count(),
            'today_events' => AuditLog::whereDate('created_at', Carbon::today())->count(),
            'events_by_type' => (clone $baseQuery)
                ->select('event_type', DB::raw('count(*) as count'))
                ->groupBy('event_type')
                ->orderByDesc('count')
                ->get()
                ->pluck('count', 'event_type'),
            'events_by_day' => (clone $baseQuery)
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->pluck('count', 'date'),
        ];
    }

    /**
     * Get available event types
     */
    public function getEventTypes()
    {
        return AuditLog::distinct('event_type')
            ->orderBy('event_type')
            ->pluck('event_type')
            ->map(function ($type) {
                return [
                    'value' => $type,
                    'label' => ucfirst(str_replace('_', ' ', $type)),
                ];
            });
    }

    /**
     * Get available auditable types
     */
    public function getAuditableTypes()
    {
        return AuditLog::distinct('auditable_type')
            ->whereNotNull('auditable_type')
            ->orderBy('auditable_type')
            ->pluck('auditable_type')
            ->map(function ($type) {
                return [
                    'value' => $type,
                    'label' => class_basename($type),
                ];
            });
    }

    /**
     * Cleanup old audit logs
     */
    public function cleanup(int $olderThanDays, bool $keepCritical = true): array
    {
        $cutoffDate = Carbon::now()->subDays($olderThanDays);

        $query = AuditLog::where('created_at', '<', $cutoffDate);

        // Keep critical events if requested
        if ($keepCritical) {
            $criticalEvents = ['deleted', 'login_failed', 'permission_changed', 'role_changed'];
            $query->whereNotIn('event_type', $criticalEvents);
        }

        $deletedCount = $query->count();
        $query->delete();

        return [
            'success' => true,
            'message' => "Cleaned up {$deletedCount} audit log entries",
            'deleted_count' => $deletedCount,
        ];
    }

    /**
     * Get audit log with relationships
     */
    public function getAuditLogWithRelations(string $id): ?AuditLog
    {
        return AuditLog::with('user')->find($id);
    }
}
