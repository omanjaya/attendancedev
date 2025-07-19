@extends('layouts.app')

@section('title', 'Holiday Calendar')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center space-x-4">
                    <a href="{{ route('holidays.index') }}" 
                       class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back to Holidays
                    </a>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mt-4">Holiday Calendar</h1>
                <p class="mt-2 text-sm text-gray-600">View holidays in calendar format for {{ $year }}</p>
            </div>
            <div class="mt-4 sm:mt-0 flex space-x-3">
                @can('create_holidays')
                <button onclick="window.location.href='{{ route('holidays.create') }}'" 
                        class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Add Holiday
                </button>
                @endcan
            </div>
        </div>
    </div>

    <!-- Calendar Controls -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
            <!-- Year Navigation -->
            <div class="flex items-center space-x-4">
                <div class="flex items-center space-x-2">
                    <label for="yearSelect" class="text-sm font-medium text-gray-700">Year:</label>
                    <select id="yearSelect" onchange="changeYear(this.value)" 
                            class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @for($i = 2020; $i <= 2030; $i++)
                            <option value="{{ $i }}" {{ $i == $year ? 'selected' : '' }}>{{ $i }}</option>
                        @endfor
                    </select>
                </div>
                
                <div class="flex items-center space-x-1">
                    <button onclick="changeYear({{ $year - 1 }})" 
                            class="p-2 text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <span class="text-lg font-semibold text-gray-900 px-4">{{ $year }}</span>
                    <button onclick="changeYear({{ $year + 1 }})" 
                            class="p-2 text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Legend -->
            <div class="flex flex-wrap items-center gap-4">
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                    <span class="text-sm text-gray-600">Public Holiday</span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                    <span class="text-sm text-gray-600">Religious Holiday</span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                    <span class="text-sm text-gray-600">School Holiday</span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 bg-purple-500 rounded-full"></div>
                    <span class="text-sm text-gray-600">Substitute Holiday</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Calendar -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-6">
            <div id="calendar" class="calendar-container">
                <!-- Calendar will be rendered here -->
                <div class="grid grid-cols-12 gap-4">
                    @php
                        $months = [
                            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                        ];
                        
                        // Group holidays by month
                        $holidaysByMonth = $holidays->groupBy(function($holiday) {
                            return $holiday->date->month;
                        });
                    @endphp
                    
                    @foreach($months as $monthNum => $monthName)
                        <div class="col-span-12 md:col-span-6 lg:col-span-4 xl:col-span-3">
                            <div class="border border-gray-200 rounded-lg">
                                <!-- Month Header -->
                                <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                                    <h3 class="text-sm font-semibold text-gray-900">{{ $monthName }} {{ $year }}</h3>
                                </div>
                                
                                <!-- Calendar Grid -->
                                <div class="p-3">
                                    @php
                                        $monthStart = \Carbon\Carbon::create($year, $monthNum, 1);
                                        $monthEnd = $monthStart->copy()->endOfMonth();
                                        $startDay = $monthStart->copy()->startOfWeek(\Carbon\Carbon::SUNDAY);
                                        $endDay = $monthEnd->copy()->endOfWeek(\Carbon\Carbon::SATURDAY);
                                        $monthHolidays = $holidaysByMonth->get($monthNum, collect());
                                    @endphp
                                    
                                    <!-- Day Headers -->
                                    <div class="grid grid-cols-7 gap-1 mb-2">
                                        <div class="text-xs font-medium text-gray-500 text-center py-1">Su</div>
                                        <div class="text-xs font-medium text-gray-500 text-center py-1">Mo</div>
                                        <div class="text-xs font-medium text-gray-500 text-center py-1">Tu</div>
                                        <div class="text-xs font-medium text-gray-500 text-center py-1">We</div>
                                        <div class="text-xs font-medium text-gray-500 text-center py-1">Th</div>
                                        <div class="text-xs font-medium text-gray-500 text-center py-1">Fr</div>
                                        <div class="text-xs font-medium text-gray-500 text-center py-1">Sa</div>
                                    </div>
                                    
                                    <!-- Calendar Days -->
                                    <div class="grid grid-cols-7 gap-1">
                                        @for($date = $startDay->copy(); $date->lte($endDay); $date->addDay())
                                            @php
                                                $isCurrentMonth = $date->month === $monthNum;
                                                $isToday = $date->isToday();
                                                $dayHolidays = $monthHolidays->filter(function($holiday) use ($date) {
                                                    return $holiday->date->isSameDay($date) || 
                                                           ($holiday->end_date && $date->between($holiday->date, $holiday->end_date));
                                                });
                                            @endphp
                                            
                                            <div class="relative min-h-[32px] p-1 text-center
                                                {{ $isCurrentMonth ? '' : 'text-gray-300' }}
                                                {{ $isToday ? 'bg-blue-100 rounded' : '' }}
                                            ">
                                                <span class="text-xs {{ $isToday ? 'font-bold text-blue-600' : '' }}">
                                                    {{ $date->day }}
                                                </span>
                                                
                                                @if($dayHolidays->isNotEmpty())
                                                    <div class="absolute bottom-0 left-1/2 transform -translate-x-1/2 flex space-x-0.5">
                                                        @foreach($dayHolidays->take(3) as $holiday)
                                                            <div class="w-1.5 h-1.5 rounded-full" 
                                                                 style="background-color: {{ $holiday->color }}"
                                                                 title="{{ $holiday->name }}"></div>
                                                        @endforeach
                                                        @if($dayHolidays->count() > 3)
                                                            <div class="w-1.5 h-1.5 rounded-full bg-gray-400" title="More holidays"></div>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        @endfor
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Holiday List -->
    <div class="mt-6 bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Holidays in {{ $year }}</h3>
        </div>
        
        <div class="p-6">
            @if($holidays->isNotEmpty())
                @php
                    $holidaysByMonth = $holidays->groupBy(function($holiday) {
                        return $holiday->date->format('F');
                    });
                @endphp
                
                <div class="space-y-6">
                    @foreach($holidaysByMonth as $month => $monthHolidays)
                        <div>
                            <h4 class="text-md font-semibold text-gray-800 mb-3">{{ $month }}</h4>
                            <div class="space-y-2">
                                @foreach($monthHolidays as $holiday)
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                        <div class="flex items-center space-x-3">
                                            <div class="flex-shrink-0 h-3 w-3 rounded-full" style="background-color: {{ $holiday->color }}"></div>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">{{ $holiday->name }}</div>
                                                <div class="text-sm text-gray-500">
                                                    {{ $holiday->date->format('d M Y') }}
                                                    @if($holiday->end_date && $holiday->end_date != $holiday->date)
                                                        - {{ $holiday->end_date->format('d M Y') }}
                                                    @endif
                                                    ({{ $holiday->date->format('l') }})
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="flex items-center space-x-2">
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
                                            
                                            @if($holiday->is_recurring)
                                                <span class="inline-flex items-center text-green-600" title="Recurring holiday">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" clip-rule="evenodd" />
                                                    </svg>
                                                </span>
                                            @endif
                                            
                                            <a href="{{ route('holidays.show', $holiday) }}" 
                                               class="text-blue-600 hover:text-blue-900 text-sm">View</a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No holidays found</h3>
                    <p class="mt-1 text-sm text-gray-500">No holidays are scheduled for {{ $year }}.</p>
                    @can('create_holidays')
                    <div class="mt-6">
                        <a href="{{ route('holidays.create') }}" 
                           class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Add Holiday
                        </a>
                    </div>
                    @endcan
                </div>
            @endif
        </div>
    </div>
</div>

<script>
function changeYear(year) {
    if (year >= 2020 && year <= 2030) {
        window.location.href = '{{ route('holidays.calendar') }}?year=' + year;
    }
}
</script>

<style>
.calendar-container {
    font-family: inherit;
}

.calendar-container .grid {
    display: grid;
}

@media (max-width: 768px) {
    .calendar-container .grid-cols-12 > div {
        grid-column: span 6;
    }
}

@media (max-width: 640px) {
    .calendar-container .grid-cols-12 > div {
        grid-column: span 12;
    }
}
</style>
@endsection