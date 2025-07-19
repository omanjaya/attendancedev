<template>
  <div class="qr-modal">
    <div class="modal-backdrop" @click="closeModal">
      <div class="modal-content" @click.stop>
        <!-- Header -->
        <div class="modal-header">
          <h2 class="modal-title">
            Authenticator QR Code
          </h2>
          <p class="modal-subtitle">
            Scan this QR code with your authenticator app to add this account
          </p>
          <button class="close-button" @click="closeModal">
            <Icon name="x" class="h-5 w-5" />
          </button>
        </div>

        <!-- QR Code Section -->
        <div class="qr-section">
          <div class="qr-container">
            <div v-if="qrCode" class="qr-display">
              <div class="qr-code" v-html="sanitizedQrCode" />
              <p class="qr-instructions">
                Open your authenticator app and scan this QR code
              </p>
            </div>
            <div v-else class="qr-loading">
              <div class="spinner" />
              <p>Loading QR code...</p>
            </div>
          </div>

          <!-- Manual Entry Alternative -->
          <div class="manual-entry">
            <div class="divider">
              <span class="divider-text">Can't scan the code?</span>
            </div>

            <div class="manual-content">
              <h3 class="manual-title">
                Enter code manually
              </h3>
              <p class="manual-description">
                You can manually enter this secret key in your authenticator app:
              </p>

              <div class="secret-display">
                <div class="secret-field">
                  <label class="secret-label">Secret Key:</label>
                  <div class="secret-value">
                    <code class="secret-code">{{ maskedSecret }}</code>
                    <button
                      class="visibility-btn"
                      :title="secretVisible ? 'Hide secret' : 'Show secret'"
                      @click="toggleSecretVisibility"
                    >
                      <Icon :name="secretVisible ? 'eye-off' : 'eye'" class="h-4 w-4" />
                    </button>
                    <button class="copy-btn" title="Copy to clipboard" @click="copySecret">
                      <Icon name="copy" class="h-4 w-4" />
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
                <h4 class="steps-title">
                  Manual setup steps:
                </h4>
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
            <Icon name="info" class="h-4 w-4 text-blue-600" />
            <p class="footer-text">
              This QR code contains your secret key. Don't share it with anyone.
            </p>
          </div>

          <div class="footer-actions">
            <button class="btn-secondary" :disabled="!qrCode" @click="downloadQR">
              <Icon name="download" class="h-4 w-4" />
              Download QR Code
            </button>
            <button class="btn-primary" @click="closeModal">
              <Icon name="check" class="h-4 w-4" />
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
import { HTMLSanitizer } from '@/utils/xssAudit'
import { useErrorTrackingForAuth } from '@/composables/useErrorTracking'
import { withAsyncErrorBoundary, getUserFriendlyMessage } from '@/utils/errorHandlingPatterns'

// Props
const props = defineProps({
  qrCode: {
    type: String,
    required: true,
  },
  user: {
    type: Object,
    default: () => ({ email: 'user@example.com' }),
  },
  serviceName: {
    type: String,
    default: 'Attendance System',
  },
  secretKey: {
    type: String,
    default: '',
  },
})

// Emits
const emit = defineEmits(['close'])

// Reactive data
const secretVisible = ref(false)

// Composables
const { toast } = useToast()
const errorTracking = useErrorTrackingForAuth()

// Computed
const sanitizedQrCode = computed(() => {
  return props.qrCode ? HTMLSanitizer.sanitize(props.qrCode) : ''
})

