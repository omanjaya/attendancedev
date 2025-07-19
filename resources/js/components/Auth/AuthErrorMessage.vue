<!--
  Authentication Error Message Component

  Displays user-friendly error messages for authentication failures
  with appropriate styling and actions based on error type and severity.
-->

<template>
  <div v-if="error" class="auth-error-container">
    <!-- Error Alert -->
    <div class="error-alert" :class="errorClasses">
      <div class="error-icon">
        <component :is="errorIcon" class="h-5 w-5" />
      </div>

      <div class="error-content">
        <div class="error-message">
          {{ error.userMessage }}
        </div>

        <div v-if="error.suggestedAction" class="error-suggestion">
          {{ error.suggestedAction }}
        </div>

        <!-- Retry countdown -->
        <div v-if="retryCountdown" class="retry-countdown">
          {{ retryCountdown }}
        </div>
      </div>

      <!-- Action buttons -->
      <div v-if="showActions" class="error-actions">
        <button
          v-if="error.canRetry && canRetry"
          class="retry-button"
          :disabled="retryLoading"
          @click="handleRetry"
        >
          <div v-if="retryLoading" class="spinner-sm" />
          <ArrowPathIcon v-else class="h-4 w-4" />
          Retry
        </button>

        <button v-if="showHelpButton" class="help-button" @click="showHelp">
          <QuestionMarkCircleIcon class="h-4 w-4" />
          Help
        </button>

        <button v-if="showContactSupport" class="support-button" @click="contactSupport">
          <ChatBubbleLeftRightIcon class="h-4 w-4" />
          Contact Support
        </button>
      </div>
    </div>

    <!-- Detailed Help Section -->
    <div v-if="showDetailedHelp" class="help-section">
      <div class="help-header">
        <h3 class="help-title">
          Troubleshooting Guide
        </h3>
        <button class="close-help" @click="showDetailedHelp = false">
          <XMarkIcon class="h-4 w-4" />
        </button>
      </div>

      <div class="help-content">
        <div v-if="troubleshootingSteps.length > 0" class="troubleshooting-steps">
          <h4>Try these steps:</h4>
          <ol class="steps-list">
            <li v-for="(step, index) in troubleshootingSteps" :key="index" class="step-item">
              {{ step }}
            </li>
          </ol>
        </div>

        <div v-if="additionalResources.length > 0" class="additional-resources">
          <h4>Additional Resources:</h4>
          <ul class="resources-list">
            <li v-for="resource in additionalResources" :key="resource.title" class="resource-item">
              <a
                :href="resource.url"
                target="_blank"
                rel="noopener noreferrer"
                class="resource-link"
              >
                {{ resource.title }}
                <ArrowTopRightOnSquareIcon class="ml-1 inline h-3 w-3" />
              </a>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import {
  ExclamationTriangleIcon,
  XCircleIcon,
  ShieldExclamationIcon,
  ArrowPathIcon,
  QuestionMarkCircleIcon,
  ChatBubbleLeftRightIcon,
  XMarkIcon,
  ArrowTopRightOnSquareIcon,
} from '@heroicons/vue/24/outline'

interface AuthError {
  code: string
  message: string
  type: 'validation' | 'authentication' | 'authorization' | 'network' | 'server'
  severity: 'low' | 'medium' | 'high' | 'critical'
  userMessage: string
  suggestedAction?: string
  canRetry: boolean
  retryAfter?: number
}

interface Props {
  error: AuthError | null
  showActions?: boolean
  showHelpButton?: boolean
  showContactSupport?: boolean
  retryLoading?: boolean
  maxRetries?: number
  retryCount?: number
}

const props = withDefaults(defineProps<Props>(), {
  showActions: true,
  showHelpButton: true,
  showContactSupport: true,
  retryLoading: false,
  maxRetries: 3,
  retryCount: 0,
})

const emit = defineEmits<{
  retry: []
  contactSupport: []
  dismiss: []
}>()

