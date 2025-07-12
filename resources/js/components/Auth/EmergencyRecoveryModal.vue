<template>
  <div class="emergency-recovery-modal">
    <!-- Modal Backdrop -->
    <div class="modal-backdrop" @click="handleBackdropClick">
      <!-- Modal Content -->
      <div class="modal-content" @click.stop>
        <!-- Header -->
        <div class="modal-header">
          <div class="header-icon">
            <Icon name="exclamation-triangle" class="w-8 h-8 text-red-600" />
          </div>
          <h2 class="modal-title">Emergency Account Recovery</h2>
          <p class="modal-subtitle">
            Request emergency access to your account when you've lost access to all 2FA methods.
          </p>
          <button @click="closeModal" class="close-button">
            <Icon name="x" class="w-5 h-5" />
          </button>
        </div>

        <!-- Warning Notice -->
        <div class="warning-notice">
          <Icon name="exclamation-triangle" class="w-5 h-5 text-amber-600" />
          <div>
            <h4 class="warning-title">Security Notice</h4>
            <p class="warning-text">
              This process requires manual verification by an administrator and may take 24-48 hours.
              You will need to provide additional identity verification.
            </p>
          </div>
        </div>

        <!-- Form -->
        <form @submit.prevent="handleSubmit" class="recovery-form">
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
              :class="{ 'error': errors.reason }"
              rows="4"
              placeholder="Please explain why you need emergency access (e.g., lost phone, broken authenticator app, lost recovery codes)"
              required
              maxlength="500"
            ></textarea>
            <div class="char-counter">{{ formData.reason.length }}/500</div>
            <div v-if="errors.reason" class="error-message">
              <Icon name="x-circle" class="w-4 h-4" />
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
                />
                <div class="radio-custom"></div>
                <div class="radio-content">
                  <Icon name="mail" class="w-4 h-4" />
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
                />
                <div class="radio-custom"></div>
                <div class="radio-content">
                  <Icon name="phone" class="w-4 h-4" />
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
              :class="{ 'error': errors.emergencyContact }"
              :placeholder="formData.contactMethod === 'phone' ? '+1234567890' : 'your-email@example.com'"
              required
            />
            <div class="help-text">
              Administrators will use this to verify your identity and contact you.
            </div>
            <div v-if="errors.emergencyContact" class="error-message">
              <Icon name="x-circle" class="w-4 h-4" />
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
            ></textarea>
            <div class="char-counter">{{ formData.additionalInfo.length }}/300</div>
          </div>

          <!-- Terms Agreement -->
          <div class="form-group">
            <label class="checkbox-container">
              <input
                v-model="formData.agreeToTerms"
                type="checkbox"
                class="checkbox-input"
                required
              />
              <div class="checkbox-custom"></div>
              <span class="checkbox-label">
                I understand that this request will be reviewed by administrators and
                I may be required to provide additional identity verification.
                <span class="required">*</span>
              </span>
            </label>
          </div>

          <!-- Actions -->
          <div class="form-actions">
            <button type="button" @click="closeModal" class="btn-secondary">
              Cancel
            </button>
            <button 
              type="submit" 
              class="btn-primary"
              :disabled="!isFormValid || loading"
            >
              <div v-if="loading" class="spinner" />
              <Icon v-else name="send" class="w-4 h-4" />
              {{ loading ? 'Submitting...' : 'Submit Recovery Request' }}
            </button>
          </div>
        </form>

        <!-- Information Section -->
        <div class="info-section">
          <h4 class="info-title">What happens next?</h4>
          <div class="info-steps">
            <div class="info-step">
              <div class="step-number">1</div>
              <div class="step-content">
                <h5>Request Review</h5>
                <p>Your request will be reviewed by system administrators</p>
              </div>
            </div>
            <div class="info-step">
              <div class="step-number">2</div>
              <div class="step-content">
                <h5>Identity Verification</h5>
                <p>You may be contacted for additional identity verification</p>
              </div>
            </div>
            <div class="info-step">
              <div class="step-number">3</div>
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
  agreeToTerms: false
})

