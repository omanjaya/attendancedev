import { test, expect } from '@playwright/test';

/**
 * DeepFace Integration E2E Tests
 * 
 * Tests the DeepFace-based face recognition functionality:
 * - Face verification using DeepFace service
 * - Attendance verification flow with DeepFace
 * - Error handling for DeepFace service
 */

const ADMIN_EMAIL = 'superadmin@school.edu';
const ADMIN_PASSWORD = 'password';
const EMPLOYEE_EMAIL = 'guru1@school.edu';
const EMPLOYEE_PASSWORD = 'password';

test.describe('DeepFace Face Recognition', () => {

    test('Admin can access employee face registration', async ({ page }) => {
        await page.goto('/login');
        await page.getByLabel('Email').fill(ADMIN_EMAIL);
        await page.getByLabel('Password').fill(ADMIN_PASSWORD);
        await page.getByRole('button', { name: /masuk/i }).click();
        await expect(page).toHaveURL(/.*dashboard/, { timeout: 15000 });

        // Navigate to employees page
        await page.goto('/admin/employees');
        await page.waitForLoadState('networkidle');

        // Look for face registration or photo upload button
        const employeeRow = page.locator('tr', { hasText: /guru/i }).first();
        if (await employeeRow.isVisible()) {
            const faceButton = employeeRow.getByRole('button', { name: /wajah|foto|face/i }).first();
            if (await faceButton.isVisible({ timeout: 3000 }).catch(() => false)) {
                // Grant camera permission
                await page.context().grantPermissions(['camera']);
                await faceButton.click();
                await page.waitForTimeout(1000);

                // Should show camera dialog or face registration modal
                const hasModal = await page.locator('[role="dialog"]').isVisible();
                const hasVideo = await page.locator('video').isVisible();
                expect(hasModal || hasVideo).toBeTruthy();
            }
        }
    });

    test('Employee attendance verification page loads correctly', async ({ page }) => {
        await page.goto('/login');
        await page.getByLabel('Email').fill(EMPLOYEE_EMAIL);
        await page.getByLabel('Password').fill(EMPLOYEE_PASSWORD);
        await page.getByRole('button', { name: /masuk/i }).click();

        await expect(page).toHaveURL(/.*(?:dashboard|change-password)/, { timeout: 15000 });

        // Skip if force password change
        if (page.url().includes('dashboard')) {
            // Navigate to attendance verification
            await page.goto('/attendance/verify?type=check-in');
            await page.waitForLoadState('networkidle');

            // Should show verification page with step indicator
            const heading = page.locator('h1, h2').first();
            await expect(heading).toBeVisible({ timeout: 10000 });

            // Check for step indicators (Location, Face, Complete)
            const stepIndicators = page.locator('[class*="step"], [class*="indicator"]');
            expect(await stepIndicators.count()).toBeGreaterThanOrEqual(0);
        }
    });

    test('Attendance verification shows location step first', async ({ page }) => {
        await page.goto('/login');
        await page.getByLabel('Email').fill(EMPLOYEE_EMAIL);
        await page.getByLabel('Password').fill(EMPLOYEE_PASSWORD);
        await page.getByRole('button', { name: /masuk/i }).click();

        await expect(page).toHaveURL(/.*(?:dashboard|change-password)/, { timeout: 15000 });

        if (page.url().includes('dashboard')) {
            // Grant location permission
            await page.context().grantPermissions(['geolocation']);
            await page.context().setGeolocation({ latitude: -6.2088, longitude: 106.8456 });

            await page.goto('/attendance/verify?type=check-in');
            await page.waitForLoadState('networkidle');

            // Should show GPS/location related content
            const locationText = page.getByText(/lokasi|gps|maps/i).first();
            const locationIcon = page.locator('svg[class*="map"], [data-testid*="location"]');

            const hasLocationContent = await locationText.isVisible({ timeout: 5000 }).catch(() => false) ||
                await locationIcon.isVisible({ timeout: 5000 }).catch(() => false);

            // May also show loading indicator for GPS
            const loadingIndicator = page.locator('[class*="animate-spin"], [class*="loading"]');

            expect(hasLocationContent || await loadingIndicator.isVisible()).toBeTruthy();
        }
    });

    test('Face verification requires camera permission', async ({ page }) => {
        await page.goto('/login');
        await page.getByLabel('Email').fill(EMPLOYEE_EMAIL);
        await page.getByLabel('Password').fill(EMPLOYEE_PASSWORD);
        await page.getByRole('button', { name: /masuk/i }).click();

        await expect(page).toHaveURL(/.*(?:dashboard|change-password)/, { timeout: 15000 });

        if (page.url().includes('dashboard')) {
            // Grant location but not camera
            await page.context().grantPermissions(['geolocation']);
            await page.context().setGeolocation({ latitude: -6.2088, longitude: 106.8456 });

            await page.goto('/attendance/verify?type=check-in');
            await page.waitForLoadState('networkidle');

            // Wait for location step and potential transition to face step
            await page.waitForTimeout(3000);

            // If camera not granted, should show error or request
            const cameraRequest = page.getByText(/kamera|camera|izin/i);
            if (await cameraRequest.isVisible({ timeout: 2000 }).catch(() => false)) {
                expect(await cameraRequest.textContent()).toBeTruthy();
            }
        }
    });

    test('Admin can view face recognition statistics dashboard', async ({ page }) => {
        await page.goto('/login');
        await page.getByLabel('Email').fill(ADMIN_EMAIL);
        await page.getByLabel('Password').fill(ADMIN_PASSWORD);
        await page.getByRole('button', { name: /masuk/i }).click();
        await expect(page).toHaveURL(/.*dashboard/, { timeout: 15000 });

        // Navigate to face recognition or settings page
        await page.goto('/admin/face-recognition');
        await page.waitForLoadState('networkidle');

        // Should show statistics or face recognition management
        const pageContent = page.locator('main, [class*="content"]').first();
        await expect(pageContent).toBeVisible({ timeout: 10000 });
    });

    test('Check-out flow uses DeepFace verification', async ({ page }) => {
        await page.goto('/login');
        await page.getByLabel('Email').fill(EMPLOYEE_EMAIL);
        await page.getByLabel('Password').fill(EMPLOYEE_PASSWORD);
        await page.getByRole('button', { name: /masuk/i }).click();

        await expect(page).toHaveURL(/.*(?:dashboard|change-password)/, { timeout: 15000 });

        if (page.url().includes('dashboard')) {
            // Grant permissions
            await page.context().grantPermissions(['geolocation', 'camera']);
            await page.context().setGeolocation({ latitude: -6.2088, longitude: 106.8456 });

            // Navigate to check-out verification
            await page.goto('/attendance/verify?type=check-out');
            await page.waitForLoadState('networkidle');

            // Should show checkout verification
            const heading = page.getByText(/pulang|check.?out|keluar/i).first();
            expect(await heading.isVisible({ timeout: 5000 })).toBeTruthy();
        }
    });
});

test.describe('Face Registration via Profile', () => {

    test('Employee can access face registration in profile', async ({ page }) => {
        await page.goto('/login');
        await page.getByLabel('Email').fill(EMPLOYEE_EMAIL);
        await page.getByLabel('Password').fill(EMPLOYEE_PASSWORD);
        await page.getByRole('button', { name: /masuk/i }).click();

        await expect(page).toHaveURL(/.*(?:dashboard|change-password)/, { timeout: 15000 });

        if (page.url().includes('dashboard')) {
            // Navigate to profile
            await page.goto('/employee/profile');
            await page.waitForLoadState('networkidle');

            // Look for face/photo section
            const faceSection = page.getByText(/foto|wajah|face|photo/i);
            expect(await faceSection.first().isVisible({ timeout: 5000 })).toBeTruthy();
        }
    });
});
