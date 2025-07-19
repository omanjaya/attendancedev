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
    // Use CSS classes from our design system
    $baseClasses = 'btn';
    
    // Variant classes using design system
    $variantClasses = [
        'default' => 'btn-primary',
        'secondary' => 'btn-secondary', 
        'destructive' => 'btn-destructive',
        'outline' => 'btn-outline',
        'ghost' => 'btn-ghost',
        'link' => 'btn-link',
        'success' => 'btn-success',
        'warning' => 'btn-warning',
        'info' => 'btn-info',
    ];
    
    // Size classes using design system
    $sizeClasses = [
        'default' => 'btn-md',
        'sm' => 'btn-sm',
        'lg' => 'btn-lg', 
        'icon' => 'btn-md w-10 touch-target',
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