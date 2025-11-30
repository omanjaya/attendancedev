import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  getSchedules,
  getSchedule,
  getSchedulesByClass,
  createSchedule,
  updateSchedule,
  deleteSchedule,
  lockSchedule,
  unlockSchedule,
  getScheduleStatistics,
  getScheduleConflicts,
  getTimeSlots,
  getSubjects,
  getClasses,
  getAvailableTeachers,
  getMonthlySchedules,
  getMonthlySchedule,
  createMonthlySchedule,
  publishMonthlySchedule,
  deleteMonthlySchedule,
  type ScheduleFilters,
  type MonthlyScheduleFilters,
} from '@/lib/api/schedules';
import type { ScheduleFormData, DayOfWeek } from '@/types/schedule';
import { useNotificationStore } from '@/stores';

// Query keys
export const scheduleKeys = {
  all: ['schedules'] as const,
  lists: () => [...scheduleKeys.all, 'list'] as const,
  list: (filters: ScheduleFilters) => [...scheduleKeys.lists(), filters] as const,
  details: () => [...scheduleKeys.all, 'detail'] as const,
  detail: (id: string) => [...scheduleKeys.details(), id] as const,
  byClass: (classId: string) => [...scheduleKeys.all, 'byClass', classId] as const,
  statistics: () => [...scheduleKeys.all, 'statistics'] as const,
  conflicts: (classId?: string) => [...scheduleKeys.all, 'conflicts', classId] as const,
  timeSlots: () => [...scheduleKeys.all, 'timeSlots'] as const,
  subjects: () => [...scheduleKeys.all, 'subjects'] as const,
  classes: () => [...scheduleKeys.all, 'classes'] as const,
  availableTeachers: (subjectId: string, dayOfWeek: DayOfWeek, timeSlotId: string) =>
    [...scheduleKeys.all, 'availableTeachers', subjectId, dayOfWeek, timeSlotId] as const,
  monthly: () => [...scheduleKeys.all, 'monthly'] as const,
  monthlyList: (filters: MonthlyScheduleFilters) => [...scheduleKeys.monthly(), 'list', filters] as const,
  monthlyDetail: (id: string) => [...scheduleKeys.monthly(), 'detail', id] as const,
};

// Get schedules list with pagination
export function useSchedules(filters?: ScheduleFilters) {
  return useQuery({
    queryKey: scheduleKeys.list(filters || {}),
    queryFn: () => getSchedules(filters),
  });
}

// Get single schedule by ID
export function useSchedule(id: string) {
  return useQuery({
    queryKey: scheduleKeys.detail(id),
    queryFn: () => getSchedule(id),
    enabled: !!id,
  });
}

// Get schedules by class (for schedule grid/builder)
export function useSchedulesByClass(classId: string) {
  return useQuery({
    queryKey: scheduleKeys.byClass(classId),
    queryFn: () => getSchedulesByClass(classId),
    enabled: !!classId,
  });
}

// Get schedule statistics
export function useScheduleStatistics() {
  return useQuery({
    queryKey: scheduleKeys.statistics(),
    queryFn: getScheduleStatistics,
  });
}

// Get schedule conflicts
export function useScheduleConflicts(classId?: string) {
  return useQuery({
    queryKey: scheduleKeys.conflicts(classId),
    queryFn: () => getScheduleConflicts(classId),
  });
}

// Get time slots
export function useTimeSlots() {
  return useQuery({
    queryKey: scheduleKeys.timeSlots(),
    queryFn: getTimeSlots,
    staleTime: 1000 * 60 * 30, // Cache for 30 minutes (rarely changes)
  });
}

// Get subjects
export function useSubjects() {
  return useQuery({
    queryKey: scheduleKeys.subjects(),
    queryFn: getSubjects,
    staleTime: 1000 * 60 * 30, // Cache for 30 minutes
  });
}

// Get classes
export function useAcademicClasses() {
  return useQuery({
    queryKey: scheduleKeys.classes(),
    queryFn: getClasses,
    staleTime: 1000 * 60 * 30, // Cache for 30 minutes
  });
}

// Get available teachers for a specific slot
export function useAvailableTeachers(
  subjectId: string,
  dayOfWeek: DayOfWeek,
  timeSlotId: string
) {
  return useQuery({
    queryKey: scheduleKeys.availableTeachers(subjectId, dayOfWeek, timeSlotId),
    queryFn: () => getAvailableTeachers(subjectId, dayOfWeek, timeSlotId),
    enabled: !!subjectId && !!dayOfWeek && !!timeSlotId,
  });
}

// Create schedule mutation
export function useCreateSchedule() {
  const queryClient = useQueryClient();
  const { success, error } = useNotificationStore();

  return useMutation({
    mutationFn: ({ classId, data }: { classId: string; data: ScheduleFormData }) =>
      createSchedule(classId, data),
    onSuccess: (_, { classId }) => {
      queryClient.invalidateQueries({ queryKey: scheduleKeys.lists() });
      queryClient.invalidateQueries({ queryKey: scheduleKeys.byClass(classId) });
      queryClient.invalidateQueries({ queryKey: scheduleKeys.statistics() });
      queryClient.invalidateQueries({ queryKey: scheduleKeys.conflicts() });
      success('Berhasil', 'Jadwal berhasil dibuat');
    },
    onError: (err: Error) => {
      error('Gagal', err.message || 'Gagal membuat jadwal');
    },
  });
}

