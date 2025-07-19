import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type {
  AttendanceStatus,
  AttendanceRecord,
  CheckInData,
  CheckOutData,
} from '@/types/attendance'

export const useAttendanceStore = defineStore('attendance', () => {
  // State
  const currentStatus = ref<AttendanceStatus | null>(null)
  const todayAttendance = ref<AttendanceRecord | null>(null)
  const attendanceHistory = ref<AttendanceRecord[]>([])
  const isLoading = ref<boolean>(false)
  const processing = ref<boolean>(false)
  const error = ref<string | null>(null)

  // Location and face detection state
  const currentLocation = ref<GeolocationPosition | null>(null)
  const faceDetectionActive = ref<boolean>(false)
  const lastFaceDetection = ref<any>(null)

  // Statistics
  const monthlyStats = ref<{
    present_days: number
    absent_days: number
    late_days: number
    total_working_hours: number
    overtime_hours: number
    average_checkin_time: string
    average_checkout_time: string
  } | null>(null)

  // Getters
  const isCheckedIn = computed(
    () => currentStatus.value?.check_in_time && !currentStatus.value?.check_out_time
  )

  const isCheckedOut = computed(
    () => currentStatus.value?.check_in_time && currentStatus.value?.check_out_time
  )

  const canCheckIn = computed(() => !currentStatus.value?.check_in_time && !processing.value)

  const canCheckOut = computed(
    () =>
      currentStatus.value?.check_in_time &&
      !currentStatus.value?.check_out_time &&
      !processing.value
  )

  const workingHoursToday = computed(() => {
    if (!currentStatus.value) {return 0}
    return currentStatus.value.working_hours || 0
  })

  const statusColor = computed(() => {
    if (!currentStatus.value) {return 'gray'}

    switch (currentStatus.value.status) {
      case 'present':
        return 'green'
      case 'late':
        return 'yellow'
      case 'absent':
        return 'red'
      case 'early':
        return 'blue'
      default:
        return 'gray'
    }
  })

  const recentAttendance = computed(() =>
    attendanceHistory.value
      .sort((a, b) => new Date(b.date).getTime() - new Date(a.date).getTime())
      .slice(0, 7)
  )

  // Actions
  const fetchTodayStatus = async (): Promise<void> => {
    isLoading.value = true
    error.value = null

    try {
      const response = await fetch('/api/attendance/today', {
        headers: {
          Authorization: `Bearer ${getAuthToken()}`,
          'X-CSRF-TOKEN': getCSRFToken(),
        },
      })

      if (!response.ok) {
        throw new Error('Failed to fetch today\'s attendance status')
      }

      const data = await response.json()
      currentStatus.value = data.status
      todayAttendance.value = data.attendance
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Failed to fetch attendance status'
      console.error('Fetch today status error:', err)
    } finally {
      isLoading.value = false
    }
  }

  const fetchAttendanceHistory = async (
    params: {
      start_date?: string
      end_date?: string
      limit?: number
      page?: number
    } = {}
  ): Promise<void> => {
    isLoading.value = true
    error.value = null

    try {
      const searchParams = new URLSearchParams()
      if (params.start_date) {searchParams.append('start_date', params.start_date)}
      if (params.end_date) {searchParams.append('end_date', params.end_date)}
      if (params.limit) {searchParams.append('limit', params.limit.toString())}
      if (params.page) {searchParams.append('page', params.page.toString())}

      const response = await fetch(`/api/attendance/history?${searchParams}`, {
        headers: {
          Authorization: `Bearer ${getAuthToken()}`,
          'X-CSRF-TOKEN': getCSRFToken(),
        },
      })

      if (!response.ok) {
        throw new Error('Failed to fetch attendance history')
      }

      const data = await response.json()
      attendanceHistory.value = data.attendance || data.data || []
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Failed to fetch attendance history'
      console.error('Fetch attendance history error:', err)
    } finally {
      isLoading.value = false
    }
  }

  const fetchMonthlyStats = async (month?: string, year?: string): Promise<void> => {
    isLoading.value = true
    error.value = null

    try {
      const params = new URLSearchParams()
      if (month) {params.append('month', month)}
      if (year) {params.append('year', year)}

      const response = await fetch(`/api/attendance/stats/monthly?${params}`, {
        headers: {
          Authorization: `Bearer ${getAuthToken()}`,
          'X-CSRF-TOKEN': getCSRFToken(),
        },
      })

      if (!response.ok) {
        throw new Error('Failed to fetch monthly statistics')
      }

      const data = await response.json()
      monthlyStats.value = data.stats
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Failed to fetch monthly stats'
      console.error('Fetch monthly stats error:', err)
    } finally {
      isLoading.value = false
    }
  }

  const checkIn = async (data: CheckInData): Promise<void> => {
    processing.value = true
    error.value = null

    try {
      const response = await fetch('/api/attendance/check-in', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${getAuthToken()}`,
          'X-CSRF-TOKEN': getCSRFToken(),
        },
        body: JSON.stringify(data),
      })

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.message || 'Check-in failed')
      }

      const responseData = await response.json()

      // Update local state
      currentStatus.value = responseData.status
      todayAttendance.value = responseData.attendance

      // Add to history
      if (responseData.attendance) {
        const existingIndex = attendanceHistory.value.findIndex(
          (a) => a.date === responseData.attendance.date
        )
        if (existingIndex > -1) {
          attendanceHistory.value[existingIndex] = responseData.attendance
        } else {
          attendanceHistory.value.unshift(responseData.attendance)
        }
      }
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Check-in failed'
      console.error('Check-in error:', err)
      throw err
    } finally {
      processing.value = false
    }
  }

  const checkOut = async (data: CheckOutData): Promise<void> => {
    processing.value = true
    error.value = null

    try {
      const response = await fetch('/api/attendance/check-out', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${getAuthToken()}`,
          'X-CSRF-TOKEN': getCSRFToken(),
        },
        body: JSON.stringify(data),
      })

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.message || 'Check-out failed')
      }

      const responseData = await response.json()

      // Update local state
      currentStatus.value = responseData.status
      todayAttendance.value = responseData.attendance

      // Update in history
      if (responseData.attendance) {
        const existingIndex = attendanceHistory.value.findIndex(
          (a) => a.date === responseData.attendance.date
        )
        if (existingIndex > -1) {
          attendanceHistory.value[existingIndex] = responseData.attendance
        }
      }
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Check-out failed'
      console.error('Check-out error:', err)
      throw err
    } finally {
      processing.value = false
    }
  }

  const getCurrentLocation = async (): Promise<GeolocationPosition> => {
    return new Promise((resolve, reject) => {
      if (!navigator.geolocation) {
        reject(new Error('Geolocation is not supported by this browser'))
        return
      }

      navigator.geolocation.getCurrentPosition(
        (position) => {
          currentLocation.value = position
          resolve(position)
        },
        (error) => {
          let message = 'Failed to get location'
          switch (error.code) {
            case error.PERMISSION_DENIED:
              message = 'Location access denied by user'
              break
            case error.POSITION_UNAVAILABLE:
              message = 'Location information unavailable'
              break
            case error.TIMEOUT:
              message = 'Location request timed out'
              break
          }
          reject(new Error(message))
        },
        {
          enableHighAccuracy: true,
          timeout: 10000,
          maximumAge: 300000, // 5 minutes
        }
      )
    })
  }

  const validateLocation = async (location: GeolocationPosition): Promise<boolean> => {
    try {
      const response = await fetch('/api/attendance/validate-location', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${getAuthToken()}`,
          'X-CSRF-TOKEN': getCSRFToken(),
        },
        body: JSON.stringify({
          latitude: location.coords.latitude,
          longitude: location.coords.longitude,
          accuracy: location.coords.accuracy,
        }),
      })

      if (!response.ok) {
        throw new Error('Location validation failed')
      }

      const data = await response.json()
      return data.valid === true
    } catch (err) {
      console.error('Location validation error:', err)
      return false
    }
  }

  const startFaceDetection = (): void => {
    faceDetectionActive.value = true
  }

  const stopFaceDetection = (): void => {
    faceDetectionActive.value = false
    lastFaceDetection.value = null
  }

  const updateFaceDetection = (detectionData: any): void => {
    lastFaceDetection.value = detectionData
  }

  // Helper functions
  const getAuthToken = (): string => {
    return localStorage.getItem('auth_token') || ''
  }

  const getCSRFToken = (): string => {
    const token = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement
    return token?.content || ''
  }

  // Reset store state
  const reset = (): void => {
    currentStatus.value = null
    todayAttendance.value = null
    attendanceHistory.value = []
    error.value = null
    currentLocation.value = null
    faceDetectionActive.value = false
    lastFaceDetection.value = null
    monthlyStats.value = null
  }

  // Initialize store
  const init = async (): Promise<void> => {
    await Promise.allSettled([
      fetchTodayStatus(),
      fetchAttendanceHistory({ limit: 7 }),
      fetchMonthlyStats(),
    ])
  }

  return {
    // State
    currentStatus,
    todayAttendance,
    attendanceHistory,
    isLoading,
    processing,
    error,
    currentLocation,
    faceDetectionActive,
    lastFaceDetection,
    monthlyStats,

    // Getters
    isCheckedIn,
    isCheckedOut,
    canCheckIn,
    canCheckOut,
    workingHoursToday,
    statusColor,
    recentAttendance,

    // Actions
    fetchTodayStatus,
    fetchAttendanceHistory,
    fetchMonthlyStats,
    checkIn,
    checkOut,
    getCurrentLocation,
    validateLocation,
    startFaceDetection,
    stopFaceDetection,
    updateFaceDetection,
    reset,
    init,
  }
})
