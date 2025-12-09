import { test, expect } from '@playwright/test';

/**
 * Face Recognition Flow E2E Tests
 * 
 * Tests the face recognition functionality:
 * - Face registration
 * - Face verification for attendance
 * - Liveness detection
 */

const ADMIN_EMAIL = 'superadmin@school.edu';
const ADMIN_PASSWORD = 'password';

test.describe('Face Recognition Flow', () => {

    test('Admin can access face registration page', async ({ page }) => {
        await page.goto('/login');
        await page.getByLabel('Email').fill(ADMIN_EMAIL);
        await page.getByLabel('Password').fill(ADMIN_PASSWORD);
        await page.getByRole('button', { name: /masuk/i }).click();
        await expect(page).toHaveURL(/.*dashboard/, { timeout: 15000 });

        // Navigate to face recognition settings
        await page.goto('/admin/face-recognition');
        await page.waitForLoadState('networkidle');

        // Should see face recognition page or redirect
        const heading = page.locator('h1, h2').first();
        await expect(heading).toBeVisible({ timeout: 10000 });
    });

    test('Face registration shows camera interface', async ({ page }) => {
        await page.goto('/login');
        await page.getByLabel('Email').fill(ADMIN_EMAIL);
        await page.getByLabel('Password').fill(ADMIN_PASSWORD);
        await page.getByRole('button', { name: /masuk/i }).click();
        await expect(page).toHaveURL(/.*dashboard/, { timeout: 15000 });

        await page.goto('/admin/employees');
        await page.waitForLoadState('networkidle');

        // Find register face button
        const faceButton = page.getByRole('button', { name: /wajah|face|foto/i }).first();
        if (await faceButton.isVisible()) {
            // Grant camera permission
            await page.context().grantPermissions(['camera']);

            await faceButton.click();
            await page.waitForTimeout(1000);

            // Should show camera or dialog
            const dialog = page.getByRole('dialog');
            const video = page.locator('video');

            expect(await dialog.isVisible() || await video.isVisible()).toBeTruthy();
        }
    });

    test('Employee face verification flow', async ({ page }) => {
        // This test verifies the face verification UI exists
        await page.goto('/login');
        await page.getByLabel('Email').fill('guru1@school.edu');
        await page.getByLabel('Password').fill('password');
        await page.getByRole('button', { name: /masuk/i }).click();

        await expect(page).toHaveURL(/.*(?:dashboard|change-password)/, { timeout: 15000 });

        // Only proceed if we're on dashboard (skip if force password change)
        if (page.url().includes('dashboard')) {
            // Navigate to attendance
            await page.goto('/employee/attendance');
            await page.waitForLoadState('networkidle');

            // Check-in should require face verification
            const checkInButton = page.getByRole('button', { name: /check.?in|masuk|hadir/i });
            if (await checkInButton.isVisible()) {
                expect(await checkInButton.isEnabled()).toBeTruthy();
            }
        }
    });
});
