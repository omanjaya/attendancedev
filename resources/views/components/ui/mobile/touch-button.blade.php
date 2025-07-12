@props([
    'variant' => 'default',
    'size' => 'default',
    'type' => 'button',
    'href' => null,
    'disabled' => false,
    'loading' => false,
    'haptic' => true,
    'ripple' => true,
    'longPress' => false,
    'longPressDelay' => 800,
    'pressScale' => 0.95
])

@php
    // Touch-optimized size classes (minimum 44px touch target)
    $sizeClasses = [
        'sm' => 'h-11 px-4 text-sm min-w-[44px]',
        'default' => 'h-12 px-6 text-base min-w-[48px]',
        'lg' => 'h-14 px-8 text-lg min-w-[56px]',
        'icon' => 'h-12 w-12 p-0',
        'icon-sm' => 'h-11 w-11 p-0',
        'icon-lg' => 'h-14 w-14 p-0'
    ];
    
    // Variant classes
    $variantClasses = [
        'default' => 'bg-primary text-primary-foreground hover:bg-primary/90 active:bg-primary/80',
        'secondary' => 'bg-secondary text-secondary-foreground hover:bg-secondary/80 active:bg-secondary/70',
        'outline' => 'border border-input bg-background hover:bg-accent hover:text-accent-foreground active:bg-accent/80',
        'ghost' => 'hover:bg-accent hover:text-accent-foreground active:bg-accent/80',
        'destructive' => 'bg-destructive text-destructive-foreground hover:bg-destructive/90 active:bg-destructive/80',
        'success' => 'bg-success text-success-foreground hover:bg-success/90 active:bg-success/80',
        'warning' => 'bg-warning text-warning-foreground hover:bg-warning/90 active:bg-warning/80',
        'info' => 'bg-info text-info-foreground hover:bg-info/90 active:bg-info/80'
    ];
    
    $sizeClass = $sizeClasses[$size] ?? $sizeClasses['default'];
    $variantClass = $variantClasses[$variant] ?? $variantClasses['default'];
    
    $baseClasses = 'inline-flex items-center justify-center rounded-md font-medium 
                    transition-all duration-200 ease-out touch-manipulation select-none
                    focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2
                    disabled:pointer-events-none disabled:opacity-50
                    active:scale-[' . $pressScale . '] relative overflow-hidden';
    
    $classes = "{$baseClasses} {$sizeClass} {$variantClass}";
    
    $tag = $href ? 'a' : 'button';
@endphp

<{{ $tag }}
    @if($href) href="{{ $href }}" @endif
    @if(!$href) type="{{ $type }}" @endif
    @if($disabled) disabled @endif
    class="{{ $classes }}"
    x-data="touchButton({
        haptic: {{ $haptic ? 'true' : 'false' }},
        ripple: {{ $ripple ? 'true' : 'false' }},
        longPress: {{ $longPress ? 'true' : 'false' }},
        longPressDelay: {{ $longPressDelay }},
        disabled: {{ $disabled ? 'true' : 'false' }},
        loading: {{ $loading ? 'true' : 'false' }}
    })"
    x-init="init()"
    @touchstart="handleTouchStart($event)"
    @touchend="handleTouchEnd($event)"
    @touchcancel="handleTouchCancel($event)"
    @mousedown="handleMouseDown($event)"
    @mouseup="handleMouseUp($event)"
    @mouseleave="handleMouseLeave($event)"
    @click="handleClick($event)"
    {{ $attributes }}>
    
    @if($ripple)
    <!-- Ripple Effect Container -->
    <span class="absolute inset-0 overflow-hidden rounded-md pointer-events-none">
        <template x-for="ripple in ripples" :key="ripple.id">
            <span class="absolute rounded-full bg-current opacity-20 pointer-events-none animate-ping"
                  :style="`left: ${ripple.x}px; top: ${ripple.y}px; width: ${ripple.size}px; height: ${ripple.size}px; transform: translate(-50%, -50%)`">
            </span>
        </template>
    </span>
    @endif
    
    <!-- Loading Spinner -->
    @if($loading)
    <div class="absolute inset-0 flex items-center justify-center">
        <div class="animate-spin rounded-full h-4 w-4 border-2 border-current border-t-transparent opacity-60"></div>
    </div>
    @endif
    
    <!-- Button Content -->
    <div class="flex items-center justify-center space-x-2 {{ $loading ? 'opacity-0' : '' }} transition-opacity">
        {{ $slot }}
    </div>
    
    <!-- Long Press Progress Indicator -->
    @if($longPress)
    <div x-show="isLongPressing" 
         class="absolute inset-0 pointer-events-none">
        <div class="absolute inset-0 bg-current opacity-10 rounded-md origin-left transition-transform duration-300 ease-linear"
             :style="`transform: scaleX(${longPressProgress})`">
        </div>
    </div>
    @endif
