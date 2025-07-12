@extends('layouts.authenticated')

@section('title', 'Leave Approvals')

@section('page-header')
@section('page-pretitle', 'Leave Management')
@section('page-title', 'Leave Approvals')
@section('page-actions')
    <div class="flex items-center space-x-2">
        <a href="{{ route('leave.calendar.manager') }}" class="btn btn-outline-info">
            <svg class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                <path d="M4 5m0 2a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2z"/>
                <path d="M16 3l0 4"/>
                <path d="M8 3l0 4"/>
                <path d="M4 11l16 0"/>
            </svg>
            Calendar View
        </a>
        <button class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-150" id="bulk-approve-btn" disabled>
            <svg class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                <path d="M5 12l5 5l10 -10"/>
            </svg>
            Bulk Approve
        </button>
        <a href="{{ route('leave.analytics') }}" class="inline-flex items-center px-4 py-2 border border-blue-300 text-sm font-medium rounded-md text-blue-700 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <svg class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"/>
                <path d="M9 12l2 2l4 -4"/>
            </svg>
            Analytics
        </a>
    </div>
@endsection
@endsection

@section('page-content')
<!-- Statistics Cards -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <div class="sm:w-1/2 px-2 lg:w-1/4 px-2">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 bg-white rounded-lg shadow-sm border border-gray-200-sm">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200-body">
                <div class="grid grid-cols-12 gap-4 items-center">
                    <div class="col-auto">
                        <span class="bg-warning text-white avatar">
                            <svg class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <circle cx="12" cy="12" r="9"/>
                                <polyline points="12,7 12,12 15,15"/>
                            </svg>
                        </span>
                    </div>
                    <div class="col">
                        <div class="font-medium">
                            {{ $stats['pending_requests'] }} Pending Requests
                        </div>
                        <div class="text-gray-600">
                            Awaiting your approval
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="sm:w-1/2 px-2 lg:w-1/4 px-2">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 bg-white rounded-lg shadow-sm border border-gray-200-sm">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200-body">
                <div class="grid grid-cols-12 gap-4 items-center">
                    <div class="col-auto">
                        <span class="bg-red text-white avatar">
                            <svg class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M12 9v2m0 4v.01"/>
                                <path d="M5 19h14a2 2 0 0 0 1.84 -2.75l-7.1 -12.25a2 2 0 0 0 -3.5 0l-7.1 12.25a2 2 0 0 0 1.75 2.75"/>
                            </svg>
                        </span>
                    </div>
                    <div class="col">
                        <div class="font-medium">
                            {{ $stats['emergency_requests'] }} Emergency
                        </div>
                        <div class="text-gray-600">
                            High priority requests
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="sm:w-1/2 px-2 lg:w-1/4 px-2">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 bg-white rounded-lg shadow-sm border border-gray-200-sm">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200-body">
                <div class="grid grid-cols-12 gap-4 items-center">
                    <div class="col-auto">
                        <span class="bg-success text-white avatar">
                            <svg class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M5 12l5 5l10 -10"/>
                            </svg>
                        </span>
                    </div>
                    <div class="col">
                        <div class="font-medium">
                            {{ $stats['approved_this_month'] }} Approved
                        </div>
                        <div class="text-gray-600">
                            This month
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="sm:w-1/2 px-2 lg:w-1/4 px-2">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 bg-white rounded-lg shadow-sm border border-gray-200-sm">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200-body">
                <div class="grid grid-cols-12 gap-4 items-center">
                    <div class="col-auto">
                        <span class="bg-blue text-white avatar">
                            <svg class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <rect x="4" y="5" width="16" height="16" rx="2"/>
                                <line x1="16" y1="3" x2="16" y2="7"/>
                                <line x1="8" y1="3" x2="8" y2="7"/>
                                <line x1="4" y1="11" x2="20" y2="11"/>
                            </svg>
                        </span>
                    </div>
                    <div class="col">
                        <div class="font-medium">
                            {{ $stats['total_this_month'] }} Total Requests
                        </div>
                        <div class="text-gray-600">
                            This month
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="col-span-12">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200-header">
                <h3 class="bg-white rounded-lg shadow-sm border border-gray-200-title">Leave Requests for Approval</h3>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200-actions">
                    <div class="inline-flex rounded-md shadow-sm" role="group">
                        <input type="radio" class="btn-check" name="status-filter" id="all" value="" checked>
                        <label for="all" class="btn px-3 py-1.5 text-xs btn-outline-primary">All</label>
                        
                        <input type="radio" class="btn-check" name="status-filter" id="pending" value="pending">
                        <label for="pending" class="btn px-3 py-1.5 text-xs btn-outline-warning">Pending</label>
                        
                        <input type="radio" class="btn-check" name="status-filter" id="approved" value="approved">
                        <label for="approved" class="btn px-3 py-1.5 text-xs btn-outline-success">Approved</label>
                        
                        <input type="radio" class="btn-check" name="status-filter" id="rejected" value="rejected">
                        <label for="rejected" class="btn px-3 py-1.5 text-xs btn-outline-danger">Rejected</label>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200-body">
                <div class="overflow-x-auto">
                    <table id="approvalsTable" class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th>
                                    <input type="checkbox" id="select-all" class="flex items-center-input">
                                </th>
                                <th>Employee</th>
                                <th>Leave Details</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Submitted</th>
                                <th class="w-1">Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Approval Modal -->
