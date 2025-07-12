@extends('layouts.authenticated')

@section('title', 'Audit Logs')

@section('page-content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <x-layout.page-header
            title="Audit Logs"
            subtitle="System Administration - Monitor all user activities">
            <x-slot name="actions">
                <div class="flex flex-col sm:flex-row gap-2">
                    <x-ui.select onchange="exportAuditLogs(this.value)">
                        <option value="">Export Options</option>
                        <option value="csv">Export as CSV</option>
                        <option value="pdf">Export as PDF</option>
                    </x-ui.select>
                    
                    <x-ui.button 
                        variant="outline" 
                        onclick="cleanupAuditLogs()">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Cleanup
                    </x-ui.button>
                    
                    <x-ui.button 
                        variant="default" 
                        onclick="refreshAuditLogs()">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Refresh
                    </x-ui.button>
                </div>
            </x-slot>
        </x-layout.page-header>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <x-ui.card class="stats-card">
                <div class="flex items-center">
                    <div class="mr-4">
                        <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                    </div>
                    <div class="flex-1">
                        <div class="font-medium text-foreground">Total Events</div>
                        <div class="text-sm text-muted-foreground">All activities</div>
                    </div>
                </div>
                <div class="text-2xl font-bold mt-4 text-primary" id="totalEvents">{{ $stats['total_events'] }}</div>
            </x-ui.card>
            
            <x-ui.card class="stats-card">
                <div class="flex items-center">
                    <div class="mr-4">
                        <div class="w-12 h-12 bg-success/10 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="flex-1">
                        <div class="font-medium text-foreground">Unique Users</div>
                        <div class="text-sm text-muted-foreground">Active accounts</div>
                    </div>
                </div>
                <div class="text-2xl font-bold mt-4 text-success" id="uniqueUsers">{{ $stats['unique_users'] }}</div>
            </x-ui.card>
            
            <x-ui.card class="stats-card">
                <div class="flex items-center">
                    <div class="mr-4">
                        <div class="w-12 h-12 bg-destructive/10 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-destructive" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="flex-1">
                        <div class="font-medium text-foreground">High Risk Events</div>
                        <div class="text-sm text-muted-foreground">Security alerts</div>
                    </div>
                </div>
                <div class="text-2xl font-bold mt-4 text-destructive" id="highRiskEvents">{{ $stats['high_risk_events'] }}</div>
            </x-ui.card>
            
            <x-ui.card class="stats-card">
                <div class="flex items-center">
                    <div class="mr-4">
                        <div class="w-12 h-12 bg-warning/10 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="flex-1">
                        <div class="font-medium text-foreground">Today's Events</div>
                        <div class="text-sm text-muted-foreground">Current activity</div>
                    </div>
                </div>
                <div class="text-2xl font-bold mt-4 text-warning" id="todayEvents">{{ $stats['today_events'] }}</div>
            </x-ui.card>
        </div>

        <!-- Filters -->
        <x-ui.card>
            <x-slot name="title">
                <div class="flex justify-between items-center">
                    <span>Filters</span>
                    <x-ui.button variant="outline" size="sm" onclick="clearFilters()">
                        Clear All
                    </x-ui.button>
                </div>
            </x-slot>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
                <div class="lg:col-span-2">
                    <x-ui.label for="date-range">Date Range</x-ui.label>
                    <div class="grid grid-cols-2 gap-2">
                        <x-ui.input type="date" id="startDate" value="{{ now()->subDays(30)->format('Y-m-d') }}" />
                        <x-ui.input type="date" id="endDate" value="{{ now()->format('Y-m-d') }}" />
                    </div>
                </div>
                
                <div>
                    <x-ui.label for="eventTypeFilter">Event Type</x-ui.label>
                    <x-ui.select id="eventTypeFilter">
                        <option value="">All Events</option>
                        @foreach($eventTypes as $eventType)
                            <option value="{{ $eventType['value'] }}">{{ $eventType['label'] }}</option>
                        @endforeach
                    </x-ui.select>
                </div>
                
                <div>
                    <x-ui.label for="auditableTypeFilter">Model Type</x-ui.label>
                    <x-ui.select id="auditableTypeFilter">
                        <option value="">All Models</option>
                        @foreach($auditableTypes as $type)
                            <option value="{{ $type['value'] }}">{{ $type['label'] }}</option>
                        @endforeach
                    </x-ui.select>
                </div>
                
                <div>
                    <x-ui.label for="riskLevelFilter">Risk Level</x-ui.label>
                    <x-ui.select id="riskLevelFilter">
                        <option value="">All Levels</option>
                        <option value="high">High Risk</option>
                        <option value="medium">Medium Risk</option>
                        <option value="low">Low Risk</option>
                    </x-ui.select>
                </div>
                
                <div>
                    <x-ui.label for="apply-filter">Filter</x-ui.label>
                    <x-ui.button class="w-full" onclick="applyFilters()">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        Apply
                    </x-ui.button>
                </div>
            </div>
        </x-ui.card>

        <!-- Audit Logs Table -->
        <x-ui.card>
            <x-slot name="title">
                <div class="flex justify-between items-center">
                    <span>Audit Trail</span>
                    <x-ui.badge variant="success" id="liveIndicator">LIVE</x-ui.badge>
                </div>
            </x-slot>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-border" id="auditLogsTable">
                    <thead class="bg-muted/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">User</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Event</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Changes</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Context</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Timestamp</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider w-8">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-card divide-y divide-border">
                        <!-- Data loaded via DataTables -->
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </div>
</div>

<!-- Audit Log Details Modal -->
<div id="auditDetailsModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-background/80 backdrop-blur-sm transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-card rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full border border-border">
            <div class="bg-card px-6 py-4 border-b border-border">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-foreground" id="modal-title">Audit Log Details</h3>
                    <button type="button" class="text-muted-foreground hover:text-foreground" onclick="closeModal()">
                        <span class="sr-only">Close</span>
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
            <div class="bg-card px-6 py-4" id="auditDetailsContent">
                <!-- Content loaded via AJAX -->
            </div>
            <div class="bg-muted/30 px-6 py-4 flex justify-end">
                <button type="button" class="btn btn-outline" onclick="closeModal()">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Export Modal -->
<div id="exportModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="export-modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="exportForm">
                <div class="bg-white px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900" id="export-modal-title">Export Audit Logs</h3>
                </div>
                <div class="bg-white px-6 py-4">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Export Format</label>
                        <select class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" name="format" required>
                            <option value="">Select format...</option>
                            <option value="csv">CSV File</option>
                            <option value="pdf">PDF Report</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                        <div class="grid grid-cols-2 gap-4">
                            <input type="date" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" name="start_date" required>
                            <input type="date" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" name="end_date" required>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-4 flex justify-end space-x-3">
                    <button type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150" onclick="closeExportModal()">Cancel</button>
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">Export</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Cleanup Modal -->
<div id="cleanupModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="cleanup-modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="cleanupForm">
                <div class="bg-white px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900" id="cleanup-modal-title">Cleanup Old Audit Logs</h3>
                </div>
                <div class="bg-white px-6 py-4">
                    <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-md mb-4">
                        <h4 class="font-medium text-base">⚠️ Warning</h4>
                        <p>This will permanently delete old audit log entries. This action cannot be undone.</p>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Delete logs older than</label>
                        <div class="flex">
                            <input type="number" class="block w-full px-3 py-2 border border-gray-300 rounded-l-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" name="older_than_days" value="90" min="1" required>
                            <span class="inline-flex items-center px-3 py-2 border border-l-0 border-gray-300 bg-gray-50 text-gray-500 text-sm rounded-r-md">days</span>
                        </div>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" name="keep_critical" value="1" checked>
                        <label class="ml-2 text-sm text-gray-900">
                            Keep critical security events (login failures, deletions, permission changes)
                        </label>
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-4 flex justify-end space-x-3">
                    <button type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150" onclick="closeCleanupModal()">Cancel</button>
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition duration-150">
                        <svg class="w-4 h-4 mr-2" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M4 7h16"/>
                            <path d="M10 11v6"/>
                            <path d="M14 11v6"/>
                            <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/>
                            <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/>
                        </svg>
                        Delete Old Logs
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let auditTable;
    
    // Initialize DataTable
    initializeAuditTable();
    
    // Auto-refresh every 30 seconds
    setInterval(function() {
        if (auditTable) {
            auditTable.ajax.reload(null, false);
            updateStatistics();
        }
    }, 30000);
    
    // Form submissions
    $('#exportForm').on('submit', function(e) {
        e.preventDefault();
        performExport();
    });
    
    $('#cleanupForm').on('submit', function(e) {
        e.preventDefault();
        performCleanup();
    });
});

