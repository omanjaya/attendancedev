import { apiClient } from './client';

// Paginated response type
export interface PaginatedResponse<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

// Types
export interface AttendanceCorrection {
    id: string;
    employee_id: string;
    attendance_id: string | null;
    correction_date: string;
    correction_type: 'check_in' | 'check_out' | 'both' | 'add_missing' | 'delete';
    original_check_in: string | null;
    original_check_out: string | null;
    requested_check_in: string | null;
    requested_check_out: string | null;
    reason: string;
    supporting_document: string | null;
    status: 'pending' | 'approved' | 'rejected' | 'cancelled';
    reviewed_by: number | null;
    reviewed_at: string | null;
    review_notes: string | null;
    created_at: string;
    updated_at: string;
    // Relations
    employee?: {
        id: string;
        full_name: string;
        employee_id: string;
    };
    attendance?: {
        id: string;
        date: string;
        check_in_time: string | null;
        check_out_time: string | null;
    };
    reviewer?: {
        id: number;
        name: string;
    };
}

export interface CorrectionStatistics {
    pending: number;
    approved: number;
    rejected: number;
    total: number;
    this_month: number;
}

export interface CreateCorrectionData {
    attendance_id?: string;
    correction_date: string;
    correction_type: 'check_in' | 'check_out' | 'both' | 'add_missing' | 'delete';
    requested_check_in?: string;
    requested_check_out?: string;
    reason: string;
    supporting_document?: File;
}

export interface CorrectionFilters {
    status?: 'pending' | 'approved' | 'rejected' | 'cancelled' | 'all';
    date_from?: string;
    date_to?: string;
    employee_id?: string;
    sort?: string;
    direction?: 'asc' | 'desc';
    per_page?: number;
    page?: number;
}

// API Functions

/**
 * Get list of attendance corrections
 */
export async function getAttendanceCorrections(
    filters: CorrectionFilters = {}
): Promise<PaginatedResponse<AttendanceCorrection>> {
    const params = new URLSearchParams();

    Object.entries(filters).forEach(([key, value]) => {
        if (value !== undefined && value !== null && value !== '') {
            params.append(key, String(value));
        }
    });

    const response = await apiClient.get(`/attendance-corrections?${params.toString()}`);
    return response.data.data;
}

/**
 * Get correction statistics (admin only)
 */
export async function getCorrectionStatistics(): Promise<CorrectionStatistics> {
    const response = await apiClient.get('/attendance-corrections/statistics');
    return response.data.data;
}

/**
 * Get a single correction by ID
 */
export async function getAttendanceCorrection(id: string): Promise<AttendanceCorrection> {
    const response = await apiClient.get(`/attendance-corrections/${id}`);
    return response.data.data;
}

/**
 * Create a new correction request
 */
export async function createAttendanceCorrection(
    data: CreateCorrectionData
): Promise<AttendanceCorrection> {
    const formData = new FormData();

    formData.append('correction_date', data.correction_date);
    formData.append('correction_type', data.correction_type);
    formData.append('reason', data.reason);

    if (data.attendance_id) {
        formData.append('attendance_id', data.attendance_id);
    }
    if (data.requested_check_in) {
        formData.append('requested_check_in', data.requested_check_in);
    }
    if (data.requested_check_out) {
        formData.append('requested_check_out', data.requested_check_out);
    }
    if (data.supporting_document) {
        formData.append('supporting_document', data.supporting_document);
    }

    const response = await apiClient.post('/attendance-corrections', formData, {
        headers: {
            'Content-Type': 'multipart/form-data',
        },
    });
    return response.data.data;
}

/**
 * Cancel a correction request (employee only)
 */
export async function cancelAttendanceCorrection(id: string): Promise<AttendanceCorrection> {
    const response = await apiClient.post(`/attendance-corrections/${id}/cancel`);
    return response.data.data;
}

/**
 * Approve a correction request (admin only)
 */
export async function approveAttendanceCorrection(
    id: string,
    notes?: string
): Promise<AttendanceCorrection> {
    const response = await apiClient.post(`/attendance-corrections/${id}/approve`, { notes });
    return response.data.data;
}

/**
 * Reject a correction request (admin only)
 */
export async function rejectAttendanceCorrection(
    id: string,
    notes: string
): Promise<AttendanceCorrection> {
    const response = await apiClient.post(`/attendance-corrections/${id}/reject`, { notes });
    return response.data.data;
}

/**
 * Get URL for downloading supporting document
 */
export function getCorrectionDocumentUrl(id: string): string {
    return `/api/v1/attendance-corrections/${id}/document`;
}

// Status helpers
export const CORRECTION_STATUS_LABELS: Record<string, string> = {
    pending: 'Menunggu',
    approved: 'Disetujui',
    rejected: 'Ditolak',
    cancelled: 'Dibatalkan',
};

export const CORRECTION_STATUS_COLORS: Record<string, string> = {
    pending: 'yellow',
    approved: 'green',
    rejected: 'red',
    cancelled: 'gray',
};

export const CORRECTION_TYPE_LABELS: Record<string, string> = {
    check_in: 'Koreksi Check-In',
    check_out: 'Koreksi Check-Out',
    both: 'Koreksi Jam Masuk & Keluar',
    add_missing: 'Tambah Absensi',
    delete: 'Hapus Absensi',
};
