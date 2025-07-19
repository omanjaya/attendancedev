# Authentication Error Handling System

This documentation explains the comprehensive authentication error handling system implemented in
the Vue.js application, providing enhanced user feedback and proper error recovery mechanisms.

## Overview

The enhanced authentication error handling system provides:

- **User-friendly error messages** with clear explanations and suggested actions
- **Contextual error handling** based on error type and severity
- **Automatic retry mechanisms** with smart backoff strategies
- **Comprehensive error tracking** for monitoring and debugging
- **Accessibility-compliant** error feedback with proper ARIA attributes
- **Progressive error recovery** with multiple fallback options

## Core Components

### 1. useAuthErrorHandler Composable (`/composables/useAuthErrorHandler.ts`)

The main composable that handles authentication errors across the application.

```typescript
import { useAuthErrorHandler } from '@/composables/useAuthErrorHandler'

const authErrorHandler = useAuthErrorHandler()

// Handle login error
try {
  await login(credentials)
} catch (error) {
  const authError = await authErrorHandler.handleLoginError(error, email)
  // Error is automatically tracked and user is notified
}
```

#### Key Features:

- **Error parsing and categorization** into validation, authentication, authorization, network, and
  server errors
- **Severity classification** (low, medium, high, critical) for appropriate user feedback
- **Automatic retry logic** with configurable retry counts and backoff periods
- **Context-aware error handling** with specific actions for different error types
- **Comprehensive validation helpers** for email, password, and 2FA codes

### 2. AuthErrorMessage Component (`/components/Auth/AuthErrorMessage.vue`)

A reusable component for displaying authentication errors with proper styling and actions.

```vue
<template>
  <AuthErrorMessage
    :error="currentError"
    :retry-loading="retryLoading"
    :retry-count="retryCount"
    :max-retries="3"
    @retry="handleRetry"
    @contact-support="showSupport"
  />
</template>
```

#### Features:

- **Dynamic styling** based on error severity
- **Built-in retry functionality** with countdown timers
- **Contextual help sections** with troubleshooting steps
- **Support contact integration** for escalation
- **Accessibility compliance** with proper ARIA attributes

### 3. Enhanced Auth Store (`/stores/auth.ts`)

The authentication store has been enhanced to use the new error handling system.

```typescript
// Enhanced error handling in auth store
const login = async (credentials: LoginCredentials) => {
  try {
    // ... login logic
  } catch (err) {
    const authError = await authErrorHandler.handleLoginError(err, credentials.email)
    error.value = authError.userMessage
    throw authError
  }
}
```

## Error Types and Handling

### Authentication Errors

#### Invalid Credentials

```typescript
{
  code: 'invalid_credentials',
  type: 'authentication',
  severity: 'medium',
  userMessage: 'Invalid email or password. Please check your credentials and try again.',
  suggestedAction: 'Verify your email and password are correct, or reset your password if needed.',
  canRetry: true
}
```

#### Account Locked

```typescript
{
  code: 'account_locked',
  type: 'authentication',
  severity: 'high',
  userMessage: 'Your account has been temporarily locked due to multiple failed login attempts.',
  suggestedAction: 'Please wait 15 minutes before trying again, or contact support for assistance.',
  canRetry: true,
  retryAfter: 15 * 60 * 1000 // 15 minutes
}
```

#### 2FA Errors

```typescript
{
  code: 'invalid_2fa_code',
  type: 'authentication',
  severity: 'medium',
  userMessage: 'Invalid verification code. Please try again.',
  suggestedAction: 'Enter the current 6-digit code from your authenticator app, or use a recovery code.',
  canRetry: true
}
```

### Authorization Errors

#### Account Suspended

```typescript
{
  code: 'account_suspended',
  type: 'authorization',
  severity: 'critical',
  userMessage: 'Your account has been suspended. Please contact your administrator.',
  suggestedAction: 'Contact your system administrator or HR department for account reactivation.',
  canRetry: false
}
```

### Network and Server Errors

#### Network Error

