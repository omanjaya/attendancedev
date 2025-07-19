/**
 * Standardized Error Handling Patterns
 *
 * This file provides standardized patterns and utilities for error handling
 * across all Vue components. Follow these patterns to ensure consistent
 * error tracking and user experience.
 */

import type { ErrorContext } from '@/services/errorTracking'
import { useErrorTracking } from '@/composables/useErrorTracking'

// Standard error categories for consistent classification
export const ErrorCategories = {
  API: 'api',
  VALIDATION: 'validation',
  AUTHENTICATION: 'authentication',
  CAMERA: 'camera',
  LOCATION: 'location',
  STORAGE: 'storage',
  NETWORK: 'network',
  RENDER: 'render',
  NAVIGATION: 'navigation',
  USER_INPUT: 'user_input',
} as const

// Standard error actions for consistent tracking
export const ErrorActions = {
  // API related
  API_CALL_FAILED: 'api_call_failed',
  FETCH_DATA_FAILED: 'fetch_data_failed',
  SAVE_DATA_FAILED: 'save_data_failed',
  DELETE_DATA_FAILED: 'delete_data_failed',

  // Authentication related
  LOGIN_FAILED: 'login_failed',
  LOGOUT_FAILED: 'logout_failed',
  TOKEN_REFRESH_FAILED: 'token_refresh_failed',
  PERMISSION_DENIED: 'permission_denied',

  // Validation related
  FORM_VALIDATION_FAILED: 'form_validation_failed',
  INPUT_SANITIZATION_FAILED: 'input_sanitization_failed',
  SCHEMA_VALIDATION_FAILED: 'schema_validation_failed',

  // Camera related
  CAMERA_ACCESS_DENIED: 'camera_access_denied',
  CAMERA_INITIALIZATION_FAILED: 'camera_initialization_failed',
  FACE_DETECTION_FAILED: 'face_detection_failed',
  IMAGE_CAPTURE_FAILED: 'image_capture_failed',

  // Location related
  LOCATION_ACCESS_DENIED: 'location_access_denied',
  LOCATION_TIMEOUT: 'location_timeout',
  LOCATION_UNAVAILABLE: 'location_unavailable',

  // Storage related
  LOCAL_STORAGE_FAILED: 'local_storage_failed',
  SESSION_STORAGE_FAILED: 'session_storage_failed',
  INDEXEDDB_FAILED: 'indexeddb_failed',

  // Network related
  NETWORK_ERROR: 'network_error',
  CONNECTION_TIMEOUT: 'connection_timeout',
  OFFLINE_ERROR: 'offline_error',

  // Component related
  COMPONENT_MOUNT_FAILED: 'component_mount_failed',
  COMPONENT_UPDATE_FAILED: 'component_update_failed',
  COMPONENT_UNMOUNT_FAILED: 'component_unmount_failed',
} as const

/**
 * Standard error handling pattern for API calls
 */
export function handleAPIError(
  error: Error,
  context: {
    endpoint: string
    method: string
    action?: string
    component?: string
    additionalData?: Record<string, any>
  }
): ErrorContext {
  const { captureError } = useErrorTracking()

  const errorContext: ErrorContext = {
    component: context.component,
    action: context.action || ErrorActions.API_CALL_FAILED,
    metadata: {
      category: ErrorCategories.API,
      endpoint: context.endpoint,
      method: context.method,
      errorMessage: error.message,
      errorType: getErrorType(error),
      timestamp: new Date().toISOString(),
      ...context.additionalData,
    },
  }

  captureError(error, errorContext)
  return errorContext
}

/**
 * Standard error handling pattern for form validation
 */
export function handleValidationError(
  error: Error,
  context: {
    formName: string
    fieldName?: string
    validationRule?: string
    component?: string
    additionalData?: Record<string, any>
  }
): ErrorContext {
  const { captureError } = useErrorTracking()

  const errorContext: ErrorContext = {
    component: context.component,
    action: ErrorActions.FORM_VALIDATION_FAILED,
    metadata: {
      category: ErrorCategories.VALIDATION,
      formName: context.formName,
      fieldName: context.fieldName,
      validationRule: context.validationRule,
      errorMessage: error.message,
      timestamp: new Date().toISOString(),
      ...context.additionalData,
    },
  }

  captureError(error, errorContext)
  return errorContext
}

/**
 * Standard error handling pattern for camera operations
 */
