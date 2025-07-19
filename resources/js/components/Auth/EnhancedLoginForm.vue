<!--
  Enhanced Login Form Component

  A comprehensive login form with improved error handling, validation,
  and user feedback using the enhanced authentication error handler.
-->

<template>
  <div class="login-form-container">
    <form class="login-form" @submit.prevent="handleLogin">
      <!-- Header -->
      <div class="form-header">
        <div class="logo-container">
          <img src="/images/logo.svg" alt="School Logo" class="logo">
        </div>
        <h1 class="form-title">
          Masuk
        </h1>
        <p class="form-subtitle">
          Selamat datang kembali! Silakan masuk ke akun Anda.
        </p>
      </div>

      <!-- Authentication Error Display -->
      <AuthErrorMessage
        v-if="authStore.authErrorHandler.hasError.value"
        :error="authStore.authErrorHandler.currentError.value"
        :retryLoading="retryLoading"
        :retryCount="authStore.authErrorHandler.retryCount.value"
        :maxRetries="authStore.authErrorHandler.maxRetries"
        class="mb-6"
        @retry="handleRetry"
        @contact-support="handleContactSupport"
      />

      <!-- Email Field -->
      <div class="form-group">
        <label for="email" class="form-label"> Email Address </label>
        <div class="input-wrapper">
          <input
            id="email"
            v-model="formData.email"
            type="email"
            required
            autocomplete="email"
            class="form-input"
            :class="{ 'input-error': emailError }"
            :disabled="authStore.isLoading"
            placeholder="Enter your email"
            aria-describedby="email-error"
            @blur="validateEmail"
            @input="clearEmailError"
          >
          <EnvelopeIcon class="input-icon" />
        </div>
        <div
          v-if="emailError"
          id="email-error"
          class="field-error"
          role="alert"
        >
          {{ emailError }}
        </div>
      </div>

      <!-- Password Field -->
      <div class="form-group">
        <label for="password" class="form-label"> Password </label>
        <div class="input-wrapper">
          <input
            id="password"
            v-model="formData.password"
            :type="showPassword ? 'text' : 'password'"
            required
            autocomplete="current-password"
            class="form-input"
            :class="{ 'input-error': passwordError }"
            :disabled="authStore.isLoading"
            placeholder="Enter your password"
            aria-describedby="password-error"
            @blur="validatePassword"
            @input="clearPasswordError"
          >
          <button
            type="button"
            class="password-toggle"
            :aria-label="showPassword ? 'Hide password' : 'Show password'"
            @click="togglePasswordVisibility"
          >
            <EyeIcon v-if="!showPassword" class="h-4 w-4" />
            <EyeSlashIcon v-else class="h-4 w-4" />
          </button>
        </div>
        <div
          v-if="passwordError"
          id="password-error"
          class="field-error"
          role="alert"
        >
          {{ passwordError }}
        </div>
      </div>

      <!-- Remember Me & Forgot Password -->
      <div class="form-options">
        <label class="remember-me">
          <input
            v-model="formData.rememberMe"
            type="checkbox"
            class="checkbox-input"
            :disabled="authStore.isLoading"
          >
          <div class="checkbox-custom" />
          <span class="checkbox-label">Remember me</span>
        </label>

        <router-link to="/auth/forgot-password" class="forgot-password-link">
          Forgot password?
        </router-link>
      </div>

      <!-- Submit Button -->
      <button type="submit" class="submit-button" :disabled="!canSubmit || authStore.isLoading">
        <div v-if="authStore.isLoading" class="spinner" />
        <LockOpenIcon v-else class="h-5 w-5" />
        {{ authStore.isLoading ? 'Signing in...' : 'Sign In' }}
      </button>

      <!-- Additional Options -->
      <div class="additional-options">
        <div class="divider">
          <span class="divider-text">Need help?</span>
        </div>

        <div class="help-actions">
          <button type="button" class="help-button" @click="handleContactSupport">
            <ChatBubbleLeftRightIcon class="h-4 w-4" />
            Contact Support
          </button>

          <button type="button" class="help-button" @click="showSystemStatus">
            <ServerIcon class="h-4 w-4" />
            System Status
          </button>
        </div>
      </div>
    </form>

    <!-- Support Contact Modal -->
    <SupportContactModal
      v-if="showSupportModal"
      :errorContext="authStore.authErrorHandler.currentError.value"
      @close="showSupportModal = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useAuthErrorHandler } from '@/composables/useAuthErrorHandler'
