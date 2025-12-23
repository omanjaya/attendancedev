import apiClient from './client';
import type { User, LoginCredentials, ApiResponse } from '@/types/auth';

// Auth API endpoints (v1 API)
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

  // Token is now stored in Zustand auth-store using sessionStorage
  // No direct localStorage usage for security (XSS protection)

  return response.data;
}

// Logout user
export async function logout(): Promise<void> {
  try {
    await apiClient.post(AUTH_ENDPOINTS.logout);
  } finally {
    // Clear auth data from sessionStorage (handled by Zustand store)
    sessionStorage.removeItem('auth-storage');
  }
}



// Get current user
export async function getUser(): Promise<User> {
  const response = await apiClient.get<UserResponse>(AUTH_ENDPOINTS.user);
  return response.data.user;
}

// Forgot password
export async function forgotPassword(email: string, turnstileToken?: string): Promise<ApiResponse> {
  const response = await apiClient.post<ApiResponse>(AUTH_ENDPOINTS.forgotPassword, {
    email,
    turnstile_token: turnstileToken,
  });
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
  // Token update is handled by Zustand store
  return response.data;
}

// Check if user is authenticated (reads from sessionStorage)
export function isAuthenticated(): boolean {
  const authStorage = sessionStorage.getItem('auth-storage');
  if (!authStorage) return false;
  try {
    const parsed = JSON.parse(authStorage);
    return !!parsed.state?.token;
  } catch {
    return false;
  }
}

// Get stored user data (reads from sessionStorage)
export function getStoredUser(): User | null {
  const authStorage = sessionStorage.getItem('auth-storage');
  if (!authStorage) return null;
  try {
    const parsed = JSON.parse(authStorage);
    return parsed.state?.user || null;
  } catch {
    return null;
  }
}

// Get token from sessionStorage (for API client)
export function getStoredToken(): string | null {
  const authStorage = sessionStorage.getItem('auth-storage');
  if (!authStorage) return null;
  try {
    const parsed = JSON.parse(authStorage);
    return parsed.state?.token || null;
  } catch {
    return null;
  }
}
