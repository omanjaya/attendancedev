// Settings Types

export type Theme = 'light' | 'dark' | 'system';
export type Language = 'id' | 'en';
export type DateFormat = 'DD/MM/YYYY' | 'MM/DD/YYYY' | 'YYYY-MM-DD';
export type TimeFormat = '12h' | '24h';

export interface AppearanceSettings {
  theme: Theme;
  language: Language;
  date_format: DateFormat;
  time_format: TimeFormat;
  compact_mode: boolean;
  animations_enabled: boolean;
}

export interface NotificationSettings {
  email_notifications: boolean;
  push_notifications: boolean;
  attendance_reminders: boolean;
  leave_updates: boolean;
  payroll_notifications: boolean;
  schedule_changes: boolean;
  security_alerts: boolean;
  marketing_emails: boolean;
}

export interface PrivacySettings {
  show_profile_to_colleagues: boolean;
  show_attendance_to_manager: boolean;
  allow_location_tracking: boolean;
  share_face_data: boolean;
}

export interface UserSettings {
  appearance: AppearanceSettings;
  notifications: NotificationSettings;
  privacy: PrivacySettings;
}

export const defaultSettings: UserSettings = {
  appearance: {
    theme: 'system',
    language: 'id',
    date_format: 'DD/MM/YYYY',
    time_format: '24h',
    compact_mode: false,
    animations_enabled: true,
  },
  notifications: {
    email_notifications: true,
    push_notifications: true,
    attendance_reminders: true,
    leave_updates: true,
    payroll_notifications: true,
    schedule_changes: true,
    security_alerts: true,
    marketing_emails: false,
  },
  privacy: {
    show_profile_to_colleagues: true,
    show_attendance_to_manager: true,
    allow_location_tracking: true,
    share_face_data: false,
  },
};

export const themeLabels: Record<Theme, string> = {
  light: 'Terang',
  dark: 'Gelap',
  system: 'Ikuti Sistem',
};

export const languageLabels: Record<Language, string> = {
  id: 'Bahasa Indonesia',
  en: 'English',
};

export const dateFormatLabels: Record<DateFormat, string> = {
  'DD/MM/YYYY': '31/12/2024',
  'MM/DD/YYYY': '12/31/2024',
  'YYYY-MM-DD': '2024-12-31',
};

export const timeFormatLabels: Record<TimeFormat, string> = {
  '12h': '12 Jam (AM/PM)',
  '24h': '24 Jam',
};
