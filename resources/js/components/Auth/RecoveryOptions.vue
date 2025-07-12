<template>
  <div class="recovery-options">
    <!-- Header -->
    <div class="header-section">
      <div class="icon-container">
        <Icon name="key" class="recovery-icon" />
      </div>
      <h2 class="title">Account Recovery</h2>
      <p class="subtitle">
        Choose a recovery method to regain access to your account.
      </p>
    </div>

    <!-- Recovery Methods -->
    <div class="recovery-methods">
      <!-- Use Recovery Code -->
      <div class="recovery-method">
        <button
          @click="selectMethod('backup-code')"
          class="method-card"
          :class="{ 'selected': selectedMethod === 'backup-code' }"
        >
          <div class="method-icon">
            <Icon name="key" class="w-8 h-8" />
          </div>
          <div class="method-content">
            <h3 class="method-title">Recovery Code</h3>
            <p class="method-description">
              Use one of your saved 8-character backup codes
            </p>
            <div class="method-availability">
              <Icon name="check-circle" class="w-4 h-4 text-green-600" />
              <span>Available</span>
            </div>
          </div>
        </button>
      </div>

      <!-- SMS Recovery (if available) -->
      <div v-if="smsAvailable" class="recovery-method">
        <button
          @click="selectMethod('sms')"
          class="method-card"
          :class="{ 'selected': selectedMethod === 'sms' }"
          :disabled="smsLoading"
        >
          <div class="method-icon">
            <Icon name="phone" class="w-8 h-8" />
          </div>
          <div class="method-content">
            <h3 class="method-title">SMS Code</h3>
            <p class="method-description">
              Send a verification code to {{ maskedPhone }}
            </p>
            <div class="method-availability">
              <Icon name="check-circle" class="w-4 h-4 text-green-600" />
              <span>Available</span>
            </div>
          </div>
          <div v-if="smsLoading" class="method-loading">
            <div class="spinner" />
          </div>
        </button>
      </div>

      <!-- Emergency Recovery -->
      <div class="recovery-method">
        <button
          @click="selectMethod('emergency')"
          class="method-card emergency"
          :class="{ 'selected': selectedMethod === 'emergency' }"
        >
          <div class="method-icon">
            <Icon name="exclamation-triangle" class="w-8 h-8" />
          </div>
          <div class="method-content">
            <h3 class="method-title">Emergency Recovery</h3>
            <p class="method-description">
              Contact administrator for manual account recovery
            </p>
            <div class="method-availability warning">
              <Icon name="clock" class="w-4 h-4 text-amber-600" />
              <span>24-48 hour response</span>
            </div>
          </div>
        </button>
      </div>
    </div>

    <!-- Selected Method Actions -->
    <div v-if="selectedMethod" class="selected-actions">
      <div v-if="selectedMethod === 'backup-code'" class="action-card">
        <h4 class="action-title">Ready to use recovery code?</h4>
        <p class="action-description">
          You'll need one of your 8-character backup codes that you saved when setting up 2FA.
        </p>
        <button @click="proceedWithBackupCode" class="btn-primary">
          <Icon name="key" class="w-4 h-4" />
          Enter Recovery Code
        </button>
      </div>

      <div v-if="selectedMethod === 'sms'" class="action-card">
        <h4 class="action-title">Send SMS verification code?</h4>
        <p class="action-description">
          We'll send a 6-digit code to your registered phone number {{ maskedPhone }}.
        </p>
        <div class="action-buttons">
          <button 
            @click="requestSMSCode" 
            class="btn-primary"
            :disabled="smsLoading"
          >
            <div v-if="smsLoading" class="spinner" />
            <Icon v-else name="phone" class="w-4 h-4" />
            {{ smsLoading ? 'Sending...' : 'Send SMS Code' }}
          </button>
        </div>
      </div>

      <div v-if="selectedMethod === 'emergency'" class="action-card emergency">
        <h4 class="action-title">Request emergency access?</h4>
        <p class="action-description">
          This will create a support ticket and notify administrators. You'll need to verify your identity.
        </p>
        <div class="emergency-warning">
          <Icon name="exclamation-triangle" class="w-4 h-4" />
          <span>This process may take 24-48 hours and requires manual verification</span>
        </div>
        <button @click="startEmergencyRecovery" class="btn-emergency">
          <Icon name="exclamation-triangle" class="w-4 h-4" />
          Start Emergency Recovery
        </button>
      </div>
    </div>

    <!-- Back to Login -->
    <div class="back-section">
      <button @click="backToLogin" class="back-btn">
        <Icon name="arrow-left" class="w-4 h-4" />
        Back to Login
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
import { ref, computed } from 'vue'
import { useToast } from '@/composables/useToast'
import { twoFactorService } from '@/services/twoFactor'
import EmergencyRecoveryModal from '@/components/Auth/EmergencyRecoveryModal.vue'

// Props
const props = defineProps({
  userPhone: {
    type: String,
    default: null
  },
  smsEnabled: {
    type: Boolean,
    default: false
  }
})

// Emits
const emit = defineEmits([
  'use-backup-code',
  'request-sms',
  'start-emergency-recovery',
  'back-to-login'
])

