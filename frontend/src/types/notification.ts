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
  info: 'bg-primary/10 text-primary',
  success: 'bg-success/10 text-success',
  warning: 'bg-warning/10 text-warning',
  error: 'bg-destructive/10 text-destructive',
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
