/**
 * Error Tracking Configuration
 */

import type { ErrorTrackingConfig, ErrorContext } from '@/services/errorTracking'

// Get environment configuration from Laravel
declare global {
  interface Window {
    __ERROR_TRACKING_CONFIG__?: {
      enabled: boolean
      environment: string
      dsn?: string
      sampleRate?: number
      enablePerformanceMonitoring?: boolean
      enableUserTracking?: boolean
    }
  }
}

// Default configuration
const defaultConfig: ErrorTrackingConfig = {
  enabled: true,
  environment: 'development',
  sampleRate: 1.0,
  enablePerformanceMonitoring: true,
  enableUserTracking: true,
  beforeSend: (error: Error, context: ErrorContext) => {
    // Filter out noisy errors
    const ignoredErrors = [
      'Network Error',
      'Failed to fetch',
      'Load failed',
      'Script error.',
      'ResizeObserver loop limit exceeded',
      'Non-Error promise rejection captured',
    ]

    const errorMessage = error.message.toLowerCase()
    if (ignoredErrors.some((ignored) => errorMessage.includes(ignored.toLowerCase()))) {
      return false
    }

    // Filter out errors from browser extensions
    if (
      error.stack &&
      (error.stack.includes('chrome-extension://') ||
        error.stack.includes('moz-extension://') ||
        error.stack.includes('safari-extension://'))
    ) {
      return false
    }

    // Rate limit errors from the same component
    const componentKey = `${context.component || 'unknown'}_${error.name}`
    const now = Date.now()
    const rateLimitWindow = 60000 // 1 minute
    const maxErrorsPerWindow = 5

    const errorCounts = JSON.parse(sessionStorage.getItem('errorTracking_rateLimits') || '{}')
    const componentData = errorCounts[componentKey] || { count: 0, firstSeen: now }

    if (now - componentData.firstSeen > rateLimitWindow) {
      // Reset counter after window expires
      componentData.count = 1
      componentData.firstSeen = now
    } else {
      componentData.count++
    }

    errorCounts[componentKey] = componentData
    sessionStorage.setItem('errorTracking_rateLimits', JSON.stringify(errorCounts))

    if (componentData.count > maxErrorsPerWindow) {
      console.warn(`[ErrorTracking] Rate limit exceeded for ${componentKey}`)
      return false
    }

    return error
  },
  beforeSendPerformance: (metric) => {
    // Only send performance metrics that exceed thresholds
    const thresholds = {
      page_load: 3000, // 3 seconds
      long_task: 100, // 100ms
      api_call: 1000, // 1 second
      component_render: 16, // 16ms (60fps)
    }

    const threshold = thresholds[metric.name as keyof typeof thresholds]
    if (threshold && metric.duration < threshold) {
      return false
    }

    return metric
  },
}

// Get configuration from Laravel backend
function getConfigFromBackend(): Partial<ErrorTrackingConfig> {
  const backendConfig = window.__ERROR_TRACKING_CONFIG__
  if (!backendConfig) {
    console.warn('[ErrorTracking] No backend configuration found')
    return {}
  }

  return {
    enabled: backendConfig.enabled ?? true,
    environment: backendConfig.environment || 'development',
    dsn: backendConfig.dsn,
    sampleRate: backendConfig.sampleRate ?? 1.0,
    enablePerformanceMonitoring: backendConfig.enablePerformanceMonitoring ?? true,
    enableUserTracking: backendConfig.enableUserTracking ?? true,
  }
}

// Create final configuration by merging default and backend config
export function createErrorTrackingConfig(): ErrorTrackingConfig {
  const backendConfig = getConfigFromBackend()

  return {
    ...defaultConfig,
    ...backendConfig,
  }
}

// Environment-specific configurations
export const environmentConfigs = {
  development: {
    enabled: true,
    sampleRate: 1.0,
    enablePerformanceMonitoring: true,
    enableUserTracking: false,
  },
  staging: {
    enabled: true,
    sampleRate: 0.5,
    enablePerformanceMonitoring: true,
    enableUserTracking: true,
  },
  production: {
    enabled: true,
    sampleRate: 0.1,
    enablePerformanceMonitoring: false,
    enableUserTracking: true,
  },
} as const

// Error categories for better organization
export const errorCategories = {
  authentication: 'auth',
  api: 'api',
  validation: 'validation',
  camera: 'camera',
  location: 'location',
  storage: 'storage',
  network: 'network',
  render: 'render',
  navigation: 'navigation',
} as const

// Common error contexts
export const commonContexts = {
  faceRecognition: {
    component: 'FaceRecognition',
    category: errorCategories.camera,
  },
  attendanceForm: {
    component: 'AttendanceForm',
    category: errorCategories.validation,
  },
  authLogin: {
    component: 'LoginForm',
    category: errorCategories.authentication,
  },
  scheduleModal: {
    component: 'ScheduleModal',
    category: errorCategories.validation,
  },
  locationService: {
    component: 'LocationService',
    category: errorCategories.location,
  },
} as const

export type ErrorCategory = (typeof errorCategories)[keyof typeof errorCategories]
export type CommonContext = (typeof commonContexts)[keyof typeof commonContexts]
