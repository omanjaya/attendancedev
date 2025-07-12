@props([
    'enabled' => app()->environment('production'),
    'sampleRate' => 0.1,
    'reportEndpoint' => '/api/performance',
    'thresholds' => [
        'fcp' => 2500,  // First Contentful Paint
        'lcp' => 4000,  // Largest Contentful Paint
        'fid' => 100,   // First Input Delay
        'cls' => 0.1,   // Cumulative Layout Shift
        'ttfb' => 800   // Time to First Byte
    ],
    'enableConsoleLogs' => false,
    'enableBeacon' => true
])

@if($enabled)
<script>
// Performance monitoring utility
class PerformanceMonitor {
    constructor(config) {
        this.config = {
            sampleRate: 0.1,
            reportEndpoint: '/api/performance',
            thresholds: {
                fcp: 2500,
                lcp: 4000,
                fid: 100,
                cls: 0.1,
                ttfb: 800
            },
            enableConsoleLogs: false,
            enableBeacon: true,
            ...config
        };
        
        this.metrics = new Map();
        this.observer = null;
        this.reportQueue = [];
        this.sessionId = this.generateSessionId();
        
        // Only monitor a sample of sessions
        if (Math.random() > this.config.sampleRate) {
            return;
        }
        
        this.init();
    }
    
    init() {
        // Web Vitals monitoring
        this.measureWebVitals();
        
        // Custom metrics
        this.measureNavigationTiming();
        this.measureResourceTiming();
        this.measureCustomMetrics();
        
        // Setup reporting
        this.setupReporting();
        
        if (this.config.enableConsoleLogs) {
            console.log('Performance Monitor initialized');
        }
    }
    
