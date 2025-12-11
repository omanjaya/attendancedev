<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\Audit\AuditLogService;
use App\Services\Audit\AuditLogFilterService;
use App\Services\Audit\AuditLogExportService;
use App\Services\Audit\AuditLogDataTableService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function __construct(
        private AuditLogService $auditLogService,
        private AuditLogFilterService $filterService,
        private AuditLogExportService $exportService,
        private AuditLogDataTableService $dataTableService
    ) {
        $this->middleware('auth');
        $this->middleware('permission:view_audit_logs');
    }

    /**
     * Display audit logs interface
     */
    public function index()
    {
        $stats = $this->auditLogService->getAuditStats();
        $eventTypes = $this->auditLogService->getEventTypes();
        $auditableTypes = $this->auditLogService->getAuditableTypes();

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

        // Apply filters
        $filters = $request->only(['start_date', 'end_date', 'event_type', 'auditable_type', 'user_id', 'risk_level']);
        $query = $this->filterService->applyFilters($query, $filters);

        // Apply search
        if ($request->filled('search.value')) {
            $query = $this->filterService->applySearch($query, $request->input('search.value'));
        }

        return $this->dataTableService->getDataTableData($query);
    }

    /**
     * Show audit log details
     */
    public function show($id)
    {
        $auditLog = $this->auditLogService->getAuditLogWithRelations($id);

        if (!$auditLog) {
            return response()->json(['success' => false, 'message' => 'Audit log not found'], 404);
        }

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

        $stats = $this->auditLogService->getAuditStats($startDate, $endDate);

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

        $auditLogs = $this->exportService->getAuditLogsForExport($validated);

        if ($validated['format'] === 'csv') {
            return $this->exportService->exportCSV($auditLogs);
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

        $result = $this->auditLogService->cleanup(
            $validated['older_than_days'],
            $validated['keep_critical'] ?? true
        );

        return response()->json($result);
    }

}
