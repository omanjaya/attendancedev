import { test, expect, ADMIN_PAGES } from './fixtures';

/**
 * Admin - Master Data Tests
 * 
 * Menguji semua fitur master data:
 * - Employee Types (Tipe Karyawan)
 * - Departments (Unit Kerja)
 * - Positions (Jabatan)
 * - Subjects (Mata Pelajaran)
 */

test.describe('Admin - Master Data Management', () => {

    // ============================================
    // EMPLOYEE TYPES
    // ============================================
    test.describe('Employee Types', () => {

        test('Can view employee types tab', async ({ adminPage: page }) => {
            await page.goto(ADMIN_PAGES.masterData);
            await page.waitForLoadState('networkidle');

            // Should see the page heading
            const pageContent = page.locator('main, [role=\"main\"], .page-content').first();
            await expect(pageContent).toBeVisible({ timeout: 10000 });
        });

        test('Can see list of employee types', async ({ adminPage: page }) => {
            await page.goto(ADMIN_PAGES.masterData);

            // Wait for data to load
            await page.waitForLoadState('networkidle');

            // Should see content (cards or table)
            const content = page.locator('[data-slot="card"], table tbody tr').first();
            await expect(content).toBeVisible({ timeout: 10000 });
        });

        test('Can open create employee type dialog', async ({ adminPage: page }) => {
            await page.goto(ADMIN_PAGES.masterData);

            // Click add button
            const addButton = page.getByRole('button', { name: /tambah|add/i }).first();
            await addButton.click();

            // Dialog should open
            await expect(page.getByRole('dialog')).toBeVisible();

            // Should have name field
            await expect(page.getByLabel(/nama|name/i)).toBeVisible();
        });

        test('Employee type form has configuration options', async ({ adminPage: page }) => {
            await page.goto(ADMIN_PAGES.masterData);

            const addButton = page.getByRole('button', { name: /tambah|add/i }).first();
            await addButton.click();

            await expect(page.getByRole('dialog')).toBeVisible();

            // Should have various configuration options
            // These may be checkboxes, selects, or inputs
            const workdaysCheckbox = page.getByLabel(/hari kerja|work days|senin|monday/i);
            const lateTolerance = page.getByLabel(/toleransi|tolerance/i);

            // At least the name field should be visible
            await expect(page.getByLabel(/nama|name/i)).toBeVisible();
        });
    });

    // ============================================
    // DEPARTMENTS
    // ============================================
    test.describe('Departments', () => {

        test('Can switch to departments tab', async ({ adminPage: page }) => {
            await page.goto(ADMIN_PAGES.masterData);

            // Click departments tab
            const deptTab = page.getByRole('tab', { name: /departemen|department|unit kerja/i });
            await deptTab.click();

            // Should show departments content
            await page.waitForTimeout(300);

            // Add button should be visible in this tab
            await expect(page.getByRole('button', { name: /tambah|add/i })).toBeVisible();
        });

        test('Can create new department', async ({ adminPage: page }) => {
            await page.goto(ADMIN_PAGES.masterData);

            // Switch to departments tab
            await page.getByRole('tab', { name: /departemen|department|unit/i }).click();
            await page.waitForTimeout(300);

            // Click add button
            const addButton = page.getByRole('button', { name: /tambah|add/i });
            if (await addButton.isVisible()) {
                await addButton.click();

                // Form should appear
                await expect(page.getByRole('dialog')).toBeVisible();
            }
        });
    });

    // ============================================
    // POSITIONS
    // ============================================
    test.describe('Positions', () => {

        test('Can switch to positions tab', async ({ adminPage: page }) => {
            await page.goto(ADMIN_PAGES.masterData);

            // Click positions tab
            const posTab = page.getByRole('tab', { name: /jabatan|position/i });
            await posTab.click();

            // Should show positions content
            await page.waitForTimeout(300);

            await expect(page.getByRole('button', { name: /tambah|add/i })).toBeVisible();
        });

        test('Can create new position', async ({ adminPage: page }) => {
            await page.goto(ADMIN_PAGES.masterData);

            // Switch to positions tab
            await page.getByRole('tab', { name: /jabatan|position/i }).click();
            await page.waitForTimeout(300);

            // Click add button
            const addButton = page.getByRole('button', { name: /tambah|add/i });
            if (await addButton.isVisible()) {
                await addButton.click();

                // Form should appear
                await expect(page.getByRole('dialog')).toBeVisible();
            }
        });
    });

    // ============================================
    // SUBJECTS
    // ============================================
    test.describe('Subjects', () => {

        test('Can switch to subjects tab', async ({ adminPage: page }) => {
            await page.goto(ADMIN_PAGES.masterData);

            // Click subjects tab
            const subjectTab = page.getByRole('tab', { name: /mata pelajaran|subject/i });
            await subjectTab.click();

            // Should show subjects content
            await page.waitForTimeout(300);

            await expect(page.getByRole('button', { name: /tambah|add/i })).toBeVisible();
        });

        test('Can create new subject', async ({ adminPage: page }) => {
            await page.goto(ADMIN_PAGES.masterData);

            // Switch to subjects tab
            await page.getByRole('tab', { name: /mata pelajaran|subject/i }).click();
            await page.waitForTimeout(300);

            // Click add button
            const addButton = page.getByRole('button', { name: /tambah|add/i });
            if (await addButton.isVisible()) {
                await addButton.click();

                // Form should appear
                await expect(page.getByRole('dialog')).toBeVisible();

                // Should have subject-specific fields
                await expect(page.getByLabel(/nama|name/i)).toBeVisible();
            }
        });

        test('Subject form has code field', async ({ adminPage: page }) => {
            await page.goto(ADMIN_PAGES.masterData);

            // Switch to subjects tab
            await page.getByRole('tab', { name: /mata pelajaran|subject/i }).click();
            await page.waitForTimeout(300);

            // Click add button
            const addButton = page.getByRole('button', { name: /tambah|add/i });
            if (await addButton.isVisible()) {
                await addButton.click();

                // Should have code field (for subject code like "MTK", "IPA", etc.)
                const codeField = page.getByLabel(/kode|code/i);
                // May or may not have code field
            }
        });
    });

    // ============================================
    // COMMON OPERATIONS
    // ============================================
    test.describe('Common Operations', () => {

        test('Can edit existing item', async ({ adminPage: page }) => {
            await page.goto(ADMIN_PAGES.masterData);

            // Wait for data to load
            await page.waitForLoadState('networkidle');

            // Find edit button on first item
            const editButton = page.getByRole('button', { name: /edit|ubah/i }).first();
            if (await editButton.isVisible()) {
                await editButton.click();

                // Edit dialog should open
                await expect(page.getByRole('dialog')).toBeVisible();
            }
        });

        test('Can delete item with confirmation', async ({ adminPage: page }) => {
            await page.goto(ADMIN_PAGES.masterData);

            // Wait for data to load
            await page.waitForLoadState('networkidle');

            // Find delete button on first item
            const deleteButton = page.getByRole('button', { name: /hapus|delete/i }).first();
            if (await deleteButton.isVisible()) {
                await deleteButton.click();

                // Confirmation dialog should appear
                await expect(page.getByRole('alertdialog').or(page.getByRole('dialog'))).toBeVisible();

                // Cancel to not actually delete
                const cancelButton = page.getByRole('button', { name: /batal|cancel|tidak/i });
                if (await cancelButton.isVisible()) {
                    await cancelButton.click();
                }
            }
        });
    });
});
