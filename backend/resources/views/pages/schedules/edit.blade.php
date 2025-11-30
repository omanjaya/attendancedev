@extends('layouts.authenticated')

@section('title', 'Edit Schedule')

@section('page-content')
    <!-- Page Header -->
    <div class="mb-8 mt-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Edit Schedule</h1>
                <p class="text-sm text-muted-foreground mt-1">Modify {{ $schedule->name ?? 'schedule' }} details</p>
            </div>
            
            <div class="flex items-center space-x-3">
                <x-ui.button variant="outline" href="{{ route('schedules.show', $schedule) }}">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    View Details
                </x-ui.button>
                <x-ui.button variant="outline" href="{{ route('schedules.index') }}">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to Schedules
                </x-ui.button>
            </div>
        </div>
    </div>

    <form action="{{ route('schedules.update', $schedule) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Basic Information -->
            <x-ui.card>
                <x-slot name="title">Basic Information</x-slot>
                <x-slot name="subtitle">Schedule details and timing</x-slot>
                
                <div class="space-y-4">
                    <div>
                        <x-ui.label for="name" value="Schedule Name" />
                        <x-ui.input type="text" name="name" id="name" value="{{ old('name', $schedule->name) }}" 
                                   placeholder="e.g., Mathematics - Grade 10A" required />
                        @error('name')
                            <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <x-ui.label for="subject" value="Subject" />
                        <x-ui.input type="text" name="subject" id="subject" value="{{ old('subject', $schedule->subject) }}" 
                                   placeholder="e.g., Mathematics, English, Science" required />
                        @error('subject')
                            <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <x-ui.label for="class_grade" value="Class/Grade" />
                        <x-ui.input type="text" name="class_grade" id="class_grade" value="{{ old('class_grade', $schedule->class_grade) }}" 
                                   placeholder="e.g., Grade 10A, Form 2B" required />
                        @error('class_grade')
                            <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <x-ui.label for="room" value="Room/Location" />
                        <x-ui.input type="text" name="room" id="room" value="{{ old('room', $schedule->room) }}" 
                                   placeholder="e.g., Room 201, Science Lab, Main Hall" />
                        @error('room')
                            <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </x-ui.card>

            <!-- Time & Assignment -->
            <x-ui.card>
                <x-slot name="title">Time & Assignment</x-slot>
                <x-slot name="subtitle">Schedule timing and teacher assignment</x-slot>
                
                <div class="space-y-4">
                    <div>
                        <x-ui.label for="day_of_week" value="Day of Week" />
                        <select name="day_of_week" id="day_of_week" class="mt-1 block w-full border-input rounded-md shadow-sm focus:border-primary focus:ring-primary" required>
                            <option value="">Select Day</option>
                            <option value="monday" {{ old('day_of_week', $schedule->day_of_week) == 'monday' ? 'selected' : '' }}>Monday</option>
                            <option value="tuesday" {{ old('day_of_week', $schedule->day_of_week) == 'tuesday' ? 'selected' : '' }}>Tuesday</option>
                            <option value="wednesday" {{ old('day_of_week', $schedule->day_of_week) == 'wednesday' ? 'selected' : '' }}>Wednesday</option>
                            <option value="thursday" {{ old('day_of_week', $schedule->day_of_week) == 'thursday' ? 'selected' : '' }}>Thursday</option>
                            <option value="friday" {{ old('day_of_week', $schedule->day_of_week) == 'friday' ? 'selected' : '' }}>Friday</option>
                            <option value="saturday" {{ old('day_of_week', $schedule->day_of_week) == 'saturday' ? 'selected' : '' }}>Saturday</option>
                            <option value="sunday" {{ old('day_of_week', $schedule->day_of_week) == 'sunday' ? 'selected' : '' }}>Sunday</option>
                        </select>
                        @error('day_of_week')
                            <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <x-ui.label for="start_time" value="Start Time" />
                            <x-ui.input type="time" name="start_time" id="start_time" 
                                       value="{{ old('start_time', $schedule->start_time?->format('H:i')) }}" required />
                            @error('start_time')
                                <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <x-ui.label for="end_time" value="End Time" />
                            <x-ui.input type="time" name="end_time" id="end_time" 
                                       value="{{ old('end_time', $schedule->end_time?->format('H:i')) }}" required />
                            @error('end_time')
                                <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <x-ui.label for="period_number" value="Period Number" />
                        <select name="period_number" id="period_number" class="mt-1 block w-full border-input rounded-md shadow-sm focus:border-primary focus:ring-primary">
                            <option value="">Select Period</option>
                            @for($i = 1; $i <= 10; $i++)
                                <option value="{{ $i }}" {{ old('period_number', $schedule->period_number) == $i ? 'selected' : '' }}>
                                    Period {{ $i }}
                                </option>
                            @endfor
                        </select>
                        @error('period_number')
                            <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <x-ui.label for="teacher_id" value="Assigned Teacher" />
                        <select name="teacher_id" id="teacher_id" class="mt-1 block w-full border-input rounded-md shadow-sm focus:border-primary focus:ring-primary">
                            <option value="">Select Teacher</option>
                            @foreach($teachers ?? [] as $teacher)
                                <option value="{{ $teacher->id }}" {{ old('teacher_id', $schedule->teacher_id) == $teacher->id ? 'selected' : '' }}>
                                    {{ $teacher->full_name }} ({{ $teacher->employee_id }})
                                </option>
                            @endforeach
                        </select>
                        @error('teacher_id')
                            <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                        @enderror
                        <p class="mt-1 text-sm text-muted-foreground">Leave empty if teacher not yet assigned</p>
                    </div>
                </div>
            </x-ui.card>
        </div>

        <!-- Schedule Settings -->
        <x-ui.card>
            <x-slot name="title">Schedule Settings</x-slot>
            <x-slot name="subtitle">Additional configuration and notes</x-slot>
            
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <x-ui.label for="semester" value="Semester/Term" />
                        <x-ui.input type="text" name="semester" id="semester" value="{{ old('semester', $schedule->semester) }}" 
                                   placeholder="e.g., Fall 2024, Term 1" />
                        @error('semester')
                            <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <x-ui.label for="academic_year" value="Academic Year" />
                        <x-ui.input type="text" name="academic_year" id="academic_year" value="{{ old('academic_year', $schedule->academic_year) }}" 
                                   placeholder="e.g., 2024/2025" />
                        @error('academic_year')
                            <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <x-ui.label for="start_date" value="Schedule Start Date" />
                        <x-ui.input type="date" name="start_date" id="start_date" 
                                   value="{{ old('start_date', $schedule->start_date?->format('Y-m-d')) }}" />
                        @error('start_date')
                            <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <x-ui.label for="end_date" value="Schedule End Date" />
                        <x-ui.input type="date" name="end_date" id="end_date" 
                                   value="{{ old('end_date', $schedule->end_date?->format('Y-m-d')) }}" />
                        @error('end_date')
                            <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div>
                    <x-ui.label for="description" value="Description/Notes" />
                    <textarea name="description" id="description" rows="3" 
                              class="mt-1 block w-full border-input rounded-md shadow-sm focus:border-primary focus:ring-primary" 
                              placeholder="Additional notes about this schedule...">{{ old('description', $schedule->description) }}</textarea>
                    @error('description')
                        <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                    @enderror
                </div>

                <div class="flex items-center space-x-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $schedule->is_active) ? 'checked' : '' }} 
                               class="rounded border-input text-primary focus:border-primary focus:ring-primary" />
                        <span class="ml-2 text-sm text-foreground">Active Schedule</span>
                    </label>

                    <label class="flex items-center">
                        <input type="checkbox" name="attendance_required" value="1" {{ old('attendance_required', $schedule->attendance_required) ? 'checked' : '' }} 
                               class="rounded border-input text-primary focus:border-primary focus:ring-primary" />
                        <span class="ml-2 text-sm text-foreground">Attendance Required</span>
                    </label>

                    <label class="flex items-center">
                        <input type="checkbox" name="is_recurring" value="1" {{ old('is_recurring', $schedule->is_recurring) ? 'checked' : '' }} 
                               class="rounded border-input text-primary focus:border-primary focus:ring-primary" />
                        <span class="ml-2 text-sm text-foreground">Recurring Weekly</span>
                    </label>
                </div>

                @if($schedule->created_at)
                <div class="pt-4 border-t border-border">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-muted-foreground">
                        <div>
                            <strong>Created:</strong> {{ $schedule->created_at->format('M j, Y g:i A') }}
                        </div>
                        <div>
                            <strong>Last Modified:</strong> {{ $schedule->updated_at->format('M j, Y g:i A') }}
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
                Update Schedule
            </x-ui.button>
        </div>
    </form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const startTimeInput = document.getElementById('start_time');
    const endTimeInput = document.getElementById('end_time');
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');

    // Validate end time is after start time
    function validateTimes() {
        if (startTimeInput.value && endTimeInput.value) {
            if (endTimeInput.value <= startTimeInput.value) {
                alert('End time must be after start time');
                endTimeInput.value = '';
            }
        }
    }

    // Validate end date is after start date
    function validateDates() {
        if (startDateInput.value && endDateInput.value) {
            if (endDateInput.value < startDateInput.value) {
                alert('End date must be after start date');
                endDateInput.value = '';
            }
        }
    }

    // Event listeners
    startTimeInput.addEventListener('change', validateTimes);
    endTimeInput.addEventListener('change', validateTimes);
    startDateInput.addEventListener('change', validateDates);
    endDateInput.addEventListener('change', validateDates);

    // Update end date minimum when start date changes
    startDateInput.addEventListener('change', function() {
        endDateInput.min = this.value;
    });
});
</script>
@endpush