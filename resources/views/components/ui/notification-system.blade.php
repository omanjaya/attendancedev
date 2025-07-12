@props([
    'position' => 'top-right',
    'maxNotifications' => 5,
    'defaultDuration' => 5000,
    'enableSounds' => true,
    'enableVibration' => true,
    'persistOnHover' => true,
    'enableProgress' => true,
    'enableActions' => true,
    'enableGrouping' => true,
    'animationDuration' => 300
])

@php
    $positionClasses = [
        'top-left' => 'top-4 left-4',
        'top-right' => 'top-4 right-4',
        'top-center' => 'top-4 left-1/2 transform -translate-x-1/2',
        'bottom-left' => 'bottom-4 left-4',
        'bottom-right' => 'bottom-4 right-4',
        'bottom-center' => 'bottom-4 left-1/2 transform -translate-x-1/2'
    ];
    
    $positionClass = $positionClasses[$position] ?? $positionClasses['top-right'];
@endphp

<!-- Notification Container -->
<div class="fixed {{ $positionClass }} z-[9999] pointer-events-none max-w-sm w-full space-y-2"
     x-data="notificationSystem({
        position: '{{ $position }}',
        maxNotifications: {{ $maxNotifications }},
        defaultDuration: {{ $defaultDuration }},
        enableSounds: {{ $enableSounds ? 'true' : 'false' }},
        enableVibration: {{ $enableVibration ? 'true' : 'false' }},
        persistOnHover: {{ $persistOnHover ? 'true' : 'false' }},
        enableProgress: {{ $enableProgress ? 'true' : 'false' }},
        enableActions: {{ $enableActions ? 'true' : 'false' }},
        enableGrouping: {{ $enableGrouping ? 'true' : 'false' }},
        animationDuration: {{ $animationDuration }}
     })"
     x-init="init()"
     id="notification-system">
     
    <!-- Notifications -->
    <template x-for="notification in notifications" :key="notification.id">
        <div class="notification-item pointer-events-auto relative transform transition-all duration-300"
             :class="{
                'animate-slide-in-right': position.includes('right') && notification.isEntering,
                'animate-slide-in-left': position.includes('left') && notification.isEntering,
                'animate-slide-in-down': position.includes('top') && notification.isEntering,
                'animate-slide-in-up': position.includes('bottom') && notification.isEntering,
                'animate-slide-out-right': position.includes('right') && notification.isLeaving,
                'animate-slide-out-left': position.includes('left') && notification.isLeaving,
                'animate-slide-out-up': position.includes('top') && notification.isLeaving,
                'animate-slide-out-down': position.includes('bottom') && notification.isLeaving,
                'scale-105': notification.isHovered
             }"
             @mouseenter="handleMouseEnter(notification)"
             @mouseleave="handleMouseLeave(notification)"
             x-show="!notification.isRemoved">
            
            <div class="bg-card border border-border rounded-lg shadow-lg overflow-hidden min-w-0"
                 :class="{
                    'border-l-4 border-l-blue-500': notification.type === 'info',
                    'border-l-4 border-l-green-500': notification.type === 'success',
                    'border-l-4 border-l-yellow-500': notification.type === 'warning',
                    'border-l-4 border-l-red-500': notification.type === 'error'
                 }">
                
                <!-- Progress Bar -->
                <div x-show="enableProgress && notification.progress !== null"
                     class="h-1 bg-muted overflow-hidden">
                    <div class="h-full bg-primary transition-all duration-100 ease-linear"
                         :style="`width: ${notification.progress}%`">
                    </div>
                </div>
                
                <!-- Main Content -->
                <div class="p-4">
                    <div class="flex items-start space-x-3">
                        <!-- Icon -->
                        <div class="flex-shrink-0">
                            <div class="w-5 h-5 flex items-center justify-center">
                                <!-- Info Icon -->
                                <svg x-show="notification.type === 'info'" class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                </svg>
                                
                                <!-- Success Icon -->
                                <svg x-show="notification.type === 'success'" class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                
                                <!-- Warning Icon -->
                                <svg x-show="notification.type === 'warning'" class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                
                                <!-- Error Icon -->
                                <svg x-show="notification.type === 'error'" class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        </div>
                        
                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <!-- Title -->
                            <h3 x-show="notification.title" 
                                class="text-sm font-semibold text-foreground mb-1"
                                x-text="notification.title">
                            </h3>
                            
                            <!-- Message -->
                            <p class="text-sm text-muted-foreground"
                               :class="{ 'font-medium text-foreground': !notification.title }"
                               x-html="notification.message">
                            </p>
                            
                            <!-- Actions -->
                            <div x-show="enableActions && notification.actions && notification.actions.length > 0"
                                 class="mt-3 flex space-x-2">
                                <template x-for="action in notification.actions" :key="action.id">
                                    <button class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md transition-colors"
                                            :class="{
                                                'bg-primary text-primary-foreground hover:bg-primary/90': action.variant === 'primary',
                                                'bg-secondary text-secondary-foreground hover:bg-secondary/80': action.variant === 'secondary',
                                                'text-foreground hover:bg-muted': !action.variant || action.variant === 'ghost'
                                            }"
                                            @click="handleAction(notification, action)"
                                            x-text="action.label">
                                    </button>
                                </template>
                            </div>
                            
                            <!-- Metadata -->
                            <div x-show="notification.timestamp"
                                 class="mt-2 text-xs text-muted-foreground"
                                 x-text="formatTimestamp(notification.timestamp)">
                            </div>
                        </div>
                        
                        <!-- Close Button -->
                        <div class="flex-shrink-0">
                            <button class="w-5 h-5 flex items-center justify-center text-muted-foreground hover:text-foreground transition-colors"
                                    @click="removeNotification(notification.id)">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Group Indicator -->
                <div x-show="notification.groupCount > 1"
                     class="px-4 py-2 bg-muted border-t border-border">
                    <p class="text-xs text-muted-foreground">
                        <span x-text="notification.groupCount"></span> similar notifications
                        <button class="ml-2 text-primary hover:underline" @click="expandGroup(notification)">
                            Show all
                        </button>
                    </p>
                </div>
            </div>
        </div>
    </template>
    
    <!-- Notification Queue Indicator -->
    <div x-show="queuedCount > 0"
         class="notification-item pointer-events-auto">
        <div class="bg-muted border border-border rounded-lg shadow-lg p-3">
            <div class="flex items-center justify-between">
                <p class="text-sm text-muted-foreground">
                    <span x-text="queuedCount"></span> more notification<span x-show="queuedCount > 1">s</span>
                </p>
                <button class="text-xs text-primary hover:underline" @click="showAll()">
                    Show all
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function notificationSystem(config) {
    return {
        position: config.position,
        maxNotifications: config.maxNotifications,
        defaultDuration: config.defaultDuration,
        enableSounds: config.enableSounds,
        enableVibration: config.enableVibration,
        persistOnHover: config.persistOnHover,
        enableProgress: config.enableProgress,
        enableActions: config.enableActions,
        enableGrouping: config.enableGrouping,
        animationDuration: config.animationDuration,
        
        notifications: [],
        queue: [],
        nextId: 1,
        
        get queuedCount() {
            return this.queue.length;
        },
        
        init() {
            // Listen for notification events
            this.setupEventListeners();
            
            // Cleanup on page unload
            window.addEventListener('beforeunload', () => {
                this.cleanup();
            });
        },
        
        setupEventListeners() {
            // Global notification events
            window.addEventListener('notify', (event) => {
                this.show(event.detail);
            });
            
            window.addEventListener('notify:success', (event) => {
                this.success(event.detail.message, event.detail.title, event.detail.options);
            });
            
            window.addEventListener('notify:error', (event) => {
                this.error(event.detail.message, event.detail.title, event.detail.options);
            });
            
            window.addEventListener('notify:warning', (event) => {
                this.warning(event.detail.message, event.detail.title, event.detail.options);
            });
            
            window.addEventListener('notify:info', (event) => {
                this.info(event.detail.message, event.detail.title, event.detail.options);
            });
            
            window.addEventListener('notify:clear', () => {
                this.clearAll();
            });
        },
        
        show(options) {
            const notification = this.createNotification(options);
            
            // Check grouping
            if (this.enableGrouping) {
                const existing = this.findSimilarNotification(notification);
                if (existing) {
                    this.groupNotification(existing, notification);
                    return existing.id;
                }
            }
            
            // Add to queue if at max capacity
            if (this.notifications.length >= this.maxNotifications) {
                this.queue.push(notification);
                return notification.id;
            }
            
            this.addNotification(notification);
            return notification.id;
        },
        
        createNotification(options) {
            const id = this.nextId++;
            const timestamp = Date.now();
            
            return {
                id,
                type: options.type || 'info',
                title: options.title || null,
                message: options.message || '',
                duration: options.duration !== undefined ? options.duration : this.defaultDuration,
                persistent: options.persistent || false,
                actions: options.actions || [],
                data: options.data || {},
                timestamp,
                groupCount: 1,
                isEntering: true,
                isLeaving: false,
                isRemoved: false,
                isHovered: false,
                progress: this.enableProgress && !options.persistent ? 100 : null,
                timer: null,
                progressTimer: null
            };
        },
        
        addNotification(notification) {
            this.notifications.unshift(notification);
            
            // Play sound
            if (this.enableSounds) {
                this.playNotificationSound(notification.type);
            }
            
            // Vibrate
            if (this.enableVibration && 'vibrate' in navigator) {
                const patterns = {
                    info: [100],
                    success: [100, 50, 100],
                    warning: [200, 100, 200],
                    error: [300, 100, 300, 100, 300]
                };
                navigator.vibrate(patterns[notification.type] || [100]);
            }
            
            // Setup auto-removal
            if (!notification.persistent && notification.duration > 0) {
                this.setupAutoRemoval(notification);
            }
            
            // Animation
            setTimeout(() => {
                notification.isEntering = false;
            }, this.animationDuration);
        },
        
        setupAutoRemoval(notification) {
            if (this.enableProgress) {
                this.startProgress(notification);
            }
            
            notification.timer = setTimeout(() => {
                this.removeNotification(notification.id);
            }, notification.duration);
        },
        
        startProgress(notification) {
            const duration = notification.duration;
            const interval = 50; // Update every 50ms
            const steps = duration / interval;
            const decrement = 100 / steps;
            
            notification.progressTimer = setInterval(() => {
                if (notification.progress > 0 && !notification.isHovered) {
                    notification.progress = Math.max(0, notification.progress - decrement);
                }
            }, interval);
        },
        
        removeNotification(id) {
            const notification = this.notifications.find(n => n.id === id);
            if (!notification) return;
            
            // Clear timers
            if (notification.timer) {
                clearTimeout(notification.timer);
            }
            if (notification.progressTimer) {
                clearInterval(notification.progressTimer);
            }
            
            // Start exit animation
            notification.isLeaving = true;
            
            setTimeout(() => {
                notification.isRemoved = true;
                
                // Remove from array after animation
                setTimeout(() => {
                    const index = this.notifications.findIndex(n => n.id === id);
                    if (index > -1) {
                        this.notifications.splice(index, 1);
                        this.processQueue();
                    }
                }, this.animationDuration);
            }, 50);
        },
        
        processQueue() {
            if (this.queue.length > 0 && this.notifications.length < this.maxNotifications) {
                const next = this.queue.shift();
                this.addNotification(next);
            }
        },
        
        findSimilarNotification(notification) {
            return this.notifications.find(n => 
                n.type === notification.type &&
                n.message === notification.message &&
                !n.isLeaving &&
                !n.isRemoved
            );
        },
        
        groupNotification(existing, new_notification) {
            existing.groupCount++;
            existing.timestamp = new_notification.timestamp;
            
            // Reset timer
            if (existing.timer) {
                clearTimeout(existing.timer);
                if (!existing.persistent && existing.duration > 0) {
                    this.setupAutoRemoval(existing);
                }
            }
        },
        
        handleMouseEnter(notification) {
            if (this.persistOnHover) {
                notification.isHovered = true;
            }
        },
        
        handleMouseLeave(notification) {
            if (this.persistOnHover) {
                notification.isHovered = false;
            }
        },
        
        handleAction(notification, action) {
            if (action.handler) {
                action.handler(notification, action);
            }
            
            this.$dispatch('notification-action', {
                notification,
                action
            });
            
            if (action.dismissOnClick !== false) {
                this.removeNotification(notification.id);
            }
        },
        
        expandGroup(notification) {
            // Implementation for expanding grouped notifications
            this.$dispatch('notification-group-expand', { notification });
        },
        
        showAll() {
            const toShow = Math.min(this.queue.length, this.maxNotifications - this.notifications.length);
            for (let i = 0; i < toShow; i++) {
                const notification = this.queue.shift();
                this.addNotification(notification);
            }
        },
        
        clearAll() {
            this.notifications.forEach(notification => {
                this.removeNotification(notification.id);
            });
            this.queue = [];
        },
        
        formatTimestamp(timestamp) {
            const now = Date.now();
            const diff = now - timestamp;
            
            if (diff < 60000) { // Less than 1 minute
                return 'Just now';
            } else if (diff < 3600000) { // Less than 1 hour
                const minutes = Math.floor(diff / 60000);
                return `${minutes}m ago`;
            } else {
                const hours = Math.floor(diff / 3600000);
                return `${hours}h ago`;
            }
        },
        
        playNotificationSound(type) {
            if (!this.enableSounds) return;
            
            try {
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const frequencies = {
                    info: [800, 600],
                    success: [600, 800, 1000],
                    warning: [800, 600, 800],
                    error: [400, 300, 400]
                };
                
                const freq = frequencies[type] || frequencies.info;
                this.playTone(audioContext, freq);
            } catch (error) {
                console.warn('Could not play notification sound:', error);
            }
        },
        
        playTone(audioContext, frequencies) {
            frequencies.forEach((freq, index) => {
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();
                
                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);
                
                oscillator.frequency.value = freq;
                oscillator.type = 'sine';
                
                gainNode.gain.setValueAtTime(0, audioContext.currentTime + index * 0.1);
                gainNode.gain.linearRampToValueAtTime(0.1, audioContext.currentTime + index * 0.1 + 0.01);
                gainNode.gain.linearRampToValueAtTime(0, audioContext.currentTime + index * 0.1 + 0.1);
                
                oscillator.start(audioContext.currentTime + index * 0.1);
                oscillator.stop(audioContext.currentTime + index * 0.1 + 0.1);
            });
        },
        
        cleanup() {
            this.notifications.forEach(notification => {
                if (notification.timer) clearTimeout(notification.timer);
                if (notification.progressTimer) clearInterval(notification.progressTimer);
            });
        },
        
        // Public API methods
        success(message, title = null, options = {}) {
            return this.show({
                type: 'success',
                message,
                title,
                ...options
            });
        },
        
        error(message, title = null, options = {}) {
            return this.show({
                type: 'error',
                message,
                title,
                duration: options.duration !== undefined ? options.duration : 8000,
                ...options
            });
        },
        
        warning(message, title = null, options = {}) {
            return this.show({
                type: 'warning',
                message,
                title,
                ...options
            });
        },
        
        info(message, title = null, options = {}) {
            return this.show({
                type: 'info',
                message,
                title,
                ...options
            });
        }
    };
}

