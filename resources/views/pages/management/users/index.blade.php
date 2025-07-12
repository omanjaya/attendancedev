@extends('layouts.authenticated')

@section('title', 'User Management')

@section('page-content')
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-foreground">User Management</h1>
                <p class="text-muted-foreground mt-1">Manage system users, roles, and permissions</p>
            </div>
            
            <div class="flex items-center space-x-3">
                <x-ui.button variant="outline" x-data @click="$dispatch('open-modal', 'filter-modal')">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    Filter
                </x-ui.button>
                @can('manage_system')
                    <x-ui.button x-data @click="$dispatch('open-modal', 'create-user-modal')">
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Create User
                    </x-ui.button>
                @endcan
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        <!-- Total Users -->
        <x-attendance.stats-card
            title="Total Users"
            value="-"
            subtitle="All accounts"
            icon="ti ti-users"
            color="primary"
            href="{{ route('users.index') }}" />

        <!-- Active Users -->
        <x-attendance.stats-card
            title="Active Users"
            value="-"
            subtitle="Currently active"
            icon="ti ti-user-check"
            color="success"
            href="{{ route('users.index', ['filter' => 'active']) }}" />

        <!-- Users with Employees -->
        <x-attendance.stats-card
            title="With Employees"
            value="-"
            subtitle="Employee records"
            icon="ti ti-star"
            color="info"
            href="{{ route('users.index', ['filter' => 'with_employees']) }}" />

        <!-- Recent Users -->
        <x-attendance.stats-card
            title="Recent Users"
            value="-"
            subtitle="Last 30 days"
            icon="ti ti-user-plus"
            color="warning"
            href="{{ route('users.index', ['filter' => 'recent']) }}" />
    </div>

    <!-- Users Table -->
    <x-ui.card>
        <x-slot name="title">System Users</x-slot>
        <x-slot name="subtitle">Manage user accounts and permissions</x-slot>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-border" id="users-table">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Roles</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Employee</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Last Login</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-card divide-y divide-border">
                    <!-- Loading state -->
                    <tr id="loading-row">
                        <td colspan="7" class="text-center py-12">
                            <div class="flex flex-col items-center">
                                <svg class="animate-spin h-8 w-8 text-muted-foreground" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <p class="mt-2 text-muted-foreground">Loading users...</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <!-- Filter Modal -->
    <div x-data="{ open: false }" @open-modal.window="open = ($event.detail === 'filter-modal')" @close-modal.window="open = false" @keydown.escape.window="open = false">
        <div x-show="open" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
            <div class="flex items-center justify-center min-h-screen px-4 text-center">
                <div x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-background/80 backdrop-blur-sm" @click="open = false"></div>
                
                <div x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="relative bg-card rounded-xl shadow-xl w-full max-w-md p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-foreground">Filter Users</h3>
                        <button @click="open = false" class="text-muted-foreground hover:text-foreground transition-colors">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    
                    <form id="filterForm" class="space-y-4">
                        <div>
                            <x-ui.label for="role" value="Role" />
                            <x-ui.select name="role" id="role">
                                <option value="">All Roles</option>
                                <option value="admin">Admin</option>
                                <option value="manager">Manager</option>
                                <option value="employee">Employee</option>
                            </x-ui.select>
                        </div>
                        
                        <div>
                            <x-ui.label for="status" value="Status" />
                            <x-ui.select name="status" id="status">
                                <option value="">All Statuses</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </x-ui.select>
                        </div>
                        
                        <div class="flex space-x-3 pt-4">
                            <x-ui.button type="button" variant="outline" class="flex-1" @click="open = false">
                                Cancel
                            </x-ui.button>
                            <x-ui.button type="submit" class="flex-1">
                                Apply Filter
                            </x-ui.button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Create User Modal -->
    @can('manage_system')
    <div x-data="{ open: false }" @open-modal.window="open = ($event.detail === 'create-user-modal')" @close-modal.window="open = false" @keydown.escape.window="open = false">
        <div x-show="open" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
            <div class="flex items-center justify-center min-h-screen px-4 text-center">
                <div x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-background/80 backdrop-blur-sm" @click="open = false"></div>
                
                <div x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="relative bg-card rounded-xl shadow-xl w-full max-w-2xl p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-foreground">Create New User</h3>
                        <button @click="open = false" class="text-muted-foreground hover:text-foreground transition-colors">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    
                    <form id="createUserForm" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <x-ui.label for="name" value="Full Name" />
                                <x-ui.input type="text" name="name" id="name" required />
                            </div>
                            <div class="space-y-2">
                                <x-ui.label for="email" value="Email" />
                                <x-ui.input type="email" name="email" id="email" required />
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <x-ui.label for="password" value="Password" />
                                <x-ui.input type="password" name="password" id="password" required />
                            </div>
                            <div class="space-y-2">
                                <x-ui.label for="password_confirmation" value="Confirm Password" />
                                <x-ui.input type="password" name="password_confirmation" id="password_confirmation" required />
                            </div>
                        </div>
                        
                        <div>
                            <x-ui.label value="Roles" class="mb-3" />
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                @php
                                    $roles = [
                                        ['name' => 'admin', 'display' => 'Admin', 'description' => 'Full system access'],
                                        ['name' => 'manager', 'display' => 'Manager', 'description' => 'Manage employees and reports'],
                                        ['name' => 'employee', 'display' => 'Employee', 'description' => 'Basic user access'],
                                    ];
                                @endphp
                                @foreach($roles as $role)
                                <label class="flex items-center p-3 bg-muted/50 rounded-lg hover:bg-muted transition-colors cursor-pointer">
                                    <x-ui.checkbox name="roles[]" value="{{ $role['name'] }}" />
                                    <div class="ml-3">
                                        <div class="font-medium text-foreground">{{ $role['display'] }}</div>
                                        <div class="text-sm text-muted-foreground">{{ $role['description'] }}</div>
                                    </div>
                                </label>
                                @endforeach
                            </div>
                        </div>
                        
                        <div class="flex space-x-3 pt-4">
                            <x-ui.button type="button" variant="outline" class="flex-1" @click="open = false">
                                Cancel
                            </x-ui.button>
                            <x-ui.button type="submit" class="flex-1">
                                Create User
                            </x-ui.button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endcan
