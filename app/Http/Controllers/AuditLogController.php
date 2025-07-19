<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class AuditLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:view_audit_logs');
    }

    /**
     * Display audit logs interface
     */
    public function index()
    {
        $stats = $this->getAuditStats();
        $eventTypes = $this->getEventTypes();
        $auditableTypes = $this->getAuditableTypes();

        return view('pages.admin.audit.index', compact('stats', 'eventTypes', 'auditableTypes'));
    }

    /**
     * Get audit logs data for DataTables
     */
    public function data(Request $request)
    {
        $query = AuditLog::with(['user'])
            ->select(['audit_logs.*', 'users.name as user_name', 'users.email as user_email'])
            ->leftJoin('users', 'audit_logs.user_id', '=', 'users.id');

        return DataTables::of($query)
            ->filter(function ($query) use ($request) {
                // Date range filter
                if ($request->filled(['start_date', 'end_date'])) {
                    $query->whereBetween('audit_logs.created_at', [
                        $request->start_date.' 00:00:00',
                        $request->end_date.' 23:59:59',
                    ]);
                }

                // Event type filter
                if ($request->filled('event_type')) {
                    $query->where('audit_logs.event_type', $request->event_type);
                }

                // Auditable type filter
                if ($request->filled('auditable_type')) {
                    $query->where('audit_logs.auditable_type', 'LIKE', '%'.$request->auditable_type.'%');
                }

                // User filter
                if ($request->filled('user_id')) {
                    $query->where('audit_logs.user_id', $request->user_id);
                }

                // Risk level filter
                if ($request->filled('risk_level')) {
                    $riskLevel = $request->risk_level;
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
                }

                // Search filter
                if ($request->filled('search.value')) {
                    $search = $request->input('search.value');
                    $query->where(function ($q) use ($search) {
                        $q->where('users.name', 'LIKE', "%{$search}%")
                            ->orWhere('users.email', 'LIKE', "%{$search}%")
                            ->orWhere('audit_logs.event_type', 'LIKE', "%{$search}%")
                            ->orWhere('audit_logs.auditable_type', 'LIKE', "%{$search}%")
                            ->orWhere('audit_logs.ip_address', 'LIKE', "%{$search}%");
                    });
                }
            })
            ->addColumn('user_info', function ($auditLog) {
                if ($auditLog->user) {
                    return '<div class="d-flex align-items-center">
                        <div class="avatar avatar-sm me-2 bg-secondary text-white">
                            '.
                      strtoupper(substr($auditLog->user->name, 0, 1)).
                      '
                        </div>
                        <div>
                            <div class="font-weight-medium">'.
                      e($auditLog->user->name).
                      '</div>
                            <div class="text-muted small">'.
                      e($auditLog->user->email).
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
                    <span class="badge bg-'.
                  $riskColor.
                  '">'.
                  e($eventType).
                  '</span>
                    <div class="text-muted small mt-1">'.
                  e($modelName).
                  '</div>
                </div>';
            })
            ->addColumn('changes', function ($auditLog) {
                $model = new AuditLog($auditLog->toArray());
                $changesSummary = $model->changes_summary;

                $html = '<div class="small">'.e($changesSummary).'</div>';

                if ($model->hasSignificantChanges()) {
                    $html .= '<span class="badge bg-warning-lt mt-1">Sensitive</span>';
                }

                return $html;
            })
            ->addColumn('context', function ($auditLog) {
                $context = [];

                if ($auditLog->ip_address) {
                    $context[] = 'IP: '.$auditLog->ip_address;
                }

                if ($auditLog->tags) {
                    $tags = is_string($auditLog->tags) ? json_decode($auditLog->tags, true) : $auditLog->tags;
                    if (is_array($tags)) {
                        foreach ($tags as $tag) {
                            $context[] = '<span class="badge bg-light text-dark">'.e($tag).'</span>';
                        }
                    }
                }

                return implode('<br>', $context);
            })
            ->addColumn('timestamp', function ($auditLog) {
                $date = Carbon::parse($auditLog->created_at);

                return '<div>
                    <div>'.
                  $date->format('M j, Y').
                  '</div>
                    <div class="text-muted small">'.
                  $date->format('g:i A').
                  '</div>
                    <div class="text-muted smaller">'.
                  $date->diffForHumans().
                  '</div>
                </div>';
            })
            ->addColumn('actions', function ($auditLog) {
                return '<button class="btn btn-sm btn-outline-primary" onclick="viewAuditDetails(\''.
                  $auditLog->id.
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

    /**
     * Show audit log details
     */
    public function show(AuditLog $auditLog)
    {
        $auditLog->load('user');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $auditLog->id,
                'user' => $auditLog->user
                  ? [
                      'name' => $auditLog->user->name,
                      'email' => $auditLog->user->email,
                  ]
                  : null,
                'event_type' => $auditLog->formatted_event_type,
                'model_name' => $auditLog->model_name,
                'auditable_type' => $auditLog->auditable_type,
                'auditable_id' => $auditLog->auditable_id,
                'old_values' => $auditLog->old_values,
                'new_values' => $auditLog->new_values,
                'changes_summary' => $auditLog->changes_summary,
                'url' => $auditLog->url,
                'ip_address' => $auditLog->ip_address,
                'user_agent' => $auditLog->user_agent,
                'tags' => $auditLog->tags,
                'risk_level' => $auditLog->risk_level,
                'risk_color' => $auditLog->risk_color,
                'has_significant_changes' => $auditLog->hasSignificantChanges(),
                'created_at' => $auditLog->created_at->format('M j, Y g:i:s A'),
                'created_at_human' => $auditLog->created_at->diffForHumans(),
            ],
        ]);
    }

    /**
     * Get audit statistics
     */
    public function stats(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->subDays(30));
        $endDate = $request->input('end_date', Carbon::now());

        $stats = $this->getAuditStats($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Export audit logs
     */
    public function export(Request $request)
    {
        $validated = $request->validate([
            'format' => 'required|in:csv,pdf',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'event_type' => 'nullable|string',
            'auditable_type' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $query = AuditLog::with(['user'])->whereBetween('created_at', [
            $validated['start_date'],
            $validated['end_date'],
        ]);

        if ($request->filled('event_type')) {
            $query->where('event_type', $validated['event_type']);
        }

        if ($request->filled('auditable_type')) {
            $query->where('auditable_type', 'LIKE', '%'.$validated['auditable_type'].'%');
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $validated['user_id']);
        }

        $auditLogs = $query->orderBy('created_at', 'desc')->get();

        if ($validated['format'] === 'csv') {
            return $this->exportCSV($auditLogs);
        }

        // PDF export would go here
        return response()->json(['error' => 'PDF export not implemented yet'], 501);
    }

    /**
     * Cleanup old audit logs
     */
    public function cleanup(Request $request)
    {
        $validated = $request->validate([
            'older_than_days' => 'required|integer|min:1',
            'keep_critical' => 'boolean',
        ]);

        $cutoffDate = Carbon::now()->subDays($validated['older_than_days']);

        $query = AuditLog::where('created_at', '<', $cutoffDate);

        // Keep critical events if requested
        if ($validated['keep_critical'] ?? true) {
            $criticalEvents = ['deleted', 'login_failed', 'permission_changed', 'role_changed'];
            $query->whereNotIn('event_type', $criticalEvents);
        }

        $deletedCount = $query->count();
        $query->delete();

        return response()->json([
            'success' => true,
            'message' => "Cleaned up {$deletedCount} audit log entries",
            'deleted_count' => $deletedCount,
        ]);
    }

    /**
     * Get audit statistics
     */
    private function getAuditStats($startDate = null, $endDate = null)
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
    private function getEventTypes()
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
    private function getAuditableTypes()
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
     * Export audit logs as CSV
     */
    private function exportCSV($auditLogs)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="audit-logs-'.now()->format('Y-m-d').'.csv"',
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
}
