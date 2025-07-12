<template>
  <div class="setup-modal">
    <div class="modal-backdrop" @click="handleBackdropClick">
      <div class="modal-content" @click.stop>
        <!-- Header -->
        <div class="modal-header">
          <h2 class="modal-title">Set Up Two-Factor Authentication</h2>
          <p class="modal-subtitle">
            Follow the steps below to secure your account with 2FA
          </p>
          <button @click="closeModal" class="close-button">
            <Icon name="x" class="w-5 h-5" />
          </button>
        </div>

        <!-- Setup Steps -->
        <div class="setup-steps">
          <!-- Step 1: Install App -->
          <div class="step" :class="{ 'active': currentStep >= 1, 'completed': currentStep > 1 }">
            <div class="step-header">
              <div class="step-number">
                <Icon v-if="currentStep > 1" name="check" class="w-4 h-4" />
                <span v-else>1</span>
              </div>
              <h3 class="step-title">Install Authenticator App</h3>
            </div>
            
            <div v-if="currentStep === 1" class="step-content">
              <p class="step-description">
                Choose and install an authenticator app on your phone. We recommend:
              </p>
              
              <div class="app-recommendations">
                <div class="app-option">
                  <div class="app-icon google">
                    <Icon name="smartphone" class="w-6 h-6" />
                  </div>
                  <div class="app-info">
                    <h4>Google Authenticator</h4>
                    <p>Free app by Google</p>
                  </div>
                </div>
                
                <div class="app-option">
                  <div class="app-icon authy">
                    <Icon name="shield" class="w-6 h-6" />
                  </div>
                  <div class="app-info">
                    <h4>Authy</h4>
                    <p>Feature-rich with backup</p>
                  </div>
                </div>
                
                <div class="app-option">
                  <div class="app-icon microsoft">
                    <Icon name="key" class="w-6 h-6" />
                  </div>
                  <div class="app-info">
                    <h4>Microsoft Authenticator</h4>
                    <p>Free app by Microsoft</p>
                  </div>
                </div>
              </div>

              <div class="step-actions">
                <button @click="nextStep" class="btn-primary">
                  <Icon name="arrow-right" class="w-4 h-4" />
                  App Installed, Continue
                </button>
              </div>
            </div>
          </div>

          <!-- Step 2: Scan QR Code -->
          <div class="step" :class="{ 'active': currentStep >= 2, 'completed': currentStep > 2 }">
            <div class="step-header">
              <div class="step-number">
                <Icon v-if="currentStep > 2" name="check" class="w-4 h-4" />
                <span v-else>2</span>
              </div>
              <h3 class="step-title">Scan QR Code</h3>
            </div>
            
            <div v-if="currentStep === 2" class="step-content">
              <p class="step-description">
                Open your authenticator app and scan this QR code:
              </p>

              <!-- Loading State -->
              <div v-if="loadingQR" class="qr-loading">
                <div class="spinner"></div>
                <p>Generating QR code...</p>
              </div>

              <!-- QR Code Display -->
              <div v-else-if="qrCodeData" class="qr-section">
                <div class="qr-container">
                  <div class="qr-code" v-html="qrCodeData"></div>
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
                        <button @click="copySecret" class="copy-btn">
                          <Icon name="copy" class="w-4 h-4" />
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
                <button @click="previousStep" class="btn-secondary">
                  <Icon name="arrow-left" class="w-4 h-4" />
                  Back
                </button>
                <button @click="nextStep" class="btn-primary" :disabled="!qrCodeData">
                  <Icon name="arrow-right" class="w-4 h-4" />
                  Code Added, Continue
                </button>
              </div>
            </div>
          </div>

          <!-- Step 3: Verify Setup -->
          <div class="step" :class="{ 'active': currentStep >= 3, 'completed': setupComplete }">
            <div class="step-header">
              <div class="step-number">
                <Icon v-if="setupComplete" name="check" class="w-4 h-4" />
                <span v-else>3</span>
              </div>
              <h3 class="step-title">Verify Setup</h3>
            </div>
            
            <div v-if="currentStep === 3" class="step-content">
              <p class="step-description">
                Enter the 6-digit code from your authenticator app to verify the setup:
              </p>

              <form @submit.prevent="verifySetup" class="verification-form">
                <div class="code-input-section">
                  <label for="verification-code" class="code-label">
                    Verification Code
                  </label>
                  <input
                    id="verification-code"
                    v-model="verificationCode"
                    type="text"
                    maxlength="6"
                    pattern="[0-9]{6}"
                    placeholder="123456"
                    class="code-input"
                    :class="{ 'error': errors.code }"
                    :disabled="loading"
                    @input="handleCodeInput"
                  />
                  <div v-if="errors.code" class="error-message">
                    <Icon name="x-circle" class="w-4 h-4" />
                    {{ errors.code }}
                  </div>
                  <p class="code-hint">
                    Enter the 6-digit code shown in your authenticator app
                  </p>
                </div>

                <div class="step-actions">
                  <button @click="previousStep" type="button" class="btn-secondary" :disabled="loading">
                    <Icon name="arrow-left" class="w-4 h-4" />
                    Back
                  </button>
                  <button type="submit" class="btn-primary" :disabled="!isValidCode || loading">
                    <div v-if="loading" class="spinner" />
                    <Icon v-else name="check" class="w-4 h-4" />
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
            <Icon name="check-circle" class="w-12 h-12 text-green-600" />
            <h3 class="success-title">Two-Factor Authentication Enabled!</h3>
            <p class="success-description">
              Your account is now secured with two-factor authentication.
            </p>
          </div>

          <div class="recovery-notice">
            <Icon name="key" class="w-5 h-5 text-amber-600" />
            <div>
              <h4 class="recovery-title">Important: Save Your Recovery Codes</h4>
              <p class="recovery-description">
                You'll be shown recovery codes that can be used if you lose access to your authenticator app.
                Save them in a secure location.
              </p>
            </div>
          </div>

          <div class="success-actions">
            <button @click="completeSetup" class="btn-primary">
              <Icon name="arrow-right" class="w-4 h-4" />
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

