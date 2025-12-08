import apiClient from './client';
import type {
    AcademicYear,
    EmployeeType,
    Subject,
    Classroom,
    Period,
    Department,
    Position,
    PaginatedResponse,
} from '@/types/master-data';

// Academic Years
export const getAcademicYears = async (params?: any) => {
    const response = await apiClient.get<PaginatedResponse<AcademicYear>>('/admin/academic-years', { params });
    return response.data;
};

export const createAcademicYear = async (data: Partial<AcademicYear>) => {
    const response = await apiClient.post<{ success: boolean; data: AcademicYear }>('/admin/academic-years', data);
    return response.data;
};

export const updateAcademicYear = async (id: string, data: Partial<AcademicYear>) => {
    const response = await apiClient.put<{ success: boolean; data: AcademicYear }>(`/admin/academic-years/${id}`, data);
    return response.data;
};

export const deleteAcademicYear = async (id: string) => {
    const response = await apiClient.delete<{ success: boolean }>(`/admin/academic-years/${id}`);
    return response.data;
};

// Employee Types
export const getEmployeeTypes = async (params?: any) => {
    const response = await apiClient.get<PaginatedResponse<EmployeeType>>('/admin/employee-types', { params });
    return response.data;
};

// Get all employee types for dropdowns (no pagination)
export const getEmployeeTypesAll = async () => {
    const response = await apiClient.get<{ success: boolean; data: EmployeeType[] }>('/admin/employee-types', {
        params: { all: true }
    });
    return response.data;
};

// Get single employee type with details
export const getEmployeeType = async (id: string) => {
    const response = await apiClient.get<{ success: boolean; data: EmployeeType }>(`/admin/employee-types/${id}`);
    return response.data;
};

// Get available features for employee types
export const getEmployeeTypeFeatures = async () => {
    const response = await apiClient.get<{ success: boolean; data: Record<string, string> }>('/admin/employee-types-features');
    return response.data;
};

export const createEmployeeType = async (data: Partial<EmployeeType>) => {
    const response = await apiClient.post<{ success: boolean; data: EmployeeType }>('/admin/employee-types', data);
    return response.data;
};

export const updateEmployeeType = async (id: string, data: Partial<EmployeeType>) => {
    const response = await apiClient.put<{ success: boolean; data: EmployeeType }>(`/admin/employee-types/${id}`, data);
    return response.data;
};

export const deleteEmployeeType = async (id: string) => {
    const response = await apiClient.delete<{ success: boolean }>(`/admin/employee-types/${id}`);
    return response.data;
};

// Reorder employee types
export const reorderEmployeeTypes = async (order: { id: string; sort_order: number }[]) => {
    const response = await apiClient.post<{ success: boolean }>('/admin/employee-types-reorder', { order });
    return response.data;
};

// Subjects
export const getSubjects = async (params?: any) => {
    const response = await apiClient.get<PaginatedResponse<Subject>>('/admin/subjects', { params });
    return response.data;
};

export const createSubject = async (data: Partial<Subject>) => {
    const response = await apiClient.post<{ success: boolean; data: Subject }>('/admin/subjects', data);
    return response.data;
};

export const updateSubject = async (id: string, data: Partial<Subject>) => {
    const response = await apiClient.put<{ success: boolean; data: Subject }>(`/admin/subjects/${id}`, data);
    return response.data;
};

export const deleteSubject = async (id: string) => {
    const response = await apiClient.delete<{ success: boolean }>(`/admin/subjects/${id}`);
    return response.data;
};

// Classrooms
export const getClassrooms = async (params?: any) => {
    const response = await apiClient.get<PaginatedResponse<Classroom>>('/admin/classrooms', { params });
    return response.data;
};

export const createClassroom = async (data: Partial<Classroom>) => {
    const response = await apiClient.post<{ success: boolean; data: Classroom }>('/admin/classrooms', data);
    return response.data;
};

