/**
 * Frontend Integration for Real-time Notification System
 * This file demonstrates how to integrate all the notification components
 */

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import notificationService from './services/NotificationService.js'
import pushNotificationService from './services/PushNotificationService.js'
import { createErrorTrackingService } from './services/errorTracking.ts'
import { createErrorTrackingConfig } from './config/errorTracking.ts'
import { createRequestCacheService } from './services/requestCache.ts'

// Import Vue components with lazy loading
import { LazyComponents, preloadByContext } from './utils/lazyComponents.js'
import ToastNotification from './components/ToastNotification.vue' // Keep toast immediate for error handling

// Lazy load other components
const NotificationCenter = LazyComponents.NotificationCenter
const SecurityDashboard = LazyComponents.SecurityDashboard
const DeviceManagement = LazyComponents.DeviceManagement
const NotificationPreferences = LazyComponents.NotificationPreferences

// Create Pinia store
const pinia = createPinia()

// Initialize error tracking service
let errorTrackingService
try {
  const errorTrackingConfig = createErrorTrackingConfig()
  errorTrackingService = createErrorTrackingService(errorTrackingConfig)

  // Initialize error tracking service
  errorTrackingService
    .initialize()
    .then(() => {
      console.log('[ErrorTracking] Service initialized successfully')
    })
    .catch((error) => {
      console.error('[ErrorTracking] Failed to initialize:', error)
    })
} catch (error) {
  console.error('[ErrorTracking] Failed to create service:', error)
}

// Initialize request cache service
let requestCacheService
try {
  const cacheConfig = {
    defaultTTL: 5 * 60 * 1000, // 5 minutes
    maxSize: 200,
    enableBackgroundRefresh: true,
    backgroundRefreshThreshold: 0.7,
    enablePersistence: true,
    persistenceKey: 'attendance_app_cache',
    debugMode: import.meta.env.MODE === 'development',
  }

  requestCacheService = createRequestCacheService(cacheConfig)
  console.log('[RequestCache] Service initialized successfully')
} catch (error) {
  console.error('[RequestCache] Failed to create service:', error)
}

// Global error handler function
const globalErrorHandler = (err, instance, info) => {
  const errorContext = {
    component: instance?.$options.name || 'Unknown',
    action: 'vue_error',
    metadata: {
      info,
      vueInstance: {
        tag: instance?.$vnode?.tag,
        componentOptions: instance?.$options?.name,
      },
      errorBoundary: instance?.$parent?.$options?.name,
    },
  }

  console.error('Vue Application Error:', {
    error: err,
    ...errorContext,
    timestamp: new Date().toISOString(),
    url: window.location.href,
    userAgent: navigator.userAgent,
  })

  // Send to error tracking service
  if (errorTrackingService) {
    errorTrackingService.captureError(err, errorContext)
  } else if (window.errorTracker) {
    // Fallback to global error tracker
    window.errorTracker.captureException(err, {
      context: info,
      component: instance?.$options.name,
      extra: {
        timestamp: new Date().toISOString(),
        url: window.location.href,
      },
    })
  }

  // Show user-friendly error message
  if (window.toast) {
    const errorMessage = err?.message || 'An unexpected error occurred'
    window.toast.error('Application Error', errorMessage, { duration: 8000 })
  }
}

