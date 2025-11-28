import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import type { UserSettings, AppearanceSettings, NotificationSettings, PrivacySettings } from '@/types/settings';
import { defaultSettings } from '@/types/settings';

// Simulated API delay
const delay = (ms: number) => new Promise(resolve => setTimeout(resolve, ms));

// Local storage key
const SETTINGS_KEY = 'user_settings';

// Get settings from local storage or use defaults
function getStoredSettings(): UserSettings {
  if (typeof window === 'undefined') return defaultSettings;
  const stored = localStorage.getItem(SETTINGS_KEY);
  if (stored) {
    try {
      return { ...defaultSettings, ...JSON.parse(stored) };
    } catch {
      return defaultSettings;
    }
  }
  return defaultSettings;
}

// Save settings to local storage
function saveSettings(settings: UserSettings): void {
  if (typeof window !== 'undefined') {
    localStorage.setItem(SETTINGS_KEY, JSON.stringify(settings));
  }
}

export function useSettings() {
  return useQuery({
    queryKey: ['settings'],
    queryFn: async (): Promise<UserSettings> => {
      await delay(200);
      return getStoredSettings();
    },
  });
}

export function useUpdateAppearance() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (data: Partial<AppearanceSettings>): Promise<UserSettings> => {
      await delay(300);
      const current = getStoredSettings();
      const updated = {
        ...current,
        appearance: { ...current.appearance, ...data },
      };
      saveSettings(updated);
      return updated;
    },
    onSuccess: (data) => {
      queryClient.setQueryData(['settings'], data);
      // Apply theme immediately
      if (data.appearance.theme === 'dark') {
        document.documentElement.classList.add('dark');
      } else if (data.appearance.theme === 'light') {
        document.documentElement.classList.remove('dark');
      } else {
        // System preference
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        document.documentElement.classList.toggle('dark', prefersDark);
      }
    },
  });
}

export function useUpdateNotifications() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (data: Partial<NotificationSettings>): Promise<UserSettings> => {
      await delay(300);
      const current = getStoredSettings();
      const updated = {
        ...current,
        notifications: { ...current.notifications, ...data },
      };
      saveSettings(updated);
      return updated;
    },
    onSuccess: (data) => {
      queryClient.setQueryData(['settings'], data);
    },
  });
}

export function useUpdatePrivacy() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (data: Partial<PrivacySettings>): Promise<UserSettings> => {
      await delay(300);
      const current = getStoredSettings();
      const updated = {
        ...current,
        privacy: { ...current.privacy, ...data },
      };
      saveSettings(updated);
      return updated;
    },
    onSuccess: (data) => {
      queryClient.setQueryData(['settings'], data);
    },
  });
}

export function useResetSettings() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (): Promise<UserSettings> => {
      await delay(500);
      saveSettings(defaultSettings);
      return defaultSettings;
    },
    onSuccess: (data) => {
      queryClient.setQueryData(['settings'], data);
      // Reset theme
      const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
      document.documentElement.classList.toggle('dark', prefersDark);
    },
  });
}

export function useExportSettings() {
  return useMutation({
    mutationFn: async (): Promise<string> => {
      await delay(200);
      const settings = getStoredSettings();
      return JSON.stringify(settings, null, 2);
    },
  });
}

export function useImportSettings() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (jsonString: string): Promise<UserSettings> => {
      await delay(300);
      const imported = JSON.parse(jsonString) as Partial<UserSettings>;
      const merged = {
        appearance: { ...defaultSettings.appearance, ...imported.appearance },
        notifications: { ...defaultSettings.notifications, ...imported.notifications },
        privacy: { ...defaultSettings.privacy, ...imported.privacy },
      };
      saveSettings(merged);
      return merged;
    },
    onSuccess: (data) => {
      queryClient.setQueryData(['settings'], data);
    },
  });
}
