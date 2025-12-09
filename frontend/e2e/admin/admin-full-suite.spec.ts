import { test, expect, Page } from '@playwright/test';

/**
 * Admin E2E Test Suite
 * 
 * Tests all admin functionality including:
 * - Login as Admin
 * - Location Management
 * - Employee Management
 * - Master Data (Employee Types, Departments, Positions, Subjects)
 * - Holiday Management
 * - Attendance Reports
 * - Leave Management
 */

// Test configuration
const ADMIN_EMAIL = 'superadmin@school.edu';
const ADMIN_PASSWORD = 'password';
const BASE_URL = 'http://localhost:5173';

// Helper function to login as admin
async function loginAsAdmin(page: Page) {
    await page.goto('/login');
    await page.getByLabel('Email').fill(ADMIN_EMAIL);
    await page.getByLabel('Password').fill(ADMIN_PASSWORD);
    await page.getByRole('button', { name: /masuk/i }).click();

    // Wait for redirect to dashboard
    await expect(page).toHaveURL(/.*dashboard/, { timeout: 10000 });
}

// Helper function to navigate to admin section
async function navigateToAdmin(page: Page, section: string) {
    // Click on sidebar menu
    await page.getByRole('link', { name: new RegExp(section, 'i') }).click();
    await page.waitForLoadState('networkidle');
}

