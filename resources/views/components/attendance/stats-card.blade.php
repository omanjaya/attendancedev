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

<div class="stats-card-container {{ $href ? $config['border'] : '' }}">
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
                            @switch($trend)
                                @case('up')
                                    <x-icons.trend-up class="stats-card-trend-icon {{ $trendData['color'] }}" />
                                    @break
                                @case('down')
                                    <x-icons.trend-down class="stats-card-trend-icon {{ $trendData['color'] }}" />
                                    @break
                                @case('neutral')
                                    <x-icons.trend-neutral class="stats-card-trend-icon {{ $trendData['color'] }}" />
                                    @break
                            @endswitch
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
                    <div class="stats-card-icon-container {{ $config['icon'] }}">
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