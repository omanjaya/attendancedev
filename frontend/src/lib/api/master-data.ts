import apiClient from './client';
import type {
    AcademicYear,
    EmployeeType,
    Subject,
    Classroom,
    Period,
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
