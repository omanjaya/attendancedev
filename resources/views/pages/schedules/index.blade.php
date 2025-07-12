@extends('layouts.authenticated')

@section('title', 'Schedule Management')

@section('page-content')
<x-layouts.page-base 
    title="Schedule Management"
    subtitle="Manage class schedules and teacher assignments"
    :show-background="true"
    :show-welcome="true"
    welcome-title="Schedule Management"
    welcome-subtitle="Organize and track class periods, teacher assignments, and weekly schedules">

    @php
    $scheduleStats = [
        [
            'title' => 'Total Periods',
            'value' => $periods->count(),
            'change' => 'Configured periods',
            'change_type' => 'neutral',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
            'iconBg' => 'bg-blue-100 text-blue-600 dark:bg-blue-800 dark:text-blue-300',
        ],
        [
            'title' => 'Active Teachers',
            'value' => $employees->where('employee_type', 'honorary')->count(),
            'change' => 'Honorary teachers',
            'change_type' => 'neutral',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/></svg>',
            'iconBg' => 'bg-green-100 text-green-600 dark:bg-green-800 dark:text-green-300',
        ],
        [
            'title' => 'Total Schedules',
            'value' => \App\Models\EmployeeSchedule::active()->count(),
            'change' => 'Active assignments',
            'change_type' => 'neutral',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>',
            'iconBg' => 'bg-purple-100 text-purple-600 dark:bg-purple-800 dark:text-purple-300',
        ],
        [
            'title' => 'Unassigned Periods',
            'value' => $periods->filter(function($period) { return $period->schedules->isEmpty(); })->count(),
            'change' => 'Need teachers',
            'change_type' => 'negative',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>',
            'iconBg' => 'bg-yellow-100 text-yellow-600 dark:bg-yellow-800 dark:text-yellow-300',
        ]
    ];
    @endphp

    <!-- Compact Stats Widget -->
    <x-layouts.glass-card class="mb-4">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($scheduleStats as $stat)
            <div class="flex items-center space-x-3 p-3">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 rounded-lg {{ $stat['iconBg'] }} flex items-center justify-center">
                        {!! $stat['icon'] !!}
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $stat['value'] }}</p>
                    <p class="text-xs text-gray-600 dark:text-gray-300 truncate">{{ $stat['title'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </x-layouts.glass-card>

    <!-- Schedule Management -->
    <x-layouts.glass-card>
        <!-- Header with Action Buttons -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Weekly Schedule</h2>
                <p class="text-sm text-gray-600 dark:text-gray-300">Manage class periods and teacher assignments</p>
            </div>
            
            <!-- Quick Action Buttons -->
            <div class="flex flex-wrap gap-2">
                <button onclick="location.href='{{ route('schedules.create') }}'" 
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors duration-200 shadow-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Add Schedule
                </button>
                
                <button onclick="location.href='{{ route('schedules.calendar') }}'" 
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 rounded-lg transition-colors duration-200 shadow-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Calendar View
                </button>
                
                <button onclick="openImportModal()" 
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 rounded-lg transition-colors duration-200 shadow-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    Import Schedule
                </button>
            </div>
        </div>

        <!-- Filter dan Day Tabs -->
        <div class="mb-6 space-y-4">
            <!-- Filter Controls -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 p-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl">
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-2">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.414A1 1 0 013 6.707V4z"/>
                        </svg>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Filter:</span>
                    </div>
                    <select class="px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500">
                        <option>Semua Guru</option>
                        <option>Guru Tetap</option>
                        <option>Guru Honorer</option>
                        <option>Belum Ditugaskan</option>
                    </select>
                </div>
                
                <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Total: {{ $periods->count() }} periode</span>
                </div>
            </div>
            
            <!-- Day Navigation Tabs -->
            <div class="bg-white dark:bg-gray-800 p-2 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="grid grid-cols-5 gap-2">
                    @php $days = [
                        1 => ['name' => 'Senin', 'short' => 'Sen'],
                        2 => ['name' => 'Selasa', 'short' => 'Sel'], 
                        3 => ['name' => 'Rabu', 'short' => 'Rab'],
                        4 => ['name' => 'Kamis', 'short' => 'Kam'],
                        5 => ['name' => 'Jumat', 'short' => 'Jum']
                    ] @endphp
                    
                    @foreach($days as $dayNum => $day)
                    <button data-day="{{ $dayNum }}" class="day-tab group relative p-3 rounded-lg transition-all duration-200 {{ $dayNum == 1 ? 'bg-emerald-500 text-white shadow-lg' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                        <div class="text-center">
                            <div class="text-sm font-semibold">{{ $day['name'] }}</div>
                            <div class="text-xs opacity-75 mt-1">{{ $periods->where('day_of_week', $dayNum)->count() }} periode</div>
                        </div>
                        @if($dayNum == 1)
                            <div class="absolute inset-0 bg-white/20 rounded-lg"></div>
                        @endif
                    </button>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Schedule Table - Enhanced -->
        <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="w-full caption-bottom text-sm">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800">
                    <tr>
                        <th class="text-left px-6 py-4 text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Period</span>
                            </div>
                        </th>
                        <th class="text-left px-6 py-4 text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Time</span>
                            </div>
                        </th>
                        <th class="text-left px-6 py-4 text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                </svg>
                                <span>Subject</span>
                            </div>
                        </th>
                        <th class="text-left px-6 py-4 text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                <span>Teacher</span>
                            </div>
                        </th>
                        <th class="text-left px-6 py-4 text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                                <span>Room</span>
                            </div>
                        </th>
                        <th class="text-left px-6 py-4 text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Status</span>
                            </div>
                        </th>
                        <th class="text-right px-6 py-4 text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($periods as $period)
                    <tr class="hover:bg-gradient-to-r hover:from-blue-50 hover:to-indigo-50 dark:hover:from-gray-700 dark:hover:to-gray-600 transition-all duration-200" data-day-of-week="{{ $period->day_of_week }}" style="{{ $period->day_of_week != 1 ? 'display: none;' : '' }}">
                        <td class="px-6 py-5 whitespace-nowrap">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center text-white font-semibold text-sm">
                                    {{ $period->period_number ?? substr($period->name, -1) }}
                                </div>
                                <div>
                                    <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $period->name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'][$period->day_of_week] }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-5 whitespace-nowrap">
                            <div class="flex items-center space-x-2">
                                <div class="w-2 h-2 bg-emerald-400 rounded-full animate-pulse"></div>
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $period->start_time->format('H:i') }} - {{ $period->end_time->format('H:i') }}
                                </div>
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Duration: {{ $period->duration_minutes ?? 60 }}min
                            </div>
                        </td>
                        <td class="px-6 py-5 whitespace-nowrap">
                            <div class="flex items-center space-x-2">
                                <div class="w-8 h-8 bg-gradient-to-br from-purple-500 to-pink-600 rounded-md flex items-center justify-center text-white text-xs font-bold">
                                    {{ substr($period->subject ?? 'NS', 0, 2) }}
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ $period->subject ?? 'Not Set' }}
                                    </div>
                                    @if($period->class_name)
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $period->class_name }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-5 whitespace-nowrap">
                            @php
                                $currentSchedule = $period->schedules()->active()->current()->first();
                            @endphp
                            
                            @if($currentSchedule && $currentSchedule->employee)
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-green-400 to-blue-500 rounded-full flex items-center justify-center text-white font-semibold text-sm">
                                        {{ substr($currentSchedule->employee->full_name, 0, 2) }}
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $currentSchedule->employee->full_name }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ ucfirst($currentSchedule->employee->employee_type) }}
                                        </div>
                                    </div>
                                </div>
                            @else
                                <button onclick="assignTeacher('{{ $period->id }}')" 
                                        class="flex items-center space-x-2 px-3 py-2 text-sm font-medium text-emerald-600 hover:text-emerald-800 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-900 dark:text-emerald-300 rounded-lg transition-all duration-200">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                    </svg>
                                    <span>Assign Teacher</span>
                                </button>
                            @endif
                        </td>
                        <td class="px-6 py-5 whitespace-nowrap">
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $period->room ?? 'Not Set' }}
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-5 whitespace-nowrap">
                            @if($currentSchedule)
                                <div class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    Assigned
                                </div>
                            @else
                                <div class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                    Unassigned
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-5 whitespace-nowrap text-right">
                            <div class="flex items-center justify-end space-x-2">
                                @if($currentSchedule)
                                    <button type="button" 
                                            onclick="editSchedule('{{ $currentSchedule->id }}')" 
                                            class="inline-flex items-center justify-center w-9 h-9 text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 dark:bg-blue-900 dark:text-blue-300 rounded-lg transition-all duration-200 transform hover:scale-105"
                                            title="Edit Schedule">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    
                                    <button type="button" 
                                            onclick="deleteSchedule('{{ $currentSchedule->id }}')" 
                                            class="inline-flex items-center justify-center w-9 h-9 text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 dark:bg-red-900 dark:text-red-300 rounded-lg transition-all duration-200 transform hover:scale-105"
                                            title="Delete Schedule">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                @else
                                    <button type="button" 
                                            onclick="assignTeacher('{{ $period->id }}')" 
                                            class="inline-flex items-center justify-center w-9 h-9 text-emerald-600 hover:text-emerald-800 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-900 dark:text-emerald-300 rounded-lg transition-all duration-200 transform hover:scale-105"
                                            title="Assign Teacher">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center justify-center space-y-4">
                                    <div class="w-20 h-20 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <div class="text-center">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Belum Ada Periode</h3>
                                        <p class="text-gray-500 dark:text-gray-400 mb-6 max-w-sm">Konfigurasikan periode sekolah untuk mulai mengelola jadwal pelajaran.</p>
                                        <button onclick="location.href='{{ route('schedules.create') }}'" 
                                                class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors duration-200">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                            </svg>
                                            Buat Periode Pertama
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-layouts.glass-card>

    <!-- Teacher Load Summary - Enhanced -->
    <x-layouts.glass-card class="mt-6">
        <div class="mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Teacher Load Summary</h3>
            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Weekly teaching hours and assignments</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @php
            $teacherLoads = [];
            
            // Calculate teacher loads from actual data
            foreach($employees as $employee) {
                if ($employee->employee_type === 'honorary') {
                    $activeSchedules = $employee->schedules()->active()->current()->count();
                    $totalHours = $employee->schedules()
                        ->active()
                        ->current()
                        ->with('period')
                        ->get()
                        ->sum(function($schedule) {
                            return $schedule->period->duration_minutes / 60;
                        });
                    
                    $maxHours = 40; // Maximum working hours per week
                    $workload = $totalHours > 0 ? min(($totalHours / $maxHours) * 100, 100) : 0;
                    
                    $teacherLoads[] = [
                        'name' => $employee->full_name,
                        'subject' => $employee->department ?? 'General',
                        'periods' => $activeSchedules,
                        'hours' => round($totalHours, 2),
                        'load' => round($workload, 0)
                    ];
                }
            }
            
            // If no teacher data, show placeholder
            if (empty($teacherLoads)) {
                $teacherLoads = [
                    ['name' => 'No teachers assigned', 'subject' => 'N/A', 'periods' => 0, 'hours' => 0, 'load' => 0]
                ];
            }
            @endphp
            
            @foreach($teacherLoads as $teacher)
            <div class="group relative p-6 bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-700 rounded-xl border border-gray-200 dark:border-gray-600 hover:shadow-xl hover:scale-105 transition-all duration-300">
                <!-- Background decoration -->
                <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-blue-500/10 to-purple-500/10 rounded-bl-full"></div>
                
                <div class="relative">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center text-white font-bold text-lg shadow-lg">
                                {{ substr($teacher['name'], 0, 2) }}
                            </div>
                            <div>
                                <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ $teacher['name'] }}</h4>
                                <p class="text-sm text-gray-500 dark:text-gray-400 flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                    </svg>
                                    {{ $teacher['subject'] }}
                                </p>
                            </div>
                        </div>
                        
                        <!-- Status Badge -->
                        <div class="text-right">
                            <div class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $teacher['load'] > 80 ? 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100' : ($teacher['load'] > 60 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100' : 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100') }}">
                                {{ $teacher['load'] > 80 ? 'Overloaded' : ($teacher['load'] > 60 ? 'Busy' : 'Available') }}
                            </div>
                        </div>
                    </div>
                    
                    <!-- Statistics -->
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="text-center p-3 bg-blue-50 dark:bg-blue-900/30 rounded-lg">
                            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $teacher['periods'] }}</div>
                            <div class="text-xs text-blue-500 dark:text-blue-300">Periods</div>
                        </div>
                        <div class="text-center p-3 bg-emerald-50 dark:bg-emerald-900/30 rounded-lg">
                            <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $teacher['hours'] }}</div>
                            <div class="text-xs text-emerald-500 dark:text-emerald-300">Hours/Week</div>
                        </div>
                    </div>
                    
                    <!-- Progress Bar -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-gray-700 dark:text-gray-300">Workload</span>
                            <span class="font-bold text-gray-900 dark:text-gray-100">{{ $teacher['load'] }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-3 overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-500 ease-out {{ $teacher['load'] > 80 ? 'bg-gradient-to-r from-red-500 to-red-600' : ($teacher['load'] > 60 ? 'bg-gradient-to-r from-yellow-500 to-orange-500' : 'bg-gradient-to-r from-green-500 to-emerald-500') }}" 
                                 style="width: {{ $teacher['load'] }}%">
                                <div class="h-full bg-white bg-opacity-20 animate-pulse"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </x-layouts.glass-card>

</x-layouts.page-base>

<!-- Import Schedule Modal -->
<div id="import-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-black bg-opacity-50 backdrop-blur-sm">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="relative w-full max-w-lg transform transition-all duration-300 scale-95 opacity-0" id="import-modal-content">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-white">Import Jadwal</h3>
                                <p class="text-blue-100 text-sm">Upload file Excel atau CSV</p>
                            </div>
                        </div>
                        <button onclick="closeImportModal()" class="text-white hover:text-blue-200 transition-colors duration-200">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <!-- Modal Body -->
                <div class="p-6">
                    <form id="import-form" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        
                        <!-- File Upload Area -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Pilih File Jadwal
                            </label>
                            <div class="relative">
                                <input type="file" id="schedule-file" name="schedule_file" accept=".xlsx,.xls,.csv" 
                                       class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:text-gray-100 transition-all duration-200 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                    </svg>
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Format yang didukung: Excel (.xlsx, .xls) atau CSV (.csv)
                            </p>
                        </div>
                        
                        <!-- Template Download -->
                        <div class="bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-xl p-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-blue-100 dark:bg-blue-800 rounded-lg flex items-center justify-center">
                                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-blue-800 dark:text-blue-200">Template Jadwal</h4>
                                        <p class="text-sm text-blue-700 dark:text-blue-300">Download template untuk format yang benar</p>
                                    </div>
                                </div>
                                <a href="{{ route('schedules.download-template') }}" 
                                   class="inline-flex items-center px-3 py-2 text-sm font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200 bg-white dark:bg-gray-800 border border-blue-300 dark:border-blue-600 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/50 transition-all duration-200">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                    Download
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Modal Footer -->
                <div class="bg-gray-50 dark:bg-gray-700 px-6 py-4 flex justify-end space-x-3">
                    <button type="button" onclick="closeImportModal()" 
                            class="px-6 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 hover:bg-gray-50 dark:hover:bg-gray-500 rounded-xl transition-all duration-200">
                        Batal
                    </button>
                    <button type="submit" form="import-form"
                            class="px-6 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 rounded-xl transition-all duration-200 shadow-lg">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        Import Jadwal
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Assign Teacher Modal - Enhanced -->
<div id="assign-teacher-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-black bg-opacity-50 backdrop-blur-sm">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="relative w-full max-w-lg transform transition-all duration-300 scale-95 opacity-0" id="assign-teacher-modal-content">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-emerald-500 to-teal-600 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-white">Assign Teacher</h3>
                                <p class="text-emerald-100 text-sm">Assign a teacher to this period</p>
                            </div>
                        </div>
                        <button onclick="closeAssignTeacherModal()" class="text-white hover:text-emerald-200 transition-colors duration-200">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <!-- Modal Body -->
                    <form id="assign-teacher-form" class="space-y-6">
                        @csrf
                        <input type="hidden" id="period_id" name="period_id">
                        
                        <!-- Teacher Selection -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                Select Teacher
                            </label>
                            <div class="relative">
                                <select id="teacher_id" name="employee_id" required
                                        class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:text-gray-100 transition-all duration-200">
                                    <option value="">Choose a teacher...</option>
                                    @foreach($employees->where('employee_type', 'honorary') as $employee)
                                        <option value="{{ $employee->id }}" data-type="{{ $employee->employee_type }}">
                                            {{ $employee->full_name }} - {{ ucfirst($employee->employee_type) }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Date Range -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    Effective Date
                                </label>
                                <input type="date" id="effective_date" name="effective_date" required
                                       value="{{ date('Y-m-d') }}"
                                       class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:text-gray-100 transition-all duration-200">
                            </div>
                            
                            <div class="space-y-2">
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    End Date (Optional)
                                </label>
                                <input type="date" id="end_date" name="end_date"
                                       class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:text-gray-100 transition-all duration-200">
                            </div>
                        </div>
                        
                        <!-- Assignment Notes -->
                        <div class="bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-xl p-4">
                            <div class="flex items-start space-x-3">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <div>
                                    <h4 class="text-sm font-medium text-blue-800 dark:text-blue-200">Assignment Information</h4>
                                    <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">
                                        The teacher will be assigned to this period and will be responsible for attendance and class management.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Modal Footer -->
                <div class="bg-gray-50 dark:bg-gray-700 px-6 py-4 flex justify-end space-x-3">
                    <button type="button" onclick="closeAssignTeacherModal()" 
                            class="px-6 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 hover:bg-gray-50 dark:hover:bg-gray-500 rounded-xl transition-all duration-200 transform hover:scale-105">
                        Cancel
                    </button>
                    <button type="submit" form="assign-teacher-form"
                            class="px-6 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 rounded-xl transition-all duration-200 transform hover:scale-105 shadow-lg">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Assign Teacher
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Notification function
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg ${
        type === 'success' ? 'bg-green-500' : 
        type === 'error' ? 'bg-red-500' : 
        type === 'warning' ? 'bg-yellow-500' : 'bg-blue-500'
    } text-white`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Schedule functions
function editSchedule(scheduleId) {
    console.log('Edit schedule:', scheduleId);
    window.location.href = `/schedules/${scheduleId}/edit`;
}

function deleteSchedule(scheduleId) {
    if (confirm('Are you sure you want to delete this schedule assignment?')) {
        // Show loading indicator
        const button = event.target.closest('button');
        const originalContent = button.innerHTML;
        button.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
        button.disabled = true;
        
        fetch(`/schedules/${scheduleId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                // Reload the page to show updated assignments
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred while deleting the schedule', 'error');
        })
        .finally(() => {
            button.innerHTML = originalContent;
            button.disabled = false;
        });
    }
}

