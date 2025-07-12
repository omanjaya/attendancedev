@props([
    'title' => '',
    'value' => '',
    'description' => null,
    'icon' => null,
    'trend' => null, // 'up', 'down', 'neutral'
    'trendValue' => null,
    'trendLabel' => null,
    'variant' => 'default', // default, success, warning, destructive, info
    'size' => 'default', // sm, default, lg
    'loading' => false,
    'href' => null
])

@php
    // Size classes
    $sizeClasses = [
        'sm' => [
            'container' => 'p-4',
            'value' => 'text-xl',
            'title' => 'text-xs',
            'icon' => 'h-4 w-4',
            'iconContainer' => 'w-8 h-8'
        ],
        'default' => [
            'container' => 'p-6',
            'value' => 'text-2xl',
            'title' => 'text-sm',
            'icon' => 'h-5 w-5',
            'iconContainer' => 'w-10 h-10'
        ],
        'lg' => [
            'container' => 'p-8',
            'value' => 'text-3xl',
            'title' => 'text-base',
            'icon' => 'h-6 w-6',
            'iconContainer' => 'w-12 h-12'
        ]
    ];
    
    $sizeConfig = $sizeClasses[$size] ?? $sizeClasses['default'];
    
    // Variant classes
    $variantClasses = [
        'default' => ['border' => 'border-border', 'bg' => 'bg-card', 'iconBg' => 'bg-primary/10', 'iconColor' => 'text-primary'],
        'success' => ['border' => 'border-success/20', 'bg' => 'bg-card', 'iconBg' => 'bg-success/10', 'iconColor' => 'text-success'],
        'warning' => ['border' => 'border-warning/20', 'bg' => 'bg-card', 'iconBg' => 'bg-warning/10', 'iconColor' => 'text-warning'],
        'destructive' => ['border' => 'border-destructive/20', 'bg' => 'bg-card', 'iconBg' => 'bg-destructive/10', 'iconColor' => 'text-destructive'],
        'info' => ['border' => 'border-info/20', 'bg' => 'bg-card', 'iconBg' => 'bg-info/10', 'iconColor' => 'text-info']
    ];
    
    $variantConfig = $variantClasses[$variant] ?? $variantClasses['default'];
    
    // Trend classes
    $trendClasses = [
        'up' => 'text-success',
        'down' => 'text-destructive',
        'neutral' => 'text-muted-foreground'
    ];
    
    $trendClass = $trend ? ($trendClasses[$trend] ?? $trendClasses['neutral']) : '';
    
    // Container classes
    $containerClasses = "relative rounded-lg border shadow-sm transition-all duration-200 {$variantConfig['border']} {$variantConfig['bg']} {$sizeConfig['container']}";
    
    if ($href) {
        $containerClasses .= ' hover:shadow-md cursor-pointer card-hover';
    } else {
        $containerClasses .= ' hover:shadow-md';
    }
@endphp

@if($href)
    <a href="{{ $href }}" class="{{ $containerClasses }}">
@else
    <div class="{{ $containerClasses }}">
@endif
    @if($loading)
        <!-- Loading State -->
        <div class="space-y-3">
            <x-ui.skeleton height="h-4" width="w-1/2" />
            <x-ui.skeleton height="h-8" width="w-3/4" />
            <x-ui.skeleton height="h-3" width="w-1/3" />
        </div>
    @else
        <div class="flex items-center justify-between">
            <div class="flex-1 min-w-0">
                <!-- Title -->
                <p class="{{ $sizeConfig['title'] }} font-medium text-muted-foreground uppercase tracking-wide mb-1">
                    {{ $title }}
                </p>
                
                <!-- Value -->
                <p class="{{ $sizeConfig['value'] }} font-bold text-foreground mb-2">
                    {{ $value }}
                </p>
                
                <!-- Description -->
                @if($description)
                <p class="text-xs text-muted-foreground">
                    {{ $description }}
                </p>
                @endif
                
                <!-- Trend -->
                @if($trend && ($trendValue || $trendLabel))
                <div class="flex items-center mt-2 space-x-1">
                    @if($trend === 'up')
                        <svg class="h-3 w-3 {{ $trendClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    @elseif($trend === 'down')
                        <svg class="h-3 w-3 {{ $trendClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/>
                        </svg>
                    @else
                        <svg class="h-3 w-3 {{ $trendClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h8"/>
                        </svg>
                    @endif
                    
                    <span class="text-xs {{ $trendClass }}">
                        @if($trendValue){{ $trendValue }} @endif
                        @if($trendLabel){{ $trendLabel }}@endif
                    </span>
                </div>
                @endif
            </div>
            
            <!-- Icon -->
            @if($icon)
            <div class="ml-4">
                <div class="{{ $sizeConfig['iconContainer'] }} rounded-xl {{ $variantConfig['iconBg'] }} {{ $variantConfig['iconColor'] }} flex items-center justify-center">
                    @if(str_starts_with($icon, 'ti '))
                        <i class="{{ $icon }} {{ $sizeConfig['icon'] }}"></i>
                    @else
                        {!! $icon !!}
                    @endif
                </div>
            </div>
            @endif
        </div>
    @endif
@if($href)
    </a>
@else
    </div>
@endif