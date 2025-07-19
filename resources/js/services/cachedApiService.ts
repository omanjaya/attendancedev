/**
 * Cached API Service
 *
 * Provides cached API methods for frequently accessed data
 * with automatic cache invalidation and background refresh.
 */

import { cachedRequest, invalidateCache, getRequestCacheService } from '@/services/requestCache'
import { useErrorTracking } from '@/composables/useErrorTracking'

export interface Employee {
  id: string
  employee_id: string
  name: string
  full_name: string
  email: string
  department?: string
  position?: string
  status: 'active' | 'inactive'
}

export interface Subject {
  id: string
  name: string
  display_name: string
  code: string
  credits: number
  department?: string
}

export interface Schedule {
  id: string
  subject_id: string
  employee_id: string
  academic_class_id: string
  day_of_week: string
  time_slot_id: string
  room?: string
  effective_from: string
  subject?: Subject
  employee?: Employee
}

export interface AttendanceRecord {
  id: string
  employee_id: string
  date: string
  check_in: string | null
  check_out: string | null
  status: 'present' | 'absent' | 'late' | 'early_leave'
  location?: string
  notes?: string
}

export interface ApiResponse<T> {
  success: boolean
  data: T
  message?: string
  errors?: Record<string, string[]>
}

class CachedApiService {
  private baseUrl = '/api'
  private errorTracking = useErrorTracking()

  /**
   * Employee-related cached methods
   */
  async getEmployees(
    options: {
      department?: string
      status?: string
      search?: string
      ttl?: number
    } = {}
  ): Promise<Employee[]> {
    const params = new URLSearchParams()
    if (options.department) {params.set('department', options.department)}
    if (options.status) {params.set('status', options.status)}
    if (options.search) {params.set('search', options.search)}

    const cacheKey = `employees:list${params.toString() ? `?${params}` : ''}`

    return cachedRequest(
      cacheKey,
      async () => {
        this.errorTracking.addBreadcrumb('Fetching employees list', 'api', {
          params: Object.fromEntries(params),
        })

        const response = await fetch(`${this.baseUrl}/employees?${params}`, {
          headers: this.getDefaultHeaders(),
        })

        return this.handleResponse<Employee[]>(response)
      },
      {
        ttl: options.ttl || 10 * 60 * 1000, // 10 minutes default
        metadata: { endpoint: 'employees', params: Object.fromEntries(params) },
      }
    )
  }

  async getEmployee(id: string, ttl?: number): Promise<Employee> {
    const cacheKey = `employees:${id}`

    return cachedRequest(
      cacheKey,
      async () => {
        this.errorTracking.addBreadcrumb('Fetching employee details', 'api', { employeeId: id })

        const response = await fetch(`${this.baseUrl}/employees/${id}`, {
          headers: this.getDefaultHeaders(),
        })

        return this.handleResponse<Employee>(response)
      },
      {
        ttl: ttl || 15 * 60 * 1000, // 15 minutes default
        metadata: { endpoint: 'employee', employeeId: id },
      }
    )
  }

  /**
   * Subject-related cached methods
   */
  async getSubjects(
    options: {
      department?: string
      active?: boolean
      ttl?: number
    } = {}
  ): Promise<Subject[]> {
    const params = new URLSearchParams()
    if (options.department) {params.set('department', options.department)}
    if (options.active !== undefined) {params.set('active', options.active.toString())}

    const cacheKey = `subjects:list${params.toString() ? `?${params}` : ''}`

    return cachedRequest(
      cacheKey,
      async () => {
        this.errorTracking.addBreadcrumb('Fetching subjects list', 'api', {
          params: Object.fromEntries(params),
        })

        const response = await fetch(`${this.baseUrl}/subjects?${params}`, {
          headers: this.getDefaultHeaders(),
        })

        return this.handleResponse<Subject[]>(response)
      },
      {
        ttl: options.ttl || 30 * 60 * 1000, // 30 minutes default
        metadata: { endpoint: 'subjects', params: Object.fromEntries(params) },
      }
    )
  }

