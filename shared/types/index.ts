// Common types shared between frontend and backend

export interface User {
  id: number;
  name: string;
  email: string;
  role: string;
}

export interface Employee {
  id: number;
  name: string;
  email: string;
  position: string;
  department: string;
  hire_date: string;
}

export interface Attendance {
  id: number;
  employee_id: number;
  check_in: string;
  check_out?: string;
  status: 'present' | 'late' | 'absent' | 'on_leave';
  location?: {
    latitude: number;
    longitude: number;
  };
}

export interface Schedule {
  id: number;
  employee_id: number;
  day_of_week: number;
  start_time: string;
  end_time: string;
  is_active: boolean;
}

export interface ApiResponse<T = any> {
  success: boolean;
  data?: T;
  message?: string;
  errors?: Record<string, string[]>;
}

export interface PaginatedResponse<T> extends ApiResponse<T[]> {
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}
