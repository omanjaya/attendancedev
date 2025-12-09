import { apiClient } from './client';
import type { ImportResult } from '@/components/shared/ExcelImportDialog';

// Import employees from Excel file
export async function importEmployees(file: File): Promise<ImportResult> {
    const formData = new FormData();
    formData.append('file', file);

    const response = await apiClient.post('/import/employees', formData, {
        headers: {
            'Content-Type': 'multipart/form-data',
        },
    });

    return response.data;
}

// Get employee import template URL
export function getEmployeeTemplateUrl(): string {
    return `${import.meta.env.VITE_API_URL || '/api'}/v1/import/employees/template`;
}

// Import subjects from Excel file
export async function importSubjects(file: File): Promise<ImportResult> {
    const formData = new FormData();
    formData.append('file', file);

    const response = await apiClient.post('/import/subjects', formData, {
        headers: {
            'Content-Type': 'multipart/form-data',
        },
    });

    return response.data;
}

// Import positions from Excel file
export async function importPositions(file: File): Promise<ImportResult> {
    const formData = new FormData();
    formData.append('file', file);

    const response = await apiClient.post('/import/positions', formData, {
        headers: {
            'Content-Type': 'multipart/form-data',
        },
    });

    return response.data;
}

// Import departments from Excel file
export async function importDepartments(file: File): Promise<ImportResult> {
    const formData = new FormData();
    formData.append('file', file);

    const response = await apiClient.post('/import/departments', formData, {
        headers: {
            'Content-Type': 'multipart/form-data',
        },
    });

    return response.data;
}

// Import classrooms from Excel file
export async function importClassrooms(file: File): Promise<ImportResult> {
    const formData = new FormData();
    formData.append('file', file);

    const response = await apiClient.post('/import/classrooms', formData, {
        headers: {
            'Content-Type': 'multipart/form-data',
        },
    });

    return response.data;
}

// Generic import function using FormData
export async function importData(
    endpoint: string,
    file: File
): Promise<ImportResult> {
    const formData = new FormData();
    formData.append('file', file);

    const response = await apiClient.post(endpoint, formData, {
        headers: {
            'Content-Type': 'multipart/form-data',
        },
    });

    return response.data;
}