// State
const showDetailedHelp = ref(false)
const retryTimer = ref<NodeJS.Timeout | null>(null)
const retryCountdownSeconds = ref(0)

// Computed properties
const errorClasses = computed(() => {
  if (!props.error) {return ''}

  const baseClasses = 'error-alert-base'
  const severityClasses = {
    low: 'error-info',
    medium: 'error-warning',
    high: 'error-error',
    critical: 'error-critical',
  }

  return `${baseClasses} ${severityClasses[props.error.severity]}`
})

const errorIcon = computed(() => {
  if (!props.error) {return ExclamationTriangleIcon}

  switch (props.error.severity) {
    case 'critical':
      return ShieldExclamationIcon
    case 'high':
      return XCircleIcon
    case 'medium':
      return ExclamationTriangleIcon
    default:
      return ExclamationTriangleIcon
  }
})

const canRetry = computed(() => {
  return props.error?.canRetry && props.retryCount < props.maxRetries
})

const retryCountdown = computed(() => {
  if (!props.error?.retryAfter || retryCountdownSeconds.value <= 0) {return null}

  const minutes = Math.floor(retryCountdownSeconds.value / 60)
  const seconds = retryCountdownSeconds.value % 60

  if (minutes > 0) {
    return `Please wait ${minutes}m ${seconds}s before trying again`
  }
  return `Please wait ${seconds}s before trying again`
})

const troubleshootingSteps = computed(() => {
  if (!props.error) {return []}

  const steps: string[] = []

  switch (props.error.code) {
    case 'invalid_credentials':
      steps.push(
        'Double-check your email address and password',
        'Make sure Caps Lock is not enabled',
        'Try copying and pasting your credentials',
        'Reset your password if you\'re still having trouble'
      )
      break

    case 'invalid_2fa_code':
      steps.push(
        'Make sure your device\'s time is synchronized',
        'Generate a new code from your authenticator app',
        'Check if you\'re using the correct account in your app',
        'Try using a recovery code instead'
      )
      break

    case 'account_locked':
      steps.push(
        'Wait for the lockout period to expire',
        'Contact your administrator if urgent',
        'Use account recovery options if available'
      )
      break

    case 'network_error':
      steps.push(
        'Check your internet connection',
        'Try refreshing the page',
        'Disable any VPN or proxy temporarily',
        'Try using a different network'
      )
      break

    case 'server_error':
      steps.push(
        'Wait a moment and try again',
        'Check if other users are experiencing similar issues',
        'Try using a different browser'
      )
      break
  }

  return steps
})

const additionalResources = computed(() => {
  if (!props.error) {return []}

  const resources: Array<{ title: string; url: string }> = []

  switch (props.error.type) {
    case 'authentication':
      resources.push(
        { title: 'Account Security Guide', url: '/help/account-security' },
        { title: 'Two-Factor Authentication Setup', url: '/help/2fa-setup' }
      )
      break

    case 'network':
      resources.push(
        { title: 'Connection Troubleshooting', url: '/help/connection-issues' },
        { title: 'System Status', url: '/status' }
      )
      break
  }

  return resources
})

// Methods
const handleRetry = () => {
  if (canRetry.value && !props.retryLoading) {
    emit('retry')
  }
}

const showHelp = () => {
  showDetailedHelp.value = !showDetailedHelp.value
}

const contactSupport = () => {
  emit('contactSupport')
}

const startRetryCountdown = () => {
  if (!props.error?.retryAfter) {return}

  retryCountdownSeconds.value = Math.ceil(props.error.retryAfter / 1000)

  retryTimer.value = setInterval(() => {
    retryCountdownSeconds.value--

    if (retryCountdownSeconds.value <= 0) {
      clearInterval(retryTimer.value!)
      retryTimer.value = null
    }
  }, 1000)
}

const clearRetryCountdown = () => {
  if (retryTimer.value) {
    clearInterval(retryTimer.value)
    retryTimer.value = null
  }
  retryCountdownSeconds.value = 0
}

