@extends('layouts.authenticated')

@section('title', 'Leave Calendar')


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
                <a href="{{ route('leave.index') }}" class="text-muted-foreground hover:text-foreground transition-colors">Leave</a>
            </li>
            <li class="flex items-center">
                <svg class="h-5 w-5 text-muted-foreground mx-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                </svg>
                <span class="text-foreground font-medium">Calendar</span>
            </li>
        </ol>
    </nav>
    
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-foreground">Leave Calendar</h1>
                <p class="text-muted-foreground mt-1">Visual overview of your leave requests</p>
            </div>
            
            <div class="flex flex-wrap items-center gap-3">
                <x-ui.button variant="outline" href="{{ route('leave.index') }}">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v8a2 2 0 002 2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Manage Requests
                </x-ui.button>
                <x-ui.button href="{{ route('leave.create') }}">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Request Leave
                </x-ui.button>
                @can('approve_leave')
                <x-ui.button variant="default" href="{{ route('leave.calendar.manager') }}" class="bg-success hover:bg-success/90 text-success-foreground">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l5 5 5-5"/>
                    </svg>
                    Manager View
                </x-ui.button>
                @endcan
            </div>
        </div>
    </div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <x-attendance.stats-card
        title="Total Leaves"
        :value="'-'"
        subtitle="All requests"
        icon="ti ti-calendar"
        color="primary"
        id="total-leaves" />
    <x-attendance.stats-card
        title="Approved"
        :value="'-'"
        subtitle="Approved leaves"
        icon="ti ti-check"
        color="success"
        id="approved-leaves" />
    <x-attendance.stats-card
        title="Pending"
        :value="'-'"
        subtitle="Awaiting approval"
        icon="ti ti-clock"
        color="warning"
        id="pending-leaves" />
    <x-attendance.stats-card
        title="Active Now"
        :value="'-'"
        subtitle="Currently on leave"
        icon="ti ti-user-check"
        color="info"
        id="active-leaves" />
</div>

