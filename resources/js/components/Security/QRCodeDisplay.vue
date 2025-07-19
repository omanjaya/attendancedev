<template>
  <div class="qr-code-display">
    <!-- QR Code Container -->
    <div class="qr-container">
      <div v-if="loading" class="loading-state">
        <div class="spinner" />
        <p class="loading-text">
          Generating QR Code...
        </p>
      </div>

      <div v-else-if="error" class="error-state">
        <Icon name="x-circle" class="error-icon" />
        <p class="error-text">
          {{ error }}
        </p>
        <button class="retry-btn" @click="$emit('retry')">
          <Icon name="refresh" class="mr-2 h-4 w-4" />
          Retry
        </button>
      </div>

      <div v-else class="qr-content">
        <div class="qr-image-container">
          <img
            :src="qrCodeUrl"
            :alt="altText"
            class="qr-image"
            @load="onImageLoad"
            @error="onImageError"
          >

          <!-- QR Code Actions -->
          <div class="qr-actions">
            <button class="action-btn" title="Download QR Code" @click="downloadQR">
              <Icon name="download" class="h-4 w-4" />
            </button>
            <button class="action-btn" title="Refresh QR Code" @click="refreshQR">
              <Icon name="refresh" class="h-4 w-4" />
            </button>
          </div>
        </div>

        <!-- QR Code Info -->
        <div class="qr-info">
          <div class="info-item">
            <span class="info-label">Company:</span>
            <span class="info-value">{{ companyName }}</span>
          </div>
          <div class="info-item">
            <span class="info-label">Account:</span>
            <span class="info-value">{{ userEmail }}</span>
          </div>
          <div class="info-item">
            <span class="info-label">Type:</span>
            <span class="info-value">Time-based OTP (TOTP)</span>
          </div>
        </div>

        <!-- Security Notice -->
        <div class="security-notice">
          <Icon name="shield-check" class="security-icon" />
          <div class="security-text">
            <p class="security-title">
              Secure Setup
            </p>
            <p class="security-desc">
              This QR code contains your unique secret key. Keep it private and secure.
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Instructions -->
    <div v-if="showInstructions" class="instructions">
      <h4 class="instructions-title">
        <Icon name="info" class="mr-2 h-4 w-4" />
        How to scan this QR code:
      </h4>
      <ol class="instructions-list">
        <li>Open your authenticator app (Google Authenticator, Authy, etc.)</li>
        <li>Tap the "+" button or "Add Account"</li>
        <li>Select "Scan QR Code" or similar option</li>
        <li>Point your phone's camera at this QR code</li>
        <li>The account will be added automatically</li>
      </ol>
    </div>

    <!-- Alternative Apps -->
    <div v-if="showAppSuggestions" class="app-suggestions">
      <h4 class="suggestions-title">
        Recommended Authenticator Apps:
      </h4>
      <div class="apps-grid">
        <div v-for="app in recommendedApps" :key="app.name" class="app-card">
          <div class="app-icon">
            <Icon :name="app.icon" class="h-6 w-6" />
          </div>
          <div class="app-info">
            <h5 class="app-name">
              {{ app.name }}
            </h5>
            <p class="app-platforms">
              {{ app.platforms.join(', ') }}
            </p>
          </div>
          <button class="app-download" :title="`Download ${app.name}`" @click="openAppStore(app)">
            <Icon name="external-link" class="h-4 w-4" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useToast } from '@/composables/useToast'

// Props
const props = defineProps({
  qrCodeUrl: {
    type: String,
    required: true,
  },
  secretKey: {
    type: String,
    required: true,
  },
  companyName: {
    type: String,
    default: 'Attendance System',
  },
  userEmail: {
    type: String,
    required: true,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  error: {
    type: String,
    default: '',
  },
  showInstructions: {
    type: Boolean,
    default: true,
  },
  showAppSuggestions: {
    type: Boolean,
    default: true,
  },
})

// Emits
const emit = defineEmits(['retry', 'refresh', 'imageLoaded', 'imageError'])

// Composables
const { toast } = useToast()

// Computed
const altText = computed(() => {
  return `2FA QR Code for ${props.userEmail} at ${props.companyName}`
})

const recommendedApps = computed(() => [
  {
    name: 'Google Authenticator',
    icon: 'google',
    platforms: ['iOS', 'Android'],
    urls: {
      ios: 'https://apps.apple.com/app/google-authenticator/id388497605',
      android:
        'https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2',
    },
  },
  {
    name: 'Authy',
    icon: 'authy',
    platforms: ['iOS', 'Android', 'Desktop'],
    urls: {
      ios: 'https://apps.apple.com/app/authy/id494168017',
      android: 'https://play.google.com/store/apps/details?id=com.authy.authy',
    },
  },
  {
    name: 'Microsoft Authenticator',
    icon: 'microsoft',
    platforms: ['iOS', 'Android'],
    urls: {
      ios: 'https://apps.apple.com/app/microsoft-authenticator/id983156458',
      android: 'https://play.google.com/store/apps/details?id=com.azure.authenticator',
    },
  },
])

// Methods
const onImageLoad = () => {
  emit('imageLoaded')
}

const onImageError = () => {
  emit('imageError', 'Failed to load QR code image')
}

const downloadQR = async () => {
  try {
    // Convert data URL to blob
    const response = await fetch(props.qrCodeUrl)
    const blob = await response.blob()

    // Create download link
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `2fa-qr-code-${Date.now()}.png`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)

    toast.success('QR code downloaded successfully')
  } catch (error) {
    toast.error('Failed to download QR code')
  }
}

