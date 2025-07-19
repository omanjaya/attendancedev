@extends('layouts.app')

@section('title', 'Notification System Demo')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Real-time Notification System Demo
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Comprehensive demonstration of the notification system features including real-time updates, push notifications, and security monitoring.
            </p>
        </div>

        <!-- System Status Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">SSE Connection</dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white" id="sse-status">Connecting...</dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M15 8a3 3 0 10-2.977-2.63l-4.94 2.47a3 3 0 100 4.319l4.94 2.47a3 3 0 10.895-1.789l-4.94-2.47a3.027 3.027 0 000-.74l4.94-2.47C13.456 7.68 14.19 8 15 8z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Push Notifications</dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white" id="push-status">Checking...</dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Unread Notifications</dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white" id="unread-count">0</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Demo Controls -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Notification Testing -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Notification Testing</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Test different types of notifications</p>
                </div>
                <div class="p-6 space-y-4">
                    <button onclick="sendTestNotification('info')" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition duration-150">
                        Send Info Notification
                    </button>
                    <button onclick="sendTestNotification('success')" class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-md transition duration-150">
                        Send Success Notification
                    </button>
                    <button onclick="sendTestNotification('warning')" class="w-full bg-yellow-600 hover:bg-yellow-700 text-white font-medium py-2 px-4 rounded-md transition duration-150">
                        Send Warning Notification
                    </button>
                    <button onclick="sendTestNotification('error')" class="w-full bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-md transition duration-150">
                        Send Error Notification
                    </button>
                    <button onclick="sendTestNotification('security')" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 px-4 rounded-md transition duration-150">
                        Send Security Alert
                    </button>
                    <button onclick="sendRealTimeNotification()" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-md transition duration-150">
                        Send Real-time Notification
                    </button>
                </div>
            </div>

            <!-- System Information -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">System Information</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Current notification system status</p>
                </div>
                <div class="p-6">
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Browser Support</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white" id="browser-support">Checking...</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Notification Permission</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white" id="notification-permission">Unknown</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Service Worker</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white" id="service-worker-status">Checking...</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Connection Attempts</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white" id="connection-attempts">0</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Component Demonstrations -->
        <div class="mt-8 space-y-8">
            <!-- Security Dashboard -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Security Dashboard</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Real-time security monitoring</p>
                </div>
                <div class="p-6">
                    <div id="security-dashboard"></div>
                </div>
            </div>

            <!-- Device Management -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Device Management</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage trusted devices</p>
                </div>
                <div class="p-6">
                    <div id="device-management"></div>
                </div>
            </div>

            <!-- Notification Preferences -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Notification Preferences</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Configure notification settings</p>
                </div>
                <div class="p-6">
                    <div id="notification-preferences"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div id="toast-notifications"></div>
@endsection

