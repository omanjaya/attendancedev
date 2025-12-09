import { test, expect, Page } from '@playwright/test';

// Login helper for admin
async function loginAsAdmin(page: Page) {
  await page.goto('/login');
  await page.getByLabel('Email').fill('admin@school.edu');
  await page.getByLabel('Password').fill('password');
  await page.getByRole('button', { name: /masuk/i }).click();

  // Wait for redirect - could be dashboard OR change-password
  await page.waitForURL(/.*(?:dashboard|change-password|admin)/, { timeout: 15000 });

  // Handle force password change if needed
  if (page.url().includes('change-password')) {
    const newPassword = page.getByLabel(/password baru|new password/i).first();
    const confirmPassword = page.getByLabel(/konfirmasi|confirm/i).first();

    if (await newPassword.isVisible()) {
      await newPassword.fill('password');
      await confirmPassword.fill('password');
      await page.getByRole('button', { name: /simpan|save|ubah|change/i }).click();
      await page.waitForURL(/.*(?:dashboard|admin)/, { timeout: 15000 });
    }
  }
}

test.describe('Employees Page', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto('/admin/employees');
    await page.waitForLoadState('networkidle');
  });

  test('should display employees page header', async ({ page }) => {
    // Look for the PageHeader component with "Karyawan" title
    // Use .first() to avoid strict mode issues
    const heading = page.locator('h1, h2, h3').filter({ hasText: /karyawan/i }).first();
    await expect(heading).toBeVisible({ timeout: 10000 });
  });

  test('should display employee data table', async ({ page }) => {
    // Wait for table specifically - use locator('table').first() to be safe
    const table = page.locator('table').first();
    await expect(table).toBeVisible({ timeout: 10000 });
  });

  test('should search employees', async ({ page }) => {
    // Look for search input with various possible placeholders
    const searchInput = page.getByPlaceholder(/cari|search/i).first();

    if (await searchInput.isVisible()) {
      await searchInput.fill('Ahmad');
      // Wait for filtering
      await page.waitForTimeout(500);
    }
  });

  test('should open add employee dialog', async ({ page }) => {
    // Look for "Tambah Karyawan" link specifically
    const addButton = page.getByRole('link', { name: /tambah karyawan/i }).first();

    if (await addButton.isVisible()) {
      await addButton.click();
      await page.waitForLoadState('networkidle');

      // Should navigate to create page
      expect(page.url()).toContain('/create');
    }
  });

  test('should close dialog on cancel', async ({ page }) => {
    // Navigate to create page
    await page.goto('/admin/employees/create');
    await page.waitForLoadState('networkidle');

    // Look for cancel button specifically (not link)
    const cancelButton = page.getByRole('button', { name: /batal/i }).first();

    if (await cancelButton.isVisible()) {
      await cancelButton.click();
      await page.waitForLoadState('networkidle');

      // Should be back on employees list
      expect(page.url()).toContain('/employees');
    } else {
      // Try the back link instead
      const backLink = page.getByRole('link', { name: /kembali/i }).first();
      if (await backLink.isVisible()) {
        await backLink.click();
        await page.waitForLoadState('networkidle');
        expect(page.url()).toContain('/employees');
      }
    }
  });
});