    generateSessionId() {
        return 'perf_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }
    
    measureWebVitals() {
        // First Contentful Paint (FCP)
        this.measureFCP();
        
        // Largest Contentful Paint (LCP)
        this.measureLCP();
        
        // First Input Delay (FID)
        this.measureFID();
        
        // Cumulative Layout Shift (CLS)
        this.measureCLS();
        
        // Time to First Byte (TTFB)
        this.measureTTFB();
    }
    
    measureFCP() {
        if ('PerformanceObserver' in window) {
            const observer = new PerformanceObserver((list) => {
                const entries = list.getEntries();
                const fcp = entries.find(entry => entry.name === 'first-contentful-paint');
                
                if (fcp) {
                    this.recordMetric('fcp', fcp.startTime, 'ms');
                    observer.disconnect();
                }
            });
            
            observer.observe({ entryTypes: ['paint'] });
        }
    }
    
    measureLCP() {
        if ('PerformanceObserver' in window) {
            const observer = new PerformanceObserver((list) => {
                const entries = list.getEntries();
                const lastEntry = entries[entries.length - 1];
                
                if (lastEntry) {
                    this.recordMetric('lcp', lastEntry.startTime, 'ms');
                }
            });
            
            observer.observe({ entryTypes: ['largest-contentful-paint'] });
            
            // Stop observing after page lifecycle changes
            ['keydown', 'click'].forEach((type) => {
                addEventListener(type, () => observer.disconnect(), {
                    once: true,
                    passive: true
                });
            });
        }
    }
    
    measureFID() {
        if ('PerformanceObserver' in window) {
            const observer = new PerformanceObserver((list) => {
                const entries = list.getEntries();
                entries.forEach((entry) => {
                    this.recordMetric('fid', entry.processingStart - entry.startTime, 'ms');
                });
                observer.disconnect();
            });
            
            observer.observe({ entryTypes: ['first-input'] });
        }
    }
    
    measureCLS() {
        if ('PerformanceObserver' in window) {
            let clsValue = 0;
            let sessionValue = 0;
            let sessionEntries = [];
            
            const observer = new PerformanceObserver((list) => {
                const entries = list.getEntries();
                
                entries.forEach((entry) => {
                    if (!entry.hadRecentInput) {
                        const firstSessionEntry = sessionEntries[0];
                        const lastSessionEntry = sessionEntries[sessionEntries.length - 1];
                        
                        if (sessionValue && 
                            entry.startTime - lastSessionEntry.startTime < 1000 &&
                            entry.startTime - firstSessionEntry.startTime < 5000) {
                            sessionValue += entry.value;
                            sessionEntries.push(entry);
                        } else {
                            sessionValue = entry.value;
                            sessionEntries = [entry];
                        }
                        
                        if (sessionValue > clsValue) {
                            clsValue = sessionValue;
                            this.recordMetric('cls', clsValue, 'score');
                        }
                    }
                });
            });
            
            observer.observe({ entryTypes: ['layout-shift'] });
        }
    }
    
    measureTTFB() {
        if ('performance' in window && 'getEntriesByType' in performance) {
            const navigation = performance.getEntriesByType('navigation')[0];
            if (navigation) {
                const ttfb = navigation.responseStart - navigation.requestStart;
                this.recordMetric('ttfb', ttfb, 'ms');
            }
        }
    }
    
    measureNavigationTiming() {
        if ('performance' in window && 'getEntriesByType' in performance) {
            const navigation = performance.getEntriesByType('navigation')[0];
            if (navigation) {
                this.recordMetric('dom_content_loaded', navigation.domContentLoadedEventEnd - navigation.domContentLoadedEventStart, 'ms');
                this.recordMetric('load_complete', navigation.loadEventEnd - navigation.loadEventStart, 'ms');
                this.recordMetric('dom_interactive', navigation.domInteractive - navigation.fetchStart, 'ms');
                this.recordMetric('dom_complete', navigation.domComplete - navigation.fetchStart, 'ms');
            }
        }
    }
    
    measureResourceTiming() {
        if ('PerformanceObserver' in window) {
            const observer = new PerformanceObserver((list) => {
                const entries = list.getEntries();
                
                entries.forEach((entry) => {
                    // Categorize resources
                    let category = 'other';
                    
                    if (entry.name.includes('.css')) category = 'css';
                    else if (entry.name.includes('.js')) category = 'javascript';
                    else if (entry.name.match(/\.(jpg|jpeg|png|gif|webp|svg)$/i)) category = 'image';
                    else if (entry.name.includes('/api/')) category = 'api';
                    
                    const duration = entry.responseEnd - entry.startTime;
                    
                    this.recordMetric(`resource_${category}_duration`, duration, 'ms');
                    this.recordMetric(`resource_${category}_size`, entry.transferSize || 0, 'bytes');
                });
            });
            
            observer.observe({ entryTypes: ['resource'] });
        }
    }
    
    measureCustomMetrics() {
        // Vue app mounting time
        this.measureVuePerformance();
        
        // Alpine.js initialization time
        this.measureAlpinePerformance();
        
        // Service Worker metrics
        this.measureServiceWorkerPerformance();
    }
    
    measureVuePerformance() {
        // Listen for Vue app mount events
        window.addEventListener('vue-app-mounted', (event) => {
            const duration = event.detail.duration;
            this.recordMetric('vue_mount_time', duration, 'ms');
        });
    }
    
    measureAlpinePerformance() {
        // Monitor Alpine.js initialization
        if (window.Alpine) {
            const startTime = performance.now();
            
            document.addEventListener('alpine:init', () => {
                const duration = performance.now() - startTime;
                this.recordMetric('alpine_init_time', duration, 'ms');
            });
        }
    }
    
    measureServiceWorkerPerformance() {
        if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
            // Measure cache hit ratio
            let cacheHits = 0;
            let totalRequests = 0;
            
            // Override fetch to track cache performance
            const originalFetch = window.fetch;
            window.fetch = function(...args) {
                totalRequests++;
                
                return originalFetch.apply(this, args).then((response) => {
                    // Check if response came from cache
                    if (response.headers.get('x-cache-status') === 'hit') {
                        cacheHits++;
                    }
                    
                    return response;
                });
            };
            
            // Report cache hit ratio periodically
            setInterval(() => {
                if (totalRequests > 0) {
                    const hitRatio = (cacheHits / totalRequests) * 100;
                    this.recordMetric('cache_hit_ratio', hitRatio, 'percentage');
                    
                    // Send to service worker for monitoring
                    if (navigator.serviceWorker.controller) {
                        navigator.serviceWorker.controller.postMessage({
                            action: 'performance',
                            value: hitRatio
                        });
                    }
                }
            }, 30000); // Every 30 seconds
        }
    }
    
