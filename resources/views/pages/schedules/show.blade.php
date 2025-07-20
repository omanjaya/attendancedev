@extends('layouts.authenticated-unified')

@section('title', 'Schedule Details')

@section('page-content')
    <!-- Page Header -->
    <div class="mb-8 mt-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Schedule Details</h1>
                <p class="text-sm text-muted-foreground mt-1">View {{ $schedule->employee->user->name ?? 'schedule' }} assignment details</p>
            </div>
            
            <div class="flex items-center space-x-3">
                <x-ui.button variant="outline" href="{{ route('schedules.index') }}">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to Schedules
                </x-ui.button>
                
                @can('create_schedules')
                    <x-ui.button variant="primary" href="{{ route('schedules.edit', $schedule) }}">
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit Schedule
                    </x-ui.button>
                @endcan
            </div>
        </div>
    </div>

    <!-- Schedule Details Card -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Employee Information -->
        <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Employee Information</h3>
                
                <div class="space-y-4">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <div class="h-12 w-12 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                <span class="text-lg font-semibold text-gray-600 dark:text-gray-300">
                                    {{ substr($schedule->employee->user->name ?? 'N', 0, 1) }}
                                </span>
                            </div>
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $schedule->employee->user->name ?? 'N/A' }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $schedule->employee->employee_id ?? 'N/A' }}
                            </div>
                        </div>
                    </div>
                    
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        <dl class="grid grid-cols-1 gap-3">
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</dt>
                                <dd class="text-sm text-gray-900 dark:text-white">{{ $schedule->employee->user->email ?? 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Department</dt>
                                <dd class="text-sm text-gray-900 dark:text-white">{{ $schedule->employee->department ?? 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Position</dt>
                                <dd class="text-sm text-gray-900 dark:text-white">{{ $schedule->employee->position ?? 'N/A' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </x-ui.card>

        <!-- Schedule Information -->
        <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Schedule Information</h3>
                
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Day</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">
                                @php
                                    $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                                @endphp
                                {{ $days[$schedule->period->day_of_week ?? 0] ?? 'N/A' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Period</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">{{ $schedule->period->name ?? 'N/A' }}</dd>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Start Time</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">
                                {{ $schedule->period ? \Carbon\Carbon::parse($schedule->period->start_time)->format('H:i') : 'N/A' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">End Time</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">
                                {{ $schedule->period ? \Carbon\Carbon::parse($schedule->period->end_time)->format('H:i') : 'N/A' }}
                            </dd>
                        </div>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Subject</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">{{ $schedule->period->subject ?? 'N/A' }}</dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Room</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">{{ $schedule->period->room ?? 'N/A' }}</dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                        <dd class="text-sm">
                            @if($schedule->is_active ?? true)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400">
                                    Inactive
                                </span>
                            @endif
                        </dd>
                    </div>
                </div>
            </div>
        </x-ui.card>
    </div>

    <!-- Notes Section -->
    @if($schedule->notes ?? false)
        <div class="mt-6">
            <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Notes</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ $schedule->notes }}
                    </p>
                </div>
            </x-ui.card>
        </div>
    @endif
@endsection