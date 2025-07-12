/**
 * Frontend Integration for Real-time Notification System
 * This file demonstrates how to integrate all the notification components
 */

import { createApp } from 'vue'
import notificationService from './services/NotificationService.js'
import pushNotificationService from './services/PushNotificationService.js'

// Import Vue components
import NotificationCenter from './components/NotificationCenter.vue'
import ToastNotification from './components/ToastNotification.vue'
import SecurityDashboard from './components/SecurityDashboard.vue'
import DeviceManagement from './components/DeviceManagement.vue'
import NotificationPreferences from './components/NotificationPreferences.vue'

// Create Vue app for notification components
const notificationApp = createApp({
    components: {
        NotificationCenter,
        ToastNotification,
        SecurityDashboard,
        DeviceManagement,
        NotificationPreferences
    },
    
    async mounted() {
        // Initialize notification services
        await this.initializeNotificationServices()
        
        // Set up global notification handlers
        this.setupGlobalHandlers()
        
        console.log('Real-time notification system initialized')
    },
    
    methods: {
        async initializeNotificationServices() {
            try {
                // Initialize real-time notification service (SSE)
                await notificationService.init()
                
                // Initialize push notification service
                await pushNotificationService.init()
                
                // Request push notification permission if supported
                if (pushNotificationService.isSupported()) {
                    const permission = await pushNotificationService.requestPermission()
                    console.log('Push notification permission:', permission)
                    
                    if (permission === 'granted') {
                        await pushNotificationService.subscribe()
                    }
                }
            } catch (error) {
                console.error('Failed to initialize notification services:', error)
            }
        },
        
        setupGlobalHandlers() {
            // Listen for real-time notifications
            notificationService.on('notification', (notification) => {
                console.log('New real-time notification:', notification)
                
                // Handle different notification types
                this.handleNotificationByType(notification)
            })
            
            // Listen for connection status changes
            notificationService.on('connected', () => {
                window.toast?.success('Connected', 'Real-time notifications enabled')
            })
            
            notificationService.on('connectionLost', () => {
                window.toast?.warning('Connection Lost', 'Attempting to reconnect...')
            })
            
            // Listen for unread count changes
            notificationService.on('unreadCountChanged', (count) => {
                this.updateNotificationBadges(count)
            })
        },
        
        handleNotificationByType(notification) {
            const data = notification.data || {}
            const type = data.type || notification.type || ''
            
            // Security notifications
            if (type.includes('security') || type.includes('login') || type.includes('device')) {
                window.toast?.security(
                    data.title || 'Security Alert',
                    data.message || 'Security event detected',
                    { duration: 8000 }
                )
            }
            // 2FA notifications
            else if (type.includes('2fa') || type.includes('auth')) {
                window.toast?.warning(
                    data.title || '2FA Required',
                    data.message || 'Two-factor authentication required',
                    { duration: 6000 }
                )
            }
            // Device notifications
            else if (type.includes('device')) {
                window.toast?.info(
                    data.title || 'Device Update',
                    data.message || 'Device status changed',
                    { duration: 5000 }
                )
            }
            // General notifications
            else {
                window.toast?.info(
                    data.title || 'Notification',
                    data.message || 'You have a new notification',
                    { duration: 5000 }
                )
            }
        },
        
        updateNotificationBadges(count) {
            // Update notification bell badge
            const badges = document.querySelectorAll('[data-notification-badge]')
            badges.forEach(badge => {
                if (count > 0) {
                    badge.textContent = count > 99 ? '99+' : count
                    badge.style.display = 'inline-flex'
                } else {
                    badge.style.display = 'none'
                }
            })
            
            // Update page title
            if (count > 0) {
                document.title = `(${count}) Attendance System`
            } else {
                document.title = 'Attendance System'
            }
        }
    }
})

// Mount notification components to specific elements
function mountNotificationComponents() {
    // Mount notification center if element exists
    const notificationCenterEl = document.getElementById('notification-center')
    if (notificationCenterEl) {
        notificationApp.mount('#notification-center')
    }
    
    // Mount toast notifications (always mount to body)
    const toastEl = document.createElement('div')
    toastEl.id = 'toast-notifications'
    document.body.appendChild(toastEl)
    
    const toastApp = createApp(ToastNotification)
    toastApp.mount('#toast-notifications')
    
    // Mount security dashboard if element exists
    const securityDashboardEl = document.getElementById('security-dashboard')
    if (securityDashboardEl) {
        const securityApp = createApp(SecurityDashboard)
        securityApp.mount('#security-dashboard')
    }
    
    // Mount device management if element exists
    const deviceManagementEl = document.getElementById('device-management')
    if (deviceManagementEl) {
        const deviceApp = createApp(DeviceManagement)
        deviceApp.mount('#device-management')
    }
    
    // Mount notification preferences if element exists
    const notificationPrefsEl = document.getElementById('notification-preferences')
    if (notificationPrefsEl) {
        const prefsApp = createApp(NotificationPreferences)
        prefsApp.mount('#notification-preferences')
    }
}

// Utility functions for manual notification triggering
window.notificationSystem = {
    // Send test notification
    async sendTest() {
        return await notificationService.sendTestNotification()
    },
    
    // Send test push notification
    async sendTestPush() {
        return await pushNotificationService.sendTestNotification()
    },
    
    // Get connection status
    getStatus() {
        return {
            sse: notificationService.getConnectionStatus(),
            push: {
                supported: pushNotificationService.isSupported(),
                permission: pushNotificationService.getPermission(),
                subscribed: pushNotificationService.isSubscribed()
            }
        }
    },
    
    // Manual notification
    notify(title, message, type = 'info') {
        window.toast?.show({
            type,
            title,
            message,
            duration: 5000
        })
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    mountNotificationComponents()
})

// Handle page visibility changes for reconnection
document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
        // Reconnect services when page becomes visible
        if (!notificationService.getConnectionStatus().isConnected) {
            notificationService.connect()
        }
    }
})

// Export for use in other modules
export {
    notificationService,
    pushNotificationService,
    NotificationCenter,
    ToastNotification,
    SecurityDashboard,
    DeviceManagement,
    NotificationPreferences
}