@extends('layouts.authenticated-unified')

@section('title', 'Create Monthly Schedule')

@section('page-content')
<!-- Page Header Following System Command Center Pattern -->
<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Create Monthly Schedule</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Design a comprehensive schedule template for your team</p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="{{ route('schedule-management.monthly.index') }}" 
               class="bg-white dark:bg-gray-700 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                <x-icons.arrow-left class="inline-block w-5 h-5 mr-2" />
                Back
            </a>
            <button type="button" onclick="showPreview()" 
                    class="bg-white dark:bg-gray-700 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                <x-icons.eye class="inline-block w-5 h-5 mr-2" />
                Preview
            </button>
        </div>
    </div>
</div>

<!-- Main Content with Consistent Spacing -->
<form id="monthlyScheduleForm" class="space-y-8">
    @csrf
    
    <!-- Progress Indicator Card -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Schedule Setup Progress</h3>
            <span class="text-sm text-gray-500 dark:text-gray-400">Step <span id="currentStep">1</span> of 4</span>
        </div>
        <div class="flex items-center justify-between">
            <div class="flex items-center flex-1">
                <div class="step-indicator active" data-step="1">
                    <div class="step-number">1</div>
                    <span class="step-label">Basic Info</span>
                </div>
                <div class="step-connector"></div>
                <div class="step-indicator" data-step="2">
                    <div class="step-number">2</div>
                    <span class="step-label">Working Hours</span>
                </div>
                <div class="step-connector"></div>
                <div class="step-indicator" data-step="3">
                    <div class="step-number">3</div>
                    <span class="step-label">Settings</span>
                </div>
                <div class="step-connector"></div>
                <div class="step-indicator" data-step="4">
                    <div class="step-number">4</div>
                    <span class="step-label">Review</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 1: Basic Information -->
    <div class="form-step active" data-step="1">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center space-x-3">
                    <div class="p-2 bg-blue-600 rounded-lg">
                        <x-icons.document-text class="w-6 h-6 text-white" />
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Basic Information</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Define the fundamental details of your schedule</p>
                    </div>
                </div>
            </div>
            
            <div class="p-6 space-y-6">
                <!-- Schedule Name and Location -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Schedule Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               required
                               placeholder="e.g., January 2025 - Main Campus"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Choose a descriptive name for this schedule</p>
                    </div>

                    <div>
                        <label for="location_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Location <span class="text-red-500">*</span>
                        </label>
                        <select id="location_id" 
                                name="location_id" 
                                required
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            <option value="">Choose a location...</option>
                        </select>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Select the location for this schedule</p>
                    </div>
                </div>

                <!-- Month, Year and Schedule Period -->
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    <div>
                        <label for="month" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Month <span class="text-red-500">*</span>
                        </label>
                        <select id="month" 
                                name="month" 
                                required
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            <option value="">Select month...</option>
                        </select>
                    </div>

                    <div>
                        <label for="year" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Year <span class="text-red-500">*</span>
                        </label>
                        <select id="year" 
                                name="year" 
                                required
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            <option value="">Select year...</option>
                        </select>
                    </div>

                    <div class="lg:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Schedule Period
                        </label>
                        <div id="schedule-preview" class="bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md p-4">
                            <div class="flex items-center justify-center text-gray-500 dark:text-gray-400">
                                <x-icons.calendar class="w-5 h-5 mr-2" />
                                <span class="text-sm">Select month and year</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Date Range -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Start Date <span class="text-red-500">*</span>
                        </label>
                        <input type="date" 
                               id="start_date" 
                               name="start_date" 
                               required
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                    </div>

                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            End Date <span class="text-red-500">*</span>
                        </label>
                        <input type="date" 
                               id="end_date" 
                               name="end_date" 
                               required
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                    </div>
                </div>

                <!-- Auto-Fill Controls -->
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 bg-blue-600 rounded-lg">
                                <x-icons.calendar-days class="w-5 h-5 text-white" />
                            </div>
                            <div>
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Auto Date Range</h4>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Dates auto-fill based on selected month</p>
                            </div>
                        </div>
                        <button type="button" 
                                onclick="resetAutoFill()"
                                class="px-3 py-1.5 text-xs font-medium text-blue-600 bg-white dark:bg-gray-700 border border-blue-300 dark:border-blue-600 rounded hover:bg-blue-50 dark:hover:bg-blue-900/30 transition-colors">
                            <x-icons.arrow-path class="w-4 h-4 inline mr-1" />
                            Reset to Full Month
                        </button>
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Description
                    </label>
                    <textarea id="description" 
                              name="description" 
                              rows="4"
                              placeholder="Add a description for this schedule template..."
                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"></textarea>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Optional description to help identify this schedule template</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 2: Working Hours -->
    <div class="form-step" data-step="2">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center space-x-3">
                    <div class="p-2 bg-indigo-600 rounded-lg">
                        <x-icons.clock class="w-6 h-6 text-white" />
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Working Hours</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Set the default working hours for this schedule</p>
                    </div>
                </div>
            </div>
            
            <div class="p-6 space-y-6">
                <!-- Working Hours Template Selection -->
                <div>
                    <label for="working_hours_template" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Working Hours Template <span class="text-red-500">*</span>
                    </label>
                    <select id="working_hours_template" 
                            name="working_hours_template" 
                            required
                            onchange="applyWorkingHoursTemplate()"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                        <option value="">Choose a working hours template...</option>
                        <option value="standard_5_days" selected>Standard 5 Days (Mon-Thu: 07:30-15:30, Fri: 07:30-13:00)</option>
                        <option value="uniform_5_days">Uniform 5 Days (Mon-Fri: 08:00-16:00)</option>
                        <option value="half_day_saturday">6 Days with Half Saturday</option>
                        <option value="custom">Custom Working Hours</option>
                    </select>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Select a pre-defined template or create custom working hours</p>
                </div>

                <!-- Day-specific Working Hours Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Monday to Friday (Main Days) -->
                    <div class="space-y-4">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2">
                            Weekdays Schedule
                        </h4>
                        
                        <!-- Monday -->
                        <div class="day-schedule" data-day="monday">
                            <div class="flex items-center justify-between mb-2">
                                <label class="flex items-center space-x-2">
                                    <input type="checkbox" class="day-enabled" data-day="monday" checked onchange="toggleDay('monday')">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Monday</span>
                                </label>
                            </div>
                            <div class="grid grid-cols-2 gap-3 day-times">
                                <div>
                                    <input type="time" name="monday_start" id="monday_start" value="07:30" 
                                           class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                </div>
                                <div>
                                    <input type="time" name="monday_end" id="monday_end" value="15:30"
                                           class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                </div>
                            </div>
                            <div class="mt-2">
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-xs text-gray-500">Break Start</label>
                                        <input type="time" name="monday_break_start" id="monday_break_start" value="12:00"
                                               class="w-full px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="text-xs text-gray-500">Break End</label>
                                        <input type="time" name="monday_break_end" id="monday_break_end" value="13:00"
                                               class="w-full px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tuesday -->
                        <div class="day-schedule" data-day="tuesday">
                            <div class="flex items-center justify-between mb-2">
                                <label class="flex items-center space-x-2">
                                    <input type="checkbox" class="day-enabled" data-day="tuesday" checked onchange="toggleDay('tuesday')">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Tuesday</span>
                                </label>
                            </div>
                            <div class="grid grid-cols-2 gap-3 day-times">
                                <div>
                                    <input type="time" name="tuesday_start" id="tuesday_start" value="07:30" 
                                           class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                </div>
                                <div>
                                    <input type="time" name="tuesday_end" id="tuesday_end" value="15:30"
                                           class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                </div>
                            </div>
                            <div class="mt-2">
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <input type="time" name="tuesday_break_start" id="tuesday_break_start" value="12:00"
                                               class="w-full px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                    </div>
                                    <div>
                                        <input type="time" name="tuesday_break_end" id="tuesday_break_end" value="13:00"
                                               class="w-full px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Wednesday -->
                        <div class="day-schedule" data-day="wednesday">
                            <div class="flex items-center justify-between mb-2">
                                <label class="flex items-center space-x-2">
                                    <input type="checkbox" class="day-enabled" data-day="wednesday" checked onchange="toggleDay('wednesday')">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Wednesday</span>
                                </label>
                            </div>
                            <div class="grid grid-cols-2 gap-3 day-times">
                                <div>
                                    <input type="time" name="wednesday_start" id="wednesday_start" value="07:30" 
                                           class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                </div>
                                <div>
                                    <input type="time" name="wednesday_end" id="wednesday_end" value="15:30"
                                           class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                </div>
                            </div>
                            <div class="mt-2">
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <input type="time" name="wednesday_break_start" id="wednesday_break_start" value="12:00"
                                               class="w-full px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                    </div>
                                    <div>
                                        <input type="time" name="wednesday_break_end" id="wednesday_break_end" value="13:00"
                                               class="w-full px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Thursday -->
                        <div class="day-schedule" data-day="thursday">
                            <div class="flex items-center justify-between mb-2">
                                <label class="flex items-center space-x-2">
                                    <input type="checkbox" class="day-enabled" data-day="thursday" checked onchange="toggleDay('thursday')">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Thursday</span>
                                </label>
                            </div>
                            <div class="grid grid-cols-2 gap-3 day-times">
                                <div>
                                    <input type="time" name="thursday_start" id="thursday_start" value="07:30" 
                                           class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                </div>
                                <div>
                                    <input type="time" name="thursday_end" id="thursday_end" value="15:30"
                                           class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                </div>
                            </div>
                            <div class="mt-2">
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <input type="time" name="thursday_break_start" id="thursday_break_start" value="12:00"
                                               class="w-full px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                    </div>
                                    <div>
                                        <input type="time" name="thursday_break_end" id="thursday_break_end" value="13:00"
                                               class="w-full px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Friday -->
                        <div class="day-schedule" data-day="friday">
                            <div class="flex items-center justify-between mb-2">
                                <label class="flex items-center space-x-2">
                                    <input type="checkbox" class="day-enabled" data-day="friday" checked onchange="toggleDay('friday')">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Friday</span>
                                    <span class="text-xs text-amber-600 bg-amber-100 px-2 py-1 rounded">Half Day</span>
                                </label>
                            </div>
                            <div class="grid grid-cols-2 gap-3 day-times">
                                <div>
                                    <input type="time" name="friday_start" id="friday_start" value="07:30" 
                                           class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                </div>
                                <div>
                                    <input type="time" name="friday_end" id="friday_end" value="13:00"
                                           class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                </div>
                            </div>
                            <div class="mt-2">
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <input type="time" name="friday_break_start" id="friday_break_start" value="11:30"
                                               class="w-full px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                    </div>
                                    <div>
                                        <input type="time" name="friday_break_end" id="friday_break_end" value="12:00"
                                               class="w-full px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Weekend (Saturday/Sunday) -->
                    <div class="space-y-4">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2">
                            Weekend Schedule
                        </h4>
                        
                        <!-- Saturday -->
                        <div class="day-schedule" data-day="saturday">
                            <div class="flex items-center justify-between mb-2">
                                <label class="flex items-center space-x-2">
                                    <input type="checkbox" class="day-enabled" data-day="saturday" onchange="toggleDay('saturday')">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Saturday</span>
                                    <span class="text-xs text-gray-500">(Optional)</span>
                                </label>
                            </div>
                            <div class="grid grid-cols-2 gap-3 day-times opacity-50">
                                <div>
                                    <input type="time" name="saturday_start" id="saturday_start" value="07:30" disabled
                                           class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                </div>
                                <div>
                                    <input type="time" name="saturday_end" id="saturday_end" value="12:00" disabled
                                           class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                </div>
                            </div>
                        </div>

                        <!-- Sunday -->
                        <div class="day-schedule" data-day="sunday">
                            <div class="flex items-center justify-between mb-2">
                                <label class="flex items-center space-x-2">
                                    <input type="checkbox" class="day-enabled" data-day="sunday" onchange="toggleDay('sunday')">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Sunday</span>
                                    <span class="text-xs text-gray-500">(Optional)</span>
                                </label>
                            </div>
                            <div class="grid grid-cols-2 gap-3 day-times opacity-50">
                                <div>
                                    <input type="time" name="sunday_start" id="sunday_start" value="08:00" disabled
                                           class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                </div>
                                <div>
                                    <input type="time" name="sunday_end" id="sunday_end" value="12:00" disabled
                                           class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                </div>
                            </div>
                        </div>

                        <!-- Working Hours Summary -->
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="p-2 bg-blue-600 rounded-lg">
                                        <x-icons.clock class="w-5 h-5 text-white" />
                                    </div>
                                    <div>
                                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Weekly Total</h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Working hours</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div id="weekly-hours-display" class="text-2xl font-bold text-blue-600 dark:text-blue-400">37.5 hours</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">Per week</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 3: Advanced Settings -->
    <div class="form-step" data-step="3">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center space-x-3">
                    <div class="p-2 bg-purple-600 rounded-lg">
                        <x-icons.cog class="w-6 h-6 text-white" />
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Advanced Settings</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Configure schedule rules and preferences</p>
                    </div>
                </div>
            </div>
            
            <div class="p-6 space-y-8">
                <!-- Working Days -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">
                        Working Days
                    </label>
                    <div class="grid grid-cols-7 gap-3">
                        @foreach([
                            ['value' => 'monday', 'label' => 'Mon', 'full' => 'Monday'],
                            ['value' => 'tuesday', 'label' => 'Tue', 'full' => 'Tuesday'],
                            ['value' => 'wednesday', 'label' => 'Wed', 'full' => 'Wednesday'],
                            ['value' => 'thursday', 'label' => 'Thu', 'full' => 'Thursday'],
                            ['value' => 'friday', 'label' => 'Fri', 'full' => 'Friday'],
                            ['value' => 'saturday', 'label' => 'Sat', 'full' => 'Saturday'],
                            ['value' => 'sunday', 'label' => 'Sun', 'full' => 'Sunday']
                        ] as $day)
                        <label class="day-selector {{ in_array($day['value'], ['monday', 'tuesday', 'wednesday', 'thursday', 'friday']) ? 'selected' : '' }}">
                            <input type="checkbox" 
                                   name="work_days[]" 
                                   value="{{ $day['value'] }}" 
                                   {{ in_array($day['value'], ['monday', 'tuesday', 'wednesday', 'thursday', 'friday']) ? 'checked' : '' }}
                                   class="hidden">
                            <div class="day-content">
                                <span class="day-short">{{ $day['label'] }}</span>
                                <span class="day-full">{{ $day['full'] }}</span>
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>

                <!-- Break Time Configuration -->
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-6">
                    <h4 class="flex items-center text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        <x-icons.pause class="w-5 h-5 mr-2" />
                        Break Time Configuration
                    </h4>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div>
                            <label for="break_time_start" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Break Start
                            </label>
                            <input type="time" 
                                   id="break_time_start" 
                                   name="break_time_start" 
                                   value="12:00"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div>
                            <label for="break_time_end" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Break End
                            </label>
                            <input type="time" 
                                   id="break_time_end" 
                                   name="break_time_end" 
                                   value="13:00"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>
                </div>

                <!-- Time Thresholds -->
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-6">
                    <h4 class="flex items-center text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        <x-icons.clock class="w-5 h-5 mr-2" />
                        Time Thresholds
                    </h4>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div>
                            <label for="late_threshold_minutes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Late Threshold (minutes)
                            </label>
                            <input type="number" 
                                   id="late_threshold_minutes" 
                                   name="late_threshold_minutes" 
                                   value="15"
                                   min="1" 
                                   max="120"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Grace period before marking as late</p>
                        </div>

                        <div>
                            <label for="early_departure_threshold_minutes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Early Departure Threshold (minutes)
                            </label>
                            <input type="number" 
                                   id="early_departure_threshold_minutes" 
                                   name="early_departure_threshold_minutes" 
                                   value="30"
                                   min="1" 
                                   max="120"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Time before end considered early departure</p>
                        </div>
                    </div>
                </div>

                <!-- Additional Options -->
                <div>
                    <label class="flex items-center space-x-3 cursor-pointer">
                        <input type="checkbox" name="overtime_allowed" checked 
                               class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Allow Overtime Hours
                        </span>
                    </label>
                    <p class="ml-7 text-sm text-gray-500 dark:text-gray-400">Enable employees to work beyond scheduled hours</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 4: Review -->
    <div class="form-step" data-step="4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center space-x-3">
                    <div class="p-2 bg-green-600 rounded-lg">
                        <x-icons.check-circle class="w-6 h-6 text-white" />
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Review & Confirm</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Review your schedule configuration before creating</p>
                    </div>
                </div>
            </div>
            
            <div class="p-6">
                <div id="reviewContent" class="space-y-6">
                    <!-- Review content will be populated by JavaScript -->
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Buttons -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
            <button type="button" id="prevBtn" 
                    class="bg-white dark:bg-gray-700 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors hidden">
                <x-icons.arrow-left class="inline-block w-5 h-5 mr-2" />
                Previous
            </button>
            
            <div class="flex items-center space-x-4 ml-auto">
                <button type="button" id="nextBtn" 
                        class="bg-blue-600 hover:bg-blue-700 px-4 py-2 text-white rounded-md shadow-sm text-sm font-medium transition-colors">
                    Next
                    <x-icons.arrow-right class="inline-block w-5 h-5 ml-2" />
                </button>
                
                <button type="submit" id="submitBtn" 
                        class="bg-green-600 hover:bg-green-700 px-4 py-2 text-white rounded-md shadow-sm text-sm font-medium transition-colors hidden">
                    <x-icons.check class="inline-block w-5 h-5 mr-2" />
                    Create Schedule
                </button>
            </div>
        </div>
    </div>
