import apiClient from './client';
import type {
  Schedule,
  ScheduleFormData,
  ScheduleStatistics,
  ScheduleConflict,
  TimeSlot,
  Subject,
  AcademicClass,
  AvailableTeacher,
  MonthlySchedule,
  DayOfWeek,
  PaginatedResponse,
} from '@/types';

export interface ScheduleFilters {
  class_id?: string;
  employee_id?: string;
  subject_id?: string;
  day_of_week?: DayOfWeek;
  status?: 'active' | 'inactive' | 'cancelled';
  page?: number;
  per_page?: number;
}

export interface MonthlyScheduleFilters {
  year?: number;
  month?: number;
  status?: 'draft' | 'published' | 'archived';
  page?: number;
  per_page?: number;
}

const ENDPOINTS = {
  list: '/schedules',
  detail: (id: string) => `/schedules/${id}`,
  byClass: (classId: string) => `/schedules/class/${classId}`,
  statistics: '/schedules/statistics',
  conflicts: '/schedules/conflicts',
  lock: (id: string) => `/schedules/${id}/lock`,
  unlock: (id: string) => `/schedules/${id}/unlock`,
  timeSlots: '/schedules/time-slots',
  subjects: '/schedules/subjects',
  classes: '/schedules/classes',
  availableTeachers: '/schedules/available-teachers',
  monthly: '/schedules/monthly',
  monthlyDetail: (id: string) => `/schedules/monthly/${id}`,
  monthlyPublish: (id: string) => `/schedules/monthly/${id}/publish`,
} as const;

// Get all schedules with pagination and filters
export async function getSchedules(
  filters?: ScheduleFilters
): Promise<PaginatedResponse<Schedule>> {
  const response = await apiClient.get<PaginatedResponse<Schedule>>(ENDPOINTS.list, {
    params: filters,
  });
  return response.data;
}

// Get schedules by class
export async function getSchedulesByClass(classId: string): Promise<Schedule[]> {
  const response = await apiClient.get<{ data: Schedule[] }>(ENDPOINTS.byClass(classId));
  return response.data.data;
}

// Get single schedule by ID
export async function getSchedule(id: string): Promise<Schedule> {
  const response = await apiClient.get<{ data: Schedule }>(ENDPOINTS.detail(id));
  return response.data.data;
}

// Create new schedule
export async function createSchedule(classId: string, data: ScheduleFormData): Promise<Schedule> {
  const response = await apiClient.post<{ data: Schedule }>(ENDPOINTS.list, {
    academic_class_id: classId,
    ...data,
  });
  return response.data.data;
}

// Update schedule
export async function updateSchedule(
  id: string,
  data: Partial<ScheduleFormData>
): Promise<Schedule> {
  const response = await apiClient.put<{ data: Schedule }>(ENDPOINTS.detail(id), data);
  return response.data.data;
}

// Delete schedule
export async function deleteSchedule(id: string): Promise<void> {
  await apiClient.delete(ENDPOINTS.detail(id));
}

// Lock schedule
export async function lockSchedule(id: string, reason?: string): Promise<Schedule> {
  const response = await apiClient.post<{ data: Schedule }>(ENDPOINTS.lock(id), { reason });
  return response.data.data;
}

// Unlock schedule
export async function unlockSchedule(id: string): Promise<Schedule> {
  const response = await apiClient.post<{ data: Schedule }>(ENDPOINTS.unlock(id));
  return response.data.data;
}

// Get schedule statistics
export async function getScheduleStatistics(): Promise<ScheduleStatistics> {
  const response = await apiClient.get<{ data: ScheduleStatistics }>(ENDPOINTS.statistics);
  return response.data.data;
}

// Get schedule conflicts
export async function getScheduleConflicts(classId?: string): Promise<ScheduleConflict[]> {
  const response = await apiClient.get<{ data: ScheduleConflict[] }>(ENDPOINTS.conflicts, {
    params: { class_id: classId },
  });
  return response.data.data;
}

// Get time slots
export async function getTimeSlots(): Promise<TimeSlot[]> {
  const response = await apiClient.get<{ data: TimeSlot[] }>(ENDPOINTS.timeSlots);
  return response.data.data;
}

// Get subjects
export async function getSubjects(): Promise<Subject[]> {
  const response = await apiClient.get<{ data: Subject[] }>(ENDPOINTS.subjects);
  return response.data.data;
}

// Get classes
export async function getClasses(): Promise<AcademicClass[]> {
  const response = await apiClient.get<{ data: AcademicClass[] }>(ENDPOINTS.classes);
  return response.data.data;
}

// Get available teachers
export async function getAvailableTeachers(
  subjectId: string,
  dayOfWeek: DayOfWeek,
  timeSlotId: string
): Promise<AvailableTeacher[]> {
  const response = await apiClient.get<{ data: AvailableTeacher[] }>(ENDPOINTS.availableTeachers, {
    params: {
      subject_id: subjectId,
      day_of_week: dayOfWeek,
      time_slot_id: timeSlotId,
    },
  });
  return response.data.data;
}

// Monthly Schedules

// Get monthly schedules
export async function getMonthlySchedules(
  filters?: MonthlyScheduleFilters
): Promise<PaginatedResponse<MonthlySchedule>> {
  const response = await apiClient.get<PaginatedResponse<MonthlySchedule>>(ENDPOINTS.monthly, {
    params: filters,
  });
  return response.data;
}

// Get single monthly schedule
export async function getMonthlySchedule(id: string): Promise<MonthlySchedule> {
  const response = await apiClient.get<{ data: MonthlySchedule }>(ENDPOINTS.monthlyDetail(id));
  return response.data.data;
}

// Create monthly schedule
export async function createMonthlySchedule(data: {
  name: string;
  academic_year: string;
  semester: 1 | 2;
  month: number;
  year: number;
}): Promise<MonthlySchedule> {
  const response = await apiClient.post<{ data: MonthlySchedule }>(ENDPOINTS.monthly, data);
  return response.data.data;
}

// Publish monthly schedule
export async function publishMonthlySchedule(id: string): Promise<MonthlySchedule> {
  const response = await apiClient.post<{ data: MonthlySchedule }>(ENDPOINTS.monthlyPublish(id));
  return response.data.data;
}

// Delete monthly schedule
export async function deleteMonthlySchedule(id: string): Promise<void> {
  await apiClient.delete(ENDPOINTS.monthlyDetail(id));
}
