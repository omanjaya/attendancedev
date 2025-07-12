@props([
    'class' => '',
    'clickable' => false,
    'href' => null,
    'selected' => false,
])

@php
    $rowClasses = 'border-b transition-colors';
    
    if ($clickable || $href) {
        $rowClasses .= ' cursor-pointer hover:bg-muted/50';
    }
    
    if ($selected) {
        $rowClasses .= ' bg-muted/50';
    }
    
    $rowClasses .= " {$class}";
@endphp

@if($href)
    <tr {{ $attributes->merge(['class' => $rowClasses]) }} onclick="window.location.href='{{ $href }}'">
        {{ $slot }}
    </tr>
@else
    <tr {{ $attributes->merge(['class' => $rowClasses]) }}>
        {{ $slot }}
    </tr>
@endif