export const updateClassroom = async (id: string, data: Partial<Classroom>) => {
    const response = await apiClient.put<{ success: boolean; data: Classroom }>(`/admin/classrooms/${id}`, data);
    return response.data;
};

export const deleteClassroom = async (id: string) => {
    const response = await apiClient.delete<{ success: boolean }>(`/admin/classrooms/${id}`);
    return response.data;
};

// Periods
export const getPeriods = async (params?: any) => {
    const response = await apiClient.get<PaginatedResponse<Period>>('/admin/periods', { params });
    return response.data;
};

export const createPeriod = async (data: Partial<Period>) => {
    const response = await apiClient.post<{ success: boolean; data: Period }>('/admin/periods', data);
    return response.data;
};

export const updatePeriod = async (id: string, data: Partial<Period>) => {
    const response = await apiClient.put<{ success: boolean; data: Period }>(`/admin/periods/${id}`, data);
    return response.data;
};

export const deletePeriod = async (id: string) => {
    const response = await apiClient.delete<{ success: boolean }>(`/admin/periods/${id}`);
    return response.data;
};

// Departments (Unit Kerja)
export const getDepartments = async (params?: any) => {
    const response = await apiClient.get<PaginatedResponse<Department>>('/admin/departments', { params });
    return response.data;
};

export const getDepartmentsAll = async () => {
    const response = await apiClient.get<{ success: boolean; data: Department[] }>('/admin/departments', {
        params: { all: true }
    });
    return response.data;
};

export const getDepartment = async (id: string) => {
    const response = await apiClient.get<{ success: boolean; data: Department }>(`/admin/departments/${id}`);
    return response.data;
};

export const createDepartment = async (data: Partial<Department>) => {
    const response = await apiClient.post<{ success: boolean; data: Department }>('/admin/departments', data);
    return response.data;
};

export const updateDepartment = async (id: string, data: Partial<Department>) => {
    const response = await apiClient.put<{ success: boolean; data: Department }>(`/admin/departments/${id}`, data);
    return response.data;
};

export const deleteDepartment = async (id: string) => {
    const response = await apiClient.delete<{ success: boolean }>(`/admin/departments/${id}`);
    return response.data;
};

export const reorderDepartments = async (order: { id: string; sort_order: number }[]) => {
    const response = await apiClient.post<{ success: boolean }>('/admin/departments-reorder', { order });
    return response.data;
};

// Positions (Jabatan)
export const getPositions = async (params?: any) => {
    const response = await apiClient.get<PaginatedResponse<Position>>('/admin/positions', { params });
    return response.data;
};

export const getPositionsAll = async () => {
    const response = await apiClient.get<{ success: boolean; data: Position[] }>('/admin/positions', {
        params: { all: true }
    });
    return response.data;
};

export const getPosition = async (id: string) => {
    const response = await apiClient.get<{ success: boolean; data: Position }>(`/admin/positions/${id}`);
    return response.data;
};

export const createPosition = async (data: Partial<Position>) => {
    const response = await apiClient.post<{ success: boolean; data: Position }>('/admin/positions', data);
    return response.data;
};

export const updatePosition = async (id: string, data: Partial<Position>) => {
    const response = await apiClient.put<{ success: boolean; data: Position }>(`/admin/positions/${id}`, data);
    return response.data;
};

export const deletePosition = async (id: string) => {
    const response = await apiClient.delete<{ success: boolean }>(`/admin/positions/${id}`);
    return response.data;
};

export const reorderPositions = async (order: { id: string; sort_order: number }[]) => {
    const response = await apiClient.post<{ success: boolean }>('/admin/positions-reorder', { order });
    return response.data;
};

// Get all subjects for dropdowns (no pagination)
export const getSubjectsAll = async () => {
    const response = await apiClient.get<{ success: boolean; data: Subject[] }>('/admin/subjects', {
        params: { all: true }
    });
    return response.data;
};