const maskedSecret = computed(() => {
  if (!props.secretKey) {return '••••••••••••••••'}

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
    const error = new Error('Secret key not available')
    errorTracking.captureError(error, {
      action: 'copy_secret_unavailable',
      metadata: {
        hasSecretKey: !!props.secretKey,
        propsProvided: Object.keys(props),
      },
    })
    toast.error('Secret key not available')
    return
  }

  return withAsyncErrorBoundary(
    async () => {
      errorTracking.addBreadcrumb('Attempting to copy secret key', 'user_action')

      try {
        await navigator.clipboard.writeText(props.secretKey)
        errorTracking.addBreadcrumb('Secret key copied via Clipboard API', 'user_action')
      } catch (clipboardError) {
        // Fallback for older browsers
        errorTracking.addBreadcrumb('Using fallback copy method', 'user_action')

        const textArea = document.createElement('textarea')
        textArea.value = props.secretKey
        textArea.style.position = 'fixed'
        textArea.style.opacity = '0'
        document.body.appendChild(textArea)
        textArea.select()

        const success = document.execCommand('copy')
        document.body.removeChild(textArea)

        if (!success) {
          throw new Error('Fallback copy method failed')
        }

        errorTracking.addBreadcrumb('Fallback copy completed successfully', 'user_action')
      }

      toast.success('Secret key copied to clipboard')
    },
    {
      operationName: 'copy_secret_key',
      component: 'QRCodeModal',
      category: 'user_input',
      additionalData: {
        hasClipboardAPI: !!navigator.clipboard,
        secretKeyLength: props.secretKey?.length || 0,
      },
      onError: (error, context) => {
        const friendlyMessage = getUserFriendlyMessage(error, context)
        toast.error(friendlyMessage)
      },
    }
  )
}

const downloadQR = () => {
  if (!props.qrCode) {
    const error = new Error('QR code not available')
    errorTracking.captureError(error, {
      action: 'download_qr_unavailable',
      metadata: {
        hasQrCode: !!props.qrCode,
        propsProvided: Object.keys(props),
      },
    })
    toast.error('QR code not available')
    return
  }

  return (
    errorTracking.withErrorBoundary(
      () => {
        errorTracking.addBreadcrumb('Starting QR code download', 'user_action', {
          qrCodeType: typeof props.qrCode,
          qrCodeLength: props.qrCode?.length || 0,
        })

        // Extract SVG from the QR code data
        const parser = new DOMParser()
        const doc = parser.parseFromString(props.qrCode, 'image/svg+xml')
        const svgElement = doc.querySelector('svg')

        if (!svgElement) {
          // Handle base64 data URLs
          errorTracking.addBreadcrumb('Using direct download for base64 QR code', 'user_action')

          const link = document.createElement('a')
          link.href = props.qrCode
          link.download = '2fa-qr-code.png'
          document.body.appendChild(link)
          link.click()
          document.body.removeChild(link)

          toast.success('QR code downloaded')
          errorTracking.addBreadcrumb('QR code downloaded successfully (direct)', 'user_action')
          return
        }

        errorTracking.addBreadcrumb('Converting SVG QR code to canvas', 'user_action')

        // Convert SVG to canvas and download
        const canvas = document.createElement('canvas')
        const ctx = canvas.getContext('2d')

        if (!ctx) {
          throw new Error('Could not get canvas context')
        }

        const img = new Image()

        // Set canvas size
        canvas.width = 300
        canvas.height = 300

        // Create blob URL from SVG
        const svgData = new XMLSerializer().serializeToString(svgElement)
        const svgBlob = new Blob([svgData], { type: 'image/svg+xml;charset=utf-8' })
        const url = URL.createObjectURL(svgBlob)

        img.onload = () => {
          try {
            // Clear canvas with white background
            ctx.fillStyle = '#ffffff'
            ctx.fillRect(0, 0, canvas.width, canvas.height)

            // Draw QR code
            ctx.drawImage(img, 0, 0, canvas.width, canvas.height)

            // Convert to blob and download
            canvas.toBlob((blob) => {
              if (!blob) {
                throw new Error('Failed to create blob from canvas')
              }

              const link = document.createElement('a')
              link.href = URL.createObjectURL(blob)
              link.download = '2fa-qr-code.png'
              document.body.appendChild(link)
              link.click()
              document.body.removeChild(link)

              // Clean up
              URL.revokeObjectURL(url)

              toast.success('QR code downloaded')
              errorTracking.addBreadcrumb('QR code downloaded successfully (canvas)', 'user_action')
            })
          } catch (canvasError) {
            URL.revokeObjectURL(url)
            throw canvasError
          }
        }

        img.onerror = () => {
          URL.revokeObjectURL(url)
          throw new Error('Failed to load SVG image')
        }

        img.src = url
      },
      {
        action: 'download_qr_failed',
        metadata: {
          qrCodeType: typeof props.qrCode,
          hasCanvas: !!document.createElement('canvas').getContext,
          userAgent: navigator.userAgent,
        },
      }
    ) || Promise.resolve()
  )
}

