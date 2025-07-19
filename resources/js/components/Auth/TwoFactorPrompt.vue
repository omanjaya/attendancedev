<template>
  <div class="two-factor-prompt">
    <!-- Header -->
    <div class="header-section">
      <div class="icon-container">
        <Icon name="shield-check" class="security-icon" />
      </div>
      <h2 class="title">
        Two-Factor Authentication
      </h2>
      <p class="subtitle">
        Enter the verification code from your authenticator app to complete login.
      </p>
    </div>

    <!-- Alert Messages -->
    <div v-if="alert.show" class="alert" :class="alert.type">
      <Icon :name="alert.icon" class="h-4 w-4" />
      <span>{{ alert.message }}</span>
    </div>

    <!-- Main Form -->
    <form class="verification-form" @submit.prevent="handleSubmit">
      <!-- Code Input -->
      <div class="input-section">
        <VerificationInput
          v-model="verificationCode"
          :label="inputLabel"
          :placeholder="inputPlaceholder"
          :codeType="verificationMethod"
          :error="errors.code"
          :disabled="loading"
          :showTimer="verificationMethod === 'totp'"
          :showAlternatives="true"
          :allowRecoveryCode="true"
          :allowSms="user?.phone && smsEnabled"
          :autoSubmit="true"
          @complete="handleSubmit"
          @switch-to-recovery="switchToRecovery"
          @request-sms="requestSMS"
        />
      </div>

      <!-- Remember Device Option -->
      <div v-if="allowRememberDevice" class="remember-device">
        <label class="checkbox-container">
          <input v-model="rememberDevice" type="checkbox" class="checkbox-input">
          <div class="checkbox-custom" />
          <span class="checkbox-label">
            Trust this device for 30 days
            <span class="tooltip-trigger">
              <Icon name="info" class="h-3 w-3" />
              <div class="tooltip">
                You won't need to enter a 2FA code on this device for 30 days. Only enable this on
                trusted devices.
              </div>
            </span>
          </span>
        </label>
      </div>

      <!-- Action Buttons -->
      <div class="actions">
        <button type="submit" class="btn-primary" :disabled="!isValidCode || loading">
          <div v-if="loading" class="spinner" />
          <Icon v-else name="lock-open" class="h-4 w-4" />
          {{ loading ? 'Verifying...' : 'Verify & Continue' }}
        </button>
      </div>
    </form>

    <!-- Alternative Methods -->
    <div class="alternatives-section">
      <div class="divider">
        <span class="divider-text">Having trouble?</span>
      </div>

      <div class="alternative-actions">
        <!-- Use Recovery Code -->
        <button
          v-if="verificationMethod !== 'recovery'"
          class="alternative-btn"
          @click="switchToRecovery"
        >
          <Icon name="key" class="h-4 w-4" />
          Use Recovery Code
        </button>

        <!-- Back to Authenticator -->
        <button
          v-if="verificationMethod === 'recovery'"
          class="alternative-btn"
          @click="switchToAuthenticator"
        >
          <Icon name="smartphone" class="h-4 w-4" />
          Use Authenticator App
        </button>

        <!-- Request SMS -->
        <button
          v-if="user?.phone && smsEnabled && verificationMethod !== 'sms'"
          class="alternative-btn"
          :disabled="smsLoading"
          @click="requestSMS"
        >
          <div v-if="smsLoading" class="spinner-sm" />
          <Icon v-else name="phone" class="h-4 w-4" />
          Send SMS Code
        </button>

        <!-- Emergency Recovery -->
        <button class="alternative-btn emergency" @click="showEmergencyRecovery">
          <Icon name="exclamation-triangle" class="h-4 w-4" />
          Emergency Access
        </button>
      </div>
    </div>

    <!-- Emergency Recovery Modal -->
    <EmergencyRecoveryModal
      v-if="showEmergencyModal"
      @close="showEmergencyModal = false"
      @submit="handleEmergencyRecovery"
    />

    <!-- Help Section -->
    <div class="help-section">
      <details class="help-details">
        <summary class="help-summary">
          <Icon name="help-circle" class="h-4 w-4" />
          Need help with 2FA?
        </summary>
        <div class="help-content">
          <div class="help-item">
            <h4>Lost your authenticator device?</h4>
            <p>
              Use one of your recovery codes or contact your administrator for emergency access.
            </p>
          </div>
          <div class="help-item">
            <h4>Code not working?</h4>
            <p>Make sure your device's time is synchronized and you're using the latest code.</p>
          </div>
          <div class="help-item">
            <h4>New device?</h4>
            <p>You'll need to verify your identity even if you trust this device.</p>
          </div>
        </div>
      </details>
    </div>

    <!-- Logout Option -->
    <div class="logout-section">
      <button class="logout-btn" @click="logout">
        <Icon name="log-out" class="h-4 w-4" />
        Sign out and try different account
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useToast } from '@/composables/useToast'
import { useAuthErrorHandler } from '@/composables/useAuthErrorHandler'
import { twoFactorService } from '@/services/twoFactor'
import VerificationInput from '@/components/Security/VerificationInput.vue'
import EmergencyRecoveryModal from '@/components/Auth/EmergencyRecoveryModal.vue'