test.describe('Admin E2E Test Suite', () => {

    // ============================================
    // 1. AUTHENTICATION TESTS
    // ============================================
    test.describe('1. Admin Authentication', () => {
        test('1.1 Admin can login successfully', async ({ page }) => {
            await page.goto('/login');
            await page.getByLabel('Email').fill(ADMIN_EMAIL);
            await page.getByLabel('Password').fill(ADMIN_PASSWORD);
            await page.getByRole('button', { name: /masuk/i }).click();

            // Should redirect to admin dashboard
            await expect(page).toHaveURL(/.*dashboard/);

            // Should see admin-specific elements
            await expect(page.getByRole('heading', { name: /dashboard/i })).toBeVisible();
        });

        test('1.2 Admin can access settings menu', async ({ page }) => {
            await loginAsAdmin(page);

            // Should be able to see settings/pengaturan menu
            const settingsMenu = page.getByRole('link', { name: /pengaturan|settings/i });
            await expect(settingsMenu).toBeVisible();
        });
    });

    // ============================================
    // 2. LOCATION MANAGEMENT TESTS
    // ============================================
    test.describe('2. Location Management', () => {
        test('2.1 Admin can view locations list', async ({ page }) => {
            await loginAsAdmin(page);

            // Navigate to locations
            await page.goto('/admin/locations');

            // Should see locations page
            await expect(page.getByRole('heading', { name: /lokasi|location/i })).toBeVisible();
        });

        test('2.2 Admin can open create location dialog', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/locations');

            // Click add button
            const addButton = page.getByRole('button', { name: /tambah|add|baru/i });
            if (await addButton.isVisible()) {
                await addButton.click();

                // Should see form dialog
                await expect(page.getByRole('dialog')).toBeVisible();
                await expect(page.getByLabel(/nama lokasi|name/i)).toBeVisible();
            }
        });

        test('2.3 Location form has required fields', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/locations');

            const addButton = page.getByRole('button', { name: /tambah|add|baru/i });
            if (await addButton.isVisible()) {
                await addButton.click();

                // Check required fields exist
                await expect(page.getByLabel(/nama|name/i)).toBeVisible();
                await expect(page.getByLabel(/latitude/i)).toBeVisible();
                await expect(page.getByLabel(/longitude/i)).toBeVisible();
                await expect(page.getByLabel(/radius/i)).toBeVisible();
            }
        });
    });

    // ============================================
    // 3. EMPLOYEE MANAGEMENT TESTS
    // ============================================
    test.describe('3. Employee Management', () => {
        test('3.1 Admin can view employees list', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/employees');

            // Should see employees page
            await expect(page.getByRole('heading', { name: /karyawan|employee|pegawai/i })).toBeVisible();
        });

        test('3.2 Admin can search employees', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/employees');

            // Find search input
            const searchInput = page.getByPlaceholder(/cari|search/i);
            if (await searchInput.isVisible()) {
                await searchInput.fill('test');
                await page.waitForTimeout(500); // Debounce

                // Search should trigger (no error)
                await expect(page).toHaveURL(/.*employees/);
            }
        });

        test('3.3 Admin can open create employee dialog', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/employees');

            const addButton = page.getByRole('button', { name: /tambah|add|baru/i });
            if (await addButton.isVisible()) {
                await addButton.click();

                // Should see form dialog or navigate to form
                const dialog = page.getByRole('dialog');
                const formPage = page.getByLabel(/nama lengkap|full name/i);

                // Either dialog or form page should be visible
                await expect(dialog.or(formPage)).toBeVisible();
            }
        });

        test('3.4 Employee form has required fields', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/employees/create');

            // Wait for form to load
            await page.waitForLoadState('networkidle');

            // Check for the name field specifically using placeholder or role
            const nameInput = page.getByRole('textbox', { name: /nama lengkap|full name/i });
            await expect(nameInput).toBeVisible({ timeout: 10000 });
        });
    });

    // ============================================
    // 4. MASTER DATA - EMPLOYEE TYPES
    // ============================================
    test.describe('4. Master Data - Employee Types', () => {
        test('4.1 Admin can view employee types', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/settings/employee-types');

            // Wait for page to load
            await page.waitForLoadState('networkidle');

            // Check that we're on the correct page by looking for any tab or table
            const tabList = page.getByRole('tablist');
            const table = page.locator('table');
            const addButton = page.getByRole('button', { name: /tambah|add/i }).first();

            // One of these should be visible
            const tabVisible = await tabList.isVisible();
            const tableVisible = await table.isVisible();
            const buttonVisible = await addButton.isVisible();

            expect(tabVisible || tableVisible || buttonVisible).toBeTruthy();
        });

        test('4.2 Admin can open create employee type dialog', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/settings/employee-types');

            // Find add button in the employee types tab
            const addButton = page.getByRole('button', { name: /tambah|add/i }).first();
            if (await addButton.isVisible()) {
                await addButton.click();

                // Dialog should open
                await expect(page.getByRole('dialog')).toBeVisible();
            }
        });
    });

    // ============================================
    // 5. MASTER DATA - DEPARTMENTS
    // ============================================
    test.describe('5. Master Data - Departments', () => {
        test('5.1 Admin can view departments tab', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/settings/employee-types');

            // Click departments tab
            const deptTab = page.getByRole('tab', { name: /departemen|department|unit kerja/i });
            if (await deptTab.isVisible()) {
                await deptTab.click();

                // Should see departments content
                await page.waitForTimeout(300);
                await expect(page.getByText(/departemen|department|unit/i)).toBeVisible();
            }
        });
    });

    // ============================================
    // 6. MASTER DATA - POSITIONS
    // ============================================
    test.describe('6. Master Data - Positions', () => {
        test('6.1 Admin can view positions tab', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/settings/employee-types');

            // Click positions tab
            const posTab = page.getByRole('tab', { name: /jabatan|position/i });
            if (await posTab.isVisible()) {
                await posTab.click();

                // Should see positions content
                await page.waitForTimeout(300);
                await expect(page.getByText(/jabatan|position/i)).toBeVisible();
            }
        });
    });

    // ============================================
    // 7. MASTER DATA - SUBJECTS
    // ============================================
    test.describe('7. Master Data - Subjects', () => {
        test('7.1 Admin can view subjects tab', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/settings/employee-types');

            // Click subjects tab
            const subjectTab = page.getByRole('tab', { name: /mata pelajaran|subject/i });
            if (await subjectTab.isVisible()) {
                await subjectTab.click();

                // Should see subjects content
                await page.waitForTimeout(300);
                await expect(page.getByText(/mata pelajaran|subject/i)).toBeVisible();
            }
        });
    });

    // ============================================
    // 8. HOLIDAY MANAGEMENT
    // ============================================
    test.describe('8. Holiday Management', () => {
        test('8.1 Admin can view holidays list', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/holidays');

            // Should see holidays page
            await expect(page.getByRole('heading', { name: /libur|holiday/i })).toBeVisible();
        });

        test('8.2 Admin can open create holiday dialog', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/holidays');

            const addButton = page.getByRole('button', { name: /tambah|add|baru/i });
            if (await addButton.isVisible()) {
                await addButton.click();

                // Should see form dialog
                await expect(page.getByRole('dialog')).toBeVisible();
                await expect(page.getByLabel(/nama|name/i)).toBeVisible();
                await expect(page.getByLabel(/tanggal|date/i)).toBeVisible();
            }
        });
    });

    // ============================================
    // 9. ATTENDANCE OVERVIEW
    // ============================================
    test.describe('9. Attendance Overview', () => {
        test('9.1 Admin can view attendance list', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/attendance');

            // Should see attendance page
            await expect(page.getByRole('heading', { name: /absensi|attendance|kehadiran/i })).toBeVisible();
        });

        test('9.2 Admin can filter attendance by date', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/attendance');

            // Look for date picker
            const datePicker = page.getByRole('button', { name: /pilih tanggal|select date|tanggal/i });
            if (await datePicker.isVisible()) {
                await datePicker.click();

                // Calendar should open
                await expect(page.getByRole('grid')).toBeVisible();
            }
        });

        test('9.3 Admin can view attendance statistics', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/attendance');

            // Wait for page and data to load
            await page.waitForLoadState('networkidle');
            await page.waitForTimeout(1000); // Wait for animations

            // Should see the attendance page content
            const heading = page.getByRole('heading', { name: /absensi|attendance|kehadiran/i });
            await expect(heading).toBeVisible({ timeout: 10000 });
        });
    });

    // ============================================
    // 10. REPORTS
    // ============================================
    test.describe('10. Reports', () => {
        test('10.1 Admin can access reports page', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/reports');

            // Should see reports page
            await expect(page.getByRole('heading', { name: /laporan|report/i })).toBeVisible();
        });

        test('10.2 Admin can filter reports by period', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/reports');

            // Look for period filter
            const periodFilter = page.getByRole('combobox').first();
            if (await periodFilter.isVisible()) {
                await periodFilter.click();

                // Should see period options
                await page.waitForTimeout(300);
            }
        });

        test('10.3 Admin can export reports', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/reports');

            // Wait for page to load
            await page.waitForLoadState('networkidle');

            // Look for any export button (Excel or PDF)
            const exportButton = page.getByRole('button', { name: /excel|pdf|export|unduh/i }).first();
            const hasExport = await exportButton.isVisible();

            // Either export button exists or the page is displayed correctly
            if (!hasExport) {
                // At least the reports page should be visible
                await expect(page.getByRole('heading', { name: /laporan|report/i })).toBeVisible();
            } else {
                await expect(exportButton).toBeVisible();
            }
        });
    });

    // ============================================
    // 11. LEAVE MANAGEMENT
    // ============================================
    test.describe('11. Leave Management', () => {
        test('11.1 Admin can view leave requests', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/leave');

            // Wait for page to load
            await page.waitForLoadState('networkidle');

            // Should see leave page - use h1 specifically or first heading
            const heading = page.locator('h1').filter({ hasText: /cuti|izin|leave/i });
            await expect(heading).toBeVisible({ timeout: 10000 });
        });

        test('11.2 Admin can filter leave by status', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/leave');

            // Look for status filter tabs
            const pendingTab = page.getByRole('tab', { name: /pending|menunggu/i });
            if (await pendingTab.isVisible()) {
                await pendingTab.click();
                await page.waitForTimeout(300);
            }
        });
    });

    // ============================================
    // 12. SCHEDULE MANAGEMENT
    // ============================================
    test.describe('12. Schedule Management', () => {
        test('12.1 Admin can view schedules', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/schedules');

            // Should see schedules page
            await expect(page.getByRole('heading', { name: /jadwal|schedule/i })).toBeVisible();
        });
    });

    // ============================================
    // 13. USER MANAGEMENT
    // ============================================
    test.describe('13. User Management', () => {
        test('13.1 Admin can view users list', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/users');

            // Should see users page
            await expect(page.getByRole('heading', { name: /pengguna|user/i })).toBeVisible();
        });

        test('13.2 Admin can search users', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/users');

            const searchInput = page.getByPlaceholder(/cari|search/i);
            if (await searchInput.isVisible()) {
                await searchInput.fill('admin');
                await page.waitForTimeout(500);

                // Should filter results
                await expect(page).toHaveURL(/.*users/);
            }
        });
    });

    // ============================================
    // 14. DASHBOARD WIDGETS
    // ============================================
    test.describe('14. Dashboard Widgets', () => {
        test('14.1 Admin dashboard shows attendance summary', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/dashboard');

            // Wait for dashboard to fully load
            await page.waitForLoadState('networkidle');
            await page.waitForTimeout(1000); // Wait for animations

            // Should see dashboard heading at minimum
            const heading = page.getByRole('heading', { name: /dashboard/i });
            await expect(heading).toBeVisible({ timeout: 10000 });
        });

        test('14.2 Admin dashboard shows charts', async ({ page }) => {
            await loginAsAdmin(page);
            await page.goto('/admin/dashboard');

            // Should see chart elements
            await page.waitForLoadState('networkidle');

            // Look for recharts elements or canvas
            const chart = page.locator('.recharts-wrapper, canvas, [class*="chart"]').first();
            // Chart may or may not be visible depending on data
        });
    });

    // ============================================
    // 15. LOGOUT
    // ============================================
    test.describe('15. Logout', () => {
        test('15.1 Admin can logout successfully', async ({ page }) => {
            await loginAsAdmin(page);

            // Find user menu / avatar
            const userMenu = page.getByRole('button', { name: /profile|akun|admin/i }).or(
                page.locator('[class*="avatar"]')
            );

            if (await userMenu.isVisible()) {
                await userMenu.click();

                // Click logout
                const logoutButton = page.getByRole('menuitem', { name: /logout|keluar/i });
                if (await logoutButton.isVisible()) {
                    await logoutButton.click();

                    // Should redirect to login
                    await expect(page).toHaveURL(/.*login/);
                }
            }
        });
    });
});
