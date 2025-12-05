import { describe, it, expect, vi, beforeEach } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import type { ReactNode } from 'react';
import { useEmployees, useEmployee, useEmployeeSearch, employeeKeys } from './use-employees';

// Mock the API module
vi.mock('@/lib/api/employees', () => ({
  getEmployees: vi.fn(),
  getEmployee: vi.fn(),
  searchEmployees: vi.fn(),
  createEmployee: vi.fn(),
  updateEmployee: vi.fn(),
  deleteEmployee: vi.fn(),
  getEmployeeStatistics: vi.fn(),
}));

// Mock notification store
vi.mock('@/stores', () => ({
  useNotificationStore: () => ({
    success: vi.fn(),
    error: vi.fn(),
    warning: vi.fn(),
    info: vi.fn(),
  }),
}));

import * as employeesApi from '@/lib/api/employees';

const mockEmployees = [
  {
    id: '1',
    employee_id: 'EMP001',
    name: 'Ahmad Rizki',
    email: 'ahmad.rizki@company.com',
    phone: '081234567890',
    department: 'IT',
    position: 'Software Engineer',
    status: 'active' as const,
    join_date: '2023-01-15',
    face_registered: true,
    created_at: '2023-01-15T00:00:00',
    updated_at: '2023-01-15T00:00:00',
  },
  {
    id: '2',
    employee_id: 'EMP002',
    name: 'Siti Nurhaliza',
    email: 'siti.nurhaliza@company.com',
    phone: '081234567891',
    department: 'HR',
    position: 'HR Manager',
    status: 'active' as const,
    join_date: '2022-06-01',
    face_registered: true,
    created_at: '2022-06-01T00:00:00',
    updated_at: '2022-06-01T00:00:00',
  },
];

const createTestQueryClient = () =>
  new QueryClient({
    defaultOptions: {
      queries: {
        retry: false,
        gcTime: 0,
        staleTime: 0,
      },
    },
  });

const createWrapper = () => {
  const queryClient = createTestQueryClient();
  return ({ children }: { children: ReactNode }) => (
    <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
  );
};

describe('useEmployees Hook', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('employeeKeys', () => {
    it('should generate correct query keys', () => {
      expect(employeeKeys.all).toEqual(['employees']);
      expect(employeeKeys.lists()).toEqual(['employees', 'list']);
      expect(employeeKeys.list({ department: 'IT' })).toEqual(['employees', 'list', { department: 'IT' }]);
      expect(employeeKeys.details()).toEqual(['employees', 'detail']);
      expect(employeeKeys.detail('1')).toEqual(['employees', 'detail', '1']);
      expect(employeeKeys.search('ahmad')).toEqual(['employees', 'search', 'ahmad']);
      expect(employeeKeys.statistics()).toEqual(['employees', 'statistics']);
    });
  });

  describe('useEmployees', () => {
    it('should fetch employees successfully', async () => {
      vi.mocked(employeesApi.getEmployees).mockResolvedValueOnce({
        data: mockEmployees,
        meta: { current_page: 1, from: 1, last_page: 1, per_page: 10, to: 2, total: 2 },
      });

      const { result } = renderHook(() => useEmployees(), {
        wrapper: createWrapper(),
      });

      expect(result.current.isLoading).toBe(true);

      await waitFor(() => {
        expect(result.current.isSuccess).toBe(true);
      });

      expect(result.current.data?.data).toEqual(mockEmployees);
      expect(employeesApi.getEmployees).toHaveBeenCalledWith(undefined);
    });

    it('should pass filters to API', async () => {
      vi.mocked(employeesApi.getEmployees).mockResolvedValueOnce({
        data: [mockEmployees[0]],
        meta: { current_page: 1, from: 1, last_page: 1, per_page: 10, to: 1, total: 1 },
      });

      const filters = { department: 'IT', status: 'active' as const };

      const { result } = renderHook(() => useEmployees(filters), {
        wrapper: createWrapper(),
      });

      await waitFor(() => {
        expect(result.current.isSuccess).toBe(true);
      });

      expect(employeesApi.getEmployees).toHaveBeenCalledWith(filters);
    });

    it('should handle error', async () => {
      vi.mocked(employeesApi.getEmployees).mockRejectedValueOnce(new Error('Network error'));

      const { result } = renderHook(() => useEmployees(), {
        wrapper: createWrapper(),
      });

      await waitFor(() => {
        expect(result.current.isError).toBe(true);
      });

      expect(result.current.error?.message).toBe('Network error');
    });
  });

  describe('useEmployee', () => {
    it('should fetch single employee', async () => {
      vi.mocked(employeesApi.getEmployee).mockResolvedValueOnce(mockEmployees[0]);

      const { result } = renderHook(() => useEmployee('1'), {
        wrapper: createWrapper(),
      });

      await waitFor(() => {
        expect(result.current.isSuccess).toBe(true);
      });

      expect(result.current.data).toEqual(mockEmployees[0]);
      expect(employeesApi.getEmployee).toHaveBeenCalledWith('1');
    });

    it('should not fetch when id is empty', async () => {
      const { result } = renderHook(() => useEmployee(''), {
        wrapper: createWrapper(),
      });

      expect(result.current.fetchStatus).toBe('idle');
      expect(employeesApi.getEmployee).not.toHaveBeenCalled();
    });
  });

  describe('useEmployeeSearch', () => {
    it('should search employees when query is at least 2 characters', async () => {
      vi.mocked(employeesApi.searchEmployees).mockResolvedValueOnce([mockEmployees[0]]);

      const { result } = renderHook(() => useEmployeeSearch('ahmad'), {
        wrapper: createWrapper(),
      });

      await waitFor(() => {
        expect(result.current.isSuccess).toBe(true);
      });

      expect(result.current.data).toEqual([mockEmployees[0]]);
      expect(employeesApi.searchEmployees).toHaveBeenCalledWith('ahmad');
    });

    it('should not search when query is less than 2 characters', async () => {
      const { result } = renderHook(() => useEmployeeSearch('a'), {
        wrapper: createWrapper(),
      });

      expect(result.current.fetchStatus).toBe('idle');
      expect(employeesApi.searchEmployees).not.toHaveBeenCalled();
    });
  });
});
