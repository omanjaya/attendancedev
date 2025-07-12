@props([
    'leftActions' => [],
    'rightActions' => [],
    'threshold' => 50,
    'hapticFeedback' => true,
    'disabled' => false
])

@php
    $swipeId = 'swipe-' . Str::random(6);
@endphp

<div class="relative overflow-hidden touch-manipulation {{ $disabled ? 'pointer-events-none' : '' }}"
     x-data="swipeActions({
        leftActions: {{ json_encode($leftActions) }},
        rightActions: {{ json_encode($rightActions) }},
        threshold: {{ $threshold }},
        hapticFeedback: {{ $hapticFeedback ? 'true' : 'false' }}
     })"
     x-init="init()"
     id="{{ $swipeId }}">
     
    <!-- Left Actions -->
    @if(!empty($leftActions))
    <div class="absolute left-0 top-0 bottom-0 flex items-center"
         :style="`transform: translateX(${Math.min(0, swipeX - actionsWidth)}px)`">
        <div class="flex">
            @foreach($leftActions as $action)
                @php
                    $actionVariant = $action['variant'] ?? 'default';
                    $actionClasses = [
                        'default' => 'bg-muted text-muted-foreground',
                        'primary' => 'bg-primary text-primary-foreground',
                        'success' => 'bg-success text-success-foreground',
                        'warning' => 'bg-warning text-warning-foreground',
                        'destructive' => 'bg-destructive text-destructive-foreground',
                        'info' => 'bg-info text-info-foreground'
                    ];
                    $actionClass = $actionClasses[$actionVariant] ?? $actionClasses['default'];
                @endphp
                
                <button @click="executeAction('{{ $action['action'] }}', 'left')"
                        class="flex flex-col items-center justify-center px-4 h-full min-w-[80px] 
                               {{ $actionClass }} transition-colors"
                        type="button">
                    @if(isset($action['icon']))
                        @if(str_starts_with($action['icon'], 'ti '))
                            <i class="{{ $action['icon'] }} text-lg mb-1"></i>
                        @else
                            <div class="text-lg mb-1">{!! $action['icon'] !!}</div>
                        @endif
                    @endif
                    <span class="text-xs font-medium">{{ $action['label'] }}</span>
                </button>
            @endforeach
        </div>
    </div>
    @endif
    
    <!-- Right Actions -->
    @if(!empty($rightActions))
    <div class="absolute right-0 top-0 bottom-0 flex items-center"
         :style="`transform: translateX(${Math.max(0, swipeX + actionsWidth)}px)`">
        <div class="flex">
            @foreach($rightActions as $action)
                @php
                    $actionVariant = $action['variant'] ?? 'default';
                    $actionClasses = [
                        'default' => 'bg-muted text-muted-foreground',
                        'primary' => 'bg-primary text-primary-foreground',
                        'success' => 'bg-success text-success-foreground',
                        'warning' => 'bg-warning text-warning-foreground',
                        'destructive' => 'bg-destructive text-destructive-foreground',
                        'info' => 'bg-info text-info-foreground'
                    ];
                    $actionClass = $actionClasses[$actionVariant] ?? $actionClasses['default'];
                @endphp
                
                <button @click="executeAction('{{ $action['action'] }}', 'right')"
                        class="flex flex-col items-center justify-center px-4 h-full min-w-[80px] 
                               {{ $actionClass }} transition-colors"
                        type="button">
                    @if(isset($action['icon']))
                        @if(str_starts_with($action['icon'], 'ti '))
                            <i class="{{ $action['icon'] }} text-lg mb-1"></i>
                        @else
                            <div class="text-lg mb-1">{!! $action['icon'] !!}</div>
                        @endif
                    @endif
                    <span class="text-xs font-medium">{{ $action['label'] }}</span>
                </button>
            @endforeach
        </div>
    </div>
    @endif
    
    <!-- Main Content -->
    <div class="relative z-10 bg-background transition-transform duration-200 ease-out"
         :style="`transform: translateX(${swipeX}px)`"
         @touchstart="handleTouchStart($event)"
         @touchmove="handleTouchMove($event)"
         @touchend="handleTouchEnd($event)"
         @mousedown="handleMouseDown($event)"
         @mousemove="handleMouseMove($event)"
         @mouseup="handleMouseUp($event)"
         @mouseleave="handleMouseUp($event)">
        {{ $slot }}
    </div>
    
    <!-- Swipe Indicator -->
    <div x-show="isActive && Math.abs(swipeX) > threshold / 2"
         class="absolute top-1/2 transform -translate-y-1/2 z-20 pointer-events-none
                flex items-center justify-center w-8 h-8 
                bg-primary text-primary-foreground rounded-full shadow-lg"
         :class="{
            'left-4': swipeX > 0,
            'right-4': swipeX < 0
         }">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  :d="swipeX > 0 ? 'M9 5l7 7-7 7' : 'M15 19l-7-7 7-7'"/>
        </svg>
    </div>
