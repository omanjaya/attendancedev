<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use Illuminate\Support\Collection;

class AuditLogExportService
{
    /**
     * Export audit logs as CSV
     */
    public function exportCSV(Collection $auditLogs)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="audit-logs-' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($auditLogs) {
            $file = fopen('php://output', 'w');

            // Headers
            fputcsv($file, [
                'Timestamp',
                'User',
                'Event Type',
                'Model',
                'Model ID',
                'Changes Summary',
                'IP Address',
                'URL',
                'Tags',
            ]);

            // Data
            foreach ($auditLogs as $log) {
                $model = new AuditLog($log->toArray());
                fputcsv($file, [
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->user ? $log->user->name : 'System',
                    $model->formatted_event_type,
                    $model->model_name,
                    $log->auditable_id,
                    $model->changes_summary,
                    $log->ip_address,
                    $log->url,
                    is_array($log->tags) ? implode(', ', $log->tags) : $log->tags,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get formatted audit log data for export
     */
    public function getAuditLogsForExport(array $filters)
    {
        $query = AuditLog::with(['user'])->whereBetween('created_at', [
            $filters['start_date'],
            $filters['end_date'],
        ]);

        if (!empty($filters['event_type'])) {
            $query->where('event_type', $filters['event_type']);
        }

        if (!empty($filters['auditable_type'])) {
            $query->where('auditable_type', 'LIKE', '%' . $filters['auditable_type'] . '%');
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }
}
