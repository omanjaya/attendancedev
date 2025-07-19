<!--
  Support Contact Modal Component

  Modal for users to contact support when experiencing authentication issues.
  Includes error context and pre-filled information for better support.
-->

<template>
  <div
    class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
    role="dialog"
    aria-modal="true"
  >
    <div class="mx-4 max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl bg-white">
      <!-- Header -->
      <div class="flex items-center justify-between border-b border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900">
          Contact Support
        </h2>
        <button
          class="text-gray-400 transition-colors hover:text-gray-600"
          aria-label="Close modal"
          @click="$emit('close')"
        >
          <XMarkIcon class="h-6 w-6" />
        </button>
      </div>

      <!-- Content -->
      <div class="space-y-6 p-6">
        <!-- Error Context -->
        <div v-if="errorContext" class="rounded-lg border border-red-200 bg-red-50 p-4">
          <div class="flex items-start space-x-3">
            <ExclamationTriangleIcon class="mt-0.5 h-5 w-5 flex-shrink-0 text-red-600" />
            <div>
              <h3 class="mb-1 font-medium text-red-900">
                Current Issue
              </h3>
              <p class="text-sm text-red-800">
                {{ errorContext.userMessage }}
              </p>
              <div class="mt-2 text-xs text-red-600">
                <span>Error Code: {{ errorContext.code }}</span>
                <span class="mx-2">•</span>
                <span>Type: {{ errorContext.type }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Contact Form -->
        <form class="space-y-4" @submit.prevent="submitSupportRequest">
          <!-- Contact Method -->
          <div>
            <label class="mb-2 block text-sm font-medium text-gray-700">
              Preferred Contact Method
            </label>
            <div class="grid grid-cols-2 gap-4">
              <label class="contact-method-option">
                <input
                  v-model="formData.contactMethod"
                  type="radio"
                  value="email"
                  class="sr-only"
                >
                <div class="method-card">
                  <EnvelopeIcon class="h-5 w-5 text-blue-600" />
                  <span class="font-medium">Email</span>
                  <span class="text-sm text-gray-600">Response within 24 hours</span>
                </div>
              </label>

              <label class="contact-method-option">
                <input
                  v-model="formData.contactMethod"
                  type="radio"
                  value="phone"
                  class="sr-only"
                >
                <div class="method-card">
                  <PhoneIcon class="h-5 w-5 text-green-600" />
                  <span class="font-medium">Phone</span>
                  <span class="text-sm text-gray-600">Immediate assistance</span>
                </div>
              </label>
            </div>
          </div>

          <!-- Name -->
          <div>
            <label for="name" class="mb-2 block text-sm font-medium text-gray-700">
              Your Name
            </label>
            <input
              id="name"
              v-model="formData.name"
              type="text"
              required
              class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Enter your full name"
            >
          </div>

          <!-- Email -->
          <div>
            <label for="email" class="mb-2 block text-sm font-medium text-gray-700">
              Email Address
            </label>
            <input
              id="email"
              v-model="formData.email"
              type="email"
              required
              class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Enter your email address"
            >
          </div>

          <!-- Phone (if phone contact selected) -->
          <div v-if="formData.contactMethod === 'phone'">
            <label for="phone" class="mb-2 block text-sm font-medium text-gray-700">
              Phone Number
            </label>
            <input
              id="phone"
              v-model="formData.phone"
              type="tel"
              required
              class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Enter your phone number"
            >
          </div>

          <!-- Issue Priority -->
          <div>
            <label for="priority" class="mb-2 block text-sm font-medium text-gray-700">
              Issue Priority
            </label>
            <select
              id="priority"
              v-model="formData.priority"
              class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="low">
                Low - General inquiry
              </option>
              <option value="medium">
                Medium - Account access issue
              </option>
              <option value="high">
                High - Unable to access system
              </option>
              <option value="critical">
                Critical - Emergency access needed
              </option>
            </select>
          </div>

          <!-- Message -->
          <div>
            <label for="message" class="mb-2 block text-sm font-medium text-gray-700">
              Describe Your Issue
            </label>
            <textarea
              id="message"
              v-model="formData.message"
              rows="4"
              required
              class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Please describe the issue you're experiencing..."
            />
          </div>

          <!-- System Information -->
          <div class="rounded-lg bg-gray-50 p-4">
            <h4 class="mb-2 font-medium text-gray-900">
              System Information
            </h4>
            <div class="space-y-1 text-sm text-gray-600">
              <div>Browser: {{ systemInfo.browser }}</div>
              <div>Operating System: {{ systemInfo.os }}</div>
              <div>Screen Resolution: {{ systemInfo.screenResolution }}</div>
              <div>Timestamp: {{ systemInfo.timestamp }}</div>
            </div>
            <label class="mt-3 flex items-center space-x-2">
              <input
                v-model="formData.includeSystemInfo"
                type="checkbox"
                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
              >
              <span class="text-sm text-gray-700">Include system information in support request</span>
            </label>
          </div>

          <!-- Submit Button -->
          <div class="flex justify-end space-x-3 pt-4">
            <button
              type="button"
              class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-200"
              @click="$emit('close')"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="submitting"
              class="inline-flex items-center space-x-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
            >
              <div v-if="submitting" class="spinner-sm" />
              <PaperAirplaneIcon v-else class="h-4 w-4" />
              <span>{{ submitting ? 'Sending...' : 'Send Support Request' }}</span>
            </button>
          </div>
        </form>

        <!-- Quick Contact Options -->
        <div class="border-t border-gray-200 pt-4">
          <h4 class="mb-3 font-medium text-gray-900">
            Quick Contact Options
          </h4>
          <div class="space-y-2">
            <div class="flex items-center space-x-3 text-sm">
              <PhoneIcon class="h-4 w-4 text-green-600" />
              <span>Emergency Support: </span>
              <a href="tel:+1234567890" class="font-medium text-blue-600 hover:text-blue-800">
                +1 (234) 567-8900
              </a>
            </div>
            <div class="flex items-center space-x-3 text-sm">
              <EnvelopeIcon class="h-4 w-4 text-blue-600" />
              <span>Email Support: </span>
              <a
                href="mailto:support@school.edu"
                class="font-medium text-blue-600 hover:text-blue-800"
              >
                support@school.edu
              </a>
            </div>
            <div class="flex items-center space-x-3 text-sm">
              <GlobeAltIcon class="h-4 w-4 text-purple-600" />
              <span>Help Center: </span>
              <a href="/help" target="_blank" class="font-medium text-blue-600 hover:text-blue-800">
                View Help Articles
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useNotificationStore } from '@/stores/notification'
import {
  XMarkIcon,
  ExclamationTriangleIcon,
  EnvelopeIcon,
  PhoneIcon,
  PaperAirplaneIcon,
  GlobeAltIcon,
} from '@heroicons/vue/24/outline'

