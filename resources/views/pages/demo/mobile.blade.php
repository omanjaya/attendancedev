@extends('layouts.app')

@section('title', 'Mobile-First Responsive Design Demo')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 shadow-sm">
        <div class="container mx-auto px-4 py-6">
            <div class="text-center">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white mb-2">
                    Mobile-First Responsive Design
                </h1>
                <p class="text-gray-600 dark:text-gray-400 text-sm sm:text-base">
                    Optimized for mobile devices with touch-friendly interfaces and progressive enhancement
                </p>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-6 space-y-8">
        <!-- Responsive Cards Grid -->
        <section>
            <h2 class="text-lg sm:text-xl font-semibold text-gray-900 dark:text-white mb-4">
                Responsive Cards
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                <x-ui.mobile.responsive-card
                    title="Active Users"
                    value="1,247"
                    icon="fas fa-users"
                    trend="+12%"
                    trend-direction="up"
                    @can('view_employees')
                    href="{{ route('employees.index') }}"
                    @else
                    href="#"
                    @endcan>
                    <p class="text-xs text-gray-500">Compared to last month</p>
                </x-ui.mobile.responsive-card>

                <x-ui.mobile.responsive-card
                    title="Attendance Rate"
                    value="94.2%"
                    icon="fas fa-chart-line"
                    trend="+2.1%"
                    trend-direction="up">
                    <p class="text-xs text-gray-500">Above target of 90%</p>
                </x-ui.mobile.responsive-card>

                <x-ui.mobile.responsive-card
                    title="Pending Requests"
                    value="8"
                    icon="fas fa-clock"
                    trend="-3"
                    trend-direction="down">
                    <p class="text-xs text-gray-500">Needs review</p>
                </x-ui.mobile.responsive-card>

                <x-ui.mobile.responsive-card
                    title="Revenue"
                    value="$45,280"
                    icon="fas fa-dollar-sign"
                    trend="+8.4%"
                    trend-direction="up"
                    compact="true">
                    <p class="text-xs text-gray-500">This month</p>
                </x-ui.mobile.responsive-card>
            </div>
        </section>

        <!-- Responsive Table -->
        <section>
            <h2 class="text-lg sm:text-xl font-semibold text-gray-900 dark:text-white mb-4">
                Responsive Data Table
            </h2>
            
            @php
                $tableHeaders = [
                    ['label' => 'Name', 'field' => 'name', 'sortable' => true],
                    ['label' => 'Position', 'field' => 'position'],
                    ['label' => 'Department', 'field' => 'department'],
                    ['label' => 'Status', 'field' => 'status', 'component' => 'partials.status-badge'],
                    ['label' => 'Last Seen', 'field' => 'last_seen']
                ];
                
                $tableData = [
                    [
                        'id' => 1,
                        'name' => 'John Doe',
                        'position' => 'Senior Developer',
                        'department' => 'Engineering',
                        'status' => 'active',
                        'last_seen' => '2 hours ago'
                    ],
                    [
                        'id' => 2,
                        'name' => 'Jane Smith',
                        'position' => 'Project Manager',
                        'department' => 'Operations',
                        'status' => 'away',
                        'last_seen' => '1 day ago'
                    ],
                    [
                        'id' => 3,
                        'name' => 'Mike Johnson',
                        'position' => 'Designer',
                        'department' => 'Creative',
                        'status' => 'offline',
                        'last_seen' => '3 days ago'
                    ]
                ];
                
                $tableActions = function($row) {
                    return '
                        <x-ui.button size="sm" variant="outline" class="text-xs">Edit</x-ui.button>
                        <x-ui.button size="sm" variant="destructive" class="text-xs">Delete</x-ui.button>
                    ';
                };
            @endphp
            
            <x-ui.mobile.responsive-table
                :headers="$tableHeaders"
                :data="$tableData"
                :actions="$tableActions"
                :searchable="true"
                :sortable="true"
                empty-message="No employees found" />
        </section>

        <!-- Mobile Form -->
        <section>
            <h2 class="text-lg sm:text-xl font-semibold text-gray-900 dark:text-white mb-4">
                Mobile-Optimized Form
            </h2>
            
            <x-ui.mobile.responsive-form
                title="Employee Registration"
                subtitle="Create a new employee account"
                action="{{ route('demo.components') }}"
                method="POST"
                submit-text="Create Employee"
                cancel-url="{{ route('dashboard') }}">
                
                <x-ui.mobile.form-field
                    label="Full Name"
                    name="name"
                    type="text"
                    placeholder="Enter full name"
                    icon="fas fa-user"
                    required="true" />

                <x-ui.mobile.form-field
                    label="Email Address"
                    name="email"
                    type="email"
                    placeholder="Enter email address"
                    icon="fas fa-envelope"
                    help="This will be used for login and notifications"
                    required="true" />

                <x-ui.mobile.form-field
                    label="Phone Number"
                    name="phone"
                    type="tel"
                    placeholder="Enter phone number"
                    icon="fas fa-phone" />

                <x-ui.mobile.form-field
                    label="Department"
                    name="department"
                    type="select"
                    placeholder="Select department"
                    :options="[
                        'engineering' => 'Engineering',
                        'operations' => 'Operations',
                        'creative' => 'Creative',
                        'hr' => 'Human Resources',
                        'finance' => 'Finance'
                    ]"
                    required="true" />

                <x-ui.mobile.form-field
                    label="Employee Type"
                    name="type"
                    type="select"
                    :options="[
                        'permanent' => 'Permanent Staff',
                        'honorary' => 'Honorary Teacher',
                        'contract' => 'Contract Worker'
                    ]"
                    required="true" />

                <x-ui.mobile.form-field
                    label="Profile Picture"
                    name="avatar"
                    type="file"
                    accept="image/*"
                    help="Upload a profile picture (optional)" />

                <x-ui.mobile.form-field
                    label="Bio"
                    name="bio"
                    type="textarea"
                    placeholder="Tell us about yourself"
                    rows="4"
                    help="Brief description about the employee" />

                <x-ui.mobile.form-field
                    name="terms"
                    type="checkbox"
                    placeholder="I agree to the terms and conditions"
                    required="true" />
            </x-ui.mobile.responsive-form>
        </section>

        <!-- Mobile Features -->
        <section>
            <h2 class="text-lg sm:text-xl font-semibold text-gray-900 dark:text-white mb-4">
                Mobile-Specific Features
            </h2>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 sm:p-6 space-y-6">
                <!-- Touch Gestures -->
                <div>
                    <h3 class="text-base font-medium text-gray-900 dark:text-white mb-3">
                        Touch Gestures & Interactions
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <h4 class="font-medium text-gray-900 dark:text-white mb-2">Swipe Actions</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Swipe left on table rows for quick actions
                            </p>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <h4 class="font-medium text-gray-900 dark:text-white mb-2">Pull to Refresh</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Pull down on lists to refresh content
                            </p>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <h4 class="font-medium text-gray-900 dark:text-white mb-2">Haptic Feedback</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Vibration feedback for important actions
                            </p>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <h4 class="font-medium text-gray-900 dark:text-white mb-2">Large Touch Targets</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Minimum 44px touch targets for accessibility
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Responsive Breakpoints -->
                <div>
                    <h3 class="text-base font-medium text-gray-900 dark:text-white mb-3">
                        Responsive Breakpoints
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-gray-600">
                                    <th class="text-left py-2 font-medium text-gray-900 dark:text-white">Device</th>
                                    <th class="text-left py-2 font-medium text-gray-900 dark:text-white">Breakpoint</th>
                                    <th class="text-left py-2 font-medium text-gray-900 dark:text-white">Behavior</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-600 dark:text-gray-400">
                                <tr class="border-b border-gray-100 dark:border-gray-700">
                                    <td class="py-2">Mobile</td>
                                    <td class="py-2">&lt; 640px</td>
                                    <td class="py-2">Single column, bottom navigation</td>
                                </tr>
                                <tr class="border-b border-gray-100 dark:border-gray-700">
                                    <td class="py-2">Tablet</td>
                                    <td class="py-2">640px - 1024px</td>
                                    <td class="py-2">Two columns, enhanced spacing</td>
                                </tr>
                                <tr>
                                    <td class="py-2">Desktop</td>
                                    <td class="py-2">&gt; 1024px</td>
                                    <td class="py-2">Full layout, sidebar navigation</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Performance Features -->
                <div>
                    <h3 class="text-base font-medium text-gray-900 dark:text-white mb-3">
                        Performance Optimizations
                    </h3>
                    <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                        <li class="flex items-center">
                            <svg class="w-4 h-4 text-emerald-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            Lazy loading for images and components
                        </li>
                        <li class="flex items-center">
                            <svg class="w-4 h-4 text-emerald-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            Progressive Web App capabilities
                        </li>
                        <li class="flex items-center">
                            <svg class="w-4 h-4 text-emerald-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            Optimized bundle sizes with tree shaking
                        </li>
                        <li class="flex items-center">
                            <svg class="w-4 h-4 text-emerald-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            Offline capability with service workers
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- Device Testing -->
        <section>
            <h2 class="text-lg sm:text-xl font-semibold text-gray-900 dark:text-white mb-4">
                Device Testing
            </h2>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 sm:p-6">
                <div class="text-center space-y-4">
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        Current viewport: <span id="viewport-size" class="font-medium text-gray-900 dark:text-white"></span>
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        Device type: <span id="device-type" class="font-medium text-gray-900 dark:text-white"></span>
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        Touch support: <span id="touch-support" class="font-medium text-gray-900 dark:text-white"></span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Back to Dashboard -->
        <div class="text-center pt-8">
            <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors duration-200">
                ← Back to Dashboard
            </a>
        </div>
    </div>