<div class="modal fade" id="quickApprovalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Quick Approval</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="quickApprovalForm">
                    <input type="hidden" id="approvalLeaveId">
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">Approval Notes (Optional)</label>
                        <textarea class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="approvalNotes" rows="3" 
                                placeholder="Add any notes about this approval..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-150" id="confirmApproval">
                    <svg class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M5 12l5 5l10 -10"/>
                    </svg>
                    Approve Request
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Quick Rejection Modal -->
<div class="modal fade" id="quickRejectionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Leave Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="quickRejectionForm">
                    <input type="hidden" id="rejectionLeaveId">
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 required">Rejection Reason</label>
                        <textarea class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="rejectionReason" rows="3" required
                                placeholder="Please provide a reason for rejecting this request..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition duration-150" id="confirmRejection">
                    <svg class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                    Reject Request
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Approval Modal -->
<div class="modal fade" id="bulkApprovalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bulk Approve Requests</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>You are about to approve <span id="bulkCount">0</span> leave request(s).</p>
                <form id="bulkApprovalForm">
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">Approval Notes (Optional)</label>
                        <textarea class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="bulkApprovalNotes" rows="3" 
                                placeholder="Add notes that will apply to all approved requests..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-150" id="confirmBulkApproval">
                    <svg class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M5 12l5 5l10 -10"/>
                    </svg>
                    Approve All Selected
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Modal helper functions
function showModal(modalId) {
    document.getElementById(modalId).classList.remove('hidden');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}

