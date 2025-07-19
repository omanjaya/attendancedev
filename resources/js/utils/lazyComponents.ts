import { defineAsyncComponent, type AsyncComponentLoader, type Component } from 'vue'
import type { ErrorComponent, LoadingComponent } from 'vue'

// Loading component for async components
const LoadingComponent: LoadingComponent = {
  template: `
    <div class="flex items-center justify-center p-8">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary" role="status">
        <span class="sr-only">Loading component...</span>
      </div>
    </div>
  `,
}

// Error component for failed async components
const ErrorComponent: ErrorComponent = {
  template: `
    <div class="flex items-center justify-center p-8">
      <div class="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium text-red-800 dark:text-red-200">
              Failed to load component
            </p>
            <p class="mt-1 text-sm text-red-700 dark:text-red-300">
              Please refresh the page or try again later.
            </p>
          </div>
        </div>
      </div>
    </div>
  `,
}

interface LazyComponentOptions {
  /**
   * Loading component to show while the async component is loading
   */
  loadingComponent?: LoadingComponent

  /**
   * Error component to show when the async component fails to load
   */
  errorComponent?: ErrorComponent

  /**
   * Delay before showing the loading component (in ms)
   * @default 200
   */
  delay?: number

  /**
   * Timeout for loading the component (in ms)
   * @default 30000
   */
  timeout?: number

  /**
   * Whether to show suspense wrapper
   * @default false
   */
  suspensible?: boolean

  /**
   * Retry attempts when component fails to load
   * @default 3
   */
  retryAttempts?: number

  /**
   * Delay between retry attempts (in ms)
   * @default 1000
   */
  retryDelay?: number
}

/**
 * Create a lazy-loaded component with enhanced loading states and error handling
 */
export function createLazyComponent(
  loader: AsyncComponentLoader,
  options: LazyComponentOptions = {}
): Component {
  const {
    loadingComponent = LoadingComponent,
    errorComponent = ErrorComponent,
    delay = 200,
    timeout = 30000,
    suspensible = false,
    retryAttempts = 3,
    retryDelay = 1000,
  } = options

  let retryCount = 0

  const enhancedLoader = async (): Promise<Component> => {
    try {
      console.log(`Loading component... (attempt ${retryCount + 1}/${retryAttempts + 1})`)

      const component = await loader()

      // Reset retry count on successful load
      retryCount = 0

      console.log('Component loaded successfully')
      return component
    } catch (error) {
      console.error('Failed to load component:', error)

      // Retry if attempts remaining
      if (retryCount < retryAttempts) {
        retryCount++
        console.log(`Retrying in ${retryDelay}ms... (${retryCount}/${retryAttempts})`)

        await new Promise((resolve) => setTimeout(resolve, retryDelay))
        return enhancedLoader()
      }

      // Track failed component loads
      if (window.errorTracker) {
        window.errorTracker.captureException(error, {
          component: 'LazyComponent',
          extra: {
            retryCount,
            retryAttempts,
            timeout,
          },
        })
      }

      throw error
    }
  }

  return defineAsyncComponent({
    loader: enhancedLoader,
    loadingComponent,
    errorComponent,
    delay,
    timeout,
    suspensible,
  })
}

/**
 * Preload a component for better perceived performance
 */
export function preloadComponent(loader: AsyncComponentLoader): Promise<Component> {
  return loader()
}

/**
 * Create multiple lazy components with shared options
 */
export function createLazyComponents(
  components: Record<string, AsyncComponentLoader>,
  options: LazyComponentOptions = {}
): Record<string, Component> {
  const lazyComponents: Record<string, Component> = {}

  for (const [name, loader] of Object.entries(components)) {
    lazyComponents[name] = createLazyComponent(loader, options)
  }

  return lazyComponents
}

/**
 * Commonly used lazy components for the attendance system
 */
export const LazyComponents = createLazyComponents(
  {
    // Face Recognition Components
    FaceRecognition: () => import('@/components/FaceRecognition.vue'),
    FaceEnrollment: () => import('@/components/FaceEnrollment.vue'),
    FaceAnalytics: () => import('@/components/FaceAnalytics.vue'),
    FaceTemplateManagement: () => import('@/components/FaceTemplateManagement.vue'),

    // Attendance Components
    AttendanceWidget: () => import('@/attendance/AttendanceWidget.vue'),
    AttendanceReporting: () => import('@/components/AttendanceReporting.vue'),

    // Schedule Components
    ScheduleCalendarView: () => import('@/components/ScheduleCalendarView.vue'),
    ScheduleGrid: () => import('@/components/ScheduleGrid.vue'),
    ScheduleModal: () => import('@/components/ScheduleModal.vue'),
    JadwalPatraCalendarView: () => import('@/components/JadwalPatraCalendarView.vue'),

    // Security Components
    SecurityDashboard: () => import('@/components/Security/SecurityDashboard.vue'),
    TwoFactorSetup: () => import('@/components/Security/TwoFactorSetup.vue'),
    TwoFactorDashboard: () => import('@/components/Security/TwoFactorDashboard.vue'),
    DeviceManagement: () => import('@/components/DeviceManagement.vue'),

    // Notification Components
    NotificationCenter: () => import('@/components/NotificationCenter.vue'),
    NotificationPreferences: () => import('@/components/NotificationPreferences.vue'),

    // Admin Components (heavy, rarely used)
    FaceRecognitionSettings: () => import('@/components/FaceRecognitionSettings.vue'),
    FaceRecognitionBackupRestore: () => import('@/components/FaceRecognitionBackupRestore.vue'),
    FaceRecognitionAuditLogs: () => import('@/components/FaceRecognitionAuditLogs.vue'),
  },
  {
    delay: 200,
    timeout: 30000,
    retryAttempts: 3,
    retryDelay: 1000,
  }
)

/**
 * High-priority components that should be preloaded
 */
export const preloadCriticalComponents = async (): Promise<void> => {
  const criticalComponents = [
    () => import('@/attendance/AttendanceWidget.vue'),
    () => import('@/components/NotificationCenter.vue'),
    () => import('@/components/ToastNotification.vue'),
  ]

  try {
    await Promise.allSettled(criticalComponents.map(preloadComponent))
    console.log('Critical components preloaded')
  } catch (error) {
    console.warn('Some critical components failed to preload:', error)
  }
}

/**
 * Preload components based on user role or route
 */
export const preloadByContext = async (context: string): Promise<void> => {
  const contextComponents: Record<string, AsyncComponentLoader[]> = {
    admin: [
      () => import('@/components/FaceRecognitionSettings.vue'),
      () => import('@/components/Security/SecurityDashboard.vue'),
      () => import('@/components/DeviceManagement.vue'),
    ],
    employee: [
      () => import('@/components/FaceRecognition.vue'),
      () => import('@/attendance/AttendanceWidget.vue'),
    ],
    schedule: [
      () => import('@/components/ScheduleCalendarView.vue'),
      () => import('@/components/ScheduleGrid.vue'),
    ],
    reports: [
      () => import('@/components/AttendanceReporting.vue'),
      () => import('@/components/FaceAnalytics.vue'),
    ],
  }

  const components = contextComponents[context]
  if (components) {
    try {
      await Promise.allSettled(components.map(preloadComponent))
      console.log(`${context} components preloaded`)
    } catch (error) {
      console.warn(`Some ${context} components failed to preload:`, error)
    }
  }
}

// Auto-preload critical components when module loads
if (typeof window !== 'undefined') {
  // Preload after a short delay to not block initial page load
  setTimeout(preloadCriticalComponents, 2000)
}