</div>

<!-- Mobile Bottom Navigation -->
<x-ui.mobile.bottom-nav :current-route="request()->route()->getName()" />

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update viewport info
    function updateViewportInfo() {
        const width = window.innerWidth;
        const height = window.innerHeight;
        document.getElementById('viewport-size').textContent = `${width} × ${height}`;
        
        // Determine device type
        let deviceType = 'Desktop';
        if (width < 640) {
            deviceType = 'Mobile';
        } else if (width < 1024) {
            deviceType = 'Tablet';
        }
        document.getElementById('device-type').textContent = deviceType;
        
        // Check touch support
        const hasTouch = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
        document.getElementById('touch-support').textContent = hasTouch ? 'Yes' : 'No';
    }
    
    updateViewportInfo();
    window.addEventListener('resize', updateViewportInfo);
    
    // Demo notification
    setTimeout(() => {
        toast.success('Mobile-first design loaded successfully!', {
            title: 'Welcome',
            duration: 4000,
            progress: true
        });
    }, 1000);
});
</script>

<!-- Add status badge partial for table demo -->
@if(!View::exists('partials.status-badge'))
    <script>
    // Create a simple status badge renderer for the table demo
    window.renderStatusBadge = function(status) {
        const badges = {
            'active': '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>',
            'away': '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Away</span>',
            'offline': '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Offline</span>'
        };
        return badges[status] || badges.offline;
    };
    </script>
@endif
@endsection