import { useNotificationStore } from '@/stores/notification'
import AuthErrorMessage from '@/components/Auth/AuthErrorMessage.vue'
import SupportContactModal from '@/components/Auth/SupportContactModal.vue'
import {
  EnvelopeIcon,
  EyeIcon,
  EyeSlashIcon,
  LockOpenIcon,
  ChatBubbleLeftRightIcon,
  ServerIcon,
} from '@heroicons/vue/24/outline'

interface LoginForm {
  email: string
  password: string
  rememberMe: boolean
}

// Composables
const router = useRouter()
const authStore = useAuthStore()
const authErrorHandler = useAuthErrorHandler()
const notificationStore = useNotificationStore()

// State
const formData = ref<LoginForm>({
  email: '',
  password: '',
  rememberMe: false,
})

const showPassword = ref(false)
const showSupportModal = ref(false)
const retryLoading = ref(false)
const emailError = ref('')
const passwordError = ref('')

// Computed properties
const canSubmit = computed(() => {
  return (
    formData.value.email.trim() !== '' &&
    formData.value.password.trim() !== '' &&
    !emailError.value &&
    !passwordError.value
  )
})

// Methods
const validateEmail = () => {
  const validation = authErrorHandler.validateEmail(formData.value.email)
  emailError.value = validation.isValid ? '' : validation.message || ''
}

const validatePassword = () => {
  const validation = authErrorHandler.validatePassword(formData.value.password)
  passwordError.value = validation.isValid ? '' : validation.message || ''
}

const clearEmailError = () => {
  emailError.value = ''
}

const clearPasswordError = () => {
  passwordError.value = ''
}

const togglePasswordVisibility = () => {
  showPassword.value = !showPassword.value
}

const handleLogin = async () => {
  // Clear previous errors
  emailError.value = ''
  passwordError.value = ''

  // Validate fields
  validateEmail()
  validatePassword()

  if (!canSubmit.value) {
    return
  }

  try {
    await authStore.login({
      email: formData.value.email,
      password: formData.value.password,
      remember: formData.value.rememberMe,
    })

    // Success - redirect will be handled by the auth store
    notificationStore.addNotification({
      type: 'success',
      title: 'Login Successful',
      message: `Welcome back, ${authStore.fullName}!`,
      timeout: 5000,
    })

    // Navigate to intended route or dashboard
    const intended = router.currentRoute.value.query.redirect as string
    router.push(intended || '/dashboard')
  } catch (error) {
    // Error handling is done by the auth store using authErrorHandler
    console.error('Login failed:', error)
  }
}

const handleRetry = async () => {
  retryLoading.value = true

  try {
    await authErrorHandler.retryLastOperation(() =>
      authStore.login({
        email: formData.value.email,
        password: formData.value.password,
        remember: formData.value.rememberMe,
      })
    )

    // Success handling same as above
    notificationStore.addNotification({
      type: 'success',
      title: 'Login Successful',
      message: `Welcome back, ${authStore.fullName}!`,
      timeout: 5000,
    })

    const intended = router.currentRoute.value.query.redirect as string
    router.push(intended || '/dashboard')
  } catch (error) {
    // Error already handled by authErrorHandler
    console.error('Retry failed:', error)
  } finally {
    retryLoading.value = false
  }
}

const handleContactSupport = () => {
  showSupportModal.value = true
}

const showSystemStatus = () => {
  window.open('/status', '_blank')
}

