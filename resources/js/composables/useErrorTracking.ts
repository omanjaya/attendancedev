/**
 * Vue Composable for Error Tracking
 *
 * Provides reactive error tracking capabilities for Vue components
 * with automatic component context detection and performance monitoring.
 */

import { getCurrentInstance, onErrorCaptured, onMounted, onUnmounted, type Ref } from 'vue'
import {
  getErrorTrackingService,
  type ErrorContext,
  type PerformanceMetric,
} from '@/services/errorTracking'
import { commonContexts, errorCategories, type ErrorCategory } from '@/config/errorTracking'

export interface UseErrorTrackingOptions {
  component?: string
  category?: ErrorCategory
  autoCapture?: boolean
  enablePerformanceTracking?: boolean
  context?: Partial<ErrorContext>
}

export interface ErrorTrackingComposable {
  captureError: (error: Error, context?: Partial<ErrorContext>) => void
  captureMessage: (
    message: string,
    level?: 'info' | 'warning' | 'error',
    context?: Partial<ErrorContext>
  ) => void
  capturePerformance: (name: string, duration: number, metadata?: Record<string, any>) => void
  addBreadcrumb: (message: string, category?: string, data?: Record<string, any>) => void
  setUser: (user: any) => void
  startPerformanceTimer: (name: string) => () => void
  trackAsyncOperation: <T>(name: string, operation: () => Promise<T>) => Promise<T>
  withErrorBoundary: <T>(operation: () => T, context?: Partial<ErrorContext>) => T | null
}

