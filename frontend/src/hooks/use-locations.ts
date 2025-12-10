import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  getLocations,
  getLocation,
  createLocation as createLocationApi,
  updateLocation as updateLocationApi,
  deleteLocation as deleteLocationApi,
  toggleLocationStatus as toggleLocationStatusApi,
  assignEmployees as assignEmployeesApi,
  getLocationStatistics,
} from '@/lib/api/locations';
import type { LocationFormData } from '@/types/location';
import { useNotificationStore } from '@/stores';

// Location filters type (for future use)
export interface LocationFilters {
  is_active?: boolean;
  search?: string;
}

// Query keys
export const locationKeys = {
  all: ['locations'] as const,
  lists: () => [...locationKeys.all, 'list'] as const,
  list: (filters?: LocationFilters) => [...locationKeys.lists(), filters] as const,
  details: () => [...locationKeys.all, 'detail'] as const,
  detail: (id: string) => [...locationKeys.details(), id] as const,
  statistics: () => [...locationKeys.all, 'statistics'] as const,
};

// Get locations list
export function useLocations(filters?: LocationFilters) {
  return useQuery({
    queryKey: locationKeys.list(filters),
    queryFn: () => getLocations(),
    // Client-side filtering can be added here if needed via select option
  });
}

// Get single location
export function useLocation(id: string) {
  return useQuery({
    queryKey: locationKeys.detail(id),
    queryFn: () => getLocation(id),
    enabled: !!id,
  });
}

// Get location statistics
export function useLocationStatistics() {
  return useQuery({
    queryKey: locationKeys.statistics(),
    queryFn: getLocationStatistics,
  });
}

// Create location mutation
export function useCreateLocation() {
  const queryClient = useQueryClient();
  const { success } = useNotificationStore();

  return useMutation({
    mutationFn: (data: LocationFormData) => createLocationApi(data),
    onSuccess: async () => {
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: locationKeys.lists() }),
        queryClient.invalidateQueries({ queryKey: locationKeys.statistics() }),
      ]);
      success('Berhasil', 'Lokasi berhasil dibuat');
    },
    // onError removed - global error handler will catch it
  });
}

// Update location mutation
export function useUpdateLocation() {
  const queryClient = useQueryClient();
  const { success } = useNotificationStore();

  return useMutation({
    mutationFn: ({ id, data }: { id: string; data: Partial<LocationFormData> }) =>
      updateLocationApi(id, data),
    onSuccess: async (result) => {
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: locationKeys.lists() }),
        queryClient.invalidateQueries({ queryKey: locationKeys.detail(result.id) }),
        queryClient.invalidateQueries({ queryKey: locationKeys.statistics() }),
      ]);
      success('Berhasil', 'Lokasi berhasil diperbarui');
    },
    // onError removed - global error handler will catch it
  });
}

// Delete location mutation
export function useDeleteLocation() {
  const queryClient = useQueryClient();
  const { success } = useNotificationStore();

  return useMutation({
    mutationFn: (id: string) => deleteLocationApi(id),
    onSuccess: async () => {
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: locationKeys.lists() }),
        queryClient.invalidateQueries({ queryKey: locationKeys.statistics() }),
      ]);
      success('Berhasil', 'Lokasi berhasil dihapus');
    },
    // onError removed - global error handler will catch it
  });
}

// Toggle location status mutation
export function useToggleLocationStatus() {
  const queryClient = useQueryClient();
  const { success } = useNotificationStore();

  return useMutation({
    mutationFn: (id: string) => toggleLocationStatusApi(id),
    onSuccess: async (result) => {
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: locationKeys.lists() }),
        queryClient.invalidateQueries({ queryKey: locationKeys.detail(result.id) }),
        queryClient.invalidateQueries({ queryKey: locationKeys.statistics() }),
      ]);
      success('Berhasil', 'Status lokasi berhasil diubah');
    },
    // onError removed - global error handler will catch it
  });
}

// Assign employees mutation
export function useAssignEmployees() {
  const queryClient = useQueryClient();
  const { success } = useNotificationStore();

  return useMutation({
    mutationFn: ({ locationId, employeeIds }: { locationId: string; employeeIds: string[] }) =>
      assignEmployeesApi(locationId, employeeIds),
    onSuccess: async (_, variables) => {
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: locationKeys.lists() }),
        queryClient.invalidateQueries({ queryKey: locationKeys.detail(variables.locationId) }),
        queryClient.invalidateQueries({ queryKey: locationKeys.statistics() }),
      ]);
      success('Berhasil', 'Pegawai berhasil ditetapkan ke lokasi');
    },
    // onError removed - global error handler will catch it
  });
}
