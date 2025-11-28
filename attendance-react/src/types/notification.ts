// Notification Types

export type NotificationType = 'info' | 'success' | 'warning' | 'error';
export type NotificationCategory =
  | 'attendance'
  | 'leave'
  | 'schedule'
  | 'payroll'
  | 'security'
  | 'system';

export interface Notification {
  id: string;
  type: NotificationType;
  category: NotificationCategory;
  title: string;
  message: string;
  read: boolean;
  created_at: string;
  action_url?: string;
  action_label?: string;
  metadata?: Record<string, unknown>;
}

export interface NotificationGroup {
  date: string;
  notifications: Notification[];
}

export interface NotificationPreferences {
  enabled: boolean;
  sound: boolean;
  desktop: boolean;
  categories: {
    [key in NotificationCategory]: boolean;
  };
}

export interface NotificationStats {
  total: number;
  unread: number;
  by_category: Record<NotificationCategory, number>;
}

export const notificationTypeColors: Record<NotificationType, string> = {
  info: 'bg-blue-100 text-blue-600 dark:bg-blue-900/30',
  success: 'bg-green-100 text-green-600 dark:bg-green-900/30',
  warning: 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/30',
  error: 'bg-red-100 text-red-600 dark:bg-red-900/30',
};

export const notificationCategoryLabels: Record<NotificationCategory, string> = {
  attendance: 'Kehadiran',
  leave: 'Cuti',
  schedule: 'Jadwal',
  payroll: 'Payroll',
  security: 'Keamanan',
  system: 'Sistem',
};

export const notificationCategoryIcons: Record<NotificationCategory, string> = {
  attendance: 'Clock',
  leave: 'Calendar',
  schedule: 'CalendarDays',
  payroll: 'DollarSign',
  security: 'Shield',
  system: 'Bell',
};