// Reactive data
const selectedMethod = ref(null)
const smsLoading = ref(false)
const showEmergencyModal = ref(false)

// Composables
const { toast } = useToast()

// Computed
const smsAvailable = computed(() => {
  return props.smsEnabled && props.userPhone
})

const maskedPhone = computed(() => {
  if (!props.userPhone) return ''
  
  const phone = props.userPhone
  if (phone.length <= 4) return phone
  
  const visible = phone.slice(-4)
  const masked = '*'.repeat(phone.length - 4)
  return masked + visible
})

// Methods
const selectMethod = (method) => {
  selectedMethod.value = method
}

const proceedWithBackupCode = () => {
  emit('use-backup-code')
}

const requestSMSCode = async () => {
  if (smsLoading.value) return

  smsLoading.value = true

  try {
    await twoFactorService.sendSMS()
    toast.success('SMS code sent to your phone')
    emit('request-sms')
  } catch (error) {
    toast.error('Failed to send SMS code')
  } finally {
    smsLoading.value = false
  }
}

const startEmergencyRecovery = () => {
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
      toast.success(result.message)
      emit('start-emergency-recovery', result)
    } else {
      throw new Error(result.message)
    }
  } catch (error) {
    toast.error('Failed to submit emergency recovery request')
  }
}

const backToLogin = () => {
  emit('back-to-login')
}
</script>

<style scoped>
.recovery-options {
  @apply max-w-lg mx-auto p-6 space-y-6;
}

.header-section {
  @apply text-center space-y-3;
}

.icon-container {
  @apply flex justify-center;
}

.recovery-icon {
  @apply w-12 h-12 text-amber-600;
}

.title {
  @apply text-2xl font-bold text-gray-900;
}

.subtitle {
  @apply text-gray-600;
}

.recovery-methods {
  @apply space-y-3;
}

.recovery-method {
  @apply w-full;
}

.method-card {
  @apply w-full p-4 border border-gray-200 rounded-lg text-left 
         hover:border-blue-300 hover:bg-blue-50 
         focus:ring-2 focus:ring-blue-500 focus:border-blue-500
         transition-all duration-200 relative;
}

.method-card.selected {
  @apply border-blue-500 bg-blue-50 ring-2 ring-blue-500;
}

.method-card.emergency {
  @apply border-red-200 hover:border-red-300 hover:bg-red-50;
}

.method-card.emergency.selected {
  @apply border-red-500 bg-red-50 ring-2 ring-red-500;
}

.method-card:disabled {
  @apply opacity-50 cursor-not-allowed;
}

.method-card {
  @apply flex items-start space-x-4;
}

.method-icon {
  @apply flex-shrink-0 text-gray-600;
}

.method-card.selected .method-icon {
  @apply text-blue-600;
}

.method-card.emergency .method-icon {
  @apply text-red-600;
}

.method-content {
  @apply flex-1 space-y-2;
}

.method-title {
  @apply text-lg font-medium text-gray-900;
}

.method-description {
  @apply text-sm text-gray-600;
}

.method-availability {
  @apply flex items-center space-x-1 text-sm;
}

.method-availability.warning {
  @apply text-amber-600;
}

.method-loading {
  @apply flex-shrink-0;
}

.selected-actions {
  @apply border-t border-gray-200 pt-6;
}

.action-card {
  @apply p-4 bg-gray-50 rounded-lg space-y-3;
}

.action-card.emergency {
  @apply bg-red-50 border border-red-200;
}

.action-title {
  @apply text-lg font-medium text-gray-900;
}

.action-description {
  @apply text-sm text-gray-600;
}

.emergency-warning {
  @apply flex items-start space-x-2 p-3 bg-amber-50 border border-amber-200 
         rounded-lg text-sm text-amber-800;
}

.action-buttons {
  @apply flex space-x-3;
}

.btn-primary {
  @apply bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 
         rounded-lg transition-colors duration-200 inline-flex items-center 
         space-x-2 disabled:opacity-50 disabled:cursor-not-allowed;
}

.btn-emergency {
  @apply bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 
         rounded-lg transition-colors duration-200 inline-flex items-center 
         space-x-2;
}

.spinner {
  @apply animate-spin rounded-full h-4 w-4 border-b-2 border-white;
}

.back-section {
  @apply text-center border-t border-gray-200 pt-4;
}

.back-btn {
  @apply text-sm text-gray-500 hover:text-gray-700 inline-flex items-center 
         space-x-2 transition-colors duration-200;
}

/* Mobile optimizations */
@media (max-width: 640px) {
  .recovery-options {
    @apply p-4;
  }
  
  .method-card {
    @apply p-3;
  }
  
  .action-buttons {
    @apply flex-col space-x-0 space-y-2;
  }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .recovery-options {
    @apply text-gray-100;
  }
  
  .title {
    @apply text-gray-100;
  }
  
  .subtitle {
    @apply text-gray-300;
  }
  
  .method-card {
    @apply bg-gray-800 border-gray-600;
  }
  
  .action-card {
    @apply bg-gray-800;
  }
}
</style>