function assignTeacher(periodId) {
    console.log('Assign teacher to period:', periodId);
    openAssignTeacherModal(periodId);
}

// Enhanced Import Modal
function openImportModal() {
    const modal = document.getElementById('import-modal');
    const modalContent = document.getElementById('import-modal-content');
    
    modal.classList.remove('hidden');
    
    // Animate modal entrance
    setTimeout(() => {
        modalContent.style.transform = 'scale(1)';
        modalContent.style.opacity = '1';
    }, 10);
}

function closeImportModal() {
    const modal = document.getElementById('import-modal');
    const modalContent = document.getElementById('import-modal-content');
    
    // Animate modal exit
    modalContent.style.transform = 'scale(0.95)';
    modalContent.style.opacity = '0';
    
    setTimeout(() => {
        modal.classList.add('hidden');
        document.getElementById('import-form').reset();
        // Reset animation state
        modalContent.style.transform = 'scale(0.95)';
        modalContent.style.opacity = '0';
    }, 300);
}

// Enhanced Assign Teacher Modal
function openAssignTeacherModal(periodId) {
    document.getElementById('period_id').value = periodId;
    const modal = document.getElementById('assign-teacher-modal');
    const modalContent = document.getElementById('assign-teacher-modal-content');
    
    modal.classList.remove('hidden');
    
    // Animate modal entrance
    setTimeout(() => {
        modalContent.style.transform = 'scale(1)';
        modalContent.style.opacity = '1';
    }, 10);
}

