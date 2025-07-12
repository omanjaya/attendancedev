@extends('layouts.authenticated')

@section('title', 'Employee Management')

@section('page-content')
<x-layouts.page-base 
    title="Employee Management"
    subtitle="Manage your organization's staff directory"
    :show-background="true"
    :show-welcome="true"
    welcome-title="Employee Management"
    welcome-subtitle="Manage your organization's staff directory and employee information">

        @php
        // Fallback values if variables are not set
        $totalEmployees = $totalEmployees ?? 0;
        $activeToday = $activeToday ?? 0;
        $onLeave = $onLeave ?? 0;
        $pendingApprovals = $pendingApprovals ?? 0;
        
        $employeeStats = [
            [
                'title' => 'Total Employees',
                'value' => $totalEmployees,
                'change' => $totalEmployees > 0 ? 'Total registered' : 'No employees yet',
                'change_type' => 'neutral',
                'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/></svg>',
                'iconBg' => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-800 dark:text-emerald-300',
                'color' => 'primary'
            ],
            [
                'title' => 'Active Today',
                'value' => $activeToday,
                'change' => $totalEmployees > 0 ? round(($activeToday / $totalEmployees) * 100, 1) . '% present' : 'No data',
                'change_type' => 'neutral',
                'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                'iconBg' => 'bg-green-100 text-green-600 dark:bg-green-800 dark:text-green-300',
                'color' => 'success'
            ],
            [
                'title' => 'On Leave',
                'value' => $onLeave,
                'change' => $onLeave > 0 ? 'Currently on leave' : 'No leaves today',
                'change_type' => $onLeave > 0 ? 'negative' : 'neutral',
                'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>',
                'iconBg' => 'bg-yellow-100 text-yellow-600 dark:bg-yellow-800 dark:text-yellow-300',
                'color' => 'warning'
            ],
            [
                'title' => 'Pending Approvals',
                'value' => $pendingApprovals,
                'change' => $pendingApprovals > 0 ? 'Require approval' : 'All cleared',
                'change_type' => $pendingApprovals > 0 ? 'positive' : 'neutral',
                'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                'iconBg' => 'bg-blue-100 text-blue-600 dark:bg-blue-800 dark:text-blue-300',
                'color' => 'info'
            ]
        ];
        @endphp

        <!-- Compact Stats Grid -->
        <x-layouts.glass-card class="mb-4">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($employeeStats as $stat)
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

        <!-- Employee Directory -->
        <x-layouts.glass-card>
            <!-- Header with Action Buttons -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div class="flex items-center space-x-4">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Employee Directory</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-300">Manage your organization's staff</p>
                    </div>
                </div>
                
                <!-- Quick Action Buttons -->
                <div class="flex flex-wrap gap-2">
                    @can('create_employees')
                        <button onclick="location.href='{{ route('employees.create') }}'" 
                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors duration-200 shadow-sm">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Add Employee
                        </button>
                    @endcan
                    
                    @can('create_employees')
                        <button onclick="openUploadModal()" 
                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 rounded-lg transition-colors duration-200 shadow-sm">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                            </svg>
                            Upload Template
                        </button>
                    @endcan
                    
                    @can('edit_employees')
                        <button onclick="toggleBulkEdit()" id="bulk-edit-btn"
                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 rounded-lg transition-colors duration-200 shadow-sm">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            <span id="bulk-edit-text">Bulk Update</span>
                        </button>
                    @endcan
                </div>
            </div>

            <!-- Search and Filters -->
            <div class="flex flex-col sm:flex-row gap-4 mb-6">
                <div class="flex-1">
                    <input type="text" 
                           placeholder="Search employees by name, email, or department..."
                           class="w-full px-4 py-2 border border-white/20 dark:border-gray-600/30 rounded-xl bg-white/20 dark:bg-gray-700/30 backdrop-blur-sm text-gray-800 dark:text-gray-100 placeholder-gray-600 dark:placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500/50 transition-all duration-300" />
                </div>
                <div class="flex flex-col sm:flex-row gap-3">
                    <select class="px-3 py-2 border border-white/20 dark:border-gray-600/30 rounded-xl bg-white/20 dark:bg-gray-700/30 backdrop-blur-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-500/50">
                        <option value="">All Departments</option>
                        <option value="teaching">Teaching Staff</option>
                        <option value="administrative">Administrative</option>
                        <option value="support">Support Staff</option>
                    </select>
                    <select class="px-3 py-2 border border-white/20 dark:border-gray-600/30 rounded-xl bg-white/20 dark:bg-gray-700/30 backdrop-blur-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-500/50">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="leave">On Leave</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    <select class="px-3 py-2 border border-white/20 dark:border-gray-600/30 rounded-xl bg-white/20 dark:bg-gray-700/30 backdrop-blur-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-500/50">
                        <option value="">All Types</option>
                        <option value="permanent">Permanent</option>
                        <option value="honorary">Honorary</option>
                        <option value="contract">Contract</option>
                    </select>
                </div>
            </div>
        
        <!-- Bulk Edit Bar (Hidden by default) -->
        <div id="bulk-edit-bar" class="hidden mb-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <span class="text-sm font-medium text-blue-900 dark:text-blue-100">
                        <span id="selected-count">0</span> employees selected
                    </span>
                    <x-ui.button variant="outline" size="sm" onclick="selectAll()">Select All</x-ui.button>
                    <x-ui.button variant="outline" size="sm" onclick="clearSelection()">Clear Selection</x-ui.button>
                </div>
                <div class="flex items-center gap-2">
                    @can('delete_employees')
                    <x-ui.button variant="outline" size="sm" onclick="bulkDelete()">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Delete Selected
                    </x-ui.button>
                    @endcan
                    @can('edit_employees')
                    <x-ui.button size="sm" onclick="bulkEdit()">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit Selected
                    </x-ui.button>
                    @endcan
                </div>
            </div>
        </div>

        <!-- Employee Table -->
        <x-layouts.glass-card>
            <div class="overflow-x-auto">
                <table class="w-full caption-bottom text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <div class="flex items-center">
                                <input type="checkbox" id="select-all-checkbox" class="bulk-edit-checkbox hidden mr-3 rounded border-gray-300 text-blue-600 focus:ring-blue-500" onchange="toggleSelectAll(this)">
                                <span>Employee</span>
                            </div>
                        </th>
                        <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Type</th>
                        <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Department</th>
                        <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Last Check-in</th>
                        <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Face Registration</th>
                        <th class="text-right px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($employees ?? [] as $employee)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <input type="checkbox" class="bulk-edit-checkbox employee-checkbox hidden mr-3 rounded border-gray-300 text-blue-600 focus:ring-blue-500" 
                                           value="{{ $employee['id'] }}" onchange="updateSelectedCount()">
                                    <x-ui.avatar 
                                        :name="$employee['name'] ?? 'John Doe'"
                                        size="md" />
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $employee['name'] ?? 'John Doe' }}
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $employee['email'] ?? 'john.doe@school.edu' }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-ui.badge variant="{{ ($employee['type'] ?? 'permanent') === 'permanent' ? 'info' : 'warning' }}">
                                    {{ ucfirst($employee['type'] ?? 'Permanent') }}
                                </x-ui.badge>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-gray-100">{{ $employee['department'] ?? 'Mathematics' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-ui.badge variant="{{ ($employee['status'] ?? 'active') === 'active' ? 'success' : 'secondary' }}">
                                    {{ ucfirst($employee['status'] ?? 'Active') }}
                                </x-ui.badge>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $employee['last_check_in'] ?? 'Today 08:30 AM' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-ui.badge variant="{{ ($employee['face_registered'] ?? true) ? 'success' : 'destructive' }}">
                                    {{ ($employee['face_registered'] ?? true) ? 'Registered' : 'Pending' }}
                                </x-ui.badge>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-1">
                                    <!-- View Button -->
                                    @can('view_employees')
                                    <button type="button" 
                                            onclick="viewEmployee({{ $employee['id'] ?? 1 }})" 
                                            class="inline-flex items-center justify-center w-8 h-8 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors duration-200"
                                            title="View Employee">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </button>
                                    @endcan
                                    
                                    <!-- Edit Button -->
                                    @can('edit_employees')
                                    <button type="button" 
                                            onclick="editEmployee({{ $employee['id'] ?? 1 }})" 
                                            class="inline-flex items-center justify-center w-8 h-8 text-gray-600 hover:text-green-600 hover:bg-green-50 rounded-lg transition-colors duration-200"
                                            title="Edit Employee">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    @endcan
                                    
                                    <!-- Delete Button -->
                                    @can('delete_employees')
                                    <button type="button" 
                                            onclick="confirmDelete({{ $employee['id'] ?? 1 }})" 
                                            class="inline-flex items-center justify-center w-8 h-8 text-gray-600 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors duration-200"
                                            title="Delete Employee">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="text-gray-500 dark:text-gray-400">
                                    <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                                    </svg>
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">No employees found</h3>
                                    <p class="text-gray-500 dark:text-gray-400 mb-4">No employees match your current filters. Try adjusting your search criteria.</p>
                                    <a href="{{ route('employees.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                        Add First Employee
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </x-layouts.glass-card>
        
        <!-- Pagination (removed for static demo) -->
        <div class="mt-6">
            <div class="flex items-center justify-between px-2">
                <div class="text-sm text-muted-foreground">
                    Showing 1 to 10 of 24 results
                </div>
                <nav role="navigation" aria-label="Pagination" class="flex items-center gap-1">
                    <span class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 w-9 opacity-50 cursor-not-allowed">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        <span class="sr-only">Previous page</span>
                    </span>
                    <span class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-9 w-9">1</span>
                    <a href="#" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 w-9">2</a>
                    <a href="#" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 w-9">3</a>
                    <a href="#" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 w-9">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                        <span class="sr-only">Next page</span>
                    </a>
                </nav>
            </div>
            </div>
        </x-layouts.glass-card>
    </div>