// Lifecycle
onMounted(() => {
  if (props.error?.retryAfter) {
    startRetryCountdown()
  }
})

onUnmounted(() => {
  clearRetryCountdown()
})

// Watch for error changes
watch(
  () => props.error,
  (newError) => {
    clearRetryCountdown()
    if (newError?.retryAfter) {
      startRetryCountdown()
    }
  }
)
</script>

<style scoped>
.auth-error-container {
  @apply space-y-3;
}

.error-alert-base {
  @apply flex items-start space-x-3 rounded-lg border p-4;
}

.error-info {
  @apply border-blue-200 bg-blue-50 text-blue-900;
}

.error-warning {
  @apply border-yellow-200 bg-yellow-50 text-yellow-900;
}

.error-error {
  @apply border-red-200 bg-red-50 text-red-900;
}

.error-critical {
  @apply border-red-300 bg-red-100 text-red-900;
}

.error-icon {
  @apply mt-0.5 flex-shrink-0;
}

.error-info .error-icon {
  @apply text-blue-500;
}

.error-warning .error-icon {
  @apply text-yellow-500;
}

.error-error .error-icon {
  @apply text-red-500;
}

.error-critical .error-icon {
  @apply text-red-600;
}

.error-content {
  @apply min-w-0 flex-1;
}

.error-message {
  @apply mb-1 font-medium;
}

.error-suggestion {
  @apply text-sm opacity-90;
}

.retry-countdown {
  @apply mt-2 text-sm font-medium opacity-75;
}

.error-actions {
  @apply ml-auto flex flex-col gap-2 sm:flex-row;
}

.retry-button {
  @apply inline-flex items-center space-x-1 rounded-md bg-blue-600 px-3 py-1.5 text-sm font-medium text-white transition-colors duration-200 hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50;
}

.help-button {
  @apply inline-flex items-center space-x-1 rounded-md bg-gray-600 px-3 py-1.5 text-sm font-medium text-white transition-colors duration-200 hover:bg-gray-700;
}

.support-button {
  @apply inline-flex items-center space-x-1 rounded-md bg-green-600 px-3 py-1.5 text-sm font-medium text-white transition-colors duration-200 hover:bg-green-700;
}

.spinner-sm {
  @apply h-3 w-3 animate-spin rounded-full border-b-2 border-white;
}

.help-section {
  @apply rounded-lg border border-gray-200 bg-gray-50 p-4;
}

.help-header {
  @apply mb-3 flex items-center justify-between;
}

.help-title {
  @apply text-lg font-semibold text-gray-900;
}

.close-help {
  @apply text-gray-400 transition-colors hover:text-gray-600;
}

.help-content {
  @apply space-y-4;
}

.troubleshooting-steps h4,
.additional-resources h4 {
  @apply mb-2 font-medium text-gray-900;
}

.steps-list {
  @apply list-inside list-decimal space-y-1 text-sm text-gray-700;
}

.step-item {
  @apply leading-relaxed;
}

.resources-list {
  @apply space-y-1;
}

.resource-link {
  @apply text-sm text-blue-600 transition-colors hover:text-blue-800;
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .error-info {
    @apply border-blue-700 bg-blue-900/30 text-blue-100;
  }

  .error-warning {
    @apply border-yellow-700 bg-yellow-900/30 text-yellow-100;
  }

  .error-error {
    @apply border-red-700 bg-red-900/30 text-red-100;
  }

  .error-critical {
    @apply border-red-600 bg-red-900/40 text-red-100;
  }

  .help-section {
    @apply border-gray-700 bg-gray-800;
  }

  .help-title {
    @apply text-gray-100;
  }

  .steps-list {
    @apply text-gray-300;
  }
}

/* Mobile responsiveness */
@media (max-width: 640px) {
  .error-alert-base {
    @apply flex-col space-x-0 space-y-3;
  }

  .error-actions {
    @apply w-full flex-col;
  }

  .retry-button,
  .help-button,
  .support-button {
    @apply w-full justify-center;
  }
}
</style>
