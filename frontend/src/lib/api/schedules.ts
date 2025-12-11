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

// ====================================
// Monthly Attendance Schedules (New V1 API)
// ====================================

const V1_ENDPOINTS = {
  monthlySchedules: '/monthly-schedules',
  monthlyScheduleDetail: (id: string) => `/monthly-schedules/${id}`,
  monthlyScheduleAssign: (id: string) => `/monthly-schedules/${id}/assign`,
  monthlyScheduleUnassign: (id: string) => `/monthly-schedules/${id}/unassign`,
  monthlyScheduleSync: (id: string) => `/monthly-schedules/${id}/sync`,
  monthlyScheduleEmployees: (id: string) => `/monthly-schedules/${id}/employees`,
  generateWorkingDays: '/monthly-schedules/generate-working-days',
} as const;

export interface MonthlyAttendanceScheduleFormData {
  name: string;
  month: number; // 1-12
  year: number;
  default_start_time: string; // HH:mm format
  default_end_time: string; // HH:mm format
  checkin_start_time: string; // HH:mm format
  checkin_end_time: string; // HH:mm format
  checkout_start_time: string; // HH:mm format
  checkout_end_time: string; // HH:mm format
  working_days: string[]; // Array of ISO date strings ["2025-02-01", "2025-02-02"]
  is_active?: boolean;
  description?: string;
  metadata?: Record<string, any>;
}

export interface MonthlyAttendanceSchedule {
  id: string;
  name: string;
  month: number;
  year: number;
  start_date: string;
  end_date: string;
  default_start_time: string;
  default_end_time: string;
  checkin_start_time: string;
  checkin_end_time: string;
  checkout_start_time: string;
  checkout_end_time: string;
  working_days: string[];
  total_working_days: number;
  is_active: boolean;
  description?: string;
  metadata?: Record<string, any>;
  created_by?: string;
  updated_by?: string;
  creator?: {
    id: string;
    name: string;
  };
  assigned_employees_count?: number;
  holiday_conflicts?: any[];
  created_at: string;
  updated_at: string;
}

export interface GenerateWorkingDaysParams {
  month: number;
  year: number;
  working_day_pattern: string[]; // ["monday", "tuesday", "wednesday", "thursday", "friday"]
}

export interface GenerateWorkingDaysResponse {
  working_days: string[];
  total_working_days: number;
  total_holidays: number;
  holidays: string[];
}

export interface AssignScheduleParams {
  employee_ids: string[];
}

export interface AssignScheduleResponse {
  assigned_count: number;
  replaced_count: number;
  total_employees: number;
  errors: string[];
}

// Get monthly attendance schedules with filters
export async function getMonthlyAttendanceSchedules(filters?: {
  month?: number;
  year?: number;
  location_id?: string;
  is_active?: boolean;
  per_page?: number;
}): Promise<PaginatedResponse<MonthlyAttendanceSchedule>> {
  const response = await apiClient.get<PaginatedResponse<MonthlyAttendanceSchedule>>(
    V1_ENDPOINTS.monthlySchedules,
    { params: filters }
  );
  return response.data;
}

// Get single monthly attendance schedule
export async function getMonthlyAttendanceSchedule(
  id: string
): Promise<MonthlyAttendanceSchedule> {
  const response = await apiClient.get<{ success: boolean; data: MonthlyAttendanceSchedule }>(
    V1_ENDPOINTS.monthlyScheduleDetail(id)
  );
  return response.data.data;
}

// Create monthly attendance schedule
export async function createMonthlyAttendanceSchedule(
  data: MonthlyAttendanceScheduleFormData
): Promise<MonthlyAttendanceSchedule> {
  // Calculate start_date and end_date from month and year
  const startDate = new Date(data.year, data.month - 1, 1);
  const endDate = new Date(data.year, data.month, 0); // Last day of month
  
  const formatDate = (d: Date) => d.toISOString().split('T')[0];
  
  const payload = {
    ...data,
    start_date: formatDate(startDate),
    end_date: formatDate(endDate),
  };
  
  const response = await apiClient.post<{ success: boolean; data: MonthlyAttendanceSchedule }>(
    V1_ENDPOINTS.monthlySchedules,
    payload
  );
  return response.data.data;
}

// Update monthly attendance schedule
export async function updateMonthlyAttendanceSchedule(
  id: string,
  data: Partial<MonthlyAttendanceScheduleFormData>
): Promise<MonthlyAttendanceSchedule> {
  const response = await apiClient.put<{ success: boolean; data: MonthlyAttendanceSchedule }>(
    V1_ENDPOINTS.monthlyScheduleDetail(id),
    data
  );
  return response.data.data;
}

// Delete monthly attendance schedule
export async function deleteMonthlyAttendanceSchedule(id: string): Promise<void> {
  await apiClient.delete(V1_ENDPOINTS.monthlyScheduleDetail(id));
}

// Assign employees to schedule (with auto-replace)
export async function assignEmployeesToSchedule(
  id: string,
  params: AssignScheduleParams
): Promise<AssignScheduleResponse> {
  const response = await apiClient.post<{ success: boolean; data: AssignScheduleResponse }>(
    V1_ENDPOINTS.monthlyScheduleAssign(id),
    params
  );
  return response.data.data;
}