</form>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="fixed inset-0 z-50 bg-gray-900/50 backdrop-blur-sm flex items-center justify-center hidden">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Creating Schedule</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">Please wait while we set up your schedule template...</p>
    </div>
</div>

<!-- Preview Modal -->
<div id="previewModal" class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/50 backdrop-blur-sm hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Schedule Preview</h3>
                    <button onclick="closePreviewModal()" 
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <x-icons.x class="w-6 h-6" />
                    </button>
                </div>
            </div>
            
            <div class="p-6 overflow-y-auto max-h-[60vh]">
                <div id="previewContent" class="space-y-6">
                    <!-- Preview content will be loaded here -->
                </div>
            </div>
            
            <div class="p-6 border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-end space-x-4">
                    <button onclick="closePreviewModal()" 
                            class="bg-white dark:bg-gray-700 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                        Close Preview
                    </button>
                    <button onclick="submitFormFromPreview()" 
                            class="bg-blue-600 hover:bg-blue-700 px-4 py-2 text-white rounded-md shadow-sm text-sm font-medium transition-colors">
                        Looks Perfect, Create Schedule
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
/* Step Indicator */
.step-indicator {
    @apply flex flex-col items-center space-y-2 transition-all duration-300;
}

.step-indicator.active .step-number {
    @apply bg-blue-600 text-white;
}

