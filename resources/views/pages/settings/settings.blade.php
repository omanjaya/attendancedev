@extends('layouts.authenticated')

@section('title', 'System Settings')

@section('page-content')
    <div class="max-w-4xl mx-auto">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">System Settings</h1>
            <p class="mt-1 text-sm text-gray-600">Configure system-wide settings for your attendance management system</p>
        </div>
        
        <!-- Settings Form -->
        <form action="{{ route('system.settings.update') }}" method="POST" class="space-y-8">
            @csrf
            @method('PUT')
            
            <!-- General Settings -->
            <x-ui.card title="General Settings" subtitle="Basic system configuration">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <x-ui.label for="system_name">System Name</x-ui.label>
                        <x-ui.input 
                            type="text"
                            name="system_name"
                            id="system_name"
                            value="{{ config('app.name', 'AttendanceHub') }}"
                            placeholder="AttendanceHub" />
                        <p class="mt-1 text-sm text-muted-foreground">The name of your organization or system</p>
                    </div>
                    
                    <div>
                        <x-ui.label for="timezone">System Timezone</x-ui.label>
                        <x-ui.select name="timezone" id="timezone">
                            <option value="America/New_York">Eastern Time (UTC-5)</option>
                            <option value="America/Chicago">Central Time (UTC-6)</option>
                            <option value="America/Denver">Mountain Time (UTC-7)</option>
                            <option value="America/Los_Angeles">Pacific Time (UTC-8)</option>
                            <option value="UTC" selected>UTC (Universal Time)</option>
                            <option value="Europe/London">London (UTC+0)</option>
                            <option value="Asia/Tokyo">Tokyo (UTC+9)</option>
                        </x-ui.select>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <x-ui.label for="date_format">Date Format</x-ui.label>
                        <x-ui.select name="date_format" id="date_format">
                            <option value="Y-m-d" selected>2024-01-15 (YYYY-MM-DD)</option>
                            <option value="m/d/Y">01/15/2024 (MM/DD/YYYY)</option>
                            <option value="d/m/Y">15/01/2024 (DD/MM/YYYY)</option>
                            <option value="F j, Y">January 15, 2024</option>
                        </x-ui.select>
                    </div>
                    
                    <div>
                        <x-ui.label for="time_format">Time Format</x-ui.label>
                        <x-ui.select name="time_format" id="time_format">
                            <option value="H:i" selected>14:30 (24 Hour)</option>
                            <option value="g:i A">2:30 PM (12 Hour)</option>
                        </x-ui.select>
                    </div>
                </div>
            </x-ui.card>
                
            <!-- Attendance Settings -->
            <x-ui.card title="Attendance Settings" subtitle="Configure attendance tracking rules">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <x-ui.label for="default_work_start">Default Work Start Time</x-ui.label>
                        <x-ui.input type="time" name="default_work_start" id="default_work_start" value="09:00" />
                        <p class="mt-1 text-sm text-muted-foreground">Standard work day start time</p>
                    </div>
                    
                    <div>
                        <x-ui.label for="default_work_end">Default Work End Time</x-ui.label>
                        <x-ui.input type="time" name="default_work_end" id="default_work_end" value="17:00" />
                        <p class="mt-1 text-sm text-muted-foreground">Standard work day end time</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <x-ui.label for="late_threshold">Late Threshold (minutes)</x-ui.label>
                        <x-ui.input type="number" name="late_threshold" id="late_threshold" value="15" min="1" max="60" />
                        <p class="mt-1 text-sm text-muted-foreground">Minutes after start time to mark as late</p>
                    </div>
                    
                    <div>
                        <x-ui.label for="break_duration">Break Duration (minutes)</x-ui.label>
                        <x-ui.input type="number" name="break_duration" id="break_duration" value="60" min="30" max="120" />
                        <p class="mt-1 text-sm text-muted-foreground">Standard lunch break duration</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-6">
                    <label class="flex items-center">
                        <x-ui.checkbox name="require_face_recognition" value="1" checked />
                        <span class="ml-2 text-sm text-foreground">Require face recognition for check-in</span>
                    </label>
                    
                    <label class="flex items-center">
                        <x-ui.checkbox name="require_gps_verification" value="1" checked />
                        <span class="ml-2 text-sm text-foreground">Require GPS verification</span>
                    </label>
                </div>
            </x-ui.card>
                
                <!-- Face Recognition Settings -->
                <x-ui.card title="Face Recognition Settings" subtitle="Configure face detection parameters">
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <x-ui.label for="face_confidence_threshold">Face Confidence Threshold</x-ui.label>
                            <x-ui.input
                                type="number"
                                name="face_confidence_threshold"
                                id="face_confidence_threshold"
                                value="0.85"
                                step="0.01"
                                min="0.5"
                                max="1.0"
                            />
                            <p class="mt-1 text-sm text-muted-foreground">Minimum confidence level for face recognition (0.5-1.0)</p>
                        </div>
                        
                        <div>
                            <x-ui.label for="max_face_distance">Max Face Distance</x-ui.label>
                            <x-ui.input
                                type="number"
                                name="max_face_distance"
                                id="max_face_distance"
                                value="0.6"
                                step="0.01"
                                min="0.1"
                                max="1.0"
                            />
                            <p class="mt-1 text-sm text-muted-foreground">Maximum distance between faces for matching</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <x-ui.label for="required_gestures">Required Anti-Spoofing Gestures</x-ui.label>
                            <x-ui.select name="required_gestures" id="required_gestures" multiple>
                                <option value="blink">Eye Blink</option>
                                <option value="smile">Smile</option>
                                <option value="head_nod">Head Nod</option>
                                <option value="head_shake">Head Shake</option>
                            </x-ui.select>
                            <p class="mt-1 text-sm text-muted-foreground">Select gestures required for verification</p>
                        </div>
                        
                        <div>
                            <x-ui.label for="gesture_timeout">Gesture Timeout (seconds)</x-ui.label>
                            <x-ui.input
                                type="number"
                                name="gesture_timeout"
                                id="gesture_timeout"
                                value="10"
                                min="5"
                                max="30"
                            />
                            <p class="mt-1 text-sm text-muted-foreground">Time limit for completing gestures</p>
                        </div>
                    </div>
                </x-ui.card>
                
                <!-- GPS & Location Settings -->
                <x-ui.card title="GPS & Location Settings" subtitle="Configure location verification">
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <x-ui.label for="gps_accuracy_threshold">GPS Accuracy Threshold (meters)</x-ui.label>
                            <x-ui.input
                                type="number"
                                name="gps_accuracy_threshold"
                                id="gps_accuracy_threshold"
                                value="50"
                                min="10"
                                max="500"
                            />
                            <p class="mt-1 text-sm text-muted-foreground">Maximum GPS accuracy required for check-in</p>
                        </div>
                        
                        <div>
                            <x-ui.label for="location_radius">Location Radius (meters)</x-ui.label>
                            <x-ui.input
                                type="number"
                                name="location_radius"
                                id="location_radius"
                                value="100"
                                min="25"
                                max="1000"
                            />
                            <p class="mt-1 text-sm text-muted-foreground">Allowed distance from office location</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-6">
                        <label class="flex items-center">
                            <x-ui.checkbox name="allow_remote_checkin" value="1" />
                            <span class="ml-2 text-sm text-foreground">Allow remote check-in</span>
                        </label>
                        
                        <label class="flex items-center">
                            <x-ui.checkbox name="log_gps_coordinates" value="1" checked />
                            <span class="ml-2 text-sm text-foreground">Log GPS coordinates</span>
                        </label>
                    </div>
                </x-ui.card>
                
                <!-- Notification Settings -->
                <x-ui.card title="Notification Settings" subtitle="Configure system notifications">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between py-3 border-b border-border">
                            <div>
                                <h4 class="text-sm font-medium text-foreground">Late Arrival Notifications</h4>
                                <p class="text-sm text-muted-foreground">Notify managers when employees are late</p>
                            </div>
                            <label class="flex items-center">
                                <x-ui.checkbox name="notify_late_arrivals" value="1" checked />
                            </label>
                        </div>
                        
                        <div class="flex items-center justify-between py-3 border-b border-border">
                            <div>
                                <h4 class="text-sm font-medium text-foreground">Missed Check-out Notifications</h4>
                                <p class="text-sm text-muted-foreground">Notify employees who forgot to check out</p>
                            </div>
                            <label class="flex items-center">
                                <x-ui.checkbox name="notify_missed_checkout" value="1" checked />
                            </label>
                        </div>
                        
                        <div class="flex items-center justify-between py-3 border-b border-border">
                            <div>
                                <h4 class="text-sm font-medium text-foreground">Weekly Reports</h4>
                                <p class="text-sm text-muted-foreground">Send weekly attendance summaries</p>
                            </div>
                            <label class="flex items-center">
                                <x-ui.checkbox name="send_weekly_reports" value="1" />
                            </label>
                        </div>
                        
                        <div class="flex items-center justify-between py-3">
                            <div>
                                <h4 class="text-sm font-medium text-foreground">Email Notifications</h4>
                                <p class="text-sm text-muted-foreground">Send notifications via email</p>
                            </div>
                            <label class="flex items-center">
                                <x-ui.checkbox name="email_notifications" value="1" checked />
                            </label>
                        </div>
                    </div>
                </x-ui.card>
                
                <!-- Data Retention Settings -->
                <x-ui.card title="Data Retention Settings" subtitle="Configure data cleanup policies">
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <x-ui.label for="attendance_retention_period">Attendance Data Retention</x-ui.label>
                            <x-ui.select name="attendance_retention_period" id="attendance_retention_period">
                                <option value="90">3 Months</option>
                                <option value="180">6 Months</option>
                                <option value="365" selected>1 Year</option>
                                <option value="730">2 Years</option>
                                <option value="0">Keep Forever</option>
                            </x-ui.select>
                            <p class="mt-1 text-sm text-muted-foreground">How long to keep attendance records</p>
                        </div>
                        
                        <div>
                            <x-ui.label for="face_data_retention_period">Face Data Retention</x-ui.label>
                            <x-ui.select name="face_data_retention_period" id="face_data_retention_period">
                                <option value="30">1 Month</option>
                                <option value="90" selected>3 Months</option>
                                <option value="180">6 Months</option>
                                <option value="365">1 Year</option>
                            </x-ui.select>
                            <p class="mt-1 text-sm text-muted-foreground">How long to keep face recognition data</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <x-ui.label for="log_retention_period">System Logs Retention</x-ui.label>
                            <x-ui.select name="log_retention_period" id="log_retention_period">
                                <option value="30">1 Month</option>
                                <option value="90" selected>3 Months</option>
                                <option value="180">6 Months</option>
                                <option value="365">1 Year</option>
                            </x-ui.select>
                            <p class="mt-1 text-sm text-muted-foreground">How long to keep system logs</p>
                        </div>
                        
                        <div>
                            <x-ui.label for="cleanup_time">Daily Cleanup Time</x-ui.label>
                            <x-ui.input
                                type="time"
                                name="cleanup_time"
                                id="cleanup_time"
                                value="02:00"
                            />
                            <p class="mt-1 text-sm text-muted-foreground">When to run daily cleanup tasks</p>
                        </div>
                    </div>
                </x-ui.card>
                
                <!-- Form Actions -->
                <div class="flex items-center justify-end space-x-3 pt-6 border-t border-border">
                    <x-ui.button
                        variant="outline"
                        href="{{ route('dashboard') }}"
                    >
                        Cancel
                    </x-ui.button>
                    
                    <x-ui.button
                        type="submit"
                        variant="primary"
                    >
                        <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Save Settings
                    </x-ui.button>
                </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    // Settings form enhancements
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-save draft settings to localStorage
        const form = document.querySelector('form');
        const inputs = form.querySelectorAll('input, select, textarea');
        
        // Load saved settings
        inputs.forEach(input => {
            const savedValue = localStorage.getItem(`setting_${input.name}`);
            if (savedValue && input.type !== 'checkbox') {
                input.value = savedValue;
            } else if (savedValue && input.type === 'checkbox') {
                input.checked = savedValue === 'true';
            }
        });
        
        // Save on change
        inputs.forEach(input => {
            input.addEventListener('change', function() {
                const value = input.type === 'checkbox' ? input.checked : input.value;
                localStorage.setItem(`setting_${input.name}`, value);
            });
        });
        
        // Clear saved settings on successful submit
        form.addEventListener('submit', function() {
            inputs.forEach(input => {
                localStorage.removeItem(`setting_${input.name}`);
            });
        });
    });
</script>
@endpush