    recordMetric(name, value, unit = 'ms') {
        const metric = {
            name,
            value: Math.round(value * 100) / 100, // Round to 2 decimal places
            unit,
            timestamp: Date.now(),
            url: window.location.href,
            userAgent: navigator.userAgent,
            sessionId: this.sessionId
        };
        
        this.metrics.set(name, metric);
        
        // Check thresholds
        this.checkThreshold(name, value);
        
        if (this.config.enableConsoleLogs) {
            console.log(`Performance Metric: ${name} = ${value}${unit}`);
        }
        
        // Queue for reporting
        this.reportQueue.push(metric);
    }
    
    checkThreshold(name, value) {
        const threshold = this.config.thresholds[name];
        if (threshold && value > threshold) {
            console.warn(`Performance threshold exceeded: ${name} (${value}) > ${threshold}`);
            
            // Dispatch custom event for threshold violations
            window.dispatchEvent(new CustomEvent('performance-threshold-exceeded', {
                detail: { name, value, threshold }
            }));
        }
    }
    
    setupReporting() {
        // Report on page unload
        window.addEventListener('beforeunload', () => {
            this.sendReport();
        });
        
        // Report periodically
        setInterval(() => {
            if (this.reportQueue.length > 0) {
                this.sendReport();
            }
        }, 30000); // Every 30 seconds
        
        // Report on visibility change (when user switches tabs)
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'hidden') {
                this.sendReport();
            }
        });
    }
    
    sendReport() {
        if (this.reportQueue.length === 0) return;
        
        const report = {
            metrics: [...this.reportQueue],
            sessionId: this.sessionId,
            timestamp: Date.now(),
            page: {
                url: window.location.href,
                title: document.title,
                referrer: document.referrer
            },
            browser: {
                userAgent: navigator.userAgent,
                language: navigator.language,
                cookieEnabled: navigator.cookieEnabled,
                onLine: navigator.onLine
            },
            screen: {
                width: screen.width,
                height: screen.height,
                pixelRatio: window.devicePixelRatio
            }
        };
        
        // Use sendBeacon for reliable delivery
        if (this.config.enableBeacon && 'sendBeacon' in navigator) {
            navigator.sendBeacon(
                this.config.reportEndpoint,
                JSON.stringify(report)
            );
        } else {
            // Fallback to fetch
            fetch(this.config.reportEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(report),
                keepalive: true
            }).catch(error => {
                console.error('Failed to send performance report:', error);
            });
        }
        
        // Clear the queue
        this.reportQueue = [];
        
        if (this.config.enableConsoleLogs) {
            console.log('Performance report sent', report);
        }
    }
    
    // Public API methods
    getMetrics() {
        return Object.fromEntries(this.metrics);
    }
    
    getMetric(name) {
        return this.metrics.get(name);
    }
    
    markStart(name) {
        performance.mark(`${name}-start`);
    }
    
    markEnd(name) {
        performance.mark(`${name}-end`);
        performance.measure(name, `${name}-start`, `${name}-end`);
        
        const measure = performance.getEntriesByName(name, 'measure')[0];
        if (measure) {
            this.recordMetric(name, measure.duration, 'ms');
        }
    }
}

// Initialize performance monitor
const performanceMonitor = new PerformanceMonitor({
    sampleRate: {{ $sampleRate }},
    reportEndpoint: '{{ $reportEndpoint }}',
    thresholds: @json($thresholds),
    enableConsoleLogs: {{ $enableConsoleLogs ? 'true' : 'false' }},
    enableBeacon: {{ $enableBeacon ? 'true' : 'false' }}
});

// Expose globally for manual measurements
window.performanceMonitor = performanceMonitor;

// Helper functions for common measurements
window.measureUserInteraction = function(name, fn) {
    performanceMonitor.markStart(name);
    const result = fn();
    
    if (result && result.then) {
        // Handle promises
        return result.finally(() => {
            performanceMonitor.markEnd(name);
        });
    } else {
        performanceMonitor.markEnd(name);
        return result;
    }
};

window.measureAPICall = function(url, options = {}) {
    const startTime = performance.now();
    
    return fetch(url, options).then(response => {
        const duration = performance.now() - startTime;
        performanceMonitor.recordMetric('api_response_time', duration, 'ms');
        
        // Record status code metrics
        const statusCategory = Math.floor(response.status / 100) * 100;
        performanceMonitor.recordMetric(`api_status_${statusCategory}`, 1, 'count');
        
        return response;
    });
};
</script>
@endif