export function handleCameraError(
  error: Error,
  context: {
    operation: string
    detectionMethod?: string
    component?: string
    additionalData?: Record<string, any>
  }
): ErrorContext {
  const { captureError } = useErrorTracking()

  const errorContext: ErrorContext = {
    component: context.component,
    action:
      context.operation === 'access'
        ? ErrorActions.CAMERA_ACCESS_DENIED
        : ErrorActions.CAMERA_INITIALIZATION_FAILED,
    metadata: {
      category: ErrorCategories.CAMERA,
      operation: context.operation,
      detectionMethod: context.detectionMethod,
      errorMessage: error.message,
      errorType: getErrorType(error),
      userAgent: navigator.userAgent,
      hasCamera: !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia),
      timestamp: new Date().toISOString(),
      ...context.additionalData,
    },
  }

  captureError(error, errorContext)
  return errorContext
}

/**
 * Standard error handling pattern for authentication operations
 */
export function handleAuthError(
  error: Error,
  context: {
    operation: string
    userId?: string | number
    component?: string
    additionalData?: Record<string, any>
  }
): ErrorContext {
  const { captureError } = useErrorTracking()

  const errorContext: ErrorContext = {
    component: context.component,
    action:
      context.operation === 'login' ? ErrorActions.LOGIN_FAILED : ErrorActions.TOKEN_REFRESH_FAILED,
    metadata: {
      category: ErrorCategories.AUTHENTICATION,
      operation: context.operation,
      userId: context.userId,
      errorMessage: error.message,
      errorType: getErrorType(error),
      timestamp: new Date().toISOString(),
      ...context.additionalData,
    },
  }

  captureError(error, errorContext)
  return errorContext
}

/**
 * Get error type from error object
 */
function getErrorType(error: Error): string {
  if (error.name) {return error.name}

  // Network errors
  if (error.message.includes('fetch')) {return 'FetchError'}
  if (error.message.includes('Network')) {return 'NetworkError'}
  if (error.message.includes('timeout')) {return 'TimeoutError'}

  // Permission errors
  if (error.message.includes('Permission')) {return 'PermissionError'}
  if (error.message.includes('Access')) {return 'AccessError'}

  // Validation errors
  if (error.message.includes('validation')) {return 'ValidationError'}
  if (error.message.includes('required')) {return 'RequiredFieldError'}

  return 'UnknownError'
}

/**
 * Standard user-friendly error messages
 */
export const UserFriendlyMessages = {
  // Network errors
  NETWORK_ERROR: 'Unable to connect to the server. Please check your internet connection.',
  TIMEOUT_ERROR: 'The request took too long to complete. Please try again.',
  OFFLINE_ERROR: 'You appear to be offline. Please check your connection.',

  // API errors
  SERVER_ERROR: 'A server error occurred. Please try again later.',
  NOT_FOUND: 'The requested resource was not found.',
  UNAUTHORIZED: 'You are not authorized to perform this action.',
  FORBIDDEN: 'Access to this resource is forbidden.',

  // Validation errors
  VALIDATION_ERROR: 'Please check your input and try again.',
  REQUIRED_FIELD: 'This field is required.',
  INVALID_FORMAT: 'Please enter a valid value.',

  // Camera errors
  CAMERA_ACCESS_DENIED:
    'Camera access was denied. Please allow camera access in your browser settings.',
  CAMERA_NOT_FOUND: 'No camera found. Please ensure a camera is connected.',
  CAMERA_IN_USE: 'Camera is being used by another application.',

  // Location errors
  LOCATION_ACCESS_DENIED: 'Location access was denied. Please allow location access to continue.',
  LOCATION_TIMEOUT: 'Unable to determine your location. Please try again.',
  LOCATION_UNAVAILABLE: 'Location services are not available.',

  // General errors
  UNKNOWN_ERROR: 'An unexpected error occurred. Please try again.',
  SESSION_EXPIRED: 'Your session has expired. Please log in again.',
} as const

/**
 * Get user-friendly message for error
 */
