@extends('layouts.authenticated')

@section('title', 'Edit Attendance Record')

@section('page-content')
    <!-- Page Header -->
    <div class="mb-8 mt-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Edit Attendance Record</h1>
                <p class="text-sm text-muted-foreground mt-1">Modify attendance record for {{ $attendance->employee->full_name ?? 'Employee' }}</p>
            </div>
            
            <div class="flex items-center space-x-3">
                <x-ui.button variant="outline" href="{{ route('attendance.show', $attendance) }}">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    View Details
                </x-ui.button>
                <x-ui.button variant="outline" href="{{ route('attendance.index') }}">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to Attendance
                </x-ui.button>
            </div>
        </div>
    </div>

    <form action="{{ route('attendance.update', $attendance) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Employee Information -->
            <x-ui.card>
                <x-slot name="title">Employee Information</x-slot>
                <x-slot name="subtitle">Employee and date details</x-slot>
                
                <div class="space-y-4">
                    <div>
                        <x-ui.label value="Employee" />
                        <div class="mt-1 block w-full px-3 py-2 bg-muted border border-input rounded-md text-muted-foreground">
                            {{ $attendance->employee->full_name ?? 'Unknown Employee' }} ({{ $attendance->employee->employee_id ?? 'N/A' }})
                        </div>
                        <p class="mt-1 text-sm text-muted-foreground">Employee cannot be changed</p>
                    </div>

                    <div>
                        <x-ui.label for="date" value="Date" />
                        <x-ui.input type="date" name="date" id="date" value="{{ old('date', $attendance->date?->format('Y-m-d')) }}" required />
                        @error('date')
                            <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <x-ui.label for="status" value="Status" />
                        <select name="status" id="status" class="mt-1 block w-full border-input rounded-md shadow-sm focus:border-primary focus:ring-primary" required>
                            <option value="">Select Status</option>
                            <option value="present" {{ old('status', $attendance->status) == 'present' ? 'selected' : '' }}>Present</option>
                            <option value="absent" {{ old('status', $attendance->status) == 'absent' ? 'selected' : '' }}>Absent</option>
                            <option value="late" {{ old('status', $attendance->status) == 'late' ? 'selected' : '' }}>Late</option>
                            <option value="early_departure" {{ old('status', $attendance->status) == 'early_departure' ? 'selected' : '' }}>Early Departure</option>
                        </select>
                        @error('status')
                            <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </x-ui.card>

            <!-- Time Information -->
            <x-ui.card>
                <x-slot name="title">Time Information</x-slot>
                <x-slot name="subtitle">Check-in and check-out times</x-slot>
                
                <div class="space-y-4">
                    <div>
                        <x-ui.label for="check_in_time" value="Check In Time" />
                        <x-ui.input type="time" name="check_in_time" id="check_in_time" 
                                   value="{{ old('check_in_time', $attendance->check_in?->format('H:i')) }}" />
                        @error('check_in_time')
                            <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <x-ui.label for="check_out_time" value="Check Out Time" />
                        <x-ui.input type="time" name="check_out_time" id="check_out_time" 
                                   value="{{ old('check_out_time', $attendance->check_out?->format('H:i')) }}" />
                        @error('check_out_time')
                            <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <x-ui.label for="break_duration" value="Break Duration (minutes)" />
                        <x-ui.input type="number" name="break_duration" id="break_duration" 
                                   value="{{ old('break_duration', $attendance->break_duration ?? 0) }}" min="0" max="480" />
                        @error('break_duration')
                            <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                        @enderror
                        <p class="mt-1 text-sm text-muted-foreground">Break time to deduct from working hours</p>
                    </div>

                    <div>
                        <x-ui.label for="location_id" value="Location" />
                        <select name="location_id" id="location_id" class="mt-1 block w-full border-input rounded-md shadow-sm focus:border-primary focus:ring-primary">
                            <option value="">Select Location</option>
                            @foreach($locations ?? [] as $location)
                                <option value="{{ $location->id }}" {{ old('location_id', $attendance->location_id) == $location->id ? 'selected' : '' }}>
                                    {{ $location->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('location_id')
                            <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </x-ui.card>
        </div>

        <!-- Additional Information -->
        <x-ui.card>
            <x-slot name="title">Additional Information</x-slot>
            <x-slot name="subtitle">Notes and verification details</x-slot>
            
            <div class="space-y-4">
                <div>
                    <x-ui.label for="notes" value="Notes" />
                    <textarea name="notes" id="notes" rows="3" class="mt-1 block w-full border-input rounded-md shadow-sm focus:border-primary focus:ring-primary" placeholder="Any additional notes about this attendance record">{{ old('notes', $attendance->notes) }}</textarea>
                    @error('notes')
                        <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                    @enderror
                </div>

                <div class="flex items-center space-x-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_manual" value="1" {{ $attendance->is_manual ? 'checked' : '' }} disabled class="rounded border-input text-primary focus:border-primary focus:ring-primary" />
                        <span class="ml-2 text-sm text-muted-foreground">Manual Entry</span>
                    </label>

                    <label class="flex items-center">
                        <input type="checkbox" name="verified" value="1" {{ old('verified', $attendance->verified) ? 'checked' : '' }} class="rounded border-input text-primary focus:border-primary focus:ring-primary" />
                        <span class="ml-2 text-sm text-foreground">Verified by Administrator</span>
                    </label>
                </div>

                @if($attendance->created_at)
                <div class="pt-4 border-t border-border">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-muted-foreground">
                        <div>
                            <strong>Created:</strong> {{ $attendance->created_at->format('M j, Y g:i A') }}
                        </div>
                        <div>
                            <strong>Last Modified:</strong> {{ $attendance->updated_at->format('M j, Y g:i A') }}
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </x-ui.card>

        <!-- Actions -->
        <div class="flex items-center justify-end space-x-4">
            <x-ui.button type="button" variant="outline" onclick="history.back()">
                Cancel
            </x-ui.button>
            <x-ui.button type="submit" variant="warning">
                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Update Attendance Record
            </x-ui.button>
        </div>
    </form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusSelect = document.getElementById('status');
    const checkInTime = document.getElementById('check_in_time');
    const checkOutTime = document.getElementById('check_out_time');
    
    // Handle status change
    statusSelect.addEventListener('change', function() {
        const status = this.value;
        
        if (status === 'absent') {
            checkInTime.value = '';
            checkOutTime.value = '';
            checkInTime.disabled = true;
            checkOutTime.disabled = true;
        } else {
            checkInTime.disabled = false;
            checkOutTime.disabled = false;
        }
    });
    
    // Initialize status-based fields
    if (statusSelect.value === 'absent') {
        checkInTime.disabled = true;
        checkOutTime.disabled = true;
    }
    
    // Validate times
    checkOutTime.addEventListener('change', function() {
        if (checkInTime.value && checkOutTime.value) {
            if (checkOutTime.value <= checkInTime.value) {
                alert('Check-out time must be after check-in time');
                checkOutTime.value = '';
            }
        }
    });
});
</script>
@endpush