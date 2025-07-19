<template>
  <div class="2fa-setup-container">
    <!-- Step Indicator -->
    <div class="step-indicator mb-6">
      <div class="flex items-center justify-between">
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
              'bg-green-600': currentStep > index,
            }"
          >
            <Icon v-if="currentStep > index" name="check" class="h-4 w-4" />
            <span v-else>{{ index + 1 }}</span>
          </div>
          <span class="ml-2 text-sm font-medium">{{ stepInfo.title }}</span>
          <div
            v-if="index < steps.length - 1"
            class="step-line ml-4"
            :class="{
              'bg-blue-600': currentStep > index,
              'bg-gray-200': currentStep <= index,
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
          <h3 class="mb-4 text-xl font-semibold">
            Scan QR Code with Your Authenticator App
          </h3>
          <p class="mb-6 text-gray-600">
            Use Google Authenticator, Authy, or any compatible TOTP app to scan this QR code.
          </p>

          <!-- QR Code Display -->
          <div class="qr-container mb-6 inline-block rounded-lg bg-white p-6 shadow-lg">
            <div v-if="loading.qr" class="flex h-48 w-48 items-center justify-center">
              <div class="h-8 w-8 animate-spin rounded-full border-b-2 border-blue-600" />
            </div>
            <img
              v-else-if="qrCodeUrl"
              :src="qrCodeUrl"
              alt="2FA QR Code"
              class="h-48 w-48"
            >
            <div v-else class="flex h-48 w-48 items-center justify-center rounded bg-gray-100">
              <Icon name="qr-code" class="h-12 w-12 text-gray-400" />
            </div>
          </div>

          <!-- Manual Entry -->
          <div class="manual-entry mb-6 rounded-lg bg-gray-50 p-4">
            <h4 class="mb-2 font-medium">
              Can't scan? Enter this code manually:
            </h4>
            <div class="flex items-center justify-center space-x-2">
              <code class="rounded border bg-white px-3 py-2 font-mono text-sm">
                {{ secretKey || 'Loading...' }}
              </code>
              <button class="btn-secondary" :disabled="!secretKey" @click="copySecretKey">
                <Icon name="copy" class="h-4 w-4" />
              </button>
            </div>
          </div>

          <!-- Instructions -->
          <div class="instructions mb-6 rounded-lg bg-blue-50 p-4 text-left">
            <h4 class="mb-2 flex items-center font-medium">
              <Icon name="info" class="mr-2 h-4 w-4" />
              Setup Instructions:
            </h4>
            <ol class="list-inside list-decimal space-y-1 text-sm">
              <li>Install Google Authenticator or similar app on your mobile device</li>
              <li>Open the app and tap "Add account" or "+" button</li>
              <li>Scan the QR code above or enter the manual key</li>
              <li>Your app will generate a 6-digit code every 30 seconds</li>
              <li>Enter the current code in the next step to verify setup</li>
            </ol>
          </div>

          <!-- Actions -->
          <div class="flex justify-between">
            <button class="btn-secondary" @click="$emit('cancel')">
              Cancel Setup
            </button>
            <button class="btn-primary" :disabled="!secretKey" @click="nextStep">
              I've Added the Account
              <Icon name="arrow-right" class="ml-2 h-4 w-4" />
            </button>
          </div>
        </div>
      </div>

      <!-- Step 2: Verification -->
      <div v-if="currentStep === 1" class="verify-step">
        <div class="text-center">
          <h3 class="mb-4 text-xl font-semibold">
            Verify Your Setup
          </h3>
          <p class="mb-6 text-gray-600">
            Enter the 6-digit code from your authenticator app to complete setup.
          </p>

          <!-- Verification Code Input -->
          <div class="verification-input mb-6">
            <label class="mb-2 block text-sm font-medium"> Verification Code </label>
            <div class="flex justify-center">
              <input
                v-model="verificationCode"
                type="text"
                maxlength="6"
                pattern="[0-9]*"
                inputmode="numeric"
                class="w-48 rounded-lg border px-4 py-3 text-center font-mono text-2xl focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                placeholder="000000"
                @input="handleVerificationInput"
                @keyup.enter="verifySetup"
              >
            </div>
            <p class="mt-2 text-xs text-gray-500">
              Enter the 6-digit code from your authenticator app
            </p>
          </div>

          <!-- Error Display -->
          <div v-if="error" class="alert alert-error mb-6">
            <Icon name="x-circle" class="h-4 w-4" />
            {{ error }}
          </div>

          <!-- Actions -->
          <div class="flex justify-between">
            <button class="btn-secondary" @click="previousStep">
              <Icon name="arrow-left" class="mr-2 h-4 w-4" />
              Back
            </button>
            <button
              class="btn-primary"
              :disabled="verificationCode.length !== 6 || loading.verify"
              @click="verifySetup"
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
          <h3 class="mb-4 text-xl font-semibold text-green-600">
            <Icon name="check-circle" class="mr-2 inline h-6 w-6" />
            2FA Enabled Successfully!
          </h3>
          <p class="mb-6 text-gray-600">
            Save these backup codes in a secure location. You can use them to access your account if
            you lose your authenticator device.
          </p>

          <!-- Backup Codes Display -->
          <div class="backup-codes mb-6 rounded-lg bg-gray-50 p-6">
            <h4 class="mb-4 font-medium">
              Your Backup Codes:
            </h4>
            <div class="mx-auto grid max-w-md grid-cols-2 gap-2">
              <code
                v-for="(code, index) in backupCodes"
                :key="index"
                class="rounded border bg-white px-3 py-2 text-center font-mono text-sm"
              >
                {{ code }}
              </code>
            </div>
          </div>

          <!-- Warning -->
          <div class="warning mb-6 rounded-lg border border-yellow-200 bg-yellow-50 p-4">
            <div class="flex items-start">
              <Icon name="exclamation-triangle" class="mr-3 mt-0.5 h-5 w-5 text-yellow-600" />
              <div class="text-left">
                <h4 class="mb-1 font-medium text-yellow-800">
                  Important:
                </h4>
                <ul class="space-y-1 text-sm text-yellow-700">
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
            <button class="btn-secondary" @click="downloadBackupCodes">
              <Icon name="download" class="mr-2 h-4 w-4" />
              Download Codes
            </button>
            <button class="btn-secondary" @click="copyBackupCodes">
              <Icon name="copy" class="mr-2 h-4 w-4" />
              Copy Codes
            </button>
            <button class="btn-primary" @click="finish">
              <Icon name="check" class="mr-2 h-4 w-4" />
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
  verify: false,
})

