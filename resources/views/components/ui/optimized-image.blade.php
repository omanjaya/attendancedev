@props([
    'src' => null,
    'alt' => '',
    'width' => null,
    'height' => null,
    'loading' => 'lazy',
    'placeholder' => null,
    'blurDataUrl' => null,
    'sizes' => null,
    'srcset' => null,
    'priority' => false,
    'quality' => 85,
    'format' => 'webp',
    'fallbackFormat' => 'jpg',
    'aspectRatio' => null,
    'objectFit' => 'cover',
    'rounded' => 'none',
    'shadow' => false,
    'lazy' => true,
    'fadeIn' => true,
    'retina' => true
])

@php
    $imageId = 'img-' . Str::random(6);
    
    // Aspect ratio classes
    $aspectRatioClasses = [
        'square' => 'aspect-square',
        '16/9' => 'aspect-video',
        '4/3' => 'aspect-[4/3]',
        '3/2' => 'aspect-[3/2]',
        '21/9' => 'aspect-[21/9]',
        '1/1' => 'aspect-square'
    ];
    
    // Object fit classes
    $objectFitClasses = [
        'cover' => 'object-cover',
        'contain' => 'object-contain',
        'fill' => 'object-fill',
        'scale-down' => 'object-scale-down',
        'none' => 'object-none'
    ];
    
    // Rounded classes
    $roundedClasses = [
        'none' => '',
        'sm' => 'rounded-sm',
        'md' => 'rounded-md',
        'lg' => 'rounded-lg',
        'xl' => 'rounded-xl',
        '2xl' => 'rounded-2xl',
        'full' => 'rounded-full'
    ];
    
    $aspectClass = $aspectRatio ? ($aspectRatioClasses[$aspectRatio] ?? '') : '';
    $objectFitClass = $objectFitClasses[$objectFit] ?? 'object-cover';
    $roundedClass = $roundedClasses[$rounded] ?? '';
    $shadowClass = $shadow ? 'shadow-lg' : '';
    
    // Generate optimized URLs
    $webpSrc = null;
    $fallbackSrc = null;
    
    if ($src) {
        // For external URLs or if already optimized
        if (Str::startsWith($src, ['http://', 'https://'])) {
            $webpSrc = $src;
            $fallbackSrc = $src;
        } else {
            // Generate optimized versions
            $webpSrc = "/images/optimized/" . pathinfo($src, PATHINFO_FILENAME) . ".webp";
            $fallbackSrc = "/images/optimized/" . pathinfo($src, PATHINFO_FILENAME) . "." . $fallbackFormat;
        }
    }
    
    // Generate srcset for responsive images
    $responsiveSrcset = '';
    $responsiveWebpSrcset = '';
    
    if ($src && $retina && !Str::startsWith($src, ['http://', 'https://'])) {
        $baseName = pathinfo($src, PATHINFO_FILENAME);
        $responsiveSrcset = implode(', ', [
            "/images/optimized/{$baseName}-320w.{$fallbackFormat} 320w",
            "/images/optimized/{$baseName}-640w.{$fallbackFormat} 640w",
            "/images/optimized/{$baseName}-1024w.{$fallbackFormat} 1024w",
            "/images/optimized/{$baseName}-1280w.{$fallbackFormat} 1280w"
        ]);
        
        $responsiveWebpSrcset = implode(', ', [
            "/images/optimized/{$baseName}-320w.webp 320w",
            "/images/optimized/{$baseName}-640w.webp 640w",
            "/images/optimized/{$baseName}-1024w.webp 1024w",
            "/images/optimized/{$baseName}-1280w.webp 1280w"
        ]);
    }
    
    $classes = implode(' ', array_filter([
        'optimized-image',
        $aspectClass,
        $objectFitClass,
        $roundedClass,
        $shadowClass,
        'transition-opacity duration-300',
        $fadeIn ? 'opacity-0' : '',
        'w-full h-full'
    ]));
@endphp

