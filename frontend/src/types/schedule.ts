// Schedule Types

export type DayOfWeek = 'monday' | 'tuesday' | 'wednesday' | 'thursday' | 'friday' | 'saturday';

export interface TimeSlot {
  id: string;
  name: string;
  start_time: string;
  end_time: string;
  is_break?: boolean;
  metadata?: string;
}

export interface Subject {
  id: string;
  code: string;
  name: string;
  category?: string;
  weekly_hours?: number;
  max_meetings_per_day?: number;
  requires_lab?: boolean;
  color?: string;
}

export interface AcademicClass {
  id: string;
  name: string;
  grade_level: string;
  homeroom_teacher_id?: string;
  homeroom_teacher_name?: string;
  student_count?: number;
}

export interface ScheduleEmployee {
  id: string;
  nip: string;
  name: string;
  email?: string;
  phone?: string;
  position?: string;
  department?: string;
  type?: 'full_time' | 'part_time' | 'contract';
}

export interface Schedule {
  id: string;
  academic_class_id: string;
  subject_id: string;
  employee_id: string;
  time_slot_id: string;
  day_of_week: DayOfWeek;
  room?: string;
  effective_from: string;
  effective_until?: string;
  is_active: boolean;
  is_locked: boolean;
  lock_reason?: string;
  status: 'active' | 'inactive' | 'cancelled';
  created_at: string;
  updated_at: string;
  created_by?: string;
  updated_by?: string;
  // Relations
  subject?: Subject;
  employee?: ScheduleEmployee;
  academic_class?: AcademicClass;
  time_slot?: TimeSlot;
}

export interface ScheduleSlot {
  schedule: Schedule | null;
  time_slot: TimeSlot;
  status: 'normal' | 'locked' | 'conflict' | 'empty';
  display?: {
    subject_code?: string;
    subject_name?: string;
    teacher_name?: string;
    room?: string;
    is_locked: boolean;
    color?: string;
  };
}

export interface ScheduleGridDay {
  day_name: string;
  day_of_week: DayOfWeek;
  slots: Record<string, ScheduleSlot>;
}

export type ScheduleGrid = Record<DayOfWeek, ScheduleGridDay>;

export type ConflictType =
  | 'teacher_double_booking'
  | 'class_double_booking'
  | 'room_double_booking'
  | 'subject_frequency_exceeded'
  | 'teacher_max_hours_exceeded';

export type ConflictSeverity = 'low' | 'medium' | 'high' | 'critical';

export interface ScheduleConflict {
  id: string;
  type: ConflictType;
  severity: ConflictSeverity;
  schedule_a_id: string;
  schedule_b_id?: string;
  description: string;
  suggestion?: string;
  is_resolved: boolean;
  resolved_at?: string;
  resolved_by?: string;
  resolution_notes?: string;
  created_at: string;
  // Relations
  schedule_a?: Schedule;
  schedule_b?: Schedule;
}

export interface ScheduleChangeLog {
  id: string;
  schedule_id: string;
  action: 'created' | 'updated' | 'deleted' | 'locked' | 'unlocked';
  changes: Record<string, { old: unknown; new: unknown }>;
  reason?: string;
  user_id: string;
  user_name?: string;
  created_at: string;
}

export interface MonthlySchedule {
  id: string;
  name: string;
  academic_year: string;
  semester: 1 | 2;
  month: number;
  year: number;
  status: 'draft' | 'published' | 'archived';
  published_at?: string;
  created_at: string;
  updated_at: string;
}

// Form types
export interface ScheduleFormData {
  subject_id: string;
  employee_id: string;
  time_slot_id: string;
  day_of_week: DayOfWeek;
  room?: string;
  effective_from: string;
  effective_until?: string;
  reason?: string;
}

export interface AvailableTeacher {
  id: string;
  name: string;
  nip: string;
  is_available: boolean;
  conflict_reason?: string;
  current_load?: number;
  max_load?: number;
}

// Calendar view types
export interface CalendarCell {
  id: string;
  time_slot_id: string;
  day_of_week: DayOfWeek;
  schedule?: Schedule;
  is_locked: boolean;
  has_conflict: boolean;
}

export interface CalendarData {
  cells: Record<string, CalendarCell>;
  locked_cells: string[];
  last_saved?: string;
}

// Statistics
export interface ScheduleStatistics {
  total_schedules: number;
  active_schedules: number;
  locked_schedules: number;
  conflicts_count: number;
  classes_with_schedules: number;
  teachers_assigned: number;
  average_weekly_hours: number;
}

// API Response types
export interface ScheduleGridResponse {
  grid: ScheduleGrid;
  time_slots: TimeSlot[];
  conflicts: ScheduleConflict[];
}

export interface AvailableTeachersResponse {
  teachers: AvailableTeacher[];
  subject: Subject;
}