</x-layouts.page-base>

<!-- Upload Template Modal -->
<div id="upload-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-black bg-opacity-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="relative w-full max-w-md p-6 bg-white dark:bg-gray-800 rounded-xl shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Upload Employee Template</h3>
                <button onclick="closeUploadModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <form id="upload-form" enctype="multipart/form-data">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Choose Excel File
                    </label>
                    <input type="file" id="excel-file" name="excel_file" accept=".xlsx,.xls" 
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-100">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Accepted formats: .xlsx, .xls
                    </p>
                </div>
                
                <div class="mb-4">
                    <a href="{{ route('employees.download-template') }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Download Template
                    </a>
                </div>
                
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="closeUploadModal()" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 rounded-lg">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg">
                        Upload
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bulk Edit Modal -->
<div id="bulk-edit-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-black bg-opacity-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="relative w-full max-w-lg p-6 bg-white dark:bg-gray-800 rounded-xl shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Bulk Edit Employees</h3>
                <button onclick="closeBulkEditModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <form id="bulk-edit-form">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Employee Type
                        </label>
                        <select name="employee_type" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-100">
                            <option value="">No Change</option>
                            <option value="permanent">Permanent</option>
                            <option value="honorary">Honorary</option>
                            <option value="staff">Staff</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Status
                        </label>
                        <select name="is_active" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-100">
                            <option value="">No Change</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Department
                        </label>
                        <select name="location_id" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-100">
                            <option value="">No Change</option>
                            <option value="1">Mathematics</option>
                            <option value="2">Science</option>
                            <option value="3">English</option>
                            <option value="4">Administration</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-2 mt-6">
                    <button type="button" onclick="closeBulkEditModal()" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 rounded-lg">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg">
                        Update Selected
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    let bulkEditMode = false;
    let selectedEmployees = [];
    
    // Initialize when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Employee page loaded');
        
        // Search functionality
        const searchInput = document.querySelector('input[placeholder*="Search employees"]');
        if (searchInput) {
            searchInput.addEventListener('input', function(e) {
                const searchTerm = e.target.value.toLowerCase();
                const rows = document.querySelectorAll('tbody tr');
                
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                });
            });
        }
    });

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

    // Toggle bulk edit mode
    function toggleBulkEdit() {
        bulkEditMode = !bulkEditMode;
        const checkboxes = document.querySelectorAll('.bulk-edit-checkbox');
        const bulkEditBar = document.getElementById('bulk-edit-bar');
        const bulkEditText = document.getElementById('bulk-edit-text');
        
        if (bulkEditMode) {
            checkboxes.forEach(checkbox => checkbox.classList.remove('hidden'));
            bulkEditBar.classList.remove('hidden');
            bulkEditText.textContent = 'Cancel Selection';
        } else {
            checkboxes.forEach(checkbox => {
                checkbox.classList.add('hidden');
                checkbox.checked = false;
            });
            bulkEditBar.classList.add('hidden');
            bulkEditText.textContent = 'Bulk Update';
            selectedEmployees = [];
            updateSelectedCount();
        }
    }

    // Update selected count
    function updateSelectedCount() {
        const checkboxes = document.querySelectorAll('.employee-checkbox:checked');
        const count = checkboxes.length;
        document.getElementById('selected-count').textContent = count;
        selectedEmployees = Array.from(checkboxes).map(cb => cb.value);
    }

    // Select all employees
    function selectAll() {
        const checkboxes = document.querySelectorAll('.employee-checkbox');
        checkboxes.forEach(checkbox => checkbox.checked = true);
        updateSelectedCount();
    }

    // Clear selection
    function clearSelection() {
        const checkboxes = document.querySelectorAll('.employee-checkbox');
        checkboxes.forEach(checkbox => checkbox.checked = false);
        document.getElementById('select-all-checkbox').checked = false;
        updateSelectedCount();
    }

    // Toggle select all
    function toggleSelectAll(selectAllCheckbox) {
        const checkboxes = document.querySelectorAll('.employee-checkbox');
        checkboxes.forEach(checkbox => checkbox.checked = selectAllCheckbox.checked);
        updateSelectedCount();
    }

    // Employee actions
    function viewEmployee(employeeId) {
        console.log('View employee clicked:', employeeId);
        window.location.href = '/employees/' + employeeId;
    }

    function editEmployee(employeeId) {
        console.log('Edit employee clicked:', employeeId);
        window.location.href = '/employees/' + employeeId + '/edit';
    }

    function confirmDelete(employeeId) {
        console.log('Delete employee clicked:', employeeId);
        if (confirm('Are you sure you want to delete this employee? This action cannot be undone.')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/employees/' + employeeId;
            
            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'DELETE';
            
            const tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = '_token';
            tokenInput.value = '{{ csrf_token() }}';
            
            form.appendChild(methodInput);
            form.appendChild(tokenInput);
            document.body.appendChild(form);
            form.submit();
        }
    }

    // Upload Template Modal
    function openUploadModal() {
        document.getElementById('upload-modal').classList.remove('hidden');
    }

    function closeUploadModal() {
        document.getElementById('upload-modal').classList.add('hidden');
        document.getElementById('upload-form').reset();
    }

    // Bulk Edit Modal
    function bulkEdit() {
        if (selectedEmployees.length === 0) {
            showNotification('Please select at least one employee', 'warning');
            return;
        }
        document.getElementById('bulk-edit-modal').classList.remove('hidden');
    }

    function closeBulkEditModal() {
        document.getElementById('bulk-edit-modal').classList.add('hidden');
        document.getElementById('bulk-edit-form').reset();
    }

    // Bulk Delete
    function bulkDelete() {
        if (selectedEmployees.length === 0) {
            showNotification('Please select at least one employee', 'warning');
            return;
        }
        
        if (confirm('Are you sure you want to delete ' + selectedEmployees.length + ' employee(s)? This action cannot be undone.')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/employees/bulk-delete';
            
            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'DELETE';
            
            const tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = '_token';
            tokenInput.value = '{{ csrf_token() }}';
            
            const idsInput = document.createElement('input');
            idsInput.type = 'hidden';
            idsInput.name = 'employee_ids';
            idsInput.value = selectedEmployees.join(',');
            
            form.appendChild(methodInput);
            form.appendChild(tokenInput);
            form.appendChild(idsInput);
            document.body.appendChild(form);
            form.submit();
        }
    }

    // Handle upload form submission
    document.getElementById('upload-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const fileInput = document.getElementById('excel-file');
        
        if (!fileInput.files.length) {
            showNotification('Please select a file', 'error');
            return;
        }
        
        // Show loading
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Uploading...';
        submitBtn.disabled = true;
        
        fetch('/employees/upload', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message || 'Employees uploaded successfully', 'success');
                closeUploadModal();
                window.location.reload();
            } else {
                showNotification(data.message || 'Upload failed', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred during upload', 'error');
        })
        .finally(() => {
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        });
    });

    // Handle bulk edit form submission
    document.getElementById('bulk-edit-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (selectedEmployees.length === 0) {
            showNotification('Please select at least one employee', 'warning');
            return;
        }
        
        const formData = new FormData(this);
        formData.append('employee_ids', selectedEmployees.join(','));
        
        // Show loading
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Updating...';
        submitBtn.disabled = true;
        
        fetch('/employees/bulk-edit', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message || 'Employees updated successfully', 'success');
                closeBulkEditModal();
                window.location.reload();
            } else {
                showNotification(data.message || 'Update failed', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred during update', 'error');
        })
        .finally(() => {
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        });
    });

    // Close modals when clicking outside
    document.addEventListener('click', function(e) {
        if (e.target.id === 'upload-modal') {
            closeUploadModal();
        }
        if (e.target.id === 'bulk-edit-modal') {
            closeBulkEditModal();
        }
    });
</script>
@endpush