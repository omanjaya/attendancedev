@extends('layouts.authenticated-unified')

@section('title', $monthlySchedule->name . ' - Monthly Schedule')

@section('page-content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="p-6 lg:p-8">
        <!-- Modern Page Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <a href="{{ route('schedule-management.monthly.index') }}" 
                       class="flex items-center justify-center w-10 h-10 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-all duration-200 shadow-sm">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                    <div>
                        <h1 id="schedule-title" class="text-3xl font-bold text-gray-900 dark:text-white">{{ $monthlySchedule->name }}</h1>
                        <p id="schedule-subtitle" class="mt-1 text-sm text-gray-500 dark:text-gray-400">Loading schedule details...</p>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <button onclick="assignEmployees()" 
                            class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-medium transition-all duration-200 shadow-md hover:shadow-lg">
                        <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Assign Employees
                    </button>
                    <button onclick="editSchedule()" 
                            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition-all duration-200 shadow-md hover:shadow-lg">
                        <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit Schedule
                    </button>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div id="loading-state" class="flex flex-col items-center justify-center py-20">
            <div class="relative">
                <div class="animate-spin rounded-full h-16 w-16 border-4 border-blue-200 border-t-blue-600 mb-6"></div>
            </div>
            <p class="text-lg font-semibold text-gray-700 dark:text-gray-300">Loading schedule details...</p>
        </div>

        <!-- Main Content -->
        <div id="main-content" class="hidden space-y-8">
            <!-- Schedule Overview Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Duration Card -->
                <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-gradient-to-br from-blue-500/20 to-blue-600/20 rounded-xl">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a1 1 0 011-1h6a1 1 0 011 1v4h3a1 1 0 011 1v3a1 1 0 01-.293.707L19 14.414V19a2 2 0 01-2 2H7a2 2 0 01-2-2v-4.586l-.707-.707A1 1 0 014 13v-3a1 1 0 011-1h3z"/>
                            </svg>
                        </div>
                        <span class="text-sm text-blue-600 dark:text-blue-400 font-semibold bg-blue-100 dark:bg-blue-900/30 px-3 py-1 rounded-full">Period</span>
                    </div>
                    <h3 id="schedule-duration" class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-1">-</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">Schedule Duration</p>
                </div>

                <!-- Working Hours Card -->
                <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-gradient-to-br from-amber-500/20 to-amber-600/20 rounded-xl">
                            <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <span class="text-sm text-amber-600 dark:text-amber-400 font-semibold bg-amber-100 dark:bg-amber-900/30 px-3 py-1 rounded-full">Hours</span>
                    </div>
                    <h3 id="working-hours" class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-1">-</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">Working Hours</p>
                </div>

                <!-- Location Card -->
                <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-gradient-to-br from-emerald-500/20 to-emerald-600/20 rounded-xl">
                            <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <span class="text-sm text-emerald-600 dark:text-emerald-400 font-semibold bg-emerald-100 dark:bg-emerald-900/30 px-3 py-1 rounded-full">Location</span>
                    </div>
                    <h3 id="location-name" class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-1">-</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">Schedule Location</p>
                </div>

                <!-- Assigned Employees Card -->
                <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-gradient-to-br from-purple-500/20 to-purple-600/20 rounded-xl">
                            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <span class="text-sm text-purple-600 dark:text-purple-400 font-semibold bg-purple-100 dark:bg-purple-900/30 px-3 py-1 rounded-full">Team</span>
                    </div>
                    <h3 id="assigned-count" class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-1">0</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">Assigned Employees</p>
                </div>
            </div>

            <!-- Schedule Details Section -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Working Hours Details -->
                <div class="lg:col-span-2">
                    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white flex items-center">
                                <svg class="w-5 h-5 mr-3 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Weekly Working Hours
                            </h3>
                            <span id="template-badge" class="px-3 py-1 text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-full">Standard Template</span>
                        </div>
                        <div id="working-hours-grid" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Working hours will be populated here -->
                        </div>
                    </div>
                </div>

                <!-- Schedule Information -->
                <div class="space-y-6">
                    <!-- Basic Information -->
                    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl p-6">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-3 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Schedule Information
                        </h3>
                        <div class="space-y-4">
                            <div>
                                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Created By</label>
                                <p id="created-by" class="text-gray-900 dark:text-white font-medium">-</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Created Date</label>
                                <p id="created-date" class="text-gray-900 dark:text-white font-medium">-</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Updated</label>
                                <p id="updated-date" class="text-gray-900 dark:text-white font-medium">-</p>
                            </div>
                            <div id="description-section" class="hidden">
                                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Description</label>
                                <p id="schedule-description" class="text-gray-900 dark:text-white">-</p>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics -->
                    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl p-6">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-3 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            Statistics
                        </h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 dark:text-gray-400">Total Employees</span>
                                <span id="stat-total-employees" class="font-semibold text-gray-900 dark:text-white">0</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 dark:text-gray-400">Schedule Days</span>
                                <span id="stat-schedule-days" class="font-semibold text-gray-900 dark:text-white">0</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 dark:text-gray-400">Working Days</span>
                                <span id="stat-working-days" class="font-semibold text-gray-900 dark:text-white">0</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 dark:text-gray-400">Holiday Days</span>
                                <span id="stat-holiday-days" class="font-semibold text-gray-900 dark:text-white">0</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Assigned Employees Section -->
            <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-5 h-5 mr-3 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        Assigned Employees
                    </h3>
                    <button onclick="assignEmployees()" 
                            class="px-4 py-2 text-sm font-medium text-green-700 dark:text-green-300 bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/40 rounded-lg transition-all duration-200">
                        <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Manage Assignments
                    </button>
                </div>
                <div id="employees-list" class="space-y-3">
                    <!-- Employees will be populated here -->
                </div>
                <div id="no-employees" class="hidden text-center py-8">
                    <svg class="w-12 h-12 mx-auto text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No Employees Assigned</h4>
                    <p class="text-gray-500 dark:text-gray-400 mb-4">This schedule doesn't have any employees assigned yet.</p>
                    <button onclick="assignEmployees()" 
                            class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-medium transition-all duration-200">
                        Assign Employees Now
                    </button>
                </div>
            </div>
        </div>

        <!-- Error State -->
        <div id="error-state" class="hidden text-center py-20">
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-2xl p-8 max-w-md mx-auto">
                <svg class="w-12 h-12 mx-auto text-red-500 dark:text-red-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                </svg>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Failed to Load Schedule</h3>
                <p id="error-message" class="text-red-600 dark:text-red-400 mb-4">An error occurred while loading the schedule details.</p>
                <button onclick="loadScheduleData()" 
                        class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg font-medium transition-all duration-200">
                    Try Again
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
window.currentScheduleId = '{{ $monthlySchedule->id }}';

document.addEventListener('DOMContentLoaded', function() {
    loadScheduleData();
});

async function loadScheduleData() {
    const loadingState = document.getElementById('loading-state');
    const mainContent = document.getElementById('main-content');
    const errorState = document.getElementById('error-state');
    
    try {
        loadingState.classList.remove('hidden');
        mainContent.classList.add('hidden');
        errorState.classList.add('hidden');
        
        const response = await fetch(`/api/schedule-management/monthly/${window.currentScheduleId}`);
        const result = await response.json();
        
        if (result.success && result.data) {
            populateScheduleData(result.data);
            loadingState.classList.add('hidden');
            mainContent.classList.remove('hidden');
        } else {
            throw new Error(result.message || 'Failed to load schedule');
        }
        
    } catch (error) {
        console.error('Error loading schedule:', error);
        loadingState.classList.add('hidden');
        errorState.classList.remove('hidden');
        document.getElementById('error-message').textContent = error.message;
    }
}

function populateScheduleData(schedule) {
    // Header
    document.getElementById('schedule-title').textContent = schedule.name;
    document.getElementById('schedule-subtitle').textContent = `${schedule.month_name || getMonthName(schedule.month)} ${schedule.year} Schedule`;
    
    // Overview Cards
    const duration = `${formatDate(schedule.start_date)} - ${formatDate(schedule.end_date)}`;
    document.getElementById('schedule-duration').textContent = duration;
    document.getElementById('working-hours').textContent = `${schedule.default_start_time} - ${schedule.default_end_time}`;
    document.getElementById('location-name').textContent = schedule.location?.name || 'Unknown Location';
    document.getElementById('assigned-count').textContent = schedule.statistics?.total_assigned_employees || 0;
    
    // Working Hours Grid
    if (schedule.metadata?.working_hours_per_day) {
        populateWorkingHoursGrid(schedule.metadata.working_hours_per_day);
    }
    
    // Template Badge
    const templateName = schedule.metadata?.working_hours_template || 'custom';
    document.getElementById('template-badge').textContent = formatTemplateName(templateName);
    
    // Information
    document.getElementById('created-by').textContent = schedule.created_by || 'System';
    document.getElementById('created-date').textContent = formatDateTime(schedule.created_at);
    document.getElementById('updated-date').textContent = formatDateTime(schedule.updated_at);
    
    // Description
    if (schedule.description) {
        document.getElementById('description-section').classList.remove('hidden');
        document.getElementById('schedule-description').textContent = schedule.description;
    }
    
    // Statistics
    document.getElementById('stat-total-employees').textContent = schedule.statistics?.total_assigned_employees || 0;
    document.getElementById('stat-schedule-days').textContent = schedule.statistics?.total_schedule_days || 0;
    document.getElementById('stat-working-days').textContent = schedule.statistics?.working_days || 0;
    document.getElementById('stat-holiday-days').textContent = schedule.statistics?.holiday_days || 0;
    
    // Employees
    populateEmployeesList(schedule.assigned_employees || []);
}

function populateWorkingHoursGrid(workingHours) {
    const grid = document.getElementById('working-hours-grid');
    const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    const dayNames = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    
    grid.innerHTML = '';
    
    days.forEach((day, index) => {
        const dayData = workingHours[day];
        const isWorking = dayData && dayData.start && dayData.end;
        
        const dayCard = document.createElement('div');
        dayCard.className = `p-4 rounded-xl border-2 transition-all ${
            isWorking 
                ? 'border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20'
                : 'border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50'
        }`;
        
        dayCard.innerHTML = `
            <div class="flex items-center justify-between mb-2">
                <h4 class="font-semibold text-gray-900 dark:text-white">${dayNames[index]}</h4>
                <span class="text-xs font-medium px-2 py-1 rounded-full ${
                    isWorking 
                        ? 'bg-green-100 dark:bg-green-800 text-green-700 dark:text-green-300'
                        : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400'
                }">
                    ${isWorking ? 'Working' : 'Off'}
                </span>
            </div>
            ${isWorking ? `
                <div class="space-y-1 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Work:</span>
                        <span class="font-medium text-gray-900 dark:text-white">${dayData.start} - ${dayData.end}</span>
                    </div>
                    ${dayData.break_start && dayData.break_end ? `
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Break:</span>
                            <span class="font-medium text-gray-900 dark:text-white">${dayData.break_start} - ${dayData.break_end}</span>
                        </div>
                    ` : ''}
                </div>
            ` : `
                <p class="text-sm text-gray-500 dark:text-gray-400">No working hours</p>
            `}
        `;
        
        grid.appendChild(dayCard);
    });
}

function populateEmployeesList(employees) {
    const list = document.getElementById('employees-list');
    const noEmployees = document.getElementById('no-employees');
    
    if (employees.length === 0) {
        list.classList.add('hidden');
        noEmployees.classList.remove('hidden');
        return;
    }
    
    list.classList.remove('hidden');
    noEmployees.classList.add('hidden');
    
    list.innerHTML = employees.map(employee => `
        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-200 dark:border-gray-700">
            <div class="flex items-center space-x-4">
                <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
                    <span class="text-white font-semibold text-sm">${employee.name.charAt(0).toUpperCase()}</span>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-900 dark:text-white">${employee.name}</h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400">${formatEmployeeType(employee.employee_type)}</p>
                </div>
            </div>
            <div class="text-right">
                <div class="text-sm font-medium text-gray-900 dark:text-white">${employee.total_days} days</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    ${employee.working_days} working, ${employee.holiday_days} holidays
                </div>
            </div>
        </div>
    `).join('');
}

function assignEmployees() {
    window.location.href = `/schedule-management/assign?schedule_id=${window.currentScheduleId}`;
}

function editSchedule() {
    window.location.href = `/schedule-management/monthly/${window.currentScheduleId}/edit`;
}

// Utility functions
function getMonthName(monthNumber) {
    const months = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];
    return months[monthNumber - 1] || 'Unknown';
}

function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function formatDateTime(dateTimeString) {
    return new Date(dateTimeString).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function formatTemplateName(template) {
    const templates = {
        'standard_5_days': 'Standard 5 Days',
        'uniform_5_days': 'Uniform 5 Days',
        'half_day_saturday': '6 Days with Half Saturday',
        'custom': 'Custom Template'
    };
    return templates[template] || 'Custom Template';
}

function formatEmployeeType(type) {
    return type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}
</script>
@endpush
@endsection