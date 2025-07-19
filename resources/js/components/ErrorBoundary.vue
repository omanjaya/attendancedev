<template>
  <div v-if="hasError" class="error-boundary">
    <div
      class="rounded-lg border border-red-200 bg-red-50 p-6 dark:border-red-800 dark:bg-red-900/20"
    >
      <div class="flex items-start">
        <div class="flex-shrink-0">
          <ExclamationTriangleIcon class="h-6 w-6 text-red-400" />
        </div>
        <div class="ml-3 flex-1">
          <h3 class="text-sm font-medium text-red-800 dark:text-red-200">
            {{ errorTitle }}
          </h3>
          <div class="mt-2 text-sm text-red-700 dark:text-red-300">
            <p>{{ errorMessage }}</p>
            <p v-if="showDetails" class="mt-2 break-all font-mono text-xs">
              {{ errorDetails }}
            </p>
          </div>
          <div class="mt-4 flex flex-wrap gap-2">
            <button
              class="inline-flex items-center rounded-md bg-red-100 px-3 py-2 text-sm font-medium text-red-800 hover:bg-red-200 dark:bg-red-800 dark:text-red-100 dark:hover:bg-red-700"
              @click="retry"
            >
              <ArrowPathIcon class="mr-1 h-4 w-4" />
              Try Again
            </button>
            <button
              class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-medium text-red-800 ring-1 ring-red-300 hover:bg-red-50 dark:bg-red-900 dark:text-red-100 dark:ring-red-600 dark:hover:bg-red-800"
              @click="reload"
            >
              <ArrowPathIcon class="mr-1 h-4 w-4" />
              Reload Page
            </button>
            <button
              v-if="!showDetails"
              class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-medium text-red-800 ring-1 ring-red-300 hover:bg-red-50 dark:bg-red-900 dark:text-red-100 dark:ring-red-600 dark:hover:bg-red-800"
              @click="showDetails = true"
            >
              <InformationCircleIcon class="mr-1 h-4 w-4" />
              Show Details
            </button>
            <button
              v-else
              class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-medium text-red-800 ring-1 ring-red-300 hover:bg-red-50 dark:bg-red-900 dark:text-red-100 dark:ring-red-600 dark:hover:bg-red-800"
              @click="showDetails = false"
            >
              <EyeSlashIcon class="mr-1 h-4 w-4" />
              Hide Details
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
  <slot v-else />
</template>

<script setup lang="ts">
import { ref, onErrorCaptured, nextTick } from 'vue'
import {
  ExclamationTriangleIcon,
  ArrowPathIcon,
  InformationCircleIcon,
  EyeSlashIcon,
} from '@heroicons/vue/24/outline'

interface Props {
  fallbackTitle?: string
  fallbackMessage?: string
  showRetry?: boolean
  showReload?: boolean
  autoRetry?: boolean
  autoRetryDelay?: number
  maxRetries?: number
  onError?: (error: Error, info: string) => void
}

const props = withDefaults(defineProps<Props>(), {
  fallbackTitle: 'Something went wrong',
  fallbackMessage: 'An unexpected error occurred. Please try again.',
  showRetry: true,
  showReload: true,
  autoRetry: false,
  autoRetryDelay: 3000,
  maxRetries: 3,
})

const hasError = ref<boolean>(false)
const errorTitle = ref<string>('')
const errorMessage = ref<string>('')
const errorDetails = ref<string>('')
const showDetails = ref<boolean>(false)
const retryCount = ref<number>(0)
const retryTimer = ref<NodeJS.Timeout | null>(null)

// Error capture
onErrorCaptured((error: Error, instance, info: string) => {
  console.error('ErrorBoundary caught error:', {
    error,
    instance: instance?.$options.name || 'Unknown',
    info,
    timestamp: new Date().toISOString(),
  })

  // Set error state
  hasError.value = true
  errorTitle.value = getErrorTitle(error)
  errorMessage.value = getErrorMessage(error)
  errorDetails.value = getErrorDetails(error, info)

  // Call custom error handler if provided
  if (props.onError) {
    props.onError(error, info)
  }

  // Send to error tracking
  if (window.errorTracker) {
    window.errorTracker.captureException(error, {
      component: 'ErrorBoundary',
      context: info,
      extra: {
        component: instance?.$options.name,
        retryCount: retryCount.value,
      },
    })
  }

  // Auto retry if enabled
  if (props.autoRetry && retryCount.value < props.maxRetries) {
    retryTimer.value = setTimeout(() => {
      retry()
    }, props.autoRetryDelay)
  }

  // Prevent error from propagating up
  return false
})

const getErrorTitle = (error: Error): string => {
  // Common error types
  if (error.name === 'ChunkLoadError') {
    return 'Loading Error'
  }
  if (error.name === 'NetworkError') {
    return 'Network Error'
  }
  if (error.name === 'TypeError') {
    return 'Application Error'
  }
  if (error.name === 'ReferenceError') {
    return 'Reference Error'
  }

  return props.fallbackTitle
}

const getErrorMessage = (error: Error): string => {
  // Provide user-friendly messages for common errors
  if (error.name === 'ChunkLoadError') {
    return 'Failed to load application resources. This might be due to a network issue or an updated version.'
  }
  if (error.name === 'NetworkError') {
    return 'Unable to connect to the server. Please check your internet connection.'
  }
  if (error.message.includes('ResizeObserver')) {
    return 'A minor display issue occurred. The application should continue to work normally.'
  }
  if (error.message.includes('Non-Error promise rejection')) {
    return 'A request was cancelled or failed. You can try the action again.'
  }

  // For development, show actual error message
  if (import.meta.env.DEV) {
    return error.message || props.fallbackMessage
  }

  return props.fallbackMessage
}

const getErrorDetails = (error: Error, info: string): string => {
  const details = []

  if (error.name) {details.push(`Type: ${error.name}`)}
  if (error.message) {details.push(`Message: ${error.message}`)}
  if (info) {details.push(`Context: ${info}`)}
  if (error.stack && import.meta.env.DEV) {
    details.push(`Stack: ${error.stack}`)
  }

  return details.join('\n')
}

const retry = async (): Promise<void> => {
  if (retryTimer.value) {
    clearTimeout(retryTimer.value)
    retryTimer.value = null
  }

  retryCount.value++
  hasError.value = false
  showDetails.value = false

  // Reset error state
  errorTitle.value = ''
  errorMessage.value = ''
  errorDetails.value = ''

  // Force re-render
  await nextTick()
}

const reload = (): void => {
  window.location.reload()
}

// Cleanup on unmount
import { onUnmounted } from 'vue'
onUnmounted(() => {
  if (retryTimer.value) {
    clearTimeout(retryTimer.value)
  }
})
</script>

<style scoped>
.error-boundary {
  min-height: 200px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

/* Ensure proper text wrapping for long error messages */
.break-all {
  word-break: break-all;
  white-space: pre-wrap;
}
</style>
