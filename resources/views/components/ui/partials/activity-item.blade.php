@php
    $activityType = $activity['type'] ?? 'default';
    $activityUser = $activity['user'] ?? null;
    $activityTitle = $activity['title'] ?? '';
    $activityDescription = $activity['description'] ?? '';
    $activityTime = $activity['created_at'] ?? $activity['time'] ?? null;
    $activityIcon = $activity['icon'] ?? null;
    $activityColor = $activity['color'] ?? 'default';
    $activityMeta = $activity['meta'] ?? [];
    
    // Activity type configurations
    $typeConfigs = [
        'login' => ['icon' => 'ti ti-login', 'color' => 'success'],
        'logout' => ['icon' => 'ti ti-logout', 'color' => 'muted'],
        'checkin' => ['icon' => 'ti ti-clock-play', 'color' => 'success'],
        'checkout' => ['icon' => 'ti ti-clock-stop', 'color' => 'info'],
        'leave_request' => ['icon' => 'ti ti-calendar-off', 'color' => 'warning'],
        'leave_approved' => ['icon' => 'ti ti-calendar-check', 'color' => 'success'],
        'leave_rejected' => ['icon' => 'ti ti-calendar-x', 'color' => 'destructive'],
        'schedule_change' => ['icon' => 'ti ti-calendar-time', 'color' => 'info'],
        'system' => ['icon' => 'ti ti-settings', 'color' => 'muted'],
        'error' => ['icon' => 'ti ti-alert-circle', 'color' => 'destructive'],
        'warning' => ['icon' => 'ti ti-alert-triangle', 'color' => 'warning'],
        'info' => ['icon' => 'ti ti-info-circle', 'color' => 'info'],
        'default' => ['icon' => 'ti ti-point', 'color' => 'muted']
    ];
    
    $typeConfig = $typeConfigs[$activityType] ?? $typeConfigs['default'];
    $finalIcon = $activityIcon ?: $typeConfig['icon'];
    $finalColor = $activityColor !== 'default' ? $activityColor : $typeConfig['color'];
    
    // Color classes
    $colorClasses = [
        'default' => 'bg-muted text-muted-foreground',
        'primary' => 'bg-primary text-primary-foreground',
        'success' => 'bg-success text-success-foreground',
        'warning' => 'bg-warning text-warning-foreground',
        'destructive' => 'bg-destructive text-destructive-foreground',
        'info' => 'bg-info text-info-foreground',
        'muted' => 'bg-muted text-muted-foreground'
    ];
    
    $iconClasses = $colorClasses[$finalColor] ?? $colorClasses['muted'];
    
    // Format time
    $formattedTime = '';
    if ($activityTime) {
        try {
            $carbon = Carbon\Carbon::parse($activityTime);
            $formattedTime = $carbon->diffForHumans();
        } catch (Exception $e) {
            $formattedTime = $activityTime;
        }
    }
@endphp

<div class="flex items-start space-x-3 {{ $compact ? 'py-2' : 'py-3' }} group hover:bg-muted/30 rounded-lg transition-colors">
    @if($showAvatar && $activityUser)
        <!-- User Avatar -->
        <x-ui.avatar 
            :name="$activityUser['name'] ?? 'User'"
            :image="$activityUser['avatar'] ?? null"
            size="{{ $compact ? 'sm' : 'md' }}" />
    @elseif($finalIcon)
        <!-- Activity Icon -->
        <div class="flex-shrink-0 flex items-center justify-center {{ $compact ? 'w-6 h-6' : 'w-8 h-8' }} rounded-full {{ $iconClasses }}">
            @if(str_starts_with($finalIcon, 'ti '))
                <i class="{{ $finalIcon }} {{ $compact ? 'text-xs' : 'text-sm' }}"></i>
            @else
                {!! $finalIcon !!}
            @endif
        </div>
    @endif
    
    <div class="flex-1 min-w-0">
        <!-- Activity Content -->
        <div class="flex items-start justify-between">
            <div class="flex-1 min-w-0">
                <!-- Title -->
                <p class="{{ $compact ? 'text-xs' : 'text-sm' }} font-medium text-foreground">
                    {{ $activityTitle }}
                </p>
                
                <!-- Description -->
                @if($activityDescription)
                <p class="{{ $compact ? 'text-xs' : 'text-sm' }} text-muted-foreground mt-1">
                    {{ $activityDescription }}
                </p>
                @endif
                
                <!-- Meta Information -->
                @if(!empty($activityMeta))
                <div class="flex items-center space-x-4 mt-2">
                    @foreach($activityMeta as $key => $value)
                    <span class="text-xs text-muted-foreground">
                        <span class="font-medium">{{ ucfirst($key) }}:</span> {{ $value }}
                    </span>
                    @endforeach
                </div>
                @endif
            </div>
            
            <!-- Time -->
            @if($showTime && $formattedTime)
            <time class="{{ $compact ? 'text-xs' : 'text-xs' }} text-muted-foreground ml-2 flex-shrink-0">
                {{ $formattedTime }}
            </time>
            @endif
        </div>
    </div>
</div>