const errors = reactive({
  reason: '',
  emergencyContact: ''
})

// Computed
const isFormValid = computed(() => {
  return formData.reason.trim().length > 10 &&
         formData.emergencyContact.trim().length > 0 &&
         formData.agreeToTerms &&
         isValidContact.value
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
  if (!validateForm()) return

  loading.value = true

  try {
    const submissionData = {
      reason: formData.reason.trim(),
      contact_method: formData.contactMethod,
      emergency_contact: formData.emergencyContact.trim(),
      additional_info: formData.additionalInfo.trim() || null,
      timestamp: new Date().toISOString(),
      user_agent: navigator.userAgent,
      ip_address: null // Will be filled by backend
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
    errors.emergencyContact = formData.contactMethod === 'email' 
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
  @apply relative p-6 border-b border-gray-200;
}

.header-icon {
  @apply flex justify-center mb-4;
}

.modal-title {
  @apply text-2xl font-bold text-gray-900 text-center;
}

.modal-subtitle {
  @apply text-gray-600 text-center mt-2;
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
  @apply font-medium text-amber-900;
}

.warning-text {
  @apply text-sm text-amber-800 mt-1;
}

.recovery-form {
  @apply p-6 space-y-6;
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

.form-input,
.form-textarea {
  @apply w-full px-3 py-2 border border-gray-300 rounded-lg 
         focus:ring-2 focus:ring-blue-500 focus:border-blue-500
         transition-all duration-200;
}

.form-input.error,
.form-textarea.error {
  @apply border-red-500 ring-red-500;
}

.char-counter {
  @apply text-xs text-gray-500 text-right;
}

.help-text {
  @apply text-xs text-gray-500;
}

.error-message {
  @apply text-sm text-red-600 flex items-center space-x-1;
}

.radio-group {
  @apply space-y-3;
}

.radio-option {
  @apply flex items-center space-x-3 cursor-pointer p-3 border border-gray-200 
         rounded-lg hover:bg-gray-50 transition-all duration-200;
}

.radio-input {
  @apply sr-only;
}

.radio-custom {
  @apply w-4 h-4 border-2 border-gray-300 rounded-full transition-all duration-200;
}

.radio-input:checked + .radio-custom {
  @apply border-blue-600 bg-blue-600;
}

.radio-input:checked + .radio-custom::after {
  content: '';
  @apply block w-2 h-2 bg-white rounded-full mx-auto mt-0.5;
}

.radio-content {
  @apply flex items-center space-x-2 text-sm font-medium text-gray-700;
}

.checkbox-container {
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
  @apply text-sm text-gray-700;
}

.form-actions {
  @apply flex space-x-3 pt-4 border-t border-gray-200;
}

.btn-secondary {
  @apply flex-1 px-4 py-2 text-gray-700 bg-white border border-gray-300 
         rounded-lg hover:bg-gray-50 transition-colors duration-200;
}

.btn-primary {
  @apply flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white 
         rounded-lg transition-colors duration-200 inline-flex items-center 
         justify-center space-x-2 disabled:opacity-50 disabled:cursor-not-allowed;
}

.spinner {
  @apply animate-spin rounded-full h-4 w-4 border-b-2 border-white;
}

.info-section {
  @apply bg-gray-50 p-6 border-t border-gray-200;
}

.info-title {
  @apply text-lg font-medium text-gray-900 mb-4;
}

.info-steps {
  @apply space-y-4;
}

.info-step {
  @apply flex items-start space-x-3;
}

.step-number {
  @apply w-6 h-6 bg-blue-600 text-white text-sm font-medium rounded-full 
         flex items-center justify-center flex-shrink-0;
}

.step-content h5 {
  @apply font-medium text-gray-900;
}

.step-content p {
  @apply text-sm text-gray-600 mt-1;
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
    @apply bg-gray-700 border-gray-600 text-gray-100;
  }
  
  .radio-option {
    @apply bg-gray-700 border-gray-600;
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