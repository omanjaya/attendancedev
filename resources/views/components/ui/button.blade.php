@props([
    'variant' => 'default',    // default, secondary, destructive, outline, ghost, link, success, warning, info
    'size' => 'default',       // default, sm, lg, icon
    'type' => 'button',
    'href' => null,
    'disabled' => false,
    'loading' => false,
    'loadingText' => 'Loading...',
])

@php
    // Base button classes using pure Tailwind
    $baseClasses = 'inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50';
    
    // Variant classes using pure Tailwind
    $variantClasses = [
        'default' => 'bg-emerald-500 text-white hover:bg-emerald-600 dark:bg-emerald-600 dark:hover:bg-emerald-700',
        'secondary' => 'bg-gray-100 text-gray-900 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-100 dark:hover:bg-gray-700', 
        'destructive' => 'bg-red-500 text-white hover:bg-red-600 dark:bg-red-600 dark:hover:bg-red-700',
        'outline' => 'border border-emerald-500 text-emerald-600 bg-background hover:bg-emerald-50 hover:text-emerald-700 dark:border-emerald-400 dark:text-emerald-400 dark:hover:bg-emerald-950',
        'ghost' => 'text-emerald-600 hover:bg-emerald-50 hover:text-emerald-700 dark:text-emerald-400 dark:hover:bg-emerald-950',
        'link' => 'text-emerald-600 underline-offset-4 hover:underline dark:text-emerald-400',
        'success' => 'bg-green-500 text-white hover:bg-green-600 dark:bg-green-600 dark:hover:bg-green-700',
        'warning' => 'bg-amber-500 text-white hover:bg-amber-600 dark:bg-amber-600 dark:hover:bg-amber-700',
        'info' => 'bg-blue-500 text-white hover:bg-blue-600 dark:bg-blue-600 dark:hover:bg-blue-700',
    ];
    
    // Size classes using pure Tailwind
    $sizeClasses = [
        'default' => 'h-10 px-4 py-2',
        'sm' => 'h-9 rounded-md px-3 text-xs',
        'lg' => 'h-11 rounded-md px-8 text-base', 
        'icon' => 'h-10 w-10 min-h-[44px] min-w-[44px]',
    ];
    
    $variant = $variantClasses[$variant] ?? $variantClasses['default'];
    $size = $sizeClasses[$size] ?? $sizeClasses['default'];
    
    $classes = collect([$baseClasses, $variant, $size])
        ->filter()
        ->implode(' ');
        
    $isDisabled = $disabled || $loading;
@endphp

@if($href && !$isDisabled)
    {{-- Render as link --}}
    <a href="{{ $href }}" 
       {{ $attributes->merge(['class' => $classes]) }}
       @if($isDisabled) aria-disabled="true" @endif>
        @if($loading)
            <svg class="mr-2 h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ $loadingText }}
        @else
            {{ $slot }}
        @endif
    </a>
@else
    {{-- Render as button --}}
    <button type="{{ $type }}" 
            {{ $attributes->merge(['class' => $classes]) }}
            @if($isDisabled) disabled @endif>
        @if($loading)
            <svg class="mr-2 h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ $loadingText }}
        @else
            {{ $slot }}
        @endif
    </button>
@endif