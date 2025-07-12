<template>
  <div class="qr-modal">
    <div class="modal-backdrop" @click="closeModal">
      <div class="modal-content" @click.stop>
        <!-- Header -->
        <div class="modal-header">
          <h2 class="modal-title">Authenticator QR Code</h2>
          <p class="modal-subtitle">
            Scan this QR code with your authenticator app to add this account
          </p>
          <button @click="closeModal" class="close-button">
            <Icon name="x" class="w-5 h-5" />
          </button>
        </div>

        <!-- QR Code Section -->
        <div class="qr-section">
          <div class="qr-container">
            <div v-if="qrCode" class="qr-display">
              <div class="qr-code" v-html="qrCode"></div>
              <p class="qr-instructions">
                Open your authenticator app and scan this QR code
              </p>
            </div>
            <div v-else class="qr-loading">
              <div class="spinner"></div>
              <p>Loading QR code...</p>
            </div>
          </div>

          <!-- Manual Entry Alternative -->
          <div class="manual-entry">
            <div class="divider">
              <span class="divider-text">Can't scan the code?</span>
            </div>

            <div class="manual-content">
              <h3 class="manual-title">Enter code manually</h3>
              <p class="manual-description">
                You can manually enter this secret key in your authenticator app:
              </p>
              
              <div class="secret-display">
                <div class="secret-field">
                  <label class="secret-label">Secret Key:</label>
                  <div class="secret-value">
                    <code class="secret-code">{{ maskedSecret }}</code>
                    <button 
                      @click="toggleSecretVisibility" 
                      class="visibility-btn"
                      :title="secretVisible ? 'Hide secret' : 'Show secret'"
                    >
                      <Icon :name="secretVisible ? 'eye-off' : 'eye'" class="w-4 h-4" />
                    </button>
                    <button @click="copySecret" class="copy-btn" title="Copy to clipboard">
                      <Icon name="copy" class="w-4 h-4" />
                    </button>
                  </div>
                </div>

                <div class="account-details">
                  <div class="detail-field">
                    <label class="detail-label">Account:</label>
                    <span class="detail-value">{{ user.email }}</span>
                  </div>
                  <div class="detail-field">
                    <label class="detail-label">Service:</label>
                    <span class="detail-value">{{ serviceName }}</span>
                  </div>
                </div>
              </div>

              <div class="manual-steps">
                <h4 class="steps-title">Manual setup steps:</h4>
                <ol class="steps-list">
                  <li>Open your authenticator app</li>
                  <li>Select "Add account" or "+" button</li>
                  <li>Choose "Enter setup key manually"</li>
                  <li>Enter the account name and secret key above</li>
                  <li>Save the account</li>
                </ol>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer Actions -->
        <div class="modal-footer">
          <div class="footer-info">
            <Icon name="info" class="w-4 h-4 text-blue-600" />
            <p class="footer-text">
              This QR code contains your secret key. Don't share it with anyone.
            </p>
          </div>
          
          <div class="footer-actions">
            <button @click="downloadQR" class="btn-secondary" :disabled="!qrCode">
              <Icon name="download" class="w-4 h-4" />
              Download QR Code
            </button>
            <button @click="closeModal" class="btn-primary">
              <Icon name="check" class="w-4 h-4" />
              Done
            </button>
          </div>
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
  qrCode: {
    type: String,
    required: true
  },
  user: {
    type: Object,
    default: () => ({ email: 'user@example.com' })
  },
  serviceName: {
    type: String,
    default: 'Attendance System'
  },
  secretKey: {
    type: String,
    default: ''
  }
})

// Emits
const emit = defineEmits(['close'])

// Reactive data
const secretVisible = ref(false)

// Composables
const { toast } = useToast()

// Computed
const maskedSecret = computed(() => {
  if (!props.secretKey) return '••••••••••••••••'
  
  if (secretVisible.value) {
    return props.secretKey
  }
  
  return '••••••••••••••••'
})

// Methods
const closeModal = () => {
  emit('close')
}

const toggleSecretVisibility = () => {
  secretVisible.value = !secretVisible.value
}

const copySecret = async () => {
  if (!props.secretKey) {
    toast.error('Secret key not available')
    return
  }

  try {
    await navigator.clipboard.writeText(props.secretKey)
    toast.success('Secret key copied to clipboard')
  } catch (error) {
    // Fallback for older browsers
    const textArea = document.createElement('textarea')
    textArea.value = props.secretKey
    textArea.style.position = 'fixed'
    textArea.style.opacity = '0'
    document.body.appendChild(textArea)
    textArea.select()
    document.execCommand('copy')
    document.body.removeChild(textArea)
    toast.success('Secret key copied to clipboard')
  }
}