</{{ $tag }}>

<script>
function touchButton(config) {
    return {
        haptic: config.haptic,
        ripple: config.ripple,
        longPress: config.longPress,
        longPressDelay: config.longPressDelay,
        disabled: config.disabled,
        loading: config.loading,
        
        ripples: [],
        isPressed: false,
        isLongPressing: false,
        longPressProgress: 0,
        longPressTimer: null,
        longPressInterval: null,
        
        init() {
            // Clean up old ripples periodically
            setInterval(() => {
                this.ripples = this.ripples.filter(ripple => 
                    Date.now() - ripple.created < 1000
                );
            }, 1000);
        },
        
        handleTouchStart(event) {
            if (this.disabled || this.loading) return;
            
            this.isPressed = true;
            this.createRipple(event.touches[0]);
            this.startLongPress();
            this.triggerHaptic('light');
        },
        
        handleTouchEnd(event) {
            this.isPressed = false;
            this.stopLongPress();
        },
        
        handleTouchCancel(event) {
            this.isPressed = false;
            this.stopLongPress();
        },
        
        handleMouseDown(event) {
            if (this.disabled || this.loading) return;
            
            this.isPressed = true;
            this.createRipple(event);
            this.startLongPress();
        },
        
        handleMouseUp(event) {
            this.isPressed = false;
            this.stopLongPress();
        },
        
        handleMouseLeave(event) {
            this.isPressed = false;
            this.stopLongPress();
        },
        
        handleClick(event) {
            if (this.disabled || this.loading) {
                event.preventDefault();
                return;
            }
            
            // Add click haptic feedback
            this.triggerHaptic('medium');
            
            // Dispatch click event for tracking
            this.$dispatch('touch-button-click', {
                element: this.$el,
                event: event
            });
        },
        
        createRipple(event) {
            if (!this.ripple) return;
            
            const rect = this.$el.getBoundingClientRect();
            const x = (event.clientX || event.pageX) - rect.left;
            const y = (event.clientY || event.pageY) - rect.top;
            const size = Math.max(rect.width, rect.height) * 2;
            
            const ripple = {
                id: Date.now() + Math.random(),
                x: x,
                y: y,
                size: size,
                created: Date.now()
            };
            
            this.ripples.push(ripple);
        },
        
        startLongPress() {
            if (!this.longPress) return;
            
            this.isLongPressing = true;
            this.longPressProgress = 0;
            
            // Animate progress
            this.longPressInterval = setInterval(() => {
                this.longPressProgress += 1 / (this.longPressDelay / 50);
                if (this.longPressProgress >= 1) {
                    this.longPressProgress = 1;
                    this.triggerLongPress();
                }
            }, 50);
            
            // Set timer for long press completion
            this.longPressTimer = setTimeout(() => {
                this.triggerLongPress();
            }, this.longPressDelay);
        },
        
        stopLongPress() {
            this.isLongPressing = false;
            this.longPressProgress = 0;
            
            if (this.longPressTimer) {
                clearTimeout(this.longPressTimer);
                this.longPressTimer = null;
            }
            
            if (this.longPressInterval) {
                clearInterval(this.longPressInterval);
                this.longPressInterval = null;
            }
        },
        
        triggerLongPress() {
            this.stopLongPress();
            this.triggerHaptic('heavy');
            
            this.$dispatch('long-press', {
                element: this.$el
            });
        },
        
        triggerHaptic(intensity = 'light') {
            if (!this.haptic || !('vibrate' in navigator)) return;
            
            const patterns = {
                'light': 5,
                'medium': 10,
                'heavy': 20
            };
            
            navigator.vibrate(patterns[intensity] || 5);
        }
    };
}
</script>

<style>
/* Improve touch targets for accessibility */
.touch-button {
    -webkit-tap-highlight-color: transparent;
    -webkit-touch-callout: none;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
}

/* Enhance active states for better feedback */
.touch-button:active {
    transform: scale(0.95);
}

/* Ripple animation */
@keyframes ripple {
    0% {
        transform: translate(-50%, -50%) scale(0);
        opacity: 0.3;
    }
    100% {
        transform: translate(-50%, -50%) scale(1);
        opacity: 0;
    }
}

.ripple-effect {
    animation: ripple 0.6s ease-out;
}

/* Long press progress animation */
@keyframes longPressProgress {
    0% {
        transform: scaleX(0);
    }
    100% {
        transform: scaleX(1);
    }
}
</style>