const refreshQR = () => {
  emit('refresh')
}

const openAppStore = (app) => {
  const platform = navigator.platform.toLowerCase()
  let url = ''

  if (platform.includes('iphone') || platform.includes('ipad')) {
    url = app.urls.ios
  } else if (platform.includes('android')) {
    url = app.urls.android
  } else {
    // Default to iOS App Store for desktop users
    url = app.urls.ios
  }

  if (url) {
    window.open(url, '_blank', 'noopener,noreferrer')
  }
}
</script>

<style scoped>
.qr-code-display {
  @apply space-y-6;
}

.qr-container {
  @apply rounded-lg border-2 border-gray-200 bg-white p-6 text-center;
}

.loading-state {
  @apply flex h-64 flex-col items-center justify-center;
}

.spinner {
  @apply mb-4 h-8 w-8 animate-spin rounded-full border-b-2 border-blue-600;
}

.loading-text {
  @apply text-gray-600;
}

.error-state {
  @apply flex h-64 flex-col items-center justify-center text-red-600;
}

.error-icon {
  @apply mb-2 h-8 w-8;
}

.error-text {
  @apply mb-4;
}

.retry-btn {
  @apply inline-flex items-center rounded-lg bg-red-600 px-4 py-2 text-white transition-colors duration-200 hover:bg-red-700;
}

.qr-content {
  @apply space-y-4;
}

.qr-image-container {
  @apply relative inline-block;
}

.qr-image {
  @apply h-64 w-64 rounded-lg border border-gray-300;
}

.qr-actions {
  @apply absolute right-2 top-2 flex space-x-1;
}

.action-btn {
  @apply rounded-md border border-gray-200 bg-white p-2 text-gray-600 shadow-sm transition-colors duration-200 hover:bg-gray-50;
}

.qr-info {
  @apply space-y-2 rounded-lg bg-gray-50 p-4;
}

.info-item {
  @apply flex justify-between text-sm;
}

.info-label {
  @apply font-medium text-gray-600;
}

.info-value {
  @apply text-gray-900;
}

.security-notice {
  @apply flex items-start space-x-3 rounded-lg border border-green-200 bg-green-50 p-4;
}

.security-icon {
  @apply mt-0.5 h-5 w-5 text-green-600;
}

.security-text {
  @apply flex-1;
}

.security-title {
  @apply mb-1 font-medium text-green-800;
}

.security-desc {
  @apply text-sm text-green-700;
}

.instructions {
  @apply rounded-lg border border-blue-200 bg-blue-50 p-4;
}

.instructions-title {
  @apply mb-3 flex items-center font-medium text-blue-800;
}

.instructions-list {
  @apply list-inside list-decimal space-y-1 text-sm text-blue-700;
}

.app-suggestions {
  @apply space-y-4;
}

.suggestions-title {
  @apply font-medium text-gray-800;
}

.apps-grid {
  @apply grid grid-cols-1 gap-3 md:grid-cols-3;
}

.app-card {
  @apply flex items-center space-x-3 rounded-lg border border-gray-200 bg-white p-3 transition-colors duration-200 hover:border-gray-300;
}

.app-icon {
  @apply flex-shrink-0;
}

.app-info {
  @apply min-w-0 flex-1;
}

.app-name {
  @apply text-sm font-medium text-gray-900;
}

.app-platforms {
  @apply text-xs text-gray-500;
}

.app-download {
  @apply rounded p-1 text-blue-600 transition-colors duration-200 hover:text-blue-700;
}

/* Responsive adjustments */
@media (max-width: 640px) {
  .qr-image {
    @apply h-48 w-48;
  }

  .apps-grid {
    @apply grid-cols-1;
  }
}
</style>
