<template>
  <div class="relative">
    <!-- Notification Bell Icon -->
    <button
      @click="toggleDropdown"
      class="relative p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 rounded-md"
      :class="{ 'text-indigo-600 dark:text-indigo-400': isOpen }"
    >
      <BellIcon class="h-6 w-6" />
      
      <!-- Notification Badge -->
      <span
        v-if="unreadCount > 0"
        class="absolute -top-1 -right-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-500 rounded-full min-w-[1.25rem] h-5"
      >
        {{ unreadCount > 99 ? '99+' : unreadCount }}
      </span>
      
      <!-- Connection Status Indicator -->
      <span
        v-if="!isConnected"
        class="absolute -bottom-1 -right-1 w-3 h-3 bg-yellow-400 border-2 border-white dark:border-gray-800 rounded-full"
        title="Reconnecting..."
      ></span>
    </button>

    <!-- Notification Dropdown -->
    <transition
      enter-active-class="transition ease-out duration-100"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <div
        v-if="isOpen"
        :class="[dropdownClasses, isMobile ? 'w-full' : 'w-80']"
        @click.stop
      >
        <!-- Header -->
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
          <div class="flex items-center justify-between">
            <h3 class="text-sm font-medium text-gray-900 dark:text-white">
              Notifications
            </h3>
            <div class="flex items-center space-x-2">
              <!-- Connection Status -->
              <div class="flex items-center">
                <div
                  class="w-2 h-2 rounded-full mr-1"
                  :class="isConnected ? 'bg-green-400' : 'bg-yellow-400'"
                ></div>
                <span class="text-xs text-gray-500 dark:text-gray-400">
                  {{ isConnected ? 'Live' : 'Reconnecting' }}
                </span>
              </div>
              
              <!-- Mark All Read -->
              <button
                v-if="unreadCount > 0"
                @click="markAllAsRead"
                class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-200"
              >
                Mark all read
              </button>
            </div>
          </div>
        </div>

        <!-- Notifications List -->
        <div class="max-h-96 overflow-y-auto">
          <!-- Loading State -->
          <div v-if="loading" class="px-4 py-8 text-center">
            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-indigo-600 mx-auto"></div>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Loading notifications...</p>
          </div>

          <!-- Empty State -->
          <div v-else-if="notifications.length === 0" class="px-4 py-8 text-center">
            <BellIcon class="h-8 w-8 text-gray-300 dark:text-gray-600 mx-auto mb-2" />
            <p class="text-sm text-gray-500 dark:text-gray-400">No notifications yet</p>
          </div>

          <!-- Notification Items -->
          <div v-else class="divide-y divide-gray-200 dark:divide-gray-700">
            <div
              v-for="notification in notifications"
              :key="notification.id"
              class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition-colors"
              :class="{ 'bg-blue-50 dark:bg-blue-900/20': !notification.read_at }"
              @click="markAsRead(notification)"
            >
              <div class="flex items-start space-x-3">
                <!-- Notification Icon -->
                <div class="flex-shrink-0">
                  <component
                    :is="getNotificationIcon(notification)"
                    class="h-5 w-5"
                    :class="getNotificationIconColor(notification)"
                  />
                </div>

                <!-- Notification Content -->
                <div class="flex-1 min-w-0">
                  <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                      {{ notification.data?.title || 'Notification' }}
                    </p>
                    <div class="flex items-center space-x-1">
                      <!-- Priority Indicator -->
                      <span
                        v-if="notification.data?.priority === 'high'"
                        class="w-2 h-2 bg-red-400 rounded-full"
                        title="High Priority"
                      ></span>
                      <!-- Unread Indicator -->
                      <span
                        v-if="!notification.read_at"
                        class="w-2 h-2 bg-blue-500 rounded-full"
                      ></span>
                    </div>
                  </div>
                  
                  <p class="text-sm text-gray-600 dark:text-gray-300 line-clamp-2">
                    {{ notification.data?.message || 'No message content' }}
                  </p>
                  
                  <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {{ formatDate(notification.created_at) }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700 rounded-b-md">
          <div class="flex items-center justify-between">
            <button
              @click="sendTestNotification"
              :disabled="testingSent"
              class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 disabled:opacity-50"
            >
              {{ testingSent ? 'Test sent!' : 'Send test' }}
            </button>
            
            <router-link
              to="/notification-preferences"
              class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-200"
              @click="closeDropdown"
            >
              Preferences
            </router-link>
          </div>
        </div>
      </div>
    </transition>

    <!-- Click Outside Handler -->
    <div
      v-if="isOpen"
      class="fixed inset-0 z-40"
      @click="closeDropdown"
    ></div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { formatDistanceToNow } from 'date-fns'
import notificationService from '../services/NotificationService'
import { useResponsive } from '../composables/useResponsive'

// Icons
const BellIcon = {
  template: `
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4 7a8 8 0 0116 0v4.5a2 2 0 01-.586 1.414L17 15H7l-2.414-2.086A2 2 0 014 11.5V7z"/>
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 21h6"/>
    </svg>
  `
}

const ShieldCheckIcon = {
  template: `
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.031 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
    </svg>
  `
}

const DevicePhoneMobileIcon = {
  template: `
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a1 1 0 001-1V4a1 1 0 00-1-1H8a1 1 0 00-1 1v16a1 1 0 001 1z"/>
    </svg>
  `
}

const ExclamationTriangleIcon = {
  template: `
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
    </svg>
  `
}

// Responsive utilities
const { 
  isMobile, 
  isTablet, 
  notificationCenterConfig, 
  responsiveClasses 
} = useResponsive()

// Reactive data
const isOpen = ref(false)
const notifications = ref([])
const unreadCount = ref(0)
const loading = ref(false)
const isConnected = ref(false)
const testingSent = ref(false)

// Responsive dropdown classes
const dropdownClasses = computed(() => {
  const config = notificationCenterConfig.value
  
  if (config.fullscreen) {
    return 'fixed inset-0 z-50 bg-white dark:bg-gray-800'
  } else {
    return `absolute right-0 z-50 mt-2 bg-white dark:bg-gray-800 rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none`
  }
})

// Methods
const toggleDropdown = () => {
  isOpen.value = !isOpen.value
  if (isOpen.value) {
    loadNotifications()
  }
}

const closeDropdown = () => {
  isOpen.value = false
}

const loadNotifications = async () => {
  try {
    loading.value = true
    const status = await notificationService.getStatus()
    if (status) {
      notifications.value = status.recent_notifications || []
      unreadCount.value = status.unread_count || 0
    }
  } catch (error) {
    console.error('Failed to load notifications:', error)
  } finally {
    loading.value = false
  }
}

const markAsRead = async (notification) => {
  if (notification.read_at) return

  try {
    const response = await fetch('/api/notification-preferences/mark-read', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
      },
      body: JSON.stringify({ notification_id: notification.id })
    })

    if (response.ok) {
      // Update local state
      notification.read_at = new Date()
      unreadCount.value = Math.max(0, unreadCount.value - 1)
    }
  } catch (error) {
    console.error('Failed to mark notification as read:', error)
  }
}

