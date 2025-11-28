import { describe, it, expect, beforeEach, vi } from 'vitest';
import { useUIStore } from './ui-store';

describe('UI Store', () => {
  beforeEach(() => {
    // Reset the store state
    useUIStore.setState({
      sidebarOpen: true,
      sidebarCollapsed: false,
      theme: 'light',
      activeModal: null,
      modalData: {},
      globalLoading: false,
      mobileMenuOpen: false,
    });
    vi.clearAllMocks();
  });

  describe('Initial State', () => {
    it('should have correct default state', () => {
      const state = useUIStore.getState();

      expect(state.sidebarOpen).toBe(true);
      expect(state.sidebarCollapsed).toBe(false);
      expect(state.activeModal).toBeNull();
      expect(state.modalData).toEqual({});
      expect(state.globalLoading).toBe(false);
      expect(state.mobileMenuOpen).toBe(false);
    });
  });

  describe('Sidebar Actions', () => {
    it('should toggle sidebar', () => {
      useUIStore.getState().toggleSidebar();
      expect(useUIStore.getState().sidebarOpen).toBe(false);

      useUIStore.getState().toggleSidebar();
      expect(useUIStore.getState().sidebarOpen).toBe(true);
    });

    it('should set sidebar open state', () => {
      useUIStore.getState().setSidebarOpen(false);
      expect(useUIStore.getState().sidebarOpen).toBe(false);

      useUIStore.getState().setSidebarOpen(true);
      expect(useUIStore.getState().sidebarOpen).toBe(true);
    });

    it('should set sidebar collapsed state', () => {
      useUIStore.getState().setSidebarCollapsed(true);
      expect(useUIStore.getState().sidebarCollapsed).toBe(true);

      useUIStore.getState().setSidebarCollapsed(false);
      expect(useUIStore.getState().sidebarCollapsed).toBe(false);
    });
  });

  describe('Theme Actions', () => {
    it('should set theme', () => {
      useUIStore.getState().setTheme('dark');
      expect(useUIStore.getState().theme).toBe('dark');

      useUIStore.getState().setTheme('light');
      expect(useUIStore.getState().theme).toBe('light');

      useUIStore.getState().setTheme('system');
      expect(useUIStore.getState().theme).toBe('system');
    });

    it('should toggle between light and dark', () => {
      useUIStore.setState({ theme: 'light' });

      useUIStore.getState().toggleTheme();
      expect(useUIStore.getState().theme).toBe('dark');

      useUIStore.getState().toggleTheme();
      expect(useUIStore.getState().theme).toBe('light');
    });
  });

  describe('Modal Actions', () => {
    it('should open modal with id', () => {
      useUIStore.getState().openModal('confirm-delete');

      expect(useUIStore.getState().activeModal).toBe('confirm-delete');
      expect(useUIStore.getState().modalData).toEqual({});
    });

    it('should open modal with data', () => {
      const data = { employeeId: 1, name: 'Test' };
      useUIStore.getState().openModal('edit-employee', data);

      expect(useUIStore.getState().activeModal).toBe('edit-employee');
      expect(useUIStore.getState().modalData).toEqual(data);
    });

    it('should close modal and clear data', () => {
      useUIStore.getState().openModal('test-modal', { test: 'data' });
      useUIStore.getState().closeModal();

      expect(useUIStore.getState().activeModal).toBeNull();
      expect(useUIStore.getState().modalData).toEqual({});
    });
  });

  describe('Loading State', () => {
    it('should set global loading', () => {
      useUIStore.getState().setGlobalLoading(true);
      expect(useUIStore.getState().globalLoading).toBe(true);

      useUIStore.getState().setGlobalLoading(false);
      expect(useUIStore.getState().globalLoading).toBe(false);
    });
  });

  describe('Mobile Menu Actions', () => {
    it('should toggle mobile menu', () => {
      useUIStore.getState().toggleMobileMenu();
      expect(useUIStore.getState().mobileMenuOpen).toBe(true);

      useUIStore.getState().toggleMobileMenu();
      expect(useUIStore.getState().mobileMenuOpen).toBe(false);
    });

    it('should set mobile menu open state', () => {
      useUIStore.getState().setMobileMenuOpen(true);
      expect(useUIStore.getState().mobileMenuOpen).toBe(true);

      useUIStore.getState().setMobileMenuOpen(false);
      expect(useUIStore.getState().mobileMenuOpen).toBe(false);
    });
  });
});
