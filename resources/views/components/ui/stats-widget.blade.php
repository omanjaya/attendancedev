@props([
    'title' => '',
    'stats' => [],
    'loading' => false,
    'variant' => 'default', // default, compact, detailed
    'showProgress' => false,
    'progressMax' => 100
])

@php
    // Variant configurations
    $variantConfigs = [
        'default' => [
            'container' => 'p-6',
            'grid' => 'grid-cols-2 gap-4',
            'valueSize' => 'text-xl',
            'labelSize' => 'text-sm'
        ],
        'compact' => [
            'container' => 'p-4',
            'grid' => 'grid-cols-2 gap-3',
            'valueSize' => 'text-lg',
            'labelSize' => 'text-xs'
        ],
        'detailed' => [
            'container' => 'p-6',
            'grid' => 'grid-cols-1 gap-4',
            'valueSize' => 'text-2xl',
            'labelSize' => 'text-sm'
        ]
    ];
    
    $config = $variantConfigs[$variant] ?? $variantConfigs['default'];
@endphp

<x-ui.card>
    @if($title)
    <x-slot name="title">{{ $title }}</x-slot>
    @endif
    
    @if($loading)
        <!-- Loading State -->
        <div class="space-y-4">
            @for($i = 0; $i < count($stats); $i++)
                <div class="space-y-2">
                    <x-ui.skeleton height="h-4" width="w-1/2" />
                    <x-ui.skeleton height="h-6" width="w-3/4" />
                    @if($showProgress)
                        <x-ui.skeleton height="h-2" width="w-full" />
                    @endif
                </div>
            @endfor
        </div>
    @else
        <div class="grid {{ $config['grid'] }}">
            @foreach($stats as $stat)
                @php
                    $statValue = $stat['value'] ?? 0;
                    $statLabel = $stat['label'] ?? '';
                    $statColor = $stat['color'] ?? 'default';
                    $statIcon = $stat['icon'] ?? null;
                    $statChange = $stat['change'] ?? null;
                    $statChangeType = $stat['change_type'] ?? 'neutral';
                    $statDescription = $stat['description'] ?? null;
                    
                    // Color classes
                    $colorClasses = [
                        'default' => 'text-foreground',
                        'primary' => 'text-primary',
                        'success' => 'text-success',
                        'warning' => 'text-warning',
                        'destructive' => 'text-destructive',
                        'info' => 'text-info'
                    ];
                    
                    $valueColor = $colorClasses[$statColor] ?? $colorClasses['default'];
                    
                    // Change color classes
                    $changeColors = [
                        'positive' => 'text-success',
                        'negative' => 'text-destructive',
                        'neutral' => 'text-muted-foreground'
                    ];
                    
                    $changeColor = $changeColors[$statChangeType] ?? $changeColors['neutral'];
                @endphp
                
                <div class="space-y-2">
                    <!-- Stat Header -->
                    <div class="flex items-center justify-between">
                        <span class="{{ $config['labelSize'] }} font-medium text-muted-foreground">
                            {{ $statLabel }}
                        </span>
                        @if($statIcon)
                        <div class="flex-shrink-0">
                            @if(str_starts_with($statIcon, 'ti '))
                                <i class="{{ $statIcon }} h-4 w-4 {{ $valueColor }}"></i>
                            @else
                                {!! $statIcon !!}
                            @endif
                        </div>
                        @endif
                    </div>
                    
                    <!-- Stat Value -->
                    <div class="{{ $config['valueSize'] }} font-bold {{ $valueColor }}">
                        {{ $statValue }}
                    </div>
                    
                    <!-- Progress Bar -->
                    @if($showProgress && isset($stat['progress']))
                        <x-ui.progress 
                            :value="$stat['progress']"
                            :max="$progressMax"
                            size="sm"
                            :variant="$statColor" />
                    @endif
                    
                    <!-- Change Indicator -->
                    @if($statChange)
                    <div class="flex items-center space-x-1">
                        @if($statChangeType === 'positive')
                            <svg class="h-3 w-3 {{ $changeColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                        @elseif($statChangeType === 'negative')
                            <svg class="h-3 w-3 {{ $changeColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/>
                            </svg>
                        @else
                            <svg class="h-3 w-3 {{ $changeColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h8"/>
                            </svg>
                        @endif
                        <span class="text-xs {{ $changeColor }}">{{ $statChange }}</span>
                    </div>
                    @endif
                    
                    <!-- Description -->
                    @if($statDescription)
                    <p class="text-xs text-muted-foreground">{{ $statDescription }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</x-ui.card>