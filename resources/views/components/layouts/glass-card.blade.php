@props([
    'variant' => 'default', // default, primary, success, warning, danger, info
    'size' => 'default', // sm, default, lg
    'hover' => true,
    'padding' => 'default', // sm, default, lg, none
])

@php
    $variantClasses = match($variant) {
        'primary' => 'bg-white/30 dark:bg-gray-800/40 border-emerald-200/30 dark:border-emerald-700/30',
        'success' => 'bg-white/30 dark:bg-gray-800/40 border-green-200/30 dark:border-green-700/30',
        'warning' => 'bg-white/30 dark:bg-gray-800/40 border-yellow-200/30 dark:border-yellow-700/30',
        'danger' => 'bg-white/30 dark:bg-gray-800/40 border-red-200/30 dark:border-red-700/30',
        'info' => 'bg-white/30 dark:bg-gray-800/40 border-blue-200/30 dark:border-blue-700/30',
        default => 'bg-white/30 dark:bg-gray-800/40 border-white/20 dark:border-gray-600/30',
    };

    $sizeClasses = match($size) {
        'sm' => 'rounded-xl',
        'lg' => 'rounded-3xl',
        default => 'rounded-2xl',
    };

    $paddingClasses = match($padding) {
        'sm' => 'p-3 sm:p-4',
        'lg' => 'p-6 sm:p-8',
        'none' => '',
        default => 'p-4 sm:p-6',
    };

    $hoverClasses = $hover ? 'hover:bg-white/40 dark:hover:bg-gray-800/50 hover:shadow-lg hover:shadow-emerald-500/10 hover:border-white/30 dark:hover:border-gray-500/40' : '';
@endphp

<div {{ $attributes->merge([
    'class' => implode(' ', array_filter([
        'relative backdrop-blur-xl border transition-all duration-300',
        $variantClasses,
        $sizeClasses,
        $paddingClasses,
        $hoverClasses,
        'group'
    ]))
]) }}>
    {{ $slot }}
</div>