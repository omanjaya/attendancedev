import axios, { type AxiosError, type InternalAxiosRequestConfig } from 'axios';
import { getStoredToken } from './auth';

// API base URL from environment or default to localhost
// Using relative path to leverage Vite proxy in development
const API_URL = '/api/v1';

// TanStack Query default options for performance optimization
export const defaultQueryOptions = {
  staleTime: 5 * 60 * 1000, // 5 minutes - data is considered fresh
  gcTime: 10 * 60 * 1000,   // 10 minutes - cache time (formerly cacheTime)
  retry: 1,                  // Only retry once on failure
  refetchOnWindowFocus: false, // Don't refetch when window regains focus
};

// Create axios instance
export const apiClient = axios.create({
  baseURL: API_URL,
  withCredentials: true, // Required for Sanctum cookie-based auth
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Request interceptor - Add auth token from sessionStorage
apiClient.interceptors.request.use(
  (config: InternalAxiosRequestConfig) => {
    const token = getStoredToken();
    if (token && config.headers) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error: AxiosError) => {
    return Promise.reject(error);
  }
);

// Response interceptor - Handle errors
apiClient.interceptors.response.use(
  (response) => response,
  async (error: AxiosError) => {
    const originalRequest = error.config as InternalAxiosRequestConfig & { _retry?: boolean };

    // Handle 401 Unauthorized
    if (error.response?.status === 401 && !originalRequest._retry) {
      originalRequest._retry = true;

      // Clear auth data from sessionStorage
      sessionStorage.removeItem('auth-storage');

      // Only redirect if not already on login page
      if (window.location.pathname !== '/login') {
        window.location.href = '/login';
      }
    }

    // All other errors handled by global error handler in QueryClient
    return Promise.reject(error);
  }
);

export default apiClient;
