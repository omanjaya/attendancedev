import axios, { type AxiosError, type InternalAxiosRequestConfig } from 'axios';

// API base URL from environment or default to localhost
// Using relative path to leverage Vite proxy in development
const API_URL = '/api/v1';

// Create axios instance
export const apiClient = axios.create({
  baseURL: API_URL,
  withCredentials: true, // Required for Sanctum cookie-based auth
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Request interceptor - Add auth token
apiClient.interceptors.request.use(
  (config: InternalAxiosRequestConfig) => {
    const token = localStorage.getItem('auth_token');
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

      // Clear auth data and redirect to login
      // Clear auth data and redirect to login
      localStorage.removeItem('auth-storage'); // Clear Zustand persist storage
      localStorage.removeItem('auth_token');
      localStorage.removeItem('auth_user');

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
