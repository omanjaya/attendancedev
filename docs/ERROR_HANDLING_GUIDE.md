# Error Handling Guide

This guide documents the standardized error handling patterns implemented across the Vue.js
components in this application.

## Overview

We have implemented a comprehensive error tracking and handling system that provides:

- **Consistent error capture and reporting**
- **User-friendly error messages**
- **Detailed error context for debugging**
- **Performance monitoring**
- **Centralized error management**

## Error Tracking Service

The core error tracking service (`/resources/js/services/errorTracking.ts`) provides:

- Integration with Sentry (optional)
- Custom error endpoint reporting
- Performance monitoring
- User context tracking
- Rate limiting and filtering

## Vue Composables

### Primary Composable: `useErrorTracking`

```typescript
import { useErrorTracking } from '@/composables/useErrorTracking'

const errorTracking = useErrorTracking({
  component: 'MyComponent',
  category: 'api',
  autoCapture: true,
  enablePerformanceTracking: true,
})
```

### Specialized Composables

- `useErrorTrackingForAPI()` - For API-related operations
- `useErrorTrackingForAuth()` - For authentication operations
- `useErrorTrackingForCamera()` - For camera and face detection
- `useErrorTrackingForValidation()` - For form validation
- `useErrorTrackingForLocation()` - For location services

## Standard Error Handling Patterns

### 1. API Calls

```typescript
const loadData = async () => {
  return errorTracking
    .trackAsyncOperation('load_data', async () => {
      errorTracking.addBreadcrumb('Loading data from API', 'api')

      const response = await fetch('/api/data')
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`)
      }

      const data = await response.json()
      errorTracking.addBreadcrumb('Data loaded successfully', 'api')
      return data
    })
    .catch((error: Error) => {
      errorTracking.captureError(error, {
        action: 'api_call_failed',
        metadata: {
          endpoint: '/api/data',
          method: 'GET',
        },
      })
      throw error
    })
}
```

### 2. Form Validation

```typescript
const handleSubmit = async () => {
  const validationResult = validation.validateAll()

  if (!validationResult.isValid) {
    errorTracking.captureMessage('Validation failed', 'warning', {
      action: 'form_validation_failed',
      metadata: {
        errors: validationResult.errors,
        formName: 'UserForm',
      },
    })
    return
  }

  // Continue with submission...
}
```

### 3. Camera Operations

```typescript
const startCamera = async () => {
  return errorTracking
    .trackAsyncOperation('start_camera', async () => {
      errorTracking.addBreadcrumb('Starting camera', 'camera')

      const stream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: 'user' },
      })

      // Set up video element...
      errorTracking.addBreadcrumb('Camera started successfully', 'camera')
    })
    .catch((error: Error) => {
      errorTracking.captureError(error, {
        action: 'camera_access_failed',
        metadata: {
          hasCamera: !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia),
          userAgent: navigator.userAgent,
        },
      })
      throw error
    })
}
```

### 4. User Actions (Copy, Download, etc.)

```typescript
const copyToClipboard = async (text: string) => {
  return errorTracking.withErrorBoundary(
    () => {
      errorTracking.addBreadcrumb('Copying to clipboard', 'user_action')

      return navigator.clipboard
        .writeText(text)
        .then(() => {
          errorTracking.addBreadcrumb('Copy successful', 'user_action')
          toast.success('Copied to clipboard')
        })
        .catch((clipboardError) => {
          // Fallback implementation...
        })
    },
    {
      action: 'copy_failed',
      metadata: {
        hasClipboardAPI: !!navigator.clipboard,
        textLength: text.length,
      },
    }
  )
}
```

## Error Categories and Actions

### Standard Categories

- `api` - API and network operations
- `validation` - Form and input validation
- `authentication` - Authentication and authorization
- `camera` - Camera and face detection
- `location` - Location services
- `storage` - Local storage operations
- `network` - Network connectivity
- `render` - Component rendering
- `navigation` - Route navigation
- `user_input` - User interactions

### Standard Actions

- `api_call_failed`
- `form_validation_failed`
- `camera_access_denied`
- `login_failed`
- `save_data_failed`
- `component_mount_failed`

## User-Friendly Error Messages

The system provides user-friendly error messages for common scenarios:

```typescript
import { getUserFriendlyMessage } from '@/utils/errorHandlingPatterns'

