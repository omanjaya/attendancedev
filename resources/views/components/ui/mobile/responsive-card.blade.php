@props([
    'title' => null,
    'subtitle' => null,
    'value' => null,
    'icon' => null,
    'trend' => null,
    'trendDirection' => 'up', // up, down, neutral
    'compact' => false,
    'action' => null,
    'href' => null
])

@php
    $baseClasses = 'bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm transition-all duration-200';
    $hoverClasses = $href ? 'hover:shadow-md hover:scale-[1.02] cursor-pointer' : '';
    $paddingClasses = $compact ? 'p-4' : 'p-4 sm:p-6';
    
    $trendClasses = [
        'up' => 'text-emerald-600 bg-emerald-50 dark:bg-emerald-900/20',
        'down' => 'text-red-600 bg-red-50 dark:bg-red-900/20',
        'neutral' => 'text-gray-600 bg-gray-50 dark:bg-gray-900/20'
    ];
    
    $trendClass = $trendClasses[$trendDirection] ?? $trendClasses['neutral'];
    
    $iconBgClasses = [
        'up' => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400',
        'down' => 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400',
        'neutral' => 'bg-gray-100 text-gray-600 dark:bg-gray-900/30 dark:text-gray-400'
    ];
    
    $iconBgClass = $iconBgClasses[$trendDirection] ?? $iconBgClasses['neutral'];
@endphp

@if($href)
    <a href="{{ $href }}" class="{{ $baseClasses }} {{ $hoverClasses }} {{ $paddingClasses }}">
@else
    <div class="{{ $baseClasses }} {{ $hoverClasses }} {{ $paddingClasses }}">
@endif

<div class="flex flex-col space-y-4 {{ $compact ? 'sm:space-y-3' : 'sm:space-y-4' }}">
    <!-- Header Row -->
    <div class="flex items-start justify-between">
        <!-- Icon and Title -->
        <div class="flex items-center space-x-3 min-w-0 flex-1">
            @if($icon)
                <div class="flex-shrink-0 w-10 h-10 sm:w-12 sm:h-12 rounded-lg {{ $iconBgClass }} flex items-center justify-center">
                    <i class="{{ $icon }} text-sm sm:text-base"></i>
                </div>
            @endif
            
            <div class="min-w-0 flex-1">
                @if($title)
                    <h3 class="text-sm sm:text-base font-medium text-gray-900 dark:text-white truncate">
                        {{ $title }}
                    </h3>
                @endif
                @if($subtitle)
                    <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 truncate">
                        {{ $subtitle }}
                    </p>
                @endif
            </div>
        </div>
        
        <!-- Trend Indicator -->
        @if($trend)
            <div class="flex-shrink-0 flex items-center px-2 py-1 rounded-full {{ $trendClass }} text-xs font-medium">
                @if($trendDirection === 'up')
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L4.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                @elseif($trendDirection === 'down')
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 10.293a1 1 0 010 1.414l-6 6a1 1 0 01-1.414 0l-6-6a1 1 0 111.414-1.414L9 14.586V3a1 1 0 012 0v11.586l4.293-4.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                @endif
                {{ $trend }}
            </div>
        @endif
    </div>
    
    <!-- Value Display -->
    @if($value)
        <div class="space-y-1">
            <div class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">
                {{ $value }}
            </div>
        </div>
    @endif
    
    <!-- Content Slot -->
    @if($slot->isNotEmpty())
        <div class="text-sm sm:text-base text-gray-600 dark:text-gray-400">
            {{ $slot }}
        </div>
    @endif
    
    <!-- Action Button -->
    @if($action)
        <div class="pt-2 border-t border-gray-200 dark:border-gray-700">
            {{ $action }}
        </div>
    @endif
</div>

@if($href)
    </a>
@else
    </div>
@endif