<template>
  <div class="two-factor-verification">
    <!-- Main 2FA Prompt -->
    <TwoFactorPrompt
      v-if="currentView === 'main'"
      :user="user"
      :intended-url="intendedUrl"
      :allow-remember-device="allowRememberDevice"
      :sms-enabled="smsEnabled"
      @success="handleSuccess"
      @logout="handleLogout"
      @error="handleError"
    />

    <!-- Backup Code Entry -->
    <BackupCodePrompt
      v-else-if="currentView === 'backup-code'"
      :remaining-codes="remainingCodes"
      :sms-enabled="smsEnabled"
      @success="handleSuccess"
      @switch-to-authenticator="switchToMain"
      @request-sms="requestSMS"
      @error="handleError"
    />

    <!-- Recovery Options -->
    <RecoveryOptions
      v-else-if="currentView === 'recovery'"
      :user-phone="user.phone"
      :sms-enabled="smsEnabled"
      @use-backup-code="switchToBackupCode"
      @request-sms="requestSMS"
      @start-emergency-recovery="handleEmergencyRecovery"
      @back-to-login="switchToMain"
    />

    <!-- SMS Verification -->
    <TwoFactorPrompt
      v-else-if="currentView === 'sms'"
      :user="user"
      :intended-url="intendedUrl"
      :allow-remember-device="allowRememberDevice"
      :sms-enabled="smsEnabled"
      verification-method="sms"
      @success="handleSuccess"
      @logout="handleLogout"
      @error="handleError"
    />

    <!-- Loading Overlay -->
    <div v-if="loading" class="loading-overlay">
      <div class="loading-content">
        <div class="loading-spinner"></div>
        <p class="loading-text">{{ loadingMessage }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useToast } from '@/composables/useToast'
import { twoFactorService } from '@/services/twoFactor'
import TwoFactorPrompt from './TwoFactorPrompt.vue'
import BackupCodePrompt from './BackupCodePrompt.vue'
import RecoveryOptions from './RecoveryOptions.vue'

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
  },
  csrfToken: {
    type: String,
    required: true
  }
})

// Emits
const emit = defineEmits(['success', 'logout', 'error'])

// Reactive data
const currentView = ref('main') // main, backup-code, recovery, sms
const loading = ref(false)
const loadingMessage = ref('')
const remainingCodes = ref(null)

// Composables
const { toast } = useToast()

// Methods
const handleSuccess = (result) => {
  // Store remaining codes info if provided
  if (result.remainingCodes !== undefined) {
    remainingCodes.value = result.remainingCodes
  }

  // Emit success to parent
  emit('success', result)
}

const handleLogout = () => {
  emit('logout')
}

const handleError = (error) => {
  emit('error', error)
}

const switchToMain = () => {
  currentView.value = 'main'
}

const switchToBackupCode = () => {
  currentView.value = 'backup-code'
}

const switchToRecovery = () => {
  currentView.value = 'recovery'
}

const switchToSMS = () => {
  currentView.value = 'sms'
}

const requestSMS = async () => {
  loading.value = true
  loadingMessage.value = 'Sending SMS code...'

  try {
    await twoFactorService.sendSMS()
    switchToSMS()
    toast.success('SMS code sent to your phone')
  } catch (error) {
    toast.error('Failed to send SMS code')
    handleError(error)
  } finally {
    loading.value = false
  }
}

const handleEmergencyRecovery = (result) => {
  // Show success message and stay on current view
  toast.success('Emergency recovery request submitted successfully')
  
  // Optionally redirect or show instructions
  setTimeout(() => {
    switchToMain()
  }, 3000)
}

// Set up CSRF token for API requests
if (props.csrfToken) {
  // Set CSRF token for axios requests
  const csrfMeta = document.querySelector('meta[name="csrf-token"]')
  if (csrfMeta) {
    csrfMeta.setAttribute('content', props.csrfToken)
  }
}
</script>

<style scoped>
.two-factor-verification {
  @apply relative w-full;
}

.loading-overlay {
  @apply absolute inset-0 bg-white bg-opacity-90 flex items-center justify-center z-50;
}

.loading-content {
  @apply text-center space-y-4;
}

.loading-spinner {
  @apply mx-auto w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin;
}

.loading-text {
  @apply text-sm text-gray-600 font-medium;
}

/* Transition animations */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .loading-spinner {
    @apply animate-none;
  }
  
  .fade-enter-active,
  .fade-leave-active {
    transition: none;
  }
}
</style>