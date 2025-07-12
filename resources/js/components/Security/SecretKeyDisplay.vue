<template>
  <div class="secret-key-display">
    <!-- Header -->
    <div class="header">
      <h4 class="title">
        <Icon name="key" class="w-4 h-4 mr-2" />
        Manual Entry Key
      </h4>
      <p class="subtitle">
        Use this key if you can't scan the QR code
      </p>
    </div>

    <!-- Secret Key -->
    <div class="secret-container">
      <div class="secret-key-wrapper">
        <!-- Key Display -->
        <div class="key-display" :class="{ 'masked': masked }">
          <code class="key-text">
            {{ displayKey }}
          </code>
        </div>

        <!-- Actions -->
        <div class="actions">
          <button 
            @click="toggleMask" 
            class="action-btn"
            :title="masked ? 'Show key' : 'Hide key'"
          >
            <Icon :name="masked ? 'eye' : 'eye-off'" class="w-4 h-4" />
          </button>
          
          <button 
            @click="copyKey" 
            class="action-btn"
            title="Copy to clipboard"
            :disabled="copying"
          >
            <Icon v-if="copying" name="check" class="w-4 h-4 text-green-600" />
            <Icon v-else name="copy" class="w-4 h-4" />
          </button>
        </div>
      </div>

      <!-- Formatted Display -->
      <div v-if="!masked && formattedKey" class="formatted-display">
        <p class="format-label">Formatted for easier reading:</p>
        <code class="formatted-key">{{ formattedKey }}</code>
      </div>
    </div>

    <!-- Instructions -->
    <div class="instructions">
      <h5 class="instructions-title">How to enter manually:</h5>
      <ol class="instructions-list">
        <li>Open your authenticator app</li>
        <li>Select "Add account" or "+"</li>
        <li>Choose "Enter key manually" or "Enter setup key"</li>
        <li>Enter the key above exactly as shown</li>
        <li>Set account name to: <strong>{{ accountName }}</strong></li>
        <li>Ensure "Time-based" is selected</li>
      </ol>
    </div>

    <!-- Security Warning -->
    <div class="security-warning">
      <Icon name="shield-alert" class="warning-icon" />
      <div class="warning-content">
        <p class="warning-title">Keep this key secure!</p>
        <p class="warning-text">
          This secret key gives access to your account. Don't share it with anyone.
        </p>
      </div>
    </div>

    <!-- Key Properties -->
    <div class="key-properties">
      <div class="property">
        <span class="property-label">Key Length:</span>
        <span class="property-value">{{ secretKey.length }} characters</span>
      </div>
      <div class="property">
        <span class="property-label">Encoding:</span>
        <span class="property-value">Base32</span>
      </div>
      <div class="property">
        <span class="property-label">Algorithm:</span>
        <span class="property-value">HMAC-SHA1</span>
      </div>
      <div class="property">
        <span class="property-label">Time Step:</span>
        <span class="property-value">30 seconds</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useToast } from '@/composables/useToast'

// Props
const props = defineProps({
  secretKey: {
    type: String,
    required: true
  },
  accountName: {
    type: String,
    default: 'Attendance System'
  },
  masked: {
    type: Boolean,
    default: true
  }
})

// Emits
const emit = defineEmits(['keyRevealed', 'keyCopied'])

// Reactive data
const masked = ref(props.masked)
const copying = ref(false)

// Composables
const { toast } = useToast()

// Computed
const displayKey = computed(() => {
  if (masked.value) {
    return '•'.repeat(props.secretKey.length)
  }
  return props.secretKey
})

const formattedKey = computed(() => {
  if (masked.value || !props.secretKey) return ''
  
  // Format as groups of 4 characters separated by spaces
  return props.secretKey.replace(/(.{4})/g, '$1 ').trim()
})

// Methods
const toggleMask = () => {
  masked.value = !masked.value
  
  if (!masked.value) {
    emit('keyRevealed')
    
    // Analytics: Track key reveal for security monitoring
    if (window.analytics) {
      window.analytics.track('2FA Key Revealed', {
        timestamp: new Date().toISOString(),
        userAgent: navigator.userAgent
      })
    }
  }
}

