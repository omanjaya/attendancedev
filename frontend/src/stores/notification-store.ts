import { create } from 'zustand';
import { persist } from 'zustand/middleware';

export type NotificationType = 'success' | 'error' | 'warning' | 'info';
export type NotificationCategory =
  | 'attendance'
  | 'leave'
  | 'schedule'
  | 'payroll'
  | 'security'
  | 'system';

// Toast notification (temporary)
export interface ToastNotification {
  id: string;
  type: NotificationType;
  title: string;
  message?: string;
  duration?: number;
  dismissible?: boolean;
}

// Persistent notification (notification center)
export interface PersistentNotification {
  id: string;
  type: NotificationType;
  category: NotificationCategory;
  title: string;
  message: string;
  read: boolean;
  created_at: string;
  action_url?: string;
  action_label?: string;
}

interface NotificationState {
  // Toast notifications (temporary)
  toasts: ToastNotification[];
  // Persistent notifications (notification center)
  notifications: PersistentNotification[];
  // UI state
  isNotificationCenterOpen: boolean;

  // Toast actions
  addToast: (notification: Omit<ToastNotification, 'id'>) => string;
  removeToast: (id: string) => void;
  clearAllToasts: () => void;

  // Convenience toast methods
  success: (title: string, message?: string) => string;
  error: (title: string, message?: string) => string;
  warning: (title: string, message?: string) => string;
  info: (title: string, message?: string) => string;

  // Persistent notification actions
  addNotification: (
    notification: Omit<PersistentNotification, 'id' | 'read' | 'created_at'>
  ) => void;
  markAsRead: (id: string) => void;
  markAllAsRead: () => void;
  removeNotification: (id: string) => void;
  clearAllNotifications: () => void;
  clearByCategory: (category: NotificationCategory) => void;

  // UI actions
  setNotificationCenterOpen: (open: boolean) => void;
  toggleNotificationCenter: () => void;

  // Getters
  getUnreadCount: () => number;
  getNotificationsByCategory: (category: NotificationCategory) => PersistentNotification[];
}

// Generate unique ID
const generateId = () => `${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;

export const useNotificationStore = create<NotificationState>()(
  persist(
    (set, get) => ({
      toasts: [],
      notifications: [],
      isNotificationCenterOpen: false,

      // Toast actions
      addToast: (notification) => {
        const id = generateId();
        const newToast: ToastNotification = {
          id,
          duration: 5000,
          dismissible: true,
          ...notification,
        };

        set((state) => ({
          toasts: [...state.toasts, newToast],
        }));

        // Auto-remove after duration
        if (newToast.duration && newToast.duration > 0) {
          setTimeout(() => {
            get().removeToast(id);
          }, newToast.duration);
        }

        return id;
      },

      removeToast: (id) => {
        set((state) => ({
          toasts: state.toasts.filter((n) => n.id !== id),
        }));
      },

      clearAllToasts: () => {
        set({ toasts: [] });
      },

      // Convenience toast methods
      success: (title, message) => {
        return get().addToast({ type: 'success', title, message });
      },

      error: (title, message) => {
        return get().addToast({ type: 'error', title, message, duration: 8000 });
      },

      warning: (title, message) => {
        return get().addToast({ type: 'warning', title, message });
      },

      info: (title, message) => {
        return get().addToast({ type: 'info', title, message });
      },

      // Persistent notification actions
      addNotification: (notification) => {
        const newNotification: PersistentNotification = {
          ...notification,
          id: generateId(),
          read: false,
          created_at: new Date().toISOString(),
        };

        set((state) => ({
          notifications: [newNotification, ...state.notifications].slice(0, 100),
        }));

        // Also show as toast
        get().addToast({
          type: notification.type,
          title: notification.title,
          message: notification.message,
        });
      },

      markAsRead: (id) => {
        set((state) => ({
          notifications: state.notifications.map((n) =>
            n.id === id ? { ...n, read: true } : n
          ),
        }));
      },

      markAllAsRead: () => {
        set((state) => ({
          notifications: state.notifications.map((n) => ({ ...n, read: true })),
        }));
      },

      removeNotification: (id) => {
        set((state) => ({
          notifications: state.notifications.filter((n) => n.id !== id),
        }));
      },

      clearAllNotifications: () => {
        set({ notifications: [] });
      },

      clearByCategory: (category) => {
        set((state) => ({
          notifications: state.notifications.filter((n) => n.category !== category),
        }));
      },

      // UI actions
      setNotificationCenterOpen: (open) => {
        set({ isNotificationCenterOpen: open });
      },

      toggleNotificationCenter: () => {
        set((state) => ({ isNotificationCenterOpen: !state.isNotificationCenterOpen }));
      },

      // Getters
      getUnreadCount: () => {
        return get().notifications.filter((n) => !n.read).length;
      },

      getNotificationsByCategory: (category) => {
        return get().notifications.filter((n) => n.category === category);
      },
    }),
    {
      name: 'notification-storage',
      partialize: (state) => ({
        notifications: state.notifications.slice(0, 50),
      }),
    }
  )
);

// Category labels
export const notificationCategoryLabels: Record<NotificationCategory, string> = {
  attendance: 'Kehadiran',
  leave: 'Cuti',
  schedule: 'Jadwal',
  payroll: 'Payroll',
  security: 'Keamanan',
  system: 'Sistem',
};
