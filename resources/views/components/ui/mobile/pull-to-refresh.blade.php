@props([
    'threshold' => 60,
    'resistance' => 2.5,
    'snapBack' => true,
    'hapticFeedback' => true,
    'showSpinner' => true,
    'refreshText' => 'Pull to refresh',
    'releaseText' => 'Release to refresh',
    'refreshingText' => 'Refreshing...'
])

@php
    $pullId = 'pull-' . Str::random(6);
@endphp

<div class="relative overflow-hidden"
     x-data="pullToRefresh({
        threshold: {{ $threshold }},
        resistance: {{ $resistance }},
        snapBack: {{ $snapBack ? 'true' : 'false' }},
        hapticFeedback: {{ $hapticFeedback ? 'true' : 'false' }}
     })"
     x-init="init()"
     id="{{ $pullId }}">
     
    <!-- Pull Indicator -->
    <div class="absolute top-0 left-0 right-0 flex items-center justify-center 
                bg-background/95 backdrop-blur-sm border-b border-border z-10"
         :style="`transform: translateY(${Math.min(0, pullY - 60)}px); opacity: ${pullOpacity}`"
         :class="{ 'transition-transform duration-300': !isDragging }">
        
        <div class="flex items-center space-x-3 py-4">
            <!-- Spinner or Arrow -->
            <div class="relative w-6 h-6">
                <!-- Loading Spinner -->
                <div x-show="isRefreshing" 
                     class="animate-spin rounded-full h-6 w-6 border-2 border-primary border-t-transparent">
                </div>
                
                <!-- Arrow Icon -->
                <div x-show="!isRefreshing" 
                     class="flex items-center justify-center w-6 h-6 transition-transform duration-200"
                     :class="{ 'rotate-180': pullY >= threshold }">
                    <svg class="w-4 h-4 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                    </svg>
                </div>
            </div>
            
            <!-- Status Text -->
            <span class="text-sm font-medium text-muted-foreground">
                <span x-show="!isRefreshing && pullY < threshold">{{ $refreshText }}</span>
                <span x-show="!isRefreshing && pullY >= threshold">{{ $releaseText }}</span>
                <span x-show="isRefreshing">{{ $refreshingText }}</span>
            </span>
        </div>
    </div>
    
    <!-- Content Container -->
    <div class="transition-transform duration-200 ease-out"
         :style="`transform: translateY(${Math.max(0, pullY)}px)`"
         :class="{ 'transition-none': isDragging }"
         @touchstart="handleTouchStart($event)"
         @touchmove="handleTouchMove($event)"
         @touchend="handleTouchEnd($event)"
         @scroll="handleScroll($event)">
        {{ $slot }}
    </div>
</div>

<script>
function pullToRefresh(config) {
    return {
        pullY: 0,
        startY: 0,
        isDragging: false,
        isRefreshing: false,
        canPull: true,
        threshold: config.threshold,
        resistance: config.resistance,
        snapBack: config.snapBack,
        hapticFeedback: config.hapticFeedback,
        
        get pullOpacity() {
            return Math.min(1, Math.max(0, this.pullY / this.threshold));
        },
        
        init() {
            // Listen for manual refresh trigger
            this.$el.addEventListener('trigger-refresh', () => {
                this.triggerRefresh();
            });
            
            // Handle refresh completion
            this.$el.addEventListener('refresh-complete', () => {
                this.completeRefresh();
            });
        },
        
        handleTouchStart(event) {
            // Only start if we're at the top of the scroll container
            const scrollTop = this.getScrollTop();
            if (scrollTop > 0) {
                this.canPull = false;
                return;
            }
            
            this.canPull = true;
            this.startY = event.touches[0].clientY;
            this.isDragging = false;
        },
        
        handleTouchMove(event) {
            if (!this.canPull || this.isRefreshing) return;
            
            // Check if we're still at the top
            const scrollTop = this.getScrollTop();
            if (scrollTop > 0) {
                this.canPull = false;
                this.pullY = 0;
                return;
            }
            
            const currentY = event.touches[0].clientY;
            let deltaY = currentY - this.startY;
            
            // Only allow downward pull
            if (deltaY <= 0) {
                this.pullY = 0;
                return;
            }
            
            // Apply resistance
            deltaY = deltaY / config.resistance;
            
            // Limit maximum pull distance
            const maxPull = this.threshold * 1.5;
            deltaY = Math.min(deltaY, maxPull);
            
            this.pullY = deltaY;
            this.isDragging = true;
            
            // Prevent default scrolling when pulling
            if (deltaY > 10) {
                event.preventDefault();
            }
            
            // Haptic feedback at threshold
            if (this.hapticFeedback && deltaY >= this.threshold && !this.thresholdReached) {
                this.thresholdReached = true;
                if ('vibrate' in navigator) {
                    navigator.vibrate(10);
                }
            } else if (deltaY < this.threshold) {
                this.thresholdReached = false;
            }
        },
        
        handleTouchEnd(event) {
            if (!this.canPull || this.isRefreshing) return;
            
            this.isDragging = false;
            
            // Trigger refresh if threshold met
            if (this.pullY >= this.threshold) {
                this.triggerRefresh();
            } else {
                // Snap back
                this.pullY = 0;
            }
            
            this.thresholdReached = false;
        },
        
        handleScroll(event) {
            // Reset pull state if user scrolls down
            const scrollTop = this.getScrollTop();
            if (scrollTop > 0 && this.pullY > 0) {
                this.pullY = 0;
                this.canPull = false;
            }
        },
        
        triggerRefresh() {
            if (this.isRefreshing) return;
            
            this.isRefreshing = true;
            this.pullY = this.threshold;
            
            // Haptic feedback
            if (this.hapticFeedback && 'vibrate' in navigator) {
                navigator.vibrate(20);
            }
            
            // Dispatch refresh event
            this.$dispatch('pull-to-refresh', {
                element: this.$el,
                complete: () => this.completeRefresh()
            });
        },
        
        completeRefresh() {
            this.isRefreshing = false;
            
            if (this.snapBack) {
                // Animate back to original position
                setTimeout(() => {
                    this.pullY = 0;
                }, 100);
            }
        },
        
        getScrollTop() {
            // Get scroll position of the content container
            const container = this.$el.querySelector('[data-scroll-container]') || this.$el;
            return container.scrollTop || window.pageYOffset || document.documentElement.scrollTop;
        }
    };
}

// Global method to trigger refresh programmatically
window.triggerPullRefresh = function(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.dispatchEvent(new CustomEvent('trigger-refresh'));
    }
};

// Global method to complete refresh programmatically
window.completePullRefresh = function(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.dispatchEvent(new CustomEvent('refresh-complete'));
    }
};
</script>

<style>
/* Improve touch scrolling on iOS */
.pull-to-refresh-container {
    -webkit-overflow-scrolling: touch;
    overscroll-behavior-y: contain;
}

/* Prevent bounce scrolling interference on iOS */
.pull-to-refresh-content {
    overscroll-behavior-y: none;
}

/* Smooth momentum scrolling */
@supports (overscroll-behavior: contain) {
    .pull-to-refresh-container {
        overscroll-behavior: contain;
    }
}
</style>