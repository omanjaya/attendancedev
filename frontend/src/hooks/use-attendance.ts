import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  getAttendance,
  getAttendanceRecord,
  getTodayAttendance,
  checkIn,
  checkOut,
  getAttendanceStatistics,
  getAttendanceTrends,
} from '@/lib/api/attendance';
import type { AttendanceFilters, CheckRequest } from '@/types';
import { useNotificationStore } from '@/stores';

// Query keys
export const attendanceKeys = {
  all: ['attendance'] as const,
  lists: () => [...attendanceKeys.all, 'list'] as const,
  list: (filters: AttendanceFilters) => [...attendanceKeys.lists(), filters] as const,
  details: () => [...attendanceKeys.all, 'detail'] as const,
  detail: (id: number) => [...attendanceKeys.details(), id] as const,
  today: () => [...attendanceKeys.all, 'today'] as const,
  statistics: (date?: string) => [...attendanceKeys.all, 'statistics', date] as const,
  trends: (start: string, end: string) => [...attendanceKeys.all, 'trends', start, end] as const,
};

// Get attendance list
export function useAttendance(filters?: AttendanceFilters) {
  return useQuery({
    queryKey: attendanceKeys.list(filters || {}),
    queryFn: () => getAttendance(filters),
  });
}

// Get single attendance record
export function useAttendanceRecord(id: number) {
  return useQuery({
    queryKey: attendanceKeys.detail(id),
    queryFn: () => getAttendanceRecord(id),
    enabled: !!id,
  });
}

// Get today's attendance
export function useTodayAttendance() {
  return useQuery({
    queryKey: attendanceKeys.today(),
    queryFn: getTodayAttendance,
    refetchInterval: 60000, // Refetch every minute
  });
}

// Get attendance statistics
export function useAttendanceStatistics(date?: string) {
  return useQuery({
    queryKey: attendanceKeys.statistics(date),
    queryFn: () => getAttendanceStatistics(date),
  });
}

// Get attendance trends
export function useAttendanceTrends(startDate: string, endDate: string) {
  return useQuery({
    queryKey: attendanceKeys.trends(startDate, endDate),
    queryFn: () => getAttendanceTrends(startDate, endDate),
    enabled: !!startDate && !!endDate,
  });
}

// Check in mutation
export function useCheckIn() {
  const queryClient = useQueryClient();
  const { success, error } = useNotificationStore();

  return useMutation({
    mutationFn: (data: CheckRequest) => checkIn(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: attendanceKeys.today() });
      queryClient.invalidateQueries({ queryKey: attendanceKeys.lists() });
      queryClient.invalidateQueries({ queryKey: attendanceKeys.statistics() });
      success('Check-in Berhasil', 'Selamat bekerja!');
    },
    onError: (err: Error) => {
      error('Check-in Gagal', err.message || 'Terjadi kesalahan saat check-in');
    },
  });
}

// Check out mutation
export function useCheckOut() {
  const queryClient = useQueryClient();
  const { success, error } = useNotificationStore();

  return useMutation({
    mutationFn: (data: CheckRequest) => checkOut(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: attendanceKeys.today() });
      queryClient.invalidateQueries({ queryKey: attendanceKeys.lists() });
      queryClient.invalidateQueries({ queryKey: attendanceKeys.statistics() });
      success('Check-out Berhasil', 'Hati-hati di jalan!');
    },
    onError: (err: Error) => {
      error('Check-out Gagal', err.message || 'Terjadi kesalahan saat check-out');
    },
  });
}
