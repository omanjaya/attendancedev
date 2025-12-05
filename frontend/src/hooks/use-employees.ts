import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  getEmployees,
  getEmployee,
  createEmployee,
  updateEmployee,
  deleteEmployee,
  searchEmployees,
  getEmployeeStatistics,
  uploadEmployeeAvatar,
  deleteEmployeeAvatar,
} from '@/lib/api/employees';
import type { EmployeeFilters, EmployeeFormData } from '@/types';
import { useNotificationStore } from '@/stores';

// Query keys
export const employeeKeys = {
  all: ['employees'] as const,
  lists: () => [...employeeKeys.all, 'list'] as const,
  list: (filters: EmployeeFilters) => [...employeeKeys.lists(), filters] as const,
  details: () => [...employeeKeys.all, 'detail'] as const,
  detail: (id: string) => [...employeeKeys.details(), id] as const,
  search: (query: string) => [...employeeKeys.all, 'search', query] as const,
  statistics: () => [...employeeKeys.all, 'statistics'] as const,
};

// Get employees list
export function useEmployees(filters?: EmployeeFilters) {
  return useQuery({
    queryKey: employeeKeys.list(filters || {}),
    queryFn: () => getEmployees(filters),
  });
}

// Get single employee
export function useEmployee(id: string) {
  return useQuery({
    queryKey: employeeKeys.detail(id),
    queryFn: () => getEmployee(id),
    enabled: !!id,
  });
}

// Search employees
export function useEmployeeSearch(query: string) {
  return useQuery({
    queryKey: employeeKeys.search(query),
    queryFn: () => searchEmployees(query),
    enabled: query.length >= 2,
  });
}

// Get employee statistics
export function useEmployeeStatistics() {
  return useQuery({
    queryKey: employeeKeys.statistics(),
    queryFn: getEmployeeStatistics,
  });
}

// Create employee mutation
export function useCreateEmployee() {
  const queryClient = useQueryClient();
  const { success, error } = useNotificationStore();

  return useMutation({
    mutationFn: (data: EmployeeFormData) => createEmployee(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: employeeKeys.lists() });
      queryClient.invalidateQueries({ queryKey: employeeKeys.statistics() });
      success('Berhasil', 'Karyawan berhasil ditambahkan');
    },
    onError: (err: Error) => {
      error('Gagal', err.message || 'Gagal menambahkan karyawan');
    },
  });
}

// Update employee mutation
export function useUpdateEmployee() {
  const queryClient = useQueryClient();
  const { success, error } = useNotificationStore();

  return useMutation({
    mutationFn: ({ id, data }: { id: string; data: Partial<EmployeeFormData> }) =>
      updateEmployee(id, data),
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: employeeKeys.lists() });
      queryClient.invalidateQueries({ queryKey: employeeKeys.detail(variables.id) });
      success('Berhasil', 'Data karyawan berhasil diperbarui');
    },
    onError: (err: Error) => {
      error('Gagal', err.message || 'Gagal memperbarui data karyawan');
    },
  });
}

// Delete employee mutation
export function useDeleteEmployee() {
  const queryClient = useQueryClient();
  const { success, error } = useNotificationStore();

  return useMutation({
    mutationFn: (id: string) => deleteEmployee(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: employeeKeys.lists() });
      queryClient.invalidateQueries({ queryKey: employeeKeys.statistics() });
      success('Berhasil', 'Karyawan berhasil dihapus');
    },
    onError: (err: Error) => {
      error('Gagal', err.message || 'Gagal menghapus karyawan');
    },
  });
}

// Upload employee avatar mutation
export function useUploadEmployeeAvatar() {
  const queryClient = useQueryClient();
  const { success, error } = useNotificationStore();

  return useMutation({
    mutationFn: ({ employeeId, file }: { employeeId: string; file: File }) =>
      uploadEmployeeAvatar(employeeId, file),
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: employeeKeys.lists() });
      queryClient.invalidateQueries({ queryKey: employeeKeys.detail(variables.employeeId) });
      success('Berhasil', 'Avatar berhasil diunggah');
    },
    onError: (err: Error) => {
      error('Gagal', err.message || 'Gagal mengunggah avatar');
    },
  });
}

// Delete employee avatar mutation
export function useDeleteEmployeeAvatar() {
  const queryClient = useQueryClient();
  const { success, error } = useNotificationStore();

  return useMutation({
    mutationFn: (employeeId: string) => deleteEmployeeAvatar(employeeId),
    onSuccess: (_, employeeId) => {
      queryClient.invalidateQueries({ queryKey: employeeKeys.lists() });
      queryClient.invalidateQueries({ queryKey: employeeKeys.detail(employeeId) });
      success('Berhasil', 'Avatar berhasil dihapus');
    },
    onError: (err: Error) => {
      error('Gagal', err.message || 'Gagal menghapus avatar');
    },
  });
}
