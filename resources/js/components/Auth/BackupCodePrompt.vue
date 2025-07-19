<template>
  <div class="backup-code-prompt">
    <!-- Header -->
    <div class="header-section">
      <div class="icon-container">
        <Icon name="key" class="backup-icon" />
      </div>
      <h2 class="title">
        Use Recovery Code
      </h2>
      <p class="subtitle">
        Enter one of your 8-character backup codes to access your account.
      </p>
    </div>

    <!-- Remaining Codes Info -->
    <div v-if="remainingCodes !== null" class="codes-info">
      <div class="info-badge" :class="getInfoBadgeClass()">
        <Icon :name="getInfoIcon()" class="h-4 w-4" />
        <span>{{ remainingCodes }} recovery codes remaining</span>
      </div>
      <p v-if="remainingCodes <= 2" class="warning-text">
        You're running low on recovery codes. After logging in, consider regenerating new codes from
        your security settings.
      </p>
    </div>

    <!-- Main Form -->
    <form class="recovery-form" @submit.prevent="handleSubmit">
      <!-- Recovery Code Input -->
      <div class="input-section">
        <label for="recovery-code" class="input-label">
          Recovery Code
          <span class="required">*</span>
        </label>

        <div class="input-wrapper">
          <input
            id="recovery-code"
            v-model="recoveryCode"
            type="text"
            maxlength="8"
            pattern="[A-Za-z0-9]{8}"
            placeholder="XXXXXXXX"
            class="recovery-input"
            :class="{ error: errors.code }"
            :disabled="loading"
            @input="handleInput"
            @paste="handlePaste"
          >

          <!-- Format Helper -->
          <div class="format-helper">
            <div
              v-for="(char, index) in displayCode"
              :key="index"
              class="char-box"
              :class="{ filled: char !== '_', active: index === cursorPosition }"
            >
              {{ char }}
            </div>
          </div>
        </div>

        <!-- Error Message -->
        <div v-if="errors.code" class="error-message">
          <Icon name="x-circle" class="h-4 w-4" />
          {{ errors.code }}
        </div>

        <!-- Helper Text -->
        <div class="helper-text">
          Recovery codes are 8 characters long and contain only letters and numbers.
        </div>
      </div>

      <!-- Actions -->
      <div class="actions">
        <button type="submit" class="btn-primary" :disabled="!isValidCode || loading">
          <div v-if="loading" class="spinner" />
          <Icon v-else name="unlock" class="h-4 w-4" />
          {{ loading ? 'Verifying...' : 'Verify Recovery Code' }}
        </button>
      </div>
    </form>

    <!-- Alternative Actions -->
    <div class="alternatives-section">
      <div class="divider">
        <span class="divider-text">Or try another method</span>
      </div>

      <div class="alternative-actions">
        <button class="alternative-btn" @click="switchToAuthenticator">
          <Icon name="smartphone" class="h-4 w-4" />
          Use Authenticator App
        </button>

        <button
          v-if="smsEnabled"
          class="alternative-btn"
          :disabled="smsLoading"
          @click="requestSMS"
        >
          <div v-if="smsLoading" class="spinner-sm" />
          <Icon v-else name="phone" class="h-4 w-4" />
          Send SMS Code
        </button>
      </div>
    </div>

    <!-- Help Section -->
    <div class="help-section">
      <details class="help-details">
        <summary class="help-summary">
          <Icon name="help-circle" class="h-4 w-4" />
          About recovery codes
        </summary>
        <div class="help-content">
          <div class="help-item">
            <h4>What are recovery codes?</h4>
            <p>
              Recovery codes are backup codes that allow you to access your account when you don't
              have access to your authenticator device.
            </p>
          </div>
          <div class="help-item">
            <h4>How do I use them?</h4>
            <p>
              Enter any unused 8-character recovery code exactly as it appears in your saved list.
              Each code can only be used once.
            </p>
          </div>
          <div class="help-item">
            <h4>Where are my codes?</h4>
            <p>
              You should have saved them when you first set up 2FA. Check your password manager,
              secure notes, or printed copy.
            </p>
          </div>
          <div class="help-item">
            <h4>Lost all your codes?</h4>
            <p>Contact your administrator for emergency account recovery assistance.</p>
          </div>
        </div>
      </details>
    </div>

    <!-- Emergency Recovery -->
    <div class="emergency-section">
      <button class="emergency-btn" @click="showEmergencyRecovery">
        <Icon name="exclamation-triangle" class="h-4 w-4" />
        Lost access? Request emergency recovery
      </button>
    </div>

    <!-- Emergency Recovery Modal -->
    <EmergencyRecoveryModal
      v-if="showEmergencyModal"
      @close="showEmergencyModal = false"
      @submit="handleEmergencyRecovery"
    />
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useToast } from '@/composables/useToast'
import { twoFactorService } from '@/services/twoFactor'
import EmergencyRecoveryModal from '@/components/Auth/EmergencyRecoveryModal.vue'

