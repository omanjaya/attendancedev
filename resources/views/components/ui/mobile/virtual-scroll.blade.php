@props([
    'items' => [],
    'itemHeight' => 60,
    'containerHeight' => 400,
    'overscan' => 5,
    'threshold' => 100,
    'loading' => false,
    'hasMore' => false,
    'emptyMessage' => 'No items to display'
])

@php
    $scrollId = 'virtual-scroll-' . Str::random(6);
@endphp

<div class="virtual-scroll-container relative overflow-hidden"
     :style="`height: ${containerHeight}px`"
     x-data="virtualScroll({
        items: {{ json_encode($items) }},
        itemHeight: {{ $itemHeight }},
        containerHeight: {{ $containerHeight }},
        overscan: {{ $overscan }},
        threshold: {{ $threshold }},
        loading: {{ $loading ? 'true' : 'false' }},
        hasMore: {{ $hasMore ? 'true' : 'false' }}
     })"
     x-init="init()"
     id="{{ $scrollId }}">
     
    <!-- Scrollable Area -->
    <div class="virtual-scroll-content absolute inset-0 overflow-auto"
         @scroll="handleScroll($event)"
         x-ref="scrollContainer"
         style="-webkit-overflow-scrolling: touch;">
        
        <!-- Virtual spacer for total height -->
        <div :style="`height: ${totalHeight}px; position: relative;`">
            
            <!-- Visible items container -->
            <div :style="`transform: translateY(${offsetY}px); position: absolute; top: 0; left: 0; right: 0;`">
                
                @if(empty($items) && !$loading)
                <!-- Empty State -->
                <div class="flex items-center justify-center h-full">
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-muted-foreground mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p class="text-muted-foreground">{{ $emptyMessage }}</p>
                    </div>
                </div>
                @else
                <!-- Virtual items -->
                <template x-for="(item, index) in visibleItems" :key="item.id || index">
                    <div class="virtual-item"
                         :style="`height: ${itemHeight}px`"
                         :data-index="item.originalIndex">
                        
                        <!-- Custom item slot -->
                        <div class="h-full w-full" x-html="renderItem(item, item.originalIndex)"></div>
                    </div>
                </template>
                @endif
                
                <!-- Loading indicator at bottom -->
                <div x-show="loading" class="flex items-center justify-center py-4">
                    <div class="animate-spin rounded-full h-6 w-6 border-2 border-primary border-t-transparent mr-2"></div>
                    <span class="text-sm text-muted-foreground">Loading more...</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scroll position indicator -->
    <div class="absolute right-2 top-2 bg-black/20 text-white text-xs px-2 py-1 rounded"
         x-show="showScrollIndicator"
         x-transition>
        <span x-text="`${Math.round(scrollProgress * 100)}%`"></span>
    </div>
    
    <!-- Item template (hidden) -->
    <template x-ref="itemTemplate">
        {{ $slot }}
    </template>
</div>