function initializeAuditTable() {
    auditTable = $('#auditLogsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("audit.data") }}',
            data: function(d) {
                d.start_date = $('#startDate').val();
                d.end_date = $('#endDate').val();
                d.event_type = $('#eventTypeFilter').val();
                d.auditable_type = $('#auditableTypeFilter').val();
                d.risk_level = $('#riskLevelFilter').val();
                d.user_id = $('#userFilter').val();
            }
        },
        columns: [
            { data: 'user_info', name: 'user_name', orderable: false },
            { data: 'event_info', name: 'event_type', orderable: false },
            { data: 'changes', name: 'changes', orderable: false },
            { data: 'context', name: 'context', orderable: false },
            { data: 'timestamp', name: 'created_at' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[4, 'desc']],
        pageLength: 25,
        responsive: true,
        dom: '<"flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4"<"mb-2 sm:mb-0"l><"sm:ml-auto"f>>rtip',
        language: {
            search: "Search logs:",
            lengthMenu: "Show _MENU_ entries per page",
            info: "Showing _START_ to _END_ of _TOTAL_ audit logs",
            infoEmpty: "No audit logs found",
            infoFiltered: "(filtered from _MAX_ total logs)",
            emptyTable: "No audit logs available"
        }
    });
}

function applyFilters() {
    if (auditTable) {
        auditTable.ajax.reload();
    }
}

