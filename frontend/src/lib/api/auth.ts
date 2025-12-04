import apiClient from './client';
import type { User, LoginCredentials, ApiResponse } from '@/types/auth';

// Auth API endpoints
const AUTH_ENDPOINTS = {
  login: 'auth/login',
  logout: 'auth/logout',
  user: 'auth/user',
  forgotPassword: 'auth/forgot-password',
  resetPassword: 'auth/reset-password',
  refresh: 'auth/refresh',
} as const;

export interface LoginResponse {
  user: User;
  token: string;
  message: string;
}

export interface UserResponse {
  user: User;
}

// Login user
export async function login(credentials: LoginCredentials): Promise<LoginResponse> {
  const response = await apiClient.post<LoginResponse>(AUTH_ENDPOINTS.login, { ...credentials, device_name: 'web' });

  // Store token in localStorage
  if (response.data.token) {
    localStorage.setItem('auth_token', response.data.token);
    localStorage.setItem('auth_user', JSON.stringify(response.data.user));
  }

  return response.data;
}

// Logout user
export async function logout(): Promise<void> {
  try {
    await apiClient.post(AUTH_ENDPOINTS.logout);
  } finally {
    // Always clear local storage, even if API call fails
    localStorage.removeItem('auth_token');
    localStorage.removeItem('auth_user');
  }
}



// Get current user
export async function getUser(): Promise<User> {
  const response = await apiClient.get<UserResponse>(AUTH_ENDPOINTS.user);
  return response.data.user;
}

// Forgot password
export async function forgotPassword(email: string): Promise<ApiResponse> {
  const response = await apiClient.post<ApiResponse>(AUTH_ENDPOINTS.forgotPassword, { email });
  return response.data;
}

// Reset password
export async function resetPassword(data: {
  token: string;
  email: string;
  password: string;
  password_confirmation: string;
}): Promise<ApiResponse> {
  const response = await apiClient.post<ApiResponse>(AUTH_ENDPOINTS.resetPassword, data);
  return response.data;
}

// Refresh token
export async function refreshToken(): Promise<{ token: string }> {
  const response = await apiClient.post<{ token: string }>(AUTH_ENDPOINTS.refresh);

  if (response.data.token) {
    localStorage.setItem('auth_token', response.data.token);
  }

  return response.data;
}

// Check if user is authenticated
export function isAuthenticated(): boolean {
  return !!localStorage.getItem('auth_token');
}

// Get stored user data
export function getStoredUser(): User | null {
  const userData = localStorage.getItem('auth_user');
  return userData ? JSON.parse(userData) : null;
}
