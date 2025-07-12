@extends('layouts.authenticated')

@section('title', 'My Leave Requests')

@section('page-content')
    <!-- Page Header -->
    <x-layouts.page-header 
        title="My Leave Requests"
        subtitle="View and manage your leave applications"
        :breadcrumbs="[
            ['label' => 'Home', 'url' => route('dashboard')],
            ['label' => 'Leave Management', 'url' => null]
        ]">
        <x-slot name="actions">
            <x-ui.button variant="outline" href="{{ route('leave.calendar') }}">
                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Calendar View
            </x-ui.button>
            <x-ui.button href="{{ route('leave.create') }}">
                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Request Leave
            </x-ui.button>
        </x-slot>
    </x-layouts.page-header>

    <!-- Leave Requests Table with Status Filters -->
    <x-ui.card>
        <x-slot name="title">Leave Request History</x-slot>
        <x-slot name="subtitle">Your leave application history and status</x-slot>
        
        <x-slot name="actions">
            <div class="flex items-center space-x-1 bg-muted rounded-lg p-1">
                <input type="radio" class="sr-only" name="status-filter" id="all" value="" checked>
                <label for="all" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md cursor-pointer transition-colors hover:bg-background data-[checked]:bg-background data-[checked]:text-foreground text-muted-foreground">
                    All
                </label>
                
                <input type="radio" class="sr-only" name="status-filter" id="pending" value="pending">
                <label for="pending" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md cursor-pointer transition-colors hover:bg-background text-muted-foreground">
                    Pending
                </label>
                
                <input type="radio" class="sr-only" name="status-filter" id="approved" value="approved">
                <label for="approved" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md cursor-pointer transition-colors hover:bg-background text-muted-foreground">
                    Approved
                </label>
                
                <input type="radio" class="sr-only" name="status-filter" id="rejected" value="rejected">
                <label for="rejected" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md cursor-pointer transition-colors hover:bg-background text-muted-foreground">
                    Rejected
                </label>
            </div>
        </x-slot>
        
        <x-ui.table id="leaveRequestsTable" :responsive="true" :striped="true">
            <x-ui.table.header>
                <x-ui.table.row>
                    <x-ui.table.cell tag="th" class="text-left">Leave Type</x-ui.table.cell>
                    <x-ui.table.cell tag="th" class="text-left">Dates</x-ui.table.cell>
                    <x-ui.table.cell tag="th" class="text-left">Duration</x-ui.table.cell>
                    <x-ui.table.cell tag="th" class="text-left">Status</x-ui.table.cell>
                    <x-ui.table.cell tag="th" class="text-left">Approved By</x-ui.table.cell>
                    <x-ui.table.cell tag="th" class="text-left">Submitted</x-ui.table.cell>
                    <x-ui.table.cell tag="th" class="text-left">Actions</x-ui.table.cell>
                </x-ui.table.row>
            </x-ui.table.header>
            <x-ui.table.body>
                <!-- DataTables will populate this via AJAX -->
            </x-ui.table.body>
        </x-ui.table>
    </x-ui.card>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize DataTable
    const table = $('#leaveRequestsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("leave.requests.data") }}',
            data: function(d) {
                d.status = $('input[name="status-filter"]:checked').val();
            }
        },
        columns: [
            { data: 'leave_type', name: 'leaveType.name' },
            { data: 'date_range', name: 'start_date' },
            { data: 'duration', name: 'days_requested' },
            { data: 'status_badge', name: 'status', orderable: false },
            { data: 'approver_name', name: 'approver.first_name', orderable: false },
            { data: 'created_at', name: 'created_at' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[5, 'desc']] // Order by created_at desc
    });
    
    // Status filter
    $('input[name="status-filter"]').on('change', function() {
        // Update label styles
        $('input[name="status-filter"]').each(function() {
            const label = $('label[for="' + this.id + '"]');
            if (this.checked) {
                label.removeClass('bg-white').addClass('bg-blue-100');
            } else {
                label.removeClass('bg-blue-100').addClass('bg-white');
            }
        });
        table.ajax.reload();
    });
    
    // Cancel leave request
    $(document).on('click', '.cancel-leave', function() {
        const leaveId = $(this).data('id');
        
        if (confirm('Are you sure you want to cancel this leave request? This action cannot be undone.')) {
            $.ajax({
                url: `/leave/${leaveId}/cancel`,
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        table.ajax.reload();
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON?.message || 'An error occurred');
                }
            });
        }
    });
});
</script>
@endpush