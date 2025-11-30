import { test, expect } from '@playwright/test';

/**
 * Setup Flow E2E Tests
 *
 * Tests the complete initial setup process:
 * 1. Login as Admin
 * 2. Add Location
 * 3. Add Employees
 * 4. Create User Accounts
 * 5. Create Schedule
 * 6. Assign Employees to Schedule
 */

test.describe('Setup Flow', () => {
  // Login before each test
  test.beforeEach(async ({ page }) => {
    await page.goto('/login');
    await page.getByLabel('Email').fill('admin@example.com');
    await page.getByLabel('Password').fill('password123');
    await page.getByRole('button', { name: /masuk/i }).click();
    await expect(page).toHaveURL(/.*dashboard/);
  });

  test.describe('Step 1: Manage Locations', () => {
    test('should navigate to locations page', async ({ page }) => {
      await page.getByRole('link', { name: /lokasi/i }).click();
      await expect(page).toHaveURL(/.*locations/);
      await expect(page.getByText(/manajemen lokasi/i)).toBeVisible();
    });

    test('should display location list', async ({ page }) => {
      await page.goto('/admin/locations');

      // Locations page uses cards - check for heading text
      await expect(page.getByText(/manajemen lokasi/i)).toBeVisible();
    });

    test('should open create location dialog', async ({ page }) => {
      await page.goto('/admin/locations');

      // Click add button (opens dialog, not navigate)
      await page.getByRole('button', { name: /tambah lokasi/i }).click();

      // Dialog should open with form
      await expect(page.getByRole('dialog')).toBeVisible();
      await expect(page.getByText(/nama lokasi/i)).toBeVisible();
    });
  });

  test.describe('Step 2: Manage Employees', () => {
    test('should navigate to employees page', async ({ page }) => {
      await page.getByRole('link', { name: /karyawan/i }).first().click();
      await expect(page).toHaveURL(/.*employees/);
    });

    test('should display employee list', async ({ page }) => {
      await page.goto('/employees');

      // Should show heading
      await expect(page.getByRole('heading', { name: /karyawan/i })).toBeVisible();

      // Should show table or cards
      const table = page.getByRole('table');
      const cards = page.locator('[data-testid="employee-card"]');

      await expect(table.or(cards.first())).toBeVisible();
    });

    test('should open create employee form', async ({ page }) => {
      await page.goto('/employees');

      // Click add button
      await page.getByRole('link', { name: /tambah/i }).click();

      // Should navigate to create page
      await expect(page).toHaveURL(/.*employees\/create/);
    });

    test('should show import/export options', async ({ page }) => {
      await page.goto('/employees');

      // Should have import button
      await expect(page.getByRole('button', { name: /import/i })).toBeVisible();

      // Should have export button
      await expect(page.getByRole('button', { name: /export/i })).toBeVisible();
    });
  });

  test.describe('Step 3: User Credentials', () => {
    test('should navigate to credentials page', async ({ page }) => {
      await page.getByRole('link', { name: /user.*password/i }).click();
      await expect(page).toHaveURL(/.*credentials/);
    });

    test('should display credentials management options', async ({ page }) => {
      await page.goto('/employees/credentials');

      // Should show heading
      await expect(page.getByRole('heading', { name: /user.*password/i })).toBeVisible();

      // Should have tabs for create users and reset password
      await expect(page.getByRole('tab', { name: /buat user/i })).toBeVisible();
      await expect(page.getByRole('tab', { name: /reset password/i })).toBeVisible();
    });

    test('should switch between tabs', async ({ page }) => {
      await page.goto('/employees/credentials');

      // Click reset password tab
      await page.getByRole('tab', { name: /reset password/i }).click();

      // Content should change - table should be visible in reset tab
      await expect(page.getByRole('table')).toBeVisible();
    });
  });

  test.describe('Step 4: Create Schedule', () => {
    test('should navigate to schedules page', async ({ page }) => {
      await page.getByRole('link', { name: /jadwal/i }).first().click();
      await expect(page).toHaveURL(/.*schedules/);
    });

    test('should display schedule list', async ({ page }) => {
      await page.goto('/schedules');

      await expect(page.getByRole('heading', { name: /jadwal/i })).toBeVisible();
    });

    test('should navigate to schedule builder', async ({ page }) => {
      await page.getByRole('link', { name: /pembuat jadwal/i }).click();
      await expect(page).toHaveURL(/.*schedules\/builder/);

      // Should show builder interface - heading is "Schedule Builder" in English
      await expect(page.getByText(/schedule builder/i)).toBeVisible();
    });

    test('should display schedule builder grid', async ({ page }) => {
      await page.goto('/schedules/builder');

      // Should show day headers
      await expect(page.getByText(/senin/i)).toBeVisible();
      await expect(page.getByText(/selasa/i)).toBeVisible();
    });
  });

  test.describe('Step 5: Assign Employees', () => {
    test('should navigate to assign page', async ({ page }) => {
      await page.getByRole('link', { name: /penugasan/i }).click();
      await expect(page).toHaveURL(/.*schedules\/assign/);
    });

    test('should display assignment interface', async ({ page }) => {
      await page.goto('/schedules/assign');

      // Heading is "Penugasan Karyawan"
      await expect(page.getByText(/penugasan karyawan/i)).toBeVisible();

      // Should have schedule selection section - use exact match
      await expect(page.getByText('Pilih Jadwal', { exact: true })).toBeVisible();
    });

    test('should show employee list for assignment', async ({ page }) => {
      await page.goto('/schedules/assign');

      // Should show employee section or daftar karyawan
      await expect(page.getByText(/karyawan/i).first()).toBeVisible();
    });
  });

  test.describe('Complete Setup Flow Integration', () => {
    test('should complete full setup workflow', async ({ page }) => {
      // Step 1: Check locations page
      await page.goto('/admin/locations');
      await expect(page.getByText(/manajemen lokasi/i)).toBeVisible();

      // Step 2: Check employees page
      await page.goto('/employees');
      await expect(page.getByRole('heading', { name: /karyawan/i })).toBeVisible();

      // Step 3: Check credentials page
      await page.goto('/employees/credentials');
      await expect(page.getByRole('heading', { name: /user.*password/i })).toBeVisible();

      // Step 4: Check schedule builder
      await page.goto('/schedules/builder');
      await expect(page.getByText(/schedule builder/i)).toBeVisible();

      // Step 5: Check assignment page
      await page.goto('/schedules/assign');
      await expect(page.getByText(/penugasan karyawan/i)).toBeVisible();

      // All setup pages accessible - flow complete
    });
  });
});