const copyKey = async () => {
  if (copying.value) return
  
  copying.value = true
  
  try {
    await navigator.clipboard.writeText(props.secretKey)
    
    toast.success('Secret key copied to clipboard')
    emit('keyCopied')
    
    // Auto-hide the checkmark after 2 seconds
    setTimeout(() => {
      copying.value = false
    }, 2000)
    
    // Analytics: Track key copy for security monitoring
    if (window.analytics) {
      window.analytics.track('2FA Key Copied', {
        timestamp: new Date().toISOString(),
        keyLength: props.secretKey.length
      })
    }
    
  } catch (error) {
    // Fallback for older browsers
    try {
      const textArea = document.createElement('textarea')
      textArea.value = props.secretKey
      textArea.style.position = 'fixed'
      textArea.style.opacity = '0'
      document.body.appendChild(textArea)
      textArea.select()
      document.execCommand('copy')
      document.body.removeChild(textArea)
      
      toast.success('Secret key copied to clipboard')
      emit('keyCopied')
      
      setTimeout(() => {
        copying.value = false
      }, 2000)
      
    } catch (fallbackError) {
      toast.error('Failed to copy secret key')
      copying.value = false
    }
  }
}

const selectKey = () => {
  if (masked.value) {
    toggleMask()
  }
  
  // Select the text for easy copying
  const keyElement = document.querySelector('.key-text')
  if (keyElement && window.getSelection) {
    const selection = window.getSelection()
    const range = document.createRange()
    range.selectNodeContents(keyElement)
    selection.removeAllRanges()
    selection.addRange(range)
  }
}
</script>

<style scoped>
.secret-key-display {
  @apply space-y-4;
}

.header {
  @apply text-center;
}

.title {
  @apply text-lg font-semibold text-gray-800 flex items-center justify-center mb-1;
}

.subtitle {
  @apply text-sm text-gray-600;
}

.secret-container {
  @apply bg-gray-50 border border-gray-200 rounded-lg p-4 space-y-3;
}

.secret-key-wrapper {
  @apply flex items-center space-x-3;
}

.key-display {
  @apply flex-1 bg-white border border-gray-300 rounded-md px-3 py-2;
}

.key-display.masked {
  @apply bg-gray-100;
}

.key-text {
  @apply font-mono text-sm break-all select-all cursor-pointer;
  font-family: 'SF Mono', 'Monaco', 'Inconsolata', 'Roboto Mono', monospace;
}

.actions {
  @apply flex space-x-1;
}

.action-btn {
  @apply bg-white hover:bg-gray-50 text-gray-600 hover:text-gray-800 
         p-2 rounded-md border border-gray-300 transition-all duration-200
         disabled:opacity-50 disabled:cursor-not-allowed;
}

.action-btn:hover:not(:disabled) {
  @apply shadow-sm;
}

.formatted-display {
  @apply space-y-2;
}

.format-label {
  @apply text-xs font-medium text-gray-600;
}

.formatted-key {
  @apply font-mono text-sm bg-white border border-gray-200 rounded px-3 py-2 
         block select-all;
  font-family: 'SF Mono', 'Monaco', 'Inconsolata', 'Roboto Mono', monospace;
  letter-spacing: 0.5px;
}

.instructions {
  @apply bg-blue-50 border border-blue-200 rounded-lg p-4;
}

.instructions-title {
  @apply font-medium text-blue-800 mb-2;
}

.instructions-list {
  @apply list-decimal list-inside space-y-1 text-sm text-blue-700;
}

.security-warning {
  @apply bg-amber-50 border border-amber-200 rounded-lg p-4 flex items-start space-x-3;
}

.warning-icon {
  @apply w-5 h-5 text-amber-600 mt-0.5 flex-shrink-0;
}

.warning-content {
  @apply flex-1;
}

.warning-title {
  @apply font-medium text-amber-800 mb-1;
}

.warning-text {
  @apply text-sm text-amber-700;
}

.key-properties {
  @apply bg-white border border-gray-200 rounded-lg p-4 space-y-2;
}

.property {
  @apply flex justify-between text-sm;
}

.property-label {
  @apply font-medium text-gray-600;
}

.property-value {
  @apply text-gray-900 font-mono;
}

/* Mobile optimizations */
@media (max-width: 640px) {
  .secret-key-wrapper {
    @apply flex-col space-x-0 space-y-3;
  }
  
  .key-display {
    @apply w-full;
  }
  
  .actions {
    @apply justify-center;
  }
  
  .property {
    @apply flex-col space-y-1;
  }
  
  .property-label {
    @apply text-xs;
  }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
  .key-display {
    @apply border-2 border-gray-900;
  }
  
  .action-btn {
    @apply border-2;
  }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .secret-container {
    @apply bg-gray-800 border-gray-700;
  }
  
  .key-display {
    @apply bg-gray-900 border-gray-600 text-gray-100;
  }
  
  .key-display.masked {
    @apply bg-gray-700;
  }
  
  .formatted-key {
    @apply bg-gray-900 border-gray-600 text-gray-100;
  }
}
</style>