export function getUserFriendlyMessage(error: Error, context?: ErrorContext): string {
  const errorMessage = error.message.toLowerCase()

  // Network errors
  if (errorMessage.includes('network') || errorMessage.includes('fetch')) {
    return UserFriendlyMessages.NETWORK_ERROR
  }
  if (errorMessage.includes('timeout')) {
    return UserFriendlyMessages.TIMEOUT_ERROR
  }
  if (errorMessage.includes('offline')) {
    return UserFriendlyMessages.OFFLINE_ERROR
  }

  // HTTP status errors
  if (errorMessage.includes('401') || errorMessage.includes('unauthorized')) {
    return UserFriendlyMessages.UNAUTHORIZED
  }
  if (errorMessage.includes('403') || errorMessage.includes('forbidden')) {
    return UserFriendlyMessages.FORBIDDEN
  }
  if (errorMessage.includes('404') || errorMessage.includes('not found')) {
    return UserFriendlyMessages.NOT_FOUND
  }
  if (errorMessage.includes('500') || errorMessage.includes('server error')) {
    return UserFriendlyMessages.SERVER_ERROR
  }

  // Camera errors
  if (context?.metadata?.category === ErrorCategories.CAMERA) {
    if (errorMessage.includes('permission') || errorMessage.includes('denied')) {
      return UserFriendlyMessages.CAMERA_ACCESS_DENIED
    }
    if (errorMessage.includes('not found') || errorMessage.includes('no camera')) {
      return UserFriendlyMessages.CAMERA_NOT_FOUND
    }
    if (errorMessage.includes('in use') || errorMessage.includes('busy')) {
      return UserFriendlyMessages.CAMERA_IN_USE
    }
  }

  // Location errors
  if (context?.metadata?.category === ErrorCategories.LOCATION) {
    if (errorMessage.includes('permission') || errorMessage.includes('denied')) {
      return UserFriendlyMessages.LOCATION_ACCESS_DENIED
    }
    if (errorMessage.includes('timeout')) {
      return UserFriendlyMessages.LOCATION_TIMEOUT
    }
    if (errorMessage.includes('unavailable')) {
      return UserFriendlyMessages.LOCATION_UNAVAILABLE
    }
  }

  // Validation errors
  if (context?.metadata?.category === ErrorCategories.VALIDATION) {
    if (errorMessage.includes('required')) {
      return UserFriendlyMessages.REQUIRED_FIELD
    }
    if (errorMessage.includes('invalid') || errorMessage.includes('format')) {
      return UserFriendlyMessages.INVALID_FORMAT
    }
    return UserFriendlyMessages.VALIDATION_ERROR
  }

  return UserFriendlyMessages.UNKNOWN_ERROR
}

/**
 * Standard error boundary for async operations
 */
export async function withAsyncErrorBoundary<T>(
  operation: () => Promise<T>,
  context: {
    operationName: string
    component?: string
    category?: string
    additionalData?: Record<string, any>
    onError?: (error: Error, errorContext: ErrorContext) => void
  }
): Promise<T> {
  const { trackAsyncOperation, captureError } = useErrorTracking()

  return trackAsyncOperation(context.operationName, operation).catch((error: Error) => {
    const errorContext: ErrorContext = {
      component: context.component,
      action: `${context.operationName}_failed`,
      metadata: {
        category: context.category || ErrorCategories.API,
        operationName: context.operationName,
        errorMessage: error.message,
        errorType: getErrorType(error),
        timestamp: new Date().toISOString(),
        ...context.additionalData,
      },
    }

    captureError(error, errorContext)

    if (context.onError) {
      context.onError(error, errorContext)
    }

    throw error
  })
}

/**
 * Standard retry mechanism with exponential backoff
 */
export async function withRetry<T>(
  operation: () => Promise<T>,
  options: {
    maxRetries?: number
    baseDelay?: number
    maxDelay?: number
    backoffFactor?: number
    shouldRetry?: (error: Error) => boolean
    onRetry?: (attempt: number, error: Error) => void
  } = {}
): Promise<T> {
  const {
    maxRetries = 3,
    baseDelay = 1000,
    maxDelay = 10000,
    backoffFactor = 2,
    shouldRetry = () => true,
    onRetry,
  } = options

  let lastError: Error

  for (let attempt = 0; attempt <= maxRetries; attempt++) {
    try {
      return await operation()
    } catch (error) {
      lastError = error instanceof Error ? error : new Error(String(error))

      if (attempt === maxRetries || !shouldRetry(lastError)) {
        throw lastError
      }

      const delay = Math.min(baseDelay * Math.pow(backoffFactor, attempt), maxDelay)

      if (onRetry) {
        onRetry(attempt + 1, lastError)
      }

      await new Promise((resolve) => setTimeout(resolve, delay))
    }
  }

  throw lastError!
}

export type { ErrorContext }
