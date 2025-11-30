import { create } from 'zustand';
import { persist } from 'zustand/middleware';

type Theme = 'light' | 'dark' | 'system';

interface UIState {
  // Sidebar
  sidebarOpen: boolean;
  sidebarCollapsed: boolean;

  // Theme
  theme: Theme;

  // Modals
  activeModal: string | null;
  modalData: Record<string, unknown>;

  // Loading states
  globalLoading: boolean;

  // Mobile menu
  mobileMenuOpen: boolean;

  // Actions
  toggleSidebar: () => void;
  setSidebarOpen: (open: boolean) => void;
  setSidebarCollapsed: (collapsed: boolean) => void;
  setTheme: (theme: Theme) => void;
  toggleTheme: () => void;
  openModal: (modalId: string, data?: Record<string, unknown>) => void;
  closeModal: () => void;
  setGlobalLoading: (loading: boolean) => void;
  toggleMobileMenu: () => void;
  setMobileMenuOpen: (open: boolean) => void;
}

// Get initial theme from system preference
const getInitialTheme = (): Theme => {
  if (typeof window !== 'undefined') {
    const stored = localStorage.getItem('theme') as Theme | null;
    if (stored) return stored;

    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  }
  return 'light';
};

// Apply theme to document
const applyTheme = (theme: Theme) => {
  if (typeof window === 'undefined') return;

  const root = document.documentElement;
  const isDark =
    theme === 'dark' ||
    (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);

  if (isDark) {
    root.classList.add('dark');
  } else {
    root.classList.remove('dark');
  }
};

export const useUIStore = create<UIState>()(
  persist(
    (set, get) => ({
      // Initial state
      sidebarOpen: true,
      sidebarCollapsed: false,
      theme: getInitialTheme(),
      activeModal: null,
      modalData: {},
      globalLoading: false,
      mobileMenuOpen: false,

      // Sidebar actions
      toggleSidebar: () => {
        set((state) => ({ sidebarOpen: !state.sidebarOpen }));
      },

      setSidebarOpen: (open: boolean) => {
        set({ sidebarOpen: open });
      },

      setSidebarCollapsed: (collapsed: boolean) => {
        set({ sidebarCollapsed: collapsed });
      },

      // Theme actions
      setTheme: (theme: Theme) => {
        applyTheme(theme);
        set({ theme });
      },

      toggleTheme: () => {
        const { theme } = get();
        const newTheme = theme === 'dark' ? 'light' : 'dark';
        applyTheme(newTheme);
        set({ theme: newTheme });
      },

      // Modal actions
      openModal: (modalId: string, data: Record<string, unknown> = {}) => {
        set({ activeModal: modalId, modalData: data });
      },

      closeModal: () => {
        set({ activeModal: null, modalData: {} });
      },

      // Loading actions
      setGlobalLoading: (loading: boolean) => {
        set({ globalLoading: loading });
      },

      // Mobile menu actions
      toggleMobileMenu: () => {
        set((state) => ({ mobileMenuOpen: !state.mobileMenuOpen }));
      },

      setMobileMenuOpen: (open: boolean) => {
        set({ mobileMenuOpen: open });
      },
    }),
    {
      name: 'ui-storage',
      partialize: (state) => ({
        sidebarCollapsed: state.sidebarCollapsed,
        theme: state.theme,
      }),
      onRehydrateStorage: () => (state) => {
        // Apply theme on rehydration
        if (state?.theme) {
          applyTheme(state.theme);
        }
      },
    }
  )
);
