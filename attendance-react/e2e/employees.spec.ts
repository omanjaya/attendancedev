import { test, expect } from '@playwright/test';

test.describe('Employees Page', () => {
  test.beforeEach(async ({ page }) => {
    // Set auth state to simulate logged in user
    await page.goto('/');

    await page.evaluate(() => {
      const mockUser = {
        id: 1,
        name: 'Admin User',
        email: 'admin@example.com',
        role: 'admin',
        permissions: ['dashboard.view', 'employees.view', 'employees.create', 'employees.edit'],
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

    await page.goto('/employees');
  });

  test('should display employees page header', async ({ page }) => {
    await expect(page.getByRole('heading', { name: /karyawan/i })).toBeVisible();
    await expect(page.getByRole('button', { name: /tambah karyawan/i })).toBeVisible();
  });

  test('should display employee data table', async ({ page }) => {
    await expect(page.getByRole('table')).toBeVisible();
    await expect(page.getByText(/nama/i).first()).toBeVisible();
    await expect(page.getByText(/email/i).first()).toBeVisible();
    await expect(page.getByText(/departemen/i).first()).toBeVisible();
  });

  test('should search employees', async ({ page }) => {
    const searchInput = page.getByPlaceholder(/cari/i);
    await searchInput.fill('Ahmad');

    // Wait for filtering
    await page.waitForTimeout(500);
  });

  test('should open add employee dialog', async ({ page }) => {
    await page.getByRole('button', { name: /tambah karyawan/i }).click();

    // Dialog should appear
    await expect(page.getByRole('dialog')).toBeVisible();
    await expect(page.getByText(/tambah karyawan baru/i)).toBeVisible();
  });

  test('should close dialog on cancel', async ({ page }) => {
    await page.getByRole('button', { name: /tambah karyawan/i }).click();
    await expect(page.getByRole('dialog')).toBeVisible();

    // Click cancel or close button
    await page.keyboard.press('Escape');

    // Dialog should close
    await expect(page.getByRole('dialog')).not.toBeVisible();
  });
});