const { toast } = useToast()

// Computed
const steps = computed(() => [
  { title: 'Scan QR Code', description: 'Add account to authenticator app' },
  { title: 'Verify Setup', description: 'Enter verification code' },
  { title: 'Save Backup Codes', description: 'Download recovery codes' },
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
    backup_codes: backupCodes.value,
  })
}

// Lifecycle
onMounted(() => {
  initializeSetup()
})
</script>

<style scoped>
.step-indicator {
  @apply mx-auto max-w-2xl;
}

.step-circle {
  @apply flex h-8 w-8 items-center justify-center rounded-full text-sm font-medium;
}

.step-line {
  @apply h-0.5 w-16;
}

.btn-primary {
  @apply inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 font-medium text-white transition-colors duration-200 hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50;
}

.btn-secondary {
  @apply inline-flex items-center rounded-lg bg-gray-200 px-4 py-2 font-medium text-gray-800 transition-colors duration-200 hover:bg-gray-300 disabled:cursor-not-allowed disabled:opacity-50;
}

.alert {
  @apply flex items-center space-x-2 rounded-lg p-4;
}

.alert-error {
  @apply border border-red-200 bg-red-50 text-red-800;
}

.spinner {
  @apply h-4 w-4 animate-spin rounded-full border-b-2 border-white;
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
    @apply h-8 w-0.5;
  }

  .backup-codes .grid {
    @apply grid-cols-1;
  }
}
</style>
