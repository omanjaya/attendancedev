@extends('layouts.authenticated')

@section('title', 'Location Details - ' . $location->name)

@section('page-header')
@section('page-pretitle', 'Location')
@section('page-title', $location->name)
@section('page-actions')
    @can('manage_system')
    <a href="{{ route('locations.edit', $location) }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
        <svg class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
            <path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1"/>
            <path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z"/>
            <path d="M16 5l3 3"/>
        </svg>
        Edit Location
    </a>
    @endcan
@endsection
@endsection

@section('page-content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Location Information -->
    <div class="lg:col-span-8">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200-header">
                <h3 class="bg-white rounded-lg shadow-sm border border-gray-200-title">Location Information</h3>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200-body">
                <div class="grid grid-cols-12 gap-4">
                    <div class="md:col-span-6">
                        <div class="mb-3">
                            <label class="block text-sm font-medium text-gray-700">Location Name</label>
                            <div class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm-plaintext">{{ $location->name }}</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="block text-sm font-medium text-gray-700">Address</label>
                            <div class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm-plaintext">
                                @if($location->address)
                                    {{ $location->address }}
                                @else
                                    <span class="text-gray-600">Not provided</span>
                                @endif
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <div class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm-plaintext">
                                @if($location->is_active)
                                    <span class="badge bg-green">Active</span>
                                @else
                                    <span class="badge bg-red">Inactive</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <div class="md:col-span-6">
                        <div class="mb-3">
                            <label class="block text-sm font-medium text-gray-700">GPS Coordinates</label>
                            <div class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm-plaintext">
                                @if($location->latitude && $location->longitude)
                                    {{ number_format($location->latitude, 6) }}, {{ number_format($location->longitude, 6) }}
                                    <a href="https://www.google.com/maps?q={{ $location->latitude }},{{ $location->longitude }}" 
                                       target="_blank" class="btn px-3 py-1.5 text-xs btn-outline-primary ml-2">
                                        <svg class="icon icon-sm" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <circle cx="12" cy="11" r="3"/>
                                            <path d="M17.657 16.657l-4.243 4.243a2 2 0 0 1 -2.827 0l-4.244 -4.243a8 8 0 1 1 11.314 0z"/>
                                        </svg>
                                        View on Map
                                    </a>
                                @else
                                    <span class="text-gray-600">Not set</span>
                                @endif
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="block text-sm font-medium text-gray-700">Allowed Radius</label>
                            <div class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm-plaintext">{{ $location->radius_meters }} meters</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="block text-sm font-medium text-gray-700">WiFi Network</label>
                            <div class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm-plaintext">
                                @if($location->wifi_ssid)
                                    <code>{{ $location->wifi_ssid }}</code>
                                @else
                                    <span class="text-gray-600">Not configured</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Assigned Employees -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mt-4">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200-header">
                <h3 class="bg-white rounded-lg shadow-sm border border-gray-200-title">Assigned Employees ({{ $location->employees->count() }})</h3>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200-body">
                @if($location->employees->count() > 0)
                    <div class="min-w-full divide-y divide-gray-200-responsive">
                        <min-w-full divide-y divide-gray-200 class="min-w-full divide-y divide-gray-200 min-w-full divide-y divide-gray-200-vcenter">
                            <thead>
                                <tr>
                                    <th>Employee ID</th>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($location->employees as $employee)
                                <tr>
                                    <td>{{ $employee->employee_id }}</td>
                                    <td>
                                        <a href="{{ route('employees.show', $employee) }}">
                                            {{ $employee->full_name }}
                                        </a>
                                    </td>
                                    <td>
                                        @php
                                            $colors = [
                                                'permanent' => 'green',
                                                'honorary' => 'blue',
                                                'staff' => 'yellow'
                                            ];
                                            $color = $colors[$employee->employee_type] ?? 'secondary';
                                        @endphp
                                        <span class="badge bg-{{ $color }}-lt">{{ ucfirst($employee->employee_type) }}</span>
                                    </td>
                                    <td>
                                        @foreach($employee->user->roles as $role)
                                            <span class="badge bg-blue-lt">{{ ucfirst($role->name) }}</span>
                                        @endforeach
                                    </td>
                                    <td>
                                        @if($employee->is_active)
                                            <span class="badge bg-green">Active</span>
                                        @else
                                            <span class="badge bg-red">Inactive</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </min-w-full divide-y divide-gray-200>
                    </div>
                @else
                    <div class="text-center py-4 text-gray-600">
                        <svg class="icon mb-2" width="48" height="48" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                            <path d="M21 21v-2a4 4 0 0 0 -3 -3.85"/>
                        </svg>
                        <div>No employees assigned</div>
                        <small>Employees can be assigned to this location in their profile settings</small>
                    </div>
                @endif
            </div>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="lg:col-span-4">
        <!-- Verification Methods -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200-header">
                <h3 class="bg-white rounded-lg shadow-sm border border-gray-200-title">Verification Methods</h3>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200-body">
                @php
                    $hasGPS = $location->latitude && $location->longitude;
                    $hasWiFi = $location->wifi_ssid;
                @endphp
                
                @if($hasGPS)
                <div class="flex items-center mb-3">
                    <span class="badge bg-blue mr-2">GPS</span>
                    <div>
                        <div class="truncate">
                            <strong>Location-based verification</strong>
                        </div>
                        <div class="text-gray-600 small">
                            Within {{ $location->radius_meters }}m radius
                        </div>
                    </div>
                </div>
                @endif
                
                @if($hasWiFi)
                <div class="flex items-center mb-3">
                    <span class="badge bg-green mr-2">WiFi</span>
                    <div>
                        <div class="truncate">
                            <strong>Network-based verification</strong>
                        </div>
                        <div class="text-gray-600 small">
                            Connected to "{{ $location->wifi_ssid }}"
                        </div>
                    </div>
                </div>
                @endif
                
                @if(!$hasGPS && !$hasWiFi)
                <div class="text-center py-4 text-gray-600">
                    <svg class="icon mb-2" width="48" height="48" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <circle cx="12" cy="12" r="9"/>
                        <line x1="9" y1="9" x2="15" y2="15"/>
                        <line x1="15" y1="9" x2="9" y2="15"/>
                    </svg>
                    <div>No verification methods</div>
                    <small>Configure GPS or WiFi verification</small>
                </div>
                @endif
            </div>
        </div>
        
        <!-- Quick Stats -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mt-4">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200-header">
                <h3 class="bg-white rounded-lg shadow-sm border border-gray-200-title">Quick Stats</h3>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200-body">
                <div class="grid grid-cols-12 gap-4">
                    <div class="w-1/2 px-2">
                        <div class="subheader">Total Employees</div>
                        <div class="h1 m-0">{{ $location->employees->count() }}</div>
                    </div>
                    <div class="w-1/2 px-2">
                        <div class="subheader">Active</div>
                        <div class="h1 m-0">{{ $location->employees->where('is_active', true)->count() }}</div>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="subheader">Verification Methods</div>
                    <div class="h2 m-0 text-blue">{{ ($hasGPS ? 1 : 0) + ($hasWiFi ? 1 : 0) }}</div>
                    <div class="progress progress-sm mt-2">
                        @php $completeness = (($hasGPS ? 50 : 0) + ($hasWiFi ? 50 : 0)); @endphp
                        <div class="progress-bar bg-blue" style="width: {{ $completeness }}%" role="progressbar" aria-valuenow="{{ $completeness }}"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Location Details -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mt-4">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200-header">
                <h3 class="bg-white rounded-lg shadow-sm border border-gray-200-title">Location Details</h3>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200-body">
                <div class="mb-2">
                    <strong>Created:</strong> {{ $location->created_at->format('M j, Y') }}
                </div>
                <div class="mb-2">
                    <strong>Last Updated:</strong> {{ $location->updated_at->format('M j, Y g:i A') }}
                </div>
                @if($location->metadata)
                <div class="mb-2">
                    <strong>Additional Data:</strong> Available
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection