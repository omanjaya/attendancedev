@props([
    'variant' => 'default',    // default, secondary, destructive, outline, success, warning, info
    'size' => 'default',       // default, sm, lg
])

@php
    // Base badge classes using pure Tailwind
    $baseClasses = 'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2';
    
    // Variant classes using pure Tailwind
    $variantClasses = [
        'default' => 'border-transparent bg-emerald-500 text-white hover:bg-emerald-600',
        'secondary' => 'border-transparent bg-gray-100 text-gray-900 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-100',
        'destructive' => 'border-transparent bg-red-500 text-white hover:bg-red-600',
        'outline' => 'text-foreground border-border',
        'success' => 'border-transparent bg-green-500 text-white hover:bg-green-600',
        'warning' => 'border-transparent bg-amber-500 text-white hover:bg-amber-600',
        'info' => 'border-transparent bg-blue-500 text-white hover:bg-blue-600',
    ];
    
    // Size classes using pure Tailwind
    $sizeClasses = [
        'default' => 'px-2.5 py-0.5 text-xs',
        'sm' => 'px-2 py-0.5 text-xs',
        'lg' => 'px-3 py-1 text-sm',
    ];
    
    $variant = $variantClasses[$variant] ?? $variantClasses['default'];
    $size = $sizeClasses[$size] ?? $sizeClasses['default'];
    
    $classes = collect([$baseClasses, $variant, $size])
        ->filter()
        ->implode(' ');
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</span>