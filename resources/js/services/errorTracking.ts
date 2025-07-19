/**
 * Error Tracking Service
 *
 * Provides centralized error tracking and monitoring functionality.
 * Supports multiple providers (Sentry, LogRocket, custom) and
 * includes performance monitoring capabilities.
 */

import type { User } from '@/types/auth'

export interface ErrorContext {
  user?: Partial<User>
  component?: string
  action?: string
  metadata?: Record<string, any>
  timestamp?: string
  userAgent?: string
  url?: string
  stackTrace?: string
}

export interface PerformanceMetric {
  name: string
  duration: number
  metadata?: Record<string, any>
  timestamp?: string
}

export interface ErrorTrackingConfig {
  enabled: boolean
  environment: string
  dsn?: string
  sampleRate: number
  enablePerformanceMonitoring: boolean
  enableUserTracking: boolean
  beforeSend?: (error: Error, context: ErrorContext) => boolean | Error
  beforeSendPerformance?: (metric: PerformanceMetric) => boolean | PerformanceMetric
}

class ErrorTrackingService {
  private config: ErrorTrackingConfig
  private initialized = false
  private currentUser: Partial<User> | null = null
  private errorQueue: Array<{ error: Error; context: ErrorContext }> = []
  private performanceQueue: PerformanceMetric[] = []

  constructor(config: ErrorTrackingConfig) {
    this.config = {
      sampleRate: 1.0,
      enablePerformanceMonitoring: true,
      enableUserTracking: true,
      ...config,
    }
  }

  /**
   * Initialize error tracking service
   */
  async initialize(): Promise<void> {
    if (!this.config.enabled || this.initialized) {return}

    try {
      // Initialize Sentry if DSN is provided
      if (this.config.dsn) {
        await this.initializeSentry()
      }

      // Set up global error handlers
      this.setupGlobalErrorHandlers()

      // Set up performance monitoring
      if (this.config.enablePerformanceMonitoring) {
        this.setupPerformanceMonitoring()
      }

      this.initialized = true
      console.log('[ErrorTracking] Service initialized successfully')

      // Process queued errors
      this.processErrorQueue()
    } catch (error) {
      console.error('[ErrorTracking] Failed to initialize:', error)
    }
  }

  /**
   * Initialize Sentry integration
   */
  private async initializeSentry(): Promise<void> {
    try {
      // Dynamically import Sentry to avoid including it in main bundle if not needed
      const { init, setUser, captureException, captureMessage, addBreadcrumb } = await import(
        '@sentry/browser'
      )
      const { BrowserTracing } = await import('@sentry/tracing')

      init({
        dsn: this.config.dsn,
        environment: this.config.environment,
        sampleRate: this.config.sampleRate,
        integrations: [
          new BrowserTracing({
            tracingOrigins: [window.location.hostname],
            routingInstrumentation: this.getVueRoutingInstrumentation(),
          }),
        ],
        tracesSampleRate: this.config.enablePerformanceMonitoring ? 0.1 : 0,
        beforeSend: (event, hint) => {
          const error = hint.originalException as Error
          const context = (hint.extra as ErrorContext) || {}

          if (this.config.beforeSend) {
            const result = this.config.beforeSend(error, context)
            if (result === false) {return null}
            if (result instanceof Error) {
              // Update the event with modified error
              event.exception = {
                values: [
                  {
                    type: result.name,
                    value: result.message,
                    stacktrace: this.parseStackTrace(result.stack || ''),
                  },
                ],
              }
            }
          }

          return event
        },
      })

      console.log('[ErrorTracking] Sentry initialized')
    } catch (error) {
      console.warn('[ErrorTracking] Sentry not available:', error)
    }
  }

  /**
   * Set up global error handlers
   */
  private setupGlobalErrorHandlers(): void {
    // Handle unhandled promise rejections
    window.addEventListener('unhandledrejection', (event) => {
      this.captureError(new Error(event.reason), {
        component: 'Global',
        action: 'unhandledrejection',
        metadata: { reason: event.reason },
      })
    })

    // Handle global JavaScript errors
    window.addEventListener('error', (event) => {
      this.captureError(new Error(event.message), {
        component: 'Global',
        action: 'javascript_error',
        metadata: {
          filename: event.filename,
          lineno: event.lineno,
          colno: event.colno,
        },
      })
    })

    // Handle Vue error handler
    if (window.Vue) {
      const originalErrorHandler = window.Vue.config.errorHandler
      window.Vue.config.errorHandler = (err: Error, instance: any, info: string) => {
        this.captureError(err, {
          component: instance?.$options.name || 'Unknown',
          action: 'vue_error',
          metadata: { info, instance: this.sanitizeVueInstance(instance) },
        })

        if (originalErrorHandler) {
          originalErrorHandler(err, instance, info)
        }
      }
    }
  }