// Global notification API
window.notify = {
    show: (options) => window.dispatchEvent(new CustomEvent('notify', { detail: options })),
    success: (message, title, options) => window.dispatchEvent(new CustomEvent('notify:success', { detail: { message, title, options } })),
    error: (message, title, options) => window.dispatchEvent(new CustomEvent('notify:error', { detail: { message, title, options } })),
    warning: (message, title, options) => window.dispatchEvent(new CustomEvent('notify:warning', { detail: { message, title, options } })),
    info: (message, title, options) => window.dispatchEvent(new CustomEvent('notify:info', { detail: { message, title, options } })),
    clear: () => window.dispatchEvent(new CustomEvent('notify:clear'))
};
</script>

<style>
/* Notification animations */
@keyframes slide-in-right {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes slide-in-left {
    from {
        transform: translateX(-100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes slide-in-down {
    from {
        transform: translateY(-100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

@keyframes slide-in-up {
    from {
        transform: translateY(100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

@keyframes slide-out-right {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(100%);
        opacity: 0;
    }
}

@keyframes slide-out-left {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(-100%);
        opacity: 0;
    }
}

@keyframes slide-out-up {
    from {
        transform: translateY(0);
        opacity: 1;
    }
    to {
        transform: translateY(-100%);
        opacity: 0;
    }
}

@keyframes slide-out-down {
    from {
        transform: translateY(0);
        opacity: 1;
    }
    to {
        transform: translateY(100%);
        opacity: 0;
    }
}

.animate-slide-in-right {
    animation: slide-in-right 0.3s ease-out;
}

.animate-slide-in-left {
    animation: slide-in-left 0.3s ease-out;
}

.animate-slide-in-down {
    animation: slide-in-down 0.3s ease-out;
}

.animate-slide-in-up {
    animation: slide-in-up 0.3s ease-out;
}

.animate-slide-out-right {
    animation: slide-out-right 0.3s ease-in;
}

.animate-slide-out-left {
    animation: slide-out-left 0.3s ease-in;
}

.animate-slide-out-up {
    animation: slide-out-up 0.3s ease-in;
}

.animate-slide-out-down {
    animation: slide-out-down 0.3s ease-in;
}

/* Reduce motion for accessibility */
@media (prefers-reduced-motion: reduce) {
    .notification-item {
        animation: none !important;
        transition: none !important;
    }
}
</style>