  /**
   * Schedule-related cached methods
   */
  async getSchedules(
    options: {
      academicClassId?: string
      employeeId?: string
      date?: string
      dayOfWeek?: string
      ttl?: number
    } = {}
  ): Promise<Schedule[]> {
    const params = new URLSearchParams()
    if (options.academicClassId) {params.set('academic_class_id', options.academicClassId)}
    if (options.employeeId) {params.set('employee_id', options.employeeId)}
    if (options.date) {params.set('date', options.date)}
    if (options.dayOfWeek) {params.set('day_of_week', options.dayOfWeek)}

    const cacheKey = `schedules:list${params.toString() ? `?${params}` : ''}`

    return cachedRequest(
      cacheKey,
      async () => {
        this.errorTracking.addBreadcrumb('Fetching schedules list', 'api', {
          params: Object.fromEntries(params),
        })

        const response = await fetch(`${this.baseUrl}/academic-schedules?${params}`, {
          headers: this.getDefaultHeaders(),
        })

        return this.handleResponse<Schedule[]>(response)
      },
      {
        ttl: options.ttl || 5 * 60 * 1000, // 5 minutes default
        metadata: { endpoint: 'schedules', params: Object.fromEntries(params) },
      }
    )
  }

  async getAvailableTeachers(options: {
    subjectId: string
    dayOfWeek: string
    timeSlotId: string
    ttl?: number
  }): Promise<Employee[]> {
    const cacheKey = `available-teachers:${options.subjectId}:${options.dayOfWeek}:${options.timeSlotId}`

    return cachedRequest(
      cacheKey,
      async () => {
        this.errorTracking.addBreadcrumb('Fetching available teachers', 'api', {
          subjectId: options.subjectId,
          dayOfWeek: options.dayOfWeek,
          timeSlotId: options.timeSlotId,
        })

        const response = await fetch(`${this.baseUrl}/academic-schedules/available-teachers`, {
          method: 'POST',
          headers: this.getDefaultHeaders(),
          body: JSON.stringify({
            subject_id: options.subjectId,
            day_of_week: options.dayOfWeek,
            time_slot_id: options.timeSlotId,
          }),
        })

        return this.handleResponse<Employee[]>(response)
      },
      {
        ttl: options.ttl || 2 * 60 * 1000, // 2 minutes default
        metadata: {
          endpoint: 'available-teachers',
          subjectId: options.subjectId,
          dayOfWeek: options.dayOfWeek,
          timeSlotId: options.timeSlotId,
        },
      }
    )
  }

  /**
   * Attendance-related cached methods
   */
  async getAttendanceRecords(
    options: {
      employeeId?: string
      date?: string
      dateFrom?: string
      dateTo?: string
      status?: string
      limit?: number
      ttl?: number
    } = {}
  ): Promise<AttendanceRecord[]> {
    const params = new URLSearchParams()
    if (options.employeeId) {params.set('employee_id', options.employeeId)}
    if (options.date) {params.set('date', options.date)}
    if (options.dateFrom) {params.set('date_from', options.dateFrom)}
    if (options.dateTo) {params.set('date_to', options.dateTo)}
    if (options.status) {params.set('status', options.status)}
    if (options.limit) {params.set('limit', options.limit.toString())}

    const cacheKey = `attendance:list${params.toString() ? `?${params}` : ''}`

    return cachedRequest(
      cacheKey,
      async () => {
        this.errorTracking.addBreadcrumb('Fetching attendance records', 'api', {
          params: Object.fromEntries(params),
        })

        const response = await fetch(`${this.baseUrl}/attendance?${params}`, {
          headers: this.getDefaultHeaders(),
        })

        return this.handleResponse<AttendanceRecord[]>(response)
      },
      {
        ttl: options.ttl || 1 * 60 * 1000, // 1 minute default for real-time data
        metadata: { endpoint: 'attendance', params: Object.fromEntries(params) },
      }
    )
  }

  async getAttendanceStatistics(
    options: {
      employeeId?: string
      period?: 'daily' | 'weekly' | 'monthly'
      date?: string
      ttl?: number
    } = {}
  ): Promise<any> {
    const params = new URLSearchParams()
    if (options.employeeId) {params.set('employee_id', options.employeeId)}
    if (options.period) {params.set('period', options.period)}
    if (options.date) {params.set('date', options.date)}

    const cacheKey = `attendance:stats${params.toString() ? `?${params}` : ''}`

    return cachedRequest(
      cacheKey,
      async () => {
        this.errorTracking.addBreadcrumb('Fetching attendance statistics', 'api', {
          params: Object.fromEntries(params),
        })

        const response = await fetch(`${this.baseUrl}/attendance/statistics?${params}`, {
          headers: this.getDefaultHeaders(),
        })

        return this.handleResponse<any>(response)
      },
      {
        ttl: options.ttl || 5 * 60 * 1000, // 5 minutes default
        metadata: { endpoint: 'attendance-stats', params: Object.fromEntries(params) },
      }
    )
  }

