@extends('layouts.authenticated')

@section('title', 'Request Leave')

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
                <span class="text-foreground font-medium">Request Leave</span>
            </li>
        </ol>
    </nav>
    
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-foreground">Request Leave</h1>
                <p class="text-muted-foreground mt-1">Submit new leave request</p>
            </div>
            
            <x-ui.button variant="outline" href="{{ route('leave.index') }}">
                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to List
            </x-ui.button>
        </div>
    </div>

    <form action="{{ route('leave.store') }}" method="POST">
        @csrf
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2">
                <x-ui.card>
                    <x-slot name="title">Leave Request Details</x-slot>
                    <x-slot name="subtitle">Fill in your leave request information</x-slot>
                    
                    <div class="space-y-6">
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div class="space-y-2">
                                <x-ui.label for="leave_type_id" value="Leave Type" required />
                                <x-ui.select name="leave_type_id" id="leave_type_id" required>
                                    <option value="">Select Leave Type</option>
                                    @foreach($leaveTypes as $leaveType)
                                        <option value="{{ $leaveType->id }}" {{ old('leave_type_id') == $leaveType->id ? 'selected' : '' }}>
                                            {{ $leaveType->display_name }}
                                        </option>
                                    @endforeach
                                </x-ui.select>
                                @error('leave_type_id')
                                    <x-ui.input-error class="mt-1" :messages="$message" />
                                @enderror
                            </div>
                            
                            <div class="space-y-2">
                                <x-ui.label value="Current Balance" />
                                <div id="leave-balance" class="p-3 bg-muted/50 rounded-md border border-border text-sm text-muted-foreground">
                                    Select a leave type to view balance
                                </div>
                            </div>
                        </div>
                    
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div class="space-y-2">
                                <x-ui.label for="start_date" value="Start Date" required />
                                <x-ui.input type="date" name="start_date" id="start_date" 
                                           value="{{ old('start_date') }}" min="{{ date('Y-m-d') }}" required />
                                @error('start_date')
                                    <x-ui.input-error class="mt-1" :messages="$message" />
                                @enderror
                            </div>
                            
                            <div class="space-y-2">
                                <x-ui.label for="end_date" value="End Date" required />
                                <x-ui.input type="date" name="end_date" id="end_date" 
                                           value="{{ old('end_date') }}" min="{{ date('Y-m-d') }}" required />
                                @error('end_date')
                                    <x-ui.input-error class="mt-1" :messages="$message" />
                                @enderror
                            </div>
                        </div>
                    
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div class="space-y-2">
                                <x-ui.label value="Working Days" />
                                <div id="working-days" class="p-3 bg-muted/50 rounded-md border border-border text-sm text-muted-foreground">
                                    Select dates to calculate
                                </div>
                            </div>
                            
                            <div class="space-y-2">
                                <x-ui.label value="Emergency Leave" />
                                <div class="flex items-center space-x-3">
                                    <input type="hidden" name="is_emergency" value="0">
                                    <x-ui.checkbox name="is_emergency" value="1" {{ old('is_emergency') ? 'checked' : '' }} />
                                    <div>
                                        <span class="text-sm font-medium text-foreground">Emergency Leave</span>
                                        <p class="text-xs text-muted-foreground">Check if this is an emergency leave request</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    
                        <div class="space-y-2">
                            <x-ui.label for="reason" value="Reason for Leave" />
                            <textarea name="reason" id="reason"
                                      class="block w-full border-input bg-background px-3 py-2 text-sm text-foreground shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 rounded-md" 
                                      rows="4" placeholder="Please provide a reason for your leave request...">{{ old('reason') }}</textarea>
                            @error('reason')
                                <x-ui.input-error class="mt-1" :messages="$message" />
                            @enderror
                            <p class="text-xs text-muted-foreground">Optional: Provide additional details about your leave request</p>
                        </div>
                    </div>
                </x-ui.card>
            </div>
        
            <div class="space-y-6">
                <x-ui.card>
                    <x-slot name="title">Leave Balance Summary</x-slot>
                    <x-slot name="subtitle">Your available leave days</x-slot>
                    
                    <div id="balance-summary" class="space-y-4">
                        @if($leaveBalances->count() > 0)
                            @foreach($leaveBalances as $balance)
                            <div class="p-4 bg-muted/30 rounded-lg">
                                <div class="flex justify-between items-center mb-3">
                                    <span class="font-medium text-foreground">{{ $balance->leaveType->name }}</span>
                                    <x-ui.badge variant="secondary">{{ $balance->remaining_days }} days left</x-ui.badge>
                                </div>
                                <div class="w-full bg-muted rounded-full h-2">
                                    @php
                                        $percentage = $balance->allocated_days > 0 ? ($balance->remaining_days / $balance->allocated_days) * 100 : 0;
                                        $colorClass = $percentage > 50 ? 'bg-success' : ($percentage > 20 ? 'bg-warning' : 'bg-destructive');
                                    @endphp
                                    <div class="{{ $colorClass }} h-2 rounded-full transition-all" style="width: {{ $percentage }}%"></div>
                                </div>
                                <p class="text-xs text-muted-foreground mt-2">
                                    {{ $balance->used_days }} used of {{ $balance->allocated_days }} allocated
                                </p>
                            </div>
                            @endforeach
                        @else
                            <div class="text-center py-8 text-muted-foreground">
                                <svg class="w-12 h-12 mx-auto mb-4 text-muted-foreground/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="9"/>
                                    <line x1="9" y1="9" x2="15" y2="15"/>
                                    <line x1="15" y1="9" x2="9" y2="15"/>
                                </svg>
                                <p class="font-medium">No leave balances found</p>
                                <p class="text-sm">Contact HR to set up your leave entitlements</p>
                            </div>
                        @endif
                    </div>
                </x-ui.card>
            
                <x-ui.card>
                    <x-slot name="title">Important Notes</x-slot>
                    <x-slot name="subtitle">Please read before submitting</x-slot>
                    
                    <div class="space-y-3 text-sm text-muted-foreground">
                        <div class="flex items-start space-x-2">
                            <svg class="w-4 h-4 mt-0.5 text-primary" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>Leave requests must be submitted at least 24 hours in advance</span>
                        </div>
                        <div class="flex items-start space-x-2">
                            <svg class="w-4 h-4 mt-0.5 text-primary" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>Emergency leaves may be approved retroactively</span>
                        </div>
                        <div class="flex items-start space-x-2">
                            <svg class="w-4 h-4 mt-0.5 text-primary" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>Weekend days are automatically excluded from leave calculation</span>
                        </div>
                        <div class="flex items-start space-x-2">
                            <svg class="w-4 h-4 mt-0.5 text-primary" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>You can cancel pending or future approved leaves</span>
                        </div>
                        <div class="flex items-start space-x-2">
                            <svg class="w-4 h-4 mt-0.5 text-primary" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>Approved leaves will be deducted from your leave balance</span>
                        </div>
                    </div>
                </x-ui.card>
            
                <x-ui.card>
                    <x-slot name="title">Submit Request</x-slot>
                    
                    <div class="space-y-3">
                        <x-ui.button type="submit" class="w-full" id="submit-btn">
                            <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l7-7-7-7"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14"/>
                            </svg>
                            Submit Leave Request
                        </x-ui.button>
                        <x-ui.button type="button" variant="outline" class="w-full" onclick="window.location.href='{{ route('leave.index') }}'">
                            Cancel
                        </x-ui.button>
                    </div>
                </x-ui.card>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Update leave balance when leave type changes
    $('#leave_type_id').on('change', function() {
        const leaveTypeId = $(this).val();
        const balanceDiv = $('#leave-balance');
        
        if (!leaveTypeId) {
            balanceDiv.html('<span class="text-gray-600">Select a leave type to view balance</span>');
            return;
        }
        
        balanceDiv.html('<span class="text-gray-600">Loading...</span>');
        
        $.ajax({
            url: '/api/v1/leave/balance',
            method: 'GET',
            data: { leave_type_id: leaveTypeId },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success && response.balance) {
                    const balance = response.balance;
                    const percentage = balance.allocated_days > 0 ? (balance.remaining_days / balance.allocated_days) * 100 : 0;
                    const color = percentage > 50 ? 'success' : (percentage > 20 ? 'warning' : 'danger');
                    
                    balanceDiv.html(`
                        <div class="flex justify-between items-center mb-1">
                            <span>${balance.remaining_days} days remaining</span>
                            <span class="badge bg-${color}-lt">${percentage.toFixed(0)}%</span>
                        </div>
                        <div class="progress progress-sm">
                            <div class="progress-bar bg-${color}" style="width: ${percentage}%"></div>
                        </div>
                        <small class="text-gray-600">${balance.used_days} used of ${balance.allocated_days} allocated</small>
                    `);
                } else {
                    balanceDiv.html('<span class="text-danger">No balance found for this leave type</span>');
                }
            },
            error: function() {
                balanceDiv.html('<span class="text-danger">Error loading balance</span>');
            }
        });
    });
    
    // Calculate working days when dates change
    function calculateWorkingDays() {
        const startDate = $('#start_date').val();
        const endDate = $('#end_date').val();
        const workingDaysDiv = $('#working-days');
        
        if (!startDate || !endDate) {
            workingDaysDiv.html('<span class="text-gray-600">Select dates to calculate</span>');
            return;
        }
        
        if (new Date(endDate) < new Date(startDate)) {
            workingDaysDiv.html('<span class="text-danger">End date must be after start date</span>');
            return;
        }
        
        workingDaysDiv.html('<span class="text-gray-600">Calculating...</span>');
        
        $.ajax({
            url: '/api/v1/leave/calculate-days',
            method: 'POST',
            data: {
                start_date: startDate,
                end_date: endDate
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                const days = response.working_days;
                const dayText = days === 1 ? 'day' : 'days';
                workingDaysDiv.html(`<strong>${days} working ${dayText}</strong>`);
                
                // Update submit button text
                $('#submit-btn').html(`
                    <svg class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M12 19l7 -7l-7 -7"/>
                        <path d="M5 12l14 0"/>
                    </svg>
                    Submit ${days} ${dayText} Leave Request
                `);
            },
            error: function() {
                workingDaysDiv.html('<span class="text-danger">Error calculating days</span>');
            }
        });
    }
    
    $('#start_date, #end_date').on('change', calculateWorkingDays);
    
    // Set end date minimum when start date changes
    $('#start_date').on('change', function() {
        $('#end_date').attr('min', $(this).val());
    });
    
    // Initialize if dates are pre-filled
    if ($('#start_date').val() && $('#end_date').val()) {
        calculateWorkingDays();
    }
    
    // Initialize if leave type is pre-selected
    if ($('#leave_type_id').val()) {
        $('#leave_type_id').trigger('change');
    }
});
</script>
@endpush