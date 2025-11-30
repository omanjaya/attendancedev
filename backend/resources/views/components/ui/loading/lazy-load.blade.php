@props([
    'src' => null, // For images
    'alt' => null, // For images
    'placeholder' => null, // Skeleton type or custom placeholder
    'threshold' => '0px', // Intersection observer root margin
    'component' => null, // Component to lazy load
    'data' => [], // Data to pass to component
    'height' => 'auto', // Placeholder height
    'width' => 'auto', // Placeholder width
    'fade' => true, // Fade in animation
    'blur' => true, // Blur-to-focus effect for images
])

@php
    $loaderId = 'lazy-' . uniqid();
    $isImage = !empty($src);
    $hasComponent = !empty($component);
@endphp

<div x-data="lazyLoader('{{ $loaderId }}')" 
     x-init="init"
     class="lazy-load-container {{ $fade ? 'transition-opacity duration-500' : '' }}"
     x-intersect.margin.{{ $threshold }}="load"
     {{ $attributes }}>
     
    <!-- Loading State -->
    <div x-show="!loaded" class="lazy-placeholder">
        @if($placeholder)
            @if($placeholder === 'skeleton')
                <x-ui.loading.skeleton type="card" :animated="true" />
            @elseif($placeholder === 'image')
                <x-ui.loading.skeleton width="{{ $width }}" height="{{ $height }}" :animated="true" />
            @elseif(view()->exists($placeholder))
                @include($placeholder)
            @else
                {{ $placeholder }}
            @endif
        @elseif($isImage)
            <!-- Image placeholder with blur effect -->
            <div class="bg-gray-200 dark:bg-gray-700 animate-pulse rounded {{ $height !== 'auto' ? "h-{$height}" : 'aspect-video' }} {{ $width !== 'auto' ? "w-{$width}" : 'w-full' }} flex items-center justify-center">
                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
        @else
            <!-- Default loading skeleton -->
            <div class="animate-pulse bg-gray-200 dark:bg-gray-700 rounded h-32 flex items-center justify-center">
                <x-ui.loading.spinner size="lg" color="gray" text="Loading..." />
            </div>
        @endif
    </div>
    
    <!-- Loaded Content -->
    <div x-show="loaded" 
         x-transition:enter="transition ease-out duration-500"
         x-transition:enter-start="opacity-0 {{ $fade ? 'scale-95' : '' }}"
         x-transition:enter-end="opacity-100 {{ $fade ? 'scale-100' : '' }}"
         class="lazy-content">
         
        @if($isImage)
            <img x-show="loaded" 
                 :src="imageSrc"
                 alt="{{ $alt }}"
                 class="lazy-image {{ $blur ? 'transition-all duration-700 ease-out' : '' }}"
                 :class="{ 'blur-sm': loading && {{ $blur ? 'true' : 'false' }}, 'blur-0': !loading }"
                 @load="imageLoaded"
                 @error="imageError">
                 
        @elseif($hasComponent && view()->exists($component))
            <div x-show="loaded" x-html="componentContent"></div>
            
        @else
            <!-- Slot content -->
            <div x-show="loaded">
                {{ $slot }}
            </div>
        @endif
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('lazyLoader', (id) => ({
        loaded: false,
        loading: false,
        error: false,
        imageSrc: '',
        componentContent: '',
        
        init() {
            // Pre-load critical resources
            this.preloadCritical();
        },
        
        async load() {
            if (this.loaded || this.loading) return;
            
            this.loading = true;
            
            try {
                @if($isImage)
                    await this.loadImage();
                @elseif($hasComponent)
                    await this.loadComponent();
                @else
                    await this.loadContent();
                @endif
                
                this.loaded = true;
                this.error = false;
                
                // Dispatch loaded event
                this.$dispatch('lazy-loaded', { id: '{{ $loaderId }}' });
                
            } catch (error) {
                this.error = true;
                console.error('Lazy loading failed:', error);
                
                // Dispatch error event
                this.$dispatch('lazy-error', { id: '{{ $loaderId }}', error });
            } finally {
                this.loading = false;
            }
        },
        
        @if($isImage)
        loadImage() {
            return new Promise((resolve, reject) => {
                const img = new Image();
                
                img.onload = () => {
                    this.imageSrc = '{{ $src }}';
                    resolve();
                };
                
                img.onerror = () => {
                    reject(new Error('Failed to load image'));
                };
                
                // Add loading delay for better UX
                setTimeout(() => {
                    img.src = '{{ $src }}';
                }, 100);
            });
        },
        
        imageLoaded() {
            // Additional image processing if needed
            this.$dispatch('image-loaded', { src: this.imageSrc });
        },
        
        imageError() {
            this.error = true;
            this.$dispatch('image-error', { src: '{{ $src }}' });
        },
        @endif
        
        @if($hasComponent)
        async loadComponent() {
            // Simulate component loading - in real implementation, 
            // this would fetch component data via AJAX
            return new Promise((resolve) => {
                setTimeout(() => {
                    // Load component content
                    this.componentContent = `
                        <div class="component-loaded">
                            @include('{{ $component }}', {{ json_encode($data) }})
                        </div>
                    `;
                    resolve();
                }, 500);
            });
        },
        @endif
        
        loadContent() {
            // Generic content loading
            return new Promise((resolve) => {
                setTimeout(resolve, 300);
            });
        },
        
        preloadCritical() {
            // Preload critical resources based on user interaction hints
            const handleHover = () => {
                @if($isImage)
                    // Preload image on hover
                    const link = document.createElement('link');
                    link.rel = 'preload';
                    link.as = 'image';
                    link.href = '{{ $src }}';
                    document.head.appendChild(link);
                @endif
            };
            
            // Add hover listener for preloading
            this.$el.addEventListener('mouseenter', handleHover, { once: true });
            this.$el.addEventListener('touchstart', handleHover, { once: true });
        }
    }));
});