  /**
   * Cache invalidation methods
   */
  invalidateEmployeeCache(employeeId?: string): void {
    if (employeeId) {
      invalidateCache(`employees:${employeeId}`)
      invalidateCache(/^available-teachers:.*/) // Invalidate teacher availability cache
    } else {
      invalidateCache(/^employees:.*/)
      invalidateCache(/^available-teachers:.*/)
    }

    this.errorTracking.addBreadcrumb('Invalidated employee cache', 'cache', { employeeId })
  }

  invalidateSubjectCache(subjectId?: string): void {
    if (subjectId) {
      invalidateCache(`subjects:${subjectId}`)
      invalidateCache(/^available-teachers:.*/) // Invalidate teacher availability cache
    } else {
      invalidateCache(/^subjects:.*/)
      invalidateCache(/^available-teachers:.*/)
    }

    this.errorTracking.addBreadcrumb('Invalidated subject cache', 'cache', { subjectId })
  }

  invalidateScheduleCache(academicClassId?: string): void {
    if (academicClassId) {
      invalidateCache(new RegExp(`schedules:.*academic_class_id=${academicClassId}`))
    } else {
      invalidateCache(/^schedules:.*/)
    }
    invalidateCache(/^available-teachers:.*/) // Invalidate teacher availability cache

    this.errorTracking.addBreadcrumb('Invalidated schedule cache', 'cache', { academicClassId })
  }

  invalidateAttendanceCache(employeeId?: string): void {
    if (employeeId) {
      invalidateCache(new RegExp(`attendance:.*employee_id=${employeeId}`))
    } else {
      invalidateCache(/^attendance:.*/)
    }

    this.errorTracking.addBreadcrumb('Invalidated attendance cache', 'cache', { employeeId })
  }

  /**
   * Bulk cache operations
   */
  async prefetchEmployeeData(employeeIds: string[]): Promise<void> {
    const cacheService = getRequestCacheService()
    if (!cacheService) {return}

    const requests = employeeIds.map((id) => ({
      key: `employees:${id}`,
      fetchFn: () => this.getEmployee(id),
    }))

    await cacheService.prefetch(requests)
    this.errorTracking.addBreadcrumb('Prefetched employee data', 'cache', {
      count: employeeIds.length,
    })
  }

  async warmUpCommonData(): Promise<void> {
    try {
      // Warm up frequently accessed data
      await Promise.allSettled([
        this.getSubjects({ active: true }),
        this.getEmployees({ status: 'active' }),
        this.getAttendanceStatistics({
          period: 'daily',
          date: new Date().toISOString().split('T')[0],
        }),
      ])

      this.errorTracking.addBreadcrumb('Warmed up common data cache', 'cache')
    } catch (error) {
      this.errorTracking.captureError(error instanceof Error ? error : new Error(String(error)), {
        action: 'cache_warmup_failed',
      })
    }
  }

  /**
   * Private helper methods
   */
  private getDefaultHeaders(): Record<string, string> {
    const headers: Record<string, string> = {
      'Content-Type': 'application/json',
      Accept: 'application/json',
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    if (csrfToken) {
      headers['X-CSRF-TOKEN'] = csrfToken
    }

    return headers
  }

  private async handleResponse<T>(response: Response): Promise<T> {
    if (!response.ok) {
      const errorText = await response.text()
      throw new Error(`HTTP ${response.status}: ${errorText || response.statusText}`)
    }

    const data: ApiResponse<T> = await response.json()

    if (!data.success) {
      throw new Error(data.message || 'API request failed')
    }

    return data.data
  }
}

// Create and export singleton instance
export const cachedApiService = new CachedApiService()

// Export convenience methods
export const {
  getEmployees,
  getEmployee,
  getSubjects,
  getSchedules,
  getAvailableTeachers,
  getAttendanceRecords,
  getAttendanceStatistics,
  invalidateEmployeeCache,
  invalidateSubjectCache,
  invalidateScheduleCache,
  invalidateAttendanceCache,
  prefetchEmployeeData,
  warmUpCommonData,
} = cachedApiService
