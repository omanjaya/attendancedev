<template>
  <div class="setup-modal">
    <div class="modal-backdrop" @click="handleBackdropClick">
      <div class="modal-content" @click.stop>
        <!-- Header -->
        <div class="modal-header">
          <h2 class="modal-title">
            Set Up Two-Factor Authentication
          </h2>
          <p class="modal-subtitle">
            Follow the steps below to secure your account with 2FA
          </p>
          <button class="close-button" @click="closeModal">
            <Icon name="x" class="h-5 w-5" />
          </button>
        </div>

        <!-- Setup Steps -->
        <div class="setup-steps">
          <!-- Step 1: Install App -->
          <div class="step" :class="{ active: currentStep >= 1, completed: currentStep > 1 }">
            <div class="step-header">
              <div class="step-number">
                <Icon v-if="currentStep > 1" name="check" class="h-4 w-4" />
                <span v-else>1</span>
              </div>
              <h3 class="step-title">
                Install Authenticator App
              </h3>
            </div>

            <div v-if="currentStep === 1" class="step-content">
              <p class="step-description">
                Choose and install an authenticator app on your phone. We recommend:
              </p>

              <div class="app-recommendations">
                <div class="app-option">
                  <div class="app-icon google">
                    <Icon name="smartphone" class="h-6 w-6" />
                  </div>
                  <div class="app-info">
                    <h4>Google Authenticator</h4>
                    <p>Free app by Google</p>
                  </div>
                </div>

                <div class="app-option">
                  <div class="app-icon authy">
                    <Icon name="shield" class="h-6 w-6" />
                  </div>
                  <div class="app-info">
                    <h4>Authy</h4>
                    <p>Feature-rich with backup</p>
                  </div>
                </div>

                <div class="app-option">
                  <div class="app-icon microsoft">
                    <Icon name="key" class="h-6 w-6" />
                  </div>
                  <div class="app-info">
                    <h4>Microsoft Authenticator</h4>
                    <p>Free app by Microsoft</p>
                  </div>
                </div>
              </div>

              <div class="step-actions">
                <button class="btn-primary" @click="nextStep">
                  <Icon name="arrow-right" class="h-4 w-4" />
                  App Installed, Continue
                </button>
              </div>
            </div>
          </div>

          <!-- Step 2: Scan QR Code -->
          <div class="step" :class="{ active: currentStep >= 2, completed: currentStep > 2 }">
            <div class="step-header">
              <div class="step-number">
                <Icon v-if="currentStep > 2" name="check" class="h-4 w-4" />
                <span v-else>2</span>
              </div>
              <h3 class="step-title">
                Scan QR Code
              </h3>
            </div>

            <div v-if="currentStep === 2" class="step-content">
              <p class="step-description">
                Open your authenticator app and scan this QR code:
              </p>

              <!-- Loading State -->
              <div v-if="loadingQR" class="qr-loading">
                <div class="spinner" />
                <p>Generating QR code...</p>
              </div>

              <!-- QR Code Display -->
              <div v-else-if="qrCodeData" class="qr-section">
                <div class="qr-container">
                  <div class="qr-code" v-html="sanitizedQrCode" />
                </div>

                <div class="manual-entry">
                  <details class="manual-details">
                    <summary class="manual-summary">
                      Can't scan? Enter manually
                    </summary>
                    <div class="manual-content">
                      <p class="manual-instructions">
                        Enter this secret key in your authenticator app:
                      </p>
                      <div class="secret-key">
                        <code>{{ secretKey }}</code>
                        <button class="copy-btn" @click="copySecret">
                          <Icon name="copy" class="h-4 w-4" />
                        </button>
                      </div>
                      <div class="manual-settings">
                        <p><strong>Account:</strong> {{ user.email }}</p>
                        <p><strong>Service:</strong> {{ appName }}</p>
                      </div>
                    </div>
                  </details>
                </div>
              </div>

              <div class="step-actions">
                <button class="btn-secondary" @click="previousStep">
                  <Icon name="arrow-left" class="h-4 w-4" />
                  Back
                </button>
                <button class="btn-primary" :disabled="!qrCodeData" @click="nextStep">
                  <Icon name="arrow-right" class="h-4 w-4" />
                  Code Added, Continue
                </button>
              </div>
            </div>
          </div>

          <!-- Step 3: Verify Setup -->
          <div class="step" :class="{ active: currentStep >= 3, completed: setupComplete }">
            <div class="step-header">
              <div class="step-number">
                <Icon v-if="setupComplete" name="check" class="h-4 w-4" />
                <span v-else>3</span>
              </div>
              <h3 class="step-title">
                Verify Setup
              </h3>
            </div>

            <div v-if="currentStep === 3" class="step-content">
              <p class="step-description">
                Enter the 6-digit code from your authenticator app to verify the setup:
              </p>

              <form class="verification-form" @submit.prevent="verifySetup">
                <div class="code-input-section">
                  <label for="verification-code" class="code-label"> Verification Code </label>
                  <input
                    id="verification-code"
                    v-model="verificationCode"
                    type="text"
                    maxlength="6"
                    pattern="[0-9]{6}"
                    placeholder="123456"
                    class="code-input"
                    :class="{ error: errors.code }"
                    :disabled="loading"
                    @input="handleCodeInput"
                  >
                  <div v-if="errors.code" class="error-message">
                    <Icon name="x-circle" class="h-4 w-4" />
                    {{ errors.code }}
                  </div>
                  <p class="code-hint">
                    Enter the 6-digit code shown in your authenticator app
                  </p>
                </div>

                <div class="step-actions">
                  <button
                    type="button"
                    class="btn-secondary"
                    :disabled="loading"
                    @click="previousStep"
                  >
                    <Icon name="arrow-left" class="h-4 w-4" />
                    Back
                  </button>
                  <button type="submit" class="btn-primary" :disabled="!isValidCode || loading">
                    <div v-if="loading" class="spinner" />
                    <Icon v-else name="check" class="h-4 w-4" />
                    {{ loading ? 'Verifying...' : 'Enable 2FA' }}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- Success State -->
        <div v-if="setupComplete" class="success-section">
          <div class="success-content">
            <Icon name="check-circle" class="h-12 w-12 text-green-600" />
            <h3 class="success-title">
              Two-Factor Authentication Enabled!
            </h3>
            <p class="success-description">
              Your account is now secured with two-factor authentication.
            </p>
          </div>

          <div class="recovery-notice">
            <Icon name="key" class="h-5 w-5 text-amber-600" />
            <div>
              <h4 class="recovery-title">
                Important: Save Your Recovery Codes
              </h4>
              <p class="recovery-description">
                You'll be shown recovery codes that can be used if you lose access to your
                authenticator app. Save them in a secure location.
              </p>
            </div>
          </div>

          <div class="success-actions">
            <button class="btn-primary" @click="completeSetup">
              <Icon name="arrow-right" class="h-4 w-4" />
              View Recovery Codes
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useToast } from '@/composables/useToast'
import { twoFactorService } from '@/services/twoFactor'
import { HTMLSanitizer } from '@/utils/xssAudit'
import { useErrorTrackingForAuth } from '@/composables/useErrorTracking'

