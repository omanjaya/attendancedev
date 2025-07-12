@extends('layouts.authenticated')

@section('title', 'Add Manual Attendance')

@section('page-content')
    <!-- Page Header -->
    <div class="mb-8 mt-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Add Manual Attendance</h1>
                <p class="text-sm text-muted-foreground mt-1">Create attendance record manually for employees</p>
            </div>
            
            <x-ui.button variant="outline" href="{{ route('attendance.index') }}">
                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Attendance
            </x-ui.button>
        </div>
    </div>

    <form action="{{ route('attendance.store') }}" method="POST" class="space-y-6">
        @csrf
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Employee Selection -->
            <x-ui.card>
                <x-slot name="title">Employee Information</x-slot>
                <x-slot name="subtitle">Select employee and date</x-slot>
                
                <div class="space-y-4">
                    <div>
                        <x-ui.label for="employee_id" value="Employee" />
                        <select name="employee_id" id="employee_id" class="mt-1 block w-full border-input rounded-md shadow-sm focus:border-primary focus:ring-primary" required>
                            <option value="">Select Employee</option>
                            @foreach($employees ?? [] as $employee)
                                <option value="{{ $employee->id }}" {{ old('employee_id') == $employee->id ? 'selected' : '' }}>
                                    {{ $employee->full_name }} ({{ $employee->employee_id }})
                                </option>
                            @endforeach
                        </select>
                        @error('employee_id')
                            <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <x-ui.label for="date" value="Date" />
                        <x-ui.input type="date" name="date" id="date" value="{{ old('date', date('Y-m-d')) }}" required />
                        @error('date')
                            <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <x-ui.label for="status" value="Status" />
                        <select name="status" id="status" class="mt-1 block w-full border-input rounded-md shadow-sm focus:border-primary focus:ring-primary" required>
                            <option value="">Select Status</option>
                            <option value="present" {{ old('status') == 'present' ? 'selected' : '' }}>Present</option>
                            <option value="absent" {{ old('status') == 'absent' ? 'selected' : '' }}>Absent</option>
                            <option value="late" {{ old('status') == 'late' ? 'selected' : '' }}>Late</option>
                            <option value="early_departure" {{ old('status') == 'early_departure' ? 'selected' : '' }}>Early Departure</option>
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
                <x-slot name="subtitle">Set check-in and check-out times</x-slot>
                
                <div class="space-y-4">
                    <div>
                        <x-ui.label for="check_in_time" value="Check In Time" />
                        <x-ui.input type="time" name="check_in_time" id="check_in_time" value="{{ old('check_in_time') }}" />
                        @error('check_in_time')
                            <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <x-ui.label for="check_out_time" value="Check Out Time" />
                        <x-ui.input type="time" name="check_out_time" id="check_out_time" value="{{ old('check_out_time') }}" />
                        @error('check_out_time')
                            <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <x-ui.label for="break_duration" value="Break Duration (minutes)" />
                        <x-ui.input type="number" name="break_duration" id="break_duration" value="{{ old('break_duration', 0) }}" min="0" max="480" />
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
                                <option value="{{ $location->id }}" {{ old('location_id') == $location->id ? 'selected' : '' }}>
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
            <x-slot name="subtitle">Optional notes and verification details</x-slot>
            
            <div class="space-y-4">
                <div>
                    <x-ui.label for="notes" value="Notes" />
                    <textarea name="notes" id="notes" rows="3" class="mt-1 block w-full border-input rounded-md shadow-sm focus:border-primary focus:ring-primary" placeholder="Any additional notes about this attendance record">{{ old('notes') }}</textarea>
                    @error('notes')
                        <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                    @enderror
                </div>

                <div class="flex items-center space-x-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_manual" value="1" checked disabled class="rounded border-input text-primary focus:border-primary focus:ring-primary" />
                        <span class="ml-2 text-sm text-muted-foreground">Manual Entry</span>
                    </label>

                    <label class="flex items-center">
                        <input type="checkbox" name="verified" value="1" {{ old('verified') ? 'checked' : '' }} class="rounded border-input text-primary focus:border-primary focus:ring-primary" />
                        <span class="ml-2 text-sm text-foreground">Verified by Administrator</span>
                    </label>
                </div>
            </div>
        </x-ui.card>

        <!-- Actions -->
        <div class="flex items-center justify-end space-x-4">
            <x-ui.button type="button" variant="outline" onclick="history.back()">
                Cancel
            </x-ui.button>
            <x-ui.button type="submit">
                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Create Attendance Record
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
            
            if (status === 'present' && !checkInTime.value) {
                checkInTime.value = '09:00';
                checkOutTime.value = '17:00';
            }
        }
    });
    
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