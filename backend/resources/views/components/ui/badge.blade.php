@props([
    'variant' => 'default',    // default, secondary, destructive, outline, success, warning, info
    'size' => 'default',       // default, sm, lg
])

@php
    // Use design system classes
    $baseClasses = 'badge';
    
    // Variant classes using design system
    $variantClasses = [
        'default' => 'badge-default',
        'secondary' => 'badge-secondary',
        'destructive' => 'badge-destructive',
        'outline' => 'badge-outline',
        'success' => 'badge-success',
        'warning' => 'badge-warning',
        'info' => 'badge-info',
    ];
    
    // Size classes - since we don't have size variants in design system, keep these
    $sizeClasses = [
        'default' => '',
        'sm' => 'text-xs px-2 py-0.5',
        'lg' => 'text-sm px-3 py-1',
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