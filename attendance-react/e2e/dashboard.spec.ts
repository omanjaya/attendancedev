import { test, expect } from '@playwright/test';

test.describe('Dashboard', () => {
  test.beforeEach(async ({ page }) => {
    // Set auth state to simulate logged in user
    await page.goto('/');

    // Store mock auth data
    await page.evaluate(() => {
      const mockUser = {
        id: 1,
        name: 'Admin User',
        email: 'admin@example.com',
        role: 'admin',
        permissions: ['dashboard.view', 'employees.view', 'attendance.view'],
        created_at: '2023-01-01T00:00:00',
        updated_at: '2023-01-01T00:00:00',
      };
      localStorage.setItem('auth_token', 'mock-token-123');
      localStorage.setItem('auth_user', JSON.stringify(mockUser));
      localStorage.setItem('auth-storage', JSON.stringify({
        state: {
          user: mockUser,
          token: 'mock-token-123',
          isAuthenticated: true,
        },
        version: 0,
      }));
    });

    await page.goto('/dashboard');
  });

  test('should display dashboard header', async ({ page }) => {
    await expect(page.getByRole('heading', { name: /dashboard/i })).toBeVisible();
  });

  test('should display stat cards', async ({ page }) => {
    await expect(page.getByText(/total karyawan/i)).toBeVisible();
    await expect(page.getByText(/hadir hari ini/i)).toBeVisible();
  });

  test('should navigate via sidebar', async ({ page }) => {
    // Click on Karyawan (Employees) in sidebar
    await page.getByRole('link', { name: /karyawan/i }).click();
    await expect(page).toHaveURL(/.*employees/);

    // Click on Kehadiran (Attendance) in sidebar
    await page.getByRole('link', { name: /kehadiran/i }).click();
    await expect(page).toHaveURL(/.*attendance/);
  });
});