// Props
const props = defineProps({
  user: {
    type: Object,
    required: true
  }
})

// Emits
const emit = defineEmits(['close', 'complete'])

// Reactive data
const currentStep = ref(1)
const loadingQR = ref(false)
const loading = ref(false)
const setupComplete = ref(false)
const qrCodeData = ref('')
const secretKey = ref('')
const verificationCode = ref('')
const appName = ref('Attendance System')

const errors = reactive({
  code: ''
})

// Composables
const { toast } = useToast()
const errorTracking = useErrorTrackingForAuth()

// Computed
const isValidCode = computed(() => {
  return verificationCode.value.length === 6 && /^\d{6}$/.test(verificationCode.value)
})

const sanitizedQrCode = computed(() => {
  return qrCodeData.value ? HTMLSanitizer.sanitize(qrCodeData.value) : ''
})

// Methods
const nextStep = async () => {
  if (currentStep.value === 1) {
    currentStep.value = 2
    await loadQRCode()
  } else if (currentStep.value === 2) {
    currentStep.value = 3
  }
}

const previousStep = () => {
  if (currentStep.value > 1) {
    currentStep.value--
  }
}

const loadQRCode = async () => {
  loadingQR.value = true

  return errorTracking.trackAsyncOperation('load_qr_code', async () => {
    errorTracking.addBreadcrumb('Loading QR code for 2FA setup', 'auth')

    const response = await twoFactorService.initializeSetup()
    qrCodeData.value = response.data.qr_code_url
    secretKey.value = response.data.secret_key
    appName.value = response.data.company_name

    errorTracking.addBreadcrumb('QR code loaded successfully', 'auth')
  }).catch((error: Error) => {
    errorTracking.captureError(error, {
      action: 'qr_code_generation_failed',
      metadata: {
        currentStep: currentStep.value,
        userId: props.user?.id
      }
    })
    toast.error('Failed to generate QR code')
    console.error('QR Code generation error:', error)
    throw error
  }).finally(() => {
    loadingQR.value = false
  })
}

