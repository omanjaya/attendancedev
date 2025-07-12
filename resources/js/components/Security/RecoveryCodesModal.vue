<template>
  <div class="recovery-codes-modal">
    <div class="modal-backdrop" @click="handleBackdropClick">
      <div class="modal-content" @click.stop>
        <!-- Header -->
        <div class="modal-header">
          <div class="header-icon">
            <Icon name="key" class="w-8 h-8 text-amber-600" />
          </div>
          <h2 class="modal-title">
            {{ isNew ? 'Save Your Recovery Codes' : 'Recovery Codes' }}
          </h2>
          <p class="modal-subtitle">
            {{ isNew 
              ? 'Store these codes in a safe place. You can use them to access your account if you lose your phone.'
              : 'These are your backup codes for account recovery.'
            }}
          </p>
          <button @click="closeModal" class="close-button">
            <Icon name="x" class="w-5 h-5" />
          </button>
        </div>

        <!-- Warning Notice -->
        <div v-if="isNew" class="warning-notice">
          <Icon name="exclamation-triangle" class="w-5 h-5 text-amber-600" />
          <div>
            <h4 class="warning-title">Important Security Information</h4>
            <ul class="warning-list">
              <li>Each code can only be used once</li>
              <li>Store them in a secure location (password manager, safe, etc.)</li>
              <li>Don't share these codes with anyone</li>
              <li>If you lose these codes, you'll need to contact support</li>
            </ul>
          </div>
        </div>

        <!-- Recovery Codes Display -->
        <div class="codes-section">
          <div class="codes-header">
            <h3 class="codes-title">
              Recovery Codes ({{ codes.length }} {{ isNew ? 'generated' : 'remaining' }})
            </h3>
            <div class="codes-actions-top">
              <button @click="copyAllCodes" class="action-btn copy">
                <Icon name="copy" class="w-4 h-4" />
                Copy All
              </button>
              <button @click="printCodes" class="action-btn print">
                <Icon name="printer" class="w-4 h-4" />
                Print
              </button>
            </div>
          </div>

          <div class="codes-grid">
            <div 
              v-for="(code, index) in codes" 
              :key="index"
              class="code-item"
              :class="{ 'used': code.used }"
            >
              <div class="code-number">{{ index + 1 }}</div>
              <div class="code-value">
                <code class="code-text">{{ formatCode(code.code || code) }}</code>
                <button 
                  @click="copyCode(code.code || code)" 
                  class="copy-code-btn"
                  :title="'Copy code ' + (index + 1)"
                >
                  <Icon name="copy" class="w-3 h-3" />
                </button>
              </div>
              <div v-if="code.used" class="used-indicator">
                <Icon name="check" class="w-3 h-3" />
                <span class="used-text">Used</span>
              </div>
            </div>
          </div>

          <div v-if="codes.length === 0" class="no-codes">
            <Icon name="key" class="w-8 h-8 text-gray-400" />
            <p>No recovery codes available</p>
          </div>
        </div>

        <!-- Download Section -->
        <div v-if="isNew" class="download-section">
          <h4 class="download-title">Save These Codes</h4>
          <p class="download-description">
            Choose how you'd like to save your recovery codes:
          </p>
          
          <div class="download-options">
            <button @click="downloadAsText" class="download-btn">
              <Icon name="file-text" class="w-5 h-5" />
              <div class="download-info">
                <h5>Download as Text File</h5>
                <p>Plain text file for password managers</p>
              </div>
            </button>
            
            <button @click="downloadAsPDF" class="download-btn">
              <Icon name="file" class="w-5 h-5" />
              <div class="download-info">
                <h5>Download as PDF</h5>
                <p>Formatted document for printing</p>
              </div>
            </button>
          </div>
        </div>

        <!-- Footer Actions -->
        <div class="modal-footer">
          <div v-if="isNew" class="confirmation-section">
            <label class="confirmation-checkbox">
              <input
                v-model="confirmed"
                type="checkbox"
                class="checkbox-input"
              />
              <div class="checkbox-custom"></div>
              <span class="checkbox-label">
                I have saved these recovery codes in a secure location
              </span>
            </label>
          </div>

          <div class="footer-actions">
            <button 
              v-if="!isNew" 
              @click="closeModal" 
              class="btn-secondary"
            >
              Close
            </button>
            <button 
              v-else
              @click="closeModal" 
              class="btn-primary"
              :disabled="!confirmed"
            >
              <Icon name="check" class="w-4 h-4" />
              I've Saved My Codes
            </button>
          </div>
        </div>

        <!-- Print Template (hidden) -->
        <div ref="printTemplate" class="print-template">
          <div class="print-header">
            <h1>{{ serviceName }} - Recovery Codes</h1>
            <p>Account: {{ userEmail }}</p>
            <p>Generated: {{ new Date().toLocaleString() }}</p>
          </div>
          
          <div class="print-warning">
            <h2>⚠️ Important Security Information</h2>
            <ul>
              <li>Each code can only be used once</li>
              <li>Store this document in a secure location</li>
              <li>Don't share these codes with anyone</li>
              <li>Contact support if you lose access to all codes</li>
            </ul>
          </div>

          <div class="print-codes">
            <h2>Recovery Codes</h2>
            <div class="print-codes-grid">
              <div v-for="(code, index) in codes" :key="index" class="print-code">
                {{ index + 1 }}. {{ formatCode(code.code || code) }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useToast } from '@/composables/useToast'

