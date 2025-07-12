<template>
  <div class="2fa-setup-container">
    <!-- Step Indicator -->
    <div class="step-indicator mb-6">
      <div class="flex justify-between items-center">
        <div 
          v-for="(stepInfo, index) in steps" 
          :key="index"
          class="flex items-center"
          :class="{ 'opacity-50': currentStep < index }"
        >
          <div 
            class="step-circle"
            :class="{
              'bg-blue-600 text-white': currentStep >= index,
              'bg-gray-200 text-gray-600': currentStep < index,
              'bg-green-600': currentStep > index
            }"
          >
            <Icon 
              v-if="currentStep > index" 
              name="check" 
              class="w-4 h-4" 
            />
            <span v-else>{{ index + 1 }}</span>
          </div>
          <span class="ml-2 text-sm font-medium">{{ stepInfo.title }}</span>
          <div 
            v-if="index < steps.length - 1" 
            class="step-line ml-4"
            :class="{
              'bg-blue-600': currentStep > index,
              'bg-gray-200': currentStep <= index
            }"
          />
        </div>
      </div>
    </div>

    <!-- Step Content -->
    <div class="step-content">
      <!-- Step 1: QR Code Display -->
      <div v-if="currentStep === 0" class="qr-step">
        <div class="text-center">
          <h3 class="text-xl font-semibold mb-4">
            Scan QR Code with Your Authenticator App
          </h3>
          <p class="text-gray-600 mb-6">
            Use Google Authenticator, Authy, or any compatible TOTP app to scan this QR code.
          </p>

          <!-- QR Code Display -->
          <div class="qr-container bg-white p-6 rounded-lg shadow-lg inline-block mb-6">
            <div v-if="loading.qr" class="flex justify-center items-center h-48 w-48">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            </div>
            <img 
              v-else-if="qrCodeUrl" 
              :src="qrCodeUrl" 
              alt="2FA QR Code"
              class="h-48 w-48"
            />
            <div v-else class="h-48 w-48 bg-gray-100 flex items-center justify-center rounded">
              <Icon name="qr-code" class="w-12 h-12 text-gray-400" />
            </div>
          </div>

          <!-- Manual Entry -->
          <div class="manual-entry bg-gray-50 p-4 rounded-lg mb-6">
            <h4 class="font-medium mb-2">Can't scan? Enter this code manually:</h4>
            <div class="flex items-center justify-center space-x-2">
              <code class="bg-white px-3 py-2 rounded border font-mono text-sm">
                {{ secretKey || 'Loading...' }}
              </code>
              <button
                @click="copySecretKey"
                class="btn-secondary"
                :disabled="!secretKey"
              >
                <Icon name="copy" class="w-4 h-4" />
              </button>
            </div>
          </div>

          <!-- Instructions -->
          <div class="instructions text-left bg-blue-50 p-4 rounded-lg mb-6">
            <h4 class="font-medium mb-2 flex items-center">
              <Icon name="info" class="w-4 h-4 mr-2" />
              Setup Instructions:
            </h4>
            <ol class="list-decimal list-inside space-y-1 text-sm">
              <li>Install Google Authenticator or similar app on your mobile device</li>
              <li>Open the app and tap "Add account" or "+" button</li>
              <li>Scan the QR code above or enter the manual key</li>
              <li>Your app will generate a 6-digit code every 30 seconds</li>
              <li>Enter the current code in the next step to verify setup</li>
            </ol>
          </div>

          <!-- Actions -->
          <div class="flex justify-between">
            <button @click="$emit('cancel')" class="btn-secondary">
              Cancel Setup
            </button>
            <button 
              @click="nextStep" 
              class="btn-primary"
              :disabled="!secretKey"
            >
              I've Added the Account
              <Icon name="arrow-right" class="w-4 h-4 ml-2" />
            </button>
          </div>
        </div>
      </div>

      <!-- Step 2: Verification -->
      <div v-if="currentStep === 1" class="verify-step">
        <div class="text-center">
          <h3 class="text-xl font-semibold mb-4">
            Verify Your Setup
          </h3>
          <p class="text-gray-600 mb-6">
            Enter the 6-digit code from your authenticator app to complete setup.
          </p>

          <!-- Verification Code Input -->
          <div class="verification-input mb-6">
            <label class="block text-sm font-medium mb-2">
              Verification Code
            </label>
            <div class="flex justify-center">
              <input
                v-model="verificationCode"
                type="text"
                maxlength="6"
                pattern="[0-9]*"
                inputmode="numeric"
                class="text-center text-2xl font-mono w-48 px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="000000"
                @input="handleVerificationInput"
                @keyup.enter="verifySetup"
              />
            </div>
            <p class="text-xs text-gray-500 mt-2">
              Enter the 6-digit code from your authenticator app
            </p>
          </div>

          <!-- Error Display -->
          <div v-if="error" class="alert alert-error mb-6">
            <Icon name="x-circle" class="w-4 h-4" />
            {{ error }}
          </div>

          <!-- Actions -->
          <div class="flex justify-between">
            <button @click="previousStep" class="btn-secondary">
              <Icon name="arrow-left" class="w-4 h-4 mr-2" />
              Back
            </button>
            <button 
              @click="verifySetup" 
              class="btn-primary"
              :disabled="verificationCode.length !== 6 || loading.verify"
            >
              <div v-if="loading.verify" class="spinner mr-2" />
              Verify & Enable 2FA
            </button>
          </div>
        </div>
      </div>

      <!-- Step 3: Backup Codes -->
      <div v-if="currentStep === 2" class="backup-step">
        <div class="text-center">
          <h3 class="text-xl font-semibold mb-4 text-green-600">
            <Icon name="check-circle" class="w-6 h-6 inline mr-2" />
            2FA Enabled Successfully!
          </h3>
          <p class="text-gray-600 mb-6">
            Save these backup codes in a secure location. You can use them to access your account if you lose your authenticator device.
          </p>

          <!-- Backup Codes Display -->
          <div class="backup-codes bg-gray-50 p-6 rounded-lg mb-6">
            <h4 class="font-medium mb-4">Your Backup Codes:</h4>
            <div class="grid grid-cols-2 gap-2 max-w-md mx-auto">
              <code 
                v-for="(code, index) in backupCodes" 
                :key="index"
                class="bg-white px-3 py-2 rounded border font-mono text-sm text-center"
              >
                {{ code }}
              </code>
            </div>
          </div>

          <!-- Warning -->
          <div class="warning bg-yellow-50 border border-yellow-200 p-4 rounded-lg mb-6">
            <div class="flex items-start">
              <Icon name="exclamation-triangle" class="w-5 h-5 text-yellow-600 mt-0.5 mr-3" />
              <div class="text-left">
                <h4 class="font-medium text-yellow-800 mb-1">Important:</h4>
                <ul class="text-sm text-yellow-700 space-y-1">
                  <li>• Each backup code can only be used once</li>
                  <li>• Store them in a safe, secure location</li>
                  <li>• Don't share them with anyone</li>
                  <li>• You can regenerate new codes anytime from your security settings</li>
                </ul>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex justify-center space-x-4">
            <button @click="downloadBackupCodes" class="btn-secondary">
              <Icon name="download" class="w-4 h-4 mr-2" />
              Download Codes
            </button>
            <button @click="copyBackupCodes" class="btn-secondary">
              <Icon name="copy" class="w-4 h-4 mr-2" />
              Copy Codes
            </button>
            <button @click="finish" class="btn-primary">
              <Icon name="check" class="w-4 h-4 mr-2" />
              I've Saved My Codes
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useToast } from '@/composables/useToast'
import { twoFactorService } from '@/services/twoFactor'

