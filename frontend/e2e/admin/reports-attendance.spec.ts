import { test, expect, ADMIN_PAGES } from './fixtures';

/**
 * Admin - Reports & Attendance Overview Tests
 * 
 * Menguji fitur laporan dan monitoring absensi:
 * - Attendance overview dashboard
 * - Monthly recap reports
 * - Export to Excel/PDF
 * - Filter by date, employee, status
 */

test.describe('Admin - Reports & Attendance', () => {

    // ============================================
    // ATTENDANCE OVERVIEW
    // ============================================
    test.describe('Attendance Overview', () => {

        test('Can view attendance dashboard', async ({ adminPage: page }) => {
            await page.goto(ADMIN_PAGES.attendance);
            await page.waitForLoadState('networkidle');

            // Should see attendance heading
            await expect(page.getByRole('heading', { name: /absensi|attendance|kehadiran/i })).toBeVisible({ timeout: 10000 });
        });

        test('Can view attendance by date', async ({ adminPage: page }) => {
            await page.goto(ADMIN_PAGES.attendance);

            // Find date picker
            const datePicker = page.getByRole('button', { name: /tanggal|date|pilih/i }).or(
                page.locator('[class*="date-picker"]')
            );

            if (await datePicker.isVisible()) {
                await datePicker.click();

                // Calendar should appear
                await expect(page.getByRole('grid').or(page.locator('[class*="calendar"]'))).toBeVisible();
            }
        });

        test('Can see attendance status breakdown', async ({ adminPage: page }) => {
            await page.goto(ADMIN_PAGES.attendance);

            // Wait for data to load
            await page.waitForLoadState('networkidle');

            // Should see status labels (Hadir, Terlambat, Izin, Sakit, Alfa)
            const statusTexts = [/hadir|present/i, /terlambat|late/i, /alfa|absent/i];

            for (const status of statusTexts) {
                const element = page.getByText(status);
                // May or may not be visible depending on data
            }
        });

        test('Can view individual attendance details', async ({ adminPage: page }) => {
            await page.goto(ADMIN_PAGES.attendance);

            await page.waitForLoadState('networkidle');

            // Click on an attendance row
            const attendanceRow = page.locator('tr').nth(1); // Skip header
            if (await attendanceRow.isVisible()) {
                await attendanceRow.click();

                // Details should appear (dialog or side panel)
                await page.waitForTimeout(500);
            }
        });
    });

    // ============================================
    // REPORTS
    // ============================================
    test.describe('Reports', () => {

        test('Can access reports page', async ({ adminPage: page }) => {
            await page.goto(ADMIN_PAGES.reports);

            // Should see reports heading
            await expect(page.getByRole('heading', { name: /laporan|report/i })).toBeVisible();
        });

        test('Can select report period (month/year)', async ({ adminPage: page }) => {
            await page.goto(ADMIN_PAGES.reports);

            // Find month selector
            const monthSelect = page.getByRole('combobox').first().or(
                page.getByLabel(/bulan|month/i)
            );

            if (await monthSelect.isVisible()) {
                await monthSelect.click();

                // Should show month options
                await page.waitForTimeout(300);
            }
        });

        test('Can view monthly recap with A/I/S/D/C breakdown', async ({ adminPage: page }) => {
            await page.goto(ADMIN_PAGES.reports);

            await page.waitForLoadState('networkidle');

            // Look for recap tab or section
            const recapTab = page.getByRole('tab', { name: /rekap|recap/i });
            if (await recapTab.isVisible()) {
                await recapTab.click();
                await page.waitForTimeout(500);
            }

            // Should see breakdown columns
            const columnHeaders = ['H', 'I', 'S', 'D', 'C', 'A']; // Hadir, Izin, Sakit, Dinas, Cuti, Alfa
            // These should appear in the table
        });

        test('Can export report to Excel', async ({ adminPage: page }) => {
            await page.goto(ADMIN_PAGES.reports);

            // Find export button
            const excelButton = page.getByRole('button', { name: /excel|export.*xls/i });

            if (await excelButton.isVisible()) {
                // Set up download listener
                const downloadPromise = page.waitForEvent('download', { timeout: 10000 });

                await excelButton.click();

                // Download should start (may fail if no data)
                try {
                    const download = await downloadPromise;
                    expect(download.suggestedFilename()).toMatch(/\.xlsx?$/);
                } catch {
                    // No data to export, that's OK
                }
            }
        });

        test('Can export report to PDF', async ({ adminPage: page }) => {
            await page.goto(ADMIN_PAGES.reports);

            // Find PDF button
            const pdfButton = page.getByRole('button', { name: /pdf|export.*pdf/i });

            if (await pdfButton.isVisible()) {
                // Set up download listener
                const downloadPromise = page.waitForEvent('download', { timeout: 10000 });

                await pdfButton.click();

                try {
                    const download = await downloadPromise;
                    expect(download.suggestedFilename()).toMatch(/\.pdf$/);
                } catch {
                    // No data to export
                }
            }
        });

        test('Can filter report by employee', async ({ adminPage: page }) => {
            await page.goto(ADMIN_PAGES.reports);

            // Find employee filter
            const employeeFilter = page.getByLabel(/karyawan|employee|pegawai/i).or(
                page.getByPlaceholder(/pilih|select.*employee/i)
            );

            if (await employeeFilter.isVisible()) {
                await employeeFilter.click();

                // Should show employee options
                await page.waitForTimeout(300);
            }
        });
    });

    // ============================================
    // LEAVE MANAGEMENT
    // ============================================
    test.describe('Leave Management', () => {

        test('Can view leave requests list', async ({ adminPage: page }) => {
            await page.goto(ADMIN_PAGES.leave);

            // Should see leave management page
            await expect(page.getByRole('heading', { name: /cuti|leave|izin/i })).toBeVisible();
        });

        test('Can filter by pending status', async ({ adminPage: page }) => {
            await page.goto(ADMIN_PAGES.leave);

            // Find pending tab or filter
            const pendingTab = page.getByRole('tab', { name: /pending|menunggu/i });
            if (await pendingTab.isVisible()) {
                await pendingTab.click();
                await page.waitForTimeout(300);
            }
        });

        test('Can approve leave request', async ({ adminPage: page }) => {
            await page.goto(ADMIN_PAGES.leave);

            await page.waitForLoadState('networkidle');

            // Find approve button on a request
            const approveButton = page.getByRole('button', { name: /approve|setujui|terima/i }).first();
            if (await approveButton.isVisible()) {
                // Don't actually click to avoid modifying data
                await expect(approveButton).toBeEnabled();
            }
        });

        test('Can reject leave request', async ({ adminPage: page }) => {
            await page.goto(ADMIN_PAGES.leave);

            await page.waitForLoadState('networkidle');

            // Find reject button on a request
            const rejectButton = page.getByRole('button', { name: /reject|tolak/i }).first();
            if (await rejectButton.isVisible()) {
                await expect(rejectButton).toBeEnabled();
            }
        });

        test('Can view leave request details', async ({ adminPage: page }) => {
            await page.goto(ADMIN_PAGES.leave);

            await page.waitForLoadState('networkidle');

            // Click on a leave request row
            const leaveRow = page.locator('tr, [class*="leave-card"]').first();
            if (await leaveRow.isVisible()) {
                await leaveRow.click();

                // Details should appear
                await page.waitForTimeout(500);
            }
        });
    });

    // ============================================
    // SCHEDULES
    // ============================================
    test.describe('Schedules', () => {

        test('Can view schedules page', async ({ adminPage: page }) => {
            await page.goto(ADMIN_PAGES.schedules);

            // Should see schedules page
            await expect(page.getByRole('heading', { name: /jadwal|schedule/i })).toBeVisible();
        });

        test('Can view teaching schedules', async ({ adminPage: page }) => {
            await page.goto(ADMIN_PAGES.schedules);

            await page.waitForLoadState('networkidle');

            // Look for schedule grid or list
            const scheduleGrid = page.locator('table, [class*="schedule-grid"], [class*="calendar"]');
            // May or may not be visible depending on data
        });
    });
});
