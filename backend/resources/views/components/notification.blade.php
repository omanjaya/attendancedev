@if(session('success') || session('error') || session('warning') || session('info'))
<div id="notification-container" class="fixed top-4 right-4 z-50 space-y-2">
    @if(session('success'))
    <div id="success-notification" class="notification-item bg-green-50 border border-green-200 rounded-lg p-4 shadow-lg max-w-md transform transition-all duration-300 ease-in-out">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="ml-3 flex-1">
                <div class="text-sm font-medium text-green-800">
                    {{ session('success') }}
                </div>
            </div>
            <div class="ml-4 flex-shrink-0">
                <button type="button" class="inline-flex text-green-400 hover:text-green-600 transition-colors duration-200" onclick="closeNotification('success-notification')">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
    @endif

    @if(session('error'))
    <div id="error-notification" class="notification-item bg-red-50 border border-red-200 rounded-lg p-4 shadow-lg max-w-md transform transition-all duration-300 ease-in-out">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="ml-3 flex-1">
                <div class="text-sm font-medium text-red-800">
                    {{ session('error') }}
                </div>
            </div>
            <div class="ml-4 flex-shrink-0">
                <button type="button" class="inline-flex text-red-400 hover:text-red-600 transition-colors duration-200" onclick="closeNotification('error-notification')">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
    @endif

    @if(session('warning'))
    <div id="warning-notification" class="notification-item bg-yellow-50 border border-yellow-200 rounded-lg p-4 shadow-lg max-w-md transform transition-all duration-300 ease-in-out">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.99-.833-2.76 0L3.054 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            <div class="ml-3 flex-1">
                <div class="text-sm font-medium text-yellow-800">
                    {{ session('warning') }}
                </div>
            </div>
            <div class="ml-4 flex-shrink-0">
                <button type="button" class="inline-flex text-yellow-400 hover:text-yellow-600 transition-colors duration-200" onclick="closeNotification('warning-notification')">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
    @endif

    @if(session('info'))
    <div id="info-notification" class="notification-item bg-blue-50 border border-blue-200 rounded-lg p-4 shadow-lg max-w-md transform transition-all duration-300 ease-in-out">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="ml-3 flex-1">
                <div class="text-sm font-medium text-blue-800">
                    {{ session('info') }}
                </div>
            </div>
            <div class="ml-4 flex-shrink-0">
                <button type="button" class="inline-flex text-blue-400 hover:text-blue-600 transition-colors duration-200" onclick="closeNotification('info-notification')">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
    @endif
</div>

<script>
function closeNotification(notificationId) {
    const notification = document.getElementById(notificationId);
    if (notification) {
        notification.classList.add('opacity-0', 'translate-x-full');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }
}

// Auto-close notifications after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const notifications = document.querySelectorAll('.notification-item');
    notifications.forEach(notification => {
        setTimeout(() => {
            notification.classList.add('opacity-0', 'translate-x-full');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 5000);
    });
});
</script>
@endif