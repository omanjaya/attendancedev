@props([
    'variant' => 'default', // default, primary, success, warning, danger, info
    'size' => 'default', // sm, default, lg
    'hover' => true,
    'padding' => 'default', // sm, default, lg, none
])

@php
    $variantClasses = match($variant) {
        'primary' => 'bg-emerald-50 dark:bg-emerald-900 border-emerald-200 dark:border-emerald-700',
        'success' => 'bg-green-50 dark:bg-green-900 border-green-200 dark:border-green-700',
        'warning' => 'bg-amber-50 dark:bg-amber-900 border-amber-200 dark:border-amber-700',
        'danger' => 'bg-red-50 dark:bg-red-900 border-red-200 dark:border-red-700',
        'info' => 'bg-blue-50 dark:bg-blue-900 border-blue-200 dark:border-blue-700',
        default => 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700',
    };

    $sizeClasses = match($size) {
        'sm' => 'rounded-md',
        'lg' => 'rounded-xl',
        default => 'rounded-lg',
    };

    $paddingClasses = match($padding) {
        'sm' => 'p-3',
        'lg' => 'p-6',
        'none' => '',
        default => 'p-4',
    };

    $hoverClasses = $hover ? 'hover:shadow-md hover:border-gray-300 dark:hover:border-gray-600' : '';
@endphp

<div {{ $attributes->merge([
    'class' => implode(' ', array_filter([
        'border shadow-sm transition-all duration-200',
        $variantClasses,
        $sizeClasses,
        $paddingClasses,
        $hoverClasses,
    ]))
]) }}>
    {{ $slot }}
</div>