```typescript
{
  code: 'network_error',
  type: 'network',
  severity: 'medium',
  userMessage: 'Network connection error. Please check your internet connection.',
  suggestedAction: 'Check your internet connection and try again.',
  canRetry: true
}
```

#### Server Error

```typescript
{
  code: 'server_error',
  type: 'server',
  severity: 'high',
  userMessage: 'Server error occurred. Please try again later.',
  suggestedAction: 'If the problem persists, please contact technical support.',
  canRetry: true
}
```

## Usage Examples

### Basic Login Form with Error Handling

```vue
<template>
  <div>
    <!-- Error Display -->
    <AuthErrorMessage
      v-if="authStore.authErrorHandler.hasError.value"
      :error="authStore.authErrorHandler.currentError.value"
      :retry-loading="retryLoading"
      @retry="handleRetry"
    />

    <!-- Login Form -->
    <form @submit.prevent="handleLogin">
      <input v-model="email" type="email" placeholder="Email" />
      <input v-model="password" type="password" placeholder="Password" />
      <button type="submit" :disabled="authStore.isLoading">
        {{ authStore.isLoading ? 'Signing in...' : 'Sign In' }}
      </button>
    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import AuthErrorMessage from '@/components/Auth/AuthErrorMessage.vue'

const authStore = useAuthStore()
const email = ref('')
const password = ref('')
const retryLoading = ref(false)

const handleLogin = async () => {
  try {
    await authStore.login({ email: email.value, password: password.value })
  } catch (error) {
    // Error handling is automatic via the auth store
  }
}

const handleRetry = async () => {
  retryLoading.value = true
  try {
    await authStore.authErrorHandler.retryLastOperation(() =>
      authStore.login({ email: email.value, password: password.value })
    )
  } finally {
    retryLoading.value = false
  }
}
</script>
```

### 2FA Component with Enhanced Error Handling

```vue
<template>
  <div>
    <AuthErrorMessage
      v-if="authErrorHandler.hasError.value"
      :error="authErrorHandler.currentError.value"
      @retry="handleRetry"
    />

    <form @submit.prevent="handleSubmit">
      <input
        v-model="verificationCode"
        type="text"
        placeholder="Enter 6-digit code"
        maxlength="6"
      />
      <button type="submit">Verify</button>
    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useAuthErrorHandler } from '@/composables/useAuthErrorHandler'
import { twoFactorService } from '@/services/twoFactor'

const authErrorHandler = useAuthErrorHandler()
const verificationCode = ref('')

const handleSubmit = async () => {
  // Validate code format
  const validation = authErrorHandler.validate2FACode(verificationCode.value, 'totp')
  if (!validation.isValid) {
    // Show validation error
    return
  }

  try {
    await twoFactorService.verify(verificationCode.value, 'totp')
  } catch (error) {
    await authErrorHandler.handle2FAError(error, 'totp')
  }
}
</script>
```

## Error Tracking and Monitoring

### Error Context Information

The system automatically captures comprehensive error context:

```typescript
interface AuthErrorContext {
  action: string // 'login', '2fa_verification', 'password_reset'
  userId?: string // User ID if available
  email?: string // Email address
  method?: string // Authentication method
  ipAddress?: string // User's IP address
  userAgent?: string // Browser user agent
  timestamp: Date // Error timestamp
}
```

### Error Tracking Integration

```typescript
// Automatic error tracking
const trackError = (error: AuthError, context: AuthErrorContext) => {
  errorTracking.captureError(new Error(error.message), {
    action: context.action,
    metadata: {
      errorCode: error.code,
      errorType: error.type,
      severity: error.severity,
      userId: context.userId,
      retryCount: retryCount.value,
      canRetry: error.canRetry,
    },
  })
}
```

## Validation Helpers

### Email Validation

```typescript
const validation = authErrorHandler.validateEmail(email)
if (!validation.isValid) {
  console.log(validation.message) // "Please enter a valid email address"
}
```

### Password Validation

```typescript
const validation = authErrorHandler.validatePassword(password)
if (!validation.isValid) {
  console.log(validation.message) // "Password must be at least 8 characters long"
}
```

