import { ref } from 'vue'

export function useAttendance() {
  const attendanceStatus = ref(null)
  const loading = ref(false)
  const processing = ref(false)
  const error = ref(null)

  const fetchAttendanceStatus = async () => {
    loading.value = true
    error.value = null

    try {
      const response = await fetch('/api/v1/attendance/status', {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          Authorization: `Bearer ${getAuthToken()}`,
        },
      })

      if (!response.ok) {
        throw new Error('Failed to fetch attendance status')
      }

      const data = await response.json()
      attendanceStatus.value = data.data
    } catch (err) {
      error.value = err.message
      console.error('Attendance fetch error:', err)
    } finally {
      loading.value = false
    }
  }

  const checkIn = async (locationData, faceData) => {
    processing.value = true
    error.value = null

    try {
      const response = await fetch('/api/v1/attendance/check-in', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          Authorization: `Bearer ${getAuthToken()}`,
          'X-CSRF-TOKEN': getCsrfToken(),
        },
        body: JSON.stringify({
          location: locationData,
          face_data: faceData,
        }),
      })

      if (!response.ok) {
        throw new Error('Check-in failed')
      }

      const data = await response.json()
      attendanceStatus.value = data.data
      return data
    } catch (err) {
      error.value = err.message
      console.error('Check-in error:', err)
      throw err
    } finally {
      processing.value = false
    }
  }

  const checkOut = async (locationData, faceData) => {
    processing.value = true
    error.value = null

    try {
      const response = await fetch('/api/v1/attendance/check-out', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          Authorization: `Bearer ${getAuthToken()}`,
          'X-CSRF-TOKEN': getCsrfToken(),
        },
        body: JSON.stringify({
          location: locationData,
          face_data: faceData,
        }),
      })

      if (!response.ok) {
        throw new Error('Check-out failed')
      }

      const data = await response.json()
      attendanceStatus.value = data.data
      return data
    } catch (err) {
      error.value = err.message
      console.error('Check-out error:', err)
      throw err
    } finally {
      processing.value = false
    }
  }

  // Helper functions
  const getAuthToken = () => {
    return (
      localStorage.getItem('auth_token') ||
      document.querySelector('meta[name="api-token"]')?.content
    )
  }

  const getCsrfToken = () => {
    return document.querySelector('meta[name="csrf-token"]')?.content
  }

  return {
    attendanceStatus,
    loading,
    processing,
    error,
    fetchAttendanceStatus,
    checkIn,
    checkOut,
  }
}