function clearFilters() {
    $('#startDate').val('{{ now()->subDays(30)->format("Y-m-d") }}');
    $('#endDate').val('{{ now()->format("Y-m-d") }}');
    $('#eventTypeFilter').val('');
    $('#auditableTypeFilter').val('');
    $('#riskLevelFilter').val('');
    $('#userFilter').val('');
    applyFilters();
}

function refreshAuditLogs() {
    if (auditTable) {
        auditTable.ajax.reload(null, false);
    }
    updateStatistics();
    toastr.success('Audit logs refreshed');
}

function viewAuditDetails(auditId) {
    $.ajax({
        url: `/admin/audit/${auditId}`,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                displayAuditDetails(response.data);
                document.getElementById('auditDetailsModal').classList.remove('hidden');
            } else {
                toastr.error('Failed to load audit details');
            }
        },
        error: function() {
            toastr.error('Failed to load audit details');
        }
    });
}

function displayAuditDetails(audit) {
    let oldValues = '';
    let newValues = '';
    
    if (audit.old_values && Object.keys(audit.old_values).length > 0) {
        oldValues = '<pre class="bg-gray-100 p-3 rounded text-sm overflow-x-auto">' + JSON.stringify(audit.old_values, null, 2) + '</pre>';
    } else {
        oldValues = '<em class="text-gray-600">No previous values</em>';
    }
    
    if (audit.new_values && Object.keys(audit.new_values).length > 0) {
        newValues = '<pre class="bg-gray-100 p-3 rounded text-sm overflow-x-auto">' + JSON.stringify(audit.new_values, null, 2) + '</pre>';
    } else {
        newValues = '<em class="text-gray-600">No new values</em>';
    }
    
    const riskBadge = `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-${audit.risk_color}-100 text-${audit.risk_color}-800">${audit.risk_level.toUpperCase()}</span>`;
    const significantBadge = audit.has_significant_changes ? '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 ml-2">SENSITIVE</span>' : '';
    
    const html = `
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h6 class="text-sm font-semibold text-gray-900 mb-3">Basic Information</h6>
                <table class="min-w-full divide-y divide-gray-200">
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr><td class="px-3 py-2 text-sm font-medium text-gray-500">Event Type:</td><td class="px-3 py-2 text-sm text-gray-900">${audit.event_type}</td></tr>
                        <tr><td class="px-3 py-2 text-sm font-medium text-gray-500">Model:</td><td class="px-3 py-2 text-sm text-gray-900">${audit.model_name}</td></tr>
                        <tr><td class="px-3 py-2 text-sm font-medium text-gray-500">Model ID:</td><td class="px-3 py-2 text-sm text-gray-900">${audit.auditable_id || 'N/A'}</td></tr>
                        <tr><td class="px-3 py-2 text-sm font-medium text-gray-500">User:</td><td class="px-3 py-2 text-sm text-gray-900">${audit.user ? audit.user.name + ' (' + audit.user.email + ')' : 'System'}</td></tr>
                        <tr><td class="px-3 py-2 text-sm font-medium text-gray-500">Risk Level:</td><td class="px-3 py-2 text-sm text-gray-900">${riskBadge}${significantBadge}</td></tr>
                        <tr><td class="px-3 py-2 text-sm font-medium text-gray-500">Timestamp:</td><td class="px-3 py-2 text-sm text-gray-900">${audit.created_at}<br><small class="text-gray-600">${audit.created_at_human}</small></td></tr>
                    </tbody>
                </table>
            </div>
            <div>
                <h6 class="text-sm font-semibold text-gray-900 mb-3">Context Information</h6>
                <table class="min-w-full divide-y divide-gray-200">
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr><td class="px-3 py-2 text-sm font-medium text-gray-500">IP Address:</td><td class="px-3 py-2 text-sm text-gray-900">${audit.ip_address || 'N/A'}</td></tr>
                        <tr><td class="px-3 py-2 text-sm font-medium text-gray-500">URL:</td><td class="px-3 py-2 text-sm text-gray-900">${audit.url ? '<small>' + audit.url + '</small>' : 'N/A'}</td></tr>
                        <tr><td class="px-3 py-2 text-sm font-medium text-gray-500">User Agent:</td><td class="px-3 py-2 text-sm text-gray-900">${audit.user_agent ? '<small>' + audit.user_agent + '</small>' : 'N/A'}</td></tr>
                        <tr><td class="px-3 py-2 text-sm font-medium text-gray-500">Tags:</td><td class="px-3 py-2 text-sm text-gray-900">${audit.tags ? audit.tags.map(tag => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">' + tag + '</span>').join(' ') : 'None'}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="mt-6 pt-6 border-t border-gray-200">
            <h6 class="text-sm font-semibold text-gray-900 mb-3">Changes Summary</h6>
            <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-md">
                ${audit.changes_summary}
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
            <div>
                <h6 class="text-sm font-semibold text-gray-900 mb-3">Previous Values</h6>
                <div class="bg-gray-50 p-3 rounded">${oldValues}</div>
            </div>
            <div>
                <h6 class="text-sm font-semibold text-gray-900 mb-3">New Values</h6>
                <div class="bg-gray-50 p-3 rounded">${newValues}</div>
            </div>
        </div>
    `;
    
    $('#auditDetailsContent').html(html);
}

