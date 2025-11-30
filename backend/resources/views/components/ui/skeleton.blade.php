@props([
    'class' => '',
    'height' => 'h-4',
    'width' => 'w-full',
    'shape' => 'rounded', // rounded, circle, none
    'animated' => true,
])

@php
    $skeletonClasses = 'bg-muted animate-pulse';
    
    switch ($shape) {
        case 'circle':
            $skeletonClasses .= ' rounded-full';
            break;
        case 'none':
            // No border radius
            break;
        default:
            $skeletonClasses .= ' rounded';
    }
    
    if (!$animated) {
        $skeletonClasses = str_replace(' animate-pulse', '', $skeletonClasses);
    }
    
    $skeletonClasses .= " {$height} {$width} {$class}";
@endphp

<div {{ $attributes->merge(['class' => $skeletonClasses]) }}></div>