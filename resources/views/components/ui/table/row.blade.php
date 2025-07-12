@props([
    'class' => '',
    'hover' => true
])

@php
    $baseClasses = 'border-b border-border transition-colors';
    $hoverClasses = $hover ? 'hover:bg-muted/50' : '';
    $classes = trim($baseClasses . ' ' . $hoverClasses . ' ' . $class);
@endphp

<tr {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</tr>