<div class="optimized-image-container relative overflow-hidden {{ $aspectClass }}"
     x-data="optimizedImage({
        lazy: {{ $lazy ? 'true' : 'false' }},
        fadeIn: {{ $fadeIn ? 'true' : 'false' }},
        priority: {{ $priority ? 'true' : 'false' }},
        placeholder: '{{ $placeholder }}',
        blurDataUrl: '{{ $blurDataUrl }}'
     })"
     x-init="init()"
     id="{{ $imageId }}">
     
    <!-- Blur placeholder -->
    @if($blurDataUrl)
    <div class="absolute inset-0 bg-muted"
         x-show="!isLoaded"
         style="background-image: url('{{ $blurDataUrl }}'); 
                background-size: cover; 
                background-position: center;
                filter: blur(10px);
                transform: scale(1.1);">
    </div>
    @elseif($placeholder)
    <div class="absolute inset-0 flex items-center justify-center bg-muted text-muted-foreground"
         x-show="!isLoaded">
        {{ $placeholder }}
    </div>
    @else
    <!-- Default skeleton placeholder -->
    <div class="absolute inset-0 bg-gradient-to-r from-muted via-muted/50 to-muted animate-pulse"
         x-show="!isLoaded">
    </div>
    @endif
    
    <!-- Loading indicator -->
    <div class="absolute inset-0 flex items-center justify-center bg-muted/50"
         x-show="isLoading && !isLoaded">
        <div class="animate-spin rounded-full h-8 w-8 border-2 border-primary border-t-transparent"></div>
    </div>
    
    <!-- Optimized image with WebP support -->
    <picture class="block w-full h-full" x-show="shouldLoad || isLoaded">
        @if($webpSrc && $format === 'webp')
        <source 
            type="image/webp"
            @if($responsiveWebpSrcset)
            srcset="{{ $responsiveWebpSrcset }}"
            @else
            srcset="{{ $webpSrc }}"
            @endif
            @if($sizes) sizes="{{ $sizes }}" @endif>
        @endif
        
        <img 
            class="{{ $classes }}"
            @if($lazy && !$priority)
            x-intersect.once="handleIntersect()"
            @endif
            x-ref="image"
            @load="handleLoad()"
            @error="handleError()"
            alt="{{ $alt }}"
            @if($width) width="{{ $width }}" @endif
            @if($height) height="{{ $height }}" @endif
            @if($priority || !$lazy)
            src="{{ $fallbackSrc }}"
            @if($responsiveSrcset) srcset="{{ $responsiveSrcset }}" @endif
            @else
            data-src="{{ $fallbackSrc }}"
            @if($responsiveSrcset) data-srcset="{{ $responsiveSrcset }}" @endif
            @endif
            @if($sizes) sizes="{{ $sizes }}" @endif
            loading="{{ $priority ? 'eager' : 'lazy' }}"
            decoding="async"
            {{ $attributes }}>
    </picture>
    
    <!-- Error state -->
    <div class="absolute inset-0 flex items-center justify-center bg-muted text-muted-foreground"
         x-show="hasError">
        <div class="text-center">
            <svg class="mx-auto h-8 w-8 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <p class="text-xs">Image failed to load</p>
        </div>
    </div>
</div>

<script>
function optimizedImage(config) {
    return {
        lazy: config.lazy,
        fadeIn: config.fadeIn,
        priority: config.priority,
        placeholder: config.placeholder,
        blurDataUrl: config.blurDataUrl,
        
        isLoaded: false,
        isLoading: false,
        hasError: false,
        shouldLoad: false,
        observer: null,
        
        init() {
            // If priority or not lazy, load immediately
            if (this.priority || !this.lazy) {
                this.shouldLoad = true;
                this.loadImage();
            } else {
                this.setupIntersectionObserver();
            }
        },
        
        setupIntersectionObserver() {
            if (!window.IntersectionObserver) {
                // Fallback for older browsers
                this.shouldLoad = true;
                this.loadImage();
                return;
            }
            
            const options = {
                root: null,
                rootMargin: '50px',
                threshold: 0.1
            };
            
            this.observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting && !this.shouldLoad) {
                        this.shouldLoad = true;
                        this.loadImage();
                        this.observer.unobserve(entry.target);
                    }
                });
            }, options);
            
            this.observer.observe(this.$el);
        },
        
        handleIntersect() {
            // Alpine.js intersect directive callback
            if (!this.shouldLoad) {
                this.shouldLoad = true;
                this.loadImage();
            }
        },
        
        loadImage() {
            if (this.isLoading || this.isLoaded) return;
            
            this.isLoading = true;
            
            const img = this.$refs.image;
            if (!img) return;
            
            // Set src attributes if using lazy loading
            if (img.dataset.src) {
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
            }
            
            if (img.dataset.srcset) {
                img.srcset = img.dataset.srcset;
                img.removeAttribute('data-srcset');
            }
        },
        
        handleLoad() {
            this.isLoading = false;
            this.isLoaded = true;
            this.hasError = false;
            
            // Fade in effect
            if (this.fadeIn) {
                this.$nextTick(() => {
                    this.$refs.image.classList.remove('opacity-0');
                    this.$refs.image.classList.add('opacity-100');
                });
            }
            
            // Dispatch loaded event
            this.$dispatch('image-loaded', {
                element: this.$el,
                image: this.$refs.image
            });
        },
        
        handleError() {
            this.isLoading = false;
            this.hasError = true;
            
            console.error('Failed to load image:', this.$refs.image?.src);
            
            // Dispatch error event
            this.$dispatch('image-error', {
                element: this.$el,
                image: this.$refs.image
            });
        }
    };
}

// Global utility for preloading critical images
window.preloadImage = function(src, options = {}) {
    return new Promise((resolve, reject) => {
        const img = new Image();
        
        img.onload = () => resolve(img);
        img.onerror = () => reject(new Error(`Failed to preload image: ${src}`));
        
        if (options.sizes) img.sizes = options.sizes;
        if (options.srcset) img.srcset = options.srcset;
        
        img.src = src;
    });
};

// Batch preload multiple images
window.preloadImages = function(urls) {
    return Promise.allSettled(
        urls.map(url => typeof url === 'string' ? preloadImage(url) : preloadImage(url.src, url))
    );
};
</script>

<style>
/* Image optimization styles */
.optimized-image-container {
    contain: layout style paint;
}

.optimized-image {
    will-change: opacity;
}

/* Progressive enhancement for WebP support */
.webp .optimized-image {
    /* Styles for browsers that support WebP */
}

/* Reduce motion for accessibility */
@media (prefers-reduced-motion: reduce) {
    .optimized-image {
        transition: none !important;
    }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .optimized-image {
        filter: contrast(1.2);
    }
}

/* Print styles */
@media print {
    .optimized-image-container {
        break-inside: avoid;
    }
}
</style>