<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;

class AuditLogDataTableService
{
    /**
     * Get audit logs data for DataTables
     */
    public function getDataTableData($query)
    {
        return DataTables::of($query)
            ->addColumn('user_info', function ($auditLog) {
                if ($auditLog->user) {
                    return '<div class="d-flex align-items-center">
                        <div class="avatar avatar-sm me-2 bg-secondary text-white">
                            ' .
                      strtoupper(substr($auditLog->user->name, 0, 1)) .
                      '
                        </div>
                        <div>
                            <div class="font-weight-medium">' .
                      e($auditLog->user->name) .
                      '</div>
                            <div class="text-muted small">' .
                      e($auditLog->user->email) .
                      '</div>
                        </div>
                    </div>';
                }

                return '<span class="text-muted">System</span>';
            })
            ->addColumn('event_info', function ($auditLog) {
                $model = new AuditLog($auditLog->toArray());
                $riskColor = $model->risk_color;
                $eventType = $model->formatted_event_type;
                $modelName = $model->model_name;

                return '<div>
                    <span class="badge bg-' .
                  $riskColor .
                  '">' .
                  e($eventType) .
                  '</span>
                    <div class="text-muted small mt-1">' .
                  e($modelName) .
                  '</div>
                </div>';
            })
            ->addColumn('changes', function ($auditLog) {
                $model = new AuditLog($auditLog->toArray());
                $changesSummary = $model->changes_summary;

                $html = '<div class="small">' . e($changesSummary) . '</div>';

                if ($model->hasSignificantChanges()) {
                    $html .= '<span class="badge bg-warning-lt mt-1">Sensitive</span>';
                }

                return $html;
            })
            ->addColumn('context', function ($auditLog) {
                $context = [];

                if ($auditLog->ip_address) {
                    $context[] = 'IP: ' . $auditLog->ip_address;
                }

                if ($auditLog->tags) {
                    $tags = is_string($auditLog->tags) ? json_decode($auditLog->tags, true) : $auditLog->tags;
                    if (is_array($tags)) {
                        foreach ($tags as $tag) {
                            $context[] = '<span class="badge bg-light text-dark">' . e($tag) . '</span>';
                        }
                    }
                }

                return implode('<br>', $context);
            })
            ->addColumn('timestamp', function ($auditLog) {
                $date = Carbon::parse($auditLog->created_at);

                return '<div>
                    <div>' .
                  $date->format('M j, Y') .
                  '</div>
                    <div class="text-muted small">' .
                  $date->format('g:i A') .
                  '</div>
                    <div class="text-muted smaller">' .
                  $date->diffForHumans() .
                  '</div>
                </div>';
            })
            ->addColumn('actions', function ($auditLog) {
                return '<button class="btn btn-sm btn-outline-primary" onclick="viewAuditDetails(\'' .
                  $auditLog->id .
                  '\')">
                    <svg class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <circle cx="12" cy="12" r="2"/>
                        <path d="M12 1l.6 1.8l1.8 .6l-1.8 .6l-.6 1.8l-.6 -1.8l-1.8 -.6l1.8 -.6z"/>
                        <path d="M12 19l.6 1.8l1.8 .6l-1.8 .6l-.6 1.8l-.6 -1.8l-1.8 -.6l1.8 -.6z"/>
                    </svg>
                    Details
                </button>';
            })
            ->rawColumns(['user_info', 'event_info', 'changes', 'context', 'timestamp', 'actions'])
            ->orderColumn('created_at', function ($query, $order) {
                $query->orderBy('audit_logs.created_at', $order);
            })
            ->make(true);
    }
}
