// Report Types

export type ReportType = 'attendance' | 'payroll' | 'leave' | 'performance';
export type ReportFormat = 'pdf' | 'excel';

// Dashboard/Summary Statistics
export interface ReportSummary {
  period: {
    start: string;
    end: string;
  };
  total_employees: number;
  work_days: number;
  attendance: {
    total_records: number;
    present: number;
    late: number;
    absent: number;
    rate: number;
  };
  avg_work_hours: number;
}

// Monthly Attendance Data
export interface MonthlyAttendanceData {
  month: string;
  month_num: number;
  present: number;
  late: number;
  absent: number;
}

// Weekly Trend Data
export interface WeeklyTrendData {
  week: string;
  week_start: string;
  attendance_rate: number;
}

// Department Stats
export interface DepartmentStats {
  department: string;
  employee_count: number;
  attendance_rate: number;
  present: number;
  late: number;
}

// Leave Stats
export interface LeaveStats {
  leave_type: string;
  count: number;
  total_days: number;
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

// Response from generate report API
export interface GenerateReportResponse {
  report: GeneratedReport;
  download_url?: string;
  generated_sync?: boolean;
  message?: string;
}