</div>

<script>
function swipeActions(config) {
    return {
        swipeX: 0,
        startX: 0,
        isActive: false,
        isDragging: false,
        threshold: config.threshold,
        actionsWidth: 80,
        leftActions: config.leftActions,
        rightActions: config.rightActions,
        hapticFeedback: config.hapticFeedback,
        
        init() {
            // Calculate actions width
            this.actionsWidth = Math.max(
                this.leftActions.length * 80,
                this.rightActions.length * 80
            );
        },
        
        handleTouchStart(event) {
            this.startInteraction(event.touches[0].clientX);
        },
        
        handleTouchMove(event) {
            if (!this.isActive) return;
            event.preventDefault();
            this.updateInteraction(event.touches[0].clientX);
        },
        
        handleTouchEnd(event) {
            this.endInteraction();
        },
        
        handleMouseDown(event) {
            // Only handle left mouse button
            if (event.button !== 0) return;
            this.startInteraction(event.clientX);
            event.preventDefault();
        },
        
        handleMouseMove(event) {
            if (!this.isActive) return;
            this.updateInteraction(event.clientX);
        },
        
        handleMouseUp(event) {
            this.endInteraction();
        },
        
        startInteraction(clientX) {
            this.isActive = true;
            this.startX = clientX;
            this.swipeX = 0;
            
            // Add haptic feedback
            if (this.hapticFeedback && 'vibrate' in navigator) {
                navigator.vibrate(5);
            }
        },
        
        updateInteraction(clientX) {
            if (!this.isActive) return;
            
            let deltaX = clientX - this.startX;
            
            // Limit swipe distance
            const maxSwipe = this.actionsWidth;
            
            // Apply resistance for over-swipe
            if (Math.abs(deltaX) > maxSwipe) {
                const resistance = 0.3;
                const overSwipe = Math.abs(deltaX) - maxSwipe;
                deltaX = deltaX > 0 
                    ? maxSwipe + (overSwipe * resistance)
                    : -maxSwipe - (overSwipe * resistance);
            }
            
            // Only allow swipe if actions exist in that direction
            if (deltaX > 0 && this.leftActions.length === 0) {
                deltaX = 0;
            }
            if (deltaX < 0 && this.rightActions.length === 0) {
                deltaX = 0;
            }
            
            this.swipeX = deltaX;
            this.isDragging = true;
            
            // Haptic feedback at threshold
            if (this.hapticFeedback && Math.abs(deltaX) > this.threshold && !this.thresholdReached) {
                this.thresholdReached = true;
                if ('vibrate' in navigator) {
                    navigator.vibrate(10);
                }
            }
        },
        
        endInteraction() {
            if (!this.isActive) return;
            
            this.isActive = false;
            this.isDragging = false;
            this.thresholdReached = false;
            
            // Snap back if threshold not met
            if (Math.abs(this.swipeX) < this.threshold) {
                this.swipeX = 0;
                return;
            }
            
            // Auto-execute action if threshold exceeded
            if (this.swipeX > this.threshold && this.leftActions.length > 0) {
                // Execute first left action
                this.executeAction(this.leftActions[0].action, 'left');
            } else if (this.swipeX < -this.threshold && this.rightActions.length > 0) {
                // Execute first right action
                this.executeAction(this.rightActions[0].action, 'right');
            }
            
            // Reset position
            setTimeout(() => {
                this.swipeX = 0;
            }, 200);
        },
        
        executeAction(action, direction) {
            // Haptic feedback
            if (this.hapticFeedback && 'vibrate' in navigator) {
                navigator.vibrate(20);
            }
            
            // Dispatch action event
            this.$dispatch('swipe-action', {
                action: action,
                direction: direction,
                element: this.$el
            });
            
            // Reset swipe position
            this.swipeX = 0;
        }
    };
}
</script>

<style>
/* Prevent text selection during swipe */
.swipe-container {
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
}

/* Smooth transitions */
.swipe-content {
    transition: transform 0.2s ease-out;
}

/* Touch improvements */
.touch-manipulation {
    touch-action: manipulation;
}
</style>