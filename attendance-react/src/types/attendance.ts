// Attendance status
export type AttendanceStatus = 'present' | 'late' | 'absent' | 'leave' | 'holiday';

// Check type
export type CheckType = 'check_in' | 'check_out';

// Attendance record
export interface Attendance {
  id: number;
  employee_id: number;
  employee_name: string;
  date: string;
  check_in?: string;
  check_out?: string;
  status: AttendanceStatus;
  late_minutes?: number;
  early_leave_minutes?: number;
  overtime_minutes?: number;
  work_hours?: number;
  notes?: string;
  location?: {
    latitude: number;
    longitude: number;
    address?: string;
  };
  face_verified: boolean;
  created_at: string;
  updated_at: string;
}

// Today's attendance for dashboard
export interface TodayAttendance {
  id?: number;
  check_in?: string;
  check_out?: string;
  status: AttendanceStatus;
  can_check_in: boolean;
  can_check_out: boolean;
}

// Attendance filters
export interface AttendanceFilters {
  employee_id?: number;
  date_from?: string;
  date_to?: string;
  status?: AttendanceStatus;
  department?: string;
  page?: number;
  per_page?: number;
}

// Check-in/out request
export interface CheckRequest {
  type: CheckType;
  latitude?: number;
  longitude?: number;
  face_image?: string;
  notes?: string;
}

// Attendance statistics
export interface AttendanceStatistics {
  total_employees: number;
  present: number;
  late: number;
  absent: number;
  on_leave: number;
  attendance_rate: number;
}

// Attendance trends
export interface AttendanceTrend {
  date: string;
  present: number;
  late: number;
  absent: number;
  on_leave: number;
}
