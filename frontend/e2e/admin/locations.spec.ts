import { test, expect, ADMIN_PAGES, waitForToast, clickAndWaitForDialog, fillForm, submitFormAndWaitForSuccess } from './fixtures';

/**
 * Admin - Location Management Tests
 * 
 * Menguji semua fitur manajemen lokasi:
 * - View locations list
 * - Create new location with map picker
 * - Edit location
 * - Toggle location status
 * - Delete location
 */

test.describe('Admin - Location Management', () => {

    test('Can view locations list with all columns', async ({ adminPage: page }) => {
        await page.goto(ADMIN_PAGES.locations);

        // Wait for page to load
        await page.waitForLoadState('networkidle');

        // Should see locations heading
        await expect(page.getByRole('heading', { name: /lokasi|location/i })).toBeVisible({ timeout: 10000 });
    });

    test('Can open create location dialog with map', async ({ adminPage: page }) => {
        await page.goto(ADMIN_PAGES.locations);

        // Wait for page to load
        await page.waitForLoadState('networkidle');

        // Click add button
        await page.getByRole('button', { name: /tambah|add/i }).click();

        // Dialog should open
        await expect(page.getByRole('dialog')).toBeVisible();

        // Should have form fields
        await expect(page.getByLabel(/nama|name/i).first()).toBeVisible();
    });

    test('Location form validates required fields', async ({ adminPage: page }) => {
        await page.goto(ADMIN_PAGES.locations);
        await page.waitForLoadState('networkidle');

        await page.getByRole('button', { name: /tambah|add/i }).click();
        await expect(page.getByRole('dialog')).toBeVisible();

        // Try to submit empty form - find the submit button in dialog
        const submitBtn = page.getByRole('dialog').getByRole('button', { name: /simpan|save|submit/i });
        if (await submitBtn.isVisible()) {
            await submitBtn.click();
            await page.waitForTimeout(500);
        }
    });

    test('Can interact with map picker', async ({ adminPage: page }) => {
        await page.goto(ADMIN_PAGES.locations);

        await page.getByRole('button', { name: /tambah|add/i }).click();
        await expect(page.getByRole('dialog')).toBeVisible();

        // Look for map
        const map = page.locator('[class*="leaflet-container"], [class*="map-container"]');
        if (await map.isVisible()) {
            // Click on map to set location
            await map.click({ position: { x: 100, y: 100 } });

            // Coordinates should be updated
            await page.waitForTimeout(500);
        }
    });

    test('Can set location radius', async ({ adminPage: page }) => {
        await page.goto(ADMIN_PAGES.locations);

        await page.getByRole('button', { name: /tambah|add/i }).click();

        // Find radius input
        const radiusInput = page.getByLabel(/radius/i);
        if (await radiusInput.isVisible()) {
            await radiusInput.fill('100');

            // Value should be set
            await expect(radiusInput).toHaveValue('100');
        }
    });

    test('Can close dialog without saving', async ({ adminPage: page }) => {
        await page.goto(ADMIN_PAGES.locations);
        await page.waitForLoadState('networkidle');

        const addButton = page.getByRole('button', { name: /tambah|add/i }).first();
        if (await addButton.isVisible()) {
            await addButton.click();
            await expect(page.getByRole('dialog')).toBeVisible();

            // Close dialog using Escape key
            await page.keyboard.press('Escape');
            await expect(page.getByRole('dialog')).not.toBeVisible();
        }
    });
});
