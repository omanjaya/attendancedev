@props([
    'title' => '',
    'value' => 0,
    'subtitle' => '',
    'icon' => '',
    'color' => 'primary',
    'trend' => null,        // 'up', 'down', 'neutral'
    'trendValue' => null,
    'progress' => null,     // 0-100 percentage
    'href' => null,
])

@php
    // Color configurations
    $colorConfig = [
        'primary' => [
            'icon' => 'text-primary bg-primary/10',
            'progress' => 'bg-primary',
            'border' => 'hover:border-primary/20',
        ],
        'success' => [
            'icon' => 'text-success bg-success/10',
            'progress' => 'bg-success',
            'border' => 'hover:border-success/20',
        ],
        'warning' => [
            'icon' => 'text-warning bg-warning/10',
            'progress' => 'bg-warning',
            'border' => 'hover:border-warning/20',
        ],
        'info' => [
            'icon' => 'text-info bg-info/10',
            'progress' => 'bg-info',
            'border' => 'hover:border-info/20',
        ],
        'destructive' => [
            'icon' => 'text-destructive bg-destructive/10',
            'progress' => 'bg-destructive',
            'border' => 'hover:border-destructive/20',
        ],
    ];
    
    $config = $colorConfig[$color] ?? $colorConfig['primary'];
    
    // Trend configurations
    $trendConfig = [
        'up' => [
            'icon' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6',
            'color' => 'text-success',
            'bg' => 'bg-success/10',
            'symbol' => '+',
        ],
        'down' => [
            'icon' => 'M13 17h8m0 0V9m0 8l-8-8-4 4-6-6',
            'color' => 'text-destructive',
            'bg' => 'bg-destructive/10',
            'symbol' => '-',
        ],
        'neutral' => [
            'icon' => 'M8 12h8',
            'color' => 'text-muted-foreground',
            'bg' => 'bg-muted',
            'symbol' => '',
        ],
    ];
    
    $trendData = $trend ? ($trendConfig[$trend] ?? $trendConfig['neutral']) : null;
@endphp

@if($href)
<a href="{{ $href }}" {{ $attributes->merge(['class' => "block"]) }}>
@endif

<div class="rounded-lg border bg-card text-card-foreground shadow-sm hover:shadow-md transition-all duration-200 {{ $href ? $config['border'] : '' }}">
    <div class="p-6">
        <div class="flex items-center justify-between">
            <!-- Main Content -->
            <div class="flex-1">
                <!-- Header with trend -->
                <div class="flex items-center justify-between mb-2">
                    <p class="text-sm font-medium text-muted-foreground uppercase tracking-wide">
                        {{ $title }}
                    </p>
                    
                    @if($trend && $trendValue)
                        <div class="flex items-center space-x-1 px-2 py-1 rounded-full {{ $trendData['bg'] }}">
                            <svg class="h-3 w-3 {{ $trendData['color'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $trendData['icon'] }}"/>
                            </svg>
                            <span class="text-xs font-medium {{ $trendData['color'] }}">
                                {{ $trendData['symbol'] }}{{ $trendValue }}
                            </span>
                        </div>
                    @endif
                </div>
                
                <!-- Value -->
                <p class="text-3xl font-bold text-foreground">{{ $value }}</p>
                
                <!-- Subtitle -->
                @if($subtitle)
                    <div class="flex items-center mt-2">
                        <p class="text-sm text-muted-foreground">{{ $subtitle }}</p>
                    </div>
                @endif
                
                <!-- Progress Bar -->
                @if($progress !== null)
                    <div class="mt-3 bg-muted rounded-full h-2">
                        <div class="{{ $config['progress'] }} h-2 rounded-full transition-all duration-300" 
                             style="width: {{ min(100, max(0, $progress)) }}%"></div>
                    </div>
                @endif
            </div>
            
            <!-- Icon -->
            @if($icon)
                <div class="ml-4">
                    <div class="w-12 h-12 rounded-xl {{ $config['icon'] }} flex items-center justify-center">
                        <i class="{{ $icon }} text-xl"></i>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@if($href)
</a>
@endif