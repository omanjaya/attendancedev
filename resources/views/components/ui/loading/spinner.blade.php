@props([
    'size' => 'md', // xs, sm, md, lg, xl
    'color' => 'emerald', // emerald, white, gray, blue, red
    'type' => 'spin', // spin, dots, bars, ring, pulse
    'text' => null,
    'centered' => false,
    'overlay' => false
])

@php
    $sizeClasses = [
        'xs' => 'w-3 h-3',
        'sm' => 'w-4 h-4', 
        'md' => 'w-6 h-6',
        'lg' => 'w-8 h-8',
        'xl' => 'w-12 h-12'
    ];
    
    $colorClasses = [
        'emerald' => 'text-emerald-500',
        'white' => 'text-white',
        'gray' => 'text-gray-500',
        'blue' => 'text-blue-500',
        'red' => 'text-red-500'
    ];
    
    $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
    $colorClass = $colorClasses[$color] ?? $colorClasses['emerald'];
    
    $containerClass = $centered ? 'flex items-center justify-center' : 'inline-flex items-center';
    
    if ($text) {
        $containerClass .= ' space-x-2';
    }
@endphp

@if($overlay)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 flex items-center space-x-4 shadow-xl">
@endif

<div class="{{ $containerClass }}" {{ $attributes }}>
    @if($type === 'spin')
        <svg class="{{ $sizeClass }} {{ $colorClass }} animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>

    @elseif($type === 'dots')
        <div class="flex space-x-1">
            @for($i = 0; $i < 3; $i++)
                @php
                    $delay = $i * 0.2;
                    $dotSize = $size === 'xs' ? 'w-1 h-1' : ($size === 'sm' ? 'w-1.5 h-1.5' : 'w-2 h-2');
                @endphp
                <div class="{{ $dotSize }} {{ str_replace('text-', 'bg-', $colorClass) }} rounded-full animate-bounce" 
                     style="animation-delay: {{ $delay }}s"></div>
            @endfor
        </div>

    @elseif($type === 'bars')
        <div class="flex space-x-1 items-end">
            @for($i = 0; $i < 4; $i++)
                @php
                    $delay = $i * 0.15;
                    $barWidth = $size === 'xs' ? 'w-0.5' : ($size === 'sm' ? 'w-1' : 'w-1.5');
                    $maxHeight = $size === 'xs' ? 'h-3' : ($size === 'sm' ? 'h-4' : 'h-6');
                @endphp
                <div class="{{ $barWidth }} {{ $maxHeight }} {{ str_replace('text-', 'bg-', $colorClass) }} animate-pulse" 
                     style="animation-delay: {{ $delay }}s"></div>
            @endfor
        </div>

    @elseif($type === 'ring')
        <div class="{{ $sizeClass }} relative">
            <div class="absolute inset-0 rounded-full border-2 {{ str_replace('text-', 'border-', $colorClass) }} opacity-25"></div>
            <div class="absolute inset-0 rounded-full border-2 border-transparent {{ str_replace('text-', 'border-t-', $colorClass) }} animate-spin"></div>
        </div>

    @elseif($type === 'pulse')
        <div class="{{ $sizeClass }} {{ str_replace('text-', 'bg-', $colorClass) }} rounded-full animate-ping opacity-75"></div>

    @else
        <!-- Default spin -->
        <svg class="{{ $sizeClass }} {{ $colorClass }} animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    @endif

    @if($text)
        <span class="text-sm font-medium {{ $colorClass === 'text-white' ? 'text-white' : 'text-gray-700 dark:text-gray-300' }}">
            {{ $text }}
        </span>
    @endif
</div>

@if($overlay)
        </div>
    </div>
@endif

<style>
    /* Enhanced loading animations */
    @keyframes loading-bounce {
        0%, 80%, 100% {
            transform: scale(0);
        }
        40% {
            transform: scale(1);
        }
    }
    
    .animate-loading-bounce {
        animation: loading-bounce 1.4s infinite ease-in-out both;
    }
    
    @keyframes loading-bars {
        0%, 40%, 100% {
            transform: scaleY(0.4);
        }
        20% {
            transform: scaleY(1);
        }
    }
    
    .animate-loading-bars {
        animation: loading-bars 1.2s infinite ease-in-out;
    }
    
    /* Smooth loading transitions */
    .loading-enter {
        opacity: 0;
        transform: scale(0.9);
    }
    
    .loading-enter-active {
        opacity: 1;
        transform: scale(1);
        transition: opacity 300ms, transform 300ms;
    }
    
    .loading-exit {
        opacity: 1;
        transform: scale(1);
    }
    
    .loading-exit-active {
        opacity: 0;
        transform: scale(0.9);
        transition: opacity 300ms, transform 300ms;
    }
</style>