const handleCodeInput = () => {
  // Clear errors when user types
  errors.code = ''

  // Auto-submit if code is complete
  if (isValidCode.value) {
    setTimeout(() => verifySetup(), 500)
  }
}

const verifySetup = async () => {
  if (!isValidCode.value || loading.value) {return}

  loading.value = true
  errors.code = ''

  return errorTracking.trackAsyncOperation('verify_2fa_setup', async () => {
    errorTracking.addBreadcrumb('Verifying 2FA setup code', 'auth', {
      codeLength: verificationCode.value.length
    })

    const response = await twoFactorService.enable(verificationCode.value)

    if (response.success) {
      setupComplete.value = true

      errorTracking.addBreadcrumb('2FA setup completed successfully', 'auth')
      errorTracking.captureMessage('Two-factor authentication enabled', 'info', {
        action: '2fa_enabled',
        metadata: {
          userId: props.user?.id,
          userEmail: props.user?.email
        }
      })

      toast.success('Two-factor authentication enabled successfully!')
    } else {
      throw new Error(response.message || 'Setup verification failed')
    }
  }).catch((error: Error) => {
    errorTracking.captureError(error, {
      action: '2fa_verification_failed',
      metadata: {
        currentStep: currentStep.value,
        codeLength: verificationCode.value.length,
        userId: props.user?.id,
        errorType: error.message.includes('Invalid') ? 'invalid_code' : 'unknown'
      }
    })

    errors.code = error.message || 'Invalid verification code. Please try again.'
    console.error('2FA verification error:', error)
  }).finally(() => {
    loading.value = false
  })
}

const copySecret = async () => {
  return errorTracking.withErrorBoundary(() => {
    errorTracking.addBreadcrumb('Copying secret key to clipboard', 'user_action')

    return navigator.clipboard.writeText(secretKey.value).then(() => {
      toast.success('Secret key copied to clipboard')
      errorTracking.addBreadcrumb('Secret key copied successfully', 'user_action')
    }).catch((clipboardError) => {
      // Fallback for older browsers
      errorTracking.addBreadcrumb('Using fallback copy method', 'user_action')

      const textArea = document.createElement('textarea')
      textArea.value = secretKey.value
      textArea.style.position = 'fixed'
      textArea.style.opacity = '0'
      document.body.appendChild(textArea)
      textArea.select()
      document.execCommand('copy')
      document.body.removeChild(textArea)

      toast.success('Secret key copied to clipboard')
      errorTracking.addBreadcrumb('Fallback copy completed', 'user_action')
    })
  }, {
    action: 'copy_secret_failed',
    metadata: {
      hasClipboardAPI: !!navigator.clipboard,
      secretKeyLength: secretKey.value?.length || 0
    }
  })
}

const completeSetup = () => {
  emit('complete', {
    recovery_codes: [], // Will be provided by the backend
    success: true
  })
}

const closeModal = () => {
  if (!loading.value) {
    emit('close')
  }
}

const handleBackdropClick = () => {
  if (!loading.value) {
    closeModal()
  }
}

// Lifecycle
onMounted(() => {
  // Set focus to modal for accessibility
  setTimeout(() => {
    const modal = document.querySelector('.modal-content')
    if (modal) {modal.focus()}
  }, 100)
})
</script>

<style scoped>
.setup-modal {
  @apply fixed inset-0 z-50 overflow-y-auto;
}

.modal-backdrop {
  @apply fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 p-4;
  animation: fadeIn 0.3s ease-out;
}