const markAllAsRead = async () => {
  try {
    const response = await fetch('/api/notification-preferences/mark-read', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
      },
      body: JSON.stringify({ mark_all: true })
    })

    if (response.ok) {
      // Update local state
      notifications.value.forEach(notification => {
        if (!notification.read_at) {
          notification.read_at = new Date()
        }
      })
      unreadCount.value = 0
    }
  } catch (error) {
    console.error('Failed to mark all notifications as read:', error)
  }
}

const sendTestNotification = async () => {
  const success = await notificationService.sendTestNotification()
  if (success) {
    testingSent.value = true
    setTimeout(() => {
      testingSent.value = false
    }, 3000)
  }
}

const getNotificationIcon = (notification) => {
  const type = notification.data?.type || notification.type || ''
  
  if (type.includes('security') || type.includes('login') || type.includes('2fa')) {
    return ShieldCheckIcon
  } else if (type.includes('device')) {
    return DevicePhoneMobileIcon
  } else if (type.includes('alert') || type.includes('warning')) {
    return ExclamationTriangleIcon
  }
  
  return BellIcon
}

const getNotificationIconColor = (notification) => {
  const priority = notification.data?.priority || 'low'
  const type = notification.data?.type || notification.type || ''
  
  if (priority === 'high' || type.includes('alert')) {
    return 'text-red-500'
  } else if (type.includes('security')) {
    return 'text-yellow-500'
  } else if (type.includes('device')) {
    return 'text-blue-500'
  }
  
  return 'text-gray-400'
}

const formatDate = (dateString) => {
  return formatDistanceToNow(new Date(dateString), { addSuffix: true })
}

// Event handlers for notification service
const handleNewNotification = (notification) => {
  // Add to beginning of notifications list
  notifications.value.unshift(notification)
  
  // Keep only latest 10 notifications in UI
  if (notifications.value.length > 10) {
    notifications.value = notifications.value.slice(0, 10)
  }
}

const handleUnreadCountChanged = (count) => {
  unreadCount.value = count
}

const handleConnectionStatus = (connected) => {
  isConnected.value = connected
}

// Lifecycle
onMounted(() => {
  // Initialize notification service
  notificationService.init()
  
  // Register event listeners
  notificationService.on('notification', handleNewNotification)
  notificationService.on('unreadCountChanged', handleUnreadCountChanged)
  notificationService.on('connected', () => handleConnectionStatus(true))
  notificationService.on('connectionLost', () => handleConnectionStatus(false))
  
  // Get initial connection status
  const status = notificationService.getConnectionStatus()
  isConnected.value = status.isConnected
  unreadCount.value = status.unreadCount
})

onUnmounted(() => {
  // Cleanup event listeners
  notificationService.off('notification', handleNewNotification)
  notificationService.off('unreadCountChanged', handleUnreadCountChanged)
  notificationService.off('connected', () => handleConnectionStatus(true))
  notificationService.off('connectionLost', () => handleConnectionStatus(false))
})
</script>

<style scoped>
.line-clamp-2 {
  overflow: hidden;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
}
</style>