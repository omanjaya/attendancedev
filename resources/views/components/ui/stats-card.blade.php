@props([
    'title' => '',
    'value' => '',
    'change' => null,
    'changeType' => 'increase', // increase, decrease, neutral
    'iconColor' => 'blue',
    'href' => null,
    'loading' => false,
])

@php
    $changeColorClass = match($changeType) {
        'increase' => 'text-green-600',
        'decrease' => 'text-red-600',
        'neutral' => 'text-gray-600',
        default => 'text-gray-600'
    };
    
    $iconColorClass = match($iconColor) {
        'blue' => 'text-blue-600 bg-blue-50',
        'green' => 'text-green-600 bg-green-50', 
        'red' => 'text-red-600 bg-red-50',
        'yellow' => 'text-yellow-600 bg-yellow-50',
        'purple' => 'text-purple-600 bg-purple-50',
        'indigo' => 'text-indigo-600 bg-indigo-50',
        default => 'text-blue-600 bg-blue-50'
    };
    
    $component = $href ? 'a' : 'div';
@endphp

<{{ $component }} 
    @if($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => 'bg-card rounded-lg border border-border p-6 shadow-sm hover:shadow-md transition-shadow' . ($href ? ' hover:bg-muted/50' : '')]) }}
>
    <div class="flex items-center">
        <div class="flex-shrink-0">
            <div class="flex items-center justify-center w-12 h-12 rounded-full {{ $iconColorClass }}">
                @if($loading)
                    <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-current"></div>
                @else
                    {{ $icon ?? '' }}
                @endif
            </div>
        </div>
        
        <div class="ml-5 w-0 flex-1">
            <dl>
                <dt class="text-sm font-medium text-muted-foreground truncate">
                    {{ $title }}
                </dt>
                <dd class="flex items-baseline">
                    <div class="text-2xl font-semibold text-foreground">
                        @if($loading)
                            <div class="h-8 w-20 bg-muted animate-pulse rounded"></div>
                        @else
                            {{ $value }}
                        @endif
                    </div>
                    @if($change && !$loading)
                        <div class="ml-2 flex items-baseline text-sm font-semibold {{ $changeColorClass }}">
                            @if($changeType === 'increase')
                                <svg class="self-center flex-shrink-0 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                                </svg>
                            @elseif($changeType === 'decrease')
                                <svg class="self-center flex-shrink-0 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            @endif
                            <span class="sr-only">{{ $changeType === 'increase' ? 'Increased' : 'Decreased' }} by</span>
                            {{ $change }}
                        </div>
                    @endif
                </dd>
                @if(isset($subtitle))
                    <dd class="text-sm text-muted-foreground mt-1">
                        {{ $subtitle }}
                    </dd>
                @endif
            </dl>
        </div>
    </div>
</{{ $component }}>