.step-indicator .step-number {
    @apply w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400 flex items-center justify-center font-semibold text-sm transition-all duration-300;
}

.step-indicator .step-label {
    @apply text-sm font-medium text-gray-600 dark:text-gray-400 transition-all duration-300;
}

.step-indicator.active .step-label {
    @apply text-blue-600 dark:text-blue-400;
}

.step-connector {
    @apply h-px bg-gray-200 dark:bg-gray-700 flex-1;
}

/* Form Steps */
.form-step {
    @apply hidden;
}

.form-step.active {
    @apply block;
}

/* Work Days Selector */
.day-selector {
    @apply relative cursor-pointer;
}

.day-content {
    @apply p-3 bg-white dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600 rounded-lg text-center transition-all duration-200 hover:border-blue-300 dark:hover:border-blue-500;
}

.day-selector.selected .day-content {
    @apply bg-blue-600 border-blue-600 text-white;
}

.day-short {
    @apply block font-bold text-sm;
}

.day-full {
    @apply block text-xs opacity-75;
}

/* Responsive Design */
@media (max-width: 768px) {
    .step-indicator .step-label {
        @apply hidden;
    }
    
    .day-full {
        @apply hidden;
    }
}
</style>
@endpush

@push('scripts')
<script>
// Prevent duplicate script execution
if (window.scheduleScriptLoaded) {
    console.log('Schedule script already loaded, skipping...');
} else {
    window.scheduleScriptLoaded = true;
    
    // Global variables for schedule creation
    window.scheduleCurrentStep = window.scheduleCurrentStep || 1;
    window.scheduleTotalSteps = window.scheduleTotalSteps || 4;
    console.log('Schedule script loaded - Step:', window.scheduleCurrentStep);

document.addEventListener('DOMContentLoaded', function() {
    initializeForm();
    loadFormData();
    setupEventListeners();
    setupStepNavigation();
});

function initializeForm() {
    updateSchedulePreview();
    updateWorkingHours();
    // Auto-fill dates on initial load if month/year are set
    setTimeout(() => {
        autoSetDateRange();
    }, 100);
}

async function loadFormData() {
    try {
        showLoading('Loading form data...');
        const response = await fetch('/api/schedule-management/monthly/create', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        console.log('API Response:', data); // Debug log
        
        if (data.success) {
            populateDropdowns(data.data);
        } else {
            throw new Error(data.message || 'Failed to load form data');
        }
    } catch (error) {
        console.error('Error loading form data:', error);
        showNotification('Error loading form data: ' + error.message, 'error');
        provideFallbackData();
    } finally {
        hideLoading();
    }
}

function populateDropdowns(data) {
    // Populate locations
    const locationSelect = document.getElementById('location_id');
    locationSelect.innerHTML = '<option value="">Choose a location...</option>';
    if (data.locations && data.locations.length > 0) {
        data.locations.forEach(location => {
            locationSelect.innerHTML += `<option value="${location.id}">${location.name}</option>`;
        });
    }
    
    // Store working hours templates
    if (data.working_hours_templates) {
        window.scheduleWorkingHoursTemplates = data.working_hours_templates;
        console.log('Working Hours Templates loaded:', Object.keys(window.scheduleWorkingHoursTemplates));
        
        // Populate template selector
        const templateSelect = document.getElementById('working_hours_template');
        templateSelect.innerHTML = '<option value="">Choose a working hours template...</option>';
        Object.keys(window.scheduleWorkingHoursTemplates).forEach(key => {
            const template = window.scheduleWorkingHoursTemplates[key];
            const selected = key === 'standard_5_days' ? 'selected' : '';
            templateSelect.innerHTML += `<option value="${key}" ${selected}>${template.name}</option>`;
        });
        
        // Apply default template
        applyWorkingHoursTemplate();
    }
    
    // Populate months
    const monthSelect = document.getElementById('month');
    monthSelect.innerHTML = '<option value="">Select month...</option>';
    if (data.months && data.months.length > 0) {
        data.months.forEach(month => {
            monthSelect.innerHTML += `<option value="${month.value}">${month.label}</option>`;
        });
    }
    
    // Populate years
    const yearSelect = document.getElementById('year');
    yearSelect.innerHTML = '<option value="">Select year...</option>';
    if (data.years && data.years.length > 0) {
        data.years.forEach(year => {
            yearSelect.innerHTML += `<option value="${year.value}">${year.label}</option>`;
        });
    }
    
    // Set current month and year
    const now = new Date();
    monthSelect.value = now.getMonth() + 1;
    yearSelect.value = now.getFullYear();
    
    updateSchedulePreview();
}

function provideFallbackData() {
    // Fallback locations
    const locationSelect = document.getElementById('location_id');
    locationSelect.innerHTML = `
        <option value="">Choose a location...</option>
        <option value="fallback-1">Main Campus</option>
        <option value="fallback-2">Branch Office</option>
    `;
    
    // Fallback months
    const monthSelect = document.getElementById('month');
    monthSelect.innerHTML = '<option value="">Select month...</option>';
    const months = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];
    months.forEach((month, index) => {
        monthSelect.innerHTML += `<option value="${index + 1}">${month}</option>`;
    });
    
    // Fallback years
    const yearSelect = document.getElementById('year');
    yearSelect.innerHTML = '<option value="">Select year...</option>';
    const currentYear = new Date().getFullYear();
    for (let year = currentYear; year <= currentYear + 2; year++) {
        yearSelect.innerHTML += `<option value="${year}">${year}</option>`;
    }
    
    // Set current month and year
    const now = new Date();
    monthSelect.value = now.getMonth() + 1;
    yearSelect.value = now.getFullYear();
    
    updateSchedulePreview();
}

// Auto Date Range System
function autoSetDateRange() {
    const month = document.getElementById('month').value;
    const year = document.getElementById('year').value;
    
    if (month && year) {
        const startDate = new Date(year, month - 1, 1);
        const endDate = new Date(year, month, 0);
        
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');
        
        // Only auto-fill if user hasn't manually edited
        if (!startDateInput.dataset.manuallyEdited) {
            startDateInput.value = startDate.toISOString().split('T')[0];
        }
        if (!endDateInput.dataset.manuallyEdited) {
            endDateInput.value = endDate.toISOString().split('T')[0];
        }
        
        // Show auto-fill notification
        showAutoFillNotification(month, year);
    }
}

function resetAutoFill() {
    const month = document.getElementById('month').value;
    const year = document.getElementById('year').value;
    
    if (month && year) {
        const startDate = new Date(year, month - 1, 1);
        const endDate = new Date(year, month, 0);
        
        document.getElementById('start_date').value = startDate.toISOString().split('T')[0];
        document.getElementById('end_date').value = endDate.toISOString().split('T')[0];
        
        // Reset manual edit flags
        delete document.getElementById('start_date').dataset.manuallyEdited;
        delete document.getElementById('end_date').dataset.manuallyEdited;
        
        showNotification('Date range reset to full month', 'success');
    }
}

function showAutoFillNotification(month, year) {
    const monthNames = ['', 'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'];
    const monthName = monthNames[parseInt(month)];
    
    showNotification(`Date range auto-set for ${monthName} ${year}`, 'info');
}

function setupEventListeners() {
    // Month/Year change listeners with auto date range
    document.getElementById('month').addEventListener('change', function() {
        updateSchedulePreview();
        autoSetDateRange();
    });
    document.getElementById('year').addEventListener('change', function() {
        updateSchedulePreview();
        autoSetDateRange();
    });
    
    // Working hours change listeners for day-specific inputs
    const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    days.forEach(day => {
        const startInput = document.getElementById(`${day}_start`);
        const endInput = document.getElementById(`${day}_end`);
        const breakStartInput = document.getElementById(`${day}_break_start`);
        const breakEndInput = document.getElementById(`${day}_break_end`);
        
        if (startInput) startInput.addEventListener('change', updateWeeklyHours);
        if (endInput) endInput.addEventListener('change', updateWeeklyHours);
        if (breakStartInput) breakStartInput.addEventListener('change', updateWeeklyHours);
        if (breakEndInput) breakEndInput.addEventListener('change', updateWeeklyHours);
    });
    
    // Legacy working hours listeners (if they exist)
    const legacyStartTime = document.getElementById('default_start_time');
    const legacyEndTime = document.getElementById('default_end_time');
    if (legacyStartTime) legacyStartTime.addEventListener('change', updateWorkingHours);
    if (legacyEndTime) legacyEndTime.addEventListener('change', updateWorkingHours);
    
    // Date range manual editing detection
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    
    startDateInput.addEventListener('input', function() {
        this.dataset.manuallyEdited = 'true';
    });
    
    endDateInput.addEventListener('input', function() {
        this.dataset.manuallyEdited = 'true';
    });
    
    // Day selectors
    document.querySelectorAll('.day-selector').forEach(selector => {
        selector.addEventListener('click', function() {
            const checkbox = this.querySelector('input[type="checkbox"]');
            checkbox.checked = !checkbox.checked;
            this.classList.toggle('selected', checkbox.checked);
        });
    });
    
    // Form submission
    document.getElementById('monthlyScheduleForm').addEventListener('submit', handleFormSubmit);
}

function setupStepNavigation() {
    const nextBtn = document.getElementById('nextBtn');
    const prevBtn = document.getElementById('prevBtn');
    
    nextBtn.addEventListener('click', function() {
        if (validateCurrentStep()) {
            if (window.scheduleCurrentStep < window.scheduleTotalSteps) {
                window.scheduleCurrentStep++;
                updateStepDisplay();
            }
        }
    });
    
    prevBtn.addEventListener('click', function() {
        if (window.scheduleCurrentStep > 1) {
            window.scheduleCurrentStep--;
            updateStepDisplay();
        }
    });
}

function updateStepDisplay() {
    // Update step indicators
    document.querySelectorAll('.step-indicator').forEach((indicator, index) => {
        indicator.classList.toggle('active', index + 1 === window.scheduleCurrentStep);
    });
    
    // Update step content
    document.querySelectorAll('.form-step').forEach((step, index) => {
        step.classList.toggle('active', index + 1 === window.scheduleCurrentStep);
    });
    
    // Update buttons
    document.getElementById('prevBtn').classList.toggle('hidden', window.scheduleCurrentStep === 1);
    document.getElementById('nextBtn').classList.toggle('hidden', window.scheduleCurrentStep === window.scheduleTotalSteps);
    document.getElementById('submitBtn').classList.toggle('hidden', window.scheduleCurrentStep !== window.scheduleTotalSteps);
    
    // Update current step display
    document.getElementById('currentStep').textContent = window.scheduleCurrentStep;
    
    // Update review content if on last step
    if (window.scheduleCurrentStep === window.scheduleTotalSteps) {
        updateReviewContent();
    }
}

function validateCurrentStep() {
    const currentStepElement = document.querySelector(`.form-step[data-step="${window.scheduleCurrentStep}"]`);
    const requiredFields = currentStepElement.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('border-red-500');
            isValid = false;
        } else {
            field.classList.remove('border-red-500');
        }
    });
    
    if (!isValid) {
        showNotification('Please fill in all required fields', 'error');
    }
    
    return isValid;
}