function exportAuditLogs(format) {
    if (format) {
        $('#exportForm [name="format"]').val(format);
        $('#exportForm [name="start_date"]').val($('#startDate').val());
        $('#exportForm [name="end_date"]').val($('#endDate').val());
        document.getElementById('exportModal').classList.remove('hidden');
    }
}

function performExport() {
    const formData = new FormData($('#exportForm')[0]);
    
    // Create download link
    const url = '{{ route("audit.export") }}?' + new URLSearchParams(formData).toString();
    window.open(url, '_blank');
    
    closeExportModal();
    toastr.success('Export started. Download will begin shortly.');
}

function cleanupAuditLogs() {
    document.getElementById('cleanupModal').classList.remove('hidden');
}

function performCleanup() {
    const formData = new FormData($('#cleanupForm')[0]);
    
    $.ajax({
        url: '{{ route("audit.cleanup") }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                closeCleanupModal();
                refreshAuditLogs();
            } else {
                toastr.error(response.message);
            }
        },
        error: function(xhr) {
            toastr.error(xhr.responseJSON?.message || 'Cleanup failed');
        }
    });
}

function updateStatistics() {
    $.ajax({
        url: '{{ route("audit.stats") }}',
        method: 'GET',
        data: {
            start_date: $('#startDate').val(),
            end_date: $('#endDate').val()
        },
        success: function(response) {
            if (response.success) {
                const stats = response.data;
                $('#totalEvents').text(stats.total_events);
                $('#uniqueUsers').text(stats.unique_users);
                $('#highRiskEvents').text(stats.high_risk_events);
                $('#todayEvents').text(stats.today_events);
            }
        }
    });
}

function closeModal() {
    document.getElementById('auditDetailsModal').classList.add('hidden');
}

function closeExportModal() {
    document.getElementById('exportModal').classList.add('hidden');
}

function closeCleanupModal() {
    document.getElementById('cleanupModal').classList.add('hidden');
}

// Close modals when clicking outside
document.querySelectorAll('[role="dialog"]').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.add('hidden');
        }
    });
});
</script>
@endpush