<!-- Calendar -->
<x-ui.card>
    <x-slot name="title">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <span>My Leave Calendar</span>
                <p class="text-sm text-muted-foreground mt-1">Interactive calendar view of your leave requests</p>
            </div>
            
            <!-- Filters -->
            <div class="flex flex-col sm:flex-row items-end gap-4">
                <div class="space-y-2">
                    <x-ui.label for="leave-type-filter" value="Leave Type" />
                    <x-ui.select id="leave-type-filter">
                        <option value="">All Types</option>
                        @foreach($leaveTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                        @endforeach
                    </x-ui.select>
                </div>
                <div class="space-y-2">
                    <x-ui.label for="status-filter" value="Status" />
                    <x-ui.select id="status-filter">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="cancelled">Cancelled</option>
                    </x-ui.select>
                </div>
            </div>
        </div>
    </x-slot>
    <div class="space-y-6">
        <!-- Legend -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 p-4 bg-muted/30 rounded-lg">
            <div class="flex flex-wrap gap-4">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-warning"></div>
                    <span class="text-sm text-muted-foreground">Pending</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-success"></div>
                    <span class="text-sm text-muted-foreground">Approved</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-destructive"></div>
                    <span class="text-sm text-muted-foreground">Rejected</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-muted-foreground"></div>
                    <span class="text-sm text-muted-foreground">Cancelled</span>
                </div>
            </div>
            <p class="text-sm text-muted-foreground">Click on a leave to view details</p>
        </div>
        
        <!-- Calendar -->
        <div id="leave-calendar" class="[&_.fc]:text-foreground [&_.fc-theme-standard_td]:border-border [&_.fc-theme-standard_th]:border-border"></div>
    </div>
</x-ui.card>

<!-- Leave Details Modal -->
<div id="leaveDetailsModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="fixed inset-0 bg-background/80 backdrop-blur-sm transition-opacity" aria-hidden="true"></div>
        <div class="relative bg-card rounded-xl shadow-xl w-full max-w-2xl p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-foreground">Leave Details</h3>
                <button type="button" class="text-muted-foreground hover:text-foreground transition-colors" onclick="closeModal('leaveDetailsModal')">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div id="leave-details-content" class="mb-6">
                <!-- Content will be populated dynamically -->
            </div>
            
            <div class="flex flex-col sm:flex-row items-center justify-end gap-3">
                <x-ui.button type="button" variant="outline" onclick="closeModal('leaveDetailsModal')">
                    Close
                </x-ui.button>
                <div id="leave-actions" class="flex gap-2">
                    <!-- Action buttons will be populated dynamically -->
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<link href="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.8/main.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@fullcalendar/daygrid@6.1.8/main.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@fullcalendar/timegrid@6.1.8/main.min.css" rel="stylesheet">
<style>
    /* FullCalendar theming */
    .fc {
        color: hsl(var(--foreground));
    }
    .fc-theme-standard td,
    .fc-theme-standard th {
        border-color: hsl(var(--border));
    }
    .fc-toolbar {
        background: hsl(var(--muted) / 0.3);
        border-radius: 0.5rem;
        padding: 1rem;
        margin-bottom: 1rem;
    }
    .fc-button-primary {
        background-color: hsl(var(--primary));
        border-color: hsl(var(--primary));
        color: hsl(var(--primary-foreground));
    }
    .fc-button-primary:hover {
        background-color: hsl(var(--primary) / 0.9);
        border-color: hsl(var(--primary) / 0.9);
    }
    .fc-button-primary:not(:disabled):active {
        background-color: hsl(var(--primary) / 0.8);
        border-color: hsl(var(--primary) / 0.8);
    }
    .fc-event {
        cursor: pointer;
        border-radius: 0.375rem;
        border: 1px solid transparent;
    }
    .fc-event:hover {
        opacity: 0.8;
        transform: translateY(-1px);
        transition: all 0.2s ease;
    }
    .fc-daygrid-event {
        font-size: 0.75rem;
        padding: 2px 4px;
    }
    .fc-col-header {
        background-color: hsl(var(--muted) / 0.5);
    }
    .fc-day-today {
        background-color: hsl(var(--primary) / 0.1) !important;
    }
    .fc-daygrid-day:hover {
        background-color: hsl(var(--muted) / 0.3);
    }
</style>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.8/main.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/daygrid@6.1.8/main.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/timegrid@6.1.8/main.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/interaction@6.1.8/main.min.js"></script>

<script>
// Modal helper functions
function showModal(modalId) {
    document.getElementById(modalId).classList.remove('hidden');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}

document.addEventListener('DOMContentLoaded', function() {
    // Initialize calendar
    const calendarEl = document.getElementById('leave-calendar');
    let calendar;
    
    // Load stats
    loadLeaveStats();
    
    // Initialize calendar
    initializeCalendar();
    
    // Filter event handlers
    $('#leave-type-filter, #status-filter').on('change', function() {
        calendar.refetchEvents();
    });
    
    function initializeCalendar() {
        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,listWeek'
            },
            height: 'auto',
            weekends: true,
            businessHours: {
                daysOfWeek: [1, 2, 3, 4, 5], // Monday - Friday
                startTime: '08:00',
                endTime: '17:00',
            },
            events: function(fetchInfo, successCallback, failureCallback) {
                const filters = {
                    start: fetchInfo.startStr,
                    end: fetchInfo.endStr,
                    leave_type_id: $('#leave-type-filter').val(),
                    status: $('#status-filter').val()
                };
                
                $.ajax({
                    url: '{{ route("leave.calendar.data") }}',
                    type: 'GET',
                    data: filters,
                    success: function(data) {
                        successCallback(data);
                    },
                    error: function() {
                        failureCallback();
                        toastr.error('Failed to load leave data');
                    }
                });
            },
            eventClick: function(info) {
                showLeaveDetails(info.event.extendedProps.leave_id);
            },
            eventDidMount: function(info) {
                // Add tooltip
                const event = info.event;
                const props = event.extendedProps;
                info.el.setAttribute('title', 
                    `${props.leave_type_name}\n` +
                    `${props.date_range}\n` +
                    `Status: ${props.status.charAt(0).toUpperCase() + props.status.slice(1)}\n` +
                    `Duration: ${props.duration}`
                );
            },
            dayHeaderFormat: { weekday: 'short' },
            displayEventTime: false,
            eventDisplay: 'block'
        });
        
        calendar.render();
    }
    
    function loadLeaveStats() {
        $.ajax({
            url: '{{ route("leave.calendar.stats") }}',
            type: 'GET',
            success: function(data) {
                $('#total-leaves').text(data.total_leaves);
                $('#approved-leaves').text(data.approved_leaves);
                $('#pending-leaves').text(data.pending_leaves);
                $('#active-leaves').text(data.active_leaves);
            },
            error: function() {
                console.error('Failed to load leave stats');
            }
        });
    }
    
    function showLeaveDetails(leaveId) {
        $.ajax({
            url: '{{ route("leave.calendar.details", ":id") }}'.replace(':id', leaveId),
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const leave = response.leave;
                    const content = `
                        <div class="grid grid-cols-12 gap-4">
                            <div class="md:col-span-6">
                                <dl class="grid grid-cols-12 gap-4">
                                    <dt class="w-5/12 px-2">Leave Type:</dt>
                                    <dd class="w-7/12 px-2">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">${leave.leave_type}</span>
                                    </dd>
                                    <dt class="w-5/12 px-2">Status:</dt>
                                    <dd class="w-7/12 px-2">
                                        <span class="badge bg-${leave.status_color}">${leave.status.charAt(0).toUpperCase() + leave.status.slice(1)}</span>
                                    </dd>
                                    <dt class="w-5/12 px-2">Duration:</dt>
                                    <dd class="w-7/12 px-2">${leave.duration}</dd>
                                    <dt class="w-5/12 px-2">Date Range:</dt>
                                    <dd class="w-7/12 px-2">${leave.date_range}</dd>
                                </dl>
                            </div>
                            <div class="md:col-span-6">
                                <dl class="grid grid-cols-12 gap-4">
                                    <dt class="w-5/12 px-2">Days Requested:</dt>
                                    <dd class="w-7/12 px-2">${leave.days_requested}</dd>
                                    ${leave.is_emergency ? '<dt class="w-5/12 px-2">Emergency:</dt><dd class="w-7/12 px-2"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Yes</span></dd>' : ''}
                                    ${leave.approved_by ? '<dt class="w-5/12 px-2">Approved By:</dt><dd class="w-7/12 px-2">' + leave.approved_by + '</dd>' : ''}
                                    ${leave.approved_at ? '<dt class="w-5/12 px-2">Approved At:</dt><dd class="w-7/12 px-2">' + leave.approved_at + '</dd>' : ''}
                                    <dt class="w-5/12 px-2">Requested:</dt>
                                    <dd class="w-7/12 px-2">${leave.created_at}</dd>
                                </dl>
                            </div>
                        </div>
                        
                        ${leave.reason ? '<div class="mt-4"><strong>Reason:</strong><br>' + leave.reason + '</div>' : ''}
                        ${leave.approval_notes ? '<div class="mt-4"><strong>Approval Notes:</strong><br>' + leave.approval_notes + '</div>' : ''}
                        ${leave.rejection_reason ? '<div class="mt-4"><strong>Rejection Reason:</strong><br>' + leave.rejection_reason + '</div>' : ''}
                    `;
                    
                    $('#leave-details-content').html(content);
                    
                    // Add cancel button if applicable
                    let actions = '';
                    if (leave.can_be_cancelled) {
                        actions += '<button type="button" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition duration-150" onclick="cancelLeave(' + leave.id + ')">Cancel Leave</button>';
                    }
                    actions += '<a href="{{ route("leave.show", ":id") }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">View Full Details</a>'.replace(':id', leave.id);
                    
                    $('#leave-actions').html(actions);
                    
                    showModal('leaveDetailsModal');
                } else {
                    toastr.error('Failed to load leave details');
                }
            },
            error: function() {
                toastr.error('Failed to load leave details');
            }
        });
    }
    
    // Auto-refresh calendar and stats every 5 minutes
    setInterval(function() {
        calendar.refetchEvents();
        loadLeaveStats();
    }, 300000);
});

function cancelLeave(leaveId) {
    if (confirm('Are you sure you want to cancel this leave request?')) {
        $.ajax({
            url: '{{ route("leave.cancel", ":id") }}'.replace(':id', leaveId),
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    toastr.success('Leave request cancelled successfully');
                    closeModal('leaveDetailsModal');
                    calendar.refetchEvents();
                    loadLeaveStats();
                } else {
                    toastr.error(response.message || 'Failed to cancel leave request');
                }
            },
            error: function() {
                toastr.error('Failed to cancel leave request');
            }
        });
    }
}
</script>
@endsection