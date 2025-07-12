@props([
    'src' => null,
    'alt' => '',
    'size' => 'default',    // sm, default, lg, xl
    'fallback' => null,
    'name' => null,
])

@php
    // Size classes
    $sizeClasses = [
        'sm' => 'h-8 w-8',
        'default' => 'h-10 w-10',
        'lg' => 'h-12 w-12', 
        'xl' => 'h-16 w-16',
    ];
    
    $sizeClass = $sizeClasses[$size] ?? $sizeClasses['default'];
    
    // Generate fallback text from name if not provided
    if (!$fallback && $name) {
        $words = explode(' ', trim($name));
        if (count($words) >= 2) {
            $fallback = strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        } else {
            $fallback = strtoupper(substr($name, 0, 2));
        }
    }
    
    $fallback = $fallback ?: 'U';
@endphp

<div {{ $attributes->merge(['class' => "relative flex {$sizeClass} shrink-0 overflow-hidden rounded-full"]) }}>
    @if($src)
        <img src="{{ $src }}" 
             alt="{{ $alt }}" 
             class="aspect-square h-full w-full object-cover" />
    @else
        <div class="flex h-full w-full items-center justify-center rounded-full bg-muted">
            <span class="text-xs font-medium text-muted-foreground">{{ $fallback }}</span>
        </div>
    @endif
</div>