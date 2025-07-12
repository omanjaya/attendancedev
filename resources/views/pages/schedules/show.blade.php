@extends('layouts.authenticated')

@section('title', 'Schedule Details')

@section('page-content')
    <!-- Page Header -->
    <div class="mb-8 mt-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Schedule Details</h1>
                <p class="text-sm text-muted-foreground mt-1">{{ $schedule->name ?? 'Schedule Information' }}</p>
            </div>
            
            <div class="flex items-center space-x-3">
                @can('edit_schedules')
                <x-ui.button href="{{ route('schedules.edit', $schedule) }}">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit Schedule
                </x-ui.button>
                @endcan
                <x-ui.button variant="outline" href="{{ route('schedules.index') }}">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to Schedules
                </x-ui.button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Information -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Basic Details -->
            <x-ui.card>
                <x-slot name="title">Schedule Information</x-slot>
                <x-slot name="subtitle">Course and timing details</x-slot>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-muted-foreground mb-1">Schedule Name</label>
                            <div class="text-lg font-semibold text-foreground">{{ $schedule->name ?? 'Unnamed Schedule' }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-muted-foreground mb-1">Subject</label>
                            <div class="text-sm text-foreground">{{ $schedule->subject ?? 'Not specified' }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-muted-foreground mb-1">Class/Grade</label>
                            <div class="text-sm text-foreground">{{ $schedule->class_grade ?? 'Not specified' }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-muted-foreground mb-1">Room/Location</label>
                            <div class="text-sm text-foreground">{{ $schedule->room ?? 'Not specified' }}</div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-muted-foreground mb-1">Day of Week</label>
                            <div class="text-sm text-foreground">{{ ucfirst($schedule->day_of_week) ?? 'Not specified' }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-muted-foreground mb-1">Time</label>
                            <div class="text-lg font-semibold text-foreground">
                                {{ $schedule->start_time?->format('g:i A') ?? 'N/A' }} - {{ $schedule->end_time?->format('g:i A') ?? 'N/A' }}
                            </div>
                            @if($schedule->start_time && $schedule->end_time)
                            <div class="text-sm text-muted-foreground">
                                Duration: {{ $schedule->start_time->diffInMinutes($schedule->end_time) }} minutes
                            </div>
                            @endif
                        </div>

                        @if($schedule->period_number)
                        <div>
                            <label class="block text-sm font-medium text-muted-foreground mb-1">Period</label>
                            <x-ui.badge variant="info">Period {{ $schedule->period_number }}</x-ui.badge>
                        </div>
                        @endif

                        <div>
                            <label class="block text-sm font-medium text-muted-foreground mb-1">Status</label>
                            <x-ui.badge variant="{{ $schedule->is_active ? 'success' : 'secondary' }}">
                                {{ $schedule->is_active ? 'Active' : 'Inactive' }}
                            </x-ui.badge>
                        </div>
                    </div>
                </div>
            </x-ui.card>

            <!-- Teacher Assignment -->
            @if($schedule->teacher)
            <x-ui.card>
                <x-slot name="title">Teacher Assignment</x-slot>
                <x-slot name="subtitle">Assigned instructor details</x-slot>
                
                <div class="flex items-center space-x-4">
                    <x-ui.avatar :name="$schedule->teacher->full_name" size="lg" />
                    <div class="flex-1">
                        <div class="text-lg font-semibold text-foreground">{{ $schedule->teacher->full_name }}</div>
                        <div class="text-sm text-muted-foreground">{{ $schedule->teacher->employee_id }}</div>
                        <div class="text-sm text-muted-foreground">{{ $schedule->teacher->user->email }}</div>
                        @if($schedule->teacher->phone)
                        <div class="text-sm text-muted-foreground">{{ $schedule->teacher->phone }}</div>
                        @endif
                    </div>
                    <div class="flex space-x-2">
                        <x-ui.button variant="outline" size="sm" href="{{ route('employees.show', $schedule->teacher) }}">
                            View Profile
                        </x-ui.button>
                        @can('edit_schedules')
                        <x-ui.button variant="outline" size="sm" href="mailto:{{ $schedule->teacher->user->email }}">
                            Contact
                        </x-ui.button>
                        @endcan
                    </div>
                </div>
            </x-ui.card>
            @else
            <x-ui.card>
                <x-slot name="title">Teacher Assignment</x-slot>
                <x-slot name="subtitle">No teacher assigned</x-slot>
                
                <div class="text-center py-6 text-muted-foreground">
                    <svg class="h-12 w-12 mx-auto mb-3 text-muted-foreground/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <p class="text-lg font-medium">No Teacher Assigned</p>
                    <p class="text-sm">This schedule is currently unassigned</p>
                    @can('edit_schedules')
                    <x-ui.button href="{{ route('schedules.edit', $schedule) }}" size="sm" class="mt-3">
                        Assign Teacher
                    </x-ui.button>
                    @endcan
                </div>
            </x-ui.card>
            @endif

            <!-- Academic Information -->
            @if($schedule->semester || $schedule->academic_year || $schedule->start_date || $schedule->end_date)
            <x-ui.card>
                <x-slot name="title">Academic Information</x-slot>
                <x-slot name="subtitle">Term and period details</x-slot>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @if($schedule->semester)
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Semester/Term</label>
                        <div class="text-sm text-foreground">{{ $schedule->semester }}</div>
                    </div>
                    @endif

                    @if($schedule->academic_year)
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Academic Year</label>
                        <div class="text-sm text-foreground">{{ $schedule->academic_year }}</div>
                    </div>
                    @endif

                    @if($schedule->start_date)
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Start Date</label>
                        <div class="text-sm text-foreground">{{ $schedule->start_date->format('F j, Y') }}</div>
                    </div>
                    @endif

                    @if($schedule->end_date)
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">End Date</label>
                        <div class="text-sm text-foreground">{{ $schedule->end_date->format('F j, Y') }}</div>
                    </div>
                    @endif
                </div>
            </x-ui.card>
            @endif

            <!-- Description -->
            @if($schedule->description)
            <x-ui.card>
                <x-slot name="title">Description</x-slot>
                <x-slot name="subtitle">Additional notes and information</x-slot>
                
                <div class="prose prose-sm max-w-none">
                    <p class="text-foreground">{{ $schedule->description }}</p>
                </div>
            </x-ui.card>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Quick Actions -->
            <x-ui.card>
                <x-slot name="title">Quick Actions</x-slot>
                
                <div class="space-y-3">
                    @can('edit_schedules')
                    <x-ui.button href="{{ route('schedules.edit', $schedule) }}" class="w-full" variant="outline">
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit Schedule
                    </x-ui.button>
                    @endcan

                    @if($schedule->teacher)
                    <x-ui.button href="{{ route('employees.show', $schedule->teacher) }}" class="w-full" variant="outline">
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        View Teacher
                    </x-ui.button>
                    @endif

                    <x-ui.button href="{{ route('attendance.index') }}?schedule_id={{ $schedule->id }}" class="w-full" variant="outline">
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h2m0 0h10a2 2 0 002-2V7a2 2 0 00-2-2H9m0 0V3"/>
                        </svg>
                        View Attendance
                    </x-ui.button>

                    <x-ui.button href="{{ route('schedules.calendar') }}" class="w-full" variant="outline">
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        Calendar View
                    </x-ui.button>
                </div>
            </x-ui.card>

            <!-- Schedule Settings -->
            <x-ui.card>
                <x-slot name="title">Schedule Settings</x-slot>
                
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-muted-foreground">Status:</span>
                        <x-ui.badge variant="{{ $schedule->is_active ? 'success' : 'secondary' }}">
                            {{ $schedule->is_active ? 'Active' : 'Inactive' }}
                        </x-ui.badge>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-muted-foreground">Attendance Required:</span>
                        <x-ui.badge variant="{{ $schedule->attendance_required ? 'info' : 'secondary' }}">
                            {{ $schedule->attendance_required ? 'Yes' : 'No' }}
                        </x-ui.badge>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-muted-foreground">Recurring:</span>
                        <x-ui.badge variant="{{ $schedule->is_recurring ? 'info' : 'secondary' }}">
                            {{ $schedule->is_recurring ? 'Weekly' : 'One-time' }}
                        </x-ui.badge>
                    </div>
                </div>
            </x-ui.card>

            <!-- Record Information -->
            <x-ui.card>
                <x-slot name="title">Record Information</x-slot>
                
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Schedule ID:</span>
                        <span class="font-mono text-foreground">#{{ $schedule->id }}</span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Created:</span>
                        <span class="text-foreground">{{ $schedule->created_at?->format('M j, Y g:i A') }}</span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Last Updated:</span>
                        <span class="text-foreground">{{ $schedule->updated_at?->format('M j, Y g:i A') }}</span>
                    </div>
                    
                    @if($schedule->created_by)
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Created By:</span>
                        <span class="text-foreground">{{ $schedule->creator->name ?? 'System' }}</span>
                    </div>
                    @endif
                </div>
            </x-ui.card>

            <!-- Next Occurrence -->
            @if($schedule->is_active && $schedule->is_recurring)
            <x-ui.card>
                <x-slot name="title">Next Occurrence</x-slot>
                
                <div class="text-center py-4">
                    @php
                        $nextOccurrence = \Carbon\Carbon::now()->next(ucfirst($schedule->day_of_week));
                        if ($schedule->start_time) {
                            $nextOccurrence = $nextOccurrence->setTimeFrom($schedule->start_time);
                        }
                    @endphp
                    <div class="text-lg font-semibold text-foreground">
                        {{ $nextOccurrence->format('F j, Y') }}
                    </div>
                    <div class="text-sm text-muted-foreground">
                        {{ $nextOccurrence->format('l') }} at {{ $schedule->start_time?->format('g:i A') ?? 'TBD' }}
                    </div>
                    <div class="text-xs text-muted-foreground mt-1">
                        {{ $nextOccurrence->diffForHumans() }}
                    </div>
                </div>
            </x-ui.card>
            @endif
        </div>
    </div>
@endsection