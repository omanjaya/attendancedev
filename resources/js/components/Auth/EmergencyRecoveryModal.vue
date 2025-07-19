<template>
  <div class="emergency-recovery-modal">
    <!-- Modal Backdrop -->
    <div class="modal-backdrop" @click="handleBackdropClick">
      <!-- Modal Content -->
      <div class="modal-content" @click.stop>
        <!-- Header -->
        <div class="modal-header">
          <div class="header-icon">
            <Icon name="exclamation-triangle" class="h-8 w-8 text-red-600" />
          </div>
          <h2 class="modal-title">
            Emergency Account Recovery
          </h2>
          <p class="modal-subtitle">
            Request emergency access to your account when you've lost access to all 2FA methods.
          </p>
          <button class="close-button" @click="closeModal">
            <Icon name="x" class="h-5 w-5" />
          </button>
        </div>

        <!-- Warning Notice -->
        <div class="warning-notice">
          <Icon name="exclamation-triangle" class="h-5 w-5 text-amber-600" />
          <div>
            <h4 class="warning-title">
              Security Notice
            </h4>
            <p class="warning-text">
              This process requires manual verification by an administrator and may take 24-48
              hours. You will need to provide additional identity verification.
            </p>
          </div>
        </div>

        <!-- Form -->
        <form class="recovery-form" @submit.prevent="handleSubmit">
          <!-- Reason for Recovery -->
          <div class="form-group">
            <label for="reason" class="form-label">
              Reason for Emergency Recovery
              <span class="required">*</span>
            </label>
            <textarea
              id="reason"
              v-model="formData.reason"
              class="form-textarea"
              :class="{ error: errors.reason }"
              rows="4"
              placeholder="Please explain why you need emergency access (e.g., lost phone, broken authenticator app, lost recovery codes)"
              required
              maxlength="500"
            />
            <div class="char-counter">
              {{ formData.reason.length }}/500
            </div>
            <div v-if="errors.reason" class="error-message">
              <Icon name="x-circle" class="h-4 w-4" />
              {{ errors.reason }}
            </div>
          </div>

          <!-- Contact Method -->
          <div class="form-group">
            <label class="form-label">
              Preferred Contact Method
              <span class="required">*</span>
            </label>
            <div class="radio-group">
              <label class="radio-option">
                <input
                  v-model="formData.contactMethod"
                  type="radio"
                  value="email"
                  class="radio-input"
                  required
                >
                <div class="radio-custom" />
                <div class="radio-content">
                  <Icon name="mail" class="h-4 w-4" />
                  <span>Email</span>
                </div>
              </label>
              <label class="radio-option">
                <input
                  v-model="formData.contactMethod"
                  type="radio"
                  value="phone"
                  class="radio-input"
                  required
                >
                <div class="radio-custom" />
                <div class="radio-content">
                  <Icon name="phone" class="h-4 w-4" />
                  <span>Phone</span>
                </div>
              </label>
            </div>
          </div>

          <!-- Emergency Contact -->
          <div class="form-group">
            <label for="emergency-contact" class="form-label">
              {{ formData.contactMethod === 'phone' ? 'Phone Number' : 'Email Address' }}
              <span class="required">*</span>
            </label>
            <input
              id="emergency-contact"
              v-model="formData.emergencyContact"
              :type="formData.contactMethod === 'phone' ? 'tel' : 'email'"
              class="form-input"
              :class="{ error: errors.emergencyContact }"
              :placeholder="
                formData.contactMethod === 'phone' ? '+1234567890' : 'your-email@example.com'
              "
              required
            >
            <div class="help-text">
              Administrators will use this to verify your identity and contact you.
            </div>
            <div v-if="errors.emergencyContact" class="error-message">
              <Icon name="x-circle" class="h-4 w-4" />
              {{ errors.emergencyContact }}
            </div>
          </div>

          <!-- Additional Information -->
          <div class="form-group">
            <label for="additional-info" class="form-label">
              Additional Information (Optional)
            </label>
            <textarea
              id="additional-info"
              v-model="formData.additionalInfo"
              class="form-textarea"
              rows="3"
              placeholder="Any additional information that might help verify your identity (employee ID, department, supervisor name, etc.)"
              maxlength="300"
            />
            <div class="char-counter">
              {{ formData.additionalInfo.length }}/300
            </div>
          </div>

          <!-- Terms Agreement -->
          <div class="form-group">
            <label class="checkbox-container">
              <input
                v-model="formData.agreeToTerms"
                type="checkbox"
                class="checkbox-input"
                required
              >
              <div class="checkbox-custom" />
              <span class="checkbox-label">
                I understand that this request will be reviewed by administrators and I may be
                required to provide additional identity verification.
                <span class="required">*</span>
              </span>
            </label>
          </div>

          <!-- Actions -->
          <div class="form-actions">
            <button type="button" class="btn-secondary" @click="closeModal">
              Cancel
            </button>
            <button type="submit" class="btn-primary" :disabled="!isFormValid || loading">
              <div v-if="loading" class="spinner" />
              <Icon v-else name="send" class="h-4 w-4" />
              {{ loading ? 'Submitting...' : 'Submit Recovery Request' }}
            </button>
          </div>
        </form>

        <!-- Information Section -->
        <div class="info-section">
          <h4 class="info-title">
            What happens next?
          </h4>
          <div class="info-steps">
            <div class="info-step">
              <div class="step-number">
                1
              </div>
              <div class="step-content">
                <h5>Request Review</h5>
                <p>Your request will be reviewed by system administrators</p>
              </div>
            </div>
            <div class="info-step">
              <div class="step-number">
                2
              </div>
              <div class="step-content">
                <h5>Identity Verification</h5>
                <p>You may be contacted for additional identity verification</p>
              </div>
            </div>
            <div class="info-step">
              <div class="step-number">
                3
              </div>
              <div class="step-content">
                <h5>Account Recovery</h5>
                <p>If approved, your 2FA will be reset and you'll receive new setup instructions</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'

