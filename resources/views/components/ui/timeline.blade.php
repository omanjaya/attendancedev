@props([
    'items' => [],
    'variant' => 'default', // default, compact, detailed
    'showConnector' => true,
    'alignment' => 'left', // left, center, right
    'loading' => false
])

@php
    // Variant configurations
    $variantConfigs = [
        'default' => [
            'spacing' => 'space-y-6',
            'iconSize' => 'w-8 h-8',
            'titleSize' => 'text-sm font-medium',
            'timeSize' => 'text-xs',
            'descSize' => 'text-sm'
        ],
        'compact' => [
            'spacing' => 'space-y-4',
            'iconSize' => 'w-6 h-6',
            'titleSize' => 'text-xs font-medium',
            'timeSize' => 'text-xs',
            'descSize' => 'text-xs'
        ],
        'detailed' => [
            'spacing' => 'space-y-8',
            'iconSize' => 'w-10 h-10',
            'titleSize' => 'text-base font-medium',
            'timeSize' => 'text-sm',
            'descSize' => 'text-sm'
        ]
    ];
    
    $config = $variantConfigs[$variant] ?? $variantConfigs['default'];
    
    // Alignment configurations
    $alignmentConfigs = [
        'left' => [
            'container' => '',
            'item' => 'flex items-start space-x-4',
            'content' => 'flex-1',
            'reverse' => false
        ],
        'center' => [
            'container' => 'max-w-2xl mx-auto',
            'item' => 'flex items-start space-x-4',
            'content' => 'flex-1',
            'reverse' => false
        ],
        'right' => [
            'container' => '',
            'item' => 'flex items-start space-x-4 flex-row-reverse',
            'content' => 'flex-1 text-right',
            'reverse' => true
        ]
    ];
    
    $alignConfig = $alignmentConfigs[$alignment] ?? $alignmentConfigs['left'];
@endphp

<div class="{{ $alignConfig['container'] }}">
    @if($loading)
        <!-- Loading State -->
        <div class="{{ $config['spacing'] }}">
            @for($i = 0; $i < 3; $i++)
            <div class="{{ $alignConfig['item'] }}">
                <x-ui.skeleton :width="$config['iconSize']" :height="$config['iconSize']" shape="circle" />
                <div class="flex-1 space-y-2">
                    <x-ui.skeleton height="h-4" width="w-1/3" />
                    <x-ui.skeleton height="h-3" width="w-1/4" />
                    <x-ui.skeleton height="h-4" width="w-full" />
                </div>
            </div>
            @endfor
        </div>
    @else
        <div class="relative {{ $config['spacing'] }}">
            @if($showConnector && count($items) > 1)
            <!-- Connector Line -->
            <div class="absolute {{ $alignment === 'right' ? 'right-0' : 'left-0' }} 
                        {{ $variant === 'compact' ? 'ml-3' : ($variant === 'detailed' ? 'ml-5' : 'ml-4') }}
                        top-0 bottom-0 w-px bg-border"
                 style="{{ $alignment === 'right' ? 'margin-right: ' : 'margin-left: ' }}{{ $variant === 'compact' ? '12px' : ($variant === 'detailed' ? '20px' : '16px') }}">
            </div>
            @endif
            
            @foreach($items as $index => $item)
                @php
                    $itemIcon = $item['icon'] ?? null;
                    $itemTitle = $item['title'] ?? '';
                    $itemTime = $item['time'] ?? '';
                    $itemDate = $item['date'] ?? '';
                    $itemDescription = $item['description'] ?? '';
                    $itemStatus = $item['status'] ?? 'default';
                    $itemType = $item['type'] ?? 'default';
                    $itemContent = $item['content'] ?? null;
                    
                    // Status colors
                    $statusColors = [
                        'default' => 'bg-muted text-muted-foreground',
                        'success' => 'bg-success text-success-foreground',
                        'warning' => 'bg-warning text-warning-foreground',
                        'destructive' => 'bg-destructive text-destructive-foreground',
                        'info' => 'bg-info text-info-foreground',
                        'primary' => 'bg-primary text-primary-foreground'
                    ];
                    
                    $iconClasses = $statusColors[$itemStatus] ?? $statusColors['default'];
                @endphp
                
                <div class="relative {{ $alignConfig['item'] }}">
                    <!-- Icon/Avatar -->
                    <div class="relative z-10 flex items-center justify-center {{ $config['iconSize'] }} rounded-full {{ $iconClasses }} border-2 border-background shadow-sm">
                        @if($itemIcon)
                            @if(str_starts_with($itemIcon, 'ti '))
                                <i class="{{ $itemIcon }} {{ $variant === 'compact' ? 'text-sm' : ($variant === 'detailed' ? 'text-lg' : 'text-base') }}"></i>
                            @elseif(str_starts_with($itemIcon, 'http') || str_starts_with($itemIcon, '/'))
                                <img src="{{ $itemIcon }}" alt="{{ $itemTitle }}" class="w-full h-full rounded-full object-cover">
                            @else
                                {!! $itemIcon !!}
                            @endif
                        @else
                            <div class="w-2 h-2 bg-current rounded-full"></div>
                        @endif
                    </div>
                    
                    <!-- Content -->
                    <div class="{{ $alignConfig['content'] }} min-w-0">
                        <!-- Header -->
                        <div class="flex items-center {{ $alignment === 'right' ? 'justify-end' : 'justify-start' }} gap-2 mb-1">
                            <h3 class="{{ $config['titleSize'] }} text-foreground">{{ $itemTitle }}</h3>
                            @if($itemTime || $itemDate)
                            <time class="{{ $config['timeSize'] }} text-muted-foreground">
                                {{ $itemDate }}{{ $itemDate && $itemTime ? ' at ' : '' }}{{ $itemTime }}
                            </time>
                            @endif
                        </div>
                        
                        <!-- Description -->
                        @if($itemDescription)
                        <p class="{{ $config['descSize'] }} text-muted-foreground mb-2">{{ $itemDescription }}</p>
                        @endif
                        
                        <!-- Custom Content -->
                        @if($itemContent)
                        <div class="mt-2">
                            {!! $itemContent !!}
                        </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>