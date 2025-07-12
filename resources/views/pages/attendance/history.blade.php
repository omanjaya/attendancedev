@extends('layouts.authenticated')

@section('title', 'Attendance History')

@section('page-content')
    <!-- Breadcrumb -->
    <nav class="mb-8" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-2 text-sm">
            <li>
                <a href="{{ route('dashboard') }}" class="text-muted-foreground hover:text-foreground transition-colors">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                </a>
            </li>
            <li class="flex items-center">
                <svg class="h-5 w-5 text-muted-foreground mx-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                </svg>
                <a href="{{ route('attendance.index') }}" class="text-muted-foreground hover:text-foreground transition-colors">Attendance</a>
            </li>
            <li class="flex items-center">
                <svg class="h-5 w-5 text-muted-foreground mx-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                </svg>
                <span class="text-foreground font-medium">History</span>
            </li>
        </ol>
    </nav>
    
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-foreground">My Attendance History</h1>
                <p class="text-muted-foreground mt-1">Track your daily attendance and working hours</p>
            </div>
            
            <div class="flex items-center space-x-3">
                <x-ui.button variant="outline" onclick="openFilterModal()">
                    <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    Filter
                </x-ui.button>
                <x-ui.button onclick="exportAttendance()">
                    <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Export
                </x-ui.button>
            </div>
        </div>
    </div>
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <x-attendance.stats-card
            title="Present Days"
            :value="'--'"
            subtitle="This period"
            icon="ti ti-check"
            color="success"
            id="stat-present" />
        
        <x-attendance.stats-card
            title="Late Arrivals"
            :value="'--'"
            subtitle="Days late"
            icon="ti ti-clock"
            color="warning"
            id="stat-late" />
        
        <x-attendance.stats-card
            title="Total Hours"
            :value="'--'"
            subtitle="Working time"
            icon="ti ti-clock-hour-4"
            color="primary"
            id="stat-hours" />
        
        <x-attendance.stats-card
            title="Attendance Rate"
            :value="'--'"
            subtitle="Percentage"
            icon="ti ti-percentage"
            color="info"
            id="stat-rate" />
    </div>

    <x-ui.card>
        <x-slot name="title">
            <div class="flex items-center justify-between">
                <span>My Attendance Records</span>
                <x-ui.button variant="outline" size="sm" onclick="refreshAttendanceData()" title="Refresh">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </x-ui.button>
            </div>
        </x-slot>
        <x-slot name="subtitle">Track your daily attendance and working hours</x-slot>
    
        <div class="overflow-x-auto">
            <table id="attendanceTable" class="min-w-full">
                <thead>
                    <tr class="border-b border-border">
                        <th class="px-6 py-3 text-left text-sm font-medium text-muted-foreground">Date</th>
                        @can('view_all_attendance')
                        <th class="px-6 py-3 text-left text-sm font-medium text-muted-foreground">Employee</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-muted-foreground">Employee ID</th>
                        @endcan
                        <th class="px-6 py-3 text-left text-sm font-medium text-muted-foreground">Check In</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-muted-foreground">Check Out</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-muted-foreground">Total Hours</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-muted-foreground">Status</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-muted-foreground">Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </x-ui.card>