// Emits
const emit = defineEmits(['close', 'submit'])

// Reactive data
const loading = ref(false)

const formData = reactive({
  reason: '',
  contactMethod: 'email',
  emergencyContact: '',
  additionalInfo: '',
  agreeToTerms: false,
})

const errors = reactive({
  reason: '',
  emergencyContact: '',
})

// Computed
const isFormValid = computed(() => {
  return (
    formData.reason.trim().length > 10 &&
    formData.emergencyContact.trim().length > 0 &&
    formData.agreeToTerms &&
    isValidContact.value
  )
})

const isValidContact = computed(() => {
  if (formData.contactMethod === 'email') {
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    return emailPattern.test(formData.emergencyContact)
  } else {
    const phonePattern = /^[\+]?[1-9][\d]{0,15}$/
    return phonePattern.test(formData.emergencyContact.replace(/\s/g, ''))
  }
})

// Methods
const handleSubmit = async () => {
  if (!validateForm()) {return}

  loading.value = true

  try {
    const submissionData = {
      reason: formData.reason.trim(),
      contact_method: formData.contactMethod,
      emergency_contact: formData.emergencyContact.trim(),
      additional_info: formData.additionalInfo.trim() || null,
      timestamp: new Date().toISOString(),
      user_agent: navigator.userAgent,
      ip_address: null, // Will be filled by backend
    }

    emit('submit', submissionData)
  } catch (error) {
    console.error('Emergency recovery submission error:', error)
  } finally {
    loading.value = false
  }
}

const validateForm = () => {
  // Reset errors
  errors.reason = ''
  errors.emergencyContact = ''

  let isValid = true

  // Validate reason
  if (formData.reason.trim().length < 10) {
    errors.reason = 'Please provide a detailed reason (at least 10 characters)'
    isValid = false
  }

  // Validate contact
  if (!isValidContact.value) {
    errors.emergencyContact =
      formData.contactMethod === 'email'
        ? 'Please enter a valid email address'
        : 'Please enter a valid phone number'
    isValid = false
  }

  return isValid
}

const closeModal = () => {
  emit('close')
}

const handleBackdropClick = () => {
  if (!loading.value) {
    closeModal()
  }
}
</script>

<style scoped>
.emergency-recovery-modal {
  @apply fixed inset-0 z-50 overflow-y-auto;
}

.modal-backdrop {
  @apply fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 p-4;
  animation: fadeIn 0.3s ease-out;
}

.modal-content {
  @apply max-h-screen w-full max-w-2xl overflow-y-auto rounded-lg bg-white shadow-xl;
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
  @apply relative border-b border-gray-200 p-6;
}

.header-icon {
  @apply mb-4 flex justify-center;
}

.modal-title {
  @apply text-center text-2xl font-bold text-gray-900;
}

