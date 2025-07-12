@extends('layouts.authenticated')

@section('title', 'Employee Management')

@section('page-content')
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-foreground">Employee Management</h1>
                <p class="text-muted-foreground mt-1">Manage your organization's staff directory</p>
            </div>
            
            @can('create_employees')
                <x-ui.button href="{{ route('employees.create') }}">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Add Employee
                </x-ui.button>
            @endcan
        </div>
    </div>

    <div class="space-y-8">
            
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <!-- Total Employees -->
            <x-attendance.stats-card
                title="Total Employees"
                :value="$stats['total'] ?? 24"
                subtitle="registered staff"
                icon="ti ti-users"
                color="primary"
                href="{{ route('employees.index') }}" />

            <!-- Active Employees -->
            <x-attendance.stats-card
                title="Active Today"
                :value="$stats['active'] ?? 18"
                subtitle="currently present"
                icon="ti ti-user-check"
                color="success"
                href="{{ route('attendance.index') }}" />

            <!-- Permanent Staff -->
            <x-attendance.stats-card
                title="Permanent Staff"
                :value="$stats['permanent'] ?? 16"
                subtitle="full-time employees"
                icon="ti ti-briefcase"
                color="info"
                href="{{ route('employees.index', ['filter' => 'permanent']) }}" />

            <!-- Honorary Teachers -->
            <x-attendance.stats-card
                title="Honorary Teachers"
                :value="$stats['honorary'] ?? 8"
                subtitle="part-time staff"
                icon="ti ti-school"
                color="warning"
                href="{{ route('employees.index', ['filter' => 'honorary']) }}" />
        </div>

        <!-- Employee Table -->
        <x-ui.card>
            <x-slot name="title">
                <div class="flex items-center">
                    Employee Directory
                    <x-ui.badge variant="secondary" class="ml-2">
                        {{ $stats['total'] ?? 24 }} total
                    </x-ui.badge>
                </div>
            </x-slot>
            
            <x-slot name="subtitle">
                All registered staff members
            </x-slot>
            
            <x-slot name="actions">
                <div class="flex items-center space-x-3">
                    <!-- Search Input -->
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <x-ui.input 
                            id="employee-search"
                            placeholder="Search employees..."
                            class="pl-10 w-64" />
                    </div>
                    
                    <!-- Filter Dropdown -->
                    <x-ui.dropdown>
                        <x-slot name="trigger">
                            <x-ui.button variant="outline" size="sm">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                                </svg>
                                Filter
                            </x-ui.button>
                        </x-slot>
                        
                        <x-ui.dropdown-item href="{{ route('employees.index') }}">All Employees</x-ui.dropdown-item>
                        <x-ui.dropdown-item href="{{ route('employees.index', ['filter' => 'permanent']) }}">Permanent Staff</x-ui.dropdown-item>
                        <x-ui.dropdown-item href="{{ route('employees.index', ['filter' => 'honorary']) }}">Honorary Teachers</x-ui.dropdown-item>
                        <x-ui.dropdown-item href="{{ route('employees.index', ['filter' => 'active']) }}">Active Only</x-ui.dropdown-item>
                    </x-ui.dropdown>
                </div>
            </x-slot>

            <!-- Table Content -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-border">
                    <thead class="bg-muted/50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                Employee
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                Type
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                Status
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                Last Check-in
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                Face Registration
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-card divide-y divide-border">
                            @forelse($employees ?? [] as $employee)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <x-ui.avatar 
                                            :name="$employee['name'] ?? 'John Doe'"
                                            size="md" />
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-foreground">
                                                {{ $employee['name'] ?? 'John Doe' }}
                                            </div>
                                            <div class="text-sm text-muted-foreground">
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
                                    <x-ui.badge variant="{{ ($employee['status'] ?? 'active') === 'active' ? 'success' : 'destructive' }}">
                                        <div class="w-1.5 h-1.5 rounded-full {{ ($employee['status'] ?? 'active') === 'active' ? 'bg-success' : 'bg-destructive' }} mr-1"></div>
                                        {{ ucfirst($employee['status'] ?? 'Active') }}
                                    </x-ui.badge>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground">
                                    {{ $employee['last_checkin'] ?? 'Today 09:15 AM' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <x-ui.badge variant="success">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        Registered
                                    </x-ui.badge>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end space-x-1">
                                        <x-ui.button variant="ghost" size="icon" href="{{ route('employees.show', $employee['id'] ?? 1) }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </x-ui.button>
                                        @can('edit_employees')
                                        <x-ui.button variant="ghost" size="icon" href="{{ route('employees.edit', $employee['id'] ?? 1) }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </x-ui.button>
                                        @endcan
                                        @can('delete_employees')
                                        <x-ui.button variant="ghost" size="icon" onclick="confirmDelete({{ $employee['id'] ?? 1 }})">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </x-ui.button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <!-- Sample Data Row -->
                            <tr class="hover:bg-muted/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <x-ui.avatar name="John Doe" size="md" />
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-foreground">John Doe</div>
                                            <div class="text-sm text-muted-foreground">john.doe@school.edu</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <x-ui.badge variant="info">
                                        Permanent
                                    </x-ui.badge>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <x-ui.badge variant="success">
                                        <div class="w-1.5 h-1.5 rounded-full bg-success mr-1"></div>
                                        Active
                                    </x-ui.badge>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground">
                                    Today 09:15 AM
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <x-ui.badge variant="success">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        Registered
                                    </x-ui.badge>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end space-x-1">
                                        <x-ui.button variant="ghost" size="icon">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </x-ui.button>
                                        <x-ui.button variant="ghost" size="icon">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </x-ui.button>
                                        <x-ui.button variant="ghost" size="icon" onclick="confirmDelete(1)">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </x-ui.button>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Showing <span class="font-medium">1</span> to <span class="font-medium">10</span> of <span class="font-medium">24</span> results
                        </div>
                        <div class="flex space-x-2">
                            <button class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                                Previous
                            </button>
                            <button class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600">
                                Next
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Search functionality
    document.getElementById('employee-search').addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });

    // Delete confirmation
    function confirmDelete(employeeId) {
        if (confirm('Are you sure you want to delete this employee? This action cannot be undone.')) {
            // Create form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/employees/${employeeId}`;
            form.innerHTML = `
                <input type="hidden" name="_method" value="DELETE">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
</script>
@endpush