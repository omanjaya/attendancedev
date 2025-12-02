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
  statistics: '/attendance/statistics',
  trends: '/attendance/trends',
  verifyLocation: '/locations/verify',
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
