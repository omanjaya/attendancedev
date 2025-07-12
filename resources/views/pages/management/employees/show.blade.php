@extends('layouts.authenticated')

@section('title', 'Employee Profile - ' . $employee->full_name)

@section('page-content')
<x-layouts.page-base 
    title="Employee Profile"
    subtitle="View detailed information about {{ $employee->full_name }}"
    :show-background="true"
    :show-welcome="true"
    welcome-title="Employee Profile"
    welcome-subtitle="View detailed information about {{ $employee->full_name }}">

    <!-- Action Buttons -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <a href="{{ route('employees.index') }}" class="inline-flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
                <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Back to Employees
            </a>
        </div>
        <div class="flex gap-2">
            @can('edit_employees')
                <x-ui.button href="{{ route('employees.edit', $employee) }}">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit Employee
                </x-ui.button>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Basic Information -->
        <div class="lg:col-span-2">
            <x-layouts.glass-card>
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Employee Information</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Basic details and contact information</p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Employee ID</label>
                            <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg text-sm">
                                {{ $employee->employee_id }}
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Full Name</label>
                            <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg text-sm">
                                {{ $employee->full_name }}
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email</label>
                            <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg text-sm">
                                <a href="mailto:{{ $employee->user->email }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">
                                    {{ $employee->user->email }}
                                </a>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Phone</label>
                            <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg text-sm">
                                @if($employee->phone)
                                    <a href="tel:{{ $employee->phone }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">
                                        {{ $employee->phone }}
                                    </a>
                                @else
                                    <span class="text-gray-500">Not provided</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Employee Type</label>
                            <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg text-sm">
                                <x-ui.badge variant="{{ $employee->employee_type === 'permanent' ? 'success' : 'warning' }}">
                                    {{ ucfirst($employee->employee_type) }}
                                </x-ui.badge>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                            <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg text-sm">
                                <x-ui.badge variant="{{ $employee->is_active ? 'success' : 'secondary' }}">
                                    {{ $employee->is_active ? 'Active' : 'Inactive' }}
                                </x-ui.badge>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Department</label>
                            <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg text-sm">
                                {{ $employee->location->name ?? 'Not assigned' }}
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Hire Date</label>
                            <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg text-sm">
                                {{ $employee->hire_date ? $employee->hire_date->format('F j, Y') : 'Not specified' }}
                            </div>
                        </div>
                    </div>
                </div>
            </x-layouts.glass-card>
        </div>
        
        <!-- Profile Photo & Quick Actions -->
        <div class="lg:col-span-1">
            <x-layouts.glass-card>
                <div class="text-center">
                    <div class="mb-4">
                        <x-ui.avatar :name="$employee->full_name" size="xl" class="mx-auto" />
                    </div>
                    <h4 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $employee->full_name }}</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">{{ $employee->employee_id }}</p>
                    
                    <div class="mt-6 space-y-3">
                        @can('edit_employees')
                            <x-ui.button href="{{ route('employees.edit', $employee) }}" class="w-full">
                                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                Edit Employee
                            </x-ui.button>
                        @endcan
                        
                        <x-ui.button variant="outline" href="mailto:{{ $employee->user->email }}" class="w-full">
                            <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            Send Email
                        </x-ui.button>
                        
                        @if($employee->phone)
                            <x-ui.button variant="outline" href="tel:{{ $employee->phone }}" class="w-full">
                                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                                Call
                            </x-ui.button>
                        @endif
                    </div>
                </div>
            </x-layouts.glass-card>
        </div>
    </div>

</x-layouts.page-base>
@endsection