@extends('layouts.authenticated')

@section('title', 'Attendance Details')

@section('page-content')
    <!-- Page Header -->
    <div class="mb-8 mt-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Attendance Details</h1>
                <p class="text-sm text-muted-foreground mt-1">{{ $attendance->employee->full_name ?? 'Employee' }} - {{ $attendance->date?->format('F j, Y') }}</p>
            </div>
            
            <div class="flex items-center space-x-3">
                @can('edit_attendance')
                <x-ui.button href="{{ route('attendance.edit', $attendance) }}">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit Record
                </x-ui.button>
                @endcan
                <x-ui.button variant="outline" href="{{ route('attendance.index') }}">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to Attendance
                </x-ui.button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Information -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Basic Details -->
            <x-ui.card>
                <x-slot name="title">Basic Information</x-slot>
                <x-slot name="subtitle">Employee and attendance details</x-slot>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-muted-foreground mb-1">Employee</label>
                            <div class="flex items-center space-x-3">
                                <x-ui.avatar :name="$attendance->employee->full_name ?? 'Employee'" size="md" />
                                <div>
                                    <div class="text-sm font-medium text-foreground">{{ $attendance->employee->full_name ?? 'Unknown Employee' }}</div>
                                    <div class="text-sm text-muted-foreground">{{ $attendance->employee->employee_id ?? 'N/A' }}</div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-muted-foreground mb-1">Date</label>
                            <div class="text-sm text-foreground">{{ $attendance->date?->format('l, F j, Y') ?? 'Unknown Date' }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-muted-foreground mb-1">Status</label>
                            <x-ui.badge variant="{{ 
                                match($attendance->status) {
                                    'present' => 'success',
                                    'absent' => 'destructive', 
                                    'late' => 'warning',
                                    'early_departure' => 'info',
                                    default => 'secondary'
                                }
                            }}">
                                {{ ucfirst(str_replace('_', ' ', $attendance->status)) }}
                            </x-ui.badge>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-muted-foreground mb-1">Location</label>
                            <div class="text-sm text-foreground">
                                {{ $attendance->location->name ?? 'No location specified' }}
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-muted-foreground mb-1">Entry Type</label>
                            <x-ui.badge variant="{{ $attendance->is_manual ? 'warning' : 'info' }}">
                                {{ $attendance->is_manual ? 'Manual Entry' : 'Automatic' }}
                            </x-ui.badge>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-muted-foreground mb-1">Verification</label>
                            <x-ui.badge variant="{{ $attendance->verified ? 'success' : 'secondary' }}">
                                {{ $attendance->verified ? 'Verified' : 'Unverified' }}
                            </x-ui.badge>
                        </div>
                    </div>
                </div>
            </x-ui.card>

            <!-- Time Details -->
            @if($attendance->status !== 'absent')
            <x-ui.card>
                <x-slot name="title">Time Details</x-slot>
                <x-slot name="subtitle">Check-in and check-out information</x-slot>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-muted-foreground mb-1">Check In</label>
                            <div class="text-lg font-semibold text-foreground">
                                {{ $attendance->check_in?->format('g:i A') ?? 'Not recorded' }}
                            </div>
                            @if($attendance->check_in)
                            <div class="text-sm text-muted-foreground">
                                {{ $attendance->check_in->format('l, F j, Y') }}
                            </div>
                            @endif
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-muted-foreground mb-1">Check Out</label>
                            <div class="text-lg font-semibold text-foreground">
                                {{ $attendance->check_out?->format('g:i A') ?? 'Not recorded' }}
                            </div>
                            @if($attendance->check_out)
                            <div class="text-sm text-muted-foreground">
                                {{ $attendance->check_out->format('l, F j, Y') }}
                            </div>
                            @endif
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-muted-foreground mb-1">Working Hours</label>
                            <div class="text-lg font-semibold text-foreground">
                                @if($attendance->check_in && $attendance->check_out)
                                    @php
                                        $workingMinutes = $attendance->check_out->diffInMinutes($attendance->check_in);
                                        $breakMinutes = $attendance->break_duration ?? 0;
                                        $totalMinutes = max(0, $workingMinutes - $breakMinutes);
                                        $hours = floor($totalMinutes / 60);
                                        $minutes = $totalMinutes % 60;
                                    @endphp
                                    {{ $hours }}h {{ $minutes }}m
                                @else
                                    Not calculated
                                @endif
                            </div>
                        </div>

                        @if($attendance->break_duration > 0)
                        <div>
                            <label class="block text-sm font-medium text-muted-foreground mb-1">Break Duration</label>
                            <div class="text-sm text-foreground">{{ $attendance->break_duration }} minutes</div>
                        </div>
                        @endif
                    </div>
                </div>
            </x-ui.card>
            @endif

            <!-- Notes -->
            @if($attendance->notes)
            <x-ui.card>
                <x-slot name="title">Notes</x-slot>
                <x-slot name="subtitle">Additional information</x-slot>
                
                <div class="prose prose-sm max-w-none">
                    <p class="text-foreground">{{ $attendance->notes }}</p>
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
                    @can('edit_attendance')
                    <x-ui.button href="{{ route('attendance.edit', $attendance) }}" class="w-full" variant="outline">
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit Record
                    </x-ui.button>
                    @endcan

                    @can('view_employees')
                    <x-ui.button href="{{ route('employees.show', $attendance->employee) }}" class="w-full" variant="outline">
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        View Employee
                    </x-ui.button>
                    @endcan

                    <x-ui.button href="{{ route('attendance.history') }}?employee_id={{ $attendance->employee_id }}" class="w-full" variant="outline">
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Attendance History
                    </x-ui.button>
                </div>
            </x-ui.card>

            <!-- Metadata -->
            <x-ui.card>
                <x-slot name="title">Record Information</x-slot>
                
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Record ID:</span>
                        <span class="font-mono text-foreground">#{{ $attendance->id }}</span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Created:</span>
                        <span class="text-foreground">{{ $attendance->created_at?->format('M j, Y g:i A') }}</span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Last Updated:</span>
                        <span class="text-foreground">{{ $attendance->updated_at?->format('M j, Y g:i A') }}</span>
                    </div>
                    
                    @if($attendance->created_by)
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Created By:</span>
                        <span class="text-foreground">{{ $attendance->creator->name ?? 'System' }}</span>
                    </div>
                    @endif
                </div>
            </x-ui.card>

            <!-- GPS Information -->
            @if($attendance->metadata && (isset($attendance->metadata['check_in_location']) || isset($attendance->metadata['check_out_location'])))
            <x-ui.card>
                <x-slot name="title">Location Data</x-slot>
                <x-slot name="subtitle">GPS verification information</x-slot>
                
                <div class="space-y-3 text-sm">
                    @if(isset($attendance->metadata['check_in_location']))
                    <div>
                        <span class="text-muted-foreground">Check-in Location:</span>
                        <div class="font-mono text-xs text-foreground mt-1">
                            {{ $attendance->metadata['check_in_location']['latitude'] ?? 'N/A' }}, 
                            {{ $attendance->metadata['check_in_location']['longitude'] ?? 'N/A' }}
                        </div>
                    </div>
                    @endif
                    
                    @if(isset($attendance->metadata['check_out_location']))
                    <div>
                        <span class="text-muted-foreground">Check-out Location:</span>
                        <div class="font-mono text-xs text-foreground mt-1">
                            {{ $attendance->metadata['check_out_location']['latitude'] ?? 'N/A' }}, 
                            {{ $attendance->metadata['check_out_location']['longitude'] ?? 'N/A' }}
                        </div>
                    </div>
                    @endif
                </div>
            </x-ui.card>
            @endif
        </div>
    </div>
@endsection