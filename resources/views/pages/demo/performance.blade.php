@extends('layouts.app')

@section('title', 'Loading States & Performance Demo')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Performance Monitor -->
    <x-ui.performance.monitor 
        :enabled="true" 
        :show-debug="true" 
        :track-page-load="true" 
        :track-user-interactions="true" 
        :track-resource-timing="true" />

    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 shadow-sm">
        <div class="container mx-auto px-4 py-6">
            <div class="text-center">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white mb-2">
                    Loading States & Performance Demo
                </h1>
                <p class="text-gray-600 dark:text-gray-400 text-sm sm:text-base">
                    Comprehensive loading components with performance monitoring and optimization
                </p>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-6 space-y-8">
        <!-- Spinner Showcase -->
        <section>
            <h2 class="text-lg sm:text-xl font-semibold text-gray-900 dark:text-white mb-4">
                Loading Spinners
            </h2>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <!-- Spin Types -->
                    <div>
                        <h3 class="text-base font-medium text-gray-900 dark:text-white mb-4">Spinner Types</h3>
                        <div class="space-y-4">
                            <div class="flex items-center space-x-4">
                                <x-ui.loading.spinner type="spin" size="md" color="emerald" />
                                <span class="text-sm text-gray-600 dark:text-gray-400">Spin</span>
                            </div>
                            <div class="flex items-center space-x-4">
                                <x-ui.loading.spinner type="dots" size="md" color="emerald" />
                                <span class="text-sm text-gray-600 dark:text-gray-400">Dots</span>
                            </div>
                            <div class="flex items-center space-x-4">
                                <x-ui.loading.spinner type="bars" size="md" color="emerald" />
                                <span class="text-sm text-gray-600 dark:text-gray-400">Bars</span>
                            </div>
                            <div class="flex items-center space-x-4">
                                <x-ui.loading.spinner type="ring" size="md" color="emerald" />
                                <span class="text-sm text-gray-600 dark:text-gray-400">Ring</span>
                            </div>
                            <div class="flex items-center space-x-4">
                                <x-ui.loading.spinner type="pulse" size="md" color="emerald" />
                                <span class="text-sm text-gray-600 dark:text-gray-400">Pulse</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sizes -->
                    <div>
                        <h3 class="text-base font-medium text-gray-900 dark:text-white mb-4">Spinner Sizes</h3>
                        <div class="space-y-4">
                            <div class="flex items-center space-x-4">
                                <x-ui.loading.spinner type="spin" size="xs" color="emerald" />
                                <span class="text-sm text-gray-600 dark:text-gray-400">Extra Small</span>
                            </div>
                            <div class="flex items-center space-x-4">
                                <x-ui.loading.spinner type="spin" size="sm" color="emerald" />
                                <span class="text-sm text-gray-600 dark:text-gray-400">Small</span>
                            </div>
                            <div class="flex items-center space-x-4">
                                <x-ui.loading.spinner type="spin" size="md" color="emerald" />
                                <span class="text-sm text-gray-600 dark:text-gray-400">Medium</span>
                            </div>
                            <div class="flex items-center space-x-4">
                                <x-ui.loading.spinner type="spin" size="lg" color="emerald" />
                                <span class="text-sm text-gray-600 dark:text-gray-400">Large</span>
                            </div>
                            <div class="flex items-center space-x-4">
                                <x-ui.loading.spinner type="spin" size="xl" color="emerald" />
                                <span class="text-sm text-gray-600 dark:text-gray-400">Extra Large</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- With Text -->
                    <div>
                        <h3 class="text-base font-medium text-gray-900 dark:text-white mb-4">With Text</h3>
                        <div class="space-y-4">
                            <x-ui.loading.spinner type="spin" size="md" color="emerald" text="Loading..." />
                            <x-ui.loading.spinner type="dots" size="md" color="blue" text="Processing..." />
                            <x-ui.loading.spinner type="bars" size="md" color="gray" text="Uploading..." />
                            <div class="pt-4">
                                <x-ui.loading.spinner type="spin" size="lg" color="emerald" text="Please wait" centered="true" />
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Overlay Demo -->
                <div class="mt-8 pt-8 border-t border-gray-200 dark:border-gray-700">
                    <h3 class="text-base font-medium text-gray-900 dark:text-white mb-4">Overlay Spinner</h3>
                    <div x-data="{ showOverlay: false }">
                        <x-ui.button @click="showOverlay = true">Show Overlay Loading</x-ui.button>
                        
                        <div x-show="showOverlay">
                            <x-ui.loading.spinner type="spin" size="lg" color="white" text="Processing request..." overlay="true" />
                        </div>
                        
                        <script>
                            document.addEventListener('alpine:init', () => {
                                Alpine.data('overlayDemo', () => ({
                                    showOverlay: false,
                                    
                                    showDemo() {
                                        this.showOverlay = true;
                                        setTimeout(() => {
                                            this.showOverlay = false;
                                        }, 3000);
                                    }
                                }));
                            });
                        </script>
                    </div>
                </div>
            </div>
        </section>

        <!-- Skeleton Loading -->
        <section>
            <h2 class="text-lg sm:text-xl font-semibold text-gray-900 dark:text-white mb-4">
                Skeleton Loading States
            </h2>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Basic Skeletons -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-base font-medium text-gray-900 dark:text-white mb-4">Basic Shapes</h3>
                    <div class="space-y-4">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Lines</p>
                            <x-ui.loading.skeleton type="line" />
                            <div class="mt-2">
                                <x-ui.loading.skeleton type="line" width="3/4" />
                            </div>
                        </div>
                        
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Circle</p>
                            <x-ui.loading.skeleton type="circle" width="12" />
                        </div>
                        
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Avatar</p>
                            <x-ui.loading.skeleton type="avatar" width="10" />
                        </div>
                        
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Button</p>
                            <x-ui.loading.skeleton type="button" width="32" />
                        </div>
                        
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Text Block</p>
                            <x-ui.loading.skeleton type="text" lines="3" />
                        </div>
                    </div>
                </div>
                
                <!-- Complex Skeletons -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-base font-medium text-gray-900 dark:text-white mb-4">Complex Layouts</h3>
                    <div class="space-y-6">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Card</p>
                            <x-ui.loading.skeleton type="card" lines="2" />
                        </div>
                        
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Form</p>
                            <x-ui.loading.skeleton type="form" lines="3" />
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Full Layout Skeletons -->
            <div class="mt-6 space-y-6">
                <div>
                    <h3 class="text-base font-medium text-gray-900 dark:text-white mb-4">Stats Dashboard</h3>
                    <x-ui.loading.skeleton type="stats" />
                </div>
                
                <div>
                    <h3 class="text-base font-medium text-gray-900 dark:text-white mb-4">Data Table</h3>
                    <x-ui.loading.skeleton type="table" lines="5" />
                </div>
                
                <div>
                    <h3 class="text-base font-medium text-gray-900 dark:text-white mb-4">List Items</h3>
                    <x-ui.loading.skeleton type="list" lines="4" />
                </div>
            </div>
        </section>

        <!-- Lazy Loading -->
        <section>
            <h2 class="text-lg sm:text-xl font-semibold text-gray-900 dark:text-white mb-4">
                Lazy Loading
            </h2>
            
            <div class="space-y-6">
                <!-- Image Lazy Loading -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-base font-medium text-gray-900 dark:text-white mb-4">Lazy Loaded Images</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <x-ui.loading.lazy-load 
                            src="https://picsum.photos/400/300?random=1"
                            alt="Random image 1"
                            placeholder="image"
                            :fade="true"
                            :blur="true"
                            class="rounded-lg overflow-hidden" />
                            
                        <x-ui.loading.lazy-load 
                            src="https://picsum.photos/400/300?random=2"
                            alt="Random image 2"
                            placeholder="image"
                            :fade="true"
                            :blur="true"
                            class="rounded-lg overflow-hidden" />
                            
                        <x-ui.loading.lazy-load 
                            src="https://picsum.photos/400/300?random=3"
                            alt="Random image 3"
                            placeholder="image"
                            :fade="true"
                            :blur="true"
                            class="rounded-lg overflow-hidden" />
                    </div>
                </div>
                
                <!-- Content Lazy Loading -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-base font-medium text-gray-900 dark:text-white mb-4">Lazy Loaded Content</h3>
                    <div class="space-y-4">
                        <x-ui.loading.lazy-load 
                            placeholder="skeleton"
                            threshold="100px"
                            :fade="true">
                            <div class="p-4 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg">
                                <h4 class="font-medium text-emerald-900 dark:text-emerald-100 mb-2">Lazy Loaded Content</h4>
                                <p class="text-emerald-700 dark:text-emerald-300 text-sm">
                                    This content was loaded when it came into view. This technique saves initial page load time 
                                    and bandwidth by only loading content when needed.
                                </p>
                            </div>
                        </x-ui.loading.lazy-load>
                        
                        <x-ui.loading.lazy-load 
                            placeholder="skeleton"
                            threshold="50px"
                            :fade="true">
                            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                <h4 class="font-medium text-blue-900 dark:text-blue-100 mb-2">Another Lazy Section</h4>
                                <p class="text-blue-700 dark:text-blue-300 text-sm">
                                    This is another section that loads lazily. Notice how each section loads independently 
                                    when it enters the viewport.
                                </p>
                            </div>
                        </x-ui.loading.lazy-load>
                    </div>
                </div>
            </div>
        </section>

        <!-- Performance Insights -->
        <section>
            <h2 class="text-lg sm:text-xl font-semibold text-gray-900 dark:text-white mb-4">
                Performance Insights
            </h2>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-emerald-600 mb-1" id="metric-page-load">-</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Page Load (ms)</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600 mb-1" id="metric-dom-ready">-</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">DOM Ready (ms)</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-600 mb-1" id="metric-resources">-</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Resources Loaded</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-orange-600 mb-1" id="metric-interactions">-</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">User Interactions</div>
                    </div>
                </div>
                
                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-4">Performance Tips</h4>
                    <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                        <li class="flex items-center">
                            <svg class="w-4 h-4 text-emerald-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            Use skeleton loading for perceived performance
                        </li>
                        <li class="flex items-center">
                            <svg class="w-4 h-4 text-emerald-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            Lazy load images and heavy content
                        </li>
                        <li class="flex items-center">
                            <svg class="w-4 h-4 text-emerald-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            Monitor Core Web Vitals (LCP, FID, CLS)
                        </li>
                        <li class="flex items-center">
                            <svg class="w-4 h-4 text-emerald-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            Use appropriate loading states for user feedback
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- Interactive Demo -->
        <section x-data="performanceDemo()">
            <h2 class="text-lg sm:text-xl font-semibold text-gray-900 dark:text-white mb-4">
                Interactive Performance Demo
            </h2>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <x-ui.button @click="simulateLoading" :loading="loading" class="w-full">
                        <span x-show="!loading">Simulate API Call</span>
                    </x-ui.button>
                    
                    <x-ui.button @click="triggerLazyLoad" variant="outline" class="w-full">
                        Trigger Lazy Load
                    </x-ui.button>
                    
                    <x-ui.button @click="generateMetrics" variant="secondary" class="w-full">
                        Generate Metrics
                    </x-ui.button>
                </div>
                
                <div x-show="showResults" class="mt-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <h4 class="font-medium text-gray-900 dark:text-white mb-2">Demo Results</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400" x-text="resultMessage"></p>
                </div>
            </div>
        </section>

        <!-- Back to Dashboard -->
        <div class="text-center pt-8">
            <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors duration-200">
                ← Back to Dashboard
            </a>
        </div>
    </div>