<!-- Filter Modal -->
<div id="filterModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="fixed inset-0 bg-background/80 backdrop-blur-sm transition-opacity" aria-hidden="true"></div>
        <div class="relative bg-card rounded-xl shadow-xl w-full max-w-2xl p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-foreground">Filter Attendance</h3>
                <button type="button" class="text-muted-foreground hover:text-foreground transition-colors" onclick="closeFilterModal()">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form id="filterForm" class="space-y-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <x-ui.label for="start_date" value="Start Date" />
                        <x-ui.input type="date" name="start_date" id="start_date" value="{{ now()->startOfMonth()->format('Y-m-d') }}" />
                    </div>
                    
                    <div class="space-y-2">
                        <x-ui.label for="end_date" value="End Date" />
                        <x-ui.input type="date" name="end_date" id="end_date" value="{{ now()->format('Y-m-d') }}" />
                    </div>
                </div>
                
                @can('view_all_attendance')
                <div class="space-y-2">
                    <x-ui.label for="employee_id" value="Employee" />
                    <x-ui.select name="employee_id" id="employee_id">
                        <option value="">All Employees</option>
                        <!-- Will be populated via AJAX -->
                    </x-ui.select>
                </div>
                @endcan
                
                <div class="space-y-2">
                    <x-ui.label for="status" value="Status" />
                    <x-ui.select name="status" id="status">
                        <option value="">All Statuses</option>
                        <option value="present">Present</option>
                        <option value="late">Late</option>
                        <option value="absent">Absent</option>
                        <option value="early_departure">Early Departure</option>
                        <option value="incomplete">Incomplete</option>
                    </x-ui.select>
                </div>
                
                <div class="flex flex-col sm:flex-row items-center justify-end gap-3">
                    <x-ui.button type="button" variant="outline" onclick="closeFilterModal()">
                        Cancel
                    </x-ui.button>
                    <x-ui.button type="submit">
                        Apply Filter
                    </x-ui.button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Attendance Details Modal -->
<div id="detailsModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="flex items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:ml-0 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modal-title">Attendance Details</h3>
                        <div id="attendance-details">
                            <!-- Content loaded dynamically -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:w-auto sm:text-sm" onclick="closeDetailsModal()">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Manual Checkout Modal -->
<div id="manualCheckoutModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
            <form id="manualCheckoutForm">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:ml-0 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modal-title">Manual Check-out</h3>
            <form id="manualCheckoutForm">
                <div class="modal-body">
                    <input type="hidden" id="manual-attendance-id">
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">Check-out Time</label>
                        <input type="datetime-local" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" name="check_out_time" required>
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">Notes</label>
                        <textarea class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" name="notes" rows="3" placeholder="Reason for manual check-out"></textarea>
                    </div>
                </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-yellow-600 text-base font-medium text-white hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 sm:ml-3 sm:w-auto sm:text-sm">Complete Check-out</button>
                    <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" onclick="closeManualCheckoutModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let attendanceTable;
let currentFilters = {};

$(document).ready(function() {
    // Initialize DataTable
    initializeAttendanceTable();
    
    // Load statistics
    loadStatistics();
    
    // Load employees for filter
    @can('view_all_attendance')
    loadEmployeesForFilter();
    @endcan
    
    // Filter form submission
    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        applyFilter();
    });
    
    // Manual checkout form
    $('#manualCheckoutForm').on('submit', function(e) {
        e.preventDefault();
        processManualCheckout();
    });
    
    // View details click
    $(document).on('click', '.view-details', function() {
        const attendanceId = $(this).data('id');
        loadAttendanceDetails(attendanceId);
    });
    
    // Manual checkout click
    $(document).on('click', '.manual-checkout', function() {
        const attendanceId = $(this).data('id');
        showManualCheckoutModal(attendanceId);
    });
});

function initializeAttendanceTable() {
    const columns = [
        { data: 'date_formatted', name: 'date' },
        @can('view_all_attendance')
        { data: 'employee_name', name: 'employee.first_name' },
        { data: 'employee_id', name: 'employee.employee_id' },
        @endcan
        { data: 'check_in_formatted', name: 'check_in_time' },
        { data: 'check_out_formatted', name: 'check_out_time' },
        { data: 'working_hours_formatted', name: 'total_hours' },
        { data: 'status_badge', name: 'status' },
        { data: 'actions', name: 'actions', orderable: false, searchable: false }
    ];

    attendanceTable = $('#attendanceTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '/api/v1/attendance/data',
            data: function(d) {
                // Add custom filters
                $.extend(d, currentFilters);
            }
        },
        columns: columns,
        order: [[0, 'desc']],
        pageLength: 25,
        language: {
            emptyTable: "No attendance records found."
        }
    });
}

