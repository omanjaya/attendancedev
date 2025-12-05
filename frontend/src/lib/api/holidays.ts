import apiClient from './client';
import type { Holiday, PaginatedResponse } from '@/types';

const ENDPOINTS = {
  list: '/admin/holidays',
  v1List: '/holidays',
  v1ByMonth: '/holidays/by-month',
  v1Detail: (id: string) => `/holidays/${id}`,
  v1Statistics: '/holidays/statistics',
  v1BulkImport: '/holidays/bulk-import',
} as const;

export interface HolidayFilters {
  year?: number;
  month?: number;
  type?: string;
  status?: string;
  search?: string;
  per_page?: number;
}

export interface HolidayFormData {
  name: string;
  description?: string;
  date: string;
  end_date?: string;
  type: 'public_holiday' | 'religious_holiday' | 'school_holiday' | 'substitute_holiday';
  status?: 'active' | 'cancelled' | 'moved';
  is_recurring?: boolean;
  recurring_pattern?: Record<string, any>;
  affected_roles?: string[];
  source?: string;
  color?: string;
  is_paid?: boolean;
  metadata?: Record<string, any>;
}

export interface HolidayStatistics {
  total_holidays: number;
  active_holidays: number;
  holidays_this_month: number;
  holidays_this_year: number;
  paid_holidays: number;
  unpaid_holidays: number;
  recurring_holidays: number;
  upcoming_holiday?: Holiday;
}

// Get holidays with pagination and filters (old admin endpoint)
export async function getHolidays(
  filters?: HolidayFilters
): Promise<PaginatedResponse<Holiday>> {
  const response = await apiClient.get<PaginatedResponse<Holiday>>(ENDPOINTS.list, {
    params: filters,
  });
  return response.data;
}

// Get holidays by month (new V1 API)
export async function getHolidaysByMonth(
  month: number,
  year: number
): Promise<{ holidays: Holiday[]; count: number; month: number; year: number }> {
  const response = await apiClient.get<{
    success: boolean;
    data: { holidays: Holiday[]; count: number; month: number; year: number };
  }>(ENDPOINTS.v1ByMonth, {
    params: { month, year },
  });
  return response.data.data;
}

// Get holiday statistics (new V1 API)
export async function getHolidayStatistics(year?: number): Promise<HolidayStatistics> {
  const response = await apiClient.get<{ success: boolean; data: HolidayStatistics }>(
    ENDPOINTS.v1Statistics,
    { params: { year } }
  );
  return response.data.data;
}

// Create new holiday (new V1 API)
export async function createHoliday(data: HolidayFormData): Promise<Holiday> {
  const response = await apiClient.post<{ success: boolean; data: Holiday }>(
    ENDPOINTS.v1List,
    data
  );
  return response.data.data;
}

// Update holiday (new V1 API)
export async function updateHoliday(id: string, data: Partial<HolidayFormData>): Promise<Holiday> {
  const response = await apiClient.put<{ success: boolean; data: Holiday }>(
    ENDPOINTS.v1Detail(id),
    data
  );
  return response.data.data;
}

// Delete holiday (new V1 API)
export async function deleteHoliday(id: string): Promise<void> {
  await apiClient.delete(ENDPOINTS.v1Detail(id));
}

// Bulk import holidays (new V1 API)
export async function bulkImportHolidays(
  holidays: Array<Omit<HolidayFormData, 'status' | 'is_paid' | 'color'>>
): Promise<{ imported: number; total: number; errors: string[] }> {
  const response = await apiClient.post<{
    success: boolean;
    data: { imported: number; total: number; errors: string[] };
  }>(ENDPOINTS.v1BulkImport, { holidays });
  return response.data.data;
}
