<template>
  <div class="two-factor-prompt">
    <!-- Header -->
    <div class="header-section">
      <div class="icon-container">
        <Icon name="shield-check" class="security-icon" />
      </div>
      <h2 class="title">Two-Factor Authentication</h2>
      <p class="subtitle">
        Enter the verification code from your authenticator app to complete login.
      </p>
    </div>

    <!-- Alert Messages -->
    <div v-if="alert.show" class="alert" :class="alert.type">
      <Icon :name="alert.icon" class="w-4 h-4" />
      <span>{{ alert.message }}</span>
    </div>

    <!-- Main Form -->
    <form @submit.prevent="handleSubmit" class="verification-form">
      <!-- Code Input -->
      <div class="input-section">
        <VerificationInput
          v-model="verificationCode"
          :label="inputLabel"
          :placeholder="inputPlaceholder"
          :code-type="verificationMethod"
          :error="errors.code"
          :disabled="loading"
          :show-timer="verificationMethod === 'totp'"
          :show-alternatives="true"
          :allow-recovery-code="true"
          :allow-sms="user?.phone && smsEnabled"
          :auto-submit="true"
          @complete="handleSubmit"
          @switch-to-recovery="switchToRecovery"
          @request-sms="requestSMS"
        />
      </div>

      <!-- Remember Device Option -->
      <div v-if="allowRememberDevice" class="remember-device">
        <label class="checkbox-container">
          <input
            v-model="rememberDevice"
            type="checkbox"
            class="checkbox-input"
          />
          <div class="checkbox-custom"></div>
          <span class="checkbox-label">
            Trust this device for 30 days
            <span class="tooltip-trigger">
              <Icon name="info" class="w-3 h-3" />
              <div class="tooltip">
                You won't need to enter a 2FA code on this device for 30 days.
                Only enable this on trusted devices.
              </div>
            </span>
          </span>
        </label>
      </div>

      <!-- Action Buttons -->
      <div class="actions">
        <button
          type="submit"
          class="btn-primary"
          :disabled="!isValidCode || loading"
        >
          <div v-if="loading" class="spinner" />
          <Icon v-else name="lock-open" class="w-4 h-4" />
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
          @click="switchToRecovery"
          class="alternative-btn"
        >
          <Icon name="key" class="w-4 h-4" />
          Use Recovery Code
        </button>

        <!-- Back to Authenticator -->
        <button
          v-if="verificationMethod === 'recovery'"
          @click="switchToAuthenticator"
          class="alternative-btn"
        >
          <Icon name="smartphone" class="w-4 h-4" />
          Use Authenticator App
        </button>

        <!-- Request SMS -->
        <button
          v-if="user?.phone && smsEnabled && verificationMethod !== 'sms'"
          @click="requestSMS"
          class="alternative-btn"
          :disabled="smsLoading"
        >
          <div v-if="smsLoading" class="spinner-sm" />
          <Icon v-else name="phone" class="w-4 h-4" />
          Send SMS Code
        </button>

        <!-- Emergency Recovery -->
        <button
          @click="showEmergencyRecovery"
          class="alternative-btn emergency"
        >
          <Icon name="exclamation-triangle" class="w-4 h-4" />
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
          <Icon name="help-circle" class="w-4 h-4" />
          Need help with 2FA?
        </summary>
        <div class="help-content">
          <div class="help-item">
            <h4>Lost your authenticator device?</h4>
            <p>Use one of your recovery codes or contact your administrator for emergency access.</p>
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
      <button @click="logout" class="logout-btn">
        <Icon name="log-out" class="w-4 h-4" />
        Sign out and try different account
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useToast } from '@/composables/useToast'
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
  if (!verificationCode.value) return false
  
  switch (verificationMethod.value) {
    case 'totp':
    case 'sms':
      return verificationCode.value.length === 6
    case 'recovery':
      return verificationCode.value.length === 8
    default:
      return false
  }
})

