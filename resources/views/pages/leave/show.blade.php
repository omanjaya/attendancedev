@extends('layouts.authenticated')

@section('title', 'Leave Request Details')


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
                <span class="text-foreground font-medium">Request #{{ substr($leave->id, 0, 8) }}</span>
            </li>
        </ol>
    </nav>
    
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-foreground">Leave Request Details</h1>
                <p class="text-muted-foreground mt-1">Request #{{ substr($leave->id, 0, 8) }}</p>
            </div>
            
            <div class="flex flex-wrap items-center gap-3">
                @if($leave->canBeCancelled() && $leave->employee_id === auth()->user()->employee?->id)
                    <x-ui.button variant="destructive" id="cancel-leave" data-id="{{ $leave->id }}">
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Cancel Request
                    </x-ui.button>
                @endif
                <x-ui.button variant="outline" href="{{ route('leave.index') }}">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to List
                </x-ui.button>
            </div>
        </div>
    </div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Leave Request Details -->
    <div class="lg:col-span-2">
        <x-ui.card>
            <x-slot name="title">
                <div class="flex items-center justify-between">
                    <span>Request Details</span>
                    <x-ui.badge variant="@if($leave->status === 'approved') success @elseif($leave->status === 'rejected') destructive @elseif($leave->status === 'pending') warning @else secondary @endif">
                        {{ ucfirst($leave->status) }}
                    </x-ui.badge>
                </div>
            </x-slot>
            <x-slot name="subtitle">Complete details of the leave request</x-slot>
            
            <div class="space-y-6">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div class="space-y-4">
                        <div>
                            <x-ui.label value="Employee" class="mb-2" />
                            <div class="flex items-center space-x-3">
                                <x-ui.avatar :name="$leave->employee->full_name" size="sm" />
                                <div>
                                    <div class="font-medium text-foreground">{{ $leave->employee->full_name }}</div>
                                    <div class="text-sm text-muted-foreground">{{ $leave->employee->employee_id }}</div>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <x-ui.label value="Leave Type" class="mb-2" />
                            <div class="flex items-center space-x-2">
                                <x-ui.badge variant="secondary">{{ $leave->leaveType->name }}</x-ui.badge>
                                @if($leave->leaveType->is_paid)
                                    <x-ui.badge variant="success" size="sm">Paid</x-ui.badge>
                                @else
                                    <x-ui.badge variant="warning" size="sm">Unpaid</x-ui.badge>
                                @endif
                            </div>
                        </div>
                        
                        <div>
                            <x-ui.label value="Duration" class="mb-2" />
                            <div class="flex items-center space-x-2">
                                <span class="font-medium text-foreground">{{ $leave->duration }}</span>
                                @if($leave->is_emergency)
                                    <x-ui.badge variant="destructive" size="sm">Emergency</x-ui.badge>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <x-ui.label value="Leave Dates" class="mb-2" />
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <rect x="4" y="5" width="16" height="16" rx="2"/>
                                    <line x1="16" y1="3" x2="16" y2="7"/>
                                    <line x1="8" y1="3" x2="8" y2="7"/>
                                    <line x1="4" y1="11" x2="20" y2="11"/>
                                </svg>
                                <span class="text-foreground font-medium">{{ $leave->date_range }}</span>
                            </div>
                            @if($leave->isActive())
                                <div class="text-sm text-success mt-1">Currently on leave</div>
                            @elseif($leave->start_date > now())
                                <div class="text-sm text-primary mt-1">Upcoming leave</div>
                            @endif
                        </div>
                        
                        <div>
                            <x-ui.label value="Request Submitted" class="mb-2" />
                            <div class="text-foreground font-medium">{{ $leave->created_at->format('M j, Y g:i A') }}</div>
                            <div class="text-sm text-muted-foreground">{{ $leave->created_at->diffForHumans() }}</div>
                        </div>
                        
                        @if($leave->approved_by)
                        <div>
                            <x-ui.label value="@if($leave->isApproved()) Approved By @elseif($leave->isRejected()) Rejected By @endif" class="mb-2" />
                            <div class="flex items-center space-x-3">
                                <x-ui.avatar :name="$leave->approver->full_name" size="sm" />
                                <div>
                                    <div class="font-medium text-foreground">{{ $leave->approver->full_name }}</div>
                                    @if($leave->approved_at)
                                        <div class="text-sm text-muted-foreground">{{ $leave->approved_at->format('M j, Y g:i A') }}</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                
                @if($leave->reason)
                <div>
                    <x-ui.label value="Reason for Leave" class="mb-2" />
                    <div class="p-4 bg-muted/50 rounded-lg border border-border">
                        <p class="text-foreground">{{ $leave->reason }}</p>
                    </div>
                </div>
                @endif
                
                @if($leave->approval_notes && $leave->isApproved())
                <div>
                    <x-ui.label value="Approval Notes" class="mb-2" />
                    <x-ui.alert variant="success">
                        {{ $leave->approval_notes }}
                    </x-ui.alert>
                </div>
                @endif
                
                @if($leave->rejection_reason && $leave->isRejected())
                <div>
                    <x-ui.label value="Rejection Reason" class="mb-2" />
                    <x-ui.alert variant="destructive">
                        {{ $leave->rejection_reason }}
                    </x-ui.alert>
                </div>
                @endif
            </div>
        </x-ui.card>
    </div>
    
    <!-- Sidebar -->
    <div class="lg:col-span-1 space-y-6">
        <!-- Status Timeline -->
        <x-ui.card>
            <x-slot name="title">Request Timeline</x-slot>
            <x-slot name="subtitle">Track request progress</x-slot>
            
            <div class="space-y-6">
                <div class="relative">
                    <div class="absolute left-0 top-0 w-3 h-3 bg-success rounded-full"></div>
                    <div class="ml-6">
                        <div class="text-sm text-muted-foreground">{{ $leave->created_at->format('M j, g:i A') }}</div>
                        <div class="mt-1">
                            <div class="font-medium text-foreground">Request Submitted</div>
                            <div class="text-sm text-muted-foreground">Leave request created and submitted for approval</div>
                        </div>
                    </div>
                </div>
                
                @if($leave->approved_at)
                <div class="relative">
                    <div class="absolute left-0 top-0 w-3 h-3 {{ $leave->isApproved() ? 'bg-success' : 'bg-destructive' }} rounded-full"></div>
                    <div class="ml-6">
                        <div class="text-sm text-muted-foreground">{{ $leave->approved_at->format('M j, g:i A') }}</div>
                        <div class="mt-1">
                            <div class="font-medium text-foreground">
                                @if($leave->isApproved())
                                    Request Approved
                                @elseif($leave->isRejected())
                                    Request Rejected
                                @endif
                            </div>
                            <div class="text-sm text-muted-foreground">
                                @if($leave->approver)
                                    by {{ $leave->approver->full_name }}
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @elseif($leave->isPending())
                <div class="relative">
                    <div class="absolute left-0 top-0 w-3 h-3 bg-warning rounded-full"></div>
                    <div class="ml-6">
                        <div class="text-sm text-muted-foreground">Pending</div>
                        <div class="mt-1">
                            <div class="font-medium text-foreground">Awaiting Approval</div>
                            <div class="text-sm text-muted-foreground">Request is under review</div>
                        </div>
                    </div>
                </div>
                @endif
                
                @if($leave->isCancelled())
                <div class="relative">
                    <div class="absolute left-0 top-0 w-3 h-3 bg-muted-foreground rounded-full"></div>
                    <div class="ml-6">
                        <div class="text-sm text-muted-foreground">{{ $leave->updated_at->format('M j, g:i A') }}</div>
                        <div class="mt-1">
                            <div class="font-medium text-foreground">Request Cancelled</div>
                            <div class="text-sm text-muted-foreground">Leave request was cancelled</div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </x-ui.card>
        
        <!-- Leave Balance Impact -->
        <x-ui.card>
            <x-slot name="title">Balance Impact</x-slot>
            <x-slot name="subtitle">Effect on leave balance</x-slot>
            
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-muted-foreground">Leave Type:</span>
                    <span class="font-medium text-foreground">{{ $leave->leaveType->name }}</span>
                </div>
                
                <div class="flex justify-between items-center">
                    <span class="text-muted-foreground">Days Requested:</span>
                    <span class="font-medium text-foreground">{{ $leave->days_requested }}</span>
                </div>
                
                @php
                    $balance = $leave->employee->getLeaveBalance($leave->leaveType->id);
                @endphp
                
                @if($balance)
                <div class="flex justify-between items-center">
                    <span class="text-muted-foreground">Current Balance:</span>
                    <span class="font-medium text-foreground">{{ $balance->remaining_days }} days</span>
                </div>
                
                @if($leave->isPending() || $leave->isApproved())
                <div class="flex justify-between items-center border-t border-border pt-3">
                    <span class="text-muted-foreground">After This Request:</span>
                    <span class="font-semibold {{ $balance->remaining_days >= 0 ? 'text-success' : 'text-destructive' }}">
                        {{ $balance->remaining_days }} days
                    </span>
                </div>
                @endif
                @else
                <div class="text-center py-6 text-muted-foreground">
                    <p class="text-sm">No balance information available</p>
                </div>
                @endif
            </div>
        </x-ui.card>
        
        <!-- Quick Actions -->
        @if($leave->employee_id === auth()->user()->employee?->id)
        <x-ui.card>
            <x-slot name="title">Quick Actions</x-slot>
            <x-slot name="subtitle">Available actions</x-slot>
            
            <div class="space-y-3">
                @if($leave->canBeCancelled())
                    <x-ui.button variant="destructive" class="w-full" id="cancel-leave-btn" data-id="{{ $leave->id }}">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Cancel Request
                    </x-ui.button>
                @endif
                
                <x-ui.button variant="outline" class="w-full" href="{{ route('leave.create') }}">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    New Request
                </x-ui.button>
            </div>
        </x-ui.card>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Cancel leave request
    $('#cancel-leave, #cancel-leave-btn').on('click', function() {
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
});
</script>
@endpush