  /**
   * Set up performance monitoring
   */
  private setupPerformanceMonitoring(): void {
    // Monitor page load performance
    window.addEventListener('load', () => {
      setTimeout(() => {
        if (window.performance && window.performance.timing) {
          const timing = window.performance.timing
          const loadTime = timing.loadEventEnd - timing.navigationStart

          this.capturePerformance({
            name: 'page_load',
            duration: loadTime,
            metadata: {
              domContentLoaded: timing.domContentLoadedEventEnd - timing.navigationStart,
              domComplete: timing.domComplete - timing.navigationStart,
              firstPaint: this.getFirstPaintTime(),
            },
          })
        }
      }, 0)
    })

    // Monitor long tasks using PerformanceObserver if available
    if (window.PerformanceObserver) {
      try {
        const observer = new PerformanceObserver((list) => {
          list.getEntries().forEach((entry) => {
            if (entry.duration > 50) {
              // Tasks longer than 50ms
              this.capturePerformance({
                name: 'long_task',
                duration: entry.duration,
                metadata: {
                  startTime: entry.startTime,
                  entryType: entry.entryType,
                },
              })
            }
          })
        })

        observer.observe({ entryTypes: ['longtask'] })
      } catch (error) {
        console.warn('[ErrorTracking] PerformanceObserver not supported:', error)
      }
    }
  }

  /**
   * Capture an error with context
   */
  captureError(error: Error, context: ErrorContext = {}): void {
    if (!this.config.enabled) {return}

    const enrichedContext: ErrorContext = {
      ...context,
      user: this.currentUser || undefined,
      timestamp: new Date().toISOString(),
      userAgent: navigator.userAgent,
      url: window.location.href,
      stackTrace: error.stack,
    }

    // Apply beforeSend filter
    if (this.config.beforeSend) {
      const result = this.config.beforeSend(error, enrichedContext)
      if (result === false) {return}
      if (result instanceof Error) {
        error = result
      }
    }

    if (!this.initialized) {
      // Queue error for later processing
      this.errorQueue.push({ error, context: enrichedContext })
      return
    }

    try {
      // Send to Sentry if available
      if (window.Sentry) {
        window.Sentry.withScope((scope) => {
          scope.setContext('errorContext', enrichedContext)
          if (enrichedContext.component) {
            scope.setTag('component', enrichedContext.component)
          }
          if (enrichedContext.action) {
            scope.setTag('action', enrichedContext.action)
          }
          if (enrichedContext.metadata) {
            scope.setExtra('metadata', enrichedContext.metadata)
          }
          window.Sentry.captureException(error)
        })
      }

      // Log to console in development
      if (this.config.environment === 'development') {
        console.group(`[ErrorTracking] ${error.name}: ${error.message}`)
        console.error('Error:', error)
        console.log('Context:', enrichedContext)
        console.groupEnd()
      }

      // Send to custom endpoint if needed
      this.sendToCustomEndpoint(error, enrichedContext)
    } catch (trackingError) {
      console.error('[ErrorTracking] Failed to capture error:', trackingError)
    }
  }

  /**
   * Capture a performance metric
   */
  capturePerformance(metric: PerformanceMetric): void {
    if (!this.config.enabled || !this.config.enablePerformanceMonitoring) {return}

    const enrichedMetric: PerformanceMetric = {
      ...metric,
      timestamp: metric.timestamp || new Date().toISOString(),
    }

    // Apply beforeSendPerformance filter
    if (this.config.beforeSendPerformance) {
      const result = this.config.beforeSendPerformance(enrichedMetric)
      if (result === false) {return}
      if (result && typeof result === 'object') {
        Object.assign(enrichedMetric, result)
      }
    }

    if (!this.initialized) {
      this.performanceQueue.push(enrichedMetric)
      return
    }

    try {
      // Send to Sentry if available
      if (window.Sentry) {
        window.Sentry.addBreadcrumb({
          message: `Performance: ${metric.name}`,
          category: 'performance',
          data: enrichedMetric,
          level: 'info',
        })
      }

      // Log in development
      if (this.config.environment === 'development') {
        console.log(`[Performance] ${metric.name}: ${metric.duration}ms`, enrichedMetric)
      }
    } catch (error) {
      console.error('[ErrorTracking] Failed to capture performance:', error)
    }
  }

