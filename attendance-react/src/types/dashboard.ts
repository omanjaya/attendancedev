import type { AttendanceStatistics, AttendanceTrend } from './attendance';
import type { EmployeeStatistics } from './employee';

// Dashboard summary
export interface DashboardSummary {
  attendance: AttendanceStatistics;
  employees: EmployeeStatistics;
  pending_leaves: number;
  upcoming_holidays: number;
}

// Recent activity item
export interface ActivityItem {
  id: number;
  type: 'check_in' | 'check_out' | 'leave_request' | 'leave_approved' | 'leave_rejected';
  employee_id: number;
  employee_name: string;
  employee_avatar?: string;
  description: string;
  timestamp: string;
  metadata?: Record<string, unknown>;
}

// Dashboard data
export interface DashboardData {
  summary: DashboardSummary;
  recent_activity: ActivityItem[];
  attendance_trends: AttendanceTrend[];
  today_schedule?: {
    shift_name: string;
    start_time: string;
    end_time: string;
  };
}