function updateSchedulePreview() {
    const month = document.getElementById('month').value;
    const year = document.getElementById('year').value;
    const previewDiv = document.getElementById('schedule-preview');
    
    if (month && year) {
        const monthNames = [
            '', 'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];
        const monthName = monthNames[parseInt(month)];
        
        // Calculate start and end dates
        const startDate = new Date(year, month - 1, 1);
        const endDate = new Date(year, month, 0);
        
        // Auto-fill date fields only if they're empty or user wants auto-fill
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');
        
        if (!startDateInput.value || startDateInput.dataset.autoFill !== 'false') {
            startDateInput.value = startDate.toISOString().split('T')[0];
        }
        if (!endDateInput.value || endDateInput.dataset.autoFill !== 'false') {
            endDateInput.value = endDate.toISOString().split('T')[0];
        }
        
        previewDiv.innerHTML = `
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="p-2 bg-green-600 rounded-lg">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <div class="font-semibold text-gray-900 dark:text-white">${monthName} ${year}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">${endDate.getDate()} days total</div>
                    </div>
                </div>
            </div>
        `;
    } else {
        previewDiv.innerHTML = `
            <div class="flex items-center justify-center text-gray-500 dark:text-gray-400">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <span class="text-sm">Select month and year</span>
            </div>
        `;
    }
}

// Working Hours Template System
window.scheduleWorkingHoursTemplates = window.scheduleWorkingHoursTemplates || {};
console.log('Working Hours Templates initialized');

function applyWorkingHoursTemplate() {
    const template = document.getElementById('working_hours_template').value;
    if (!template || !window.scheduleWorkingHoursTemplates[template]) return;
    
    const templateData = window.scheduleWorkingHoursTemplates[template].working_hours;
    
    // Apply template to all days
    Object.keys(templateData).forEach(day => {
        const dayData = templateData[day];
        const checkbox = document.querySelector(`input[data-day="${day}"].day-enabled`);
        const daySchedule = document.querySelector(`.day-schedule[data-day="${day}"]`);
        
        if (dayData === null) {
            // Disable this day
            if (checkbox) {
                checkbox.checked = false;
                toggleDay(day);
            }
        } else {
            // Enable and set times for this day
            if (checkbox) {
                checkbox.checked = true;
                toggleDay(day);
            }
            
            // Set working hours
            const startInput = document.getElementById(`${day}_start`);
            const endInput = document.getElementById(`${day}_end`);
            const breakStartInput = document.getElementById(`${day}_break_start`);
            const breakEndInput = document.getElementById(`${day}_break_end`);
            
            if (startInput) startInput.value = dayData.start;
            if (endInput) endInput.value = dayData.end;
            if (breakStartInput && dayData.break_start) breakStartInput.value = dayData.break_start;
            if (breakEndInput && dayData.break_end) breakEndInput.value = dayData.break_end;
        }
    });
    
    updateWeeklyHours();
}

function toggleDay(day) {
    const checkbox = document.querySelector(`input[data-day="${day}"].day-enabled`);
    const daySchedule = document.querySelector(`.day-schedule[data-day="${day}"]`);
    const timeInputs = daySchedule.querySelectorAll('input[type="time"]');
    
    if (checkbox.checked) {
        // Enable day
        daySchedule.classList.remove('opacity-50');
        timeInputs.forEach(input => {
            input.disabled = false;
            input.required = true;
        });
    } else {
        // Disable day
        daySchedule.classList.add('opacity-50');
        timeInputs.forEach(input => {
            input.disabled = true;
            input.required = false;
        });
    }
    
    updateWeeklyHours();
}

function calculateWorkingHours(startTime, endTime, breakStart = null, breakEnd = null) {
    if (!startTime || !endTime) return 0;
    
    const start = new Date(`2000-01-01T${startTime}:00`);
    const end = new Date(`2000-01-01T${endTime}:00`);
    let workingMs = end - start;
    
    // Subtract break time if provided
    if (breakStart && breakEnd) {
        const bStart = new Date(`2000-01-01T${breakStart}:00`);
        const bEnd = new Date(`2000-01-01T${breakEnd}:00`);
        const breakMs = bEnd - bStart;
        workingMs -= breakMs;
    }
    
    return workingMs / (1000 * 60 * 60); // Convert to hours
}

function updateWeeklyHours() {
    const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    let totalWeeklyHours = 0;
    
    days.forEach(day => {
        const checkbox = document.querySelector(`input[data-day="${day}"].day-enabled`);
        if (!checkbox || !checkbox.checked) return;
        
        const startTime = document.getElementById(`${day}_start`)?.value;
        const endTime = document.getElementById(`${day}_end`)?.value;
        const breakStart = document.getElementById(`${day}_break_start`)?.value;
        const breakEnd = document.getElementById(`${day}_break_end`)?.value;
        
        const dayHours = calculateWorkingHours(startTime, endTime, breakStart, breakEnd);
        totalWeeklyHours += dayHours;
    });
    
    const display = document.getElementById('weekly-hours-display');
    if (display) {
        display.textContent = `${totalWeeklyHours.toFixed(1)} hours`;
    }
}

function updateWorkingHours() {
    updateWeeklyHours();
    
    // Legacy function for old single time inputs (if they exist)
    const startTime = document.getElementById('default_start_time')?.value;
    const endTime = document.getElementById('default_end_time')?.value;
    const display = document.getElementById('working-hours-display');
    
    if (startTime && endTime && display) {
        const start = new Date(`2000-01-01 ${startTime}`);
        const end = new Date(`2000-01-01 ${endTime}`);
        const diffMs = end - start;
        const hours = diffMs / (1000 * 60 * 60);
        
        if (hours > 0) {
            display.innerHTML = `${hours.toFixed(1)} hours`;
            display.className = 'text-3xl font-bold text-blue-600 dark:text-blue-400';
        } else {
            display.innerHTML = 'Invalid time range';
            display.className = 'text-3xl font-bold text-red-600 dark:text-red-400';
        }
    }
}

function getWorkingHoursDisplay() {
    const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    const dayNames = {
        'monday': 'Monday',
        'tuesday': 'Tuesday', 
        'wednesday': 'Wednesday',
        'thursday': 'Thursday',
        'friday': 'Friday',
        'saturday': 'Saturday',
        'sunday': 'Sunday'
    };
    
    let workingDaysCount = 0;
    let workingHoursDisplay = '';
    let totalWeeklyHours = 0;
    
    days.forEach(day => {
        const checkbox = document.querySelector(`input[data-day="${day}"].day-enabled`);
        if (checkbox && checkbox.checked) {
            workingDaysCount++;
            const startTime = document.getElementById(`${day}_start`)?.value || '08:00';
            const endTime = document.getElementById(`${day}_end`)?.value || '16:00';
            const breakStart = document.getElementById(`${day}_break_start`)?.value || '12:00';
            const breakEnd = document.getElementById(`${day}_break_end`)?.value || '13:00';
            
            const dayHours = calculateWorkingHours(startTime, endTime, breakStart, breakEnd);
            totalWeeklyHours += dayHours;
            
            workingHoursDisplay += `
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-600 dark:text-gray-400">${dayNames[day]}:</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white">${startTime} - ${endTime}</dd>
                </div>
            `;
        }
    });
    
    // Add summary
    const avgHours = workingDaysCount > 0 ? (totalWeeklyHours / workingDaysCount).toFixed(1) : 0;
    workingHoursDisplay += `
        <div class="border-t border-gray-200 dark:border-gray-600 pt-2 mt-2">
            <div class="flex justify-between">
                <dt class="text-sm font-semibold text-gray-700 dark:text-gray-300">Weekly Total:</dt>
                <dd class="text-sm font-bold text-blue-600 dark:text-blue-400">${totalWeeklyHours.toFixed(1)} hours</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-sm text-gray-600 dark:text-gray-400">Average/Day:</dt>
                <dd class="text-sm font-medium text-gray-900 dark:text-white">${avgHours} hours</dd>
            </div>
        </div>
    `;
    
    return workingHoursDisplay;
}

function updateReviewContent() {
    const formData = new FormData(document.getElementById('monthlyScheduleForm'));
    const workDays = Array.from(document.querySelectorAll('input[name="work_days[]"]:checked'))
        .map(cb => cb.value);
    
    const reviewContent = document.getElementById('reviewContent');
    
    const locationName = document.getElementById('location_id').selectedOptions[0]?.text || 'Not selected';
    const monthName = document.getElementById('month').selectedOptions[0]?.text || 'Not selected';
    const yearValue = document.getElementById('year').value || 'Not selected';
    
    reviewContent.innerHTML = `
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="space-y-4">
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-3">Basic Information</h4>
                    <dl class="space-y-2">
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600 dark:text-gray-400">Schedule Name:</dt>
                            <dd class="text-sm font-medium text-gray-900 dark:text-white">${formData.get('name') || 'Not specified'}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600 dark:text-gray-400">Location:</dt>
                            <dd class="text-sm font-medium text-gray-900 dark:text-white">${locationName}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600 dark:text-gray-400">Period:</dt>
                            <dd class="text-sm font-medium text-gray-900 dark:text-white">${monthName} ${yearValue}</dd>
                        </div>
                    </dl>
                </div>
                
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-3">Working Hours</h4>
                    <dl class="space-y-2">
                        ${getWorkingHoursDisplay()}
                    </dl>
                </div>
            </div>
            
            <div class="space-y-4">
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-3">Schedule Settings</h4>
                    <dl class="space-y-2">
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600 dark:text-gray-400">Working Days:</dt>
                            <dd class="text-sm font-medium text-gray-900 dark:text-white">${workDays.length} days/week</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600 dark:text-gray-400">Late Threshold:</dt>
                            <dd class="text-sm font-medium text-gray-900 dark:text-white">${formData.get('late_threshold_minutes')} minutes</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600 dark:text-gray-400">Overtime Allowed:</dt>
                            <dd class="text-sm font-medium text-gray-900 dark:text-white">${formData.get('overtime_allowed') ? 'Yes' : 'No'}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    `;
}

async function handleFormSubmit(e) {
    e.preventDefault();
    
    if (!validateCurrentStep()) {
        return;
    }
    
    const submitBtn = document.getElementById('submitBtn');
    const originalText = submitBtn.innerHTML;
    
    try {
        // Show loading state
        submitBtn.innerHTML = '<svg class="animate-spin h-5 w-5 mr-2 inline-block" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Creating...';
        submitBtn.disabled = true;
        
        showLoading('Creating your schedule template...');
        
        const formData = new FormData(e.target);
        
        // Collect working hours per day
        const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        const workingHoursPerDay = {};
        const enabledDays = [];
        
        days.forEach(day => {
            const checkbox = document.querySelector(`input[data-day="${day}"].day-enabled`);
            if (checkbox && checkbox.checked) {
                enabledDays.push(day);
                workingHoursPerDay[day] = {
                    start: document.getElementById(`${day}_start`)?.value || '08:00',
                    end: document.getElementById(`${day}_end`)?.value || '16:00',
                    break_start: document.getElementById(`${day}_break_start`)?.value || '12:00',
                    break_end: document.getElementById(`${day}_break_end`)?.value || '13:00'
                };
            } else {
                workingHoursPerDay[day] = null;
            }
        });
        
        // Prepare data with new structure
        const data = {
            name: formData.get('name'),
            month: parseInt(formData.get('month')),
            year: parseInt(formData.get('year')),
            start_date: formData.get('start_date'),
            end_date: formData.get('end_date'),
            location_id: formData.get('location_id'),
            description: formData.get('description'),
            working_hours_template: formData.get('working_hours_template') || 'standard_5_days',
            working_hours_per_day: workingHoursPerDay,
            metadata: {
                work_days: enabledDays,
                working_hours_template: formData.get('working_hours_template'),
                late_threshold_minutes: parseInt(formData.get('late_threshold_minutes')) || 15,
                early_departure_threshold_minutes: parseInt(formData.get('early_departure_threshold_minutes')) || 30,
                overtime_allowed: formData.get('overtime_allowed') === 'on' || true
            }
        };
        
        console.log('📊 Data being sent to API:', JSON.stringify(data, null, 2));
        
        const response = await fetch('/api/schedule-management/monthly', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        });
        
        if (!response.ok) {
            let errorMessage = `HTTP ${response.status}: ${response.statusText}`;
            try {
                const errorData = await response.json();
                errorMessage = errorData.message || errorMessage;
            } catch (parseError) {
                console.error('Error parsing error response:', parseError);
            }
            throw new Error(errorMessage);
        }
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Monthly schedule created successfully!', 'success');
            
            // Redirect to schedule list after a short delay
            setTimeout(() => {
                window.location.href = `/schedule-management/monthly`;
            }, 2000);
        } else {
            throw new Error(result.message || 'Failed to create schedule');
        }
        
    } catch (error) {
        console.error('Error creating schedule:', error);
        showNotification('Failed to create schedule: ' + error.message, 'error');
        
        // Restore button state
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    } finally {
        hideLoading();
    }
}

