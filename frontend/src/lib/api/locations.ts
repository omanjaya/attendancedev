import apiClient from './client';
import type { Location, LocationFormData, LocationStatistics } from '@/types/location';

const ENDPOINTS = {
  list: '/admin/locations',
  detail: (id: string) => `/admin/locations/${id}`,
  statistics: '/admin/locations/statistics',
  toggle: (id: string) => `/admin/locations/${id}/toggle-status`,
  verify: '/locations/verify',
  select: '/locations/select',
} as const;

// Get all locations
export async function getLocations(): Promise<Location[]> {
  const response = await apiClient.get<{ success: boolean; data: Location[] }>(
    ENDPOINTS.list
  );
  return response.data.data;
}

// Get location statistics
export async function getLocationStatistics(): Promise<LocationStatistics> {
  const response = await apiClient.get<{
    success: boolean;
    data: {
      total: number;
      active: number;
      inactive: number;
    };
  }>(ENDPOINTS.statistics);

  return {
    total_locations: response.data.data.total,
    active_locations: response.data.data.active,
    inactive_locations: response.data.data.inactive,
    total_employees_assigned: 0,
    average_radius: 0,
    locations_with_wifi: 0,
  };
}

// Get single location by ID
export async function getLocation(id: string): Promise<Location> {
  const response = await apiClient.get<{ success: boolean; data: Location }>(
    ENDPOINTS.detail(id)
  );
  return response.data.data;
}

// Create new location
export async function createLocation(data: LocationFormData): Promise<Location> {
  const response = await apiClient.post<{ success: boolean; message: string; data: Location }>(
    ENDPOINTS.list,
    data
  );
  return response.data.data;
}

// Update location
export async function updateLocation(id: string, data: Partial<LocationFormData>): Promise<Location> {
  const response = await apiClient.put<{ success: boolean; message: string; data: Location }>(
    ENDPOINTS.detail(id),
    data
  );
  return response.data.data;
}

// Delete location
export async function deleteLocation(id: string): Promise<void> {
  await apiClient.delete(ENDPOINTS.detail(id));
}

// Toggle location status (active/inactive)
export async function toggleLocationStatus(id: string): Promise<Location> {
  const response = await apiClient.post<{ success: boolean; message: string; data: Location }>(
    ENDPOINTS.toggle(id)
  );
  return response.data.data;
}

// Verify location (GPS)
export async function verifyLocation(params: {
  latitude: number;
  longitude: number;
  location_id?: string;
}): Promise<{
  verified: boolean;
  distance?: number;
  allowed_radius?: number;
  location?: {
    id: string;
    name: string;
    address: string;
  };
  message: string;
}> {
  const response = await apiClient.post<{
    verified: boolean;
    distance?: number;
    allowed_radius?: number;
    location?: {
      id: string;
      name: string;
      address: string;
    };
    message: string;
  }>(ENDPOINTS.verify, params);
  return response.data;
}

// Get locations for select dropdown
export async function getLocationsForSelect(): Promise<
  Array<{ id: string; name: string; address: string }>
> {
  const response = await apiClient.get<Array<{ id: string; name: string; address: string }>>(
    ENDPOINTS.select
  );
  return response.data;
}