// Props
const props = defineProps({
  remainingCodes: {
    type: Number,
    default: null,
  },
  smsEnabled: {
    type: Boolean,
    default: false,
  },
})

// Emits
const emit = defineEmits(['success', 'switchToAuthenticator', 'requestSMS', 'error'])

// Reactive data
const recoveryCode = ref('')
const loading = ref(false)
const smsLoading = ref(false)
const showEmergencyModal = ref(false)
const cursorPosition = ref(0)

const errors = reactive({
  code: '',
})

// Composables
const { toast } = useToast()

// Computed
const isValidCode = computed(() => {
  return recoveryCode.value.length === 8 && /^[A-Za-z0-9]{8}$/.test(recoveryCode.value)
})

const displayCode = computed(() => {
  const code = recoveryCode.value.toUpperCase().padEnd(8, '_')
  return Array.from(code)
})

const getInfoBadgeClass = () => {
  if (props.remainingCodes === null) {return 'info'}
  if (props.remainingCodes <= 1) {return 'danger'}
  if (props.remainingCodes <= 2) {return 'warning'}
  return 'success'
}

const getInfoIcon = () => {
  if (props.remainingCodes === null) {return 'info'}
  if (props.remainingCodes <= 1) {return 'exclamation-triangle'}
  if (props.remainingCodes <= 2) {return 'exclamation'}
  return 'check-circle'
}

// Methods
const handleInput = (event) => {
  let value = event.target.value.toUpperCase()

  // Only allow alphanumeric characters
  value = value.replace(/[^A-Z0-9]/g, '')

  // Limit to 8 characters
  value = value.slice(0, 8)

  recoveryCode.value = value
  cursorPosition.value = value.length

  // Clear previous errors
  errors.code = ''
}

const handlePaste = (event) => {
  event.preventDefault()
  const paste = (event.clipboardData || window.clipboardData).getData('text')

  // Clean pasted text
  let cleanPaste = paste.toUpperCase().replace(/[^A-Z0-9]/g, '')

  // Handle common formats like "XXXX-XXXX" or "XXXX XXXX"
  cleanPaste = cleanPaste.replace(/[-\s]/g, '')

  // Take only first 8 characters
  cleanPaste = cleanPaste.slice(0, 8)

  recoveryCode.value = cleanPaste
  cursorPosition.value = cleanPaste.length

  // Auto-submit if complete
  if (cleanPaste.length === 8) {
    setTimeout(() => handleSubmit(), 100)
  }
}

const handleSubmit = async () => {
  if (!isValidCode.value || loading.value) {return}

  loading.value = true
  errors.code = ''

  try {
    const response = await twoFactorService.verify(recoveryCode.value, 'recovery')

    if (response.success) {
      toast.success('Recovery code verified successfully!')

      emit('success', {
        redirect: response.redirect,
        warning: response.warning,
        remainingCodes: response.remaining_codes,
      })
    }
  } catch (error) {
    const message = error.message || 'Invalid recovery code. Please check and try again.'
    errors.code = message

    // Shake animation for visual feedback
    const input = document.getElementById('recovery-code')
    if (input) {
      input.classList.add('shake')
      setTimeout(() => input.classList.remove('shake'), 500)
    }
  } finally {
    loading.value = false
  }
}

const switchToAuthenticator = () => {
  emit('switchToAuthenticator')
}

