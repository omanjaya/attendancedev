@extends('layouts.authenticated')

@section('title', 'Edit Leave Request')

@section('page-content')
    <!-- Page Header -->
    <div class="mb-8 mt-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Edit Leave Request</h1>
                <p class="text-sm text-muted-foreground mt-1">Modify your leave application details</p>
            </div>
            
            <div class="flex items-center space-x-3">
                <x-ui.button variant="outline" href="{{ route('leave.show', $leave) }}">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    View Details
                </x-ui.button>
                <x-ui.button variant="outline" href="{{ route('leave.index') }}">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to Leave Requests
                </x-ui.button>
            </div>
        </div>
    </div>

    @if($leave->status !== 'pending')
        <div class="mb-6 p-4 bg-warning/10 border border-warning/20 rounded-lg">
            <div class="flex items-center">
                <svg class="h-5 w-5 text-warning mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.314 18.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
                <div>
                    <h4 class="font-medium text-warning">Limited Editing</h4>
                    <p class="text-sm text-warning/80">This leave request has been {{ $leave->status }}. Only certain fields can be modified.</p>
                </div>
            </div>
        </div>
    @endif

    <form action="{{ route('leave.update', $leave) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Leave Details -->
            <x-ui.card>
                <x-slot name="title">Leave Details</x-slot>
                <x-slot name="subtitle">Basic leave information</x-slot>
                
                <div class="space-y-4">
                    <div>
                        <x-ui.label for="leave_type_id" value="Leave Type" />
                        <select name="leave_type_id" id="leave_type_id" 
                                class="mt-1 block w-full border-input rounded-md shadow-sm focus:border-primary focus:ring-primary" 
                                {{ $leave->status !== 'pending' ? 'disabled' : '' }} required>
                            <option value="">Select Leave Type</option>
                            @foreach($leaveTypes ?? [] as $type)
                                <option value="{{ $type->id }}" 
                                        data-max-days="{{ $type->max_days_per_request }}"
                                        {{ old('leave_type_id', $leave->leave_type_id) == $type->id ? 'selected' : '' }}>
                                    {{ $type->name }}
                                    @if($type->max_days_per_request)
                                        (Max: {{ $type->max_days_per_request }} days)
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @error('leave_type_id')
                            <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <x-ui.label for="start_date" value="Start Date" />
                            <x-ui.input type="date" name="start_date" id="start_date" 
                                       value="{{ old('start_date', $leave->start_date?->format('Y-m-d')) }}" 
                                       {{ $leave->status !== 'pending' ? 'disabled' : '' }} required />
                            @error('start_date')
                                <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <x-ui.label for="end_date" value="End Date" />
                            <x-ui.input type="date" name="end_date" id="end_date" 
                                       value="{{ old('end_date', $leave->end_date?->format('Y-m-d')) }}" 
                                       {{ $leave->status !== 'pending' ? 'disabled' : '' }} required />
                            @error('end_date')
                                <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <x-ui.label value="Duration" />
                        <div class="mt-1 px-3 py-2 bg-muted border border-input rounded-md text-muted-foreground">
                            <span id="duration-display">
                                {{ $leave->days_requested }} {{ $leave->days_requested == 1 ? 'day' : 'days' }}
                            </span>
                        </div>
                        <input type="hidden" name="days_requested" id="days_requested" value="{{ $leave->days_requested }}">
                    </div>

                    @if($leave->status === 'pending')
                    <div class="flex items-center">
                        <input type="checkbox" name="is_half_day" id="is_half_day" value="1" 
                               {{ old('is_half_day', $leave->is_half_day) ? 'checked' : '' }}
                               class="rounded border-input text-primary focus:border-primary focus:ring-primary">
                        <label for="is_half_day" class="ml-2 text-sm text-foreground">Half Day Leave</label>
                    </div>
                    @endif
                </div>
            </x-ui.card>

            <!-- Reason and Documentation -->
            <x-ui.card>
                <x-slot name="title">Reason & Documentation</x-slot>
                <x-slot name="subtitle">Explain your leave request</x-slot>
                
                <div class="space-y-4">
                    <div>
                        <x-ui.label for="reason" value="Reason for Leave" />
                        <textarea name="reason" id="reason" rows="4" 
                                  class="mt-1 block w-full border-input rounded-md shadow-sm focus:border-primary focus:ring-primary" 
                                  placeholder="Please provide a detailed reason for your leave request..."
                                  {{ $leave->status !== 'pending' ? 'disabled' : '' }} required>{{ old('reason', $leave->reason) }}</textarea>
                        @error('reason')
                            <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                        @enderror
                    </div>

                    @if($leave->status === 'pending')
                    <div>
                        <x-ui.label for="supporting_document" value="Supporting Document (Optional)" />
                        <input type="file" name="supporting_document" id="supporting_document" 
                               class="mt-1 block w-full border-input rounded-md shadow-sm focus:border-primary focus:ring-primary"
                               accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                        @error('supporting_document')
                            <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                        @enderror
                        <p class="mt-1 text-sm text-muted-foreground">
                            Upload medical certificate, invitation letter, or other supporting documents. Max size: 5MB.
                        </p>
                    </div>
                    @endif

                    @if($leave->supporting_document_path)
                    <div>
                        <x-ui.label value="Current Document" />
                        <div class="mt-1 flex items-center space-x-2">
                            <svg class="h-5 w-5 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <a href="{{ asset('storage/' . $leave->supporting_document_path) }}" target="_blank" 
                               class="text-primary hover:text-primary/80 text-sm">
                                View Current Document
                            </a>
                        </div>
                    </div>
                    @endif

                    @if($leave->status === 'pending')
                    <div>
                        <x-ui.label for="emergency_contact" value="Emergency Contact (Optional)" />
                        <x-ui.input type="text" name="emergency_contact" id="emergency_contact" 
                                   value="{{ old('emergency_contact', $leave->emergency_contact) }}" 
                                   placeholder="Contact person and phone number during leave" />
                        @error('emergency_contact')
                            <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                        @enderror
                    </div>
                    @endif
                </div>
            </x-ui.card>
        </div>

        <!-- Status Information -->
        @if($leave->status !== 'pending')
        <x-ui.card>
            <x-slot name="title">Approval Information</x-slot>
            <x-slot name="subtitle">Current status and approval details</x-slot>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <x-ui.label value="Current Status" />
                    <div class="mt-1">
                        <x-ui.badge variant="{{ 
                            match($leave->status) {
                                'approved' => 'success',
                                'rejected' => 'destructive',
                                'cancelled' => 'secondary',
                                default => 'warning'
                            }
                        }}">
                            {{ ucfirst($leave->status) }}
                        </x-ui.badge>
                    </div>
                </div>

                @if($leave->approver)
                <div>
                    <x-ui.label value="Reviewed By" />
                    <div class="mt-1 text-sm text-foreground">{{ $leave->approver->name }}</div>
                </div>
                @endif

                @if($leave->approved_at)
                <div>
                    <x-ui.label value="Review Date" />
                    <div class="mt-1 text-sm text-foreground">{{ $leave->approved_at->format('F j, Y g:i A') }}</div>
                </div>
                @endif

                @if($leave->approval_notes)
                <div class="md:col-span-2">
                    <x-ui.label value="Approval Notes" />
                    <div class="mt-1 p-3 bg-muted rounded-md text-sm text-foreground">{{ $leave->approval_notes }}</div>
                </div>
                @endif
            </div>
        </x-ui.card>
        @endif

        <!-- Available Balance Information -->
        @if(isset($leaveBalance))
        <x-ui.card>
            <x-slot name="title">Leave Balance</x-slot>
            <x-slot name="subtitle">Your current available leave days</x-slot>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach($leaveBalance as $type => $balance)
                <div class="text-center">
                    <div class="text-2xl font-bold text-foreground">{{ $balance['available'] }}</div>
                    <div class="text-sm text-muted-foreground">{{ $type }}</div>
                    <div class="text-xs text-muted-foreground">of {{ $balance['total'] }} days</div>
                </div>
                @endforeach
            </div>
        </x-ui.card>
        @endif

        <!-- Actions -->
        <div class="flex items-center justify-end space-x-4">
            <x-ui.button type="button" variant="outline" onclick="history.back()">
                Cancel
            </x-ui.button>
            
            @if($leave->status === 'pending')
            <x-ui.button type="submit" variant="warning">
                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Update Leave Request
            </x-ui.button>
            @else
            <x-ui.button type="submit" variant="secondary">
                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Update Notes Only
            </x-ui.button>
            @endif
        </div>
    </form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    const isHalfDayInput = document.getElementById('is_half_day');
    const durationDisplay = document.getElementById('duration-display');
    const daysRequestedInput = document.getElementById('days_requested');
    const leaveTypeSelect = document.getElementById('leave_type_id');

    // Calculate duration
    function calculateDuration() {
        const startDate = new Date(startDateInput.value);
        const endDate = new Date(endDateInput.value);
        
        if (startDate && endDate && startDate <= endDate) {
            let days = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24)) + 1;
            
            // Adjust for half day
            if (isHalfDayInput && isHalfDayInput.checked && days === 1) {
                days = 0.5;
                durationDisplay.textContent = '0.5 day';
            } else {
                durationDisplay.textContent = days + (days === 1 ? ' day' : ' days');
            }
            
            daysRequestedInput.value = days;
            
            // Check against leave type limits
            if (leaveTypeSelect.value) {
                const selectedOption = leaveTypeSelect.querySelector(`option[value="${leaveTypeSelect.value}"]`);
                const maxDays = selectedOption ? selectedOption.dataset.maxDays : null;
                
                if (maxDays && days > parseInt(maxDays)) {
                    alert(`This leave type allows maximum ${maxDays} days per request.`);
                }
            }
        }
    }

    // Event listeners
    if (startDateInput && endDateInput) {
        startDateInput.addEventListener('change', calculateDuration);
        endDateInput.addEventListener('change', calculateDuration);
    }
    
    if (isHalfDayInput) {
        isHalfDayInput.addEventListener('change', calculateDuration);
    }

    // Set minimum date to today for start date
    if (startDateInput) {
        const today = new Date().toISOString().split('T')[0];
        startDateInput.min = today;
    }

    // Update end date minimum when start date changes
    if (startDateInput && endDateInput) {
        startDateInput.addEventListener('change', function() {
            endDateInput.min = this.value;
            if (endDateInput.value && endDateInput.value < this.value) {
                endDateInput.value = this.value;
            }
            calculateDuration();
        });
    }

    // Initial calculation
    calculateDuration();
});
</script>
@endpush