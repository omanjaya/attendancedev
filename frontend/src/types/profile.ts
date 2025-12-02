// Profile Types

export interface UserProfile {
  id: string;
  name: string;
  email: string;
  phone: string | null;
  avatar: string | null;
  role: string;
  department: string | null;
  position: string | null;
  employee_id: string | null;
  employee_pk: string | null;
  joined_at: string | null;
  email_verified_at: string | null;
  two_factor_enabled: boolean;
  created_at: string;
  updated_at: string;
}

export interface ProfileUpdateData {
  name: string;
  email: string;
  phone?: string | null;
}

export interface PasswordChangeData {
  current_password: string;
  password: string;
  password_confirmation: string;
}

export interface AvatarUploadResponse {
  success: boolean;
  avatar_url: string;
}

export interface ProfileStatistics {
  total_attendance: number;
  present_days: number;
  late_days: number;
  absent_days: number;
  leave_days: number;
  overtime_hours: number;
  current_month_attendance_rate: number;
}
