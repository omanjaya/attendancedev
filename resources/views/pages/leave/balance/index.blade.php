@extends('layouts.authenticated')

@section('title', 'Leave Balance Dashboard')

@section('page-header')
@section('page-pretitle', 'Leave Management')
@section('page-title', 'My Leave Balance')
@section('page-actions')
    <div class="flex items-center space-x-2">
        <a href="{{ route('leave.calendar') }}" class="inline-flex items-center px-4 py-2 border border-blue-300 text-sm font-medium rounded-md text-blue-700 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <svg class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                <path d="M4 5m0 2a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2z"/>
                <path d="M16 3l0 4"/>
                <path d="M8 3l0 4"/>
                <path d="M4 11l16 0"/>
            </svg>
            Calendar View
        </a>
        <a href="{{ route('leave.requests') }}" class="btn btn-outline-info">
            <svg class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                <path d="M3 3m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z"/>
                <path d="M17 7l-10 10"/>
                <path d="M8 7l9 0"/>
            </svg>
            My Requests
        </a>
        <a href="{{ route('leave.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <svg class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                <path d="M12 5l0 14"/>
                <path d="M5 12l14 0"/>
            </svg>
            Request Leave
        </a>
    </div>
@endsection
@endsection

@section('page-content')
<div class="grid grid-cols-12 gap-4">
    <!-- Summary Cards -->
    <div class="sm:w-1/2 px-2 lg:w-1/4 px-2">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200-body">
                <div class="flex items-center">
                    <div class="subheader">Total Allocated</div>
                    <div class="ml-auto">
                        <span class="text-green inline-flex items-center lh-1">
                            <svg class="icon icon-sm" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M12 6v6l3 3"/>
                                <circle cx="12" cy="12" r="9"/>
                            </svg>
                        </span>
                    </div>
                </div>
                <div class="h1 mb-3">{{ $totalAllocated }} days</div>
                <div class="flex mb-2">
                    <div class="progress progress-sm flex-1">
                        <div class="progress-bar bg-blue" style="width: 100%"></div>
                    </div>
                </div>
                <div class="text-gray-600">Annual entitlement</div>
            </div>
        </div>
    </div>
    
    <div class="sm:w-1/2 px-2 lg:w-1/4 px-2">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200-body">
                <div class="flex items-center">
                    <div class="subheader">Days Used</div>
                    <div class="ml-auto">
                        <span class="text-red inline-flex items-center lh-1">
                            <svg class="icon icon-sm" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M10 14l2 -2m0 0l2 -2m-2 2l-2 -2m2 2l2 2"/>
                                <circle cx="12" cy="12" r="9"/>
                            </svg>
                        </span>
                    </div>
                </div>
                <div class="h1 mb-3">{{ $totalUsed }} days</div>
                <div class="flex mb-2">
                    <div class="progress progress-sm flex-1">
                        @php
                            $usedPercentage = $totalAllocated > 0 ? ($totalUsed / $totalAllocated) * 100 : 0;
                        @endphp
                        <div class="progress-bar bg-red" style="width: {{ $usedPercentage }}%"></div>
                    </div>
                </div>
                <div class="text-gray-600">{{ round($usedPercentage, 1) }}% utilized</div>
            </div>
        </div>
    </div>
    
    <div class="sm:w-1/2 px-2 lg:w-1/4 px-2">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200-body">
                <div class="flex items-center">
                    <div class="subheader">Remaining</div>
                    <div class="ml-auto">
                        <span class="text-green inline-flex items-center lh-1">
                            <svg class="icon icon-sm" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M5 12l5 5l10 -10"/>
                            </svg>
                        </span>
                    </div>
                </div>
                <div class="h1 mb-3">{{ $totalRemaining }} days</div>
                <div class="flex mb-2">
                    <div class="progress progress-sm flex-1">
                        @php
                            $remainingPercentage = $totalAllocated > 0 ? ($totalRemaining / $totalAllocated) * 100 : 0;
                        @endphp
                        <div class="progress-bar bg-green" style="width: {{ $remainingPercentage }}%"></div>
                    </div>
                </div>
                <div class="text-gray-600">{{ round($remainingPercentage, 1) }}% available</div>
            </div>
        </div>
    </div>
    
    <div class="sm:w-1/2 px-2 lg:w-1/4 px-2">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200-body">
                <div class="flex items-center">
                    <div class="subheader">Current Year</div>
                    <div class="ml-auto">
                        <span class="text-blue inline-flex items-center lh-1">
                            <svg class="icon icon-sm" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <rect x="4" y="5" width="16" height="16" rx="2"/>
                                <line x1="16" y1="3" x2="16" y2="7"/>
                                <line x1="8" y1="3" x2="8" y2="7"/>
                                <line x1="4" y1="11" x2="20" y2="11"/>
                            </svg>
                        </span>
                    </div>
                </div>
                <div class="h1 mb-3">{{ $currentYear }}</div>
                <div class="flex mb-2">
                    <div class="progress progress-sm flex-1">
                        @php
                            $yearProgress = (date('z') / 365) * 100;
                        @endphp
                        <div class="progress-bar bg-blue" style="width: {{ $yearProgress }}%"></div>
                    </div>
                </div>
                <div class="text-gray-600">{{ round($yearProgress, 1) }}% year elapsed</div>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-12 gap-4">
    <!-- Current Year Leave Balances -->
    <div class="lg:col-span-8">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200-header">
                <h3 class="bg-white rounded-lg shadow-sm border border-gray-200-title">Current Year Leave Balances ({{ $currentYear }})</h3>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200-actions">
                    <a href="{{ route('leave.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M12 5l0 14"/>
                            <path d="M5 12l14 0"/>
                        </svg>
                        Request Leave
                    </a>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200-body">
                @if($leaveBalances->count() > 0)
                    <div class="min-w-full divide-y divide-gray-200-responsive">
                        <min-w-full divide-y divide-gray-200 class="min-w-full divide-y divide-gray-200 min-w-full divide-y divide-gray-200-vcenter">
                            <thead>
                                <tr>
                                    <th>Leave Type</th>
                                    <th>Allocated</th>
                                    <th>Used</th>
                                    <th>Remaining</th>
                                    <th>Carried Forward</th>
                                    <th>Progress</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($leaveBalances as $balance)
                                <tr>
                                    <td>
                                        <div class="flex items-center">
                                            <div class="avatar avatar-sm mr-3" style="background-color: {{ $balance->leaveType->metadata['color'] ?? '#206bc4' }}">
                                                {{ strtoupper(substr($balance->leaveType->name, 0, 2)) }}
                                            </div>
                                            <div>
                                                <div class="font-medium">{{ $balance->leaveType->name }}</div>
                                                <div class="text-gray-600">{{ $balance->leaveType->code }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-blue-lt">{{ $balance->allocated_days }} days</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-red-lt">{{ $balance->used_days }} days</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-green-lt">{{ $balance->remaining_days }} days</span>
                                    </td>
                                    <td>
                                        @if($balance->carried_forward > 0)
                                            <span class="badge bg-yellow-lt">{{ $balance->carried_forward }} days</span>
                                        @else
                                            <span class="text-gray-600">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $percentage = $balance->allocated_days > 0 ? ($balance->used_days / $balance->allocated_days) * 100 : 0;
                                            $color = $percentage < 50 ? 'success' : ($percentage < 80 ? 'warning' : 'danger');
                                        @endphp
                                        <div class="flex items-center">
                                            <div class="flex-1">
                                                <div class="progress progress-sm">
                                                    <div class="progress-bar bg-{{ $color }}" style="width: {{ $percentage }}%"></div>
                                                </div>
                                            </div>
                                            <div class="ms-3">
                                                <small class="text-gray-600">{{ round($percentage, 1) }}%</small>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </min-w-full divide-y divide-gray-200>
                    </div>
                @else
                    <div class="text-center py-5">
                        <svg class="icon mb-2" width="48" height="48" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <circle cx="12" cy="12" r="9"/>
                            <line x1="9" y1="9" x2="15" y2="15"/>
                            <line x1="15" y1="9" x2="9" y2="15"/>
                        </svg>
                        <h3 class="text-gray-600">No leave balances found</h3>
                        <p class="text-gray-600">Contact HR to set up your leave entitlements for {{ $currentYear }}.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
    
    <!-- Quick Actions & Info -->
    <div class="lg:col-span-4">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200-header">
                <h3 class="bg-white rounded-lg shadow-sm border border-gray-200-title">Quick Actions</h3>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200-body">
                <div class="bg-white shadow overflow-hidden sm:rounded-md bg-white shadow overflow-hidden sm:rounded-md-flush">
                    <a href="{{ route('leave.create') }}" class="bg-white shadow overflow-hidden sm:rounded-md-item bg-white shadow overflow-hidden sm:rounded-md-item-action">
                        <div class="flex items-center">
                            <div class="avatar avatar-sm mr-3 bg-primary-lt">
                                <svg class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M12 5l0 14"/>
                                    <path d="M5 12l14 0"/>
                                </svg>
                            </div>
                            <div>
                                <div class="font-medium">Request Leave</div>
                                <div class="text-gray-600">Submit a new leave request</div>
                            </div>
                        </div>
                    </a>
                    <a href="{{ route('leave.requests') }}" class="bg-white shadow overflow-hidden sm:rounded-md-item bg-white shadow overflow-hidden sm:rounded-md-item-action">
                        <div class="flex items-center">
                            <div class="avatar avatar-sm mr-3 bg-info-lt">
                                <svg class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M9 11l3 3l8 -8"/>
                                    <path d="M20 12v6a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12c0 -1.1 .9 -2 2 -2h9"/>
                                </svg>
                            </div>
                            <div>
                                <div class="font-medium">My Requests</div>
                                <div class="text-gray-600">View all leave requests</div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mt-4">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200-header">
                <h3 class="bg-white rounded-lg shadow-sm border border-gray-200-title">Leave Policy</h3>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200-body">
                <div class="text-gray-600 small">
                    <ul class="mb-0">
                        <li>Leave year runs from January 1st to December 31st</li>
                        <li>Up to 5 days can be carried forward to next year</li>
                        <li>Leave requests must be submitted in advance</li>
                        <li>Emergency leave may be approved retroactively</li>
                        <li>Unused leave expires at year end (except carried forward)</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@if($balanceHistory->count() > 0)
<div class="grid grid-cols-12 gap-4">
    <div class="col-span-12">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200-header">
                <h3 class="bg-white rounded-lg shadow-sm border border-gray-200-title">Leave Balance History</h3>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200-body">
                <div class="min-w-full divide-y divide-gray-200-responsive">
                    <min-w-full divide-y divide-gray-200 class="min-w-full divide-y divide-gray-200 min-w-full divide-y divide-gray-200-vcenter">
                        <thead>
                            <tr>
                                <th>Year</th>
                                <th>Leave Type</th>
                                <th>Allocated</th>
                                <th>Used</th>
                                <th>Remaining</th>
                                <th>Carried Forward</th>
                                <th>Utilization</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($balanceHistory as $year => $yearBalances)
                                @foreach($yearBalances as $balance)
                                <tr>
                                    <td>
                                        <span class="badge bg-blue-lt">{{ $year }}</span>
                                    </td>
                                    <td>
                                        <div class="flex items-center">
                                            <div class="avatar avatar-sm mr-3" style="background-color: {{ $balance->leaveType->metadata['color'] ?? '#206bc4' }}">
                                                {{ strtoupper(substr($balance->leaveType->name, 0, 2)) }}
                                            </div>
                                            <div>{{ $balance->leaveType->name }}</div>
                                        </div>
                                    </td>
                                    <td>{{ $balance->allocated_days }} days</td>
                                    <td>{{ $balance->used_days }} days</td>
                                    <td>{{ $balance->remaining_days }} days</td>
                                    <td>
                                        @if($balance->carried_forward > 0)
                                            {{ $balance->carried_forward }} days
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $percentage = $balance->allocated_days > 0 ? ($balance->used_days / $balance->allocated_days) * 100 : 0;
                                            $color = $percentage < 50 ? 'success' : ($percentage < 80 ? 'warning' : 'danger');
                                        @endphp
                                        <div class="flex items-center">
                                            <div class="flex-1">
                                                <div class="progress progress-sm">
                                                    <div class="progress-bar bg-{{ $color }}" style="width: {{ $percentage }}%"></div>
                                                </div>
                                            </div>
                                            <div class="ms-3">
                                                <small class="text-gray-600">{{ round($percentage, 1) }}%</small>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </min-w-full divide-y divide-gray-200>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
@endsection