@props([
    'enabled' => true,
    'showDebug' => false,
    'logErrors' => true,
    'trackPageLoad' => true,
    'trackUserInteractions' => true,
    'trackResourceTiming' => true,
    'apiEndpoint' => null
])

@if($enabled)
<div id="performance-monitor" class="hidden">
    @if($showDebug)
        <!-- Debug Panel -->
        <div class="fixed bottom-4 right-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg p-4 max-w-sm z-50"
             x-data="{ show: false }"
             x-show="show"
             x-transition>
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium text-gray-900 dark:text-white">Performance Monitor</h3>
                <button @click="show = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="space-y-2 text-xs">
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Page Load:</span>
                    <span class="font-medium" id="debug-page-load">-</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">DOM Ready:</span>
                    <span class="font-medium" id="debug-dom-ready">-</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Resources:</span>
                    <span class="font-medium" id="debug-resources">-</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Memory:</span>
                    <span class="font-medium" id="debug-memory">-</span>
                </div>
            </div>
        </div>
        
        <!-- Debug Toggle Button -->
        <button @click="show = !show" 
                class="fixed bottom-4 right-20 bg-emerald-600 text-white rounded-full p-2 shadow-lg hover:bg-emerald-700 z-50">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
        </button>
    @endif
</div>

<script>
class PerformanceMonitor {
    constructor(options = {}) {
        this.options = {
            enabled: {{ $enabled ? 'true' : 'false' }},
            showDebug: {{ $showDebug ? 'true' : 'false' }},
            logErrors: {{ $logErrors ? 'true' : 'false' }},
            trackPageLoad: {{ $trackPageLoad ? 'true' : 'false' }},
            trackUserInteractions: {{ $trackUserInteractions ? 'true' : 'false' }},
            trackResourceTiming: {{ $trackResourceTiming ? 'true' : 'false' }},
            apiEndpoint: @json($apiEndpoint),
            ...options
        };
        
        this.metrics = {
            pageLoad: {},
            userInteractions: [],
            resourceTiming: [],
            errors: [],
            customMetrics: {}
        };
        
        this.startTime = performance.now();
        this.init();
    }
    
    init() {
        if (!this.options.enabled) return;
        
        // Track page load performance
        if (this.options.trackPageLoad) {
            this.trackPageLoad();
        }
        
        // Track user interactions
        if (this.options.trackUserInteractions) {
            this.trackUserInteractions();
        }
        
        // Track resource timing
        if (this.options.trackResourceTiming) {
            this.trackResourceTiming();
        }
        
        // Track errors
        if (this.options.logErrors) {
            this.trackErrors();
        }
        
        // Update debug panel
        if (this.options.showDebug) {
            this.startDebugUpdates();
        }
        
        // Send metrics on page unload
        this.setupMetricReporting();
    }
    
    trackPageLoad() {
        // Use Navigation Timing API
        window.addEventListener('load', () => {
            const navigation = performance.getEntriesByType('navigation')[0];
            
            if (navigation) {
                this.metrics.pageLoad = {
                    dns: navigation.domainLookupEnd - navigation.domainLookupStart,
                    tcp: navigation.connectEnd - navigation.connectStart,
                    ssl: navigation.secureConnectionStart > 0 ? navigation.connectEnd - navigation.secureConnectionStart : 0,
                    ttfb: navigation.responseStart - navigation.requestStart,
                    download: navigation.responseEnd - navigation.responseStart,
                    domParse: navigation.domContentLoadedEventEnd - navigation.responseEnd,
                    domReady: navigation.domContentLoadedEventEnd - navigation.navigationStart,
                    pageLoad: navigation.loadEventEnd - navigation.navigationStart,
                    total: performance.now() - this.startTime
                };
                
                // Mark important milestones
                this.markMetric('page-load-complete', this.metrics.pageLoad.pageLoad);
                this.markMetric('dom-ready', this.metrics.pageLoad.domReady);
                this.markMetric('ttfb', this.metrics.pageLoad.ttfb);
                
                // Analyze performance
                this.analyzePagePerformance();
            }
        });
        
        // Track First Contentful Paint and Largest Contentful Paint
        this.trackWebVitals();
    }
    
