<template>
  <teleport to="body">
    <!-- Toast Container -->
    <div class="fixed right-4 top-4 z-50 space-y-2" role="alert" aria-live="polite">
      <transition-group name="toast" tag="div" class="space-y-2">
        <div
          v-for="toast in toasts"
          :key="toast.id"
          class="flex w-full max-w-xs items-center rounded-lg bg-white p-4 text-gray-500 shadow dark:bg-gray-800 dark:text-gray-400"
          :class="getToastClasses(toast)"
        >
          <!-- Icon -->
          <div class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg">
            <component
              :is="getToastIcon(toast.type)"
              class="h-5 w-5"
              :class="getIconClasses(toast.type)"
            />
          </div>

          <!-- Content -->
          <div class="ml-3 flex-1 text-sm font-normal">
            <div class="font-semibold text-gray-900 dark:text-white">
              {{ toast.title }}
            </div>
            <div class="text-gray-600 dark:text-gray-300">
              {{ toast.message }}
            </div>
          </div>

          <!-- Close Button -->
          <button
            type="button"
            class="-mx-1.5 -my-1.5 ml-auto inline-flex h-8 w-8 rounded-lg bg-white p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-900 focus:ring-2 focus:ring-gray-300 dark:bg-gray-800 dark:text-gray-500 dark:hover:bg-gray-700 dark:hover:text-white"
            aria-label="Close"
            @click="removeToast(toast.id)"
          >
            <XMarkIcon class="h-3 w-3" />
          </button>

          <!-- Progress Bar -->
          <div
            v-if="toast.duration > 0"
            class="absolute bottom-0 left-0 h-1 rounded-b-lg bg-current opacity-30 transition-all duration-100 ease-linear"
            :style="{ width: `${toast.progress}%` }"
          />
        </div>
      </transition-group>
    </div>
  </teleport>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import notificationService from '../services/NotificationService'

// Icons
const CheckCircleIcon = {
  template: `
    <svg fill="currentColor" viewBox="0 0 20 20">
      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
    </svg>
  `,
}

const ExclamationTriangleIcon = {
  template: `
    <svg fill="currentColor" viewBox="0 0 20 20">
      <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
    </svg>
  `,
}

const XCircleIcon = {
  template: `
    <svg fill="currentColor" viewBox="0 0 20 20">
      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
    </svg>
  `,
}

const InformationCircleIcon = {
  template: `
    <svg fill="currentColor" viewBox="0 0 20 20">
      <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
    </svg>
  `,
}

const BellIcon = {
  template: `
    <svg fill="currentColor" viewBox="0 0 20 20">
      <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"/>
    </svg>
  `,
}

const XMarkIcon = {
  template: `
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
    </svg>
  `,
}

// Reactive data
const toasts = ref([])
let toastId = 0

// Toast management
const createToast = (options) => {
  const toast = {
    id: ++toastId,
    type: options.type || 'info',
    title: options.title || 'Notification',
    message: options.message || '',
    duration: options.duration !== undefined ? options.duration : 5000,
    progress: 100,
    ...options,
  }

  toasts.value.push(toast)

  // Auto-remove after duration
  if (toast.duration > 0) {
    let elapsed = 0
    const interval = 50
    const timer = setInterval(() => {
      elapsed += interval
      toast.progress = Math.max(0, 100 - (elapsed / toast.duration) * 100)

      if (elapsed >= toast.duration) {
        clearInterval(timer)
        removeToast(toast.id)
      }
    }, interval)
  }

  return toast.id
}

const removeToast = (id) => {
  const index = toasts.value.findIndex((toast) => toast.id === id)
  if (index > -1) {
    toasts.value.splice(index, 1)
  }
}

const clearAllToasts = () => {
  toasts.value = []
}

// Toast type helpers
const getToastClasses = (toast) => {
  const baseClasses = 'border-l-4'

  switch (toast.type) {
    case 'success':
      return `${baseClasses} border-green-500 bg-green-50 dark:bg-green-900/20`
    case 'error':
      return `${baseClasses} border-red-500 bg-red-50 dark:bg-red-900/20`
    case 'warning':
      return `${baseClasses} border-yellow-500 bg-yellow-50 dark:bg-yellow-900/20`
    case 'security':
      return `${baseClasses} border-purple-500 bg-purple-50 dark:bg-purple-900/20`
    default:
      return `${baseClasses} border-blue-500 bg-blue-50 dark:bg-blue-900/20`
  }
}

const getToastIcon = (type) => {
  switch (type) {
    case 'success':
      return CheckCircleIcon
    case 'error':
      return XCircleIcon
    case 'warning':
      return ExclamationTriangleIcon
    case 'security':
      return ExclamationTriangleIcon
    default:
      return InformationCircleIcon
  }
}

const getIconClasses = (type) => {
  switch (type) {
    case 'success':
      return 'text-green-500'
    case 'error':
      return 'text-red-500'
    case 'warning':
      return 'text-yellow-500'
    case 'security':
      return 'text-purple-500'
    default:
      return 'text-blue-500'
  }
}

// Notification service integration
const handleNewNotification = (notification) => {
  const data = notification.data || {}
  const type = getNotificationType(data.type || notification.type)

  createToast({
    type,
    title: data.title || 'New Notification',
    message: data.message || 'You have a new notification',
    duration: data.priority === 'high' ? 8000 : 5000,
  })
}

const getNotificationType = (notificationType) => {
  if (notificationType?.includes('security') || notificationType?.includes('login')) {
    return 'security'
  } else if (notificationType?.includes('alert') || notificationType?.includes('warning')) {
    return 'warning'
  } else if (notificationType?.includes('error') || notificationType?.includes('failed')) {
    return 'error'
  } else if (notificationType?.includes('success') || notificationType?.includes('completed')) {
    return 'success'
  }
  return 'info'
}

// Global toast API
const showToast = (options) => {
  return createToast(options)
}

const showSuccess = (title, message, options = {}) => {
  return createToast({ type: 'success', title, message, ...options })
}

const showError = (title, message, options = {}) => {
  return createToast({ type: 'error', title, message, duration: 8000, ...options })
}

const showWarning = (title, message, options = {}) => {
  return createToast({ type: 'warning', title, message, ...options })
}

const showInfo = (title, message, options = {}) => {
  return createToast({ type: 'info', title, message, ...options })
}

const showSecurity = (title, message, options = {}) => {
  return createToast({ type: 'security', title, message, duration: 8000, ...options })
}

// Make toast methods globally available
window.toast = {
  show: showToast,
  success: showSuccess,
  error: showError,
  warning: showWarning,
  info: showInfo,
  security: showSecurity,
  clear: clearAllToasts,
}

// Lifecycle
onMounted(() => {
  // Register for real-time notifications
  notificationService.on('notification', handleNewNotification)

  // Listen for global toast events
  window.addEventListener('show-toast', (event) => {
    createToast(event.detail)
  })
})

onUnmounted(() => {
  // Cleanup
  notificationService.off('notification', handleNewNotification)

  window.removeEventListener('show-toast', (event) => {
    createToast(event.detail)
  })
})

// Expose methods for parent components
defineExpose({
  showToast,
  showSuccess,
  showError,
  showWarning,
  showInfo,
  showSecurity,
  clearAllToasts,
})
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s ease;
}

.toast-enter-from {
  opacity: 0;
  transform: translateX(100%);
}

.toast-leave-to {
  opacity: 0;
  transform: translateX(100%);
}

.toast-move {
  transition: transform 0.3s ease;
}
</style>