// Props
const props = defineProps({
  codes: {
    type: Array,
    required: true
  },
  isNew: {
    type: Boolean,
    default: false
  },
  userEmail: {
    type: String,
    default: 'user@example.com'
  },
  serviceName: {
    type: String,
    default: 'Attendance System'
  }
})

// Emits
const emit = defineEmits(['close', 'downloaded'])

// Reactive data
const confirmed = ref(false)
const printTemplate = ref(null)

// Composables
const { toast } = useToast()

// Methods
const closeModal = () => {
  if (props.isNew && !confirmed.value) {
    toast.warning('Please confirm that you have saved your recovery codes')
    return
  }
  emit('close')
}

const handleBackdropClick = () => {
  if (!props.isNew) {
    closeModal()
  }
}

const formatCode = (code) => {
  if (!code) return ''
  // Format as XXXX-XXXX for better readability
  return code.replace(/(.{4})/g, '$1-').slice(0, -1)
}

const copyCode = async (code) => {
  try {
    await navigator.clipboard.writeText(code)
    toast.success('Code copied to clipboard')
  } catch (error) {
    // Fallback
    const textArea = document.createElement('textarea')
    textArea.value = code
    document.body.appendChild(textArea)
    textArea.select()
    document.execCommand('copy')
    document.body.removeChild(textArea)
    toast.success('Code copied to clipboard')
  }
}

const copyAllCodes = async () => {
  const allCodes = props.codes.map(code => code.code || code).join('\n')
  try {
    await navigator.clipboard.writeText(allCodes)
    toast.success('All codes copied to clipboard')
  } catch (error) {
    const textArea = document.createElement('textarea')
    textArea.value = allCodes
    document.body.appendChild(textArea)
    textArea.select()
    document.execCommand('copy')
    document.body.removeChild(textArea)
    toast.success('All codes copied to clipboard')
  }
}

