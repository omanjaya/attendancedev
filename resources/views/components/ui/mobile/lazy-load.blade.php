@props([
    'threshold' => '100px',
    'once' => true,
    'placeholder' => null,
    'skeleton' => false,
    'skeletonHeight' => 'h-32',
    'skeletonWidth' => 'w-full',
    'fadeIn' => true,
    'rootMargin' => '50px'
])

@php
    $lazyId = 'lazy-' . Str::random(6);
@endphp

<div class="lazy-load-container"
     x-data="lazyLoad({
        threshold: '{{ $threshold }}',
        once: {{ $once ? 'true' : 'false' }},
        fadeIn: {{ $fadeIn ? 'true' : 'false' }},
        rootMargin: '{{ $rootMargin }}'
     })"
     x-init="init()"
     x-intersect.margin.{{ $rootMargin }}="handleIntersect()"
     id="{{ $lazyId }}">
     
    <!-- Loading/Placeholder State -->
    <div x-show="!isLoaded" 
         class="{{ $fadeIn ? 'transition-opacity duration-300' : '' }}"
         :class="{ 'opacity-0': isLoading && fadeIn }">
        
        @if($skeleton)
            <!-- Skeleton Placeholder -->
            <div class="animate-pulse space-y-3">
                <x-ui.skeleton :height="$skeletonHeight" :width="$skeletonWidth" />
                <x-ui.skeleton height="h-4" width="w-3/4" />
                <x-ui.skeleton height="h-4" width="w-1/2" />
            </div>
        @elseif($placeholder)
            <!-- Custom Placeholder -->
            {{ $placeholder }}
        @else
            <!-- Default Placeholder -->
            <div class="flex items-center justify-center {{ $skeletonHeight }} bg-muted/50 rounded-lg">
                <div class="text-center">
                    <div class="animate-spin rounded-full h-8 w-8 border-2 border-primary border-t-transparent mx-auto mb-2"></div>
                    <p class="text-sm text-muted-foreground">Loading...</p>
                </div>
            </div>
        @endif
    </div>
    
    <!-- Loaded Content -->
    <div x-show="isLoaded" 
         class="{{ $fadeIn ? 'transition-opacity duration-500' : '' }}"
         :class="{ 'opacity-0': !isVisible && fadeIn, 'opacity-100': isVisible && fadeIn }">
        <div x-html="loadedContent"></div>
    </div>
    
    <!-- Hidden content template -->
    <template x-ref="content">
        {{ $slot }}
    </template>
</div>

<script>
function lazyLoad(config) {
    return {
        isLoaded: false,
        isLoading: false,
        isVisible: false,
        loadedContent: '',
        threshold: config.threshold,
        once: config.once,
        fadeIn: config.fadeIn,
        rootMargin: config.rootMargin,
        observer: null,
        
        init() {
            // Use Intersection Observer for better performance
            this.setupIntersectionObserver();
        },
        
        setupIntersectionObserver() {
            if (!window.IntersectionObserver) {
                // Fallback for older browsers
                this.loadContent();
                return;
            }
            
            const options = {
                root: null,
                rootMargin: this.rootMargin,
                threshold: 0.1
            };
            
            this.observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.loadContent();
                        
                        if (this.once) {
                            this.observer.unobserve(entry.target);
                        }
                    }
                });
            }, options);
            
            this.observer.observe(this.$el);
        },
        
        handleIntersect() {
            // Alpine.js intersect directive callback
            if (!this.isLoaded) {
                this.loadContent();
            }
        },
        
        loadContent() {
            if (this.isLoaded || this.isLoading) return;
            
            this.isLoading = true;
            
            // Simulate network delay for better UX
            setTimeout(() => {
                try {
                    // Get content from template
                    const template = this.$refs.content;
                    if (template) {
                        this.loadedContent = template.innerHTML;
                    }
                    
                    this.isLoaded = true;
                    this.isLoading = false;
                    
                    // Trigger visibility for fade-in effect
                    if (this.fadeIn) {
                        this.$nextTick(() => {
                            this.isVisible = true;
                        });
                    } else {
                        this.isVisible = true;
                    }
                    
                    // Dispatch loaded event
                    this.$dispatch('lazy-loaded', {
                        element: this.$el,
                        content: this.loadedContent
                    });
                    
                } catch (error) {
                    console.error('Lazy load error:', error);
                    this.isLoading = false;
                }
            }, 100);
        },
        
        // Method to manually trigger loading
        load() {
            this.loadContent();
        },
        
        // Method to reset lazy loading
        reset() {
            this.isLoaded = false;
            this.isLoading = false;
            this.isVisible = false;
            this.loadedContent = '';
        }
    };
}

// Global utility for manual lazy loading
window.triggerLazyLoad = function(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        const alpineData = Alpine.$data(element);
        if (alpineData && alpineData.load) {
            alpineData.load();
        }
    }
};

// Preload images when they come into view
window.lazyLoadImage = function(img) {
    if (img.dataset.src) {
        img.src = img.dataset.src;
        img.onload = function() {
            img.classList.add('loaded');
        };
    }
};
</script>

<style>
/* Smooth fade-in animations */
.lazy-load-content {
    opacity: 0;
    transition: opacity 0.3s ease-in-out;
}

.lazy-load-content.loaded {
    opacity: 1;
}

/* Image lazy loading styles */
img[data-src] {
    opacity: 0;
    transition: opacity 0.3s ease-in-out;
}

img[data-src].loaded {
    opacity: 1;
}

/* Reduce motion for accessibility */
@media (prefers-reduced-motion: reduce) {
    .lazy-load-content,
    img[data-src] {
        transition: none;
    }
}

/* Performance optimizations */
.lazy-load-container {
    contain: layout style paint;
}

/* Loading shimmer effect */
.lazy-loading-shimmer {
    background: linear-gradient(
        90deg,
        transparent 0%,
        hsl(var(--muted)) 20%,
        hsl(var(--muted)/50%) 60%,
        transparent 100%
    );
    background-size: 200% 100%;
    animation: shimmer 2s infinite;
}

@keyframes shimmer {
    0% {
        background-position: -200% 0;
    }
    100% {
        background-position: 200% 0;
    }
}
</style>