@endsection

@push('scripts')
<script>
    // Sample user data for demonstration
    const users = [
        {
            id: 1,
            name: 'Admin User',
            email: 'admin@school.edu',
            roles: ['Admin'],
            employee: 'Not Linked',
            status: 'active',
            lastLogin: '2 hours ago'
        },
        {
            id: 2,
            name: 'John Manager',
            email: 'john.manager@school.edu',
            roles: ['Manager'],
            employee: 'EMP001',
            status: 'active',
            lastLogin: '1 day ago'
        },
        {
            id: 3,
            name: 'Jane Employee',
            email: 'jane.employee@school.edu',
            roles: ['Employee'],
            employee: 'EMP002',
            status: 'active',
            lastLogin: '3 days ago'
        }
    ];

    // Load users when page loads
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(() => {
            loadUsers();
        }, 1000);
    });

    function loadUsers() {
        const tbody = document.querySelector('#users-table tbody');
        const loadingRow = document.getElementById('loading-row');
        
        // Remove loading state
        loadingRow.style.display = 'none';
        
        // Add user rows
        users.forEach(user => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-muted/50 transition-colors';
            tr.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary to-success flex items-center justify-center text-primary-foreground font-medium">
                            ${user.name.split(' ').map(n => n[0]).join('')}
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-foreground">${user.name}</div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground">${user.email}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    ${user.roles.map(role => `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary/10 text-primary">${role}</span>`).join(' ')}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground">${user.employee}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${user.status === 'active' ? 'bg-success/10 text-success' : 'bg-muted text-muted-foreground'}">
                        <div class="w-1.5 h-1.5 rounded-full ${user.status === 'active' ? 'bg-success' : 'bg-muted-foreground'} mr-1"></div>
                        ${user.status === 'active' ? 'Active' : 'Inactive'}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">${user.lastLogin}</td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <div class="flex items-center justify-end space-x-1">
                        <button class="inline-flex items-center justify-center h-8 w-8 rounded-md text-muted-foreground hover:text-foreground hover:bg-muted transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>
                        <button class="inline-flex items-center justify-center h-8 w-8 rounded-md text-muted-foreground hover:text-destructive hover:bg-destructive/10 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </td>
            `;
            tbody.appendChild(tr);
        });

        // Update stats
        document.querySelectorAll('[id^="stat-"]').forEach(el => {
            const value = el.id === 'stat-total' ? users.length : 
                          el.id === 'stat-active' ? users.filter(u => u.status === 'active').length :
                          el.id === 'stat-employees' ? users.filter(u => u.employee !== 'Not Linked').length :
                          el.id === 'stat-recent' ? 1 : 0;
            el.textContent = value;
        });
    }

    // Handle filter form
    document.getElementById('filterForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        // In a real app, this would filter the data
        console.log('Filtering users...');
    });

    // Handle create user form
    document.getElementById('createUserForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        // In a real app, this would create a new user
        console.log('Creating user...');
    });
</script>
@endpush