const downloadAsText = () => {
  const content = [
    `${props.serviceName} - Recovery Codes`,
    `Account: ${props.userEmail}`,
    `Generated: ${new Date().toLocaleString()}`,
    '',
    'IMPORTANT: Store these codes securely!',
    '- Each code can only be used once',
    '- Don\'t share these codes with anyone',
    '- Contact support if you lose access to all codes',
    '',
    'Recovery Codes:',
    ...props.codes.map((code, index) => `${index + 1}. ${code.code || code}`)
  ].join('\n')

  const blob = new Blob([content], { type: 'text/plain' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = '2fa-recovery-codes.txt'
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  URL.revokeObjectURL(url)

  toast.success('Recovery codes downloaded')
  emit('downloaded')
}

const downloadAsPDF = () => {
  // Simple PDF generation using the print template
  const printWindow = window.open('', '_blank')
  const printContent = printTemplate.value.innerHTML
  
  printWindow.document.write(`
    <html>
      <head>
        <title>${props.serviceName} Recovery Codes</title>
        <style>
          body { font-family: Arial, sans-serif; margin: 20px; }
          .print-header { margin-bottom: 30px; }
          .print-header h1 { color: #1f2937; margin-bottom: 10px; }
          .print-warning { background: #fef3c7; border: 1px solid #f59e0b; padding: 15px; margin: 20px 0; border-radius: 5px; }
          .print-warning h2 { color: #92400e; margin-top: 0; }
          .print-codes h2 { color: #1f2937; margin-bottom: 15px; }
          .print-codes-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
          .print-code { font-family: monospace; font-size: 14px; padding: 5px; background: #f9fafb; border-radius: 3px; }
          @media print { 
            body { margin: 0; }
            .print-warning { background: #f9f9f9 !important; }
          }
        </style>
      </head>
      <body>${printContent}</body>
    </html>
  `)
  
  printWindow.document.close()
  printWindow.focus()
  
  setTimeout(() => {
    printWindow.print()
    printWindow.close()
  }, 250)

  toast.success('PDF opened for download/printing')
  emit('downloaded')
}

const printCodes = () => {
  downloadAsPDF()
}
</script>

<style scoped>
.recovery-codes-modal {
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
  @apply relative p-6 border-b border-gray-200 text-center;
}

.header-icon {
  @apply flex justify-center mb-4;
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

.warning-notice {
  @apply mx-6 mt-6 p-4 bg-amber-50 border border-amber-200 rounded-lg 
         flex items-start space-x-3;
}

.warning-title {
  @apply font-medium text-amber-900 mb-2;
}

.warning-list {
  @apply text-sm text-amber-800 space-y-1 list-disc list-inside;
}

.codes-section {
  @apply p-6 space-y-4;
}

.codes-header {
  @apply flex items-center justify-between;
}

.codes-title {
  @apply text-lg font-semibold text-gray-900;
}

.codes-actions-top {
  @apply flex space-x-2;
}

.action-btn {
  @apply inline-flex items-center space-x-1 px-3 py-1 text-sm font-medium 
         border border-gray-300 rounded-lg hover:bg-gray-50 
         transition-colors duration-200;
}

.action-btn.copy {
  @apply text-blue-700 border-blue-300 hover:bg-blue-50;
}

.action-btn.print {
  @apply text-green-700 border-green-300 hover:bg-green-50;
}

.codes-grid {
  @apply grid grid-cols-1 sm:grid-cols-2 gap-3;
}

.code-item {
  @apply flex items-center space-x-3 p-3 border border-gray-200 rounded-lg 
         bg-gray-50 transition-all duration-200;
}

.code-item.used {
  @apply bg-red-50 border-red-200 opacity-60;
}

.code-number {
  @apply w-6 h-6 bg-blue-600 text-white text-sm font-medium rounded-full 
         flex items-center justify-center flex-shrink-0;
}

.code-item.used .code-number {
  @apply bg-red-600;
}

.code-value {
  @apply flex-1 flex items-center space-x-2;
}

.code-text {
  @apply font-mono text-sm text-gray-900;
}

.code-item.used .code-text {
  @apply line-through text-gray-500;
}

.copy-code-btn {
  @apply text-gray-400 hover:text-gray-600 transition-colors duration-200;
}

.used-indicator {
  @apply flex items-center space-x-1 text-xs text-red-600 font-medium;
}

.no-codes {
  @apply text-center py-8 text-gray-500 space-y-2;
}

.download-section {
  @apply mx-6 mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg space-y-4;
}

.download-title {
  @apply text-lg font-semibold text-blue-900;
}

.download-description {
  @apply text-sm text-blue-800;
}

.download-options {
  @apply space-y-2;
}

.download-btn {
  @apply w-full flex items-start space-x-3 p-3 bg-white border border-blue-200 
         rounded-lg hover:border-blue-300 hover:bg-blue-50 
         transition-all duration-200 text-left;
}

.download-info h5 {
  @apply font-medium text-gray-900;
}

.download-info p {
  @apply text-sm text-gray-600 mt-1;
}

.modal-footer {
  @apply p-6 border-t border-gray-200 space-y-4;
}

.confirmation-section {
  @apply flex items-center justify-center;
}

.confirmation-checkbox {
  @apply flex items-start space-x-3 cursor-pointer;
}

.checkbox-input {
  @apply sr-only;
}

.checkbox-custom {
  @apply w-4 h-4 border-2 border-gray-300 rounded flex-shrink-0 mt-0.5
         transition-all duration-200;
}

.checkbox-input:checked + .checkbox-custom {
  @apply bg-blue-600 border-blue-600;
}

.checkbox-input:checked + .checkbox-custom::after {
  content: '✓';
  @apply text-white text-xs flex items-center justify-center;
}

.checkbox-label {
  @apply text-sm text-gray-700 font-medium;
}

.footer-actions {
  @apply flex justify-center;
}

.btn-primary {
  @apply bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 
         rounded-lg transition-colors duration-200 inline-flex items-center 
         space-x-2 disabled:opacity-50 disabled:cursor-not-allowed;
}

.btn-secondary {
  @apply bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 
         font-medium py-2 px-6 rounded-lg transition-colors duration-200;
}

.print-template {
  @apply hidden;
}

/* Mobile optimizations */
@media (max-width: 640px) {
  .modal-content {
    @apply m-2;
  }
  
  .codes-grid {
    @apply grid-cols-1;
  }
  
  .codes-header {
    @apply flex-col items-start space-y-2;
  }
  
  .codes-actions-top {
    @apply w-full justify-end;
  }
}

/* Print styles */
@media print {
  .recovery-codes-modal {
    @apply hidden;
  }
}
</style>