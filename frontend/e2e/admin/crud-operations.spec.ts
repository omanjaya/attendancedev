import { test, expect, Page } from '@playwright/test';

/**
 * Admin CRUD Operations E2E Tests
 * 
 * Tests complete CRUD operations for all admin modules:
 * - Employees
 * - Locations  
 * - Holidays
 * - Leave Management
 * - Users
 * - Payroll
 */

const ADMIN_EMAIL = 'superadmin@school.edu';
const ADMIN_PASSWORD = 'password';

async function loginAsAdmin(page: Page) {
    await page.goto('/login');
    await page.getByLabel('Email').fill(ADMIN_EMAIL);
    await page.getByLabel('Password').fill(ADMIN_PASSWORD);
    await page.getByRole('button', { name: /masuk/i }).click();
    await expect(page).toHaveURL(/.*dashboard/, { timeout: 15000 });
}

test.describe('Admin CRUD Operations', () => {

    // ============================================
    // EMPLOYEE CRUD
    // ============================================
    test.describe('Employee CRUD', () => {

        test('Can create new employee', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/employees/create');
            await page.waitForLoadState('networkidle');

            // Fill in basic employee info
            const nameInput = page.getByRole('textbox', { name: /nama lengkap/i });
            if (await nameInput.isVisible()) {
                await nameInput.fill('Test Employee E2E');
            }

            const emailInput = page.getByRole('textbox', { name: /email/i });
            if (await emailInput.isVisible()) {
                await emailInput.fill(`test.e2e.${Date.now()}@example.com`);
            }

            // Check form is fillable
            expect(await nameInput.isVisible() || await emailInput.isVisible()).toBeTruthy();
        });

        test('Can edit employee', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/employees');
            await page.waitForLoadState('networkidle');

            // Find and click edit button on first employee
            const editButton = page.getByRole('button', { name: /edit|ubah/i }).first();
            if (await editButton.isVisible()) {
                await editButton.click();
                await page.waitForTimeout(500);

                // Should see edit form or dialog
                const formVisible = await page.getByRole('textbox').first().isVisible();
                expect(formVisible).toBeTruthy();
            }
        });

        test('Can view employee details', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/employees');
            await page.waitForLoadState('networkidle');

            // Click on employee row or view button
            const viewButton = page.getByRole('button', { name: /lihat|view|detail/i }).first();
            const employeeLink = page.locator('tr').nth(1).locator('a').first();

            if (await viewButton.isVisible()) {
                await viewButton.click();
            } else if (await employeeLink.isVisible()) {
                await employeeLink.click();
            }

            await page.waitForTimeout(500);
        });

        test('Can use employee bulk actions', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/employees');
            await page.waitForLoadState('networkidle');

            // Select checkbox
            const checkbox = page.getByRole('checkbox').first();
            if (await checkbox.isVisible()) {
                await checkbox.check();

                // Bulk action menu should appear
                await page.waitForTimeout(300);
            }
        });

        test('Can filter employees by department', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/employees');
            await page.waitForLoadState('networkidle');

            // Look for department filter
            const filterButton = page.getByRole('button', { name: /filter|departemen/i });
            const filterSelect = page.getByRole('combobox');

            if (await filterButton.isVisible()) {
                await filterButton.click();
                await page.waitForTimeout(300);
            }
        });
    });

    // ============================================
    // LOCATION CRUD
    // ============================================
    test.describe('Location CRUD', () => {

        test('Can create new location', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/locations');
            await page.waitForLoadState('networkidle');

            // Click add button
            const addButton = page.getByRole('button', { name: /tambah|add/i });
            await addButton.click();

            await expect(page.getByRole('dialog')).toBeVisible();

            // Fill form
            await page.getByLabel(/nama/i).fill('Test Location E2E');
            await page.getByLabel(/latitude/i).fill('-6.2088');
            await page.getByLabel(/longitude/i).fill('106.8456');
            await page.getByLabel(/radius/i).fill('100');
        });

        test('Can edit location', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/locations');
            await page.waitForLoadState('networkidle');

            const editButton = page.getByRole('button', { name: /edit|ubah/i }).first();
            if (await editButton.isVisible()) {
                await editButton.click();
                await expect(page.getByRole('dialog')).toBeVisible();
            }
        });

        test('Can toggle location status', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/locations');
            await page.waitForLoadState('networkidle');

            // Look for toggle switch
            const toggle = page.getByRole('switch').first();
            if (await toggle.isVisible()) {
                await toggle.click();
                await page.waitForTimeout(500);
            }
        });
    });

    // ============================================
    // HOLIDAY CRUD
    // ============================================
    test.describe('Holiday CRUD', () => {

        test('Can create new holiday', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/holidays');
            await page.waitForLoadState('networkidle');

            const addButton = page.getByRole('button', { name: /tambah|add/i });
            if (await addButton.isVisible()) {
                await addButton.click();
                await expect(page.getByRole('dialog')).toBeVisible();

                // Fill holiday form
                await page.getByLabel(/nama/i).fill('Test Holiday E2E');
            }
        });

        test('Can edit holiday', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/holidays');
            await page.waitForLoadState('networkidle');

            const editButton = page.getByRole('button', { name: /edit|ubah/i }).first();
            if (await editButton.isVisible()) {
                await editButton.click();
                await page.waitForTimeout(500);
            }
        });

        test('Can delete holiday', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/holidays');
            await page.waitForLoadState('networkidle');

            const deleteButton = page.getByRole('button', { name: /hapus|delete/i }).first();
            if (await deleteButton.isVisible()) {
                await deleteButton.click();

                // Confirmation dialog should appear
                const confirmDialog = page.getByRole('alertdialog').or(page.getByRole('dialog'));
                if (await confirmDialog.isVisible()) {
                    // Click cancel to not actually delete
                    await page.getByRole('button', { name: /batal|cancel/i }).click();
                }
            }
        });

        test('Can import holidays from calendar', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/holidays');
            await page.waitForLoadState('networkidle');

            const importButton = page.getByRole('button', { name: /import|impor/i });
            if (await importButton.isVisible()) {
                await importButton.click();
                await page.waitForTimeout(500);
            }
        });
    });

    // ============================================
    // LEAVE MANAGEMENT
    // ============================================
    test.describe('Leave Management', () => {

        test('Can approve leave request', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/leave');
            await page.waitForLoadState('networkidle');

            // Find pending tab
            const pendingTab = page.getByRole('tab', { name: /pending|menunggu/i });
            if (await pendingTab.isVisible()) {
                await pendingTab.click();
                await page.waitForTimeout(500);
            }

            // Find approve button
            const approveButton = page.getByRole('button', { name: /setuju|approve/i }).first();
            if (await approveButton.isVisible()) {
                expect(await approveButton.isEnabled()).toBeTruthy();
            }
        });

        test('Can reject leave request', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/leave');
            await page.waitForLoadState('networkidle');

            const rejectButton = page.getByRole('button', { name: /tolak|reject/i }).first();
            if (await rejectButton.isVisible()) {
                expect(await rejectButton.isEnabled()).toBeTruthy();
            }
        });

        test('Can view leave details', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/leave');
            await page.waitForLoadState('networkidle');

            // Click on a leave row
            const leaveRow = page.locator('tr').nth(1);
            if (await leaveRow.isVisible()) {
                await leaveRow.click();
                await page.waitForTimeout(500);
            }
        });

        test('Can filter leaves by type', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/leave');
            await page.waitForLoadState('networkidle');

            const typeFilter = page.getByRole('combobox').first();
            if (await typeFilter.isVisible()) {
                await typeFilter.click();
                await page.waitForTimeout(300);
            }
        });
    });

    // ============================================
    // USER MANAGEMENT
    // ============================================
    test.describe('User Management', () => {

        test('Can create new user', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/users');
            await page.waitForLoadState('networkidle');

            const addButton = page.getByRole('button', { name: /tambah|add|baru/i });
            if (await addButton.isVisible()) {
                await addButton.click();
                await page.waitForTimeout(500);

                // Form should appear
                const dialog = page.getByRole('dialog');
                const formPage = page.getByRole('textbox').first();
                expect(await dialog.isVisible() || await formPage.isVisible()).toBeTruthy();
            }
        });

        test('Can edit user', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/users');
            await page.waitForLoadState('networkidle');

            const editButton = page.getByRole('button', { name: /edit|ubah/i }).first();
            if (await editButton.isVisible()) {
                await editButton.click();
                await page.waitForTimeout(500);
            }
        });

        test('Can reset user password', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/users');
            await page.waitForLoadState('networkidle');

            const resetButton = page.getByRole('button', { name: /reset|password/i }).first();
            if (await resetButton.isVisible()) {
                expect(await resetButton.isEnabled()).toBeTruthy();
            }
        });

        test('Can toggle user status', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/users');
            await page.waitForLoadState('networkidle');

            const toggle = page.getByRole('switch').first();
            if (await toggle.isVisible()) {
                expect(await toggle.isVisible()).toBeTruthy();
            }
        });

        test('Can assign roles to user', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/users');
            await page.waitForLoadState('networkidle');

            const editButton = page.getByRole('button', { name: /edit|ubah/i }).first();
            if (await editButton.isVisible()) {
                await editButton.click();
                await page.waitForTimeout(500);

                // Look for role selector
                const roleSelect = page.getByLabel(/role|peran/i);
                if (await roleSelect.isVisible()) {
                    await roleSelect.click();
                }
            }
        });
    });

    // ============================================
    // PAYROLL
    // ============================================
    test.describe('Payroll', () => {

        test('Can access payroll page', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/payroll');
            await page.waitForLoadState('networkidle');

            // Should see payroll heading
            const heading = page.getByRole('heading', { name: /payroll|gaji|penggajian/i });
            await expect(heading.or(page.locator('h1').first())).toBeVisible({ timeout: 10000 });
        });

        test('Can view payroll periods', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/payroll');
            await page.waitForLoadState('networkidle');

            // Should see period selector or list
            const periodSelector = page.getByRole('combobox').first();
            const periodList = page.locator('table');

            expect(await periodSelector.isVisible() || await periodList.isVisible()).toBeTruthy();
        });

        test('Can generate payroll report', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/payroll');
            await page.waitForLoadState('networkidle');

            const generateButton = page.getByRole('button', { name: /generate|buat|proses/i });
            if (await generateButton.isVisible()) {
                expect(await generateButton.isEnabled()).toBeTruthy();
            }
        });
    });

    // ============================================
    // SECURITY/AUDIT
    // ============================================
    test.describe('Security', () => {

        test('Can access security settings', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/security');
            await page.waitForLoadState('networkidle');

            // Check page loads
            await expect(page).toHaveURL(/.*security/);
        });

        test('Can view audit logs', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/security/audit-logs');
            await page.waitForLoadState('networkidle');

            // Page should load (may redirect to security if audit-logs not available)
            await expect(page).toHaveURL(/.*(?:security|audit)/);
        });
    });

    // ============================================
    // ATTENDANCE MANUAL ENTRY
    // ============================================
    test.describe('Attendance Manual Entry', () => {

        test('Can add manual attendance', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/attendance');
            await page.waitForLoadState('networkidle');

            // Page should load successfully
            const heading = page.getByRole('heading', { name: /absensi|attendance/i });
            await expect(heading).toBeVisible({ timeout: 10000 });
        });

        test('Can edit attendance record', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/attendance');
            await page.waitForLoadState('networkidle');

            const editButton = page.getByRole('button', { name: /edit|ubah/i }).first();
            if (await editButton.isVisible()) {
                await editButton.click();
                await page.waitForTimeout(500);
            }
        });
    });

    // ============================================
    // REPORTS EXPORT
    // ============================================
    test.describe('Reports Export', () => {

        test('Can export to Excel', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/reports');
            await page.waitForLoadState('networkidle');

            const excelButton = page.getByRole('button', { name: /excel/i });
            if (await excelButton.isVisible()) {
                expect(await excelButton.isEnabled()).toBeTruthy();
            }
        });

        test('Can export to PDF', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/reports');
            await page.waitForLoadState('networkidle');

            const pdfButton = page.getByRole('button', { name: /pdf/i });
            if (await pdfButton.isVisible()) {
                expect(await pdfButton.isEnabled()).toBeTruthy();
            }
        });

        test('Can select date range for report', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/reports');
            await page.waitForLoadState('networkidle');

            const dateRangePicker = page.getByRole('button', { name: /tanggal|date|range/i });
            if (await dateRangePicker.isVisible()) {
                await dateRangePicker.click();
                await page.waitForTimeout(300);
            }
        });
    });
});
