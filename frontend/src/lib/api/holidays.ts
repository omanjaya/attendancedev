import apiClient from './client';
import type { Holiday, PaginatedResponse } from '@/types';

const ENDPOINTS = {
  list: '/admin/holidays',
} as const;

export interface HolidayFilters {
  year?: number;
  month?: number;
  type?: string;
  status?: string;
  search?: string;
  per_page?: number;
}

// Get holidays with pagination and filters
export async function getHolidays(
  filters?: HolidayFilters
): Promise<PaginatedResponse<Holiday>> {
  const response = await apiClient.get<PaginatedResponse<Holiday>>(ENDPOINTS.list, {
    params: filters,
  });
  return response.data;
}
