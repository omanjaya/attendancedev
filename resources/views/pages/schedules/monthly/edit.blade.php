@extends('layouts.authenticated-unified')

@section('title', 'Edit: ' . $monthlySchedule->name)

@section('page-content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="p-6 lg:p-8">
        <!-- Modern Page Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <a href="{{ url()->previous() }}" 
                       class="flex items-center justify-center w-10 h-10 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-all duration-200 shadow-sm">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                    <div>
                        <h1 id="page-title" class="text-3xl font-bold text-gray-900 dark:text-white">Edit: {{ $monthlySchedule->name }}</h1>
                        <p id="page-subtitle" class="mt-1 text-sm text-gray-500 dark:text-gray-400">Editing schedule for {{ \Carbon\Carbon::createFromDate($monthlySchedule->year, $monthlySchedule->month, 1)->format('F Y') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div id="loading-state" class="flex flex-col items-center justify-center py-20">
            <div class="relative">
                <div class="animate-spin rounded-full h-16 w-16 border-4 border-blue-200 border-t-blue-600 mb-6"></div>
            </div>
            <p class="text-lg font-semibold text-gray-700 dark:text-gray-300">Loading schedule data...</p>
        </div>

        <!-- Edit Form -->
        <div id="edit-form" class="hidden">
            <form id="schedule-form" class="space-y-8">
                <input type="hidden" id="schedule-id">
                
                <!-- Basic Information -->
                <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6 flex items-center">
                        <svg class="w-6 h-6 mr-3 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Basic Information
                    </h2>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c1.1 0 2 .9 2 2v1M7 7v12h12V7M7 7H3c-.6 0-1 .4-1 1v10c0 .6.4 1 1 1h4m4-16v4c0 1.1-.9 2-2 2H9"/>
                                </svg>
                                Schedule Name *
                            </label>
                            <input type="text" id="name" name="name" required
                                   class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700/50 dark:text-white transition-all duration-200"
                                   placeholder="e.g., July 2025 - Standard Schedule">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div>
                            <label for="location_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                Location *
                            </label>
                            <select id="location_id" name="location_id" required
                                    class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700/50 dark:text-white transition-all duration-200">
                                <option value="">Select Location</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div>
                            <label for="month" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                Month *
                            </label>
                            <select id="month" name="month" required
                                    class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700/50 dark:text-white transition-all duration-200">
                                <option value="1">January</option>
                                <option value="2">February</option>
                                <option value="3">March</option>
                                <option value="4">April</option>
                                <option value="5">May</option>
                                <option value="6">June</option>
                                <option value="7">July</option>
                                <option value="8">August</option>
                                <option value="9">September</option>
                                <option value="10">October</option>
                                <option value="11">November</option>
                                <option value="12">December</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div>
                            <label for="year" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                Year *
                            </label>
                            <select id="year" name="year" required
                                    class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700/50 dark:text-white transition-all duration-200">
                                <option value="2025">2025</option>
                                <option value="2026">2026</option>
                                <option value="2027">2027</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="lg:col-span-2">
                            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/>
                                </svg>
                                Description (Optional)
                            </label>
                            <textarea id="description" name="description" rows="3"
                                      class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700/50 dark:text-white transition-all duration-200"
                                      placeholder="Additional notes or description for this schedule..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Schedule Period -->
                <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6 flex items-center">
                        <svg class="w-6 h-6 mr-3 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        Schedule Period
                    </h2>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Start Date *
                            </label>
                            <input type="date" id="start_date" name="start_date" required
                                   class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700/50 dark:text-white transition-all duration-200">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                End Date *
                            </label>
                            <input type="date" id="end_date" name="end_date" required
                                   class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700/50 dark:text-white transition-all duration-200">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                </div>

                <!-- Working Hours Template -->
                <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6 flex items-center">
                        <svg class="w-6 h-6 mr-3 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Working Hours Configuration
                    </h2>
                    
                    <div class="mb-6">
                        <label for="working_hours_template" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Working Hours Template *
                        </label>
                        <select id="working_hours_template" name="working_hours_template" required
                                class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700/50 dark:text-white transition-all duration-200">
                            <option value="">Select Template</option>
                            <option value="standard_5_days">Standard 5 Days (Mon-Thu: 07:30-15:30, Fri: 07:30-13:00)</option>
                            <option value="uniform_5_days">Uniform 5 Days (Mon-Fri: 08:00-16:00)</option>
                            <option value="half_day_saturday">6 Days with Half Saturday (Mon-Fri: 07:30-15:30, Sat: 07:30-12:00)</option>
                            <option value="custom">Custom Working Hours</option>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>

                    <!-- Custom Working Hours Grid -->
                    <div id="custom-working-hours" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- Working hours for each day will be populated here -->
                    </div>
                </div>

                <!-- Advanced Settings -->
                <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6 flex items-center">
                        <svg class="w-6 h-6 mr-3 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Advanced Settings
                    </h2>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div>
                            <label for="late_threshold_minutes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Late Threshold (minutes)
                            </label>
                            <input type="number" id="late_threshold_minutes" name="late_threshold_minutes" min="0" max="60" value="15"
                                   class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700/50 dark:text-white transition-all duration-200">
                        </div>

                        <div>
                            <label for="early_departure_threshold_minutes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Early Departure Threshold (minutes)
                            </label>
                            <input type="number" id="early_departure_threshold_minutes" name="early_departure_threshold_minutes" min="0" max="120" value="30"
                                   class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700/50 dark:text-white transition-all duration-200">
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" id="overtime_allowed" name="overtime_allowed" checked
                                   class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                            <label for="overtime_allowed" class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                Allow Overtime
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-between">
                    <a href="{{ url()->previous() }}" 
                       class="inline-flex items-center px-6 py-3 bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-all duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Cancel
                    </a>
                    
                    <button type="submit" id="save-button"
                            class="inline-flex items-center px-8 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 border border-transparent rounded-xl text-sm font-semibold text-white hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-200 shadow-md hover:shadow-lg">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span id="save-text">Update Schedule</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Error State -->
        <div id="error-state" class="hidden text-center py-20">
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-2xl p-8 max-w-md mx-auto">
                <svg class="w-12 h-12 mx-auto text-red-500 dark:text-red-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                </svg>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Failed to Load Schedule</h3>
                <p id="error-message" class="text-red-600 dark:text-red-400 mb-4">An error occurred while loading the schedule data.</p>
                <button onclick="loadScheduleData()" 
                        class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg font-medium transition-all duration-200">
                    Try Again
                </button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.invalid-feedback {
    display: none;
    width: 100%;
    margin-top: 0.25rem;
    font-size: 0.875rem;
    color: #dc2626;
}

.is-invalid {
    border-color: #dc2626 !important;
    box-shadow: 0 0 0 0.2rem rgba(220, 38, 38, 0.25) !important;
}

.is-invalid ~ .invalid-feedback {
    display: block;
}
</style>
@endpush

@push('scripts')
<script>
window.currentScheduleId = '{{ $monthlySchedule->id }}';
window.workingHoursTemplates = {};

document.addEventListener('DOMContentLoaded', function() {
    loadFormData();
    setupFormHandlers();
});

async function loadFormData() {
    const loadingState = document.getElementById('loading-state');
    const editForm = document.getElementById('edit-form');
    const errorState = document.getElementById('error-state');
    
    try {
        console.log('🔄 Starting form data loading...');
        
        loadingState.classList.remove('hidden');
        editForm.classList.add('hidden');
        errorState.classList.add('hidden');
        
        // Load create form data for templates and locations
        console.log('📡 Loading form creation data...');
        const createResponse = await fetch('/api/schedule-management/monthly/create');
        const createData = await createResponse.json();
        
        if (createData.success) {
            console.log('✅ Form creation data loaded:', createData.data);
            window.workingHoursTemplates = createData.data.working_hours_templates;
            populateLocations(createData.data.locations);
        } else {
            console.warn('⚠️ Form creation data failed:', createData);
        }
        
        // Load current schedule data
        console.log('📡 Loading existing schedule data for ID:', window.currentScheduleId);
        const scheduleResponse = await fetch(`/api/schedule-management/monthly/${window.currentScheduleId}`, {
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });
        
        if (!scheduleResponse.ok) {
            throw new Error(`HTTP ${scheduleResponse.status}: Failed to fetch schedule data`);
        }
        
        const scheduleData = await scheduleResponse.json();
        console.log('📦 Raw API response:', scheduleData);
        
        if (scheduleData.success && scheduleData.data) {
            console.log('✅ Schedule data loaded:', scheduleData.data);
            
            // Check if we actually have valid data
            if (!scheduleData.data.id || !scheduleData.data.name) {
                console.error('⚠️ Schedule data is incomplete:', scheduleData.data);
                throw new Error('Schedule data is incomplete or corrupted');
            }
            
            // Give a moment for locations to populate first
            setTimeout(() => {
                populateFormData(scheduleData.data);
                loadingState.classList.add('hidden');
                editForm.classList.remove('hidden');
                console.log('🎉 Form ready for editing!');
            }, 200);
        } else {
            throw new Error(scheduleData.message || 'Failed to load schedule');
        }
        
    } catch (error) {
        console.error('❌ Error loading form data:', error);
        loadingState.classList.add('hidden');
        errorState.classList.remove('hidden');
        document.getElementById('error-message').textContent = error.message;
    }
}

function populateLocations(locations) {
    const locationSelect = document.getElementById('location_id');
    locationSelect.innerHTML = '<option value="">Select Location</option>';
    
    locations.forEach(location => {
        const option = document.createElement('option');
        option.value = location.id;
        option.textContent = location.name;
        locationSelect.appendChild(option);
    });
}

function populateFormData(schedule) {
    console.log('Populating form with schedule data:', schedule);
    
    // Validate schedule data
    if (!schedule || !schedule.id) {
        console.error('Invalid schedule data provided:', schedule);
        showNotification('Invalid schedule data received', 'error');
        return;
    }
    
    // Update page title to show current schedule name
    document.getElementById('page-title').textContent = `Edit: ${schedule.name || 'Unknown'}`;
    document.getElementById('page-subtitle').textContent = `Editing schedule for ${schedule.month_name || getMonthName(schedule.month)} ${schedule.year || 'Unknown'}`;
    
    // Basic Information
    document.getElementById('schedule-id').value = schedule.id;
    document.getElementById('name').value = schedule.name || '';
    document.getElementById('month').value = schedule.month || '';
    document.getElementById('year').value = schedule.year || '';
    document.getElementById('start_date').value = schedule.start_date || '';
    document.getElementById('end_date').value = schedule.end_date || '';
    document.getElementById('description').value = schedule.description || '';
    
    // Location - wait a bit for options to load
    setTimeout(() => {
        const locationSelect = document.getElementById('location_id');
        if (schedule.location?.id) {
            locationSelect.value = schedule.location.id;
        }
    }, 100);
    
    // Advanced settings with fallback defaults
    const metadata = schedule.metadata || {};
    
    document.getElementById('late_threshold_minutes').value = metadata.late_threshold_minutes || 15;
    document.getElementById('early_departure_threshold_minutes').value = metadata.early_departure_threshold_minutes || 30;
    document.getElementById('overtime_allowed').checked = metadata.overtime_allowed !== false;
    
    // Working hours template
    const template = metadata.working_hours_template || 'custom';
    document.getElementById('working_hours_template').value = template;
    
    // Wait for template to be set, then generate grid
    setTimeout(() => {
        generateWorkingHoursGrid();
        
        // Populate working hours after grid is generated
        setTimeout(() => {
            if (metadata.working_hours_per_day) {
                populateWorkingHours(metadata.working_hours_per_day);
            }
        }, 100);
    }, 100);
    
    console.log('Form populated successfully');
}

function setupFormHandlers() {
    // Template change handler
    document.getElementById('working_hours_template').addEventListener('change', function() {
        generateWorkingHoursGrid();
    });
    
    // Form submission
    document.getElementById('schedule-form').addEventListener('submit', handleFormSubmit);
    
    // Date validation
    document.getElementById('start_date').addEventListener('change', validateDates);
    document.getElementById('end_date').addEventListener('change', validateDates);
}

function generateWorkingHoursGrid() {
    const template = document.getElementById('working_hours_template').value;
    const container = document.getElementById('custom-working-hours');
    
    if (!template) {
        container.innerHTML = '<p class="text-gray-500 dark:text-gray-400 col-span-3">Please select a working hours template first.</p>';
        return;
    }
    
    const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    const dayNames = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    
    let templateData = {};
    if (template !== 'custom' && window.workingHoursTemplates[template]) {
        templateData = window.workingHoursTemplates[template].working_hours;
    }
    
    container.innerHTML = '';
    
    days.forEach((day, index) => {
        const dayData = templateData[day] || {};
        const isWorkingDay = dayData && dayData.start && dayData.end;
        
        const dayCard = document.createElement('div');
        dayCard.className = 'bg-gray-50 dark:bg-gray-700/30 rounded-xl p-4 border border-gray-200 dark:border-gray-600';
        
        dayCard.innerHTML = `
            <div class="flex items-center justify-between mb-3">
                <label class="font-semibold text-gray-900 dark:text-white">${dayNames[index]}</label>
                <input type="checkbox" id="work_${day}" name="work_${day}" ${isWorkingDay ? 'checked' : ''}
                       class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                       onchange="toggleDayInputs('${day}')">
            </div>
            <div id="inputs_${day}" class="${isWorkingDay ? '' : 'hidden'} space-y-2">
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Start</label>
                        <input type="time" id="start_${day}" name="start_${day}" value="${dayData.start || '08:00'}"
                               class="block w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 dark:bg-gray-600 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">End</label>
                        <input type="time" id="end_${day}" name="end_${day}" value="${dayData.end || '16:00'}"
                               class="block w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 dark:bg-gray-600 dark:text-white">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Break Start</label>
                        <input type="time" id="break_start_${day}" name="break_start_${day}" value="${dayData.break_start || '12:00'}"
                               class="block w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 dark:bg-gray-600 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Break End</label>
                        <input type="time" id="break_end_${day}" name="break_end_${day}" value="${dayData.break_end || '13:00'}"
                               class="block w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 dark:bg-gray-600 dark:text-white">
                    </div>
                </div>
            </div>
        `;
        
        container.appendChild(dayCard);
    });
}

function populateWorkingHours(workingHours) {
    console.log('Populating working hours with data:', workingHours);
    
    const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    
    days.forEach(day => {
        const dayData = workingHours[day];
        const isWorking = dayData && dayData.start && dayData.end;
        
        console.log(`Day ${day}:`, { dayData, isWorking });
        
        const checkbox = document.getElementById(`work_${day}`);
        const inputs = document.getElementById(`inputs_${day}`);
        
        if (checkbox && inputs) {
            checkbox.checked = isWorking;
            inputs.classList.toggle('hidden', !isWorking);
            
            if (isWorking) {
                // Fill existing time data
                const startInput = document.getElementById(`start_${day}`);
                const endInput = document.getElementById(`end_${day}`);
                const breakStartInput = document.getElementById(`break_start_${day}`);
                const breakEndInput = document.getElementById(`break_end_${day}`);
                
                if (startInput) startInput.value = dayData.start || '08:00';
                if (endInput) endInput.value = dayData.end || '16:00';
                if (breakStartInput) breakStartInput.value = dayData.break_start || '';
                if (breakEndInput) breakEndInput.value = dayData.break_end || '';
                
                console.log(`✅ Populated ${day} with:`, {
                    start: dayData.start,
                    end: dayData.end,
                    break_start: dayData.break_start,
                    break_end: dayData.break_end
                });
            } else {
                console.log(`❌ ${day} is not a working day`);
            }
        } else {
            console.warn(`Missing elements for ${day}:`, { checkbox: !!checkbox, inputs: !!inputs });
        }
    });
    
    console.log('Working hours population complete');
}

function toggleDayInputs(day) {
    const checkbox = document.getElementById(`work_${day}`);
    const inputs = document.getElementById(`inputs_${day}`);
    
    if (checkbox && inputs) {
        inputs.classList.toggle('hidden', !checkbox.checked);
    }
}

function validateDates() {
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');
    
    if (startDate.value && endDate.value) {
        if (new Date(startDate.value) >= new Date(endDate.value)) {
            endDate.setCustomValidity('End date must be after start date');
        } else {
            endDate.setCustomValidity('');
        }
    }
}

async function handleFormSubmit(event) {
    event.preventDefault();
    
    const saveButton = document.getElementById('save-button');
    const saveText = document.getElementById('save-text');
    const originalText = saveText.textContent;
    
    try {
        saveButton.disabled = true;
        saveText.textContent = 'Updating...';
        
        // Clear previous validation
        clearValidationErrors();
        
        const formData = collectFormData();
        
        const response = await fetch(`/api/schedule-management/monthly/${window.currentScheduleId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(formData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Schedule updated successfully!', 'success');
            setTimeout(() => {
                window.location.href = `/schedule-management/monthly/${window.currentScheduleId}`;
            }, 1500);
        } else {
            throw new Error(result.message || 'Failed to update schedule');
        }
        
    } catch (error) {
        console.error('Error updating schedule:', error);
        
        if (error.response && error.response.status === 422) {
            // Validation errors
            const errors = await error.response.json();
            displayValidationErrors(errors.errors);
        } else {
            showNotification(error.message || 'Failed to update schedule', 'error');
        }
    } finally {
        saveButton.disabled = false;
        saveText.textContent = originalText;
    }
}

function collectFormData() {
    const formData = {
        name: document.getElementById('name').value,
        month: parseInt(document.getElementById('month').value),
        year: parseInt(document.getElementById('year').value),
        start_date: document.getElementById('start_date').value,
        end_date: document.getElementById('end_date').value,
        location_id: document.getElementById('location_id').value,
        description: document.getElementById('description').value,
        working_hours_template: document.getElementById('working_hours_template').value
    };
    
    // Collect working hours
    const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    const workingHours = {};
    
    days.forEach(day => {
        const checkbox = document.getElementById(`work_${day}`);
        if (checkbox && checkbox.checked) {
            workingHours[day] = {
                start: document.getElementById(`start_${day}`).value,
                end: document.getElementById(`end_${day}`).value,
                break_start: document.getElementById(`break_start_${day}`).value || null,
                break_end: document.getElementById(`break_end_${day}`).value || null
            };
        } else {
            workingHours[day] = null;
        }
    });
    
    // Collect metadata
    formData.metadata = {
        working_hours_per_day: workingHours,
        working_hours_template: formData.working_hours_template,
        work_days: days.filter(day => workingHours[day]),
        late_threshold_minutes: parseInt(document.getElementById('late_threshold_minutes').value),
        early_departure_threshold_minutes: parseInt(document.getElementById('early_departure_threshold_minutes').value),
        overtime_allowed: document.getElementById('overtime_allowed').checked
    };
    
    formData.working_hours_per_day = workingHours;
    
    return formData;
}

function clearValidationErrors() {
    document.querySelectorAll('.is-invalid').forEach(element => {
        element.classList.remove('is-invalid');
    });
}

function displayValidationErrors(errors) {
    Object.keys(errors).forEach(field => {
        const input = document.getElementById(field);
        if (input) {
            input.classList.add('is-invalid');
            const feedback = input.parentNode.querySelector('.invalid-feedback');
            if (feedback) {
                feedback.textContent = errors[field][0];
            }
        }
    });
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

async function loadScheduleData() {
    await loadFormData();
}

// Utility Functions
function getMonthName(monthNumber) {
    const months = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];
    return months[monthNumber - 1] || 'Unknown';
}

function formatDateTime(dateTimeString) {
    if (!dateTimeString) return '';
    return new Date(dateTimeString).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}
</script>
@endpush
@endsection