// Update schedule mutation
export function useUpdateSchedule() {
  const queryClient = useQueryClient();
  const { success, error } = useNotificationStore();

  return useMutation({
    mutationFn: ({ id, data }: { id: string; data: Partial<ScheduleFormData> }) =>
      updateSchedule(id, data),
    onSuccess: (result) => {
      queryClient.invalidateQueries({ queryKey: scheduleKeys.lists() });
      queryClient.invalidateQueries({ queryKey: scheduleKeys.detail(result.id) });
      if (result.academic_class_id) {
        queryClient.invalidateQueries({ queryKey: scheduleKeys.byClass(result.academic_class_id) });
      }
      queryClient.invalidateQueries({ queryKey: scheduleKeys.conflicts() });
      success('Berhasil', 'Jadwal berhasil diperbarui');
    },
    onError: (err: Error) => {
      error('Gagal', err.message || 'Gagal memperbarui jadwal');
    },
  });
}

// Delete schedule mutation
export function useDeleteSchedule() {
  const queryClient = useQueryClient();
  const { success, error } = useNotificationStore();

  return useMutation({
    mutationFn: (id: string) => deleteSchedule(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: scheduleKeys.lists() });
      queryClient.invalidateQueries({ queryKey: scheduleKeys.statistics() });
      queryClient.invalidateQueries({ queryKey: scheduleKeys.conflicts() });
      success('Berhasil', 'Jadwal berhasil dihapus');
    },
    onError: (err: Error) => {
      error('Gagal', err.message || 'Gagal menghapus jadwal');
    },
  });
}

// Lock schedule mutation
export function useLockSchedule() {
  const queryClient = useQueryClient();
  const { success, error } = useNotificationStore();

  return useMutation({
    mutationFn: ({ id, reason }: { id: string; reason?: string }) => lockSchedule(id, reason),
    onSuccess: (result) => {
      queryClient.invalidateQueries({ queryKey: scheduleKeys.detail(result.id) });
      if (result.academic_class_id) {
        queryClient.invalidateQueries({ queryKey: scheduleKeys.byClass(result.academic_class_id) });
      }
      queryClient.invalidateQueries({ queryKey: scheduleKeys.statistics() });
      success('Berhasil', 'Jadwal berhasil dikunci');
    },
    onError: (err: Error) => {
      error('Gagal', err.message || 'Gagal mengunci jadwal');
    },
  });
}

// Unlock schedule mutation
export function useUnlockSchedule() {
  const queryClient = useQueryClient();
  const { success, error } = useNotificationStore();

  return useMutation({
    mutationFn: (id: string) => unlockSchedule(id),
    onSuccess: (result) => {
      queryClient.invalidateQueries({ queryKey: scheduleKeys.detail(result.id) });
      if (result.academic_class_id) {
        queryClient.invalidateQueries({ queryKey: scheduleKeys.byClass(result.academic_class_id) });
      }
      queryClient.invalidateQueries({ queryKey: scheduleKeys.statistics() });
      success('Berhasil', 'Jadwal berhasil dibuka kuncinya');
    },
    onError: (err: Error) => {
      error('Gagal', err.message || 'Gagal membuka kunci jadwal');
    },
  });
}

// Monthly Schedule hooks

// Get monthly schedules list
export function useMonthlySchedules(filters?: MonthlyScheduleFilters) {
  return useQuery({
    queryKey: scheduleKeys.monthlyList(filters || {}),
    queryFn: () => getMonthlySchedules(filters),
  });
}

// Get single monthly schedule
export function useMonthlySchedule(id: string) {
  return useQuery({
    queryKey: scheduleKeys.monthlyDetail(id),
    queryFn: () => getMonthlySchedule(id),
    enabled: !!id,
  });
}

// Create monthly schedule mutation
export function useCreateMonthlySchedule() {
  const queryClient = useQueryClient();
  const { success, error } = useNotificationStore();

  return useMutation({
    mutationFn: (data: {
      name: string;
      academic_year: string;
      semester: 1 | 2;
      month: number;
      year: number;
    }) => createMonthlySchedule(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: scheduleKeys.monthly() });
      success('Berhasil', 'Jadwal bulanan berhasil dibuat');
    },
    onError: (err: Error) => {
      error('Gagal', err.message || 'Gagal membuat jadwal bulanan');
    },
  });
}

// Publish monthly schedule mutation
export function usePublishMonthlySchedule() {
  const queryClient = useQueryClient();
  const { success, error } = useNotificationStore();

  return useMutation({
    mutationFn: (id: string) => publishMonthlySchedule(id),
    onSuccess: (_, id) => {
      queryClient.invalidateQueries({ queryKey: scheduleKeys.monthlyDetail(id) });
      queryClient.invalidateQueries({ queryKey: scheduleKeys.monthly() });
      success('Berhasil', 'Jadwal bulanan berhasil dipublikasikan');
    },
    onError: (err: Error) => {
      error('Gagal', err.message || 'Gagal mempublikasikan jadwal bulanan');
    },
  });
}

// Delete monthly schedule mutation
export function useDeleteMonthlySchedule() {
  const queryClient = useQueryClient();
  const { success, error } = useNotificationStore();

  return useMutation({
    mutationFn: (id: string) => deleteMonthlySchedule(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: scheduleKeys.monthly() });
      success('Berhasil', 'Jadwal bulanan berhasil dihapus');
    },
    onError: (err: Error) => {
      error('Gagal', err.message || 'Gagal menghapus jadwal bulanan');
    },
  });
}
