@props([
    'items' => [],
    'activeRoute' => null,
    'position' => 'bottom', // top, bottom
    'variant' => 'default', // default, tabs, floating
    'showLabels' => true,
    'showBadges' => true
])

@php
    $activeRoute = $activeRoute ?: request()->route()->getName();
    
    // Position classes
    $positionClasses = [
        'top' => 'top-0 border-b',
        'bottom' => 'bottom-0 border-t'
    ];
    
    // Variant classes
    $variantClasses = [
        'default' => 'bg-background/95 backdrop-blur-sm',
        'tabs' => 'bg-card/95 backdrop-blur-sm',
        'floating' => 'mx-4 mb-4 rounded-xl bg-card/95 backdrop-blur-sm shadow-lg border'
    ];
    
    $positionClass = $positionClasses[$position] ?? $positionClasses['bottom'];
    $variantClass = $variantClasses[$variant] ?? $variantClasses['default'];
    
    $containerClass = "fixed left-0 right-0 z-50 {$positionClass} {$variantClass}";
    if ($variant !== 'floating') {
        $containerClass .= ' border-border';
    }
@endphp

<nav class="{{ $containerClass }} md:hidden" 
     x-data="mobileNav()"
     x-init="init()">
    
    <div class="flex items-center justify-around {{ $variant === 'floating' ? 'px-2 py-2' : 'px-4 py-2' }}">
        @foreach($items as $item)
            @php
                $isActive = $activeRoute === $item['route'] || 
                           (isset($item['activeRoutes']) && in_array($activeRoute, $item['activeRoutes']));
                $hasNotification = isset($item['badge']) && $item['badge'] > 0;
                $isDisabled = $item['disabled'] ?? false;
            @endphp
            
            <a href="{{ route($item['route']) }}"
               class="relative flex flex-col items-center justify-center min-w-0 flex-1 
                      {{ $showLabels ? 'py-2' : 'py-3' }} px-1
                      transition-all duration-200 touch-manipulation
                      {{ $isActive ? 'text-primary' : 'text-muted-foreground' }}
                      {{ $isDisabled ? 'opacity-50 pointer-events-none' : 'hover:text-foreground active:scale-95' }}"
               @if($isActive) aria-current="page" @endif
               @click="handleNavClick('{{ $item['route'] }}')">
               
                <!-- Icon Container -->
                <div class="relative flex items-center justify-center">
                    <!-- Icon -->
                    <div class="flex items-center justify-center w-6 h-6 transition-transform duration-200
                                {{ $isActive ? 'scale-110' : 'scale-100' }}">
                        @if(isset($item['icon']))
                            @if(str_starts_with($item['icon'], 'ti '))
                                <i class="{{ $item['icon'] }} text-xl"></i>
                            @else
                                {!! $item['icon'] !!}
                            @endif
                        @endif
                    </div>
                    
                    <!-- Badge -->
                    @if($showBadges && $hasNotification)
                    <span class="absolute -top-1 -right-1 flex items-center justify-center 
                                 min-w-[16px] h-4 px-1 text-xs font-bold 
                                 bg-destructive text-destructive-foreground 
                                 rounded-full border border-background
                                 {{ $item['badge'] > 99 ? 'text-[10px]' : '' }}">
                        {{ $item['badge'] > 99 ? '99+' : $item['badge'] }}
                    </span>
                    @endif
                </div>
                
                <!-- Label -->
                @if($showLabels)
                <span class="text-xs font-medium mt-1 truncate max-w-full
                           transition-all duration-200
                           {{ $isActive ? 'text-primary' : 'text-muted-foreground' }}">
                    {{ $item['label'] }}
                </span>
                @endif
                
                <!-- Active Indicator -->
                @if($isActive)
                <div class="absolute {{ $position === 'top' ? 'bottom-0' : 'top-0' }} 
                           left-1/2 transform -translate-x-1/2
                           w-1 h-1 bg-primary rounded-full
                           {{ $showLabels ? ($position === 'top' ? '-mb-1' : '-mt-1') : '' }}">
                </div>
                @endif
            </a>
        @endforeach
    </div>
    
    <!-- Safe Area Padding for iOS -->
    @if($position === 'bottom')
    <div class="h-safe-area-inset-bottom bg-transparent"></div>
    @endif
</nav>

<!-- Add safe area support -->
<style>
.h-safe-area-inset-bottom {
    height: env(safe-area-inset-bottom);
}

/* Add iOS momentum scrolling to prevent conflicts */
body.mobile-nav-active {
    position: fixed;
    overflow: hidden;
    width: 100%;
}

/* Improve touch targets */
.mobile-nav-item {
    min-height: 44px;
    min-width: 44px;
}

/* Haptic feedback simulation on supported devices */
@supports (vibrate: auto) {
    .mobile-nav-item:active {
        animation: haptic-feedback 50ms ease-out;
    }
}

@keyframes haptic-feedback {
    0% { transform: scale(1); }
    50% { transform: scale(0.95); }
    100% { transform: scale(1); }
}
</style>

<script>
function mobileNav() {
    return {
        init() {
            // Add class to body for styling
            document.body.classList.add('has-mobile-nav');
            
            // Handle safe area on iOS
            this.handleSafeArea();
            
            // Add padding to content to prevent overlap
            this.adjustContentPadding();
        },
        
        handleNavClick(route) {
            // Add haptic feedback if available
            if ('vibrate' in navigator) {
                navigator.vibrate(10);
            }
            
            // Analytics tracking
            this.$dispatch('mobile-nav-click', { route: route });
        },
        
        handleSafeArea() {
            // Dynamically adjust for safe area on devices with notches
            const safeAreaBottom = getComputedStyle(document.documentElement)
                .getPropertyValue('env(safe-area-inset-bottom)');
            
            if (safeAreaBottom && safeAreaBottom !== '0px') {
                this.$el.style.paddingBottom = safeAreaBottom;
            }
        },
        
        adjustContentPadding() {
            // Add bottom padding to prevent content being hidden behind nav
            const navHeight = this.$el.offsetHeight;
            const mainContent = document.querySelector('main') || document.querySelector('#app');
            
            if (mainContent) {
                mainContent.style.paddingBottom = navHeight + 'px';
            }
        }
    };
}

// Handle orientation changes
window.addEventListener('orientationchange', function() {
    setTimeout(() => {
        // Recalculate safe areas and content padding
        const navs = document.querySelectorAll('[x-data*="mobileNav"]');
        navs.forEach(nav => {
            const alpineData = Alpine.$data(nav);
            if (alpineData) {
                alpineData.handleSafeArea();
                alpineData.adjustContentPadding();
            }
        });
    }, 100);
});
</script>