// Props & Emits
const emit = defineEmits(['success', 'cancel', 'error'])

// Reactive Data
const currentStep = ref(0)
const secretKey = ref('')
const qrCodeUrl = ref('')
const verificationCode = ref('')
const backupCodes = ref([])
const error = ref('')

const loading = reactive({
  qr: false,
  verify: false
})

const { toast } = useToast()

// Computed
const steps = computed(() => [
  { title: 'Scan QR Code', description: 'Add account to authenticator app' },
  { title: 'Verify Setup', description: 'Enter verification code' },
  { title: 'Save Backup Codes', description: 'Download recovery codes' }
])

// Methods
const initializeSetup = async () => {
  loading.qr = true
  error.value = ''
  
  try {
    const response = await twoFactorService.initializeSetup()
    
    if (response.success) {
      secretKey.value = response.data.secret_key
      qrCodeUrl.value = response.data.qr_code_url
    } else {
      throw new Error(response.message || 'Failed to initialize 2FA setup')
    }
  } catch (err) {
    error.value = err.message || 'Failed to load QR code'
    emit('error', err.message)
  } finally {
    loading.qr = false
  }
}

const nextStep = () => {
  if (currentStep.value < steps.value.length - 1) {
    currentStep.value++
    error.value = ''
  }
}