// Unassign employee from schedule
export async function unassignEmployeeFromSchedule(
  id: string,
  employeeId: string
): Promise<void> {
  await apiClient.post(V1_ENDPOINTS.monthlyScheduleUnassign(id), {
    employee_id: employeeId,
  });
}

// Sync schedule employees (assign selected, unassign unselected)
export interface SyncScheduleParams {
  employee_ids: string[];
}

export interface SyncScheduleResponse {
  assigned_count: number;
  unassigned_count: number;
  total_employees: number;
}

export async function syncScheduleEmployees(
  id: string,
  params: SyncScheduleParams
): Promise<SyncScheduleResponse> {
  const response = await apiClient.post<{ success: boolean; data: SyncScheduleResponse }>(
    V1_ENDPOINTS.monthlyScheduleSync(id),
    params
  );
  return response.data.data;
}

// Get employees assigned to schedule
export async function getScheduleEmployees(id: string): Promise<any[]> {
  const response = await apiClient.get<{ success: boolean; data: any[] }>(
    V1_ENDPOINTS.monthlyScheduleEmployees(id)
  );
  return response.data.data;
}

// Generate working days helper
export async function generateWorkingDays(
  params: GenerateWorkingDaysParams
): Promise<GenerateWorkingDaysResponse> {
  const response = await apiClient.post<{ success: boolean; data: GenerateWorkingDaysResponse }>(
    V1_ENDPOINTS.generateWorkingDays,
    params
  );
  return response.data.data;
}

// Get employee's own schedule (for employee role)
export async function getMySchedule(params?: {
  month?: number;
  year?: number;
}): Promise<{
  schedule: MonthlyAttendanceSchedule | null;
  assigned_at?: string;
  employee?: {
    id: string;
    full_name: string;
    position: string;
  };
}> {
  const response = await apiClient.get<{
    success: boolean;
    data: {
      schedule: MonthlyAttendanceSchedule | null;
      assigned_at?: string;
      employee?: {
        id: string;
        full_name: string;
        position: string;
      };
    };
  }>(`${V1_ENDPOINTS.monthlySchedules}/my-schedule`, { params });
  return response.data.data;
}

// ====================================
// Teaching Schedule API (for Excel Import Integration)
// ====================================

export interface TeacherFromExcel {
  code: string;
  name: string;
  subject?: string;
}

export interface ScheduleFromExcel {
  day: string;
  period: number;
  className: string;
  teacherCode: string;
  subject?: string;
}

export interface BulkImportRequest {
  teachers: TeacherFromExcel[];
  schedules: ScheduleFromExcel[];
  effective_from: string; // YYYY-MM-DD
  effective_until?: string; // YYYY-MM-DD
  semester?: 1 | 2;
  academic_year?: string; // e.g., "2024/2025"
}

export interface TeacherMatchResult {
  code: string;
  excel_name: string;
  subject?: string;
  matched: boolean;
  employee_id?: string;
  employee_name?: string;
  employee_nip?: string;
}

export interface BulkImportResult {
  matched_teachers: {
    code: string;
    excel_name: string;
    employee_id: string;
    employee_name: string;
  }[];
  unmatched_teachers: {
    code: string;
    name: string;
    subject?: string;
  }[];
  created_schedules: number;
  skipped_schedules: number;
  created_subjects: string[];
  errors: {
    schedule: ScheduleFromExcel;
    error: string;
  }[];
}

const TEACHING_ENDPOINTS = {
  list: '/schedules/teaching',
  bulkImport: '/schedules/teaching/bulk-import',
  matchTeachers: '/schedules/teaching/match-teachers',
  clear: '/schedules/teaching/clear',
} as const;

// Match teachers from Excel to existing employees
export async function matchTeachersFromExcel(
  teachers: TeacherFromExcel[]
): Promise<TeacherMatchResult[]> {
  const response = await apiClient.post<{ data: TeacherMatchResult[] }>(
    TEACHING_ENDPOINTS.matchTeachers,
    { teachers }
  );
  return response.data.data;
}

// Bulk import teaching schedules from Excel data
export async function bulkImportTeachingSchedules(
  data: BulkImportRequest
): Promise<BulkImportResult> {
  const response = await apiClient.post<{ data: BulkImportResult }>(
    TEACHING_ENDPOINTS.bulkImport,
    data
  );
  return response.data.data;
}

// Get teaching schedules
export async function getTeachingSchedules(params?: {
  teacher_id?: string;
  day_of_week?: string;
  effective_from?: string;
  effective_until?: string;
  active_only?: boolean;
  per_page?: number;
}): Promise<PaginatedResponse<{
  id: string;
  teacher_id: string;
  teacher?: { id: string; employee_id: string; full_name: string };
  subject_id?: string;
  subject?: { id: string; name: string };
  day_of_week: string;
  teaching_start_time: string;
  teaching_end_time: string;
  class_name?: string;
  effective_from: string;
  effective_until?: string;
  is_active: boolean;
}>> {
  const response = await apiClient.get(TEACHING_ENDPOINTS.list, { params });
  return response.data;
}

// Clear teaching schedules for a period
export async function clearTeachingSchedules(params: {
  effective_from: string;
  effective_until?: string;
  teacher_id?: string;
}): Promise<{ deleted_count: number }> {
  const response = await apiClient.delete<{ data: { deleted_count: number } }>(
    TEACHING_ENDPOINTS.clear,
    { data: params }
  );
  return response.data.data;
}