@push('scripts')
<script type="module">
    import { createApp } from 'vue'
    import NotificationCenter from '../js/components/NotificationCenter.vue'
    import ToastNotification from '../js/components/ToastNotification.vue'
    import SecurityDashboard from '../js/components/Security/SecurityDashboard.vue'
    import DeviceManagement from '../js/components/DeviceManagement.vue'
    import NotificationPreferences from '../js/components/NotificationPreferences.vue'
    import notificationService from '../js/services/NotificationService.js'
    import pushNotificationService from '../js/services/PushNotificationService.js'

    // Initialize notification services
    async function initializeServices() {
        try {
            console.log('Initializing notification services...')
            
            // Initialize SSE service
            await notificationService.init()
            updateSSEStatus('Connected', 'text-green-600 dark:text-green-400')
            
            // Initialize push notification service
            if (pushNotificationService.isSupported()) {
                await pushNotificationService.init()
                const permission = await pushNotificationService.requestPermission()
                updatePushStatus(permission)
                
                if (permission === 'granted') {
                    await pushNotificationService.subscribe()
                }
            } else {
                updatePushStatus('notsupported')
            }
            
            updateBrowserSupport()
            updateServiceWorkerStatus()
            
        } catch (error) {
            console.error('Failed to initialize services:', error)
            updateSSEStatus('Failed', 'text-red-600 dark:text-red-400')
        }
    }

    // Status update functions
    function updateSSEStatus(status, className) {
        const element = document.getElementById('sse-status')
        if (element) {
            element.textContent = status
            element.className = `text-lg font-medium ${className}`
        }
    }

    function updatePushStatus(permission) {
        const element = document.getElementById('push-status')
        const permissionElement = document.getElementById('notification-permission')
        
        if (element) {
            switch (permission) {
                case 'granted':
                    element.textContent = 'Enabled'
                    element.className = 'text-lg font-medium text-green-600 dark:text-green-400'
                    break
                case 'denied':
                    element.textContent = 'Blocked'
                    element.className = 'text-lg font-medium text-red-600 dark:text-red-400'
                    break
                case 'notsupported':
                    element.textContent = 'Not Supported'
                    element.className = 'text-lg font-medium text-gray-500 dark:text-gray-400'
                    break
                default:
                    element.textContent = 'Default'
                    element.className = 'text-lg font-medium text-yellow-600 dark:text-yellow-400'
            }
        }
        
        if (permissionElement) {
            permissionElement.textContent = permission === 'notsupported' ? 'Not Supported' : permission
        }
    }

    function updateBrowserSupport() {
        const element = document.getElementById('browser-support')
        if (element) {
            const supported = 'Notification' in window && 'serviceWorker' in navigator
            element.textContent = supported ? 'Full Support' : 'Limited Support'
        }
    }

    function updateServiceWorkerStatus() {
        const element = document.getElementById('service-worker-status')
        if (element) {
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.ready.then(() => {
                    element.textContent = 'Active'
                }).catch(() => {
                    element.textContent = 'Failed'
                })
            } else {
                element.textContent = 'Not Supported'
            }
        }
    }

    // Event listeners
    notificationService.on('connected', () => {
        updateSSEStatus('Connected', 'text-green-600 dark:text-green-400')
    })

    notificationService.on('connectionLost', () => {
        updateSSEStatus('Reconnecting...', 'text-yellow-600 dark:text-yellow-400')
        const attempts = document.getElementById('connection-attempts')
        if (attempts) {
            const current = parseInt(attempts.textContent) || 0
            attempts.textContent = (current + 1).toString()
        }
    })

    notificationService.on('unreadCountChanged', (count) => {
        const element = document.getElementById('unread-count')
        if (element) {
            element.textContent = count.toString()
        }
    })

    // Mount Vue components
    const toastApp = createApp(ToastNotification)
    toastApp.mount('#toast-notifications')

    const securityApp = createApp(SecurityDashboard)
    securityApp.mount('#security-dashboard')

    const deviceApp = createApp(DeviceManagement)
    deviceApp.mount('#device-management')

    const preferencesApp = createApp(NotificationPreferences)
    preferencesApp.mount('#notification-preferences')

    // Global functions for demo buttons
    window.sendTestNotification = function(type) {
        const titles = {
            info: 'Information',
            success: 'Success',
            warning: 'Warning',
            error: 'Error',
            security: 'Security Alert'
        }
        
        const messages = {
            info: 'This is an informational notification for testing purposes.',
            success: 'Operation completed successfully! Everything is working as expected.',
            warning: 'This is a warning notification. Please review the details.',
            error: 'An error occurred while processing your request. Please try again.',
            security: 'Security event detected. Please review your account activity.'
        }
        
        window.toast?.show({
            type,
            title: titles[type],
            message: messages[type],
            duration: type === 'error' || type === 'security' ? 8000 : 5000
        })
    }

    window.sendRealTimeNotification = async function() {
        try {
            const success = await notificationService.sendTestNotification()
            if (success) {
                window.toast?.success('Test Sent', 'Real-time notification has been sent')
            } else {
                window.toast?.error('Failed', 'Could not send test notification')
            }
        } catch (error) {
            window.toast?.error('Error', 'Failed to send test notification')
        }
    }

    // Initialize on load
    document.addEventListener('DOMContentLoaded', initializeServices)
</script>
@endpush