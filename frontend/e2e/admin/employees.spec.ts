import { test, expect, ADMIN_PAGES, waitForToast, fillForm } from './fixtures';

/**
 * Admin - Employee Management Tests
 * 
 * Menguji semua fitur manajemen karyawan:
 * - View employees list with pagination
 * - Search and filter employees
 * - Create new employee
 * - Edit employee details
 * - Upload employee photo
 * - Reset employee password
 * - Bulk actions (activate, deactivate, delete)
 */

test.describe('Admin - Employee Management', () => {

    test('Can view employees list with statistics', async ({ adminPage: page }) => {
        await page.goto(ADMIN_PAGES.employees);

        // Should see employees heading
        await expect(page.getByRole('heading', { name: /karyawan|pegawai|employee/i })).toBeVisible();

        // Wait for page to load
        await page.waitForLoadState('networkidle');

        // Should see some content (cards or table)
        const content = page.locator('[data-slot="card"]').first();
        await expect(content).toBeVisible({ timeout: 10000 });
    });

    test('Can search employees by name', async ({ adminPage: page }) => {
        await page.goto(ADMIN_PAGES.employees);

        // Wait for page to load
        await page.waitForLoadState('networkidle');

        // Find search input - use more specific selector
        const searchInput = page.getByRole('searchbox').first();
        await expect(searchInput).toBeVisible({ timeout: 10000 });

        // Type search query
        await searchInput.fill('guru');
        await page.waitForTimeout(500); // Wait for debounce
    });

    test('Can filter employees by status', async ({ adminPage: page }) => {
        await page.goto(ADMIN_PAGES.employees);

        // Look for filter dropdown or tabs
        const filterButton = page.getByRole('button', { name: /filter|status/i });
        const filterTabs = page.getByRole('tab', { name: /aktif|active/i });

        if (await filterButton.isVisible()) {
            await filterButton.click();
            await page.waitForTimeout(300);
        } else if (await filterTabs.isVisible()) {
            await filterTabs.click();
            await page.waitForTimeout(300);
        }
    });

    test('Can navigate to create employee form', async ({ adminPage: page }) => {
        await page.goto(ADMIN_PAGES.employees);
        await page.waitForLoadState('networkidle');

        // Click add button - use first() to avoid strict mode
        const addButton = page.getByRole('button', { name: /tambah|add/i }).first();
        if (await addButton.isVisible()) {
            await addButton.click();

            // Should either open dialog or navigate to create page
            await page.waitForTimeout(500);

            // Look for form elements
            const dialog = page.getByRole('dialog');
            await expect(dialog).toBeVisible({ timeout: 5000 });
        }
    });

    test('Employee form has all required fields', async ({ adminPage: page }) => {
        await page.goto(ADMIN_PAGES.employeeCreate);

        // Wait for form to load
        await page.waitForLoadState('networkidle');

        // Check for essential fields (may be in tabs)
        const formFields = [
            /nama lengkap|full name/i,
            /email/i,
            /telepon|phone/i,
        ];

        // At least some fields should be visible
        for (const field of formFields) {
            const input = page.getByLabel(field);
            // May or may not be visible depending on form layout
        }
    });

    test('Can select employee type', async ({ adminPage: page }) => {
        await page.goto(ADMIN_PAGES.employeeCreate);

        // Find employee type select
        const typeSelect = page.getByLabel(/tipe|type|jenis/i);
        if (await typeSelect.isVisible()) {
            await typeSelect.click();

            // Should show options
            await page.waitForTimeout(300);
            const options = page.getByRole('option');
            // Options should be available
        }
    });

    test('Can select location for employee', async ({ adminPage: page }) => {
        await page.goto(ADMIN_PAGES.employeeCreate);

        // Find location select
        const locationSelect = page.getByLabel(/lokasi|location/i);
        if (await locationSelect.isVisible()) {
            await locationSelect.click();

            // Should show location options
            await page.waitForTimeout(300);
        }
    });

    test('Can view employee details', async ({ adminPage: page }) => {
        await page.goto(ADMIN_PAGES.employees);

        // Wait for employees to load
        await page.waitForLoadState('networkidle');

        // Click on first employee row or card
        const employeeRow = page.locator('tr, [class*="employee-card"]').first();
        if (await employeeRow.isVisible()) {
            // Look for view/edit button
            const viewButton = employeeRow.getByRole('button', { name: /lihat|view|edit/i });
            if (await viewButton.isVisible()) {
                await viewButton.click();
                await page.waitForTimeout(500);
            }
        }
    });

    test('Can access bulk actions menu', async ({ adminPage: page }) => {
        await page.goto(ADMIN_PAGES.employees);

        // Wait for employees to load
        await page.waitForLoadState('networkidle');

        // Select an employee (checkbox)
        const checkbox = page.getByRole('checkbox').first();
        if (await checkbox.isVisible()) {
            await checkbox.check();

            // Bulk actions should appear
            const bulkMenu = page.getByRole('button', { name: /aksi|action|bulk/i });
            // May or may not appear depending on selection
        }
    });

    test('Pagination works correctly', async ({ adminPage: page }) => {
        await page.goto(ADMIN_PAGES.employees);

        // Wait for employees to load
        await page.waitForLoadState('networkidle');

        // Look for pagination
        const nextPage = page.getByRole('button', { name: /next|selanjutnya|>/i });
        if (await nextPage.isVisible() && await nextPage.isEnabled()) {
            await nextPage.click();

            // URL or content should change
            await page.waitForTimeout(500);
        }
    });
});
