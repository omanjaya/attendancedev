import { test as base, expect, Page } from '@playwright/test';

// Test credentials
export const TEST_CREDENTIALS = {
    employee: {
        email: 'guru1@school.edu',
        password: 'password',
    },
    admin: {
        email: 'admin@school.edu',
        password: 'password',
    },
};

// Employee page URLs
export const EMPLOYEE_PAGES = {
    dashboard: '/employee/dashboard',
    attendance: '/employee/attendance',
    corrections: '/employee/corrections',
};

// Custom test fixture with employee authentication
type EmployeeFixtures = {
    employeePage: Page;
};

export const test = base.extend<EmployeeFixtures>({
    // Pre-authenticated employee page
    employeePage: async ({ page }, use) => {
        // Login as employee
        await page.goto('/login');
        await page.getByLabel('Email').fill(TEST_CREDENTIALS.employee.email);
        await page.getByLabel('Password').fill(TEST_CREDENTIALS.employee.password);
        await page.getByRole('button', { name: /masuk/i }).click();

        // Wait for successful login - should go directly to dashboard now
        await page.waitForURL(/.*dashboard/, { timeout: 15000 });

        await use(page);
    },
});

export { expect };
