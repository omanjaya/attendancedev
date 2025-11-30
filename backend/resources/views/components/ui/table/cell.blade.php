@props([
    'tag' => 'td',
    'class' => '',
    'align' => 'left'
])

@php
    $alignClasses = [
        'left' => 'text-left',
        'center' => 'text-center',
        'right' => 'text-right'
    ];
    
    $baseClasses = $tag === 'th' 
        ? 'px-4 py-3 text-xs font-medium text-muted-foreground uppercase tracking-wider'
        : 'px-4 py-3 text-sm text-foreground';
        
    $alignClass = $alignClasses[$align] ?? 'text-left';
    $classes = trim($baseClasses . ' ' . $alignClass . ' ' . $class);
@endphp

<{{ $tag }} {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</{{ $tag }}>