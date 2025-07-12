@props([
    'employee' => null,
    'action' => 'checked_in',    // checked_in, checked_out, leave_request
    'time' => null,
    'relativeTime' => null,
    'status' => 'on_time',       // on_time, late, early, completed, pending
    'location' => null,
    'avatar' => null,
])

@php
    // Status configurations
    $statusConfig = [
        'on_time' => [
            'badge' => 'success',
            'label' => 'On Time',
            'iconColor' => 'text-success',
            'iconBg' => 'bg-success/10',
        ],
        'late' => [
            'badge' => 'warning', 
            'label' => 'Late',
            'iconColor' => 'text-warning',
            'iconBg' => 'bg-warning/10',
        ],
        'early' => [
            'badge' => 'info',
            'label' => 'Early', 
            'iconColor' => 'text-info',
            'iconBg' => 'bg-info/10',
        ],
        'completed' => [
            'badge' => 'default',
            'label' => 'Completed',
            'iconColor' => 'text-primary',
            'iconBg' => 'bg-primary/10',
        ],
        'pending' => [
            'badge' => 'warning',
            'label' => 'Pending Approval',
            'iconColor' => 'text-warning',
            'iconBg' => 'bg-warning/10',
        ],
    ];
    
    $config = $statusConfig[$status] ?? $statusConfig['on_time'];
    
    // Action configurations
    $actionConfig = [
        'checked_in' => [
            'icon' => 'M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1',
            'text' => 'Checked in at',
        ],
        'checked_out' => [
            'icon' => 'M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1',
            'text' => 'Checked out at',
        ],
        'leave_request' => [
            'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
            'text' => 'Submitted leave request',
        ],
    ];
    
    $actionData = $actionConfig[$action] ?? $actionConfig['checked_in'];
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center p-4 bg-muted/30 rounded-lg hover:bg-muted/50 transition-colors']) }}>
    <!-- Employee Avatar -->
    <div class="flex-shrink-0">
        <x-ui.avatar 
            :src="$employee['avatar'] ?? null"
            :name="$employee['name'] ?? null"
            :fallback="$avatar"
            size="default"
            class="ring-2 ring-background" />
    </div>
    
    <!-- Activity Details -->
    <div class="ml-4 flex-1 min-w-0">
        <div class="flex items-center justify-between">
            <p class="text-sm font-semibold text-foreground truncate">
                {{ $employee['name'] ?? 'Unknown Employee' }}
            </p>
            <span class="text-xs text-muted-foreground">{{ $relativeTime }}</span>
        </div>
        
        <p class="text-xs text-muted-foreground mt-1">
            {{ $employee['role'] ?? 'Employee' }}
        </p>
        
        <div class="flex items-center mt-2 space-x-3">
            <!-- Action with Icon -->
            <div class="flex items-center">
                <div class="h-6 w-6 rounded-full flex items-center justify-center {{ $config['iconBg'] }}">
                    <svg class="h-3 w-3 {{ $config['iconColor'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $actionData['icon'] }}"/>
                    </svg>
                </div>
                <span class="ml-2 text-sm text-foreground">
                    {{ $actionData['text'] }} 
                    @if($time && $action !== 'leave_request')
                        {{ $time }}
                    @endif
                </span>
            </div>
            
            <!-- Status Badge -->
            @if($action !== 'leave_request')
                <x-ui.badge :variant="$config['badge']">
                    {{ $config['label'] }}
                </x-ui.badge>
            @else
                <x-ui.badge variant="warning">
                    {{ $config['label'] }}
                </x-ui.badge>
            @endif
            
            <!-- Location -->
            @if($location)
                <span class="text-xs text-muted-foreground flex items-center">
                    <svg class="h-3 w-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    {{ $location }}
                </span>
            @endif
        </div>
    </div>
</div>