// Create Vue app for notification components
const notificationApp = createApp({
  components: {
    NotificationCenter,
    ToastNotification,
    SecurityDashboard,
    DeviceManagement,
    NotificationPreferences,
  },

  async mounted() {
    try {
      // Initialize notification services
      await this.initializeNotificationServices()

      // Set up global notification handlers
      this.setupGlobalHandlers()

      // Add breadcrumb for successful initialization
      if (errorTrackingService) {
        errorTrackingService.addBreadcrumb('Real-time notification system initialized', 'system', {
          timestamp: new Date().toISOString(),
        })
      }

      console.log('Real-time notification system initialized')
    } catch (error) {
      console.error('Failed to initialize notification app:', error)
      if (errorTrackingService) {
        errorTrackingService.captureError(error, {
          component: 'NotificationApp',
          action: 'mount_failed',
        })
      }
    }
  },

  methods: {
    async initializeNotificationServices() {
      try {
        // Add breadcrumb for initialization start
        if (errorTrackingService) {
          errorTrackingService.addBreadcrumb('Initializing notification services', 'initialization')
        }

        // Initialize real-time notification service (SSE)
        await notificationService.init()

        // Initialize push notification service
        await pushNotificationService.init()

        // Request push notification permission if supported
        if (pushNotificationService.isSupported()) {
          const permission = await pushNotificationService.requestPermission()
          console.log('Push notification permission:', permission)

          if (errorTrackingService) {
            errorTrackingService.addBreadcrumb(
              `Push notification permission: ${permission}`,
              'permissions'
            )
          }

          if (permission === 'granted') {
            await pushNotificationService.subscribe()
          }
        }
      } catch (error) {
        console.error('Failed to initialize notification services:', error)
        if (errorTrackingService) {
          errorTrackingService.captureError(error, {
            component: 'NotificationApp',
            action: 'initialize_services_failed',
            metadata: {
              pushSupported: pushNotificationService.isSupported(),
              serviceWorkerSupported: 'serviceWorker' in navigator,
            },
          })
        }
        throw error // Re-throw to be handled by the calling function
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
        window.toast?.info(data.title || 'Device Update', data.message || 'Device status changed', {
          duration: 5000,
        })
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
      badges.forEach((badge) => {
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
    },
  },
})

// Mount notification components to specific elements
function mountNotificationComponents() {
  // Mount notification center if element exists
  const notificationCenterEl = document.getElementById('notification-center')
  if (notificationCenterEl) {
    notificationApp.config.errorHandler = globalErrorHandler
    notificationApp.use(pinia).mount('#notification-center')
  }

  // Mount toast notifications (always mount to body)
  const toastEl = document.createElement('div')
  toastEl.id = 'toast-notifications'
  document.body.appendChild(toastEl)

  const toastApp = createApp(ToastNotification)
  toastApp.config.errorHandler = globalErrorHandler
  toastApp.use(pinia)
  toastApp.mount('#toast-notifications')

  // Mount security dashboard if element exists
  const securityDashboardEl = document.getElementById('security-dashboard')
  if (securityDashboardEl) {
    const securityApp = createApp(SecurityDashboard)
    securityApp.config.errorHandler = globalErrorHandler
    securityApp.use(pinia)
    securityApp.mount('#security-dashboard')
  }

  // Mount device management if element exists
  const deviceManagementEl = document.getElementById('device-management')
  if (deviceManagementEl) {
    const deviceApp = createApp(DeviceManagement)
    deviceApp.config.errorHandler = globalErrorHandler
    deviceApp.use(pinia)
    deviceApp.mount('#device-management')
  }

  // Mount notification preferences if element exists
  const notificationPrefsEl = document.getElementById('notification-preferences')
  if (notificationPrefsEl) {
    const prefsApp = createApp(NotificationPreferences)
    prefsApp.config.errorHandler = globalErrorHandler
    prefsApp.use(pinia)
    prefsApp.mount('#notification-preferences')
  }
}

// Utility functions for manual notification triggering
window.notificationSystem = {
  // Send test notification
  async sendTest() {
    try {
      const result = await notificationService.sendTestNotification()
      if (errorTrackingService) {
        errorTrackingService.addBreadcrumb('Test notification sent', 'user_action')
      }
      return result
    } catch (error) {
      if (errorTrackingService) {
        errorTrackingService.captureError(error, {
          component: 'NotificationSystem',
          action: 'send_test_notification',
        })
      }
      throw error
    }
  },

  // Send test push notification
  async sendTestPush() {
    try {
      const result = await pushNotificationService.sendTestNotification()
      if (errorTrackingService) {
        errorTrackingService.addBreadcrumb('Test push notification sent', 'user_action')
      }
      return result
    } catch (error) {
      if (errorTrackingService) {
        errorTrackingService.captureError(error, {
          component: 'NotificationSystem',
          action: 'send_test_push_notification',
        })
      }
      throw error
    }
  },

  // Get connection status
  getStatus() {
    return {
      sse: notificationService.getConnectionStatus(),
      push: {
        supported: pushNotificationService.isSupported(),
        permission: pushNotificationService.getPermission(),
        subscribed: pushNotificationService.isSubscribed(),
      },
      errorTracking: {
        initialized: !!errorTrackingService,
        config: errorTrackingService
          ? {
              enabled: true,
              environment: window.__ERROR_TRACKING_CONFIG__?.environment || 'unknown',
            }
          : null,
      },
      requestCache: {
        initialized: !!requestCacheService,
        stats: requestCacheService ? requestCacheService.getStats() : null,
      },
    }
  },

  // Manual notification
  notify(title, message, type = 'info') {
    window.toast?.show({
      type,
      title,
      message,
      duration: 5000,
    })
  },
}

// Expose error tracking service globally
window.errorTracking = {
  captureError: (error, context) => {
    if (errorTrackingService) {
      errorTrackingService.captureError(error, context)
    } else {
      console.warn('[ErrorTracking] Service not initialized, error not captured:', error)
    }
  },
  captureMessage: (message, level, context) => {
    if (errorTrackingService) {
      errorTrackingService.captureMessage(message, level, context)
    } else {
      console.warn('[ErrorTracking] Service not initialized, message not captured:', message)
    }
  },
  addBreadcrumb: (message, category, data) => {
    if (errorTrackingService) {
      errorTrackingService.addBreadcrumb(message, category, data)
    }
  },
  setUser: (user) => {
    if (errorTrackingService) {
      errorTrackingService.setUser(user)
    }
  },
  getService: () => errorTrackingService,
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
  NotificationPreferences,
  errorTrackingService,
  requestCacheService,
}
