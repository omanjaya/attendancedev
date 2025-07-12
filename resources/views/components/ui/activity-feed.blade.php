@props([
    'activities' => [],
    'loading' => false,
    'showAvatar' => true,
    'showTime' => true,
    'compact' => false,
    'groupByDate' => false,
    'maxItems' => null,
    'refreshable' => false,
    'emptyMessage' => 'No recent activity'
])

@php
    $displayActivities = $maxItems ? array_slice($activities, 0, $maxItems) : $activities;
    
    // Group activities by date if requested
    $groupedActivities = [];
    if ($groupByDate && !empty($displayActivities)) {
        foreach ($displayActivities as $activity) {
            $date = isset($activity['created_at']) ? Carbon\Carbon::parse($activity['created_at'])->format('Y-m-d') : 'unknown';
            $groupedActivities[$date][] = $activity;
        }
    }
@endphp

<div class="space-y-4" x-data="activityFeed()" x-init="init()">
    @if($refreshable)
    <!-- Refresh Header -->
    <div class="flex items-center justify-between">
        <h3 class="text-sm font-medium text-foreground">Recent Activity</h3>
        <button @click="refresh()" 
                :disabled="isLoading"
                class="p-1.5 hover:bg-muted rounded-md transition-colors disabled:opacity-50"
                title="Refresh">
            <svg class="h-4 w-4" :class="{ 'animate-spin': isLoading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
        </button>
    </div>
    @endif
    
    @if($loading)
        <!-- Loading State -->
        <div class="space-y-4">
            @for($i = 0; $i < 3; $i++)
            <div class="flex items-start space-x-3">
                @if($showAvatar)
                <x-ui.skeleton width="w-8" height="h-8" shape="circle" />
                @endif
                <div class="flex-1 space-y-2">
                    <x-ui.skeleton height="h-4" width="w-3/4" />
                    <x-ui.skeleton height="h-3" width="w-1/2" />
                </div>
            </div>
            @endfor
        </div>
    @elseif(empty($displayActivities))
        <!-- Empty State -->
        <div class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-muted-foreground mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="text-sm text-muted-foreground">{{ $emptyMessage }}</p>
        </div>
    @else
        <!-- Activity List -->
        <div class="space-y-{{ $compact ? '3' : '4' }}">
            @if($groupByDate)
                @foreach($groupedActivities as $date => $dateActivities)
                    <!-- Date Group Header -->
                    <div class="sticky top-0 bg-background/80 backdrop-blur-sm py-2 border-b border-border">
                        <h4 class="text-xs font-medium text-muted-foreground uppercase tracking-wide">
                            {{ $date === date('Y-m-d') ? 'Today' : Carbon\Carbon::parse($date)->format('M j, Y') }}
                        </h4>
                    </div>
                    
                    @foreach($dateActivities as $activity)
                        @include('components.ui.partials.activity-item', ['activity' => $activity])
                    @endforeach
                @endforeach
            @else
                @foreach($displayActivities as $activity)
                    @include('components.ui.partials.activity-item', ['activity' => $activity])
                @endforeach
            @endif
        </div>
    @endif
    
    @if($maxItems && count($activities) > $maxItems)
    <!-- View More -->
    <div class="text-center pt-4 border-t border-border">
        <button class="text-sm text-primary hover:text-primary/80 font-medium transition-colors">
            View {{ count($activities) - $maxItems }} more activities
        </button>
    </div>
    @endif
</div>

<script>
function activityFeed() {
    return {
        isLoading: false,
        
        init() {
            // Initialize activity feed
        },
        
        refresh() {
            this.isLoading = true;
            this.$dispatch('activity-refresh');
            
            // Simulate loading delay
            setTimeout(() => {
                this.isLoading = false;
            }, 1000);
        }
    };
}
</script>