function loadStatistics() {
    const startDate = new Date();
    startDate.setDate(1); // First day of month
    
    const params = {
        start_date: startDate.toISOString().split('T')[0],
        end_date: new Date().toISOString().split('T')[0]
    };
    
    @cannot('view_all_attendance')
    if (window.currentEmployee) {
        params.employee_id = window.currentEmployee.id;
    }
    @endcannot
    
    $.get('/api/v1/attendance/statistics', params)
        .done(function(response) {
            if (response.success) {
                const stats = response.statistics;
                $('#stat-present').text(stats.present_count);
                $('#stat-late').text(stats.late_count);
                $('#stat-hours').text(stats.total_hours + 'h');
                $('#stat-avg').text(stats.average_hours + 'h');
            }
        })
        .fail(function() {
            console.error('Failed to load statistics');
        });
}

@can('view_all_attendance')
function loadEmployeesForFilter() {
    // This would typically be an API call to get active employees
    // For now, we'll leave it empty and populate manually if needed
    console.log('Loading employees for filter...');
}
@endcan

function applyFilter() {
    currentFilters = {
        start_date: $('input[name="start_date"]').val(),
        end_date: $('input[name="end_date"]').val(),
        @can('view_all_attendance')
        employee_id: $('select[name="employee_id"]').val(),
        @endcan
        status: $('select[name="status"]').val()
    };
    
    // Remove empty filters
    Object.keys(currentFilters).forEach(key => {
        if (!currentFilters[key]) {
            delete currentFilters[key];
        }
    });
    
    // Reload table with new filters
    attendanceTable.ajax.reload();
    
    // Update statistics with new date range
    loadStatistics();
    
    // Close modal
    closeFilterModal();
    
    toastr.success('Filter applied successfully');
}

function loadAttendanceDetails(attendanceId) {
    $('#attendance-details').html('<div class="text-center py-4"><div class="inline-block animate-spin rounded-full h-8 w-8 border-2 border-primary border-t-transparent"></div></div>');
    document.getElementById('detailsModal').classList.remove('hidden');
    
    $.get(`/api/v1/attendance/${attendanceId}/details`)
        .done(function(response) {
            if (response.success) {
                displayAttendanceDetails(response.data);
            }
        })
        .fail(function() {
            $('#attendance-details').html('<div class="bg-destructive/10 border border-destructive/20 text-destructive px-4 py-3 rounded-lg">Failed to load attendance details.</div>');
        });
}

