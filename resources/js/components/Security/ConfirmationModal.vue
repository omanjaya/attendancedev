<template>
  <div class="confirmation-modal">
    <div class="modal-backdrop" @click="handleBackdropClick">
      <div class="modal-content" @click.stop>
        <!-- Header -->
        <div class="modal-header">
          <div class="header-icon" :class="danger ? 'danger' : 'info'">
            <Icon :name="danger ? 'exclamation-triangle' : 'help-circle'" class="h-8 w-8" />
          </div>
          <h2 class="modal-title">
            {{ title }}
          </h2>
          <p class="modal-message">
            {{ message }}
          </p>
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
                  :class="{ error: errors.password }"
                  placeholder="Enter your current password"
                  required
                  :disabled="loading"
                  @input="clearErrors"
                >
                <button
                  type="button"
                  class="password-toggle"
                  :disabled="loading"
                  @click="togglePasswordVisibility"
                >
                  <Icon :name="showPassword ? 'eye-off' : 'eye'" class="h-4 w-4" />
                </button>
              </div>
              <div v-if="errors.password" class="error-message">
                <Icon name="x-circle" class="h-4 w-4" />
                {{ errors.password }}
              </div>
            </div>

            <!-- Additional Warning for Dangerous Actions -->
            <div v-if="danger" class="danger-warning">
              <Icon name="exclamation-triangle" class="h-5 w-5 text-red-600" />
              <div>
                <h4 class="warning-title">
                  This action cannot be undone
                </h4>
                <p class="warning-text">
                  Please make sure you understand the consequences before proceeding.
                </p>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="form-actions">
              <button
                type="button"
                class="btn-secondary"
                :disabled="loading"
                @click="handleCancel"
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
                <Icon v-else :name="danger ? 'exclamation-triangle' : 'check'" class="h-4 w-4" />
                {{ loading ? 'Processing...' : confirmText || 'Confirm' }}
              </button>
            </div>
          </form>
        </div>

        <!-- Security Notice -->
        <div class="security-notice">
          <Icon name="shield" class="h-4 w-4 text-blue-600" />
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
    required: true,
  },
  message: {
    type: String,
    required: true,
  },
  confirmText: {
    type: String,
    default: 'Confirm',
  },
  danger: {
    type: Boolean,
    default: false,
  },
})

// Emits
const emit = defineEmits(['confirm', 'cancel'])

// Reactive data
const password = ref('')
const showPassword = ref(false)
const loading = ref(false)
const passwordInput = ref(null)

const errors = reactive({
  password: '',
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
  @apply space-y-4 p-6 text-center;
}

.header-icon {
  @apply mx-auto flex h-16 w-16 items-center justify-center rounded-full;
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
  @apply text-sm leading-relaxed text-gray-600;
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
  @apply ml-1 text-red-500;
}

.password-input-wrapper {
  @apply relative;
}

.password-input {
  @apply w-full rounded-lg border border-gray-300 px-3 py-2 pr-10 transition-all duration-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-500;
}

.password-input.error {
  @apply border-red-500 ring-red-500;
}

.password-input:disabled {
  @apply cursor-not-allowed bg-gray-100;
}

.password-toggle {
  @apply absolute right-2 top-1/2 -translate-y-1/2 transform text-gray-400 transition-colors duration-200 hover:text-gray-600 disabled:opacity-50;
}

.error-message {
  @apply flex items-center space-x-1 text-sm text-red-600;
}

.danger-warning {
  @apply mt-4 flex items-start space-x-3 rounded-lg border border-red-200 bg-red-50 p-3;
}

.warning-title {
  @apply font-medium text-red-900;
}

.warning-text {
  @apply mt-1 text-sm text-red-800;
}

.form-actions {
  @apply mt-6 flex space-x-3;
}

.btn-primary {
  @apply inline-flex flex-1 items-center justify-center space-x-2 rounded-lg bg-blue-600 px-4 py-2 font-medium text-white transition-colors duration-200 hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50;
}

.btn-primary.btn-danger {
  @apply bg-red-600 hover:bg-red-700;
}

.btn-secondary {
  @apply flex-1 rounded-lg border border-gray-300 bg-white px-4 py-2 font-medium text-gray-700 transition-colors duration-200 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50;
}

.spinner {
  @apply h-4 w-4 animate-spin rounded-full border-b-2 border-white;
}

.security-notice {
  @apply flex items-start space-x-2 border-t border-blue-200 bg-blue-50 p-4;
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
