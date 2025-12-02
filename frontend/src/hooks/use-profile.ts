import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import type { UserProfile, ProfileUpdateData, PasswordChangeData, ProfileStatistics } from '@/types/profile';
import { getUser } from '@/lib/api/auth';
import apiClient from '@/lib/api/client';

// Transform User to UserProfile
function transformUserToProfile(user: any): UserProfile {
  return {
    id: user.id,
    name: user.name,
    email: user.email,
    phone: user.phone || null,
    avatar: user.avatar || null,
    role: user.roles?.[0]?.name || 'Employee',
    department: user.employee?.metadata?.department || null,
    position: user.employee?.metadata?.position || null,
    employee_id: user.employee?.employee_id || null,
    employee_pk: user.employee?.id || null,
    joined_at: user.employee?.hire_date || user.created_at,
    email_verified_at: user.email_verified_at,
    two_factor_enabled: user.two_factor_enabled || false,
    created_at: user.created_at,
    updated_at: user.updated_at,
  };
}

export function useProfile() {
  return useQuery({
    queryKey: ['profile'],
    queryFn: async (): Promise<UserProfile> => {
      // Use /auth/me endpoint which returns current user
      const response = await apiClient.get<{ success: boolean; data: any }>('auth/me');
      const user = response.data.data;
      return transformUserToProfile(user);
    },
  });
}

export function useProfileStatistics() {
  return useQuery({
    queryKey: ['profile-statistics'],
    queryFn: async (): Promise<ProfileStatistics> => {
      const response = await apiClient.get<{ success: boolean; data: any }>('/attendance/statistics');
      const data = response.data.data;

      return {
        total_attendance: data.total_records || 0,
        present_days: data.present_count || 0,
        late_days: data.late_count || 0,
        absent_days: data.absent_count || 0,
        leave_days: data.on_leave_count || 0,
        overtime_hours: data.overtime_hours || 0,
        current_month_attendance_rate: data.attendance_rate || 0,
      };
    },
  });
}

export function useUpdateProfile() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (data: ProfileUpdateData): Promise<UserProfile> => {
      const response = await apiClient.put<{ success: boolean; data: any }>('user', data);
      return transformUserToProfile(response.data.data);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['profile'] });
    },
  });
}

export function useChangePassword() {
  return useMutation({
    mutationFn: async (data: PasswordChangeData): Promise<{ success: boolean }> => {
      const response = await apiClient.post<{ success: boolean }>('/auth/change-password', data);
      return response.data;
    },
  });
}

export function useUploadAvatar() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (file: File): Promise<{ avatar_url: string }> => {
      const formData = new FormData();
      formData.append('avatar', file);

      const response = await apiClient.post<{ success: boolean; data: { avatar_url: string } }>(
        'auth/avatar',
        formData,
        {
          headers: {
            'Content-Type': 'multipart/form-data',
          },
        }
      );
      return response.data.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['profile'] });
    },
  });
}

export function useDeleteAvatar() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (): Promise<{ success: boolean }> => {
      const response = await apiClient.delete<{ success: boolean }>('auth/avatar');
      return response.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['profile'] });
    },
  });
}

export function useDeleteAccount() {
  return useMutation({
    mutationFn: async (password: string): Promise<{ success: boolean }> => {
      const response = await apiClient.post<{ success: boolean }>('auth/delete-account', { password });
      return response.data;
    },
  });
}
