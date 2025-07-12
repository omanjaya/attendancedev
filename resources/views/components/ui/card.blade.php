@props([
    // Content props
    'title' => null,
    'subtitle' => null,
    'value' => null,
    'icon' => null,
    
    // Style props
    'color' => 'primary',
    'variant' => 'default', // default, metric, simple, compact, featured, interactive
    'shadow' => 'sm',
    'padding' => 'md',
    'hover' => false,
    'loading' => false,
    
    // Action props  
    'actions' => null,
    'footer' => null,
    'id' => null
])

@php
    // Color classes using CSS variables for theme support
    $colorClasses = [
        'primary' => 'text-primary bg-primary/10',
        'success' => 'text-success bg-success/10', 
        'warning' => 'text-warning bg-warning/10',
        'destructive' => 'text-destructive bg-destructive/10',
        'info' => 'text-info bg-info/10',
        'muted' => 'text-muted-foreground bg-muted',
    ];
    
    // Shadow classes
    $shadowClasses = [
        'none' => '',
        'sm' => 'shadow-sm',
        'md' => 'shadow-md', 
        'lg' => 'shadow-lg',
    ];
    
    // Padding classes
    $paddingClasses = [
        'none' => '',
        'sm' => 'p-4',
        'md' => 'p-6', 
        'lg' => 'p-8',
    ];
    
    $colorClass = $colorClasses[$color] ?? $colorClasses['primary'];
    $shadowClass = $shadowClasses[$shadow] ?? $shadowClasses['sm'];
    $paddingClass = $paddingClasses[$padding] ?? $paddingClasses['md'];
    
    $hoverClass = $hover ? 'transition-colors hover:bg-accent/5' : '';
    
    // Use pure Tailwind classes
    $baseClasses = "rounded-lg border bg-card text-card-foreground $shadowClass $hoverClass";
@endphp

<div {{ $attributes->merge(['class' => $baseClasses]) }}>
    {{-- Loading overlay --}}
    @if($loading)
        <div class="absolute inset-0 bg-background/80 backdrop-blur-sm flex items-center justify-center z-10 rounded-lg">
            <div class="animate-spin rounded-full h-8 w-8 border-2 border-primary border-t-transparent"></div>
        </div>
    @endif

    {{-- Metric variant - for dashboard stats --}}
    @if($variant === 'metric' && $value !== null)
        <div class="{{ $paddingClass }}">
            <div class="flex items-center">
                @if($icon)
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 rounded-lg {{ $colorClass }} flex items-center justify-center">
                        <i class="{{ $icon }} text-xl"></i>
                    </div>
                </div>
                @endif
                
                <div class="@if($icon) ml-4 @endif flex-1">
                    @if($title)
                    <div class="text-sm font-medium text-muted-foreground uppercase tracking-wide">{{ $title }}</div>
                    @endif
                    <div class="text-2xl font-bold text-foreground" @if($id) id="{{ $id }}" @endif>{{ $value }}</div>
                    @if($subtitle)
                    <div class="text-sm text-muted-foreground">{{ $subtitle }}</div>
                    @endif
                </div>
                
                @if($actions)
                <div class="ml-4 flex-shrink-0">{{ $actions }}</div>
                @endif
            </div>
        </div>
    
    {{-- Compact variant - minimal padding and condensed layout --}}
    @elseif($variant === 'compact')
        <div class="p-4">
            @if($title || $subtitle)
            <div class="mb-3">
                @if($title)
                <h4 class="text-sm font-medium text-foreground">{{ $title }}</h4>
                @endif
                @if($subtitle)
                <p class="text-xs text-muted-foreground">{{ $subtitle }}</p>
                @endif
            </div>
            @endif
            {{ $slot }}
        </div>
    
    {{-- Featured variant - highlighted card with accent border --}}
    @elseif($variant === 'featured')
        <div class="border-l-4 border-l-primary {{ $paddingClass }}">
            @if($title || $subtitle || $actions)
            <div class="flex items-start justify-between mb-4">
                <div class="flex-1">
                    @if($title)
                    <h3 class="text-lg font-semibold text-foreground mb-1">{{ $title }}</h3>
                    @endif
                    @if($subtitle)
                    <p class="text-sm text-muted-foreground">{{ $subtitle }}</p>
                    @endif
                </div>
                @if($actions)
                <div class="ml-4">{{ $actions }}</div>
                @endif
            </div>
            @endif
            {{ $slot }}
        </div>
    
    {{-- Interactive variant - hover effects and cursor pointer --}}
    @elseif($variant === 'interactive')
        <div class="cursor-pointer transition-all duration-200 hover:shadow-md hover:scale-[1.02] {{ $paddingClass }}">
            @if($title || $subtitle)
            <div class="mb-4">
                @if($title)
                <h3 class="text-lg font-medium text-foreground group-hover:text-primary transition-colors">{{ $title }}</h3>
                @endif
                @if($subtitle)
                <p class="text-sm text-muted-foreground">{{ $subtitle }}</p>
                @endif
            </div>
            @endif
            {{ $slot }}
        </div>
    
    {{-- Simple variant - basic card with just content --}}
    @elseif($variant === 'simple')
        <div class="{{ $paddingClass }}">
            {{ $slot }}
        </div>
    
    {{-- Default variant - full featured card --}}
    @else
        {{-- Header section --}}
        @if($title || $subtitle || $actions)
        <div class="flex flex-col space-y-1.5 @if($paddingClass === 'p-4') p-4 @elseif($paddingClass === 'p-8') p-8 @else p-6 @endif @if($slot->isNotEmpty()) pb-0 @endif">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    @if($icon)
                    <div class="flex items-center mb-2">
                        <div class="w-8 h-8 rounded {{ $colorClass }} flex items-center justify-center mr-3">
                            <i class="{{ $icon }} text-sm"></i>
                        </div>
                        @if($title)
                        <h3 class="text-2xl font-semibold leading-none tracking-tight">{{ $title }}</h3>
                        @endif
                    </div>
                    @elseif($title)
                    <h3 class="text-2xl font-semibold leading-none tracking-tight">{{ $title }}</h3>
                    @endif
                    
                    @if($subtitle)
                    <p class="text-sm text-muted-foreground">{{ $subtitle }}</p>
                    @endif
                    
                    @if($value !== null)
                    <p class="text-2xl font-bold text-foreground mt-2" @if($id) id="{{ $id }}" @endif>{{ $value }}</p>
                    @endif
                </div>
                
                @if($actions)
                <div class="ml-4 flex-shrink-0">{{ $actions }}</div>
                @endif
            </div>
        </div>
        @endif
        
        {{-- Content section --}}
        @if($slot->isNotEmpty())
            @if($title || $subtitle || $actions)
            <x-ui.separator />
            @endif
            <div class="@if($paddingClass === 'p-4') p-4 @elseif($paddingClass === 'p-8') p-8 @else p-6 @endif pt-0">{{ $slot }}</div>
        @endif
        
        {{-- Footer section --}}
        @if($footer)
        <x-ui.separator />
        <div class="flex items-center @if($paddingClass === 'p-4') p-4 @elseif($paddingClass === 'p-8') p-8 @else p-6 @endif pt-0 bg-muted/50 rounded-b-lg">
            {{ $footer }}
        </div>
        @endif
    @endif
</div>