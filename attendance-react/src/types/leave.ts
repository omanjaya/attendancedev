// Leave Management Types

export type LeaveType =
  | 'annual'        // Cuti Tahunan
  | 'sick'          // Sakit
  | 'maternity'     // Cuti Melahirkan
  | 'paternity'     // Cuti Ayah
  | 'marriage'      // Cuti Nikah
  | 'bereavement'   // Cuti Duka
  | 'unpaid'        // Cuti Tanpa Gaji
  | 'special'       // Cuti Khusus
  | 'other';        // Lainnya

export type LeaveStatus =
  | 'pending'       // Menunggu
  | 'approved'      // Disetujui
  | 'rejected'      // Ditolak
  | 'cancelled';    // Dibatalkan

export type LeaveDurationType = 'full_day' | 'half_day_am' | 'half_day_pm';

export interface LeaveRequest {
  id: string;
  employee_id: string;
  employee_name?: string;
  employee_department?: string;
  type: LeaveType;
  status: LeaveStatus;
  start_date: string;
  end_date: string;
  duration_type: LeaveDurationType;
  total_days: number;
  reason: string;
  attachment_url?: string;
  emergency_contact?: string;
  emergency_phone?: string;

  // Approval info
  approved_by?: string;
  approved_by_name?: string;
  approved_at?: string;
  rejection_reason?: string;

  // Metadata
  created_at: string;
  updated_at: string;
}

export interface LeaveBalance {
  id: string;
  employee_id: string;
  employee_name?: string;
  year: number;

  // Balance by type
  annual_total: number;
  annual_used: number;
  annual_remaining: number;

  sick_total: number;
  sick_used: number;
  sick_remaining: number;

  special_total: number;
  special_used: number;
  special_remaining: number;

  // Carry forward
  carry_forward: number;
  carry_forward_expiry?: string;

  updated_at: string;
}

export interface LeaveApproval {
  id: string;
  leave_request_id: string;
  leave_request?: LeaveRequest;
  approver_id: string;
  approver_name?: string;
  level: number;
  status: 'pending' | 'approved' | 'rejected';
  notes?: string;
  approved_at?: string;
  created_at: string;
}

export interface LeavePolicy {
  id: string;
  name: string;
  type: LeaveType;
  default_days: number;
  max_days_per_request: number;
  requires_approval: boolean;
  requires_attachment: boolean;
  min_notice_days: number;
  carry_forward_allowed: boolean;
  carry_forward_max_days?: number;
  carry_forward_expiry_months?: number;
  applicable_to: 'all' | 'permanent' | 'contract';
  is_active: boolean;
}

export interface LeaveCalendarEvent {
  id: string;
  title: string;
  start: string;
  end: string;
  type: LeaveType;
  status: LeaveStatus;
  employee_id: string;
  employee_name: string;
  is_current_user: boolean;
}

// Form types
export interface LeaveRequestFormData {
  type: LeaveType;
  start_date: string;
  end_date: string;
  duration_type: LeaveDurationType;
  reason: string;
  emergency_contact?: string;
  emergency_phone?: string;
  attachment?: File;
}

// Statistics
export interface LeaveStatistics {
  total_requests: number;
  pending_requests: number;
  approved_requests: number;
  rejected_requests: number;
  average_days_per_request: number;
  most_common_type: LeaveType;
  employees_on_leave_today: number;
}

// Type labels in Indonesian
export const leaveTypeLabels: Record<LeaveType, string> = {
  annual: 'Cuti Tahunan',
  sick: 'Sakit',
  maternity: 'Cuti Melahirkan',
  paternity: 'Cuti Ayah',
  marriage: 'Cuti Nikah',
  bereavement: 'Cuti Duka',
  unpaid: 'Cuti Tanpa Gaji',
  special: 'Cuti Khusus',
  other: 'Lainnya',
};

export const leaveStatusLabels: Record<LeaveStatus, string> = {
  pending: 'Menunggu',
  approved: 'Disetujui',
  rejected: 'Ditolak',
  cancelled: 'Dibatalkan',
};

export const leaveTypeColors: Record<LeaveType, string> = {
  annual: '#3B82F6',
  sick: '#EF4444',
  maternity: '#EC4899',
  paternity: '#8B5CF6',
  marriage: '#F59E0B',
  bereavement: '#6B7280',
  unpaid: '#9CA3AF',
  special: '#10B981',
  other: '#6366F1',
};