function displayAttendanceDetails(data) {
    const checkInTime = data.check_in_time ? new Date(data.check_in_time).toLocaleString() : 'Not checked in';
    const checkOutTime = data.check_out_time ? new Date(data.check_out_time).toLocaleString() : 'Not checked out';
    
    const html = `
        <div class="grid grid-cols-12 gap-4">
            <div class="md:col-span-6">
                <h4>Employee Information</h4>
                <table class="min-w-full divide-y divide-gray-200">
                    <tr><td class="py-2 pr-4 font-medium">Name:</td><td class="py-2">${data.employee.name}</td></tr>
                    <tr><td class="py-2 pr-4 font-medium">Employee ID:</td><td class="py-2">${data.employee.employee_id}</td></tr>
                    <tr><td class="py-2 pr-4 font-medium">Type:</td><td class="py-2">${data.employee.type}</td></tr>
                </table>
            </div>
            <div class="md:col-span-6">
                <h4>Attendance Details</h4>
                <table class="min-w-full divide-y divide-gray-200">
                    <tr><td class="py-2 pr-4 font-medium">Date:</td><td class="py-2">${data.date}</td></tr>
                    <tr><td class="py-2 pr-4 font-medium">Status:</td><td class="py-2"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-${getStatusColorClass(data.status)}-100 text-${getStatusColorClass(data.status)}-800">${data.status}</span></td></tr>
                    <tr><td class="py-2 pr-4 font-medium">Total Hours:</td><td class="py-2">${data.total_hours || 0}h</td></tr>
                </table>
            </div>
        </div>
        
        <div class="grid grid-cols-12 gap-4 mt-4">
            <div class="md:col-span-6">
                <h4>Check-in Details</h4>
                <table class="min-w-full divide-y divide-gray-200">
                    <tr><td class="py-2 pr-4 font-medium">Time:</td><td class="py-2">${checkInTime}</td></tr>
                    <tr><td class="py-2 pr-4 font-medium">Confidence:</td><td class="py-2">${data.check_in_confidence ? (data.check_in_confidence * 100).toFixed(1) + '%' : 'N/A'}</td></tr>
                    <tr><td class="py-2 pr-4 font-medium">Location Verified:</td><td class="py-2">${data.location_verified ? 'Yes' : 'No'}</td></tr>
                    ${data.check_in_notes ? `<tr><td class="py-2 pr-4 font-medium">Notes:</td><td class="py-2">${data.check_in_notes}</td></tr>` : ''}
                </table>
            </div>
            <div class="md:col-span-6">
                <h4>Check-out Details</h4>
                <table class="min-w-full divide-y divide-gray-200">
                    <tr><td class="py-2 pr-4 font-medium">Time:</td><td class="py-2">${checkOutTime}</td></tr>
                    <tr><td class="py-2 pr-4 font-medium">Confidence:</td><td class="py-2">${data.check_out_confidence ? (data.check_out_confidence * 100).toFixed(1) + '%' : 'N/A'}</td></tr>
                    ${data.check_out_notes ? `<tr><td class="py-2 pr-4 font-medium">Notes:</td><td class="py-2">${data.check_out_notes}</td></tr>` : ''}
                </table>
            </div>
        </div>
        
        ${data.metadata && Object.keys(data.metadata).length > 0 ? `
        <div class="grid grid-cols-12 gap-4 mt-4">
            <div class="col-span-12">
                <h4>Additional Information</h4>
                <pre class="bg-gray-100 p-3 rounded text-sm">${JSON.stringify(data.metadata, null, 2)}</pre>
            </div>
        </div>` : ''}
    `;
    
    $('#attendance-details').html(html);
}

function getStatusColorClass(status) {
    const colors = {
        'present': 'green',
        'absent': 'red',
        'late': 'yellow',
        'early_departure': 'blue',
        'incomplete': 'gray'
    };
    return colors[status] || 'gray';
}

function openFilterModal() {
    document.getElementById('filterModal').classList.remove('hidden');
}

function closeFilterModal() {
    document.getElementById('filterModal').classList.add('hidden');
}

function closeDetailsModal() {
    document.getElementById('detailsModal').classList.add('hidden');
}

function closeManualCheckoutModal() {
    document.getElementById('manualCheckoutModal').classList.add('hidden');
}

function showManualCheckoutModal(attendanceId) {
    $('#manual-attendance-id').val(attendanceId);
    
    // Set default checkout time to now
    const now = new Date();
    const localISOTime = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
    $('input[name="check_out_time"]').val(localISOTime);
    
    document.getElementById('manualCheckoutModal').classList.remove('hidden');
}

function processManualCheckout() {
    const attendanceId = $('#manual-attendance-id').val();
    const formData = {
        check_out_time: $('input[name="check_out_time"]').val(),
        notes: $('textarea[name="notes"]').val()
    };
    
    $.post(`/api/v1/attendance/${attendanceId}/manual-checkout`, formData)
        .done(function(response) {
            if (response.success) {
                closeManualCheckoutModal();
                attendanceTable.ajax.reload();
                toastr.success(response.message);
            }
        })
        .fail(function(xhr) {
            const error = xhr.responseJSON;
            toastr.error(error.message || 'Manual checkout failed');
        });
}

function exportAttendance() {
    // Prepare export parameters
    const params = new URLSearchParams(currentFilters);
    const exportUrl = `/api/v1/attendance/export?${params.toString()}`;
    
    // Create temporary link and download
    const link = document.createElement('a');
    link.href = exportUrl;
    link.download = `attendance_${new Date().toISOString().split('T')[0]}.xlsx`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    toastr.info('Export started. Download will begin shortly.');
}
</script>
@endpush