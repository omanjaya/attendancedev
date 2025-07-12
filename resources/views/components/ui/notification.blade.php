@props([
    'type' => 'success',       // success, error, warning, info
    'title' => '',
    'message' => '',
    'duration' => 5000,
    'dismissible' => true,
    'icon' => true,
    'progress' => false,
    'id' => null
])

@php
    $baseClasses = 'relative overflow-hidden rounded-lg border shadow-lg transition-all duration-300 ease-in-out max-w-sm';
    
    $typeClasses = match($type) {
        'success' => 'bg-emerald-50 border-emerald-200 text-emerald-900 dark:bg-emerald-900/20 dark:border-emerald-800 dark:text-emerald-100',
        'error' => 'bg-red-50 border-red-200 text-red-900 dark:bg-red-900/20 dark:border-red-800 dark:text-red-100',
        'warning' => 'bg-amber-50 border-amber-200 text-amber-900 dark:bg-amber-900/20 dark:border-amber-800 dark:text-amber-100',
        'info' => 'bg-blue-50 border-blue-200 text-blue-900 dark:bg-blue-900/20 dark:border-blue-800 dark:text-blue-100',
        default => 'bg-gray-50 border-gray-200 text-gray-900 dark:bg-gray-900/20 dark:border-gray-800 dark:text-gray-100',
    };
    
    $iconClasses = match($type) {
        'success' => 'text-emerald-600 dark:text-emerald-400',
        'error' => 'text-red-600 dark:text-red-400',
        'warning' => 'text-amber-600 dark:text-amber-400',
        'info' => 'text-blue-600 dark:text-blue-400',
        default => 'text-gray-600 dark:text-gray-400',
    };
    
    $borderClasses = match($type) {
        'success' => 'border-emerald-500',
        'error' => 'border-red-500',
        'warning' => 'border-amber-500',
        'info' => 'border-blue-500',
        default => 'border-gray-500',
    };
    
    $progressClasses = match($type) {
        'success' => 'bg-emerald-500',
        'error' => 'bg-red-500',
        'warning' => 'bg-amber-500',
        'info' => 'bg-blue-500',
        default => 'bg-gray-500',
    };
    
    $notificationId = $id ?? 'notification-' . uniqid();
@endphp

<div 
    x-data="notification({ 
        type: '{{ $type }}', 
        duration: {{ $duration }}, 
        dismissible: {{ $dismissible ? 'true' : 'false' }},
        progress: {{ $progress ? 'true' : 'false' }}
    })"
    x-show="show"
    x-transition:enter="transform ease-out duration-300 transition"
    x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
    x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
    x-transition:leave="transition ease-in duration-100"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="{{ $baseClasses }} {{ $typeClasses }} border-l-4 {{ $borderClasses }}"
    id="{{ $notificationId }}">
    
    <div class="p-4">
        <div class="flex items-start">
            @if($icon)
                <div class="flex-shrink-0">
                    @if($type === 'success')
                        <svg class="h-5 w-5 {{ $iconClasses }}" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    @elseif($type === 'error')
                        <svg class="h-5 w-5 {{ $iconClasses }}" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    @elseif($type === 'warning')
                        <svg class="h-5 w-5 {{ $iconClasses }}" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    @elseif($type === 'info')
                        <svg class="h-5 w-5 {{ $iconClasses }}" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    @endif
                </div>
            @endif
            
            <div class="ml-3 w-0 flex-1">
                @if($title)
                    <p class="text-sm font-medium">{{ $title }}</p>
                @endif
                
                @if($message)
                    <p class="mt-1 text-sm {{ $title ? 'opacity-90' : '' }}">{{ $message }}</p>
                @endif
                
                {{ $slot }}
            </div>
            
            @if($dismissible)
                <div class="ml-4 flex-shrink-0 flex">
                    <button 
                        @click="dismiss()"
                        class="inline-flex text-gray-400 hover:text-gray-600 focus:outline-none focus:text-gray-600 transition ease-in-out duration-150">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            @endif
        </div>
    </div>
    
    @if($progress)
        <div class="absolute bottom-0 left-0 right-0 h-1 bg-black/10">
            <div 
                x-show="show && progress"
                :style="`width: ${progressWidth}%`"
                class="h-full {{ $progressClasses }} transition-all duration-100"
                x-transition:leave="transition-none"
                x-transition:leave-start="w-full"
                x-transition:leave-end="w-0">
            </div>
        </div>
    @endif
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('notification', (config) => ({
        show: true,
        progressWidth: 100,
        timer: null,
        progressTimer: null,
        
        init() {
            if (config.duration > 0) {
                this.startTimer();
                
                if (config.progress) {
                    this.startProgress();
                }
            }
        },
        
        startTimer() {
            this.timer = setTimeout(() => {
                this.dismiss();
            }, config.duration);
        },
        
        startProgress() {
            const steps = 100;
            const stepDuration = config.duration / steps;
            let currentStep = 0;
            
            this.progressTimer = setInterval(() => {
                currentStep++;
                this.progressWidth = ((steps - currentStep) / steps) * 100;
                
                if (currentStep >= steps) {
                    clearInterval(this.progressTimer);
                }
            }, stepDuration);
        },
        
        dismiss() {
            if (this.timer) clearTimeout(this.timer);
            if (this.progressTimer) clearInterval(this.progressTimer);
            
            this.show = false;
            
            // Remove from DOM after animation
            setTimeout(() => {
                const element = this.$el;
                if (element && element.parentNode) {
                    element.parentNode.removeChild(element);
                }
            }, 300);
        },
        
        pause() {
            if (this.timer) {
                clearTimeout(this.timer);
                this.timer = null;
            }
            if (this.progressTimer) {
                clearInterval(this.progressTimer);
                this.progressTimer = null;
            }
        },
        
        resume() {
            if (config.duration > 0 && !this.timer) {
                const remainingTime = (this.progressWidth / 100) * config.duration;
                this.timer = setTimeout(() => {
                    this.dismiss();
                }, remainingTime);
                
                if (config.progress) {
                    this.startProgress();
                }
            }
        }
    }));
});
</script>