// Methods
const handleSubmit = async () => {
  if (!isValidCode.value || loading.value) return

  loading.value = true
  errors.code = ''
  hideAlert()

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
    const message = error.message || 'Verification failed. Please try again.'
    errors.code = message
    showAlert('error', message, 'x-circle')
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
  if (smsLoading.value) return

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
      if (alert.show) hideAlert()
    }, 5000)
  }
}

// Watch for alert changes
watch(() => alert.show, (show) => {
  if (show) autoHideAlert()
})

// Lifecycle
onMounted(() => {
  // Focus on the input when component mounts
  setTimeout(() => {
    const input = document.querySelector('input[type="text"]')
    if (input) input.focus()
  }, 100)
})
</script>

<style scoped>
.two-factor-prompt {
  @apply max-w-md mx-auto p-6 space-y-6;
}

.header-section {
  @apply text-center space-y-3;
}

.icon-container {
  @apply flex justify-center;
}

.security-icon {
  @apply w-12 h-12 text-blue-600;
}

.title {
  @apply text-2xl font-bold text-gray-900;
}

.subtitle {
  @apply text-gray-600;
}

.alert {
  @apply flex items-center space-x-2 p-3 rounded-lg text-sm font-medium;
}

.alert.info {
  @apply bg-blue-50 text-blue-800 border border-blue-200;
}

.alert.success {
  @apply bg-green-50 text-green-800 border border-green-200;
}

.alert.warning {
  @apply bg-yellow-50 text-yellow-800 border border-yellow-200;
}

.alert.error {
  @apply bg-red-50 text-red-800 border border-red-200;
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
  @apply flex items-start space-x-3 cursor-pointer;
}

.checkbox-input {
  @apply sr-only;
}

.checkbox-custom {
  @apply w-4 h-4 border-2 border-gray-300 rounded flex-shrink-0 mt-0.5
         transition-all duration-200;
}

.checkbox-input:checked + .checkbox-custom {
  @apply bg-blue-600 border-blue-600;
}

.checkbox-input:checked + .checkbox-custom::after {
  content: '✓';
  @apply text-white text-xs flex items-center justify-center;
}

.checkbox-label {
  @apply text-sm text-gray-700 flex items-center space-x-1;
}

.tooltip-trigger {
  @apply relative inline-block;
}

.tooltip {
  @apply absolute bottom-full left-1/2 transform -translate-x-1/2 mb-1
         bg-gray-900 text-white text-xs rounded py-1 px-2 whitespace-nowrap
         opacity-0 invisible transition-all duration-200;
}

.tooltip-trigger:hover .tooltip {
  @apply opacity-100 visible;
}

.actions {
  @apply pt-2;
}

.btn-primary {
  @apply w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 
         rounded-lg transition-colors duration-200 inline-flex items-center 
         justify-center space-x-2 disabled:opacity-50 disabled:cursor-not-allowed;
}

.spinner {
  @apply animate-spin rounded-full h-4 w-4 border-b-2 border-white;
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
  @apply grid grid-cols-1 sm:grid-cols-2 gap-2;
}

.alternative-btn {
  @apply px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300
         rounded-lg hover:bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:border-blue-500
         transition-all duration-200 inline-flex items-center justify-center space-x-2
         disabled:opacity-50 disabled:cursor-not-allowed;
}

.alternative-btn.emergency {
  @apply text-red-700 border-red-300 hover:bg-red-50;
}

.spinner-sm {
  @apply animate-spin rounded-full h-3 w-3 border-b-2 border-current;
}

.help-section {
  @apply border-t border-gray-200 pt-4;
}

.help-details {
  @apply space-y-2;
}

.help-summary {
  @apply flex items-center space-x-2 cursor-pointer text-sm font-medium text-gray-700
         hover:text-gray-900 transition-colors duration-200;
}

.help-content {
  @apply space-y-3 mt-3 text-sm text-gray-600;
}

.help-item h4 {
  @apply font-medium text-gray-900 mb-1;
}

.logout-section {
  @apply text-center border-t border-gray-200 pt-4;
}

.logout-btn {
  @apply text-sm text-gray-500 hover:text-gray-700 inline-flex items-center space-x-2
         transition-colors duration-200;
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