// Props
const props = defineProps({
  user: {
    type: Object,
    required: true
  },
  intendedUrl: {
    type: String,
    default: '/dashboard'
  },
  allowRememberDevice: {
    type: Boolean,
    default: true
  },
  smsEnabled: {
    type: Boolean,
    default: false
  }
})

// Emits
const emit = defineEmits(['success', 'logout', 'error'])

// Reactive data
const verificationCode = ref('')
const verificationMethod = ref('totp')
const rememberDevice = ref(false)
const loading = ref(false)
const smsLoading = ref(false)
const showEmergencyModal = ref(false)

const errors = reactive({
  code: ''
})

const alert = reactive({
  show: false,
  type: 'info',
  message: '',
  icon: 'info'
})

// Composables
const { toast } = useToast()
const authErrorHandler = useAuthErrorHandler()

// Computed
const inputLabel = computed(() => {
  switch (verificationMethod.value) {
    case 'totp':
      return 'Authenticator Code'
    case 'recovery':
      return 'Recovery Code'
    case 'sms':
      return 'SMS Code'
    default:
      return 'Verification Code'
  }
})

const inputPlaceholder = computed(() => {
  switch (verificationMethod.value) {
    case 'totp':
    case 'sms':
      return '000000'
    case 'recovery':
      return 'XXXXXXXX'
    default:
      return '000000'
  }
})

const isValidCode = computed(() => {
  const validation = authErrorHandler.validate2FACode(
    verificationCode.value,
    verificationMethod.value as 'totp' | 'sms' | 'recovery'
  )
  return validation.isValid
})

// Methods
const handleSubmit = async () => {
  if (!isValidCode.value || loading.value) {return}

  // Validate code format first
  const validation = authErrorHandler.validate2FACode(
    verificationCode.value,
    verificationMethod.value as 'totp' | 'sms' | 'recovery'
  )

  if (!validation.isValid) {
    errors.code = validation.message || 'Invalid code format'
    return
  }

  loading.value = true
  errors.code = ''
  hideAlert()
  authErrorHandler.clearError()

  try {
    const response = await twoFactorService.verify(
      verificationCode.value,
      verificationMethod.value
    )

    if (response.success) {
      // Handle remember device
      if (rememberDevice.value) {
        await handleRememberDevice()
      }

      // Show success message
      if (response.warning) {
        showAlert('warning', response.warning, 'exclamation-triangle')
      } else {
        toast.success('Successfully verified!')
      }

      // Emit success with redirect info
      emit('success', {
        redirect: response.redirect || props.intendedUrl,
        warning: response.warning,
        remainingCodes: response.remaining_codes
      })
    }
  } catch (error) {
    // Handle error with enhanced error handler
    const authError = await authErrorHandler.handle2FAError(error, verificationMethod.value)
    errors.code = authError.userMessage
    showAlert('error', authError.userMessage, 'x-circle')

    // Clear the code input for security
    verificationCode.value = ''
  } finally {
    loading.value = false
  }
}

const switchToRecovery = () => {
  verificationMethod.value = 'recovery'
  verificationCode.value = ''
  errors.code = ''
  showAlert('info', 'Enter one of your 8-character recovery codes', 'key')
}

const switchToAuthenticator = () => {
  verificationMethod.value = 'totp'
  verificationCode.value = ''
  errors.code = ''
  hideAlert()
}

const requestSMS = async () => {
  if (smsLoading.value) {return}

  smsLoading.value = true

  try {
    await twoFactorService.sendSMS()
    verificationMethod.value = 'sms'
    verificationCode.value = ''
    errors.code = ''
    showAlert('success', 'SMS code sent to your phone', 'phone')
    toast.success('SMS code sent!')
  } catch (error) {
    toast.error('Failed to send SMS code')
  } finally {
    smsLoading.value = false
  }
}

const handleRememberDevice = async () => {
  try {
    // This would be handled by the backend middleware
    // Just for UI feedback
    toast.success('Device will be remembered for 30 days')
  } catch (error) {
    console.warn('Failed to remember device:', error)
  }
}

const showEmergencyRecovery = () => {
  showEmergencyModal.value = true
}

const handleEmergencyRecovery = async (recoveryData) => {
  try {
    const response = await fetch('/2fa/emergency-recovery', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
      },
      body: JSON.stringify(recoveryData)
    })

    const result = await response.json()

    if (result.success) {
      showEmergencyModal.value = false
      showAlert('success', result.message, 'check-circle')
    } else {
      throw new Error(result.message)
    }
  } catch (error) {
    toast.error('Failed to submit emergency recovery request')
  }
}

const logout = () => {
  emit('logout')
}

const showAlert = (type, message, icon) => {
  alert.show = true
  alert.type = type
  alert.message = message
  alert.icon = icon
}

const hideAlert = () => {
  alert.show = false
}