.modal-content {
  @apply max-h-screen w-full max-w-2xl overflow-y-auto rounded-lg bg-white shadow-xl;
  animation: slideIn 0.3s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(20px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.modal-header {
  @apply relative border-b border-gray-200 p-6;
}

.modal-title {
  @apply text-2xl font-bold text-gray-900;
}

.modal-subtitle {
  @apply mt-2 text-gray-600;
}

.close-button {
  @apply absolute right-4 top-4 text-gray-400 transition-colors duration-200 hover:text-gray-600;
}

.setup-steps {
  @apply space-y-6 p-6;
}

.step {
  @apply overflow-hidden rounded-lg border border-gray-200;
}

.step.active {
  @apply border-blue-500 ring-1 ring-blue-500;
}

.step.completed {
  @apply border-green-500 bg-green-50;
}

.step-header {
  @apply flex items-center space-x-4 bg-gray-50 p-4;
}

.step.active .step-header {
  @apply bg-blue-50;
}

.step.completed .step-header {
  @apply bg-green-100;
}

.step-number {
  @apply flex h-8 w-8 items-center justify-center rounded-full bg-gray-300 font-medium text-gray-600;
}

.step.active .step-number {
  @apply bg-blue-600 text-white;
}

.step.completed .step-number {
  @apply bg-green-600 text-white;
}

.step-title {
  @apply text-lg font-semibold text-gray-900;
}

.step-content {
  @apply space-y-4 p-4;
}

.step-description {
  @apply text-gray-600;
}

.app-recommendations {
  @apply grid grid-cols-1 gap-4 sm:grid-cols-3;
}

.app-option {
  @apply flex items-center space-x-3 rounded-lg border border-gray-200 p-3 transition-colors duration-200 hover:border-blue-300;
}

.app-icon {
  @apply flex h-10 w-10 items-center justify-center rounded-lg;
}

.app-icon.google {
  @apply bg-blue-100 text-blue-600;
}

.app-icon.authy {
  @apply bg-red-100 text-red-600;
}

.app-icon.microsoft {
  @apply bg-green-100 text-green-600;
}

.app-info h4 {
  @apply font-medium text-gray-900;
}

.app-info p {
  @apply text-sm text-gray-600;
}

.qr-loading {
  @apply space-y-4 py-8 text-center;
}

.qr-section {
  @apply space-y-6;
}

.qr-container {
  @apply flex justify-center rounded-lg border-2 border-dashed border-gray-300 bg-white p-6;
}

.qr-code {
  @apply max-w-xs;
}

.manual-entry {
  @apply border-t border-gray-200 pt-4;
}

.manual-details {
  @apply space-y-2;
}

.manual-summary {
  @apply cursor-pointer text-sm font-medium text-blue-600 hover:text-blue-700;
}

.manual-content {
  @apply mt-3 space-y-3;
}

.manual-instructions {
  @apply text-sm text-gray-600;
}

.secret-key {
  @apply flex items-center space-x-2 rounded-lg border bg-gray-50 p-3;
}

.secret-key code {
  @apply flex-1 font-mono text-sm;
}

.copy-btn {
  @apply text-gray-400 transition-colors duration-200 hover:text-gray-600;
}

.manual-settings {
  @apply space-y-1 text-sm text-gray-600;
}

.verification-form {
  @apply space-y-4;
}

.code-input-section {
  @apply space-y-2;
}

.code-label {
  @apply block text-sm font-medium text-gray-700;
}

.code-input {
  @apply w-full rounded-lg border border-gray-300 px-4 py-3 text-center font-mono text-lg transition-all duration-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-500;
}

.code-input.error {
  @apply border-red-500 ring-red-500;
}

.error-message {
  @apply flex items-center space-x-1 text-sm text-red-600;
}

.code-hint {
  @apply text-center text-xs text-gray-500;
}

.step-actions {
  @apply flex space-x-3 border-t border-gray-200 pt-4;
}

.btn-primary {
  @apply inline-flex items-center space-x-2 rounded-lg bg-blue-600 px-4 py-2 font-medium text-white transition-colors duration-200 hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50;
}

.btn-secondary {
  @apply inline-flex items-center space-x-2 rounded-lg border border-gray-300 bg-white px-4 py-2 font-medium text-gray-700 transition-colors duration-200 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50;
}

.spinner {
  @apply h-4 w-4 animate-spin rounded-full border-b-2 border-white;
}

.success-section {
  @apply space-y-6 p-6;
}

.success-content {
  @apply space-y-4 text-center;
}

.success-title {
  @apply text-xl font-bold text-gray-900;
}

.success-description {
  @apply text-gray-600;
}

.recovery-notice {
  @apply flex items-start space-x-3 rounded-lg border border-amber-200 bg-amber-50 p-4;
}

.recovery-title {
  @apply font-medium text-amber-900;
}

.recovery-description {
  @apply mt-1 text-sm text-amber-800;
}

.success-actions {
  @apply flex justify-center;
}

/* Mobile optimizations */
@media (max-width: 640px) {
  .modal-content {
    @apply m-2;
  }

  .app-recommendations {
    @apply grid-cols-1;
  }

  .step-actions {
    @apply flex-col space-x-0 space-y-2;
  }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .modal-backdrop,
  .modal-content {
    animation: none;
  }

  .spinner {
    @apply animate-none;
  }
}
</style>