const requestSMS = async () => {
  if (smsLoading.value) {return}

  smsLoading.value = true

  try {
    await twoFactorService.sendSMS()
    emit('requestSMS')
    toast.success('SMS code sent!')
  } catch (error) {
    toast.error('Failed to send SMS code')
  } finally {
    smsLoading.value = false
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
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
      },
      body: JSON.stringify(recoveryData),
    })

    const result = await response.json()

    if (result.success) {
      showEmergencyModal.value = false
      toast.success(result.message)
    } else {
      throw new Error(result.message)
    }
  } catch (error) {
    toast.error('Failed to submit emergency recovery request')
  }
}

// Auto-focus input on mount
onMounted(() => {
  setTimeout(() => {
    const input = document.getElementById('recovery-code')
    if (input) {input.focus()}
  }, 100)
})
</script>

<style scoped>
.backup-code-prompt {
  @apply mx-auto max-w-md space-y-6 p-6;
}

.header-section {
  @apply space-y-3 text-center;
}

.icon-container {
  @apply flex justify-center;
}

.backup-icon {
  @apply h-12 w-12 text-amber-600;
}

.title {
  @apply text-2xl font-bold text-gray-900;
}

.subtitle {
  @apply text-gray-600;
}

.codes-info {
  @apply space-y-2;
}

.info-badge {
  @apply inline-flex items-center space-x-2 rounded-full px-3 py-1 text-sm font-medium;
}

.info-badge.info {
  @apply bg-blue-100 text-blue-800;
}

.info-badge.success {
  @apply bg-green-100 text-green-800;
}

.info-badge.warning {
  @apply bg-yellow-100 text-yellow-800;
}

.info-badge.danger {
  @apply bg-red-100 text-red-800;
}

.warning-text {
  @apply rounded-lg border border-yellow-200 bg-yellow-50 p-3 text-sm text-yellow-700;
}

.recovery-form {
  @apply space-y-4;
}

.input-section {
  @apply space-y-2;
}

.input-label {
  @apply block text-sm font-medium text-gray-700;
}

.required {
  @apply ml-1 text-red-500;
}

.input-wrapper {
  @apply space-y-3;
}

.recovery-input {
  @apply w-full rounded-lg border border-gray-300 px-4 py-3 text-center font-mono text-lg uppercase tracking-widest transition-all duration-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 disabled:cursor-not-allowed disabled:bg-gray-100;
}

.recovery-input.error {
  @apply border-red-500 ring-red-500;
}

.recovery-input.shake {
  animation: shake 0.5s ease-in-out;
}

@keyframes shake {
  0%,
  100% {
    transform: translateX(0);
  }
  25% {
    transform: translateX(-5px);
  }
  75% {
    transform: translateX(5px);
  }
}

.format-helper {
  @apply flex justify-center space-x-1;
}

.char-box {
  @apply flex h-8 w-8 items-center justify-center rounded border border-gray-300 bg-white font-mono text-lg font-medium transition-all duration-200;
}

.char-box.filled {
  @apply border-blue-300 bg-blue-50 text-blue-900;
}

.char-box.active {
  @apply border-blue-500 ring-2 ring-blue-500 ring-opacity-50;
}

.error-message {
  @apply flex items-center space-x-1 text-sm text-red-600;
}

.helper-text {
  @apply text-xs text-gray-500;
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

.emergency-section {
  @apply border-t border-gray-200 pt-4 text-center;
}

.emergency-btn {
  @apply inline-flex items-center space-x-2 text-sm text-red-600 transition-colors duration-200 hover:text-red-700;
}

/* Mobile optimizations */
@media (max-width: 640px) {
  .backup-code-prompt {
    @apply p-4;
  }

  .alternative-actions {
    @apply grid-cols-1;
  }

  .char-box {
    @apply h-6 w-6 text-sm;
  }

  .recovery-input {
    @apply text-base;
  }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .backup-code-prompt {
    @apply text-gray-100;
  }

  .title {
    @apply text-gray-100;
  }

  .subtitle {
    @apply text-gray-300;
  }

  .char-box {
    @apply border-gray-600 bg-gray-800;
  }

  .char-box.filled {
    @apply border-blue-600 bg-blue-900 text-blue-100;
  }

  .divider-text {
    @apply bg-gray-800;
  }
}

/* High contrast mode */
@media (prefers-contrast: high) {
  .recovery-input,
  .char-box {
    @apply border-2;
  }

  .char-box.active {
    @apply ring-4;
  }
}
</style>