export function useErrorTracking(options: UseErrorTrackingOptions = {}): ErrorTrackingComposable {
  const instance = getCurrentInstance()
  const errorTrackingService = getErrorTrackingService()

  const {
    component = instance?.type?.name || instance?.type?.__name || 'UnknownComponent',
    category,
    autoCapture = true,
    enablePerformanceTracking = true,
    context: baseContext = {},
  } = options

  // Base context that will be merged with all error captures
  const componentContext: ErrorContext = {
    component,
    ...baseContext,
    ...(category && { metadata: { category, ...baseContext.metadata } }),
  }

  // Performance tracking state
  const performanceTimers = new Map<string, number>()

  /**
   * Capture an error with automatic component context
   */
  const captureError = (error: Error, context: Partial<ErrorContext> = {}) => {
    if (!errorTrackingService) {
      console.warn('[useErrorTracking] Error tracking service not available')
      return
    }

    const enrichedContext: ErrorContext = {
      ...componentContext,
      ...context,
      metadata: {
        ...componentContext.metadata,
        ...context.metadata,
      },
    }

    errorTrackingService.captureError(error, enrichedContext)
  }

  /**
   * Capture a message with context
   */
  const captureMessage = (
    message: string,
    level: 'info' | 'warning' | 'error' = 'info',
    context: Partial<ErrorContext> = {}
  ) => {
    if (!errorTrackingService) {
      console.warn('[useErrorTracking] Error tracking service not available')
      return
    }

    const enrichedContext: ErrorContext = {
      ...componentContext,
      ...context,
      metadata: {
        ...componentContext.metadata,
        ...context.metadata,
      },
    }

    errorTrackingService.captureMessage(message, level, enrichedContext)
  }

  /**
   * Capture performance metric
   */
  const capturePerformance = (
    name: string,
    duration: number,
    metadata: Record<string, any> = {}
  ) => {
    if (!errorTrackingService || !enablePerformanceTracking) {return}

    const metric: PerformanceMetric = {
      name: `${component}.${name}`,
      duration,
      metadata: {
        component,
        ...metadata,
      },
    }

    errorTrackingService.capturePerformance(metric)
  }

  /**
   * Add breadcrumb with component context
   */
  const addBreadcrumb = (message: string, category = 'default', data: Record<string, any> = {}) => {
    if (!errorTrackingService) {return}

    errorTrackingService.addBreadcrumb(message, category, {
      component,
      ...data,
    })
  }

  /**
   * Set user context
   */
  const setUser = (user: any) => {
    if (!errorTrackingService) {return}
    errorTrackingService.setUser(user)
  }

  /**
   * Start a performance timer and return a function to end it
   */
  const startPerformanceTimer = (name: string): (() => void) => {
    const startTime = performance.now()
    performanceTimers.set(name, startTime)

    return () => {
      const endTime = performance.now()
      const duration = endTime - startTime
      performanceTimers.delete(name)
      capturePerformance(name, duration)
    }
  }

  /**
   * Track an async operation with automatic error handling and performance monitoring
   */
  const trackAsyncOperation = async <T>(name: string, operation: () => Promise<T>): Promise<T> => {
    const stopTimer = enablePerformanceTracking ? startPerformanceTimer(name) : null

    addBreadcrumb(`Starting ${name}`, 'async_operation')

    try {
      const result = await operation()
      addBreadcrumb(`Completed ${name}`, 'async_operation')
      return result
    } catch (error) {
      captureError(error instanceof Error ? error : new Error(String(error)), {
        action: name,
        metadata: {
          operationType: 'async',
          errorType: 'operation_failed',
        },
      })
      throw error
    } finally {
      stopTimer?.()
    }
  }

  /**
   * Execute an operation with error boundary
   */
  const withErrorBoundary = <T>(
    operation: () => T,
    context: Partial<ErrorContext> = {}
  ): T | null => {
    try {
      return operation()
    } catch (error) {
      captureError(error instanceof Error ? error : new Error(String(error)), {
        ...context,
        action: context.action || 'error_boundary_caught',
      })
      return null
    }
  }

  // Set up automatic error capture for the component
  if (autoCapture && instance) {
    onErrorCaptured((error, instance, info) => {
      captureError(error, {
        action: 'vue_error_captured',
        metadata: {
          errorInfo: info,
          capturedIn: component,
        },
      })

      // Return false to continue propagating the error
      return false
    })
  }

  // Component lifecycle tracking
  onMounted(() => {
    addBreadcrumb(`${component} mounted`, 'lifecycle')

    // Track component mount performance
    if (enablePerformanceTracking) {
      // Use Vue's internal timing if available
      const mountTime = instance?.appContext?.performance?.mount
      if (mountTime) {
        capturePerformance('mount', mountTime)
      }
    }
  })

  onUnmounted(() => {
    addBreadcrumb(`${component} unmounted`, 'lifecycle')

    // Clean up any remaining performance timers
    performanceTimers.clear()
  })

  return {
    captureError,
    captureMessage,
    capturePerformance,
    addBreadcrumb,
    setUser,
    startPerformanceTimer,
    trackAsyncOperation,
    withErrorBoundary,
  }
}

/**
 * Specialized composables for common scenarios
 */

export function useErrorTrackingForAPI() {
  const errorTracking = useErrorTracking({
    category: errorCategories.api,
    enablePerformanceTracking: true,
  })

  const trackAPICall = async <T>(
    endpoint: string,
    operation: () => Promise<T>,
    context: Partial<ErrorContext> = {}
  ): Promise<T> => {
    return errorTracking.trackAsyncOperation(`api_call_${endpoint}`, operation)
  }

  return {
    ...errorTracking,
    trackAPICall,
  }
}

export function useErrorTrackingForAuth() {
  return useErrorTracking({
    category: errorCategories.authentication,
    context: commonContexts.authLogin,
  })
}

export function useErrorTrackingForCamera() {
  return useErrorTracking({
    category: errorCategories.camera,
    context: commonContexts.faceRecognition,
    enablePerformanceTracking: true,
  })
}

export function useErrorTrackingForValidation() {
  return useErrorTracking({
    category: errorCategories.validation,
    enablePerformanceTracking: false,
  })
}

export function useErrorTrackingForLocation() {
  return useErrorTracking({
    category: errorCategories.location,
    context: commonContexts.locationService,
  })
}