function showPreview() {
    updateReviewContent();
    document.getElementById('previewModal').classList.remove('hidden');
}

function closePreviewModal() {
    document.getElementById('previewModal').classList.add('hidden');
}

function submitFormFromPreview() {
    closePreviewModal();
    document.getElementById('monthlyScheduleForm').dispatchEvent(new Event('submit'));
}

function showLoading(message = 'Loading...') {
    const overlay = document.getElementById('loadingOverlay');
    overlay.querySelector('h3').textContent = message;
    overlay.classList.remove('hidden');
}

function hideLoading() {
    document.getElementById('loadingOverlay').classList.add('hidden');
}

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transition-all transform ${
        type === 'success' ? 'bg-green-600 text-white' :
        type === 'error' ? 'bg-red-600 text-white' :
        'bg-blue-600 text-white'
    }`;
    
    notification.innerHTML = `
        <div class="flex items-center space-x-3">
            <div class="flex-shrink-0">
                ${type === 'success' ? '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>' :
                  type === 'error' ? '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>' :
                  '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'}
            </div>
            <div class="flex-1">
                <p class="font-medium">${message}</p>
            </div>
            <button onclick="this.parentNode.parentNode.remove()" class="flex-shrink-0 text-white/80 hover:text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => notification.remove(), 300);
        }
    }, 5000);
}

} // End of schedule script protection
</script>
@endpush
@endsection