@props([
    'title' => '',
    'subtitle' => null,
    'icon' => null,
    'variant' => 'default', // default, chart, metric, list, grid
    'loading' => false,
    'error' => null,
    'refreshable' => false,
    'collapsible' => false,
    'removable' => false,
    'fullscreen' => false,
    'height' => 'auto',
    'actions' => null
])

@php
    $widgetId = 'widget-' . Str::random(6);
    
    // Variant configurations
    $variantConfigs = [
        'default' => ['padding' => 'p-6', 'spacing' => 'space-y-4'],
        'chart' => ['padding' => 'p-4', 'spacing' => 'space-y-2'],
        'metric' => ['padding' => 'p-6', 'spacing' => 'space-y-2'],
        'list' => ['padding' => 'p-0', 'spacing' => 'space-y-0'],
        'grid' => ['padding' => 'p-4', 'spacing' => 'space-y-3']
    ];
    
    $config = $variantConfigs[$variant] ?? $variantConfigs['default'];
    
    // Height classes
    $heightClasses = [
        'auto' => '',
        'sm' => 'h-64',
        'md' => 'h-80',
        'lg' => 'h-96',
        'xl' => 'h-[28rem]',
        'full' => 'h-full'
    ];
    
    $heightClass = is_string($height) ? ($heightClasses[$height] ?? $height) : '';
@endphp

<div class="bg-card rounded-lg border shadow-sm transition-all duration-200 hover:shadow-md {{ $heightClass }}"
     x-data="dashboardWidget('{{ $widgetId }}')"
     x-init="init()"
     :class="{ 'fixed inset-4 z-50 shadow-2xl': isFullscreen }"
     id="{{ $widgetId }}">
     
    <!-- Widget Header -->
    <div class="flex items-center justify-between p-4 {{ $variant === 'list' ? 'border-b border-border' : '' }}">
        <div class="flex items-center space-x-3 min-w-0 flex-1">
            @if($icon)
            <div class="flex-shrink-0">
                @if(str_starts_with($icon, 'ti '))
                    <i class="{{ $icon }} h-5 w-5 text-muted-foreground"></i>
                @else
                    {!! $icon !!}
                @endif
            </div>
            @endif
            
            <div class="min-w-0 flex-1">
                <h3 class="text-lg font-semibold text-foreground truncate">{{ $title }}</h3>
                @if($subtitle)
                <p class="text-sm text-muted-foreground truncate">{{ $subtitle }}</p>
                @endif
            </div>
        </div>
        
        <!-- Widget Actions -->
        <div class="flex items-center space-x-1">
            @if($actions)
                {{ $actions }}
            @endif
            
            @if($refreshable)
            <button @click="refresh()" 
                    :disabled="isLoading"
                    class="p-1.5 hover:bg-muted rounded-md transition-colors disabled:opacity-50"
                    title="Refresh">
                <svg class="h-4 w-4" :class="{ 'animate-spin': isLoading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </button>
            @endif
            
            @if($fullscreen)
            <button @click="toggleFullscreen()" 
                    class="p-1.5 hover:bg-muted rounded-md transition-colors"
                    title="Toggle Fullscreen">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                </svg>
            </button>
            @endif
            
            @if($collapsible)
            <button @click="toggleCollapse()" 
                    class="p-1.5 hover:bg-muted rounded-md transition-colors"
                    title="Toggle Collapse">
                <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-180': isCollapsed }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            @endif
            
            @if($removable)
            <button @click="remove()" 
                    class="p-1.5 hover:bg-muted hover:text-destructive rounded-md transition-colors"
                    title="Remove Widget">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            @endif
        </div>
    </div>
    
    <!-- Widget Content -->
    <div class="{{ $config['padding'] }} {{ $config['spacing'] }}" 
         x-show="!isCollapsed"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 transform scale-95"
         x-transition:enter-end="opacity-100 transform scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 transform scale-100"
         x-transition:leave-end="opacity-0 transform scale-95">
         
        @if($loading)
            <!-- Loading State -->
            <div class="flex items-center justify-center py-8">
                <div class="text-center">
                    <div class="animate-spin rounded-full h-8 w-8 border-2 border-primary border-t-transparent mx-auto mb-2"></div>
                    <p class="text-sm text-muted-foreground">Loading...</p>
                </div>
            </div>
        @elseif($error)
            <!-- Error State -->
            <div class="flex items-center justify-center py-8">
                <div class="text-center">
                    <svg class="mx-auto h-12 w-12 text-destructive mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 18.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                    <h3 class="text-sm font-medium text-foreground mb-1">Error Loading Widget</h3>
                    <p class="text-sm text-muted-foreground mb-4">{{ $error }}</p>
                    @if($refreshable)
                    <button @click="refresh()" 
                            class="px-3 py-1.5 text-sm bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors">
                        Try Again
                    </button>
                    @endif
                </div>
            </div>
        @else
            <!-- Widget Content -->
            <div class="widget-content" x-show="!isLoading">
                {{ $slot }}
            </div>
        @endif
    </div>
</div>

<script>
function dashboardWidget(widgetId) {
    return {
        widgetId: widgetId,
        isLoading: false,
        isCollapsed: false,
        isFullscreen: false,
        
        init() {
            // Initialize widget
            this.$dispatch('widget-initialized', { widgetId: this.widgetId });
        },
        
        refresh() {
            this.isLoading = true;
            this.$dispatch('widget-refresh', { widgetId: this.widgetId });
            
            // Simulate loading delay
            setTimeout(() => {
                this.isLoading = false;
            }, 1000);
        },
        
        toggleCollapse() {
            this.isCollapsed = !this.isCollapsed;
            this.$dispatch('widget-collapsed', { 
                widgetId: this.widgetId, 
                collapsed: this.isCollapsed 
            });
        },
        
        toggleFullscreen() {
            this.isFullscreen = !this.isFullscreen;
            
            if (this.isFullscreen) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
            
            this.$dispatch('widget-fullscreen', { 
                widgetId: this.widgetId, 
                fullscreen: this.isFullscreen 
            });
        },
        
        remove() {
            if (confirm('Are you sure you want to remove this widget?')) {
                this.$dispatch('widget-removed', { widgetId: this.widgetId });
                this.$el.remove();
            }
        }
    };
}

// Handle escape key for fullscreen
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        // Find any fullscreen widgets and exit fullscreen
        const fullscreenWidgets = document.querySelectorAll('[x-data*="dashboardWidget"]');
        fullscreenWidgets.forEach(widget => {
            const alpineData = Alpine.$data(widget);
            if (alpineData && alpineData.isFullscreen) {
                alpineData.toggleFullscreen();
            }
        });
    }
});
</script>