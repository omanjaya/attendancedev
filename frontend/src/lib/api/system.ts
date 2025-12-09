import apiClient from './client';

export interface ServiceStatus {
  key: string;
  name: string;
  container: string;
  icon: string;
  description: string;
  status: 'running' | 'stopped' | 'unknown';
  health: 'healthy' | 'down' | 'unknown';
  uptime: number | null;
  cpu: number | null;
  memory: string | null;
  memory_percent: number | null;
  restart_count: number;
}

export interface SystemInfo {
  docker_version: string;
  compose_version: string;
  disk_usage: string;
  running_containers: number;
  total_containers: number;
}

export interface ServicesResponse {
  success: boolean;
  services: Record<string, ServiceStatus>;
  system: SystemInfo;
  timestamp: string;
}

export interface ServiceResponse {
  success: boolean;
  service: ServiceStatus;
}

export interface ActionResponse {
  success: boolean;
  message: string;
  output?: string;
}

export interface LogsResponse {
  success: boolean;
  logs: string;
  lines: number;
}

// Get all services status
export async function getServicesStatus(): Promise<ServicesResponse> {
  const response = await apiClient.get<ServicesResponse>('/admin/services');
  return response.data;
}

// Get single service status
export async function getServiceStatus(service: string): Promise<ServiceResponse> {
  const response = await apiClient.get<ServiceResponse>(`/admin/services/${service}`);
  return response.data;
}

// Restart a service
export async function restartService(service: string): Promise<ActionResponse> {
  const response = await apiClient.post<ActionResponse>(`/admin/services/${service}/restart`);
  return response.data;
}

// Start a service
export async function startService(service: string): Promise<ActionResponse> {
  const response = await apiClient.post<ActionResponse>(`/admin/services/${service}/start`);
  return response.data;
}

// Stop a service
export async function stopService(service: string): Promise<ActionResponse> {
  const response = await apiClient.post<ActionResponse>(`/admin/services/${service}/stop`);
  return response.data;
}

// Get service logs
export async function getServiceLogs(service: string, lines: number = 100): Promise<LogsResponse> {
  const response = await apiClient.get<LogsResponse>(`/admin/services/${service}/logs`, {
    params: { lines },
  });
  return response.data;
}

// Get service metrics
export async function getServiceMetrics(service: string): Promise<any> {
  const response = await apiClient.get(`/admin/services/${service}/metrics`);
  return response.data;
}

// Restart all services
export async function restartAllServices(): Promise<ActionResponse> {
  const response = await apiClient.post<ActionResponse>('/admin/services/restart-all');
  return response.data;
}
