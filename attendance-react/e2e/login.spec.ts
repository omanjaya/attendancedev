import { test, expect } from '@playwright/test';

test.describe('Login Page', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/login');
  });

  test('should display login form', async ({ page }) => {
    await expect(page.getByRole('heading', { name: /attendance system/i })).toBeVisible();
    await expect(page.getByLabel('Email')).toBeVisible();
    await expect(page.getByLabel('Password')).toBeVisible();
    await expect(page.getByRole('button', { name: /masuk/i })).toBeVisible();
  });

  test('should show validation errors for empty form', async ({ page }) => {
    await page.getByRole('button', { name: /masuk/i }).click();

    // Form should not submit without data
    await expect(page).toHaveURL('/login');
  });

  test('should toggle password visibility', async ({ page }) => {
    const passwordInput = page.getByLabel('Password');

    // Initially should be password type
    await expect(passwordInput).toHaveAttribute('type', 'password');

    // Click toggle button
    await page.locator('button[type="button"]').first().click();

    // Should now be text type
    await expect(passwordInput).toHaveAttribute('type', 'text');
  });

  test('should navigate to dashboard on successful login', async ({ page }) => {
    // Fill in valid credentials (using mock data)
    await page.getByLabel('Email').fill('admin@example.com');
    await page.getByLabel('Password').fill('password123');
    await page.getByRole('button', { name: /masuk/i }).click();

    // Should redirect to dashboard
    await expect(page).toHaveURL(/.*dashboard/);
  });
});
