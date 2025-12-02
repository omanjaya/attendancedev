// Report Types

export type ReportType = 'attendance' | 'payroll' | 'leave' | 'performance';
export type ReportFormat = 'pdf' | 'excel';

// Dashboard/Summary Statistics
export interface ReportSummary {
  average_attendance_rate: number;
  total_employees: number;
  average_working_hours: number;
  total_leave_used: number;
}

// Monthly Attendance Data
export interface MonthlyAttendanceData {
  month: string;
  hadir: number;
  terlambat: number;
  tidak_hadir: number;
}

// Weekly Trend Data
export interface WeeklyTrendData {
  day: string;
  masuk: number;
  keluar: number;
}

// Department Stats
export interface DepartmentStats {
  name: string;
  value: number;
  color: string;
}

// Leave Stats
export interface LeaveStats {
  type: string;
  used: number;
  total: number;
}

// Full Report Data
export interface ReportData {
  summary: ReportSummary;
  monthly_attendance: MonthlyAttendanceData[];
  weekly_trend: WeeklyTrendData[];
  department_stats: DepartmentStats[];
  leave_stats: LeaveStats[];
}

// Report Generation Request
export interface GenerateReportRequest {
  type: ReportType; // Changed from report_type to match backend
  format: ReportFormat;
  start_date: string;
  end_date: string;
  filters?: {
    department_id?: string;
    columns?: string[];
    [key: string]: any;
  };
}

// Saved Report Template
export interface ReportTemplate {
  id: string;
  name: string;
  description?: string;
  report_type: ReportType;
  columns: string[];
  filters: {
    department_id?: string;
  };
  created_at: string;
  updated_at: string;
}

// Generated Report
export interface GeneratedReport {
  id: string;
  name: string;
  report_type: ReportType;
  format: ReportFormat;
  status: 'pending' | 'processing' | 'completed' | 'failed';
  download_url?: string;
  created_at: string;
  completed_at?: string;
  error_message?: string;
}

// Report Filters
export interface ReportFilters {
  year?: number;
  month?: number;
  start_date?: string;
  end_date?: string;
  department_id?: string;
  employee_id?: string;
}