// Computed
const isValidCode = computed(() => {
  return verificationCode.value.length === 6 && /^\d{6}$/.test(verificationCode.value)
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
  
  try {
    const response = await twoFactorService.initializeSetup()
    qrCodeData.value = response.data.qr_code_url
    secretKey.value = response.data.secret_key
    appName.value = response.data.company_name
  } catch (error) {
    toast.error('Failed to generate QR code')
    console.error('QR Code generation error:', error)
  } finally {
    loadingQR.value = false
  }
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
  if (!isValidCode.value || loading.value) return

  loading.value = true
  errors.code = ''

  try {
    const response = await twoFactorService.enable(verificationCode.value)
    
    if (response.success) {
      setupComplete.value = true
      toast.success('Two-factor authentication enabled successfully!')
    }
  } catch (error) {
    errors.code = error.message || 'Invalid verification code. Please try again.'
  } finally {
    loading.value = false
  }
}

const copySecret = async () => {
  try {
    await navigator.clipboard.writeText(secretKey.value)
    toast.success('Secret key copied to clipboard')
  } catch (error) {
    // Fallback for older browsers
    const textArea = document.createElement('textarea')
    textArea.value = secretKey.value
    document.body.appendChild(textArea)
    textArea.select()
    document.execCommand('copy')
    document.body.removeChild(textArea)
    toast.success('Secret key copied to clipboard')
  }
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
    if (modal) modal.focus()
  }, 100)
})
</script>

<style scoped>
.setup-modal {
  @apply fixed inset-0 z-50 overflow-y-auto;
}

.modal-backdrop {
  @apply fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4;
  animation: fadeIn 0.3s ease-out;
}

.modal-content {
  @apply bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-screen overflow-y-auto;
  animation: slideIn 0.3s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
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
  @apply relative p-6 border-b border-gray-200;
}

.modal-title {
  @apply text-2xl font-bold text-gray-900;
}

.modal-subtitle {
  @apply text-gray-600 mt-2;
}

.close-button {
  @apply absolute top-4 right-4 text-gray-400 hover:text-gray-600 
         transition-colors duration-200;
}

.setup-steps {
  @apply p-6 space-y-6;
}

.step {
  @apply border border-gray-200 rounded-lg overflow-hidden;
}

.step.active {
  @apply border-blue-500 ring-1 ring-blue-500;
}

.step.completed {
  @apply border-green-500 bg-green-50;
}

.step-header {
  @apply flex items-center space-x-4 p-4 bg-gray-50;
}

.step.active .step-header {
  @apply bg-blue-50;
}

.step.completed .step-header {
  @apply bg-green-100;
}

.step-number {
  @apply w-8 h-8 rounded-full bg-gray-300 text-gray-600 font-medium 
         flex items-center justify-center;
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
  @apply p-4 space-y-4;
}

.step-description {
  @apply text-gray-600;
}

.app-recommendations {
  @apply grid grid-cols-1 sm:grid-cols-3 gap-4;
}

.app-option {
  @apply flex items-center space-x-3 p-3 border border-gray-200 rounded-lg 
         hover:border-blue-300 transition-colors duration-200;
}

.app-icon {
  @apply w-10 h-10 rounded-lg flex items-center justify-center;
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
  @apply text-center py-8 space-y-4;
}

.qr-section {
  @apply space-y-6;
}

.qr-container {
  @apply flex justify-center p-6 bg-white border-2 border-dashed border-gray-300 rounded-lg;
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
  @apply flex items-center space-x-2 p-3 bg-gray-50 border rounded-lg;
}

.secret-key code {
  @apply flex-1 font-mono text-sm;
}

.copy-btn {
  @apply text-gray-400 hover:text-gray-600 transition-colors duration-200;
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
  @apply w-full px-4 py-3 text-lg font-mono text-center border border-gray-300 
         rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 
         transition-all duration-200;
}

.code-input.error {
  @apply border-red-500 ring-red-500;
}

.error-message {
  @apply text-sm text-red-600 flex items-center space-x-1;
}

.code-hint {
  @apply text-xs text-gray-500 text-center;
}

.step-actions {
  @apply flex space-x-3 pt-4 border-t border-gray-200;
}

.btn-primary {
  @apply bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 
         rounded-lg transition-colors duration-200 inline-flex items-center 
         space-x-2 disabled:opacity-50 disabled:cursor-not-allowed;
}

.btn-secondary {
  @apply bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 
         font-medium py-2 px-4 rounded-lg transition-colors duration-200 
         inline-flex items-center space-x-2 disabled:opacity-50 disabled:cursor-not-allowed;
}

.spinner {
  @apply animate-spin rounded-full h-4 w-4 border-b-2 border-white;
}

.success-section {
  @apply p-6 space-y-6;
}

.success-content {
  @apply text-center space-y-4;
}

.success-title {
  @apply text-xl font-bold text-gray-900;
}

.success-description {
  @apply text-gray-600;
}

.recovery-notice {
  @apply flex items-start space-x-3 p-4 bg-amber-50 border border-amber-200 rounded-lg;
}

.recovery-title {
  @apply font-medium text-amber-900;
}

.recovery-description {
  @apply text-sm text-amber-800 mt-1;
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