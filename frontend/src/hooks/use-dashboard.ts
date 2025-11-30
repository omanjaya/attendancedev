import { useQuery } from '@tanstack/react-query';
import { getDashboardData, getMockDashboardData } from '@/lib/api/dashboard';

// Query keys
export const dashboardKeys = {
  all: ['dashboard'] as const,
  data: () => [...dashboardKeys.all, 'data'] as const,
};

// Get dashboard data
export function useDashboard() {
  return useQuery({
    queryKey: dashboardKeys.data(),
    queryFn: getDashboardData,
    refetchInterval: 5 * 60 * 1000, // Refetch every 5 minutes
    retry: 1,
  });
}

// Use mock data for development
export function useMockDashboard() {
  return useQuery({
    queryKey: [...dashboardKeys.data(), 'mock'],
    queryFn: () => Promise.resolve(getMockDashboardData()),
    staleTime: Infinity,
  });
}
