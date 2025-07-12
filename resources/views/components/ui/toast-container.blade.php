@props([
    'position' => 'top-right'  // top-right, top-left, bottom-right, bottom-left, top-center, bottom-center
])

@php
    $positionClasses = match($position) {
        'top-right' => 'top-4 right-4',
        'top-left' => 'top-4 left-4',
        'bottom-right' => 'bottom-4 right-4',
        'bottom-left' => 'bottom-4 left-4',
        'top-center' => 'top-4 left-1/2 transform -translate-x-1/2',
        'bottom-center' => 'bottom-4 left-1/2 transform -translate-x-1/2',
        default => 'top-4 right-4',
    };
@endphp

<div 
    x-data="toastContainer()"
    x-init="init()"
    class="fixed {{ $positionClasses }} z-50 space-y-2 max-w-sm w-full"
    id="toast-container">
    
    <!-- Toast notifications will be dynamically inserted here -->
    <template x-for="toast in toasts" :key="toast.id">
        <div 
            x-show="toast.show"
            x-transition:enter="transform ease-out duration-300 transition"
            x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
            x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="relative overflow-hidden rounded-lg border shadow-lg transition-all duration-300 ease-in-out"
            :class="getToastClasses(toast.type)"
            @mouseenter="pauseToast(toast)"
            @mouseleave="resumeToast(toast)">
            
            <!-- Border indicator -->
            <div 
                class="absolute left-0 top-0 bottom-0 w-1"
                :class="getBorderClasses(toast.type)">
            </div>
            
            <div class="p-4 pl-6">
                <div class="flex items-start">
                    <!-- Icon -->
                    <div class="flex-shrink-0" x-show="toast.icon">
                        <div :class="getIconClasses(toast.type)" x-html="getIcon(toast.type)"></div>
                    </div>
                    
                    <div class="ml-3 w-0 flex-1">
                        <!-- Title -->
                        <p 
                            x-show="toast.title" 
                            x-text="toast.title"
                            class="text-sm font-medium">
                        </p>
                        
                        <!-- Message -->
                        <p 
                            x-show="toast.message"
                            x-text="toast.message" 
                            class="text-sm"
                            :class="toast.title ? 'mt-1 opacity-90' : ''">
                        </p>
                        
                        <!-- Actions -->
                        <div x-show="toast.actions && toast.actions.length > 0" class="mt-3 flex space-x-2">
                            <template x-for="action in toast.actions || []" :key="action.label">
                                <button
                                    @click="handleAction(toast, action)"
                                    class="text-xs font-medium rounded px-2 py-1 transition-colors"
                                    :class="getActionClasses(toast.type, action.style || 'primary')"
                                    x-text="action.label">
                                </button>
                            </template>
                        </div>
                    </div>
                    
                    <!-- Dismiss button -->
                    <div class="ml-4 flex-shrink-0 flex" x-show="toast.dismissible">
                        <button 
                            @click="dismissToast(toast)"
                            class="inline-flex text-gray-400 hover:text-gray-600 focus:outline-none focus:text-gray-600 transition ease-in-out duration-150">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Progress bar -->
            <div x-show="toast.progress" class="absolute bottom-0 left-0 right-0 h-1 bg-black/10">
                <div 
                    class="h-full transition-all duration-100"
                    :class="getProgressClasses(toast.type)"
                    :style="`width: ${toast.progressWidth || 100}%`">
                </div>
            </div>
        </div>
    </template>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('toastContainer', () => ({
        toasts: [],
        maxToasts: 5,
        
        init() {
            // Listen for global toast events
            window.addEventListener('show-toast', (event) => {
                this.addToast(event.detail);
            });
            
            // Listen for Laravel flash messages
            this.checkFlashMessages();
        },
        
        checkFlashMessages() {
            // Check for Laravel session flash messages
            @if(session('success'))
                this.addToast({
                    type: 'success',
                    title: 'Success',
                    message: @json(session('success')),
                    duration: 5000
                });
            @endif
            
            @if(session('error'))
                this.addToast({
                    type: 'error',
                    title: 'Error',
                    message: @json(session('error')),
                    duration: 7000
                });
            @endif
            
            @if(session('warning'))
                this.addToast({
                    type: 'warning',
                    title: 'Warning',
                    message: @json(session('warning')),
                    duration: 6000
                });
            @endif
            
            @if(session('info'))
                this.addToast({
                    type: 'info',
                    title: 'Information',
                    message: @json(session('info')),
                    duration: 5000
                });
            @endif
        },
        
        addToast(options) {
            const toast = {
                id: 'toast-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9),
                type: options.type || 'info',
                title: options.title || '',
                message: options.message || '',
                duration: options.duration || 5000,
                dismissible: options.dismissible !== false,
                icon: options.icon !== false,
                progress: options.progress || false,
                actions: options.actions || [],
                show: true,
                timer: null,
                progressTimer: null,
                progressWidth: 100
            };
            
            // Remove oldest toast if we've reached the limit
            if (this.toasts.length >= this.maxToasts) {
                this.dismissToast(this.toasts[0]);
            }
            
            this.toasts.push(toast);
            
            // Auto-dismiss after duration
            if (toast.duration > 0) {
                this.startTimer(toast);
                
                if (toast.progress) {
                    this.startProgress(toast);
                }
            }
            
            return toast;
        },
        
        dismissToast(toast) {
            const index = this.toasts.findIndex(t => t.id === toast.id);
            if (index !== -1) {
                // Clear timers
                if (toast.timer) clearTimeout(toast.timer);
                if (toast.progressTimer) clearInterval(toast.progressTimer);
                
                // Hide with animation
                toast.show = false;
                
                // Remove from array after animation
                setTimeout(() => {
                    const currentIndex = this.toasts.findIndex(t => t.id === toast.id);
                    if (currentIndex !== -1) {
                        this.toasts.splice(currentIndex, 1);
                    }
                }, 300);
            }
        },
        
        pauseToast(toast) {
            if (toast.timer) {
                clearTimeout(toast.timer);
                toast.timer = null;
            }
            if (toast.progressTimer) {
                clearInterval(toast.progressTimer);
                toast.progressTimer = null;
            }
        },
        
        resumeToast(toast) {
            if (toast.duration > 0 && !toast.timer) {
                const remainingTime = (toast.progressWidth / 100) * toast.duration;
                this.startTimer(toast, remainingTime);
                
                if (toast.progress) {
                    this.startProgress(toast, toast.progressWidth);
                }
            }
        },
        
        startTimer(toast, duration = null) {
            toast.timer = setTimeout(() => {
                this.dismissToast(toast);
            }, duration || toast.duration);
        },
        
        startProgress(toast, startWidth = 100) {
            const steps = 100;
            const stepDuration = toast.duration / steps;
            let currentStep = 100 - startWidth;
            
            toast.progressWidth = startWidth;
            
            toast.progressTimer = setInterval(() => {
                currentStep++;
                toast.progressWidth = ((steps - currentStep) / steps) * 100;
                
                if (currentStep >= steps) {
                    clearInterval(toast.progressTimer);
                }
            }, stepDuration);
        },
        
        handleAction(toast, action) {
            if (typeof action.callback === 'function') {
                action.callback(toast);
            } else if (action.url) {
                window.location.href = action.url;
            }
            
            if (action.dismiss !== false) {
                this.dismissToast(toast);
            }
        },
        
        getToastClasses(type) {
            const classes = {
                'success': 'bg-emerald-50 border-emerald-200 text-emerald-900 dark:bg-emerald-900/20 dark:border-emerald-800 dark:text-emerald-100',
                'error': 'bg-red-50 border-red-200 text-red-900 dark:bg-red-900/20 dark:border-red-800 dark:text-red-100',
                'warning': 'bg-amber-50 border-amber-200 text-amber-900 dark:bg-amber-900/20 dark:border-amber-800 dark:text-amber-100',
                'info': 'bg-blue-50 border-blue-200 text-blue-900 dark:bg-blue-900/20 dark:border-blue-800 dark:text-blue-100'
            };
            return classes[type] || classes.info;
        },
        
        getBorderClasses(type) {
            const classes = {
                'success': 'bg-emerald-500',
                'error': 'bg-red-500',
                'warning': 'bg-amber-500',
                'info': 'bg-blue-500'
            };
            return classes[type] || classes.info;
        },
        
        getIconClasses(type) {
            const classes = {
                'success': 'h-5 w-5 text-emerald-600 dark:text-emerald-400',
                'error': 'h-5 w-5 text-red-600 dark:text-red-400',
                'warning': 'h-5 w-5 text-amber-600 dark:text-amber-400',
                'info': 'h-5 w-5 text-blue-600 dark:text-blue-400'
            };
            return classes[type] || classes.info;
        },
        
        getProgressClasses(type) {
            const classes = {
                'success': 'bg-emerald-500',
                'error': 'bg-red-500',
                'warning': 'bg-amber-500',
                'info': 'bg-blue-500'
            };
            return classes[type] || classes.info;
        },
        
        getActionClasses(type, style) {
            if (style === 'secondary') {
                return 'bg-gray-100 text-gray-700 hover:bg-gray-200';
            }
            
            const classes = {
                'success': 'bg-emerald-500 text-white hover:bg-emerald-600',
                'error': 'bg-red-500 text-white hover:bg-red-600',
                'warning': 'bg-amber-500 text-white hover:bg-amber-600',
                'info': 'bg-blue-500 text-white hover:bg-blue-600'
            };
            return classes[type] || classes.info;
        },
        
        getIcon(type) {
            const icons = {
                'success': '<svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>',
                'error': '<svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>',
                'warning': '<svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>',
                'info': '<svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" /></svg>'
            };
            return icons[type] || icons.info;
        }
    }));
});

// Global toast helper functions
window.showToast = function(options) {
    window.dispatchEvent(new CustomEvent('show-toast', { detail: options }));
};

window.toast = {
    success: (message, options = {}) => showToast({ type: 'success', message, ...options }),
    error: (message, options = {}) => showToast({ type: 'error', message, ...options }),
    warning: (message, options = {}) => showToast({ type: 'warning', message, ...options }),
    info: (message, options = {}) => showToast({ type: 'info', message, ...options })
};
</script>