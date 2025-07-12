@props([
    'value' => 0,
    'max' => 100,
    'size' => 'default', // sm, default, lg
    'variant' => 'default', // default, success, warning, destructive, info
    'label' => null,
    'showPercentage' => false,
    'showValue' => false,
    'animated' => false,
    'striped' => false
])

@php
    $percentage = $max > 0 ? min(100, ($value / $max) * 100) : 0;
    
    // Size classes
    $sizeClasses = [
        'sm' => 'h-1',
        'default' => 'h-2',
        'lg' => 'h-3'
    ];
    
    $sizeClass = $sizeClasses[$size] ?? $sizeClasses['default'];
    
    // Variant classes
    $variantClasses = [
        'default' => 'bg-primary',
        'success' => 'bg-success',
        'warning' => 'bg-warning',
        'destructive' => 'bg-destructive',
        'info' => 'bg-info'
    ];
    
    $variantClass = $variantClasses[$variant] ?? $variantClasses['default'];
    
    // Additional classes
    $progressClasses = $variantClass;
    if ($animated) {
        $progressClasses .= ' progress-animated';
    }
    if ($striped) {
        $progressClasses .= ' progress-striped';
    }
@endphp

<div class="space-y-2">
    @if($label || $showPercentage || $showValue)
    <div class="flex items-center justify-between text-sm">
        @if($label)
        <span class="font-medium text-foreground">{{ $label }}</span>
        @endif
        
        <div class="flex items-center gap-2 text-muted-foreground">
            @if($showValue)
            <span>{{ $value }}/{{ $max }}</span>
            @endif
            @if($showPercentage)
            <span>{{ number_format($percentage, 1) }}%</span>
            @endif
        </div>
    </div>
    @endif
    
    <div class="w-full bg-muted rounded-full {{ $sizeClass }} overflow-hidden">
        <div 
            class="{{ $progressClasses }} {{ $sizeClass }} rounded-full transition-all duration-500 ease-out"
            style="width: {{ $percentage }}%"
            role="progressbar"
            aria-valuenow="{{ $value }}"
            aria-valuemin="0"
            aria-valuemax="{{ $max }}"
            @if($label) aria-label="{{ $label }}" @endif>
        </div>
    </div>
</div>

@if($animated || $striped)
<style>
@keyframes progress-animation {
    0% {
        background-position: 1rem 0;
    }
    100% {
        background-position: 0 0;
    }
}

.progress-striped {
    background-image: linear-gradient(
        45deg,
        rgba(255, 255, 255, 0.15) 25%,
        transparent 25%,
        transparent 50%,
        rgba(255, 255, 255, 0.15) 50%,
        rgba(255, 255, 255, 0.15) 75%,
        transparent 75%,
        transparent
    );
    background-size: 1rem 1rem;
}

.progress-animated {
    animation: progress-animation 1s linear infinite;
}
</style>
@endif