.modal-subtitle {
  @apply mt-2 text-center text-gray-600;
}

.close-button {
  @apply absolute right-4 top-4 text-gray-400 transition-colors duration-200 hover:text-gray-600;
}

.warning-notice {
  @apply mx-6 mt-6 flex items-start space-x-3 rounded-lg border border-amber-200 bg-amber-50 p-4;
}

.warning-title {
  @apply font-medium text-amber-900;
}

.warning-text {
  @apply mt-1 text-sm text-amber-800;
}

.recovery-form {
  @apply space-y-6 p-6;
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

.form-input,
.form-textarea {
  @apply w-full rounded-lg border border-gray-300 px-3 py-2 transition-all duration-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-500;
}

.form-input.error,
.form-textarea.error {
  @apply border-red-500 ring-red-500;
}

.char-counter {
  @apply text-right text-xs text-gray-500;
}

.help-text {
  @apply text-xs text-gray-500;
}

.error-message {
  @apply flex items-center space-x-1 text-sm text-red-600;
}

.radio-group {
  @apply space-y-3;
}

.radio-option {
  @apply flex cursor-pointer items-center space-x-3 rounded-lg border border-gray-200 p-3 transition-all duration-200 hover:bg-gray-50;
}

.radio-input {
  @apply sr-only;
}

.radio-custom {
  @apply h-4 w-4 rounded-full border-2 border-gray-300 transition-all duration-200;
}

.radio-input:checked + .radio-custom {
  @apply border-blue-600 bg-blue-600;
}

.radio-input:checked + .radio-custom::after {
  content: '';
  @apply mx-auto mt-0.5 block h-2 w-2 rounded-full bg-white;
}

.radio-content {
  @apply flex items-center space-x-2 text-sm font-medium text-gray-700;
}

.checkbox-container {
  @apply flex cursor-pointer items-start space-x-3;
}

.checkbox-input {
  @apply sr-only;
}

.checkbox-custom {
  @apply mt-0.5 h-4 w-4 flex-shrink-0 rounded border-2 border-gray-300 transition-all duration-200;
}

.checkbox-input:checked + .checkbox-custom {
  @apply border-blue-600 bg-blue-600;
}

.checkbox-input:checked + .checkbox-custom::after {
  content: '✓';
  @apply flex items-center justify-center text-xs text-white;
}

.checkbox-label {
  @apply text-sm text-gray-700;
}

.form-actions {
  @apply flex space-x-3 border-t border-gray-200 pt-4;
}

.btn-secondary {
  @apply flex-1 rounded-lg border border-gray-300 bg-white px-4 py-2 text-gray-700 transition-colors duration-200 hover:bg-gray-50;
}

.btn-primary {
  @apply inline-flex flex-1 items-center justify-center space-x-2 rounded-lg bg-red-600 px-4 py-2 text-white transition-colors duration-200 hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50;
}

.spinner {
  @apply h-4 w-4 animate-spin rounded-full border-b-2 border-white;
}

.info-section {
  @apply border-t border-gray-200 bg-gray-50 p-6;
}

.info-title {
  @apply mb-4 text-lg font-medium text-gray-900;
}

.info-steps {
  @apply space-y-4;
}

.info-step {
  @apply flex items-start space-x-3;
}

.step-number {
  @apply flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 text-sm font-medium text-white;
}

.step-content h5 {
  @apply font-medium text-gray-900;
}

.step-content p {
  @apply mt-1 text-sm text-gray-600;
}

/* Mobile optimizations */
@media (max-width: 640px) {
  .modal-content {
    @apply m-2 max-h-screen;
  }

  .modal-header,
  .recovery-form,
  .info-section {
    @apply p-4;
  }

  .form-actions {
    @apply flex-col space-x-0 space-y-2;
  }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .modal-content {
    @apply bg-gray-800 text-gray-100;
  }

  .modal-title {
    @apply text-gray-100;
  }

  .form-input,
  .form-textarea {
    @apply border-gray-600 bg-gray-700 text-gray-100;
  }

  .radio-option {
    @apply border-gray-600 bg-gray-700;
  }

  .info-section {
    @apply bg-gray-700;
  }
}

/* High contrast mode */
@media (prefers-contrast: high) {
  .form-input,
  .form-textarea,
  .radio-option {
    @apply border-2;
  }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .modal-backdrop,
  .modal-content {
    animation: none;
  }
}
</style>
