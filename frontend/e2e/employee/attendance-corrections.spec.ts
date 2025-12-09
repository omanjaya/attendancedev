import { test, expect, EMPLOYEE_PAGES } from './fixtures';

test.describe('Employee Attendance Correction', () => {
    test('Should verify attendance correction menu exists', async ({ employeePage }) => {
        await employeePage.goto(EMPLOYEE_PAGES.dashboard);
        await employeePage.waitForLoadState('networkidle');

        // Check if navigation link exists in sidebar
        const correctionLink = employeePage.getByRole('link', { name: /koreksi absensi/i });
        await expect(correctionLink).toBeVisible({ timeout: 10000 });
    });

    test('Should be able to open correction page', async ({ employeePage }) => {
        await employeePage.goto(EMPLOYEE_PAGES.corrections);
        await employeePage.waitForLoadState('networkidle');

        // Verify page title or heading
        const pageHeading = employeePage.getByRole('heading').first();
        await expect(pageHeading).toBeVisible({ timeout: 10000 });
    });

    test('Should verify correction page has action button', async ({ employeePage }) => {
        await employeePage.goto(EMPLOYEE_PAGES.corrections);
        await employeePage.waitForLoadState('networkidle');

        // Look for add/submit button
        const actionButton = employeePage.getByRole('button').first();
        await expect(actionButton).toBeVisible({ timeout: 10000 });
    });
});