  /**
   * Capture a message with context
   */
  captureMessage(
    message: string,
    level: 'info' | 'warning' | 'error' = 'info',
    context?: ErrorContext
  ): void {
    if (!this.config.enabled) {return}

    try {
      if (window.Sentry) {
        window.Sentry.withScope((scope) => {
          if (context) {
            scope.setContext('messageContext', context)
          }
          window.Sentry.captureMessage(message, level)
        })
      }

      if (this.config.environment === 'development') {
        console.log(`[ErrorTracking] Message (${level}): ${message}`, context)
      }
    } catch (error) {
      console.error('[ErrorTracking] Failed to capture message:', error)
    }
  }

  /**
   * Set user context
   */
  setUser(user: Partial<User> | null): void {
    this.currentUser = user

    if (this.config.enableUserTracking && window.Sentry) {
      window.Sentry.setUser(
        user
          ? {
              id: user.id?.toString(),
              email: user.email,
              username: user.name,
            }
          : null
      )
    }
  }

  /**
   * Add breadcrumb
   */
  addBreadcrumb(message: string, category: string = 'default', data?: Record<string, any>): void {
    if (!this.config.enabled) {return}

    try {
      if (window.Sentry) {
        window.Sentry.addBreadcrumb({
          message,
          category,
          data,
          level: 'info',
        })
      }
    } catch (error) {
      console.error('[ErrorTracking] Failed to add breadcrumb:', error)
    }
  }

  /**
   * Process queued errors after initialization
   */
  private processErrorQueue(): void {
    while (this.errorQueue.length > 0) {
      const { error, context } = this.errorQueue.shift()!
      this.captureError(error, context)
    }

    while (this.performanceQueue.length > 0) {
      const metric = this.performanceQueue.shift()!
      this.capturePerformance(metric)
    }
  }

  /**
   * Send error to custom endpoint
   */
  private async sendToCustomEndpoint(error: Error, context: ErrorContext): Promise<void> {
    try {
      // Only send critical errors to avoid overwhelming the server
      if (context.action === 'vue_error' || context.action === 'unhandledrejection') {
        await fetch('/api/errors', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN':
              document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
          },
          body: JSON.stringify({
            error: {
              name: error.name,
              message: error.message,
              stack: error.stack,
            },
            context,
          }),
        })
      }
    } catch (sendError) {
      console.error('[ErrorTracking] Failed to send to custom endpoint:', sendError)
    }
  }

  /**
   * Get Vue routing instrumentation for Sentry
   */
  private getVueRoutingInstrumentation(): any {
    // This would integrate with Vue Router if available
    return undefined
  }

  /**
   * Parse stack trace into Sentry format
   */
  private parseStackTrace(stack: string): any {
    return {
      frames: stack.split('\n').map((line, index) => ({
        filename: line,
        lineno: index + 1,
        function: 'unknown',
      })),
    }
  }

  /**
   * Sanitize Vue instance for logging
   */
  private sanitizeVueInstance(instance: any): any {
    if (!instance) {return null}

    return {
      name: instance.$options?.name,
      tag: instance.$vnode?.tag,
      propsData: instance.$options?.propsData,
    }
  }

  /**
   * Get first paint time
   */
  private getFirstPaintTime(): number | undefined {
    if (window.performance && window.performance.getEntriesByType) {
      const paintEntries = window.performance.getEntriesByType('paint')
      const firstPaint = paintEntries.find((entry) => entry.name === 'first-paint')
      return firstPaint ? firstPaint.startTime : undefined
    }
    return undefined
  }
}

// Create and export singleton instance
let errorTrackingService: ErrorTrackingService | null = null

export function createErrorTrackingService(config: ErrorTrackingConfig): ErrorTrackingService {
  if (errorTrackingService) {
    console.warn('[ErrorTracking] Service already created')
    return errorTrackingService
  }

  errorTrackingService = new ErrorTrackingService(config)
  return errorTrackingService
}

export function getErrorTrackingService(): ErrorTrackingService | null {
  return errorTrackingService
}

// Export types
export type { ErrorTrackingService }

// Convenience functions
export function captureError(error: Error, context?: ErrorContext): void {
  errorTrackingService?.captureError(error, context)
}

export function captureMessage(
  message: string,
  level?: 'info' | 'warning' | 'error',
  context?: ErrorContext
): void {
  errorTrackingService?.captureMessage(message, level, context)
}

export function capturePerformance(metric: PerformanceMetric): void {
  errorTrackingService?.capturePerformance(metric)
}

export function setUser(user: Partial<User> | null): void {
  errorTrackingService?.setUser(user)
}

export function addBreadcrumb(
  message: string,
  category?: string,
  data?: Record<string, any>
): void {
  errorTrackingService?.addBreadcrumb(message, category, data)
}