function closeAssignTeacherModal() {
    const modal = document.getElementById('assign-teacher-modal');
    const modalContent = document.getElementById('assign-teacher-modal-content');
    
    // Animate modal exit
    modalContent.style.transform = 'scale(0.95)';
    modalContent.style.opacity = '0';
    
    setTimeout(() => {
        modal.classList.add('hidden');
        document.getElementById('assign-teacher-form').reset();
        // Reset animation state
        modalContent.style.transform = 'scale(0.95)';
        modalContent.style.opacity = '0';
    }, 300);
}

// Enhanced Day tab switching
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.day-tab');
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Remove active state from all tabs
            tabs.forEach(t => {
                t.classList.remove('bg-emerald-500', 'text-white', 'shadow-sm');
                t.classList.add('text-gray-600', 'dark:text-gray-400', 'hover:bg-white', 'dark:hover:bg-gray-700', 'hover:text-gray-900', 'dark:hover:text-gray-100');
                // Update badge styles
                const badge = t.querySelector('span:last-child');
                if (badge) {
                    badge.classList.remove('bg-white', 'bg-opacity-20');
                    badge.classList.add('bg-gray-200', 'dark:bg-gray-600');
                }
            });
            
            // Add active state to clicked tab
            this.classList.remove('text-gray-600', 'dark:text-gray-400', 'hover:bg-white', 'dark:hover:bg-gray-700', 'hover:text-gray-900', 'dark:hover:text-gray-100');
            this.classList.add('bg-emerald-500', 'text-white', 'shadow-sm');
            // Update active badge style
            const activeBadge = this.querySelector('span:last-child');
            if (activeBadge) {
                activeBadge.classList.remove('bg-gray-200', 'dark:bg-gray-600');
                activeBadge.classList.add('bg-white', 'bg-opacity-20');
            }
            
            // Get day number from data attribute
            const dayOfWeek = this.getAttribute('data-day');
            
            // Filter table rows by day with animation
            const tbody = document.querySelector('tbody');
            const rows = tbody.querySelectorAll('tr');
            
            rows.forEach(row => {
                const periodDayOfWeek = row.getAttribute('data-day-of-week');
                if (periodDayOfWeek && periodDayOfWeek == dayOfWeek) {
                    row.style.display = '';
                    // Add fade-in animation
                    row.style.opacity = '0';
                    setTimeout(() => {
                        row.style.transition = 'opacity 0.3s ease-in-out';
                        row.style.opacity = '1';
                    }, 50);
                } else if (periodDayOfWeek) {
                    row.style.display = 'none';
                }
            });
            
            const dayName = this.textContent.trim().split('\n')[0].trim();
            showNotification(`Showing ${dayName} schedule`, 'success');
        });
    });
});

