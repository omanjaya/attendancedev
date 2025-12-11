/**
 * Grade Schedule Builder Types
 *
 * Struktur data untuk jadwal per grade dengan tampilan Excel-like
 */

// Cell dalam grid jadwal
export interface GradeScheduleCell {
  teacherCode: string | null;  // "6", "28", etc.
  subject: string | null;      // "IPA", "Bahasa Indonesia"
  isLocked: boolean;
  teachingScheduleId?: string; // Backend ID jika sudah disimpan
}

// Teacher dengan info JP (Jam Pelajaran)
export interface TeacherWithJP {
  code: string;           // "6", "28"
  name: string;           // "Ahmad Fauzi, S.Pd"
  subject: string;        // "IPA"
  maxJP: number | null;   // null = tidak ada batas
  color: string;          // Generated color
  employeeId?: string;    // Link to backend Employee
}

// Row identifier = Day + Period
export interface ScheduleRow {
  day: string;        // 'Senin', 'Selasa', etc.
  dayEn: string;      // 'monday', 'tuesday', etc.
  period: number;     // 1-10
  timeStart: string;  // '07:00'
  timeEnd: string;    // '07:45'
  isBreak: boolean;
}

// Grid per grade
export type GradeGrid = {
  [className: string]: {           // "7A", "7B", "7C", "7D"
    [rowKey: string]: GradeScheduleCell;  // rowKey = `${day}-${period}`
  };
};

// Complete data for all grades
export interface AllGradesData {
  grade7: GradeGrid;
  grade8: GradeGrid;
  grade9: GradeGrid;
}

// Metadata for saving
export interface ScheduleMetadata {
  semester: 1 | 2;
  academicYear: string;       // "2024/2025"
  effectiveFrom: string;      // ISO date
  effectiveUntil?: string;    // ISO date or null
}

// Validation result
export interface ValidationResult {
  valid: boolean;
  type?: 'time_conflict' | 'jp_exceeded' | 'locked_cell';
  message?: string;
  conflictInfo?: {
    teacherCode: string;
    className: string;
    day: string;
    period: number;
    grade: string;
  };
}

// JP calculation result
export interface JPUsageInfo {
  teacherCode: string;
  currentJP: number;
  maxJP: number | null;
  remaining: number | null;  // null if no limit
  isOverLimit: boolean;
  usageByGrade: {
    grade7: number;
    grade8: number;
    grade9: number;
  };
}

// Excel parsed data (from KODE GURU sheet)
export interface ParsedTeacher {
  no: number;
  code: string;
  name: string;
  subject: string;
  maxJP: number | null;     // Parsed from "Jam Pel" column
  classSpread: string;      // "7CD, 8ACDE"
}

// Excel parsed schedule entry
export interface ParsedScheduleEntry {
  day: string;
  period: number;
  className: string;        // "7A", "7B"
  teacherCode: string;
  subject: string;
  rawValue: string;         // Original cell value like "6-IPA"
}

// Full parsed Excel data
export interface ParsedExcelData {
  teachers: ParsedTeacher[];
  schedules: ParsedScheduleEntry[];
  classes: string[];
  grades: string[];
  errors: string[];
}

// Save request to backend
export interface SaveGradeScheduleRequest {
  grade: string;
  academic_year: string;
  semester: 1 | 2;
  effective_from: string;
  effective_until?: string;
  schedules: Array<{
    class_name: string;
    day: string;
    period: number;
    time_start: string;
    time_end: string;
    teacher_code: string | null;
    teacher_id?: string;
    subject: string | null;
    is_locked: boolean;
  }>;
}

// Backend response for teachers with JP
export interface TeacherJPResponse {
  code: string;
  name: string;
  subject: string;
  max_jp: number | null;
  employee_id: string;
}