    trackWebVitals() {
        // First Contentful Paint
        const fcpObserver = new PerformanceObserver((list) => {
            for (const entry of list.getEntries()) {
                if (entry.name === 'first-contentful-paint') {
                    this.markMetric('fcp', entry.startTime);
                }
            }
        });
        fcpObserver.observe({ entryTypes: ['paint'] });
        
        // Largest Contentful Paint
        const lcpObserver = new PerformanceObserver((list) => {
            const entries = list.getEntries();
            const lastEntry = entries[entries.length - 1];
            this.markMetric('lcp', lastEntry.startTime);
        });
        lcpObserver.observe({ entryTypes: ['largest-contentful-paint'] });
        
        // Cumulative Layout Shift
        let clsValue = 0;
        const clsObserver = new PerformanceObserver((list) => {
            for (const entry of list.getEntries()) {
                if (!entry.hadRecentInput) {
                    clsValue += entry.value;
                }
            }
            this.markMetric('cls', clsValue);
        });
        clsObserver.observe({ entryTypes: ['layout-shift'] });
    }
    
    trackUserInteractions() {
        const interactionEvents = ['click', 'scroll', 'keydown', 'touchstart'];
        
        interactionEvents.forEach(eventType => {
            document.addEventListener(eventType, (event) => {
                const interaction = {
                    type: eventType,
                    timestamp: performance.now(),
                    target: this.getElementSelector(event.target),
                    page: window.location.pathname
                };
                
                this.metrics.userInteractions.push(interaction);
                
                // Keep only last 50 interactions to prevent memory issues
                if (this.metrics.userInteractions.length > 50) {
                    this.metrics.userInteractions = this.metrics.userInteractions.slice(-50);
                }
            }, { passive: true });
        });
        
        // Track scroll performance
        let scrollTimeout;
        document.addEventListener('scroll', () => {
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(() => {
                this.markMetric('scroll-end', performance.now());
            }, 100);
        }, { passive: true });
    }
    
    trackResourceTiming() {
        const observer = new PerformanceObserver((list) => {
            for (const entry of list.getEntries()) {
                if (entry.entryType === 'resource') {
                    const resource = {
                        name: entry.name,
                        type: this.getResourceType(entry.name),
                        size: entry.transferSize || entry.encodedBodySize || 0,
                        duration: entry.duration,
                        startTime: entry.startTime
                    };
                    
                    this.metrics.resourceTiming.push(resource);
                    
                    // Log slow resources
                    if (entry.duration > 1000) {
                        console.warn('Slow resource detected:', resource);
                    }
                }
            }
        });
        
        observer.observe({ entryTypes: ['resource'] });
    }
    
    trackErrors() {
        // JavaScript errors
        window.addEventListener('error', (event) => {
            const error = {
                type: 'javascript',
                message: event.message,
                filename: event.filename,
                line: event.lineno,
                column: event.colno,
                stack: event.error?.stack,
                timestamp: performance.now(),
                url: window.location.href
            };
            
            this.metrics.errors.push(error);
            console.error('Performance Monitor - JS Error:', error);
        });
        
        // Promise rejections
        window.addEventListener('unhandledrejection', (event) => {
            const error = {
                type: 'promise-rejection',
                reason: event.reason?.toString(),
                timestamp: performance.now(),
                url: window.location.href
            };
            
            this.metrics.errors.push(error);
            console.error('Performance Monitor - Promise Rejection:', error);
        });
        
        // Resource load errors
        document.addEventListener('error', (event) => {
            if (event.target !== window) {
                const error = {
                    type: 'resource-error',
                    element: event.target.tagName,
                    source: event.target.src || event.target.href,
                    timestamp: performance.now(),
                    url: window.location.href
                };
                
                this.metrics.errors.push(error);
                console.error('Performance Monitor - Resource Error:', error);
            }
        }, true);
    }
    
    markMetric(name, value) {
        this.metrics.customMetrics[name] = {
            value: value,
            timestamp: performance.now()
        };
        
        // Use Performance API for marking
        if (performance.mark) {
            performance.mark(`perf-${name}`);
        }
    }
    