// Import form submission
document.getElementById('import-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const fileInput = document.getElementById('schedule-file');
    if (!fileInput.files.length) {
        showNotification('Please select a file', 'error');
        return;
    }
    
    // Show loading
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Importing...';
    submitBtn.disabled = true;
    
    // Create form data
    const formData = new FormData();
    formData.append('schedule_file', fileInput.files[0]);
    formData.append('_token', '{{ csrf_token() }}');
    
    // Send to server
    fetch('/schedules/import', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message || 'Schedule imported successfully', 'success');
            closeImportModal();
            // Reload the page to show imported schedules
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showNotification(data.message || 'Import failed', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred during import', 'error');
    })
    .finally(() => {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
});

// Assign Teacher form submission
document.getElementById('assign-teacher-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    
    // Show loading
    submitBtn.textContent = 'Assigning...';
    submitBtn.disabled = true;
    
    // Prepare data for single employee assignment
    const data = {
        period_id: formData.get('period_id'),
        employee_ids: [formData.get('employee_id')],
        effective_date: formData.get('effective_date'),
        end_date: formData.get('end_date'),
        _token: '{{ csrf_token() }}'
    };
    
    fetch('{{ route('schedules.assign-employees') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            closeAssignTeacherModal();
            // Reload the page to show updated assignments
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred while assigning teacher', 'error');
    })
    .finally(() => {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
});

// Close modals when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.id === 'import-modal') {
        closeImportModal();
    }
    if (e.target.id === 'assign-teacher-modal') {
        closeAssignTeacherModal();
    }
});
</script>
@endpush