// Auto-focus email field on mount
onMounted(async () => {
  await nextTick()
  document.getElementById('email')?.focus()
})
</script>

<style scoped>
.login-form-container {
  @apply mx-auto max-w-md px-4 py-8;
}

.login-form {
  @apply space-y-6;
}

.form-header {
  @apply space-y-3 text-center;
}

.logo-container {
  @apply flex justify-center;
}

.logo {
  @apply h-12 w-auto;
}

.form-title {
  @apply text-2xl font-bold text-gray-900;
}

.form-subtitle {
  @apply text-gray-600;
}

.form-group {
  @apply space-y-2;
}

.form-label {
  @apply block text-sm font-medium text-gray-700;
}

.input-wrapper {
  @apply relative;
}

.form-input {
  @apply w-full rounded-lg border border-gray-300 px-3 py-2 pr-10 placeholder-gray-400 shadow-sm transition-colors duration-200 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-50 disabled:text-gray-500;
}

.input-error {
  @apply border-red-500 focus:border-red-500 focus:ring-red-500;
}

.input-icon {
  @apply absolute right-3 top-2.5 h-5 w-5 text-gray-400;
}

.password-toggle {
  @apply absolute right-3 top-2.5 text-gray-400 transition-colors hover:text-gray-600 focus:text-gray-600 focus:outline-none;
}

.field-error {
  @apply mt-1 text-sm text-red-600;
}

.form-options {
  @apply flex items-center justify-between;
}

.remember-me {
  @apply flex cursor-pointer items-center space-x-2;
}

.checkbox-input {
  @apply sr-only;
}

.checkbox-custom {
  @apply h-4 w-4 flex-shrink-0 rounded border-2 border-gray-300 transition-all duration-200;
}

.checkbox-input:checked + .checkbox-custom {
  @apply border-blue-600 bg-blue-600;
}

.checkbox-input:checked + .checkbox-custom::after {
  content: '✓';
  @apply flex items-center justify-center text-xs text-white;
}

.checkbox-label {
  @apply select-none text-sm text-gray-700;
}

.forgot-password-link {
  @apply text-sm font-medium text-blue-600 transition-colors hover:text-blue-800;
}

.submit-button {
  @apply inline-flex w-full items-center justify-center space-x-2 rounded-lg bg-blue-600 px-4 py-3 font-medium text-white transition-colors duration-200 hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50;
}

.spinner {
  @apply h-5 w-5 animate-spin rounded-full border-b-2 border-white;
}

.additional-options {
  @apply space-y-4;
}

.divider {
  @apply relative flex items-center;
}

.divider::before {
  @apply flex-1 border-t border-gray-300;
  content: '';
}

.divider::after {
  @apply flex-1 border-t border-gray-300;
  content: '';
}

.divider-text {
  @apply bg-white px-3 text-sm text-gray-500;
}

.help-actions {
  @apply flex justify-center space-x-4;
}

.help-button {
  @apply inline-flex items-center space-x-2 text-sm text-gray-600 transition-colors hover:text-gray-900;
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .form-title {
    @apply text-gray-100;
  }

  .form-subtitle {
    @apply text-gray-300;
  }

  .form-label {
    @apply text-gray-300;
  }

  .form-input {
    @apply border-gray-600 bg-gray-800 text-gray-100 placeholder-gray-400;
  }

  .input-icon {
    @apply text-gray-400;
  }

  .checkbox-label {
    @apply text-gray-300;
  }

  .divider-text {
    @apply bg-gray-800 text-gray-400;
  }

  .help-button {
    @apply text-gray-400 hover:text-gray-200;
  }
}

/* Mobile responsiveness */
@media (max-width: 640px) {
  .login-form-container {
    @apply px-6 py-6;
  }

  .form-options {
    @apply flex-col items-stretch space-y-3;
  }

  .help-actions {
    @apply flex-col space-x-0 space-y-2;
  }

  .help-button {
    @apply justify-center;
  }
}
</style>
