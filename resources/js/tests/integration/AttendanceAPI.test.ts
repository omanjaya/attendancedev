import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { useAttendanceStore } from '@/stores/attendance'
import { createPinia, setActivePinia } from 'pinia'

// Mock fetch globally
const mockFetch = vi.fn()
global.fetch = mockFetch

// Mock geolocation
const mockGeolocation = {
  getCurrentPosition: vi.fn(),
  watchPosition: vi.fn(),
  clearWatch: vi.fn(),
}
Object.defineProperty(navigator, 'geolocation', {
  value: mockGeolocation,
})

describe('Attendance API Integration Tests', () => {
  let store: any

  beforeEach(() => {
    vi.clearAllMocks()
    setActivePinia(createPinia())
    store = useAttendanceStore()

    // Mock CSRF token
    const mockMetaElement = {
      content: 'mock-csrf-token',
    } as HTMLMetaElement

    vi.spyOn(document, 'querySelector').mockImplementation((selector) => {
      if (selector === 'meta[name="csrf-token"]') {
        return mockMetaElement
      }
      return null
    })

    // Mock localStorage for auth token
    Object.defineProperty(window, 'localStorage', {
      value: {
        getItem: vi.fn().mockReturnValue('mock-auth-token'),
        setItem: vi.fn(),
        removeItem: vi.fn(),
      },
    })
  })

  afterEach(() => {
    vi.restoreAllMocks()
  })

  describe('Fetch Today\'s Attendance Status', () => {
    it('should fetch today\'s attendance status successfully', async () => {
      const mockResponse = {
        status: {
          status: 'present',
          check_in_time: '08:30:00',
          check_out_time: null,
          working_hours: 4.5,
          date: '2025-01-15',
          employee_id: 'EMP001',
        },
        attendance: {
          id: 'att-123',
          employee_id: 'EMP001',
          date: '2025-01-15',
          check_in_time: '08:30:00',
          check_out_time: null,
          status: 'present',
          working_hours: 4.5,
          location_in: 'Office Main',
          face_confidence_in: 95,
          created_at: '2025-01-15T08:30:00Z',
          updated_at: '2025-01-15T08:30:00Z',
        },
      }

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(mockResponse),
      })

      await store.fetchTodayStatus()

      expect(mockFetch).toHaveBeenCalledWith('/api/attendance/today', {
        headers: {
          Authorization: 'Bearer mock-auth-token',
          'X-CSRF-TOKEN': 'mock-csrf-token',
        },
      })

      expect(store.currentStatus).toEqual(mockResponse.status)
      expect(store.todayAttendance).toEqual(mockResponse.attendance)
      expect(store.isLoading).toBe(false)
      expect(store.error).toBe(null)
    })

    it('should handle API errors when fetching status', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 401,
        json: () =>
          Promise.resolve({
            message: 'Unauthorized',
          }),
      })

      await store.fetchTodayStatus()

      expect(store.error).toBe('Failed to fetch today\'s attendance status')
      expect(store.currentStatus).toBe(null)
    })
  })

  describe('Check-in Process', () => {
    beforeEach(() => {
      // Mock successful location
      mockGeolocation.getCurrentPosition.mockImplementation((success) => {
        success({
          coords: {
            latitude: -6.2088,
            longitude: 106.8456,
            accuracy: 10,
          },
          timestamp: Date.now(),
        })
      })
    })

    it('should perform check-in successfully', async () => {
      const mockCheckInData = {
        employee_id: 'EMP001',
        timestamp: '2025-01-15T08:30:00Z',
        location: {
          latitude: -6.2088,
          longitude: 106.8456,
          accuracy: 10,
        },
        face_data: {
          confidence: 95,
          liveness: 85,
          embeddings: [1, 2, 3, 4, 5],
        },
      }

      const mockResponse = {
        status: {
          status: 'present',
          check_in_time: '08:30:00',
          check_out_time: null,
          working_hours: 0,
          date: '2025-01-15',
          employee_id: 'EMP001',
        },
        attendance: {
          id: 'att-123',
          employee_id: 'EMP001',
          date: '2025-01-15',
          check_in_time: '08:30:00',
          check_out_time: null,
          status: 'present',
          working_hours: 0,
          location_in: 'Office Main',
          face_confidence_in: 95,
          created_at: '2025-01-15T08:30:00Z',
          updated_at: '2025-01-15T08:30:00Z',
        },
      }

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(mockResponse),
      })

      await store.checkIn(mockCheckInData)

      expect(mockFetch).toHaveBeenCalledWith('/api/attendance/check-in', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Authorization: 'Bearer mock-auth-token',
          'X-CSRF-TOKEN': 'mock-csrf-token',
        },
        body: JSON.stringify(mockCheckInData),
      })

      expect(store.currentStatus).toEqual(mockResponse.status)
      expect(store.todayAttendance).toEqual(mockResponse.attendance)
      expect(store.processing).toBe(false)
    })

    it('should handle check-in validation errors', async () => {
      const mockCheckInData = {
        employee_id: 'EMP001',
        timestamp: '2025-01-15T08:30:00Z',
        location: {
          latitude: -6.2088,
          longitude: 106.8456,
          accuracy: 10,
        },
        face_data: {
          confidence: 95,
          liveness: 85,
          embeddings: [1, 2, 3, 4, 5],
        },
      }

      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 422,
        json: () =>
          Promise.resolve({
            message: 'Face recognition confidence too low',
            errors: {
              face_data: ['Confidence must be at least 80%'],
            },
          }),
      })

      await expect(store.checkIn(mockCheckInData)).rejects.toThrow(
        'Face recognition confidence too low'
      )
      expect(store.error).toBe('Face recognition confidence too low')
      expect(store.processing).toBe(false)
    })

    it('should handle location validation errors', async () => {
      const mockCheckInData = {
        employee_id: 'EMP001',
        timestamp: '2025-01-15T08:30:00Z',
        location: {
          latitude: -6.3, // Outside allowed range
          longitude: 106.9,
          accuracy: 10,
        },
        face_data: {
          confidence: 95,
          liveness: 85,
          embeddings: [1, 2, 3, 4, 5],
        },
      }

      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 422,
        json: () =>
          Promise.resolve({
            message: 'Check-in location is outside allowed area',
            errors: {
              location: ['You must be within the office premises to check in'],
            },
          }),
      })

      await expect(store.checkIn(mockCheckInData)).rejects.toThrow(
        'Check-in location is outside allowed area'
      )
    })
  })

  describe('Check-out Process', () => {
    it('should perform check-out successfully', async () => {
      const mockCheckOutData = {
        attendance_id: 'att-123',
        timestamp: '2025-01-15T17:00:00Z',
        location: {
          latitude: -6.2088,
          longitude: 106.8456,
          accuracy: 10,
        },
        face_data: {
          confidence: 92,
          liveness: 88,
          embeddings: [1, 2, 3, 4, 5],
        },
      }

      const mockResponse = {
        status: {
          status: 'present',
          check_in_time: '08:30:00',
          check_out_time: '17:00:00',
          working_hours: 8.5,
          date: '2025-01-15',
          employee_id: 'EMP001',
        },
        attendance: {
          id: 'att-123',
          employee_id: 'EMP001',
          date: '2025-01-15',
          check_in_time: '08:30:00',
          check_out_time: '17:00:00',
          status: 'present',
          working_hours: 8.5,
          location_in: 'Office Main',
          location_out: 'Office Main',
          face_confidence_in: 95,
          face_confidence_out: 92,
          created_at: '2025-01-15T08:30:00Z',
          updated_at: '2025-01-15T17:00:00Z',
        },
      }

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(mockResponse),
      })

      await store.checkOut(mockCheckOutData)

      expect(mockFetch).toHaveBeenCalledWith('/api/attendance/check-out', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Authorization: 'Bearer mock-auth-token',
          'X-CSRF-TOKEN': 'mock-csrf-token',
        },
        body: JSON.stringify(mockCheckOutData),
      })

      expect(store.currentStatus.check_out_time).toBe('17:00:00')
      expect(store.currentStatus.working_hours).toBe(8.5)
    })

    it('should handle early check-out warnings', async () => {
      const mockCheckOutData = {
        attendance_id: 'att-123',
        timestamp: '2025-01-15T15:00:00Z', // Early checkout
        location: {
          latitude: -6.2088,
          longitude: 106.8456,
          accuracy: 10,
        },
        face_data: {
          confidence: 92,
          liveness: 88,
          embeddings: [1, 2, 3, 4, 5],
        },
      }

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () =>
          Promise.resolve({
            status: {
              status: 'early',
              check_in_time: '08:30:00',
              check_out_time: '15:00:00',
              working_hours: 6.5,
              date: '2025-01-15',
              employee_id: 'EMP001',
            },
            attendance: {
              id: 'att-123',
              status: 'early',
              working_hours: 6.5,
              check_out_time: '15:00:00',
            },
            warning: 'Early check-out detected. Working hours: 6.5h',
          }),
      })

      await store.checkOut(mockCheckOutData)

      expect(store.currentStatus.status).toBe('early')
      expect(store.currentStatus.working_hours).toBe(6.5)
    })
  })

  describe('Location Validation', () => {
    it('should validate location successfully', async () => {
      const mockLocation = {
        coords: {
          latitude: -6.2088,
          longitude: 106.8456,
          accuracy: 10,
        },
      } as GeolocationPosition

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () =>
          Promise.resolve({
            valid: true,
            location_name: 'Office Main Building',
            distance: 15,
          }),
      })

      const isValid = await store.validateLocation(mockLocation)

      expect(mockFetch).toHaveBeenCalledWith('/api/attendance/validate-location', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Authorization: 'Bearer mock-auth-token',
          'X-CSRF-TOKEN': 'mock-csrf-token',
        },
        body: JSON.stringify({
          latitude: -6.2088,
          longitude: 106.8456,
          accuracy: 10,
        }),
      })

      expect(isValid).toBe(true)
    })

    it('should handle location outside allowed area', async () => {
      const mockLocation = {
        coords: {
          latitude: -6.3,
          longitude: 106.9,
          accuracy: 10,
        },
      } as GeolocationPosition

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () =>
          Promise.resolve({
            valid: false,
            reason: 'Outside allowed radius',
            distance: 500,
            max_distance: 100,
          }),
      })

      const isValid = await store.validateLocation(mockLocation)

      expect(isValid).toBe(false)
    })

    it('should get current location successfully', async () => {
      mockGeolocation.getCurrentPosition.mockImplementation((success) => {
        const position = {
          coords: {
            latitude: -6.2088,
            longitude: 106.8456,
            accuracy: 10,
            altitude: null,
            altitudeAccuracy: null,
            heading: null,
            speed: null,
          },
          timestamp: Date.now(),
        }
        success(position)
      })

      const location = await store.getCurrentLocation()

      expect(location.coords.latitude).toBe(-6.2088)
      expect(location.coords.longitude).toBe(106.8456)
      expect(store.currentLocation).toEqual(location)
    })

    it('should handle location permission denied', async () => {
      mockGeolocation.getCurrentPosition.mockImplementation((success, error) => {
        const positionError = {
          code: 1, // PERMISSION_DENIED
          message: 'User denied geolocation',
        }
        error(positionError)
      })

      await expect(store.getCurrentLocation()).rejects.toThrow('Location access denied by user')
    })

    it('should handle location unavailable', async () => {
      mockGeolocation.getCurrentPosition.mockImplementation((success, error) => {
        const positionError = {
          code: 2, // POSITION_UNAVAILABLE
          message: 'Location unavailable',
        }
        error(positionError)
      })

      await expect(store.getCurrentLocation()).rejects.toThrow('Location information unavailable')
    })
  })

  describe('Attendance History', () => {
    it('should fetch attendance history with filters', async () => {
      const mockHistory = [
        {
          id: 'att-123',
          employee_id: 'EMP001',
          date: '2025-01-15',
          check_in_time: '08:30:00',
          check_out_time: '17:00:00',
          status: 'present',
          working_hours: 8.5,
          created_at: '2025-01-15T08:30:00Z',
          updated_at: '2025-01-15T17:00:00Z',
        },
        {
          id: 'att-122',
          employee_id: 'EMP001',
          date: '2025-01-14',
          check_in_time: '08:45:00',
          check_out_time: '17:15:00',
          status: 'late',
          working_hours: 8.5,
          created_at: '2025-01-14T08:45:00Z',
          updated_at: '2025-01-14T17:15:00Z',
        },
      ]

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () =>
          Promise.resolve({
            data: mockHistory,
            meta: {
              total: 2,
              per_page: 10,
              current_page: 1,
            },
          }),
      })

      await store.fetchAttendanceHistory({
        start_date: '2025-01-14',
        end_date: '2025-01-15',
        limit: 10,
      })

      expect(mockFetch).toHaveBeenCalledWith(
        '/api/attendance/history?start_date=2025-01-14&end_date=2025-01-15&limit=10',
        {
          headers: {
            Authorization: 'Bearer mock-auth-token',
            'X-CSRF-TOKEN': 'mock-csrf-token',
          },
        }
      )

      expect(store.attendanceHistory).toEqual(mockHistory)
    })
  })

  describe('Monthly Statistics', () => {
    it('should fetch monthly attendance statistics', async () => {
      const mockStats = {
        present_days: 20,
        absent_days: 2,
        late_days: 3,
        total_working_hours: 160,
        overtime_hours: 15,
        average_checkin_time: '08:35:00',
        average_checkout_time: '17:05:00',
      }

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () =>
          Promise.resolve({
            stats: mockStats,
          }),
      })

      await store.fetchMonthlyStats('01', '2025')

      expect(mockFetch).toHaveBeenCalledWith('/api/attendance/stats/monthly?month=01&year=2025', {
        headers: {
          Authorization: 'Bearer mock-auth-token',
          'X-CSRF-TOKEN': 'mock-csrf-token',
        },
      })

      expect(store.monthlyStats).toEqual(mockStats)
    })
  })

  describe('Error Handling and Edge Cases', () => {
    it('should handle network failures gracefully', async () => {
      mockFetch.mockRejectedValueOnce(new Error('Network connection failed'))

      await store.fetchTodayStatus()

      expect(store.error).toBe('Failed to fetch today\'s attendance status')
      expect(store.isLoading).toBe(false)
    })

    it('should handle malformed API responses', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () =>
          Promise.resolve({
            // Missing expected fields
            invalid: 'response',
          }),
      })

      await store.fetchTodayStatus()

      expect(store.currentStatus).toBe(null)
      expect(store.todayAttendance).toBe(null)
    })

    it('should handle concurrent requests properly', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: () =>
          Promise.resolve({
            status: { status: 'present' },
            attendance: { id: 'att-123' },
          }),
      })

      // Make concurrent requests
      const promises = [
        store.fetchTodayStatus(),
        store.fetchTodayStatus(),
        store.fetchTodayStatus(),
      ]

      await Promise.all(promises)

      // Should have made requests but handled loading state properly
      expect(store.isLoading).toBe(false)
      expect(mockFetch).toHaveBeenCalledTimes(3)
    })
  })
})
