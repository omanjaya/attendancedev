@props([
    'variant' => 'default',
    'title' => null,
])

@php
    $variants = [
        'default' => 'bg-background border-border text-foreground',
        'destructive' => 'bg-destructive/10 border-destructive/20 text-destructive',
        'success' => 'bg-success/10 border-success/20 text-success',
        'warning' => 'bg-warning/10 border-warning/20 text-warning',
        'info' => 'bg-info/10 border-info/20 text-info',
    ];
    
    $icons = [
        'default' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'destructive' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
        'success' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        'warning' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z',
        'info' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    ];
    
    $variantClasses = $variants[$variant] ?? $variants['default'];
    $iconPath = $icons[$variant] ?? $icons['default'];
@endphp

<div {{ $attributes->merge([
    'class' => 'relative rounded-lg border p-4 ' . $variantClasses
]) }}>
    <div class="flex items-start">
        <svg class="h-5 w-5 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconPath }}"/>
        </svg>
        <div class="flex-1">
            @if($title)
                <h3 class="text-sm font-medium mb-1">{{ $title }}</h3>
            @endif
            <div class="text-sm">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>