// Lifecycle
onMounted(() => {
  // Set focus for accessibility
  setTimeout(() => {
    const modal = document.querySelector('.modal-content')
    if (modal) {modal.focus()}
  }, 100)
})
</script>

<style scoped>
.qr-modal {
  @apply fixed inset-0 z-50 overflow-y-auto;
}

.modal-backdrop {
  @apply fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 p-4;
  animation: fadeIn 0.3s ease-out;
}

.modal-content {
  @apply w-full max-w-md rounded-lg bg-white shadow-xl;
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
  @apply relative border-b border-gray-200 p-6 text-center;
}

.modal-title {
  @apply text-xl font-bold text-gray-900;
}

.modal-subtitle {
  @apply mt-2 text-sm text-gray-600;
}

.close-button {
  @apply absolute right-4 top-4 text-gray-400 transition-colors duration-200 hover:text-gray-600;
}

.qr-section {
  @apply space-y-6 p-6;
}

.qr-container {
  @apply text-center;
}

.qr-display {
  @apply space-y-4;
}

.qr-code {
  @apply inline-block rounded-lg border-2 border-gray-300 bg-white p-4;
}

.qr-instructions {
  @apply text-sm text-gray-600;
}

.qr-loading {
  @apply flex flex-col items-center space-y-4 py-8;
}

.spinner {
  @apply h-8 w-8 animate-spin rounded-full border-b-2 border-blue-600;
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
  @apply space-y-4 rounded-lg bg-gray-50 p-4;
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
  @apply flex-1 rounded border border-gray-300 bg-white p-2 font-mono text-sm;
}

.visibility-btn,
.copy-btn {
  @apply rounded border border-gray-300 p-2 text-gray-400 transition-colors duration-200 hover:text-gray-600;
}

.account-details {
  @apply space-y-2;
}

.detail-field {
  @apply flex items-center space-x-2 text-sm;
}

.detail-label {
  @apply min-w-0 flex-shrink-0 font-medium text-gray-700;
}

.detail-value {
  @apply font-mono text-gray-900;
}

.manual-steps {
  @apply space-y-2;
}

.steps-title {
  @apply text-sm font-medium text-gray-700;
}

.steps-list {
  @apply list-inside list-decimal space-y-1 text-sm text-gray-600;
}

.modal-footer {
  @apply space-y-4 border-t border-gray-200 p-6;
}

.footer-info {
  @apply flex items-start space-x-2 rounded-lg border border-blue-200 bg-blue-50 p-3;
}

.footer-text {
  @apply text-sm text-blue-800;
}

.footer-actions {
  @apply flex space-x-3;
}

.btn-primary {
  @apply inline-flex flex-1 items-center justify-center space-x-2 rounded-lg bg-blue-600 px-4 py-2 font-medium text-white transition-colors duration-200 hover:bg-blue-700;
}

.btn-secondary {
  @apply inline-flex flex-1 items-center justify-center space-x-2 rounded-lg border border-gray-300 bg-white px-4 py-2 font-medium text-gray-700 transition-colors duration-200 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50;
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
    @apply flex-col items-stretch space-x-0 space-y-2;
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