</div>

<script>
function performanceDemo() {
    return {
        loading: false,
        showResults: false,
        resultMessage: '',
        
        async simulateLoading() {
            this.loading = true;
            this.showResults = false;
            
            // Simulate API call with loading spinner
            await new Promise(resolve => setTimeout(resolve, 2000));
            
            this.loading = false;
            this.showResults = true;
            this.resultMessage = 'API call completed successfully! Loading spinner provided user feedback during the wait.';
            
            // Track performance metric
            if (window.trackPerformance) {
                window.trackPerformance('demo-api-call', 2000);
            }
        },
        
        triggerLazyLoad() {
            // Scroll to lazy load section to trigger loading
            const lazySection = document.querySelector('[x-data*="lazyLoader"]');
            if (lazySection) {
                lazySection.scrollIntoView({ behavior: 'smooth' });
                this.showResults = true;
                this.resultMessage = 'Scrolled to lazy load section. Components will load as they enter the viewport.';
            }
        },
        
        generateMetrics() {
            if (window.performanceMonitor) {
                const metrics = window.performanceMonitor.getMetrics();
                
                // Update metric displays
                document.getElementById('metric-page-load').textContent = 
                    metrics.pageLoad.pageLoad ? Math.round(metrics.pageLoad.pageLoad) : '-';
                document.getElementById('metric-dom-ready').textContent = 
                    metrics.pageLoad.domReady ? Math.round(metrics.pageLoad.domReady) : '-';
                document.getElementById('metric-resources').textContent = 
                    metrics.resourceTiming.length || '-';
                document.getElementById('metric-interactions').textContent = 
                    metrics.userInteractions.length || '-';
                
                this.showResults = true;
                this.resultMessage = `Performance metrics updated! Page load: ${Math.round(metrics.pageLoad.pageLoad || 0)}ms, Resources: ${metrics.resourceTiming.length}, Interactions: ${metrics.userInteractions.length}`;
            }
        }
    }
}

// Initialize demo
document.addEventListener('DOMContentLoaded', function() {
    // Show success notification after page loads
    setTimeout(() => {
        toast.success('Performance demo loaded with monitoring enabled!', {
            title: 'Demo Ready',
            duration: 4000,
            progress: true
        });
    }, 1000);
    
    // Track demo page view
    if (window.trackPerformance) {
        window.trackPerformance('demo-page-view', performance.now());
    }
});
</script>
@endsection