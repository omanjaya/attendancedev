import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  getServicesStatus,
  restartService,
  startService,
  stopService,
  restartAllServices,
} from '@/lib/api/system';
import { useNotificationStore } from '@/stores';

// Query keys
export const servicesKeys = {
  all: ['services'] as const,
  status: () => [...servicesKeys.all, 'status'] as const,
};

/**
 * Get services status
 * @param autoRefresh - Enable auto-refresh every 5 seconds
 */
export function useServicesStatus(autoRefresh = true) {
  return useQuery({
    queryKey: servicesKeys.status(),
    queryFn: getServicesStatus,
    refetchInterval: autoRefresh ? 5000 : false,
    refetchIntervalInBackground: true,
  });
}

/**
 * Restart a service
 */
export function useRestartService() {
  const queryClient = useQueryClient();
  const { success, error: showError } = useNotificationStore();

  return useMutation({
    mutationFn: restartService,
    onSuccess: (data) => {
      success('Service Restarted', data.message);
      queryClient.invalidateQueries({ queryKey: servicesKeys.status() });
    },
    onError: (error: any) => {
      showError('Error', error.response?.data?.message || 'Failed to restart service');
    },
  });
}

/**
 * Start a service
 */
export function useStartService() {
  const queryClient = useQueryClient();
  const { success, error: showError } = useNotificationStore();

  return useMutation({
    mutationFn: startService,
    onSuccess: (data) => {
      success('Service Started', data.message);
      queryClient.invalidateQueries({ queryKey: servicesKeys.status() });
    },
    onError: (error: any) => {
      showError('Error', error.response?.data?.message || 'Failed to start service');
    },
  });
}

/**
 * Stop a service
 */
export function useStopService() {
  const queryClient = useQueryClient();
  const { success, error: showError } = useNotificationStore();

  return useMutation({
    mutationFn: stopService,
    onSuccess: (data) => {
      success('Service Stopped', data.message);
      queryClient.invalidateQueries({ queryKey: servicesKeys.status() });
    },
    onError: (error: any) => {
      showError('Error', error.response?.data?.message || 'Failed to stop service');
    },
  });
}

/**
 * Restart all services
 */
export function useRestartAllServices() {
  const queryClient = useQueryClient();
  const { success, error: showError } = useNotificationStore();

  return useMutation({
    mutationFn: restartAllServices,
    onSuccess: (data) => {
      success('All Services Restarted', data.message);
      queryClient.invalidateQueries({ queryKey: servicesKeys.status() });
    },
    onError: (error: any) => {
      showError('Error', error.response?.data?.message || 'Failed to restart all services');
    },
  });
}
