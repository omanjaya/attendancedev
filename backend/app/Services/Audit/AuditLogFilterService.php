<?php

namespace App\Services\Audit;

use Illuminate\Database\Eloquent\Builder;

class AuditLogFilterService
{
    /**
     * Apply filters to audit log query
     */
    public function applyFilters(Builder $query, array $filters): Builder
    {
        // Date range filter
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('audit_logs.created_at', [
                $filters['start_date'] . ' 00:00:00',
                $filters['end_date'] . ' 23:59:59',
            ]);
        }

        // Event type filter
        if (!empty($filters['event_type'])) {
            $query->where('audit_logs.event_type', $filters['event_type']);
        }

        // Auditable type filter
        if (!empty($filters['auditable_type'])) {
            $query->where('audit_logs.auditable_type', 'LIKE', '%' . $filters['auditable_type'] . '%');
        }

        // User filter
        if (!empty($filters['user_id'])) {
            $query->where('audit_logs.user_id', $filters['user_id']);
        }

        // Risk level filter
        if (!empty($filters['risk_level'])) {
            $query = $this->applyRiskLevelFilter($query, $filters['risk_level']);
        }

        return $query;
    }

    /**
     * Apply risk level filter
     */
    private function applyRiskLevelFilter(Builder $query, string $riskLevel): Builder
    {
        if ($riskLevel === 'high') {
            $query->whereIn('audit_logs.event_type', [
                'deleted',
                'login_failed',
                'permission_changed',
                'role_changed',
            ]);
        } elseif ($riskLevel === 'medium') {
            $query->where(function ($q) {
                $q->whereIn('audit_logs.auditable_type', [
                    'App\\Models\\User',
                    'App\\Models\\Employee',
                    'App\\Models\\Payroll',
                ])->whereNotIn('audit_logs.event_type', [
                    'deleted',
                    'login_failed',
                    'permission_changed',
                    'role_changed',
                ]);
            });
        } else {
            $query
                ->whereNotIn('audit_logs.auditable_type', [
                    'App\\Models\\User',
                    'App\\Models\\Employee',
                    'App\\Models\\Payroll',
                ])
                ->whereNotIn('audit_logs.event_type', [
                    'deleted',
                    'login_failed',
                    'permission_changed',
                    'role_changed',
                ]);
        }

        return $query;
    }

    /**
     * Apply search filter
     */
    public function applySearch(Builder $query, string $search): Builder
    {
        $query->where(function ($q) use ($search) {
            $q->where('users.name', 'LIKE', "%{$search}%")
                ->orWhere('users.email', 'LIKE', "%{$search}%")
                ->orWhere('audit_logs.event_type', 'LIKE', "%{$search}%")
                ->orWhere('audit_logs.auditable_type', 'LIKE', "%{$search}%")
                ->orWhere('audit_logs.ip_address', 'LIKE', "%{$search}%");
        });

        return $query;
    }
}
