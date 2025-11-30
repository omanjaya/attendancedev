import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import type { UserProfile, ProfileUpdateData, PasswordChangeData, ProfileStatistics } from '@/types/profile';

// Mock data
const mockProfile: UserProfile = {
  id: '1',
  name: 'Ahmad Fauzi',
  email: 'ahmad.fauzi@school.edu',
  phone: '081234567890',
  avatar: null,
  role: 'Guru',
  department: 'Matematika',
  position: 'Guru Senior',
  employee_id: 'EMP-001',
  joined_at: '2020-03-15',
  email_verified_at: '2020-03-15T10:00:00Z',
  two_factor_enabled: true,
  created_at: '2020-03-15T08:00:00Z',
  updated_at: '2024-01-10T14:30:00Z',
};

const mockStatistics: ProfileStatistics = {
  total_attendance: 245,
  present_days: 230,
  late_days: 10,
  absent_days: 5,
  leave_days: 12,
  overtime_hours: 45,
  current_month_attendance_rate: 96.5,
};

// Simulated API delay
const delay = (ms: number) => new Promise(resolve => setTimeout(resolve, ms));

export function useProfile() {
  return useQuery({
    queryKey: ['profile'],
    queryFn: async (): Promise<UserProfile> => {
      await delay(500);
      return mockProfile;
    },
  });
}

export function useProfileStatistics() {
  return useQuery({
    queryKey: ['profile-statistics'],
    queryFn: async (): Promise<ProfileStatistics> => {
      await delay(300);
      return mockStatistics;
    },
  });
}

export function useUpdateProfile() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (data: ProfileUpdateData): Promise<UserProfile> => {
      await delay(800);
      console.log('Updating profile:', data);
      return { ...mockProfile, ...data };
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['profile'] });
    },
  });
}

export function useChangePassword() {
  return useMutation({
    mutationFn: async (data: PasswordChangeData): Promise<{ success: boolean }> => {
      await delay(1000);
      console.log('Changing password:', data);
      // Simulate validation
      if (data.current_password !== 'password123') {
        throw new Error('Password saat ini tidak valid');
      }
      if (data.password !== data.password_confirmation) {
        throw new Error('Konfirmasi password tidak cocok');
      }
      return { success: true };
    },
  });
}

export function useUploadAvatar() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (file: File): Promise<{ avatar_url: string }> => {
      await delay(1500);
      console.log('Uploading avatar:', file.name);
      // Simulate URL generation
      const avatarUrl = URL.createObjectURL(file);
      return { avatar_url: avatarUrl };
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
      await delay(500);
      console.log('Deleting avatar');
      return { success: true };
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['profile'] });
    },
  });
}

export function useDeleteAccount() {
  return useMutation({
    mutationFn: async (password: string): Promise<{ success: boolean }> => {
      await delay(1000);
      console.log('Deleting account with password verification');
      if (password !== 'password123') {
        throw new Error('Password tidak valid');
      }
      return { success: true };
    },
  });
}