const previousStep = () => {
  if (currentStep.value > 0) {
    currentStep.value--
    error.value = ''
  }
}

const handleVerificationInput = (event) => {
  // Only allow numeric input
  const value = event.target.value.replace(/[^0-9]/g, '')
  verificationCode.value = value.slice(0, 6)
}

const verifySetup = async () => {
  if (verificationCode.value.length !== 6) {
    error.value = 'Please enter a 6-digit verification code'
    return
  }

  loading.verify = true
  error.value = ''

  try {
    const response = await twoFactorService.verifySetup(verificationCode.value)
    
    if (response.success) {
      backupCodes.value = response.data.backup_codes || []
      nextStep()
      toast.success('2FA has been enabled successfully!')
    } else {
      throw new Error(response.message || 'Invalid verification code')
    }
  } catch (err) {
    error.value = err.message || 'Verification failed. Please try again.'
  } finally {
    loading.verify = false
  }
}

const copySecretKey = async () => {
  try {
    await navigator.clipboard.writeText(secretKey.value)
    toast.success('Secret key copied to clipboard')
  } catch (err) {
    toast.error('Failed to copy secret key')
  }
}

const copyBackupCodes = async () => {
  const codesText = backupCodes.value.join('\n')
  try {
    await navigator.clipboard.writeText(codesText)
    toast.success('Backup codes copied to clipboard')
  } catch (err) {
    toast.error('Failed to copy backup codes')
  }
}

const downloadBackupCodes = () => {
  const codesText = `Two-Factor Authentication Backup Codes
Generated: ${new Date().toLocaleString()}

${backupCodes.value.join('\n')}

IMPORTANT:
- Each code can only be used once
- Store these codes in a safe place
- Don't share them with anyone`

  const blob = new Blob([codesText], { type: 'text/plain' })
  const url = window.URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = '2fa-backup-codes.txt'
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  window.URL.revokeObjectURL(url)
  
  toast.success('Backup codes downloaded')
}

const finish = () => {
  emit('success', {
    enabled: true,
    backup_codes: backupCodes.value
  })
}

// Lifecycle
onMounted(() => {
  initializeSetup()
})
</script>

<style scoped>
.step-indicator {
  @apply max-w-2xl mx-auto;
}

.step-circle {
  @apply w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium;
}

.step-line {
  @apply h-0.5 w-16;
}

.btn-primary {
  @apply bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg 
         transition-colors duration-200 inline-flex items-center disabled:opacity-50 
         disabled:cursor-not-allowed;
}

.btn-secondary {
  @apply bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2 px-4 rounded-lg 
         transition-colors duration-200 inline-flex items-center disabled:opacity-50 
         disabled:cursor-not-allowed;
}

.alert {
  @apply p-4 rounded-lg flex items-center space-x-2;
}

.alert-error {
  @apply bg-red-50 text-red-800 border border-red-200;
}

.spinner {
  @apply animate-spin rounded-full h-4 w-4 border-b-2 border-white;
}

.verification-input input:focus {
  @apply outline-none;
}

.backup-codes code {
  @apply select-all;
}

/* Responsive adjustments */
@media (max-width: 640px) {
  .step-indicator .flex {
    @apply flex-col space-y-4;
  }
  
  .step-line {
    @apply w-0.5 h-8;
  }
  
  .backup-codes .grid {
    @apply grid-cols-1;
  }
}
</style>