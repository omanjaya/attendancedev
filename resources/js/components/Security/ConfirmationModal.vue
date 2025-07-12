<template>
  <div class="confirmation-modal">
    <div class="modal-backdrop" @click="handleBackdropClick">
      <div class="modal-content" @click.stop>
        <!-- Header -->
        <div class="modal-header">
          <div class="header-icon" :class="danger ? 'danger' : 'info'">
            <Icon :name="danger ? 'exclamation-triangle' : 'help-circle'" class="w-8 h-8" />
          </div>
          <h2 class="modal-title">{{ title }}</h2>
          <p class="modal-message">{{ message }}</p>
        </div>

        <!-- Password Confirmation Form -->
        <div class="confirmation-form">
          <form @submit.prevent="handleConfirm">
            <div class="form-group">
              <label for="password" class="form-label">
                Enter your password to confirm
                <span class="required">*</span>
              </label>
              <div class="password-input-wrapper">
                <input
                  id="password"
                  ref="passwordInput"
                  v-model="password"
                  :type="showPassword ? 'text' : 'password'"
                  class="password-input"
                  :class="{ 'error': errors.password }"
                  placeholder="Enter your current password"
                  required
                  :disabled="loading"
                  @input="clearErrors"
                />
                <button
                  type="button"
                  @click="togglePasswordVisibility"
                  class="password-toggle"
                  :disabled="loading"
                >
                  <Icon :name="showPassword ? 'eye-off' : 'eye'" class="w-4 h-4" />
                </button>
              </div>
              <div v-if="errors.password" class="error-message">
                <Icon name="x-circle" class="w-4 h-4" />
                {{ errors.password }}
              </div>
            </div>

            <!-- Additional Warning for Dangerous Actions -->
            <div v-if="danger" class="danger-warning">
              <Icon name="exclamation-triangle" class="w-5 h-5 text-red-600" />
              <div>
                <h4 class="warning-title">This action cannot be undone</h4>
                <p class="warning-text">
                  Please make sure you understand the consequences before proceeding.
                </p>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="form-actions">
              <button 
                type="button" 
                @click="handleCancel" 
                class="btn-secondary"
                :disabled="loading"
              >
                Cancel
              </button>
              <button 
                type="submit" 
                class="btn-primary"
                :class="{ 'btn-danger': danger }"
                :disabled="!password.trim() || loading"
              >
                <div v-if="loading" class="spinner" />
                <Icon v-else :name="danger ? 'exclamation-triangle' : 'check'" class="w-4 h-4" />
                {{ loading ? 'Processing...' : (confirmText || 'Confirm') }}
              </button>
            </div>
          </form>
        </div>

        <!-- Security Notice -->
        <div class="security-notice">
          <Icon name="shield" class="w-4 h-4 text-blue-600" />
          <p class="notice-text">
            We ask for your password to ensure account security and verify your identity.
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, nextTick, onMounted } from 'vue'

// Props
const props = defineProps({
  title: {
    type: String,
    required: true
  },
  message: {
    type: String,
    required: true
  },
  confirmText: {
    type: String,
    default: 'Confirm'
  },
  danger: {
    type: Boolean,
    default: false
  }
})

// Emits
const emit = defineEmits(['confirm', 'cancel'])

// Reactive data
const password = ref('')
const showPassword = ref(false)
const loading = ref(false)
const passwordInput = ref(null)

const errors = reactive({
  password: ''
})

// Methods
const handleConfirm = () => {
  if (!password.value.trim()) {
    errors.password = 'Password is required'
    return
  }

  if (password.value.length < 8) {
    errors.password = 'Password must be at least 8 characters'
    return
  }

  loading.value = true
  emit('confirm', password.value)
}

const handleCancel = () => {
  if (!loading.value) {
    emit('cancel')
  }
}

const handleBackdropClick = () => {
  if (!loading.value) {
    handleCancel()
  }
}

const togglePasswordVisibility = () => {
  showPassword.value = !showPassword.value
}

const clearErrors = () => {
  errors.password = ''
}

// Lifecycle
onMounted(() => {
  // Focus password input when modal opens
  nextTick(() => {
    if (passwordInput.value) {
      passwordInput.value.focus()
    }
  })
})
</script>

<style scoped>
.confirmation-modal {
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
  @apply p-6 text-center space-y-4;
}

.header-icon {
  @apply w-16 h-16 mx-auto rounded-full flex items-center justify-center;
}

.header-icon.info {
  @apply bg-blue-100 text-blue-600;
}

.header-icon.danger {
  @apply bg-red-100 text-red-600;
}

.modal-title {
  @apply text-xl font-bold text-gray-900;
}

.modal-message {
  @apply text-gray-600 text-sm leading-relaxed;
}

.confirmation-form {
  @apply px-6 pb-4;
}

.form-group {
  @apply space-y-2;
}

.form-label {
  @apply block text-sm font-medium text-gray-700;
}

.required {
  @apply text-red-500 ml-1;
}

.password-input-wrapper {
  @apply relative;
}

.password-input {
  @apply w-full px-3 py-2 pr-10 border border-gray-300 rounded-lg 
         focus:ring-2 focus:ring-blue-500 focus:border-blue-500
         transition-all duration-200;
}

.password-input.error {
  @apply border-red-500 ring-red-500;
}

.password-input:disabled {
  @apply bg-gray-100 cursor-not-allowed;
}

.password-toggle {
  @apply absolute right-2 top-1/2 transform -translate-y-1/2 
         text-gray-400 hover:text-gray-600 transition-colors duration-200
         disabled:opacity-50;
}

.error-message {
  @apply text-sm text-red-600 flex items-center space-x-1;
}

.danger-warning {
  @apply flex items-start space-x-3 p-3 bg-red-50 border border-red-200 
         rounded-lg mt-4;
}

.warning-title {
  @apply font-medium text-red-900;
}

.warning-text {
  @apply text-sm text-red-800 mt-1;
}

.form-actions {
  @apply flex space-x-3 mt-6;
}

.btn-primary {
  @apply flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 
         rounded-lg transition-colors duration-200 inline-flex items-center 
         justify-center space-x-2 disabled:opacity-50 disabled:cursor-not-allowed;
}

.btn-primary.btn-danger {
  @apply bg-red-600 hover:bg-red-700;
}

.btn-secondary {
  @apply flex-1 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 
         font-medium py-2 px-4 rounded-lg transition-colors duration-200 
         disabled:opacity-50 disabled:cursor-not-allowed;
}

.spinner {
  @apply animate-spin rounded-full h-4 w-4 border-b-2 border-white;
}

.security-notice {
  @apply flex items-start space-x-2 p-4 bg-blue-50 border-t border-blue-200;
}

.notice-text {
  @apply text-sm text-blue-800;
}

/* Mobile optimizations */
@media (max-width: 640px) {
  .modal-content {
    @apply m-2;
  }
  
  .form-actions {
    @apply flex-col space-x-0 space-y-2;
  }
}

/* High contrast mode */
@media (prefers-contrast: high) {
  .password-input,
  .btn-primary,
  .btn-secondary {
    @apply border-2;
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