// Attendance Types
import type { Ref } from 'vue'

export interface AttendanceStatus {
  id?: string
  status: 'present' | 'absent' | 'late' | 'early' | 'pending'
  check_in_time: string | null
  check_out_time: string | null
  working_hours: number
  date: string
  employee_id: string
  location?: string
  notes?: string
}

export interface AttendanceRecord {
  id: string
  employee_id: string
  date: string
  check_in_time: string | null
  check_out_time: string | null
  status: AttendanceStatus['status']
  working_hours: number
  overtime_hours?: number
  break_duration?: number
  location_in?: string
  location_out?: string
  face_confidence_in?: number
  face_confidence_out?: number
  notes?: string
  created_at: string
  updated_at: string
}

export interface CheckInData {
  employee_id: string
  timestamp: string
  location: {
    latitude: number
    longitude: number
    accuracy: number
  }
  face_data: {
    confidence: number
    liveness: number
    embeddings: number[]
  }
  device_info?: {
    user_agent: string
    ip_address: string
  }
}

export interface CheckOutData extends Omit<CheckInData, 'employee_id'> {
  attendance_id: string
}

export interface AttendanceComposable {
  attendanceStatus: Ref<AttendanceStatus | null>
  loading: Ref<boolean>
  processing: Ref<boolean>
  error: Ref<string | null>
  fetchAttendanceStatus: () => Promise<void>
  checkIn: (data: CheckInData) => Promise<void>
  checkOut: (data: CheckOutData) => Promise<void>
}