    analyzePagePerformance() {
        const { pageLoad } = this.metrics;
        const recommendations = [];
        
        // Analyze metrics and provide recommendations
        if (pageLoad.ttfb > 600) {
            recommendations.push('TTFB is slow (>600ms). Consider server optimization.');
        }
        
        if (pageLoad.domReady > 2000) {
            recommendations.push('DOM ready time is slow (>2s). Consider reducing JavaScript.');
        }
        
        if (pageLoad.pageLoad > 3000) {
            recommendations.push('Page load time is slow (>3s). Consider optimization.');
        }
        
        if (recommendations.length > 0) {
            console.warn('Performance recommendations:', recommendations);
        }
        
        return recommendations;
    }
    
    startDebugUpdates() {
        setInterval(() => {
            this.updateDebugPanel();
        }, 1000);
    }
    
    updateDebugPanel() {
        const pageLoadEl = document.getElementById('debug-page-load');
        const domReadyEl = document.getElementById('debug-dom-ready');
        const resourcesEl = document.getElementById('debug-resources');
        const memoryEl = document.getElementById('debug-memory');
        
        if (pageLoadEl && this.metrics.pageLoad.pageLoad) {
            pageLoadEl.textContent = `${Math.round(this.metrics.pageLoad.pageLoad)}ms`;
        }
        
        if (domReadyEl && this.metrics.pageLoad.domReady) {
            domReadyEl.textContent = `${Math.round(this.metrics.pageLoad.domReady)}ms`;
        }
        
        if (resourcesEl) {
            resourcesEl.textContent = this.metrics.resourceTiming.length;
        }
        
        if (memoryEl && performance.memory) {
            const used = Math.round(performance.memory.usedJSHeapSize / 1048576);
            memoryEl.textContent = `${used}MB`;
        }
    }
    
    setupMetricReporting() {
        // Send metrics before page unload
        window.addEventListener('beforeunload', () => {
            this.sendMetrics();
        });
        
        // Send metrics periodically for long sessions
        setInterval(() => {
            this.sendMetrics();
        }, 60000); // Every minute
    }
    
    sendMetrics() {
        if (!this.options.apiEndpoint) return;
        
        const payload = {
            ...this.metrics,
            userAgent: navigator.userAgent,
            url: window.location.href,
            timestamp: Date.now(),
            sessionId: this.getSessionId()
        };
        
        // Use sendBeacon for reliable delivery
        if (navigator.sendBeacon) {
            navigator.sendBeacon(this.options.apiEndpoint, JSON.stringify(payload));
        } else {
            // Fallback to fetch with keepalive
            fetch(this.options.apiEndpoint, {
                method: 'POST',
                body: JSON.stringify(payload),
                headers: { 'Content-Type': 'application/json' },
                keepalive: true
            }).catch(err => console.error('Failed to send metrics:', err));
        }
    }
    
    getElementSelector(element) {
        if (!element) return '';
        
        const id = element.id ? `#${element.id}` : '';
        const className = element.className ? `.${element.className.split(' ').join('.')}` : '';
        
        return `${element.tagName.toLowerCase()}${id}${className}`;
    }
    
    getResourceType(url) {
        const extension = url.split('.').pop()?.toLowerCase();
        
        if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(extension)) return 'image';
        if (['css'].includes(extension)) return 'stylesheet';
        if (['js'].includes(extension)) return 'script';
        if (['woff', 'woff2', 'ttf', 'eot'].includes(extension)) return 'font';
        
        return 'other';
    }
    
    getSessionId() {
        let sessionId = sessionStorage.getItem('perf-session-id');
        if (!sessionId) {
            sessionId = 'sess-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
            sessionStorage.setItem('perf-session-id', sessionId);
        }
        return sessionId;
    }
    
    // Public API methods
    getMetrics() {
        return { ...this.metrics };
    }
    
    clearMetrics() {
        this.metrics = {
            pageLoad: {},
            userInteractions: [],
            resourceTiming: [],
            errors: [],
            customMetrics: {}
        };
    }
}

// Initialize performance monitor
document.addEventListener('DOMContentLoaded', () => {
    window.performanceMonitor = new PerformanceMonitor();
    
    // Make it available globally for manual tracking
    window.trackPerformance = (name, value) => {
        window.performanceMonitor.markMetric(name, value);
    };
});
</script>
@endif