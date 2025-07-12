<template>
  <div class="qr-code-display">
    <!-- QR Code Container -->
    <div class="qr-container">
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p class="loading-text">Generating QR Code...</p>
      </div>
      
      <div v-else-if="error" class="error-state">
        <Icon name="x-circle" class="error-icon" />
        <p class="error-text">{{ error }}</p>
        <button @click="$emit('retry')" class="retry-btn">
          <Icon name="refresh" class="w-4 h-4 mr-2" />
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
          />
          
          <!-- QR Code Actions -->
          <div class="qr-actions">
            <button 
              @click="downloadQR" 
              class="action-btn"
              title="Download QR Code"
            >
              <Icon name="download" class="w-4 h-4" />
            </button>
            <button 
              @click="refreshQR" 
              class="action-btn"
              title="Refresh QR Code"
            >
              <Icon name="refresh" class="w-4 h-4" />
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
            <p class="security-title">Secure Setup</p>
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
        <Icon name="info" class="w-4 h-4 mr-2" />
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
      <h4 class="suggestions-title">Recommended Authenticator Apps:</h4>
      <div class="apps-grid">
        <div 
          v-for="app in recommendedApps" 
          :key="app.name"
          class="app-card"
        >
          <div class="app-icon">
            <Icon :name="app.icon" class="w-6 h-6" />
          </div>
          <div class="app-info">
            <h5 class="app-name">{{ app.name }}</h5>
            <p class="app-platforms">{{ app.platforms.join(', ') }}</p>
          </div>
          <button 
            @click="openAppStore(app)" 
            class="app-download"
            :title="`Download ${app.name}`"
          >
            <Icon name="external-link" class="w-4 h-4" />
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
    required: true
  },
  secretKey: {
    type: String,
    required: true
  },
  companyName: {
    type: String,
    default: 'Attendance System'
  },
  userEmail: {
    type: String,
    required: true
  },
  loading: {
    type: Boolean,
    default: false
  },
  error: {
    type: String,
    default: ''
  },
  showInstructions: {
    type: Boolean,
    default: true
  },
  showAppSuggestions: {
    type: Boolean,
    default: true
  }
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
      android: 'https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2'
    }
  },
  {
    name: 'Authy',
    icon: 'authy',
    platforms: ['iOS', 'Android', 'Desktop'],
    urls: {
      ios: 'https://apps.apple.com/app/authy/id494168017',
      android: 'https://play.google.com/store/apps/details?id=com.authy.authy'
    }
  },
  {
    name: 'Microsoft Authenticator',
    icon: 'microsoft',
    platforms: ['iOS', 'Android'],
    urls: {
      ios: 'https://apps.apple.com/app/microsoft-authenticator/id983156458',
      android: 'https://play.google.com/store/apps/details?id=com.azure.authenticator'
    }
  }
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
  @apply bg-white rounded-lg border-2 border-gray-200 p-6 text-center;
}

.loading-state {
  @apply flex flex-col items-center justify-center h-64;
}

.spinner {
  @apply animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mb-4;
}

.loading-text {
  @apply text-gray-600;
}

.error-state {
  @apply flex flex-col items-center justify-center h-64 text-red-600;
}

.error-icon {
  @apply w-8 h-8 mb-2;
}

.error-text {
  @apply mb-4;
}

.retry-btn {
  @apply bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg 
         transition-colors duration-200 inline-flex items-center;
}

.qr-content {
  @apply space-y-4;
}

.qr-image-container {
  @apply relative inline-block;
}

.qr-image {
  @apply w-64 h-64 border border-gray-300 rounded-lg;
}

.qr-actions {
  @apply absolute top-2 right-2 flex space-x-1;
}

.action-btn {
  @apply bg-white hover:bg-gray-50 text-gray-600 p-2 rounded-md shadow-sm 
         border border-gray-200 transition-colors duration-200;
}

.qr-info {
  @apply bg-gray-50 rounded-lg p-4 space-y-2;
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
  @apply flex items-start space-x-3 bg-green-50 border border-green-200 
         rounded-lg p-4;
}

.security-icon {
  @apply w-5 h-5 text-green-600 mt-0.5;
}

.security-text {
  @apply flex-1;
}

.security-title {
  @apply font-medium text-green-800 mb-1;
}

.security-desc {
  @apply text-sm text-green-700;
}

.instructions {
  @apply bg-blue-50 border border-blue-200 rounded-lg p-4;
}

.instructions-title {
  @apply font-medium text-blue-800 mb-3 flex items-center;
}

.instructions-list {
  @apply list-decimal list-inside space-y-1 text-sm text-blue-700;
}

.app-suggestions {
  @apply space-y-4;
}

.suggestions-title {
  @apply font-medium text-gray-800;
}

.apps-grid {
  @apply grid grid-cols-1 md:grid-cols-3 gap-3;
}

.app-card {
  @apply bg-white border border-gray-200 rounded-lg p-3 flex items-center 
         space-x-3 hover:border-gray-300 transition-colors duration-200;
}

.app-icon {
  @apply flex-shrink-0;
}

.app-info {
  @apply flex-1 min-w-0;
}

.app-name {
  @apply font-medium text-sm text-gray-900;
}

.app-platforms {
  @apply text-xs text-gray-500;
}

.app-download {
  @apply text-blue-600 hover:text-blue-700 p-1 rounded transition-colors duration-200;
}

/* Responsive adjustments */
@media (max-width: 640px) {
  .qr-image {
    @apply w-48 h-48;
  }
  
  .apps-grid {
    @apply grid-cols-1;
  }
}
</style>