import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type {
  Notification,
  NotificationPreferences,
  ToastNotification,
} from '@/types/notifications'

export const useNotificationStore = defineStore('notifications', () => {
  // State
  const notifications = ref<Notification[]>([])
  const preferences = ref<NotificationPreferences | null>(null)
  const unreadCount = ref<number>(0)
  const isLoading = ref<boolean>(false)
  const error = ref<string | null>(null)
  const connectionStatus = ref<'connected' | 'disconnected' | 'connecting'>('disconnected')

  // Toast notifications
  const toasts = ref<ToastNotification[]>([])
  const maxToasts = ref<number>(5)

  // Getters
  const unreadNotifications = computed(() => notifications.value.filter((n) => !n.read_at))

  const importantNotifications = computed(() =>
    notifications.value.filter((n) => n.priority === 'high' && !n.read_at)
  )

  const securityNotifications = computed(() =>
    notifications.value.filter((n) => n.type.includes('security'))
  )

  const latestNotifications = computed(() =>
    notifications.value
      .sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime())
      .slice(0, 10)
  )

  // Actions
  const fetchNotifications = async (
    params: {
      page?: number
      limit?: number
      unread_only?: boolean
      type?: string
    } = {}
  ): Promise<void> => {
    isLoading.value = true
    error.value = null

    try {
      const searchParams = new URLSearchParams()
      if (params.page) {searchParams.append('page', params.page.toString())}
      if (params.limit) {searchParams.append('limit', params.limit.toString())}
      if (params.unread_only) {searchParams.append('unread_only', 'true')}
      if (params.type) {searchParams.append('type', params.type)}

      const response = await fetch(`/api/notifications?${searchParams}`, {
        headers: {
          Authorization: `Bearer ${getAuthToken()}`,
          'X-CSRF-TOKEN': getCSRFToken(),
        },
      })

      if (!response.ok) {
        throw new Error('Failed to fetch notifications')
      }

      const data = await response.json()
      notifications.value = data.notifications || data.data || []
      unreadCount.value = data.unread_count || 0
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Failed to fetch notifications'
      console.error('Fetch notifications error:', err)
    } finally {
      isLoading.value = false
    }
  }

  const markAsRead = async (notificationId: string): Promise<void> => {
    try {
      const response = await fetch(`/api/notifications/${notificationId}/read`, {
        method: 'POST',
        headers: {
          Authorization: `Bearer ${getAuthToken()}`,
          'X-CSRF-TOKEN': getCSRFToken(),
        },
      })

      if (!response.ok) {
        throw new Error('Failed to mark notification as read')
      }

      // Update local state
      const notification = notifications.value.find((n) => n.id === notificationId)
      if (notification) {
        notification.read_at = new Date().toISOString()
        unreadCount.value = Math.max(0, unreadCount.value - 1)
      }
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Failed to mark as read'
      console.error('Mark as read error:', err)
    }
  }

  const markAllAsRead = async (): Promise<void> => {
    try {
      const response = await fetch('/api/notifications/mark-all-read', {
        method: 'POST',
        headers: {
          Authorization: `Bearer ${getAuthToken()}`,
          'X-CSRF-TOKEN': getCSRFToken(),
        },
      })

      if (!response.ok) {
        throw new Error('Failed to mark all notifications as read')
      }

      // Update local state
      notifications.value.forEach((notification) => {
        if (!notification.read_at) {
          notification.read_at = new Date().toISOString()
        }
      })
      unreadCount.value = 0
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Failed to mark all as read'
      console.error('Mark all as read error:', err)
    }
  }

  const deleteNotification = async (notificationId: string): Promise<void> => {
    try {
      const response = await fetch(`/api/notifications/${notificationId}`, {
        method: 'DELETE',
        headers: {
          Authorization: `Bearer ${getAuthToken()}`,
          'X-CSRF-TOKEN': getCSRFToken(),
        },
      })

      if (!response.ok) {
        throw new Error('Failed to delete notification')
      }

      // Remove from local state
      const index = notifications.value.findIndex((n) => n.id === notificationId)
      if (index > -1) {
        const notification = notifications.value[index]
        if (!notification.read_at) {
          unreadCount.value = Math.max(0, unreadCount.value - 1)
        }
        notifications.value.splice(index, 1)
      }
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Failed to delete notification'
      console.error('Delete notification error:', err)
    }
  }

  const fetchPreferences = async (): Promise<void> => {
    try {
      const response = await fetch('/api/notification-preferences', {
        headers: {
          Authorization: `Bearer ${getAuthToken()}`,
          'X-CSRF-TOKEN': getCSRFToken(),
        },
      })

      if (!response.ok) {
        throw new Error('Failed to fetch notification preferences')
      }

      const data = await response.json()
      preferences.value = data.preferences || data
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Failed to fetch preferences'
      console.error('Fetch preferences error:', err)
    }
  }

  const updatePreferences = async (
    newPreferences: Partial<NotificationPreferences>
  ): Promise<void> => {
    try {
      const response = await fetch('/api/notification-preferences', {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${getAuthToken()}`,
          'X-CSRF-TOKEN': getCSRFToken(),
        },
        body: JSON.stringify(newPreferences),
      })

      if (!response.ok) {
        throw new Error('Failed to update notification preferences')
      }

      const data = await response.json()
      preferences.value = { ...preferences.value, ...data.preferences }
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Failed to update preferences'
      console.error('Update preferences error:', err)
      throw err
    }
  }

  // Toast Management
  const showToast = (toast: Omit<ToastNotification, 'id' | 'timestamp'>): void => {
    const newToast: ToastNotification = {
      ...toast,
      id: generateId(),
      timestamp: new Date().toISOString(),
    }

    toasts.value.unshift(newToast)

    // Limit maximum toasts
    if (toasts.value.length > maxToasts.value) {
      toasts.value = toasts.value.slice(0, maxToasts.value)
    }

    // Auto remove after duration
    if (toast.duration && toast.duration > 0) {
      setTimeout(() => {
        removeToast(newToast.id)
      }, toast.duration)
    }
  }

  const removeToast = (toastId: string): void => {
    const index = toasts.value.findIndex((t) => t.id === toastId)
    if (index > -1) {
      toasts.value.splice(index, 1)
    }
  }

  const clearAllToasts = (): void => {
    toasts.value = []
  }

  // Real-time connection management
  const connect = (): void => {
    connectionStatus.value = 'connecting'
    // Implementation would depend on your real-time solution (SSE, WebSocket, etc.)
    // This is a placeholder
    setTimeout(() => {
      connectionStatus.value = 'connected'
    }, 1000)
  }

  const disconnect = (): void => {
    connectionStatus.value = 'disconnected'
  }

  // Add new notification (typically called from real-time events)
  const addNotification = (notification: Notification): void => {
    // Add to beginning of array
    notifications.value.unshift(notification)

    // Update unread count if notification is unread
    if (!notification.read_at) {
      unreadCount.value++
    }

    // Show toast if preferences allow
    if (shouldShowToast(notification)) {
      showToast({
        type: getToastType(notification.type),
        title: notification.title,
        message: notification.message,
        duration: 5000,
      })
    }

    // Play sound if preferences allow
    if (shouldPlaySound(notification)) {
      playNotificationSound()
    }
  }

  // Helper functions
  const shouldShowToast = (notification: Notification): boolean => {
    if (!preferences.value) {return true}

    const typePrefs = preferences.value.types?.[notification.type]
    return typePrefs?.toast !== false
  }

  const shouldPlaySound = (notification: Notification): boolean => {
    if (!preferences.value) {return false}

    const typePrefs = preferences.value.types?.[notification.type]
    return typePrefs?.sound === true && preferences.value.sound_enabled
  }

  const getToastType = (notificationType: string): ToastNotification['type'] => {
    if (notificationType.includes('error') || notificationType.includes('security')) {return 'error'}
    if (notificationType.includes('warning')) {return 'warning'}
    if (notificationType.includes('success')) {return 'success'}
    return 'info'
  }

  const playNotificationSound = (): void => {
    try {
      const audio = new Audio('/sounds/notification.mp3')
      audio.volume = 0.5
      audio.play().catch(console.error)
    } catch (err) {
      console.error('Failed to play notification sound:', err)
    }
  }

  const generateId = (): string => {
    return Math.random().toString(36).substring(2) + Date.now().toString(36)
  }

  const getAuthToken = (): string => {
    return localStorage.getItem('auth_token') || ''
  }

  const getCSRFToken = (): string => {
    const token = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement
    return token?.content || ''
  }

  // Clear all state
  const reset = (): void => {
    notifications.value = []
    preferences.value = null
    unreadCount.value = 0
    error.value = null
    toasts.value = []
    connectionStatus.value = 'disconnected'
  }

  return {
    // State
    notifications,
    preferences,
    unreadCount,
    isLoading,
    error,
    connectionStatus,
    toasts,

    // Getters
    unreadNotifications,
    importantNotifications,
    securityNotifications,
    latestNotifications,

    // Actions
    fetchNotifications,
    markAsRead,
    markAllAsRead,
    deleteNotification,
    fetchPreferences,
    updatePreferences,
    showToast,
    removeToast,
    clearAllToasts,
    connect,
    disconnect,
    addNotification,
    reset,
  }
})