// Global lazy loading performance observer
if (typeof window.LazyLoadObserver === 'undefined') {
    window.LazyLoadObserver = {
        totalLoaded: 0,
        totalErrors: 0,
        loadTimes: [],
        
        init() {
            document.addEventListener('lazy-loaded', (e) => {
                this.totalLoaded++;
                this.logPerformance('loaded', e.detail);
            });
            
            document.addEventListener('lazy-error', (e) => {
                this.totalErrors++;
                this.logPerformance('error', e.detail);
            });
        },
        
        logPerformance(type, data) {
            if (window.performance && window.performance.mark) {
                window.performance.mark(`lazy-${type}-${data.id}`);
            }
            
            console.log(`Lazy loading ${type}:`, {
                id: data.id,
                totalLoaded: this.totalLoaded,
                totalErrors: this.totalErrors,
                timestamp: Date.now()
            });
        }
    };
    
    // Initialize observer
    document.addEventListener('DOMContentLoaded', () => {
        window.LazyLoadObserver.init();
    });
}
</script>

<style>
    .lazy-load-container {
        position: relative;
        overflow: hidden;
    }
    
    .lazy-placeholder {
        display: block;
    }
    
    .lazy-content {
        display: block;
    }
    
    .lazy-image {
        width: 100%;
        height: auto;
        object-fit: cover;
    }
    
    /* Improved loading animations */
    .lazy-fade-in {
        animation: lazy-fade-in 0.5s ease-out;
    }
    
    @keyframes lazy-fade-in {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    /* Error state styling */
    .lazy-error {
        background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
        border: 1px solid #f87171;
        border-radius: 0.5rem;
        padding: 1rem;
        text-align: center;
        color: #dc2626;
    }
    
    /* Responsive lazy loading */
    @media (max-width: 768px) {
        .lazy-placeholder {
            min-height: 200px;
        }
    }
    
    @media (max-width: 480px) {
        .lazy-placeholder {
            min-height: 150px;
        }
    }
</style>