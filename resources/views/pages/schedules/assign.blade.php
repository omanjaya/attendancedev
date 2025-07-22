@extends('layouts.authenticated-unified')

@section('title', 'Assign Schedule to Employees')

@section('page-content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="p-6 lg:p-8">
        <!-- Modern Page Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Assign Schedule to Employees</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Bulk assign employees to monthly schedule templates with smart filtering
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <a href="{{ route('schedule-management.monthly.index') }}" 
                       class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 px-4 py-2 rounded-lg font-medium transition-all duration-200 hover:shadow-md">
                        <x-icons.arrow-left class="w-5 h-5 mr-2 inline" />
                        Back to Schedules
                    </a>
                </div>
            </div>
        </div>

        <!-- Modern Schedule Selection Card -->
        <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl mb-8 hover:shadow-2xl transition-all duration-300">
            <div class="p-6 border-b border-gray-200/50 dark:border-gray-700/50">
                <div class="flex items-center space-x-3 mb-2">
                    <div class="p-2 bg-gradient-to-br from-blue-500/20 to-indigo-600/20 rounded-xl">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                        Step 1: Select Schedule Template
                    </h2>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 ml-11">
                    Choose which monthly schedule to assign employees to
                </p>
            </div>
            
            <div class="p-8">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-2">
                        <div class="flex items-center justify-between mb-3">
                            <label for="schedule_id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a1 1 0 011-1h6a1 1 0 011 1v4h3a1 1 0 011 1v3a1 1 0 01-.293.707L19 14.414V19a2 2 0 01-2 2H7a2 2 0 01-2-2v-4.586l-.707-.707A1 1 0 014 13v-3a1 1 0 011-1h3z"/>
                                </svg>
                                Monthly Schedule Template *
                            </label>
                            <a href="{{ route('schedule-management.monthly.create') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Buat Jadwal Baru
                            </a>
                        </div>
                        <select id="schedule_id" 
                                name="schedule_id" 
                                required
                                class="block w-full px-4 py-4 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700/50 dark:text-white transition-all duration-200 hover:shadow-md backdrop-blur-sm text-base font-medium">
                            <option value="">Select Schedule Template...</option>
                            <!-- Options will be loaded via JavaScript -->
                        </select>
                    </div>

                    <div id="schedule-info" class="hidden">
                        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200 dark:border-blue-800/30 rounded-2xl p-6 backdrop-blur-sm">
                            <h4 class="font-semibold text-blue-800 dark:text-blue-300 mb-4 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Schedule Details
                            </h4>
                            <div id="schedule-details" class="text-sm text-blue-700 dark:text-blue-300 space-y-3">
                                <!-- Schedule details will be populated here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

            <!-- Modern Employee Selection Card -->
            <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl mb-8 hover:shadow-2xl transition-all duration-300">
                <div class="p-6 border-b border-gray-200/50 dark:border-gray-700/50">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between space-y-4 lg:space-y-0">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 bg-gradient-to-br from-emerald-500/20 to-green-600/20 rounded-xl">
                                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                                    Step 2: Select Employees
                                </h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Choose employees to assign to the selected schedule
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div class="bg-gradient-to-r from-indigo-50 to-blue-50 dark:from-indigo-900/20 dark:to-blue-900/20 px-4 py-2 rounded-full">
                                <span id="selection-count" class="text-sm font-semibold text-indigo-700 dark:text-indigo-300">0 selected</span>
                            </div>
                            <button onclick="clearSelection()" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 hover:shadow-md">
                                <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Clear All
                            </button>
                            <button onclick="selectAllVisible()" class="px-4 py-2 text-sm font-medium text-emerald-600 hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-200 bg-emerald-100 hover:bg-emerald-200 dark:bg-emerald-900/20 dark:hover:bg-emerald-900/40 rounded-lg transition-all duration-200 hover:shadow-md">
                                <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Select All Visible
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="p-8 space-y-8">
                    <!-- Enhanced Filters Section -->
                    <div class="bg-gradient-to-r from-gray-50 to-slate-50 dark:from-gray-900/50 dark:to-slate-900/50 rounded-2xl p-6 border border-gray-200/50 dark:border-gray-700/50">
                        <div class="flex items-center space-x-3 mb-6">
                            <div class="p-2 bg-gradient-to-br from-purple-500/20 to-violet-600/20 rounded-xl">
                                <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Smart Filters</h3>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            <div class="space-y-2">
                                <label for="employee_type_filter" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                    Employee Type
                                </label>
                                <select id="employee_type_filter" 
                                        class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700/50 dark:text-white transition-all duration-200 hover:shadow-md backdrop-blur-sm">
                                    <option value="">All Types</option>
                                    <option value="guru_tetap">Guru Tetap</option>
                                    <option value="guru_honorer">Guru Honorer</option>
                                    <option value="staff">Staff</option>
                                    <option value="security">Security</option>
                                    <option value="cleaning">Cleaning</option>
                                </select>
                            </div>

                            <div class="space-y-2">
                                <label for="name_search" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                    Search Name
                                </label>
                                <input type="text" 
                                       id="name_search" 
                                       placeholder="Search employee name..."
                                       class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700/50 dark:text-white transition-all duration-200 hover:shadow-md backdrop-blur-sm">
                            </div>

                            <div class="space-y-2">
                                <label for="range_select" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    Quick Range
                                </label>
                                <select id="range_select" 
                                        class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700/50 dark:text-white transition-all duration-200 hover:shadow-md backdrop-blur-sm">
                                    <option value="">Select Range...</option>
                                    <option value="A-D">Names A-D</option>
                                    <option value="E-H">Names E-H</option>
                                    <option value="I-L">Names I-L</option>
                                    <option value="M-P">Names M-P</option>
                                    <option value="Q-T">Names Q-T</option>
                                    <option value="U-Z">Names U-Z</option>
                                </select>
                            </div>

                            <div class="flex items-end">
                                <button onclick="refreshEmployeeList()" class="w-full px-4 py-3 text-sm font-semibold text-purple-600 hover:text-purple-800 dark:text-purple-400 dark:hover:text-purple-200 bg-purple-100 hover:bg-purple-200 dark:bg-purple-900/20 dark:hover:bg-purple-900/40 rounded-xl transition-all duration-200 hover:shadow-md">
                                    <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                    Refresh
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Employee List by Type -->
                    <div id="employee-lists" class="space-y-8">
                        <!-- Employee lists will be populated here -->
                    </div>

                    <!-- Enhanced Loading State -->
                    <div id="loading-employees" class="text-center py-16 hidden">
                        <div class="relative">
                            <div class="animate-spin rounded-full h-16 w-16 border-4 border-gradient-to-r from-emerald-200 to-green-200 border-t-gradient-to-r from-emerald-600 to-green-600 mx-auto mb-6"></div>
                            <div class="absolute inset-0 rounded-full border-4 border-transparent bg-gradient-to-r from-emerald-600 to-green-600 opacity-20 animate-pulse"></div>
                        </div>
                        <div class="space-y-2">
                            <p class="text-lg font-semibold text-gray-700 dark:text-gray-300">Loading available employees...</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Fetching employee data and checking availability</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modern Assignment Preview -->
            <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl mb-8 hover:shadow-2xl transition-all duration-300" id="assignment-preview" style="display: none;">
                <div class="p-6 border-b border-gray-200/50 dark:border-gray-700/50">
                    <div class="flex items-center space-x-3">
                        <div class="p-2 bg-gradient-to-br from-amber-500/20 to-orange-600/20 rounded-xl">
                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                                Step 3: Review Assignment
                            </h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Review the assignment details before submitting
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="p-8">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                        <div>
                            <h4 class="font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a1 1 0 011-1h6a1 1 0 011 1v4h3a1 1 0 011 1v3a1 1 0 01-.293.707L19 14.414V19a2 2 0 01-2 2H7a2 2 0 01-2-2v-4.586l-.707-.707A1 1 0 014 13v-3a1 1 0 011-1h3z"/>
                                </svg>
                                Selected Schedule
                            </h4>
                            <div id="selected-schedule-summary" class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200 dark:border-blue-800/30 rounded-2xl p-6">
                                <!-- Schedule summary will be populated here -->
                            </div>
                        </div>

                        <div>
                            <h4 class="font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                Selected Employees
                            </h4>
                            <div id="selected-employees-summary" class="bg-gradient-to-br from-emerald-50 to-green-50 dark:from-emerald-900/20 dark:to-green-900/20 border border-emerald-200 dark:border-emerald-800/30 rounded-2xl p-6 max-h-80 overflow-y-auto">
                                <!-- Employee summary will be populated here -->
                            </div>
                        </div>
                    </div>

                    <!-- Impact Analysis -->
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-6 flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            Assignment Impact Analysis
                        </h4>
                        <div id="impact-analysis" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Impact analysis will be populated here -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modern Form Actions -->
            <div class="flex flex-col lg:flex-row items-center justify-between space-y-4 lg:space-y-0 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border border-white/20 dark:border-gray-700/50 rounded-2xl p-6 shadow-xl">
                <button onclick="history.back()" class="px-6 py-3 text-sm font-semibold text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-xl transition-all duration-200 hover:shadow-md flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18"/>
                    </svg>
                    Cancel
                </button>

                <div class="flex items-center space-x-4">
                    <button id="preview-btn" onclick="showPreview()" disabled class="px-6 py-3 text-sm font-semibold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-200 bg-indigo-100 hover:bg-indigo-200 dark:bg-indigo-900/20 dark:hover:bg-indigo-900/40 rounded-xl transition-all duration-200 hover:shadow-md disabled:opacity-50 disabled:cursor-not-allowed flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        Preview Assignment
                    </button>
                    
                    <button id="assign-btn" onclick="submitAssignment()" disabled class="px-8 py-3 text-sm font-bold bg-gradient-to-r from-emerald-600 to-green-700 hover:from-emerald-700 hover:to-green-800 text-white rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span id="assign-btn-text">Assign to Schedule</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
window.selectedEmployees = new Set();
window.selectedSchedule = null;
window.availableEmployees = [];

document.addEventListener('DOMContentLoaded', function() {
    loadSchedules();
    setupEventListeners();
});

function setupEventListeners() {
    // Schedule selection
    document.getElementById('schedule_id').addEventListener('change', onScheduleChange);
    
    // Employee filters
    document.getElementById('employee_type_filter').addEventListener('change', filterEmployees);
    document.getElementById('name_search').addEventListener('input', debounce(filterEmployees, 300));
    document.getElementById('range_select').addEventListener('change', onRangeSelect);
}

async function loadSchedules() {
    try {
        const response = await fetch('/api/schedule-management/monthly?per_page=100');
        console.log('Schedule API Response:', response);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('Schedule Data:', data);
        
        if (data.success) {
            const select = document.getElementById('schedule_id');
            select.innerHTML = '<option value="">Select Schedule...</option>';
            
            if (data.data && data.data.length > 0) {
                data.data.forEach(schedule => {
                    const option = document.createElement('option');
                    option.value = schedule.id;
                    option.textContent = `${schedule.full_name} (${schedule.assigned_employees_count} assigned)`;
                    option.dataset.schedule = JSON.stringify(schedule);
                    select.appendChild(option);
                });
            } else {
                // Jika tidak ada jadwal, tampilkan pesan
                const option = document.createElement('option');
                option.value = '';
                option.textContent = 'Tidak ada jadwal yang tersedia. Silakan buat jadwal terlebih dahulu.';
                option.disabled = true;
                select.appendChild(option);
                showNotification('Tidak ada jadwal bulanan yang ditemukan. Silakan buat jadwal terlebih dahulu.', 'warning');
            }
        } else {
            console.error('API returned success:false', data);
            showNotification('Gagal memuat jadwal: ' + (data.message || 'Unknown error'), 'error');
        }
    } catch (error) {
        console.error('Error loading schedules:', error);
        showNotification('Error loading schedules', 'error');
    }
}

async function onScheduleChange() {
    const select = document.getElementById('schedule_id');
    const scheduleId = select.value;
    
    if (scheduleId) {
        window.selectedSchedule = JSON.parse(select.selectedOptions[0].dataset.schedule);
        showScheduleInfo();
        await loadAvailableEmployees(scheduleId);
    } else {
        window.selectedSchedule = null;
        hideScheduleInfo();
        clearEmployeeLists();
    }
    
    updateButtons();
}

function showScheduleInfo() {
    const infoDiv = document.getElementById('schedule-info');
    const detailsDiv = document.getElementById('schedule-details');
    
    detailsDiv.innerHTML = `
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-blue-600 dark:text-blue-400">PERIOD</span>
                <span class="text-sm font-semibold text-blue-800 dark:text-blue-200">${window.selectedSchedule.start_date} to ${window.selectedSchedule.end_date}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-blue-600 dark:text-blue-400">WORKING HOURS</span>
                <span class="text-sm font-semibold text-blue-800 dark:text-blue-200">${window.selectedSchedule.default_start_time} - ${window.selectedSchedule.default_end_time}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-blue-600 dark:text-blue-400">LOCATION</span>
                <span class="text-sm font-semibold text-blue-800 dark:text-blue-200">${window.selectedSchedule.location.name}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-blue-600 dark:text-blue-400">ASSIGNED</span>
                <span class="text-sm font-semibold text-blue-800 dark:text-blue-200">${window.selectedSchedule.assigned_employees_count} employees</span>
            </div>
        </div>
    `;
    
    infoDiv.classList.remove('hidden');
}

function hideScheduleInfo() {
    document.getElementById('schedule-info').classList.add('hidden');
}

async function loadAvailableEmployees(scheduleId) {
    const loadingDiv = document.getElementById('loading-employees');
    const listsDiv = document.getElementById('employee-lists');
    
    loadingDiv.classList.remove('hidden');
    listsDiv.innerHTML = '';
    
    try {
        const response = await fetch(`/api/schedule-management/monthly/${scheduleId}/available-employees`);
        const data = await response.json();
        
        if (data.success) {
            window.availableEmployees = data.data.available_employees;
            renderEmployeeLists();
        }
    } catch (error) {
        console.error('Error loading employees:', error);
        showNotification('Error loading available employees', 'error');
    } finally {
        loadingDiv.classList.add('hidden');
    }
}

function renderEmployeeLists() {
    const listsDiv = document.getElementById('employee-lists');
    
    if (window.availableEmployees.length === 0) {
        listsDiv.innerHTML = `
            <div class="text-center py-16">
                <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl max-w-md mx-auto p-12">
                    <div class="p-6 bg-gradient-to-br from-gray-500/20 to-slate-600/20 rounded-2xl mx-auto w-24 h-24 flex items-center justify-center backdrop-blur-sm mb-6">
                        <svg class="w-12 h-12 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">No Available Employees</h3>
                    <p class="text-gray-500 dark:text-gray-400 text-sm mb-2">All employees may already be assigned to this schedule</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">Try selecting a different schedule or check existing assignments</p>
                </div>
            </div>
        `;
        return;
    }
    
    window.availableEmployees.forEach(employeeType => {
        const typeDiv = document.createElement('div');
        typeDiv.className = 'employee-type-section';
        typeDiv.innerHTML = `
            <div class="employee-type-header">
                <div class="employee-type-title">
                    <div class="employee-type-icon">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white">
                            ${employeeType.type_label}
                        </h4>
                        <span class="employee-count-badge">${employeeType.employees.length} karyawan</span>
                    </div>
                </div>
                <button class="toggle-selection-btn" onclick="toggleTypeSelection('${employeeType.type}')">
                    <span id="toggle-${employeeType.type}">Pilih Semua</span>
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
            <div class="employee-selection-grid" id="employees-${employeeType.type}">
                ${employeeType.employees.map(employee => `
                    <label class="employee-card-modern" 
                           data-employee-type="${employeeType.type}"
                           data-employee-name="${employee.name.toLowerCase()}">
                        <input type="checkbox" 
                               class="employee-checkbox form-checkbox-modern" 
                               value="${employee.id}"
                               onchange="onEmployeeSelectionChange(this)">
                        <div class="employee-card-content">
                            <div class="employee-name">
                                ${employee.name}
                            </div>
                            <div class="employee-capabilities">
                                ${employee.can_teach ? '<span class="capability-badge">👨‍🏫 Can Teach</span>' : ''}
                                ${employee.can_substitute ? '<span class="capability-badge">🔄 Can Substitute</span>' : ''}
                            </div>
                        </div>
                        <div class="employee-card-check">
                            <svg class="w-5 h-5 text-transparent" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    </label>
                `).join('')}
            </div>
        `;
        
        listsDiv.appendChild(typeDiv);
    });
}

function onEmployeeSelectionChange(checkbox) {
    if (checkbox.checked) {
        window.selectedEmployees.add(checkbox.value);
    } else {
        window.selectedEmployees.delete(checkbox.value);
    }
    
    updateSelectionCount();
    updateButtons();
}

function updateSelectionCount() {
    const countElement = document.getElementById('selection-count');
    if (window.selectedEmployees.size === 0) {
        countElement.innerHTML = '<span class="text-gray-500 dark:text-gray-400">Belum ada yang dipilih</span>';
    } else {
        countElement.innerHTML = `<span class="font-semibold text-blue-600 dark:text-blue-400">${window.selectedEmployees.size}</span> <span class="text-gray-600 dark:text-gray-400">karyawan dipilih</span>`;
    }
}

function clearSelection() {
    window.selectedEmployees.clear();
    document.querySelectorAll('.employee-checkbox').forEach(cb => cb.checked = false);
    updateSelectionCount();
    updateButtons();
}

function selectAllVisible() {
    document.querySelectorAll('.employee-card:not([style*="display: none"]) .employee-checkbox').forEach(cb => {
        cb.checked = true;
        window.selectedEmployees.add(cb.value);
    });
    updateSelectionCount();
    updateButtons();
}

function toggleTypeSelection(type) {
    const toggleBtn = document.getElementById(`toggle-${type}`);
    const typeEmployees = document.querySelectorAll(`[data-employee-type="${type}"] .employee-checkbox`);
    
    const allSelected = Array.from(typeEmployees).every(cb => cb.checked);
    
    typeEmployees.forEach(cb => {
        cb.checked = !allSelected;
        if (cb.checked) {
            window.selectedEmployees.add(cb.value);
        } else {
            window.selectedEmployees.delete(cb.value);
        }
    });
    
    toggleBtn.innerHTML = allSelected ? 
        'Pilih Semua <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>' : 
        'Batal Pilih <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>';
    updateSelectionCount();
    updateButtons();
}

function filterEmployees() {
    const typeFilter = document.getElementById('employee_type_filter').value;
    const nameSearch = document.getElementById('name_search').value.toLowerCase();
    
    document.querySelectorAll('.employee-card').forEach(card => {
        const employeeType = card.dataset.employeeType;
        const employeeName = card.dataset.employeeName;
        
        const typeMatch = !typeFilter || employeeType === typeFilter;
        const nameMatch = !nameSearch || employeeName.includes(nameSearch);
        
        if (typeMatch && nameMatch) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
    
    // Update type section visibility
    document.querySelectorAll('.employee-type-section').forEach(section => {
        const visibleCards = section.querySelectorAll('.employee-card:not([style*="display: none"])');
        section.style.display = visibleCards.length > 0 ? '' : 'none';
    });
}

function onRangeSelect() {
    const range = document.getElementById('range_select').value;
    if (!range) return;
    
    const [start, end] = range.split('-');
    
    document.querySelectorAll('.employee-checkbox').forEach(cb => {
        const card = cb.closest('.employee-card');
        const name = card.dataset.employeeName;
        const firstChar = name.charAt(0).toUpperCase();
        
        if (firstChar >= start && firstChar <= end) {
            cb.checked = true;
            window.selectedEmployees.add(cb.value);
        }
    });
    
    updateSelectionCount();
    updateButtons();
    
    // Reset range select
    document.getElementById('range_select').value = '';
}

function updateButtons() {
    const hasSchedule = window.selectedSchedule !== null;
    const hasEmployees = window.selectedEmployees.size > 0;
    
    document.getElementById('preview-btn').disabled = !hasSchedule || !hasEmployees;
    document.getElementById('assign-btn').disabled = !hasSchedule || !hasEmployees;
    
    if (hasSchedule && hasEmployees) {
        document.getElementById('assign-btn-text').textContent = `Assign ${window.selectedEmployees.size} Employees`;
    } else {
        document.getElementById('assign-btn-text').textContent = 'Assign to Schedule';
    }
}

function showPreview() {
    if (!window.selectedSchedule || window.selectedEmployees.size === 0) return;
    
    const previewCard = document.getElementById('assignment-preview');
    
    // Schedule summary
    document.getElementById('selected-schedule-summary').innerHTML = `
        <div class="space-y-2 text-sm">
            <div><strong>Schedule:</strong> ${window.selectedSchedule.full_name}</div>
            <div><strong>Period:</strong> ${window.selectedSchedule.start_date} to ${window.selectedSchedule.end_date}</div>
            <div><strong>Working Hours:</strong> ${window.selectedSchedule.default_start_time} - ${window.selectedSchedule.default_end_time}</div>
            <div><strong>Location:</strong> ${window.selectedSchedule.location.name}</div>
        </div>
    `;
    
    // Employee summary
    const selectedEmployeesList = Array.from(window.selectedEmployees).map(id => {
        const checkbox = document.querySelector(`input[value="${id}"]`);
        const card = checkbox.closest('.employee-card');
        return {
            id,
            name: card.dataset.employeeName,
            type: card.dataset.employeeType
        };
    });
    
    const employeesByType = selectedEmployeesList.reduce((acc, emp) => {
        if (!acc[emp.type]) acc[emp.type] = [];
        acc[emp.type].push(emp);
        return acc;
    }, {});
    
    document.getElementById('selected-employees-summary').innerHTML = `
        <div class="space-y-3 text-sm">
            ${Object.entries(employeesByType).map(([type, employees]) => `
                <div>
                    <div class="font-medium text-gray-900 dark:text-white mb-1">
                        ${type.replace('_', ' ').toUpperCase()} (${employees.length})
                    </div>
                    <div class="text-xs text-gray-600 dark:text-gray-400">
                        ${employees.map(emp => emp.name).join(', ')}
                    </div>
                </div>
            `).join('')}
        </div>
    `;
    
    // Impact analysis
    const workingDays = calculateWorkingDays();
    const totalAssignments = window.selectedEmployees.size * workingDays;
    
    document.getElementById('impact-analysis').innerHTML = `
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">${window.selectedEmployees.size}</div>
            <div class="text-sm text-blue-700 dark:text-blue-300">Employees</div>
        </div>
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-green-600 dark:text-green-400">${workingDays}</div>
            <div class="text-sm text-green-700 dark:text-green-300">Working Days</div>
        </div>
        <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">${totalAssignments}</div>
            <div class="text-sm text-purple-700 dark:text-purple-300">Total Assignments</div>
        </div>
    `;
    
    previewCard.style.display = 'block';
    previewCard.scrollIntoView({ behavior: 'smooth' });
}

async function submitAssignment() {
    if (!window.selectedSchedule || window.selectedEmployees.size === 0) return;
    
    const assignBtn = document.getElementById('assign-btn');
    const originalText = assignBtn.innerHTML;
    
    // Show loading state
    assignBtn.innerHTML = '<div class="animate-spin rounded-full h-5 w-5 border-b-2 border-white mr-2"></div>Assigning...';
    assignBtn.disabled = true;
    
    try {
        const response = await fetch(`/api/schedule-management/monthly/${window.selectedSchedule.id}/assign-employees`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                employee_ids: Array.from(window.selectedEmployees)
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(`Successfully assigned ${result.data.successful_assignments} employees to schedule`, 'success');
            
            if (result.data.failed_assignments > 0) {
                showNotification(`${result.data.failed_assignments} assignments failed. See details below.`, 'warning');
                console.log('Failed assignments:', result.data.errors);
            }
            
            // Store success message in session storage for display after redirect
            sessionStorage.setItem('assignmentSuccess', JSON.stringify({
                message: `Berhasil assign ${result.data.successful_assignments} karyawan ke jadwal ${window.selectedSchedule.name}`,
                scheduleName: window.selectedSchedule.name,
                assignedCount: result.data.successful_assignments
            }));
            
            setTimeout(() => {
                window.location.href = `/schedule-management/monthly`;
            }, 2000);
        } else {
            throw new Error(result.message || 'Failed to assign employees');
        }
        
    } catch (error) {
        console.error('Error assigning employees:', error);
        showNotification(error.message || 'Failed to assign employees', 'error');
        
        // Restore button state
        assignBtn.innerHTML = originalText;
        assignBtn.disabled = false;
    }
}

function calculateWorkingDays() {
    const start = new Date(window.selectedSchedule.start_date);
    const end = new Date(window.selectedSchedule.end_date);
    let workingDays = 0;
    
    for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
        // Assuming weekdays are working days (this could be improved based on metadata)
        if (d.getDay() !== 0 && d.getDay() !== 6) {
            workingDays++;
        }
    }
    
    return workingDays;
}

function refreshEmployeeList() {
    if (window.selectedSchedule) {
        loadAvailableEmployees(window.selectedSchedule.id);
    }
}

function clearEmployeeLists() {
    document.getElementById('employee-lists').innerHTML = '';
    window.selectedEmployees.clear();
    updateSelectionCount();
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 px-6 py-4 rounded-lg shadow-lg transition-all transform ${
        type === 'success' ? 'bg-green-500 text-white' :
        type === 'error' ? 'bg-red-500 text-white' :
        type === 'warning' ? 'bg-yellow-500 text-white' :
        'bg-blue-500 text-white'
    }`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 5000);
}
</script>
@endpush