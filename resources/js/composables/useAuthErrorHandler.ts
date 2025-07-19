/**
 * Authentication Error Handler Composable
 *
 * Provides enhanced error handling for authentication-related operations
 * with user-friendly feedback and proper recovery suggestions.
 */

import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useNotificationStore } from '@/stores/notification'
import { useErrorTrackingForValidation } from '@/composables/useErrorTracking'

interface AuthError {
  code: string
  message: string
  field?: string
  type: 'validation' | 'authentication' | 'authorization' | 'network' | 'server'
  severity: 'low' | 'medium' | 'high' | 'critical'
  userMessage: string
  suggestedAction?: string
  canRetry: boolean
  retryAfter?: number
}

interface AuthErrorContext {
  action: string
  userId?: string
  email?: string
  method?: string
  ipAddress?: string
  userAgent?: string
  timestamp: Date
}

export function useAuthErrorHandler() {
  const router = useRouter()
  const authStore = useAuthStore()
  const notificationStore = useNotificationStore()
  const errorTracking = useErrorTrackingForValidation()

  // State
  const currentError = ref<AuthError | null>(null)
  const isHandlingError = ref(false)
  const retryCount = ref(0)
  const maxRetries = 3

  // Error mapping for common authentication errors
  const errorMappings: Record<string, Partial<AuthError>> = {
    // Login errors
    invalid_credentials: {
      type: 'authentication',
      severity: 'medium',
      userMessage: 'Invalid email or password. Please check your credentials and try again.',
      suggestedAction:
        'Verify your email and password are correct, or reset your password if needed.',
      canRetry: true,
    },
    account_locked: {
      type: 'authentication',
      severity: 'high',
      userMessage:
        'Your account has been temporarily locked due to multiple failed login attempts.',
      suggestedAction:
        'Please wait 15 minutes before trying again, or contact support for assistance.',
      canRetry: true,
      retryAfter: 15 * 60 * 1000, // 15 minutes
    },
    account_suspended: {
      type: 'authorization',
      severity: 'critical',
      userMessage: 'Your account has been suspended. Please contact your administrator.',
      suggestedAction:
        'Contact your system administrator or HR department for account reactivation.',
      canRetry: false,
    },
    email_not_verified: {
      type: 'authentication',
      severity: 'medium',
      userMessage: 'Please verify your email address before logging in.',
      suggestedAction:
        'Check your email for a verification link, or request a new verification email.',
      canRetry: true,
    },
    password_expired: {
      type: 'authentication',
      severity: 'medium',
      userMessage: 'Your password has expired and must be changed.',
      suggestedAction: 'You will be redirected to change your password.',
      canRetry: false,
    },
    session_expired: {
      type: 'authentication',
      severity: 'low',
      userMessage: 'Your session has expired. Please log in again.',
      suggestedAction: 'Log in again to continue using the application.',
      canRetry: true,
    },
    too_many_attempts: {
      type: 'authentication',
      severity: 'high',
      userMessage: 'Too many login attempts. Please wait before trying again.',
      suggestedAction: 'Wait a few minutes before attempting to log in again.',
      canRetry: true,
      retryAfter: 5 * 60 * 1000, // 5 minutes
    },

    // 2FA errors
    invalid_2fa_code: {
      type: 'authentication',
      severity: 'medium',
      userMessage: 'Invalid verification code. Please try again.',
      suggestedAction:
        'Enter the current 6-digit code from your authenticator app, or use a recovery code.',
      canRetry: true,
    },
    '2fa_code_expired': {
      type: 'authentication',
      severity: 'medium',
      userMessage: 'The verification code has expired. Please try with a new code.',
      suggestedAction: 'Generate a new code from your authenticator app and try again.',
      canRetry: true,
    },
    invalid_recovery_code: {
      type: 'authentication',
      severity: 'medium',
      userMessage: 'Invalid recovery code. Please check the code and try again.',
      suggestedAction: 'Double-check your recovery code format (8 characters) and try again.',
      canRetry: true,
    },
    recovery_code_used: {
      type: 'authentication',
      severity: 'medium',
      userMessage: 'This recovery code has already been used.',
      suggestedAction:
        'Each recovery code can only be used once. Please try a different recovery code.',
      canRetry: true,
    },
    no_recovery_codes: {
      type: 'authentication',
      severity: 'high',
      userMessage: 'No recovery codes available. Please contact support.',
      suggestedAction: 'Contact your administrator for emergency access assistance.',
      canRetry: false,
    },

    // Password reset errors
    invalid_reset_token: {
      type: 'authentication',
      severity: 'medium',
      userMessage: 'Invalid or expired password reset link.',
      suggestedAction: 'Request a new password reset link from the login page.',
      canRetry: true,
    },
    reset_token_expired: {
      type: 'authentication',
      severity: 'medium',
      userMessage: 'Password reset link has expired.',
      suggestedAction: 'Password reset links expire after 1 hour. Please request a new one.',
      canRetry: true,
    },
    weak_password: {
      type: 'validation',
      severity: 'medium',
      userMessage: 'Password does not meet security requirements.',
      suggestedAction: 'Use at least 8 characters with uppercase, lowercase, numbers, and symbols.',
      canRetry: true,
    },
    password_recently_used: {
      type: 'validation',
      severity: 'medium',
      userMessage: 'You cannot reuse a recent password.',
      suggestedAction: 'Choose a password you haven\'t used in the last 12 months.',
      canRetry: true,
    },

    // Network and server errors
    network_error: {
      type: 'network',
      severity: 'medium',
      userMessage: 'Network connection error. Please check your internet connection.',
      suggestedAction: 'Check your internet connection and try again.',
      canRetry: true,
    },
    server_error: {
      type: 'server',
      severity: 'high',
      userMessage: 'Server error occurred. Please try again later.',
      suggestedAction: 'If the problem persists, please contact technical support.',
      canRetry: true,
    },
    service_unavailable: {
      type: 'server',
      severity: 'high',
      userMessage: 'Authentication service is temporarily unavailable.',
      suggestedAction: 'Please try again in a few minutes.',
      canRetry: true,
      retryAfter: 2 * 60 * 1000, // 2 minutes
    },
    maintenance_mode: {
      type: 'server',
      severity: 'high',
      userMessage: 'System is under maintenance. Please try again later.',
      suggestedAction: 'System maintenance is in progress. Please check back later.',
      canRetry: false,
    },
  }

  // Computed properties
  const hasError = computed(() => !!currentError.value)
  const canRetryCurrentError = computed(
    () => currentError.value?.canRetry && retryCount.value < maxRetries
  )
  const retryCountdownMessage = computed(() => {
    if (!currentError.value?.retryAfter) {return null}

    const minutes = Math.ceil(currentError.value.retryAfter / (60 * 1000))
    return `Please wait ${minutes} minute${minutes > 1 ? 's' : ''} before trying again.`
  })

  // Helper functions
  const parseError = (error: any, context: AuthErrorContext): AuthError => {
    let errorCode = 'unknown_error'
    let originalMessage = 'An unexpected error occurred'
    let field: string | undefined

    // Parse different error formats
    if (error.response) {
      // HTTP response error
      const status = error.response.status
      const data = error.response.data

      if (status === 401) {
        errorCode = data.code || 'invalid_credentials'
      } else if (status === 403) {
        errorCode = data.code || 'account_suspended'
      } else if (status === 422) {
        errorCode = data.code || 'validation_error'
        if (data.errors) {
          // Laravel validation errors
          const firstError = Object.keys(data.errors)[0]
          field = firstError
          originalMessage = data.errors[firstError][0]
        }
      } else if (status === 429) {
        errorCode = 'too_many_attempts'
      } else if (status >= 500) {
        errorCode = 'server_error'
      }

      originalMessage = data.message || originalMessage
    } else if (error.code) {
      // Custom error with code
      errorCode = error.code
      originalMessage = error.message || originalMessage
    } else if (error.message) {
      // Generic error
      originalMessage = error.message

      // Try to infer error type from message
      if (originalMessage.includes('network') || originalMessage.includes('fetch')) {
        errorCode = 'network_error'
      } else if (originalMessage.includes('timeout')) {
        errorCode = 'network_error'
      }
    }

    // Get mapped error info or create default
    const mappedError = errorMappings[errorCode] || {
      type: 'server' as const,
      severity: 'medium' as const,
      userMessage: 'An unexpected error occurred. Please try again.',
      suggestedAction: 'If the problem persists, please contact support.',
      canRetry: true,
    }

    return {
      code: errorCode,
      message: originalMessage,
      field,
      type: mappedError.type!,
      severity: mappedError.severity!,
      userMessage: mappedError.userMessage!,
      suggestedAction: mappedError.suggestedAction,
      canRetry: mappedError.canRetry!,
      retryAfter: mappedError.retryAfter,
    }
  }

  const trackError = (error: AuthError, context: AuthErrorContext) => {
    errorTracking.captureError(new Error(error.message), {
      action: context.action,
      metadata: {
        errorCode: error.code,
        errorType: error.type,
        severity: error.severity,
        userId: context.userId,
        email: context.email,
        method: context.method,
        field: error.field,
        retryCount: retryCount.value,
        canRetry: error.canRetry,
        userAgent: context.userAgent,
        timestamp: context.timestamp.toISOString(),
      },
    })
  }

  const showUserNotification = (error: AuthError) => {
    const notificationType =
      error.severity === 'critical'
        ? 'error'
        : error.severity === 'high'
          ? 'error'
          : error.severity === 'medium'
            ? 'warning'
            : 'info'

    notificationStore.addNotification({
      type: notificationType,
      title: 'Authentication Error',
      message: error.userMessage,
      actions: error.suggestedAction
        ? [
            {
              label: 'View Suggestion',
              action: () => showErrorDetails(error),
            },
          ]
        : undefined,
      timeout: error.severity === 'critical' ? 0 : 8000, // No timeout for critical errors
    })
  }

  const showErrorDetails = (error: AuthError) => {
    notificationStore.addNotification({
      type: 'info',
      title: 'Suggested Action',
      message: error.suggestedAction || 'Please try again or contact support.',
      timeout: 10000,
    })
  }

  const handleSpecificActions = async (error: AuthError, context: AuthErrorContext) => {
    switch (error.code) {
      case 'session_expired':
        await authStore.logout()
        router.push('/login')
        break

      case 'password_expired':
        router.push('/auth/change-password')
        break

      case 'email_not_verified':
        router.push('/auth/verify-email')
        break

      case 'account_locked':
      case 'too_many_attempts':
        // Start countdown timer if retry time is specified
        if (error.retryAfter) {
          setTimeout(() => {
            retryCount.value = 0 // Reset retry count after wait period
          }, error.retryAfter)
        }
        break

      case 'account_suspended':
        await authStore.logout()
        router.push('/auth/account-suspended')
        break

      case 'maintenance_mode':
        router.push('/maintenance')
        break
    }
  }

  // Main error handling function
  const handleAuthError = async (error: any, context: AuthErrorContext): Promise<AuthError> => {
    isHandlingError.value = true

    try {
      // Parse and categorize the error
      const parsedError = parseError(error, context)
      currentError.value = parsedError

      // Track the error for monitoring
      trackError(parsedError, context)

      // Show user-friendly notification
      showUserNotification(parsedError)

      // Handle specific error actions
      await handleSpecificActions(parsedError, context)

      // Increment retry count
      retryCount.value++

      return parsedError
    } finally {
      isHandlingError.value = false
    }
  }

  // Specific error handlers for common scenarios
  const handleLoginError = async (error: any, email?: string) => {
    return handleAuthError(error, {
      action: 'login',
      email,
      method: 'password',
      timestamp: new Date(),
    })
  }

  const handle2FAError = async (error: any, method: string) => {
    return handleAuthError(error, {
      action: '2fa_verification',
      userId: authStore.user?.id,
      method,
      timestamp: new Date(),
    })
  }

  const handlePasswordResetError = async (error: any) => {
    return handleAuthError(error, {
      action: 'password_reset',
      timestamp: new Date(),
    })
  }

  const handleRegistrationError = async (error: any, email?: string) => {
    return handleAuthError(error, {
      action: 'registration',
      email,
      timestamp: new Date(),
    })
  }

  // Retry functionality
  const retryLastOperation = async (operation: () => Promise<any>) => {
    if (!canRetryCurrentError.value) {return}

    try {
      clearError()
      const result = await operation()
      retryCount.value = 0 // Reset on success
      return result
    } catch (error) {
      // Re-handle the error
      return handleAuthError(error, {
        action: 'retry',
        timestamp: new Date(),
      })
    }
  }

  const clearError = () => {
    currentError.value = null
    retryCount.value = 0
  }

  const resetRetryCount = () => {
    retryCount.value = 0
  }

  // Validation helpers
  const validateEmail = (email: string): { isValid: boolean; message?: string } => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

    if (!email.trim()) {
      return { isValid: false, message: 'Email is required' }
    }

    if (!emailRegex.test(email)) {
      return { isValid: false, message: 'Please enter a valid email address' }
    }

    return { isValid: true }
  }

  const validatePassword = (password: string): { isValid: boolean; message?: string } => {
    if (!password) {
      return { isValid: false, message: 'Password is required' }
    }

    if (password.length < 8) {
      return { isValid: false, message: 'Password must be at least 8 characters long' }
    }

    if (!/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(password)) {
      return { isValid: false, message: 'Password must contain uppercase, lowercase, and numbers' }
    }

    return { isValid: true }
  }

  const validate2FACode = (
    code: string,
    type: 'totp' | 'sms' | 'recovery'
  ): { isValid: boolean; message?: string } => {
    if (!code.trim()) {
      return { isValid: false, message: 'Verification code is required' }
    }

    if (type === 'recovery') {
      if (code.length !== 8) {
        return { isValid: false, message: 'Recovery code must be 8 characters' }
      }
    } else {
      if (code.length !== 6 || !/^\d{6}$/.test(code)) {
        return { isValid: false, message: 'Verification code must be 6 digits' }
      }
    }

    return { isValid: true }
  }

  return {
    // State
    currentError,
    isHandlingError,
    retryCount,
    maxRetries,

    // Computed
    hasError,
    canRetryCurrentError,
    retryCountdownMessage,

    // Error handlers
    handleAuthError,
    handleLoginError,
    handle2FAError,
    handlePasswordResetError,
    handleRegistrationError,

    // Utilities
    retryLastOperation,
    clearError,
    resetRetryCount,
    showErrorDetails,

    // Validation
    validateEmail,
    validatePassword,
    validate2FACode,
  }
}
