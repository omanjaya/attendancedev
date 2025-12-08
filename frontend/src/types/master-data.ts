export interface AcademicYear {
    id: string;
    name: string;
    start_date: string;
    end_date: string;
    semester: 'odd' | 'even';
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface EmployeeType {
    id: string;
    name: string;
    code: string;
    description?: string;
    schedule_mode: 'fixed' | 'flexible';
    default_start_time?: string;  // Format: "HH:mm"
    default_end_time?: string;    // Format: "HH:mm"
    late_tolerance_minutes: number;
    require_schedule_for_attendance: boolean;
    can_override_by_teaching: boolean;
    features: string[];  // e.g., ["can_request_leave", "can_view_payroll"]
    is_active: boolean;
    sort_order: number;
    employees_count?: number;  // Added when fetching single type
    formatted_working_hours?: string;  // Accessor from backend
    schedule_mode_label?: string;  // Accessor from backend
    created_at: string;
    updated_at: string;
}

export interface Subject {
    id: string;
    name: string;
    code: string;
    description?: string;
    category?: string;
    is_active: boolean;
    sort_order?: number;
    employees_count?: number;
    created_at: string;
    updated_at: string;
}

export interface Classroom {
    id: string;
    name: string;
    grade_level: string;
    major?: string;
    class_number: string;
    capacity: number;
    room?: string;
    is_active: boolean;
    metadata?: any;
    created_at: string;
    updated_at: string;
}

export interface Period {
    id: string;
    name: string;
    start_time: string;
    end_time: string;
    order: number;
    is_active: boolean;
    metadata?: any;
    created_at: string;
    updated_at: string;
}

export interface PaginatedResponse<T> {
    success: boolean;
    message: string;
    data: T[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
        has_more_pages: boolean;
    };
}

export interface Department {
    id: string;
    name: string;
    code: string;
    description?: string;
    is_active: boolean;
    sort_order: number;
    employees_count?: number;
    created_at: string;
    updated_at: string;
}

export interface Position {
    id: string;
    name: string;
    code: string;
    description?: string;
    is_active: boolean;
    sort_order: number;
    employees_count?: number;
    created_at: string;
    updated_at: string;
}
