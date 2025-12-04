// Employee status
export type EmployeeStatus = 'active' | 'inactive' | 'on_leave' | 'terminated';

import type { Location } from './location';

// Employee interface
export interface Employee {
  id: number;
  employee_id: string;
  user_id?: number;
  name: string;
  email: string;
  phone?: string;
  position: string;
  department: string;
  status: EmployeeStatus;
  join_date: string;
  avatar?: string;
  address?: string;
  birth_date?: string;
  gender?: 'male' | 'female';
  face_registered: boolean;
  location_id?: string | number;
  location_name?: string;
  location?: Location;
  created_at: string;
  updated_at: string;
}

// Employee form data for create/update
export interface EmployeeFormData {
  name: string;
  email: string;
  phone?: string;
  position: string;
  department: string;
  status: EmployeeStatus;
  join_date: string;
  address?: string;
  birth_date?: string;
  gender?: 'male' | 'female';
  location_id?: string;
}

// Employee filters
export interface EmployeeFilters {
  search?: string;
  department?: string;
  status?: EmployeeStatus;
  page?: number;
  per_page?: number;
}

// Paginated response
export interface PaginatedResponse<T> {
  data: T[];
  meta: {
    current_page: number;
    from: number;
    last_page: number;
    per_page: number;
    to: number;
    total: number;
  };
}

// Employee statistics
export interface EmployeeStatistics {
  total: number;
  active: number;
  inactive: number;
  on_leave: number;
  by_department: Record<string, number>;
}