try {
  await apiCall()
} catch (error) {
  const friendlyMessage = getUserFriendlyMessage(error, errorContext)
  toast.error(friendlyMessage)
}
```

### Standard Messages

- **Network errors**: "Unable to connect to the server. Please check your internet connection."
- **Permission errors**: "Camera access was denied. Please allow camera access in your browser
  settings."
- **Validation errors**: "Please check your input and try again."
- **Server errors**: "A server error occurred. Please try again later."

## Breadcrumbs

Use breadcrumbs to track user actions and system state changes:

```typescript
// At the start of an operation
errorTracking.addBreadcrumb('Starting data export', 'user_action', {
  exportType: 'pdf',
  recordCount: 150,
})

// During the operation
errorTracking.addBreadcrumb('Data processing complete', 'system', {
  processingTime: '2.3s',
})

// At completion
errorTracking.addBreadcrumb('Export completed successfully', 'user_action')
```

## Performance Monitoring

Track performance metrics for important operations:

```typescript
// Automatic tracking with trackAsyncOperation
const result = await errorTracking.trackAsyncOperation('complex_calculation', async () => {
  // Your complex operation here
  return performCalculation()
})

// Manual timing
const stopTimer = errorTracking.startPerformanceTimer('component_render')
// ... rendering logic
stopTimer() // Automatically captures the duration
```

## Backend Integration

Errors are automatically sent to the backend for critical issues:

- **Endpoint**: `POST /api/errors`
- **Rate Limited**: 50 errors per minute per IP
- **Storage**: Local files with rotation
- **Notifications**: Admin alerts for critical errors

## Configuration

Error tracking is configured via:

1. **Laravel Config**: `/config/error_tracking.php`
2. **Environment Variables**: `.env` file
3. **Frontend Config**: Injected via Blade template

```bash
# .env configuration
ERROR_TRACKING_ENABLED=true
SENTRY_DSN=your_sentry_dsn_here
ERROR_TRACKING_SAMPLE_RATE=0.1
ERROR_TRACKING_PERFORMANCE=true
ERROR_TRACKING_USER_TRACKING=true
```

## Best Practices

### Do's ✅

1. **Always use specialized composables** for specific domains (API, auth, camera, etc.)
2. **Add meaningful breadcrumbs** before and after important operations
3. **Provide context metadata** relevant to the error
4. **Use trackAsyncOperation** for async operations with automatic error handling
5. **Capture user-friendly messages** for display to users
6. **Track performance** for critical operations

### Don'ts ❌

1. **Don't capture sensitive data** in error metadata
2. **Don't overwhelm** with too many breadcrumbs
3. **Don't ignore errors** - always handle or re-throw
4. **Don't use generic error messages** - be specific about the context
5. **Don't forget to test** error handling paths

## Example Component Implementation

```vue
<script setup lang="ts">
import { ref } from 'vue'
import { useErrorTrackingForAPI } from '@/composables/useErrorTracking'
import { getUserFriendlyMessage } from '@/utils/errorHandlingPatterns'

const errorTracking = useErrorTrackingForAPI()
const loading = ref(false)
const data = ref(null)

const loadUserData = async (userId: string) => {
  loading.value = true

  try {
    data.value = await errorTracking.trackAsyncOperation('load_user_data', async () => {
      errorTracking.addBreadcrumb('Loading user data', 'api', { userId })

      const response = await fetch(`/api/users/${userId}`)
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`)
      }

      const userData = await response.json()
      errorTracking.addBreadcrumb('User data loaded', 'api', {
        userId,
        hasPermissions: !!userData.permissions,
      })

      return userData
    })
  } catch (error) {
    const friendlyMessage = getUserFriendlyMessage(error)
    // Show user-friendly error message
    console.error('Failed to load user data:', friendlyMessage)
  } finally {
    loading.value = false
  }
}
</script>
```

## Testing Error Handling

Test error scenarios in your components:

```typescript
// In your test files
it('should handle API errors gracefully', async () => {
  // Mock the API to throw an error
  const mockFetch = vi.fn().mockRejectedValue(new Error('Network error'))
  global.fetch = mockFetch

  // Mount component and trigger action
  const wrapper = mount(MyComponent)
  await wrapper.find('button').trigger('click')

  // Verify error handling
  expect(mockErrorTracking.captureError).toHaveBeenCalledWith(
    expect.any(Error),
    expect.objectContaining({
      action: 'api_call_failed',
    })
  )
})
```

This comprehensive error handling system ensures that all errors are properly tracked, reported, and
handled in a consistent manner across the entire application.
