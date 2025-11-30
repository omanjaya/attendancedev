@props([
    'orientation' => 'horizontal',    // horizontal, vertical
])

@php
    $classes = 'shrink-0 bg-border';
    
    if ($orientation === 'vertical') {
        $classes .= ' w-px h-full';
    } else {
        $classes .= ' h-px w-full';
    }
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}
     role="separator"
     aria-orientation="{{ $orientation }}"></div>