### 2FA Code Validation

```typescript
const validation = authErrorHandler.validate2FACode(code, 'totp')
if (!validation.isValid) {
  console.log(validation.message) // "Verification code must be 6 digits"
}
```

## User Experience Features

### Progressive Error Recovery

1. **Immediate Feedback**: Form validation errors show immediately
2. **Contextual Help**: Error messages include specific troubleshooting steps
3. **Retry Mechanisms**: Smart retry with exponential backoff
4. **Escalation Path**: Easy access to support when needed

### Accessibility Features

- **ARIA Labels**: All error messages have proper ARIA attributes
- **Screen Reader Support**: Error announcements via `role="alert"`
- **Keyboard Navigation**: Full keyboard accessibility
- **Focus Management**: Proper focus handling after errors

### Mobile Responsiveness

- **Responsive Design**: Error messages adapt to screen size
- **Touch-Friendly**: Retry and help buttons are touch-accessible
- **Readable Text**: Appropriate font sizes for mobile devices

## Configuration Options

### Error Handler Configuration

```typescript
const authErrorHandler = useAuthErrorHandler({
  maxRetries: 3,
  retryDelay: 1000,
  trackingEnabled: true,
  notificationsEnabled: true,
})
```

### Retry Configuration

```typescript
// Custom retry logic
const retryConfig = {
  maxRetries: 5,
  backoffMultiplier: 2,
  baseDelay: 1000,
  maxDelay: 30000,
}
```

## Best Practices

### Do's ✅

1. **Always use the error handler** for authentication operations
2. **Provide clear, actionable error messages**
3. **Include retry mechanisms** where appropriate
4. **Track errors** for monitoring and improvement
5. **Test error scenarios** thoroughly
6. **Follow accessibility guidelines**

### Don'ts ❌

1. **Don't expose sensitive information** in error messages
2. **Don't use generic error messages** for specific errors
3. **Don't allow infinite retries** without user confirmation
4. **Don't ignore error context** when handling errors
5. **Don't forget to clear errors** after successful operations

## Testing

### Unit Tests

```typescript
import { describe, it, expect } from 'vitest'
import { useAuthErrorHandler } from '@/composables/useAuthErrorHandler'

describe('useAuthErrorHandler', () => {
  it('should handle login errors correctly', async () => {
    const authErrorHandler = useAuthErrorHandler()

    const error = {
      response: {
        status: 401,
        data: { code: 'invalid_credentials', message: 'Invalid credentials' },
      },
    }

    const authError = await authErrorHandler.handleLoginError(error, 'test@example.com')

    expect(authError.code).toBe('invalid_credentials')
    expect(authError.type).toBe('authentication')
    expect(authError.severity).toBe('medium')
    expect(authError.canRetry).toBe(true)
  })
})
```

### Integration Tests

```typescript
// Test error handling in components
import { mount } from '@vue/test-utils'
import LoginForm from '@/components/Auth/LoginForm.vue'

describe('LoginForm', () => {
  it('should display error message on login failure', async () => {
    const wrapper = mount(LoginForm)

    // Simulate login failure
    await wrapper.find('form').trigger('submit')

    // Check error message display
    expect(wrapper.find('[role="alert"]').exists()).toBe(true)
    expect(wrapper.find('.error-message').text()).toContain('Invalid credentials')
  })
})
```

## Support Integration

### Support Contact Modal

The system includes a comprehensive support contact modal that:

- **Pre-fills error context** for faster resolution
- **Captures system information** for debugging
- **Provides multiple contact methods** (email, phone, chat)
- **Includes priority selection** based on error severity
- **Offers quick access** to help resources

### Support Ticket Creation

```typescript
// Automatic support ticket creation for critical errors
if (error.severity === 'critical') {
  await createSupportTicket({
    errorCode: error.code,
    userMessage: error.userMessage,
    systemContext: getSystemContext(),
    priority: 'high',
  })
}
```

This enhanced authentication error handling system provides a robust, user-friendly experience that
helps users successfully authenticate while providing administrators with comprehensive error
tracking and monitoring capabilities.