const downloadQR = () => {
  if (!props.qrCode) {
    toast.error('QR code not available')
    return
  }

  try {
    // Extract SVG from the QR code data
    const parser = new DOMParser()
    const doc = parser.parseFromString(props.qrCode, 'image/svg+xml')
    const svgElement = doc.querySelector('svg')
    
    if (!svgElement) {
      // Handle base64 data URLs
      const link = document.createElement('a')
      link.href = props.qrCode
      link.download = '2fa-qr-code.png'
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      toast.success('QR code downloaded')
      return
    }

    // Convert SVG to canvas and download
    const canvas = document.createElement('canvas')
    const ctx = canvas.getContext('2d')
    const img = new Image()
    
    // Set canvas size
    canvas.width = 300
    canvas.height = 300
    
    // Create blob URL from SVG
    const svgData = new XMLSerializer().serializeToString(svgElement)
    const svgBlob = new Blob([svgData], { type: 'image/svg+xml;charset=utf-8' })
    const url = URL.createObjectURL(svgBlob)
    
    img.onload = () => {
      // Clear canvas with white background
      ctx.fillStyle = '#ffffff'
      ctx.fillRect(0, 0, canvas.width, canvas.height)
      
      // Draw QR code
      ctx.drawImage(img, 0, 0, canvas.width, canvas.height)
      
      // Convert to blob and download
      canvas.toBlob((blob) => {
        const link = document.createElement('a')
        link.href = URL.createObjectURL(blob)
        link.download = '2fa-qr-code.png'
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        
        // Clean up
        URL.revokeObjectURL(url)
        toast.success('QR code downloaded')
      })
    }
    
    img.onerror = () => {
      URL.revokeObjectURL(url)
      toast.error('Failed to download QR code')
    }
    
    img.src = url
  } catch (error) {
    console.error('Download error:', error)
    toast.error('Failed to download QR code')
  }
}

// Lifecycle
onMounted(() => {
  // Set focus for accessibility
  setTimeout(() => {
    const modal = document.querySelector('.modal-content')
    if (modal) modal.focus()
  }, 100)
})
</script>

<style scoped>
.qr-modal {
  @apply fixed inset-0 z-50 overflow-y-auto;
}

.modal-backdrop {
  @apply fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4;
  animation: fadeIn 0.3s ease-out;
}

.modal-content {
  @apply bg-white rounded-lg shadow-xl max-w-md w-full;
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
  @apply relative p-6 border-b border-gray-200 text-center;
}

.modal-title {
  @apply text-xl font-bold text-gray-900;
}

.modal-subtitle {
  @apply text-gray-600 mt-2 text-sm;
}

.close-button {
  @apply absolute top-4 right-4 text-gray-400 hover:text-gray-600 
         transition-colors duration-200;
}

.qr-section {
  @apply p-6 space-y-6;
}

.qr-container {
  @apply text-center;
}

.qr-display {
  @apply space-y-4;
}

.qr-code {
  @apply inline-block p-4 bg-white border-2 border-gray-300 rounded-lg;
}

.qr-instructions {
  @apply text-sm text-gray-600;
}

.qr-loading {
  @apply flex flex-col items-center space-y-4 py-8;
}

.spinner {
  @apply animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600;
}

.manual-entry {
  @apply space-y-4;
}

.divider {
  @apply relative;
}

.divider::before {
  @apply absolute inset-0 flex items-center;
  content: '';
  border-top: 1px solid #e5e7eb;
}

.divider-text {
  @apply relative bg-white px-3 text-sm text-gray-500;
}

.manual-content {
  @apply space-y-4;
}

.manual-title {
  @apply text-lg font-semibold text-gray-900;
}

.manual-description {
  @apply text-sm text-gray-600;
}

.secret-display {
  @apply space-y-4 p-4 bg-gray-50 rounded-lg;
}

.secret-field {
  @apply space-y-2;
}

.secret-label {
  @apply block text-sm font-medium text-gray-700;
}

.secret-value {
  @apply flex items-center space-x-2;
}

.secret-code {
  @apply flex-1 p-2 bg-white border border-gray-300 rounded font-mono text-sm;
}

.visibility-btn,
.copy-btn {
  @apply p-2 text-gray-400 hover:text-gray-600 border border-gray-300 
         rounded transition-colors duration-200;
}

.account-details {
  @apply space-y-2;
}

.detail-field {
  @apply flex items-center space-x-2 text-sm;
}

.detail-label {
  @apply font-medium text-gray-700 min-w-0 flex-shrink-0;
}

.detail-value {
  @apply text-gray-900 font-mono;
}

.manual-steps {
  @apply space-y-2;
}

.steps-title {
  @apply text-sm font-medium text-gray-700;
}

.steps-list {
  @apply list-decimal list-inside space-y-1 text-sm text-gray-600;
}

.modal-footer {
  @apply p-6 border-t border-gray-200 space-y-4;
}

.footer-info {
  @apply flex items-start space-x-2 p-3 bg-blue-50 border border-blue-200 rounded-lg;
}

.footer-text {
  @apply text-sm text-blue-800;
}

.footer-actions {
  @apply flex space-x-3;
}

.btn-primary {
  @apply flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 
         rounded-lg transition-colors duration-200 inline-flex items-center 
         justify-center space-x-2;
}

.btn-secondary {
  @apply flex-1 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 
         font-medium py-2 px-4 rounded-lg transition-colors duration-200 
         inline-flex items-center justify-center space-x-2 
         disabled:opacity-50 disabled:cursor-not-allowed;
}

/* Mobile optimizations */
@media (max-width: 640px) {
  .modal-content {
    @apply m-2;
  }
  
  .footer-actions {
    @apply flex-col space-x-0 space-y-2;
  }
  
  .secret-value {
    @apply flex-col space-x-0 space-y-2 items-stretch;
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