// Auto-hide alerts after 5 seconds
const autoHideAlert = () => {
  if (alert.show && alert.type !== 'error') {
    setTimeout(() => {
      if (alert.show) {hideAlert()}
    }, 5000)
  }
}

// Watch for alert changes
watch(() => alert.show, (show) => {
  if (show) {autoHideAlert()}
})

// Lifecycle
onMounted(() => {
  // Focus on the input when component mounts
  setTimeout(() => {
    const input = document.querySelector('input[type="text"]')
    if (input) {input.focus()}
  }, 100)
})
</script>

<style scoped>
.two-factor-prompt {
  @apply mx-auto max-w-md space-y-6 p-6;
}

.header-section {
  @apply space-y-3 text-center;
}

.icon-container {
  @apply flex justify-center;
}

.security-icon {
  @apply h-12 w-12 text-blue-600;
}

.title {
  @apply text-2xl font-bold text-gray-900;
}

.subtitle {
  @apply text-gray-600;
}

.alert {
  @apply flex items-center space-x-2 rounded-lg p-3 text-sm font-medium;
}

.alert.info {
  @apply border border-blue-200 bg-blue-50 text-blue-800;
}

.alert.success {
  @apply border border-green-200 bg-green-50 text-green-800;
}

.alert.warning {
  @apply border border-yellow-200 bg-yellow-50 text-yellow-800;
}

.alert.error {
  @apply border border-red-200 bg-red-50 text-red-800;
}

.verification-form {
  @apply space-y-4;
}

.input-section {
  @apply space-y-2;
}

.remember-device {
  @apply pt-2;
}

.checkbox-container {
  @apply flex cursor-pointer items-start space-x-3;
}

.checkbox-input {
  @apply sr-only;
}

.checkbox-custom {
  @apply mt-0.5 h-4 w-4 flex-shrink-0 rounded border-2 border-gray-300 transition-all duration-200;
}

.checkbox-input:checked + .checkbox-custom {
  @apply border-blue-600 bg-blue-600;
}

.checkbox-input:checked + .checkbox-custom::after {
  content: '✓';
  @apply flex items-center justify-center text-xs text-white;
}

.checkbox-label {
  @apply flex items-center space-x-1 text-sm text-gray-700;
}

.tooltip-trigger {
  @apply relative inline-block;
}

.tooltip {
  @apply invisible absolute bottom-full left-1/2 mb-1 -translate-x-1/2 transform whitespace-nowrap rounded bg-gray-900 px-2 py-1 text-xs text-white opacity-0 transition-all duration-200;
}

.tooltip-trigger:hover .tooltip {
  @apply visible opacity-100;
}

.actions {
  @apply pt-2;
}

.btn-primary {
  @apply inline-flex w-full items-center justify-center space-x-2 rounded-lg bg-blue-600 px-4 py-3 font-medium text-white transition-colors duration-200 hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50;
}

.spinner {
  @apply h-4 w-4 animate-spin rounded-full border-b-2 border-white;
}

.alternatives-section {
  @apply space-y-4;
}

.divider {
  @apply relative;
}

.divider::before {
  @apply absolute inset-0 flex items-center;
  content: '';
}

.divider::before {
  @apply border-t border-gray-300;
}

.divider-text {
  @apply relative bg-white px-3 text-sm text-gray-500;
}

.alternative-actions {
  @apply grid grid-cols-1 gap-2 sm:grid-cols-2;
}

.alternative-btn {
  @apply inline-flex items-center justify-center space-x-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition-all duration-200 hover:bg-gray-50 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 disabled:cursor-not-allowed disabled:opacity-50;
}

.alternative-btn.emergency {
  @apply border-red-300 text-red-700 hover:bg-red-50;
}

.spinner-sm {
  @apply h-3 w-3 animate-spin rounded-full border-b-2 border-current;
}

.help-section {
  @apply border-t border-gray-200 pt-4;
}

.help-details {
  @apply space-y-2;
}

.help-summary {
  @apply flex cursor-pointer items-center space-x-2 text-sm font-medium text-gray-700 transition-colors duration-200 hover:text-gray-900;
}

.help-content {
  @apply mt-3 space-y-3 text-sm text-gray-600;
}

.help-item h4 {
  @apply mb-1 font-medium text-gray-900;
}

.logout-section {
  @apply border-t border-gray-200 pt-4 text-center;
}

.logout-btn {
  @apply inline-flex items-center space-x-2 text-sm text-gray-500 transition-colors duration-200 hover:text-gray-700;
}

/* Mobile optimizations */
@media (max-width: 640px) {
  .two-factor-prompt {
    @apply p-4;
  }

  .alternative-actions {
    @apply grid-cols-1;
  }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .two-factor-prompt {
    @apply text-gray-100;
  }

  .title {
    @apply text-gray-100;
  }

  .subtitle {
    @apply text-gray-300;
  }

  .checkbox-custom {
    @apply border-gray-600;
  }

  .divider-text {
    @apply bg-gray-800;
  }
}
</style>
