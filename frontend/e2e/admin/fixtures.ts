import { test as base, expect, Page } from '@playwright/test';

/**
 * Admin Test Fixtures
 * Provides reusable login state and helper functions
 */

// Test credentials - should match your test database
export const TEST_CREDENTIALS = {
    superadmin: {
        email: 'superadmin@school.edu',
        password: 'password',
    },
    admin: {
        email: 'admin@school.edu',
        password: 'password',
    },
    employee: {
        email: 'guru1@school.edu',
        password: 'password',
    },
};

// Admin page URLs
export const ADMIN_PAGES = {
    dashboard: '/admin/dashboard',
    employees: '/admin/employees',
    employeeCreate: '/admin/employees/create',
    attendance: '/admin/attendance',
    locations: '/admin/locations',
    holidays: '/admin/holidays',
    reports: '/admin/reports',
    leave: '/admin/leave',
    schedules: '/admin/schedules',
    users: '/admin/users',
    settings: '/admin/settings',
    masterData: '/admin/settings/employee-types',
};

// Custom test fixture with admin authentication
type AdminFixtures = {
    adminPage: Page;
    superAdminPage: Page;
};

export const test = base.extend<AdminFixtures>({
    // Pre-authenticated admin page
    adminPage: async ({ page }, use) => {
        // Login as admin
        await page.goto('/login');
        await page.getByLabel('Email').fill(TEST_CREDENTIALS.admin.email);
        await page.getByLabel('Password').fill(TEST_CREDENTIALS.admin.password);
        await page.getByRole('button', { name: /masuk/i }).click();

        // Wait for successful login - should go directly to dashboard now
        await page.waitForURL(/.*dashboard/, { timeout: 15000 });

        // Provide the authenticated page
        await use(page);
    },

    // Pre-authenticated superadmin page
    superAdminPage: async ({ page }, use) => {
        await page.goto('/login');
        await page.getByLabel('Email').fill(TEST_CREDENTIALS.superadmin.email);
        await page.getByLabel('Password').fill(TEST_CREDENTIALS.superadmin.password);
        await page.getByRole('button', { name: /masuk/i }).click();

        await page.waitForURL(/.*dashboard/, { timeout: 15000 });

        await use(page);
    },
});

// Re-export expect
export { expect };

// Helper functions
export async function waitForAPIResponse(page: Page, urlPattern: RegExp) {
    return page.waitForResponse(response =>
        urlPattern.test(response.url()) && response.status() === 200
    );
}

export async function waitForToast(page: Page, message?: string) {
    const toast = page.locator('[role="alert"], [class*="toast"], [class*="sonner"]');
    await expect(toast).toBeVisible({ timeout: 5000 });

    if (message) {
        await expect(toast).toContainText(message);
    }

    return toast;
}

export async function fillForm(page: Page, formData: Record<string, string>) {
    for (const [label, value] of Object.entries(formData)) {
        const input = page.getByLabel(new RegExp(label, 'i'));
        if (await input.isVisible()) {
            await input.fill(value);
        }
    }
}

export async function selectOption(page: Page, label: string, optionText: string) {
    const select = page.getByLabel(new RegExp(label, 'i'));
    await select.click();
    await page.getByRole('option', { name: new RegExp(optionText, 'i') }).click();
}

export async function clickAndWaitForDialog(page: Page, buttonName: string) {
    await page.getByRole('button', { name: new RegExp(buttonName, 'i') }).click();
    await expect(page.getByRole('dialog')).toBeVisible({ timeout: 5000 });
}

export async function submitFormAndWaitForSuccess(page: Page) {
    const submitButton = page.getByRole('button', { name: /simpan|save|submit/i });
    await submitButton.click();

    // Wait for success toast or dialog close
    await page.waitForTimeout(1000);
}

export async function confirmDelete(page: Page) {
    const confirmButton = page.getByRole('button', { name: /hapus|delete|ya|yes|confirm/i });
    await confirmButton.click();

    await page.waitForTimeout(500);
}

// Screenshot helper for debugging
export async function takeScreenshot(page: Page, name: string) {
    await page.screenshot({
        path: `./playwright-report/screenshots/${name}-${Date.now()}.png`,
        fullPage: true
    });
}