<script>
function virtualScroll(config) {
    return {
        items: config.items,
        itemHeight: config.itemHeight,
        containerHeight: config.containerHeight,
        overscan: config.overscan,
        threshold: config.threshold,
        loading: config.loading,
        hasMore: config.hasMore,
        
        scrollTop: 0,
        visibleItems: [],
        startIndex: 0,
        endIndex: 0,
        showScrollIndicator: false,
        scrollIndicatorTimer: null,
        
        get totalHeight() {
            return this.items.length * this.itemHeight;
        },
        
        get offsetY() {
            return this.startIndex * this.itemHeight;
        },
        
        get visibleCount() {
            return Math.ceil(this.containerHeight / this.itemHeight);
        },
        
        get scrollProgress() {
            if (this.totalHeight <= this.containerHeight) return 1;
            return this.scrollTop / (this.totalHeight - this.containerHeight);
        },
        
        init() {
            this.updateVisibleItems();
            
            // Setup intersection observer for infinite scroll
            this.setupInfiniteScroll();
        },
        
        handleScroll(event) {
            this.scrollTop = event.target.scrollTop;
            this.updateVisibleItems();
            this.handleInfiniteScroll();
            this.showScrollProgress();
        },
        
        updateVisibleItems() {
            // Calculate which items should be visible
            this.startIndex = Math.max(0, Math.floor(this.scrollTop / this.itemHeight) - this.overscan);
            this.endIndex = Math.min(
                this.items.length - 1,
                Math.ceil((this.scrollTop + this.containerHeight) / this.itemHeight) + this.overscan
            );
            
            // Create visible items array
            this.visibleItems = [];
            for (let i = this.startIndex; i <= this.endIndex; i++) {
                if (this.items[i]) {
                    this.visibleItems.push({
                        ...this.items[i],
                        originalIndex: i
                    });
                }
            }
        },
        
        renderItem(item, index) {
            // Get the template content
            const template = this.$refs.itemTemplate;
            if (!template) return '';
            
            let html = template.innerHTML;
            
            // Replace placeholders with actual data
            // This is a simple template system - you might want to use a more sophisticated one
            html = html.replace(/\{\{(\s*[\w\.]+\s*)\}\}/g, (match, key) => {
                const trimmedKey = key.trim();
                return this.getNestedValue(item, trimmedKey) || '';
            });
            
            // Replace index placeholder
            html = html.replace(/\{\{\s*index\s*\}\}/g, index);
            
            return html;
        },
        
        getNestedValue(obj, path) {
            return path.split('.').reduce((o, i) => o?.[i], obj);
        },
        
        setupInfiniteScroll() {
            if (!this.hasMore) return;
            
            // Watch for scroll near bottom
            this.$watch('scrollTop', (scrollTop) => {
                const scrollBottom = scrollTop + this.containerHeight;
                const threshold = this.totalHeight - this.threshold;
                
                if (scrollBottom >= threshold && !this.loading) {
                    this.loadMore();
                }
            });
        },
        
        handleInfiniteScroll() {
            if (!this.hasMore || this.loading) return;
            
            const scrollBottom = this.scrollTop + this.containerHeight;
            const threshold = this.totalHeight - this.threshold;
            
            if (scrollBottom >= threshold) {
                this.loadMore();
            }
        },
        
        loadMore() {
            if (this.loading || !this.hasMore) return;
            
            this.$dispatch('virtual-scroll-load-more', {
                currentCount: this.items.length,
                startIndex: this.startIndex,
                endIndex: this.endIndex
            });
        },
        
        showScrollProgress() {
            this.showScrollIndicator = true;
            
            // Hide indicator after delay
            clearTimeout(this.scrollIndicatorTimer);
            this.scrollIndicatorTimer = setTimeout(() => {
                this.showScrollIndicator = false;
            }, 1000);
        },
        
        // Method to scroll to specific item
        scrollToItem(index) {
            const scrollContainer = this.$refs.scrollContainer;
            if (scrollContainer) {
                const targetScrollTop = index * this.itemHeight;
                scrollContainer.scrollTop = targetScrollTop;
            }
        },
        
        // Method to add new items
        addItems(newItems) {
            this.items = [...this.items, ...newItems];
            this.updateVisibleItems();
        },
        
        // Method to prepend items
        prependItems(newItems) {
            this.items = [...newItems, ...this.items];
            this.updateVisibleItems();
            
            // Maintain scroll position
            const scrollContainer = this.$refs.scrollContainer;
            if (scrollContainer) {
                scrollContainer.scrollTop += newItems.length * this.itemHeight;
            }
        },
        
        // Method to update specific item
        updateItem(index, newItem) {
            if (this.items[index]) {
                this.items[index] = { ...this.items[index], ...newItem };
                this.updateVisibleItems();
            }
        }
    };
}

// Global utility functions
window.virtualScrollTo = function(scrollId, index) {
    const element = document.getElementById(scrollId);
    if (element) {
        const alpineData = Alpine.$data(element);
        if (alpineData && alpineData.scrollToItem) {
            alpineData.scrollToItem(index);
        }
    }
};

window.addVirtualScrollItems = function(scrollId, items) {
    const element = document.getElementById(scrollId);
    if (element) {
        const alpineData = Alpine.$data(element);
        if (alpineData && alpineData.addItems) {
            alpineData.addItems(items);
        }
    }
};
</script>

<style>
/* Virtual scroll optimizations */
.virtual-scroll-container {
    contain: strict;
    will-change: scroll-position;
}

.virtual-scroll-content {
    scroll-behavior: smooth;
    -webkit-overflow-scrolling: touch;
    overscroll-behavior: contain;
}

.virtual-item {
    contain: layout style paint;
    will-change: transform;
}

/* Performance optimizations for mobile */
@media (max-width: 768px) {
    .virtual-scroll-content {
        /* Reduce paint area for better performance */
        backface-visibility: hidden;
        perspective: 1000px;
    }
    
    .virtual-item {
        /* Use GPU acceleration */
        transform: translateZ(0);
    }
}

/* Scroll indicator styles */
.scroll-indicator {
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
}

/* Smooth transitions for reduced motion users */
@media (prefers-reduced-motion: reduce) {
    .virtual-scroll-content {
        scroll-behavior: auto;
    }
}
</style>