$(document).ready(function() {
    // Initialize DataTable
    const table = $('#approvalsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("leave.approvals.data") }}',
            data: function(d) {
                d.status = $('input[name="status-filter"]:checked').val();
            }
        },
        columns: [
            { 
                data: null, 
                orderable: false, 
                searchable: false,
                width: '30px',
                render: function(data, type, grid grid-cols-12 gap-4) {
                    if (grid grid-cols-12 gap-4.status === 'pending') {
                        return '<input type="checkbox" class="flex items-center-input grid grid-cols-12 gap-4-checkbox" value="' + grid grid-cols-12 gap-4.id + '">';
                    }
                    return '';
                }
            },
            { data: 'employee_info', name: 'employee.first_name' },
            { data: 'leave_details', name: 'leave_type_id' },
            { data: 'priority', name: 'priority', orderable: false },
            { data: 'status_badge', name: 'status', orderable: false },
            { data: 'submitted_date', name: 'created_at' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[5, 'desc']] // Order by submitted date desc
    });
    
    // Status filter
    $('input[name="status-filter"]').on('change', function() {
        table.ajax.reload();
    });
    
    // Select all checkbox
    $('#select-all').on('change', function() {
        $('.grid grid-cols-12 gap-4-checkbox').prop('checked', this.checked);
        updateBulkApproveButton();
    });
    
    // Individual checkbox change
    $(document).on('change', '.grid grid-cols-12 gap-4-checkbox', function() {
        updateBulkApproveButton();
        
        // Update select all checkbox
        const allChecked = $('.grid grid-cols-12 gap-4-checkbox').length === $('.grid grid-cols-12 gap-4-checkbox:checked').length;
        $('#select-all').prop('checked', allChecked);
    });
    
    // Update bulk approve button state
    function updateBulkApproveButton() {
        const checkedCount = $('.grid grid-cols-12 gap-4-checkbox:checked').length;
        $('#bulk-approve-btn').prop('disabled', checkedCount === 0);
        $('#bulkCount').text(checkedCount);
    }
    
    // Quick approve
    $(document).on('click', '.approve-leave', function() {
        const leaveId = $(this).data('id');
        $('#approvalLeaveId').val(leaveId);
        $('#approvalNotes').val('');
        showModal('quickApprovalModal');
    });
    
    // Quick reject
    $(document).on('click', '.reject-leave', function() {
        const leaveId = $(this).data('id');
        $('#rejectionLeaveId').val(leaveId);
        $('#rejectionReason').val('');
        showModal('quickRejectionModal');
    });
    
    // Bulk approve
    $('#bulk-approve-btn').on('click', function() {
        if ($('.grid grid-cols-12 gap-4-checkbox:checked').length > 0) {
            showModal('bulkApprovalModal');
        }
    });
    
    // Confirm approval
    $('#confirmApproval').on('click', function() {
        const leaveId = $('#approvalLeaveId').val();
        const notes = $('#approvalNotes').val();
        
        approveLeave(leaveId, notes);
    });
    
    // Confirm rejection
    $('#confirmRejection').on('click', function() {
        const leaveId = $('#rejectionLeaveId').val();
        const reason = $('#rejectionReason').val();
        
        if (!reason.trim()) {
            toastr.error('Please provide a rejection reason');
            return;
        }
        
        rejectLeave(leaveId, reason);
    });
    
    // Confirm bulk approval
    $('#confirmBulkApproval').on('click', function() {
        const leaveIds = $('.grid grid-cols-12 gap-4-checkbox:checked').map(function() {
            return this.value;
        }).get();
        const notes = $('#bulkApprovalNotes').val();
        
        bulkApproveLeaves(leaveIds, notes);
    });
    
    // Approve leave function
    function approveLeave(leaveId, notes) {
        $.ajax({
            url: `/leave/approvals/${leaveId}/approve`,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                approval_notes: notes
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    closeModal('quickApprovalModal');
                    table.ajax.reload();
                    updateBulkApproveButton();
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'An error occurred');
            }
        });
    }
    
    // Reject leave function
    function rejectLeave(leaveId, reason) {
        $.ajax({
            url: `/leave/approvals/${leaveId}/reject`,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                rejection_reason: reason
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    closeModal('quickRejectionModal');
                    table.ajax.reload();
                    updateBulkApproveButton();
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'An error occurred');
            }
        });
    }
    
    // Bulk approve function
    function bulkApproveLeaves(leaveIds, notes) {
        $.ajax({
            url: '/leave/approvals/bulk-approve',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                leave_ids: leaveIds,
                approval_notes: notes
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    closeModal('bulkApprovalModal');
                    table.ajax.reload();
                    $('.grid grid-cols-12 gap-4-checkbox').prop('checked', false);
                    $('#select-all').prop('checked', false);
                    updateBulkApproveButton();
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
</script>
@endpush