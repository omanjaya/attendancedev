@props([
    'position' => 'top-right'
])

@php
$positionClasses = [
    'top-left' => 'top-4 left-4',
    'top-right' => 'top-4 right-4',
    'top-center' => 'top-4 left-1/2 transform -translate-x-1/2',
    'bottom-left' => 'bottom-4 left-4',
    'bottom-right' => 'bottom-4 right-4',
    'bottom-center' => 'bottom-4 left-1/2 transform -translate-x-1/2',
];

$positionClass = $positionClasses[$position] ?? $positionClasses['top-right'];
@endphp

<!-- Toast Container -->
<div 
    id="toast-container" 
    class="fixed {{ $positionClass }} z-50 space-y-3 pointer-events-none"
    aria-live="polite"
    aria-label="Notifications"
    {{ $attributes }}
>
    <!-- Toasts will be dynamically inserted here -->
</div>

<script>
// Toast notification system
window.showNotification = function(message, type = 'info', duration = 5000) {
    const container = document.getElementById('toast-container');
    if (!container) return;
    
    // Create toast element
    const toast = document.createElement('div');
    toast.className = getToastClasses(type);
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    
    // Toast content
    toast.innerHTML = `
        <div class="flex items-start">
            <div class="flex-shrink-0">
                ${getToastIcon(type)}
            </div>
            <div class="ml-3 w-0 flex-1">
                <p class="text-sm font-medium ${getTextColor(type)}">
                    ${escapeHtml(message)}
                </p>
            </div>
            <div class="ml-4 flex-shrink-0 flex">
                <button 
                    class="rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    onclick="closeToast(this)"
                    aria-label="Close notification"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    `;
    
    // Add toast to container
    container.appendChild(toast);
    
    // Enable pointer events
    toast.style.pointerEvents = 'auto';
    
    // Show toast with animation
    setTimeout(() => {
        toast.classList.add('transform', 'translate-x-0', 'opacity-100');
        toast.classList.remove('translate-x-full', 'opacity-0');
    }, 100);
    
    // Auto remove after duration
    if (duration > 0) {
        setTimeout(() => {
            closeToast(toast.querySelector('button'));
        }, duration);
    }
};

function getToastClasses(type) {
    const baseClasses = 'pointer-events-auto w-full max-w-sm bg-white rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 transform transition-all duration-300 ease-in-out translate-x-full opacity-0 p-4';
    
    const typeClasses = {
        success: 'border-l-4 border-green-400',
        error: 'border-l-4 border-red-400',
        warning: 'border-l-4 border-yellow-400',
        info: 'border-l-4 border-blue-400'
    };
    
    return `${baseClasses} ${typeClasses[type] || typeClasses.info}`;
}

function getToastIcon(type) {
    const icons = {
        success: `<svg class="h-6 w-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>`,
        error: `<svg class="h-6 w-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
               </svg>`,
        warning: `<svg class="h-6 w-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                 </svg>`,
        info: `<svg class="h-6 w-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>`
    };
    
    return icons[type] || icons.info;
}

function getTextColor(type) {
    const colors = {
        success: 'text-gray-900',
        error: 'text-gray-900',
        warning: 'text-gray-900',
        info: 'text-gray-900'
    };
    
    return colors[type] || colors.info;
}

function closeToast(button) {
    const toast = button.closest('[role="alert"]');
    if (toast) {
        toast.classList.add('translate-x-full', 'opacity-0');
        toast.classList.remove('translate-x-0', 'opacity-100');
        
        setTimeout(() => {
            toast.remove();
        }, 300);
    }
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

// Alternative notification system for compatibility
window.notify = {
    success: (message, duration) => showNotification(message, 'success', duration),
    error: (message, duration) => showNotification(message, 'error', duration),
    warning: (message, duration) => showNotification(message, 'warning', duration),
    info: (message, duration) => showNotification(message, 'info', duration)
};
</script>

<style>
/* Toast container positioning adjustments */
@media (max-width: 640px) {
    #toast-container {
        left: 1rem !important;
        right: 1rem !important;
        max-width: calc(100vw - 2rem);
    }
    
    #toast-container > div {
        max-width: 100%;
    }
}
</style>