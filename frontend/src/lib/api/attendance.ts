import apiClient from './client';
import type {
  Attendance,
  AttendanceFilters,
  AttendanceStatistics,
  AttendanceTrend,
  TodayAttendance,
  CheckRequest,
  PaginatedResponse,
} from '@/types';

const ENDPOINTS = {
  list: '/attendance',
  detail: (id: number) => `/attendance/${id}`,
  checkIn: '/attendance-face/check-in',
  checkOut: '/attendance-face/check-out',
  today: '/attendance/today',
  validateTime: '/attendance/validate-time',
  statistics: '/attendance/statistics',
  trends: '/attendance/trends',
  verifyLocation: '/locations/verify',
  adminStats: '/attendance/admin/stats',
  adminRecords: '/attendance/admin/records',
  approve: (id: number) => `/attendance/${id}/approve`,
  reject: (id: number) => `/attendance/${id}/reject`,
  manualEntry: '/attendance/manual',
} as const;

// Get attendance records with pagination and filters
export async function getAttendance(
  filters?: AttendanceFilters
): Promise<PaginatedResponse<Attendance>> {
  const response = await apiClient.get<PaginatedResponse<Attendance>>(ENDPOINTS.list, {
    params: filters,
  });
  return response.data;
}

// Get single attendance record
export async function getAttendanceRecord(id: number): Promise<Attendance> {
  const response = await apiClient.get<{ data: Attendance }>(ENDPOINTS.detail(id));
  return response.data.data;
}

// Get today's attendance for current user
export async function getTodayAttendance(): Promise<TodayAttendance> {
  const response = await apiClient.get<{ data: TodayAttendance }>(ENDPOINTS.today);
  return response.data.data;
}

// Validate attendance time before proceeding to verification
export interface ValidateTimeResponse {
  allowed: boolean;
  message: string;
  server_time: string;
  boundary?: {
    start_time?: string;
    end_time?: string;
    type: 'too_early' | 'too_late';
  };
}

export async function validateAttendanceTime(
  type: 'check_in' | 'check_out' | 'check-in' | 'check-out'
): Promise<ValidateTimeResponse> {
  const response = await apiClient.post<{ data: ValidateTimeResponse }>(
    ENDPOINTS.validateTime,
    { type }
  );
  return response.data.data;
}

// Check in
export async function checkIn(data: CheckRequest): Promise<Attendance> {
  const response = await apiClient.post<{ data: { attendance: Attendance } }>(ENDPOINTS.checkIn, data);
  return response.data.data.attendance;
}

// Check out
export async function checkOut(data: CheckRequest): Promise<Attendance> {
  const response = await apiClient.post<{ data: { attendance: Attendance } }>(ENDPOINTS.checkOut, data);
  return response.data.data.attendance;
}

// Get attendance statistics
export async function getAttendanceStatistics(date?: string): Promise<AttendanceStatistics> {
  const response = await apiClient.get<{ statistics: AttendanceStatistics }>(ENDPOINTS.statistics, {
    params: { date },
  });
  return response.data.statistics;
}

// Get attendance trends
export async function getAttendanceTrends(
  startDate: string,
  endDate: string
): Promise<AttendanceTrend[]> {
  const response = await apiClient.get<{ data: AttendanceTrend[] }>(ENDPOINTS.trends, {
    params: { start_date: startDate, end_date: endDate },
  });
  return response.data.data;
}

// Update attendance record (admin only)
export async function updateAttendance(
  id: number,
  data: Partial<Attendance>
): Promise<Attendance> {
  const response = await apiClient.put<{ data: Attendance }>(ENDPOINTS.detail(id), data);
  return response.data.data;
}

// Delete attendance record (admin only)
export async function deleteAttendance(id: number): Promise<void> {
  await apiClient.delete(ENDPOINTS.detail(id));
}

// Location verification types
export interface LocationVerificationRequest {
  latitude: number;
  longitude: number;
  location_id?: number;
}

export interface LocationVerificationResponse {
  verified: boolean;
  message: string;
  location: {
    id: number;
    name: string;
    address: string;
    latitude: number;
    longitude: number;
    radius_meters: number;
  } | null;
  distance: number;
}

// Verify location for attendance
export async function verifyLocation(
  data: LocationVerificationRequest
): Promise<LocationVerificationResponse> {
  const response = await apiClient.post<LocationVerificationResponse>(
    ENDPOINTS.verifyLocation,
    data
  );
  return response.data;
}

// Admin attendance statistics
export interface AdminAttendanceStats {
  presentToday: number;
  lateToday: number;
  absentToday: number;
  onLeaveToday: number;
  attendanceRate: number;
}

export async function getAdminAttendanceStats(date: string): Promise<AdminAttendanceStats> {
  const response = await apiClient.get<{ data: AdminAttendanceStats }>(ENDPOINTS.adminStats, {
    params: { date },
  });
  return response.data.data;
}

// Admin attendance records
export interface AdminAttendanceRecord {
  id: number;
  employee: {
    id: number;
    name: string;
    employeeId: string;
    type: string;
  };
  date: string;
  checkIn?: string;
  checkOut?: string;
  status: string;
  notes?: string;
}

export async function getAdminAttendanceRecords(params: {
  date: string;
  search?: string;
  status?: string;
}): Promise<AdminAttendanceRecord[]> {
  const response = await apiClient.get<{ data: AdminAttendanceRecord[] }>(ENDPOINTS.adminRecords, {
    params,
  });
  return response.data.data;
}

// Approve attendance
export async function approveAttendance(id: number): Promise<AdminAttendanceRecord> {
  const response = await apiClient.post<{ data: AdminAttendanceRecord }>(ENDPOINTS.approve(id));
  return response.data.data;
}

// Reject attendance
export interface RejectAttendanceRequest {
  reason: string;
}

export async function rejectAttendance(id: number, reason: string): Promise<AdminAttendanceRecord> {
  const response = await apiClient.post<{ data: AdminAttendanceRecord }>(
    ENDPOINTS.reject(id),
    { reason }
  );
  return response.data.data;
}

// Manual attendance entry
export interface ManualAttendanceRequest {
  employee_id: number;
  date: string;
  check_in: string;
  check_out?: string;
  notes?: string;
}

export async function createManualAttendance(data: ManualAttendanceRequest): Promise<AdminAttendanceRecord> {
  const response = await apiClient.post<{ data: AdminAttendanceRecord }>(ENDPOINTS.manualEntry, data);
  return response.data.data;
}
