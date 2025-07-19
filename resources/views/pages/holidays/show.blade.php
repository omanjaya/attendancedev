@extends('layouts.app')

@section('title', $holiday->name)

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <a href="{{ route('holidays.index') }}" 
                   class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Holidays
                </a>
            </div>
            
            <div class="flex space-x-3">
                @can('edit_holidays')
                <a href="{{ route('holidays.edit', $holiday) }}" 
                   class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Edit
                </a>
                @endcan
                
                @can('delete_holidays')
                <button onclick="deleteHoliday()" 
                        class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Delete
                </button>
                @endcan
            </div>
        </div>
    </div>

    <!-- Holiday Details -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <!-- Header with color -->
        <div class="px-6 py-4 border-b border-gray-200" style="background: linear-gradient(135deg, {{ $holiday->color }}20, {{ $holiday->color }}10);">
            <div class="flex items-center">
                <div class="flex-shrink-0 h-4 w-4 rounded-full" style="background-color: {{ $holiday->color }}"></div>
                <div class="ml-4">
                    <h1 class="text-2xl font-bold text-gray-900">{{ $holiday->name }}</h1>
                    @if($holiday->description)
                    <p class="mt-1 text-sm text-gray-600">{{ $holiday->description }}</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Basic Information -->
                <div class="space-y-4">
                    <h3 class="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2">Basic Information</h3>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Start Date</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $holiday->date->format('l, d F Y') }}</dd>
                        </div>
                        
                        @if($holiday->end_date && $holiday->end_date != $holiday->date)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">End Date</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $holiday->end_date->format('l, d F Y') }}</dd>
                        </div>
                        
                        <div class="col-span-2">
                            <dt class="text-sm font-medium text-gray-500">Duration</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ $holiday->date->diffInDays($holiday->end_date) + 1 }} day(s)
                                ({{ $holiday->date->format('d M') }} - {{ $holiday->end_date->format('d M Y') }})
                            </dd>
                        </div>
                        @endif
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Type</dt>
                            <dd class="mt-1">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @switch($holiday->type)
                                        @case('public_holiday')
                                            bg-red-100 text-red-800
                                            @break
                                        @case('religious_holiday')
                                            bg-green-100 text-green-800
                                            @break
                                        @case('school_holiday')
                                            bg-blue-100 text-blue-800
                                            @break
                                        @case('substitute_holiday')
                                            bg-purple-100 text-purple-800
                                            @break
                                        @default
                                            bg-gray-100 text-gray-800
                                    @endswitch
                                ">
                                    {{ $holiday->type_label }}
                                </span>
                            </dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Status</dt>
                            <dd class="mt-1">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @if($holiday->status === 'active')
                                        bg-green-100 text-green-800
                                    @else
                                        bg-gray-100 text-gray-800
                                    @endif
                                ">
                                    {{ $holiday->status_label }}
                                </span>
                            </dd>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Paid Holiday</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                @if($holiday->is_paid)
                                    <span class="inline-flex items-center text-green-600">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                        Yes
                                    </span>
                                @else
                                    <span class="inline-flex items-center text-red-600">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                        No
                                    </span>
                                @endif
                            </dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Source</dt>
                            <dd class="mt-1 text-sm text-gray-900 capitalize">{{ $holiday->source ?? 'Manual' }}</dd>
                        </div>
                    </div>
                </div>

                <!-- Recurring & Role Information -->
                <div class="space-y-4">
                    <h3 class="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2">Additional Details</h3>
                    
                    <!-- Recurring Information -->
                    <div>
                        <dt class="text-sm font-medium text-gray-500 mb-2">Recurring</dt>
                        <dd class="text-sm text-gray-900">
                            @if($holiday->is_recurring)
                                <div class="bg-green-50 border border-green-200 rounded-md p-3">
                                    <div class="flex">
                                        <svg class="flex-shrink-0 h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" clip-rule="evenodd" />
                                        </svg>
                                        <div class="ml-3">
                                            <p class="text-sm text-green-800 font-medium">This holiday repeats annually</p>
                                            @if($holiday->recurring_pattern)
                                                @php
                                                    $pattern = is_string($holiday->recurring_pattern) ? json_decode($holiday->recurring_pattern, true) : $holiday->recurring_pattern;
                                                @endphp
                                                @if(isset($pattern['type']))
                                                    <p class="text-sm text-green-700 mt-1">
                                                        Pattern: 
                                                        @if($pattern['type'] === 'yearly')
                                                            Same date every year
                                                        @elseif($pattern['type'] === 'relative' && isset($pattern['week'], $pattern['day'], $pattern['month']))
                                                            @php
                                                                $weekLabels = [1 => '1st', 2 => '2nd', 3 => '3rd', 4 => '4th', -1 => 'Last'];
                                                                $dayLabels = [0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday'];
                                                                $monthLabels = [1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'];
                                                            @endphp
                                                            {{ $weekLabels[$pattern['week']] ?? $pattern['week'] }} 
                                                            {{ $dayLabels[$pattern['day']] ?? $pattern['day'] }} of 
                                                            {{ $monthLabels[$pattern['month']] ?? $pattern['month'] }}
                                                        @else
                                                            Custom pattern
                                                        @endif
                                                    </p>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="bg-gray-50 border border-gray-200 rounded-md p-3">
                                    <div class="flex">
                                        <svg class="flex-shrink-0 h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                        <div class="ml-3">
                                            <p class="text-sm text-gray-800">One-time holiday</p>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </dd>
                    </div>
                    
                    <!-- Affected Roles -->
                    <div>
                        <dt class="text-sm font-medium text-gray-500 mb-2">Affected Roles</dt>
                        <dd class="text-sm text-gray-900">
                            @if($holiday->affected_roles && count($holiday->affected_roles) > 0)
                                <div class="flex flex-wrap gap-2">
                                    @foreach($holiday->affected_roles as $role)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ ucfirst(str_replace('_', ' ', $role)) }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-gray-500 italic">All employees</span>
                            @endif
                        </dd>
                    </div>
                    
                    <!-- Metadata -->
                    <div class="border-t border-gray-200 pt-4 mt-4">
                        <h4 class="text-sm font-medium text-gray-500 mb-3">Metadata</h4>
                        <dl class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <dt class="text-gray-500">Created</dt>
                                <dd class="text-gray-900">{{ $holiday->created_at->format('d M Y, H:i') }}</dd>
                            </div>
                            @if($holiday->updated_at != $holiday->created_at)
                            <div class="flex justify-between text-sm">
                                <dt class="text-gray-500">Last Updated</dt>
                                <dd class="text-gray-900">{{ $holiday->updated_at->format('d M Y, H:i') }}</dd>
                            </div>
                            @endif
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Calendar Context -->
    <div class="mt-6 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Calendar Context</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Days Until -->
            <div class="text-center">
                <div class="text-2xl font-bold text-gray-900">
                    @if($holiday->date->isFuture())
                        {{ $holiday->date->diffInDays(now()) }}
                    @elseif($holiday->date->isToday())
                        0
                    @else
                        -{{ now()->diffInDays($holiday->date) }}
                    @endif
                </div>
                <div class="text-sm text-gray-500">
                    @if($holiday->date->isFuture())
                        days until holiday
                    @elseif($holiday->date->isToday())
                        holiday is today
                    @else
                        days since holiday
                    @endif
                </div>
            </div>
            
            <!-- Day of Week -->
            <div class="text-center">
                <div class="text-2xl font-bold text-gray-900">{{ $holiday->date->format('l') }}</div>
                <div class="text-sm text-gray-500">day of the week</div>
            </div>
            
            <!-- Working Days Impact -->
            <div class="text-center">
                <div class="text-2xl font-bold text-gray-900">
                    @if($holiday->date->isWeekend())
                        0
                    @else
                        {{ $holiday->end_date ? $holiday->date->diffInWeekdays($holiday->end_date) + 1 : 1 }}
                    @endif
                </div>
                <div class="text-sm text-gray-500">working days affected</div>
            </div>
        </div>
    </div>

    @if($holiday->is_recurring)
    <!-- Next Occurrences -->
    <div class="mt-6 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Next Occurrences</h3>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Year</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Day</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Days From Now</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @for($i = 0; $i < 5; $i++)
                        @php
                            $nextYear = now()->year + $i;
                            try {
                                $nextOccurrence = $holiday->generateNextOccurrence($nextYear);
                                if ($nextOccurrence && $nextOccurrence->date) {
                                    $nextDate = $nextOccurrence->date;
                                } else {
                                    $nextDate = null;
                                }
                            } catch (Exception $e) {
                                $nextDate = null;
                            }
                        @endphp
                        
                        @if($nextDate)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $nextYear }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $nextDate->format('d M Y') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $nextDate->format('l') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                @if($nextDate->isFuture())
                                    {{ $nextDate->diffInDays(now()) }} days
                                @elseif($nextDate->isToday())
                                    Today
                                @else
                                    {{ now()->diffInDays($nextDate) }} days ago
                                @endif
                            </td>
                        </tr>
                        @endif
                    @endfor
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

<script>
function deleteHoliday() {
    if (confirm('Are you sure you want to delete "{{ $holiday->name }}"? This action cannot be undone.')) {
        fetch('{{ route('holidays.destroy', $holiday) }}', {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Holiday deleted successfully');
                window.location.href = '{{ route('holidays.index') }}';
            } else {
                alert('Delete failed: ' + data.message);
            }
        })
        .catch(error => {
            alert('Delete failed: ' + error.message);
        });
    }
}
</script>
@endsection