interface AuthError {
  code: string
  userMessage: string
  type: string
  severity: string
}

interface Props {
  errorContext?: AuthError | null
}

const props = defineProps<Props>()

const emit = defineEmits<{
  close: []
}>()

const notificationStore = useNotificationStore()

// Form data
const formData = reactive({
  contactMethod: 'email',
  name: '',
  email: '',
  phone: '',
  priority: 'medium',
  message: '',
  includeSystemInfo: true,
})

const submitting = ref(false)

// System information
const systemInfo = reactive({
  browser: '',
  os: '',
  screenResolution: '',
  timestamp: '',
})

// Methods
const detectSystemInfo = () => {
  const ua = navigator.userAgent

  // Browser detection
  if (ua.includes('Chrome')) {
    systemInfo.browser = 'Google Chrome'
  } else if (ua.includes('Firefox')) {
    systemInfo.browser = 'Mozilla Firefox'
  } else if (ua.includes('Safari')) {
    systemInfo.browser = 'Safari'
  } else if (ua.includes('Edge')) {
    systemInfo.browser = 'Microsoft Edge'
  } else {
    systemInfo.browser = 'Unknown'
  }

  // OS detection
  if (ua.includes('Windows')) {
    systemInfo.os = 'Windows'
  } else if (ua.includes('Mac')) {
    systemInfo.os = 'macOS'
  } else if (ua.includes('Linux')) {
    systemInfo.os = 'Linux'
  } else if (ua.includes('Android')) {
    systemInfo.os = 'Android'
  } else if (ua.includes('iOS')) {
    systemInfo.os = 'iOS'
  } else {
    systemInfo.os = 'Unknown'
  }

  // Screen resolution
  systemInfo.screenResolution = `${screen.width}x${screen.height}`

  // Timestamp
  systemInfo.timestamp = new Date().toLocaleString()
}

const submitSupportRequest = async () => {
  submitting.value = true

  try {
    // Prepare the support request data
    const supportData = {
      ...formData,
      errorContext: props.errorContext,
      systemInfo: formData.includeSystemInfo ? systemInfo : null,
      url: window.location.href,
      userAgent: navigator.userAgent,
    }

    // Send support request
    const response = await fetch('/api/support/contact', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
      },
      body: JSON.stringify(supportData),
    })

    if (!response.ok) {
      throw new Error('Failed to send support request')
    }

    const result = await response.json()

    if (result.success) {
      notificationStore.addNotification({
        type: 'success',
        title: 'Support Request Sent',
        message: `Your support request has been submitted. Reference ID: ${result.ticketId}`,
        timeout: 10000,
      })

      emit('close')
    } else {
      throw new Error(result.message || 'Failed to send support request')
    }
  } catch (error) {
    console.error('Support request error:', error)

    notificationStore.addNotification({
      type: 'error',
      title: 'Failed to Send Request',
      message:
        'There was an error sending your support request. Please try again or contact support directly.',
      timeout: 8000,
    })
  } finally {
    submitting.value = false
  }
}

// Pre-fill error message if error context is provided
const prefillErrorMessage = () => {
  if (props.errorContext) {
    formData.message = `I'm experiencing the following authentication issue:

Error: ${props.errorContext.userMessage}
Error Code: ${props.errorContext.code}
Error Type: ${props.errorContext.type}

Please help me resolve this issue.`

    // Set priority based on error severity
    switch (props.errorContext.severity) {
      case 'critical':
        formData.priority = 'critical'
        break
      case 'high':
        formData.priority = 'high'
        break
      case 'medium':
        formData.priority = 'medium'
        break
      default:
        formData.priority = 'low'
    }
  }
}

// Lifecycle
onMounted(() => {
  detectSystemInfo()
  prefillErrorMessage()
})
</script>

<style scoped>
.contact-method-option {
  @apply cursor-pointer;
}

.method-card {
  @apply space-y-2 rounded-lg border-2 border-gray-200 p-4 text-center transition-all duration-200 hover:border-gray-300;
}

.contact-method-option input:checked + .method-card {
  @apply border-blue-500 bg-blue-50;
}

.spinner-sm {
  @apply h-4 w-4 animate-spin rounded-full border-b-2 border-white;
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .method-card {
    @apply border-gray-600 bg-gray-800;
  }

  .contact-method-option input:checked + .method-card {
    @apply border-blue-400 bg-blue-900;
  }
}
</style>
