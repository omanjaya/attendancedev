@extends('layouts.authenticated-unified')

@section('title', 'Review Leave Request')

@section('page-header')
@section('page-pretitle', 'Leave Approvals')
@section('page-title', 'Review Leave Request #{{ substr($leave->id, 0, 8) }}')
@section('page-actions')
    @if($leave->isPending())
        <div class="flex items-center space-x-2">
            <button class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-150" id="approve-request" data-id="{{ $leave->id }}">
                <svg class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M5 12l5 5l10 -10"/>
                </svg>
                Approve Request
            </button>
            <button class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition duration-150" id="reject-request" data-id="{{ $leave->id }}">
                <svg class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
                Reject Request
            </button>
        </div>
    @endif
    <a href="{{ route('leave.approvals') }}" class="inline-flex items-center px-4 py-2 border border-blue-300 text-sm font-medium rounded-md text-blue-700 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
        <svg class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
            <path d="M15 6l-6 6l6 6"/>
        </svg>
        Back to Approvals
    </a>
@endsection
@endsection

@section('page-content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Leave Request Details -->
    <div class="lg:col-span-8">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200-header">
                <h3 class="bg-white rounded-lg shadow-sm border border-gray-200-title">Leave Request Details</h3>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200-actions">
                    <span class="badge bg-{{ $leave->status_color }} fs-3">{{ ucfirst($leave->status) }}</span>
                    @if($leave->is_emergency)
                        <span class="badge bg-red ml-2">Emergency</span>
                    @endif
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200-body">
                <div class="grid grid-cols-12 gap-4">
                    <div class="md:col-span-6">
                        <div class="mb-3">
                            <label class="block text-sm font-medium text-gray-700">Employee</label>
                            <div class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm-plaintext">
                                <div class="flex items-center">
                                    <img src="{{ $leave->employee->photo_url }}" alt="{{ $leave->employee->full_name }}" 
                                         class="avatar avatar-md mr-3">
                                    <div>
                                        <strong class="fs-4">{{ $leave->employee->full_name }}</strong>
                                        <div class="text-gray-600">{{ $leave->employee->employee_id }}</div>
                                        <div class="text-gray-600 small">{{ $leave->employee->user->email }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="block text-sm font-medium text-gray-700">Leave Type</label>
                            <div class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm-plaintext">
                                <span class="badge bg-blue-lt fs-4">{{ $leave->leaveType->name }}</span>
                                @if($leave->leaveType->is_paid)
                                    <span class="badge bg-green-lt ms-1">Paid</span>
                                @else
                                    <span class="badge bg-yellow-lt ms-1">Unpaid</span>
                                @endif
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="block text-sm font-medium text-gray-700">Duration</label>
                            <div class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm-plaintext">
                                <div class="flex items-center">
                                    <svg class="icon text-gray-600 mr-2" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <circle cx="12" cy="12" r="9"/>
                                        <polyline points="12,7 12,12 15,15"/>
                                    </svg>
                                    <strong class="fs-4">{{ $leave->duration }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="md:col-span-6">
                        <div class="mb-3">
                            <label class="block text-sm font-medium text-gray-700">Leave Dates</label>
                            <div class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm-plaintext">
                                <div class="flex items-center">
                                    <svg class="icon text-gray-600 mr-2" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <rect x="4" y="5" width="16" height="16" rx="2"/>
                                        <line x1="16" y1="3" x2="16" y2="7"/>
                                        <line x1="8" y1="3" x2="8" y2="7"/>
                                        <line x1="4" y1="11" x2="20" y2="11"/>
                                    </svg>
                                    <strong>{{ $leave->date_range }}</strong>
                                </div>
                                @if($leave->start_date <= now())
                                    <small class="text-warning">Leave has already started</small>
                                @elseif($leave->start_date <= now()->addDays(3))
                                    <small class="text-info">Leave starts soon</small>
                                @endif
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="block text-sm font-medium text-gray-700">Request Submitted</label>
                            <div class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm-plaintext">
                                {{ $leave->created_at->format('M j, Y g:i A') }}
                                <small class="text-gray-600 block">{{ $leave->created_at->diffForHumans() }}</small>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="block text-sm font-medium text-gray-700">Working Days</label>
                            <div class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm-plaintext">
                                <strong class="fs-4">{{ $leave->days_requested }} days</strong>
                                <small class="text-gray-600 block">
                                    @if($leave->days_requested === 1)
                                        Single day leave
                                    @else
                                        {{ $leave->days_requested }} working days requested
                                    @endif
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                
                @if($leave->reason)
                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-12">
                        <div class="mb-3">
                            <label class="block text-sm font-medium text-gray-700">Reason for Leave</label>
                            <div class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm-plaintext bg-light p-3 rounded">
                                {{ $leave->reason }}
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                
                @if($leave->approval_notes && $leave->isApproved())
                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-12">
                        <div class="mb-3">
                            <label class="block text-sm font-medium text-gray-700">Approval Notes</label>
                            <div class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm-plaintext bg-green-lt p-3 rounded">
                                {{ $leave->approval_notes }}
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                
                @if($leave->rejection_reason && $leave->isRejected())
                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-12">
                        <div class="mb-3">
                            <label class="block text-sm font-medium text-gray-700">Rejection Reason</label>
                            <div class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm-plaintext bg-red-lt p-3 rounded">
                                {{ $leave->rejection_reason }}
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="lg:col-span-4">
        <!-- Leave Balance Information -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200-header">
                <h3 class="bg-white rounded-lg shadow-sm border border-gray-200-title">Leave Balance</h3>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200-body">
                @if($leaveBalance)
                    <div class="grid grid-cols-12 gap-4">
                        <div class="w-1/2 px-2">
                            <div class="text-center">
                                <div class="h1 m-0 text-primary">{{ $leaveBalance->allocated_days }}</div>
                                <div class="text-gray-600">Allocated</div>
                            </div>
                        </div>
                        <div class="w-1/2 px-2">
                            <div class="text-center">
                                <div class="h1 m-0 {{ $leaveBalance->remaining_days >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $leaveBalance->remaining_days }}
                                </div>
                                <div class="text-gray-600">Remaining</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="progress mt-4">
                        @php
                            $usedPercentage = $leaveBalance->allocated_days > 0 ? 
                                (($leaveBalance->allocated_days - $leaveBalance->remaining_days) / $leaveBalance->allocated_days) * 100 : 0;
                        @endphp
                        <div class="progress-bar" style="width: {{ $usedPercentage }}%"></div>
                    </div>
                    <div class="text-gray-600 small mt-1">
                        {{ $leaveBalance->allocated_days - $leaveBalance->remaining_days }} days used
                    </div>
                    
                    @if($leave->isPending())
                        <div class="mt-4 p-2 bg-warning-lt rounded">
                            <div class="text-center">
                                <strong>After Approval:</strong><br>
                                <span class="{{ $leaveBalance->remaining_days >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $leaveBalance->remaining_days }} days remaining
                                </span>
                            </div>
                        </div>
                    @endif
                @else
                    <div class="text-gray-600 text-center py-3">
                        <svg class="icon icon-lg mb-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M12 9v2m0 4v.01"/>
                            <path d="M5 19h14a2 2 0 0 0 1.84 -2.75l-7.1 -12.25a2 2 0 0 0 -3.5 0l-7.1 12.25a2 2 0 0 0 1.75 2.75"/>
                        </svg>
                        <div>No balance information available</div>
                    </div>
                @endif
            </div>
        </div>
        
        <!-- Employee Leave History -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mt-4">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200-header">
                <h3 class="bg-white rounded-lg shadow-sm border border-gray-200-title">Recent Leave History</h3>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200-body">
                @if($recentLeaves->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($recentLeaves as $recentLeave)
                            <div class="flex justify-between items-center">
                                <div>
                                    <div class="font-medium">{{ $recentLeave->leaveType->name }}</div>
                                    <div class="text-gray-600 small">{{ $recentLeave->date_range }}</div>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-{{ $recentLeave->status_color }}">{{ ucfirst($recentLeave->status) }}</span>
                                    <div class="text-gray-600 small">{{ $recentLeave->days_requested }} days</div>
                                </div>
                            </div>
                            @if(!$loop->last)
                                <div class="hr-text hr-text-spaceless"></div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <div class="text-gray-600 text-center py-3">
                        <svg class="icon icon-lg mb-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <rect x="4" y="5" width="16" height="16" rx="2"/>
                            <line x1="16" y1="3" x2="16" y2="7"/>
                            <line x1="8" y1="3" x2="8" y2="7"/>
                            <line x1="4" y1="11" x2="20" y2="11"/>
                        </svg>
                        <div>No recent leave history</div>
                    </div>
                @endif
            </div>
        </div>
        
        <!-- Approval Actions -->
        @if($leave->isPending())
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mt-4">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200-header">
                <h3 class="bg-white rounded-lg shadow-sm border border-gray-200-title">Approval Actions</h3>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200-body">
                <div class="d-grid gap-2">
                    <button class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-150" id="approve-btn" data-id="{{ $leave->id }}">
                        <svg class="icon mr-2" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M5 12l5 5l10 -10"/>
                        </svg>
                        Approve Request
                    </button>
                    <button class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition duration-150" id="reject-btn" data-id="{{ $leave->id }}">
                        <svg class="icon mr-2" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                        Reject Request
                    </button>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Quick Approval Modal -->
<div class="modal fade" id="quickApprovalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Approve Leave Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="grid grid-cols-12 gap-4">
                        <div class="col-sm-3">
                            <strong>Employee:</strong>
                        </div>
                        <div class="col-sm-9">
                            {{ $leave->employee->full_name }}
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="grid grid-cols-12 gap-4">
                        <div class="col-sm-3">
                            <strong>Leave Type:</strong>
                        </div>
                        <div class="col-sm-9">
                            {{ $leave->leaveType->name }}
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="grid grid-cols-12 gap-4">
                        <div class="col-sm-3">
                            <strong>Duration:</strong>
                        </div>
                        <div class="col-sm-9">
                            {{ $leave->duration }}
                        </div>
                    </div>
                </div>
                <form id="approvalForm">
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
                <div class="mb-3">
                    <div class="grid grid-cols-12 gap-4">
                        <div class="col-sm-3">
                            <strong>Employee:</strong>
                        </div>
                        <div class="col-sm-9">
                            {{ $leave->employee->full_name }}
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="grid grid-cols-12 gap-4">
                        <div class="col-sm-3">
                            <strong>Leave Type:</strong>
                        </div>
                        <div class="col-sm-9">
                            {{ $leave->leaveType->name }}
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="grid grid-cols-12 gap-4">
                        <div class="col-sm-3">
                            <strong>Duration:</strong>
                        </div>
                        <div class="col-sm-9">
                            {{ $leave->duration }}
                        </div>
                    </div>
                </div>
                <form id="rejectionForm">
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
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Approve request buttons
    $('#approve-request, #approve-btn').on('click', function() {
        $('#approvalNotes').val('');
        $('#quickApprovalModal').modal('show');
    });
    
    // Reject request buttons
    $('#reject-request, #reject-btn').on('click', function() {
        $('#rejectionReason').val('');
        $('#quickRejectionModal').modal('show');
    });
    
    // Confirm approval
    $('#confirmApproval').on('click', function() {
        const leaveId = '{{ $leave->id }}';
        const notes = $('#approvalNotes').val();
        
        approveLeave(leaveId, notes);
    });
    
    // Confirm rejection
    $('#confirmRejection').on('click', function() {
        const leaveId = '{{ $leave->id }}';
        const reason = $('#rejectionReason').val();
        
        if (!reason.trim()) {
            toastr.error('Please provide a rejection reason');
            return;
        }
        
        rejectLeave(leaveId, reason);
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
                    $('#quickApprovalModal').modal('hide');
                    // Reload page to show updated status
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
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
                    $('#quickRejectionModal').modal('hide');
                    // Reload page to show updated status
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
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