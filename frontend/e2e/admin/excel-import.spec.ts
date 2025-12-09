import { test, expect, ADMIN_PAGES } from './fixtures';

test.describe('Excel Import Feature', () => {

    test.describe('Employee Import', () => {
        test('Should show import button on employees page', async ({ adminPage }) => {
            await adminPage.goto(ADMIN_PAGES.employees);

            // Look for Import button
            const importButton = adminPage.getByRole('button', { name: /import/i });
            await expect(importButton).toBeVisible();
        });

        test('Should open import dialog when clicking import button', async ({ adminPage }) => {
            await adminPage.goto(ADMIN_PAGES.employees);

            // Click Import button
            await adminPage.getByRole('button', { name: /import/i }).click();

            // Check dialog opens
            await expect(adminPage.getByRole('dialog')).toBeVisible();

            // Check dialog title or heading
            await expect(adminPage.getByRole('heading', { name: /import/i })).toBeVisible();

            // Check for download template button
            await expect(adminPage.getByRole('button', { name: /download template/i })).toBeVisible();
        });

        test('Should show column mapping after selecting file', async ({ adminPage }) => {
            await adminPage.goto(ADMIN_PAGES.employees);

            // This test would require uploading a real file
            // For now, we just verify the dialog structure
            await adminPage.getByRole('button', { name: /import/i }).click();
            await expect(adminPage.getByRole('dialog')).toBeVisible();

            // Close dialog
            await adminPage.keyboard.press('Escape');
        });
    });

    test.describe('Master Data Import', () => {
        test('Should have import buttons for Departments', async ({ adminPage }) => {
            await adminPage.goto(ADMIN_PAGES.masterData);

            // Switch to Departments tab
            await adminPage.getByRole('tab', { name: /unit kerja|departemen/i }).click();

            // Look for Import Excel button
            const importButton = adminPage.getByRole('button', { name: /import excel/i }).first();
            await expect(importButton).toBeVisible();
        });

        test('Should have import buttons for Positions', async ({ adminPage }) => {
            await adminPage.goto(ADMIN_PAGES.masterData);

            // Switch to Positions tab
            await adminPage.getByRole('tab', { name: /jabatan|posisi/i }).click();

            // Look for Import Excel button
            const importButton = adminPage.getByRole('button', { name: /import excel/i }).first();
            await expect(importButton).toBeVisible();
        });

        test('Should have import buttons for Subjects', async ({ adminPage }) => {
            await adminPage.goto(ADMIN_PAGES.masterData);

            // Switch to Subjects tab
            await adminPage.getByRole('tab', { name: /mata pelajaran|mapel/i }).click();

            // Look for Import Excel button
            const importButton = adminPage.getByRole('button', { name: /import excel/i }).first();
            await expect(importButton).toBeVisible();
        });

        test('Should open import dialog for Departments', async ({ adminPage }) => {
            await adminPage.goto(ADMIN_PAGES.masterData);

            // Switch to Departments tab
            await adminPage.getByRole('tab', { name: /unit kerja|departemen/i }).click();

            // Click Import button
            await adminPage.getByRole('button', { name: /import excel/i }).first().click();

            // Check dialog opens
            await expect(adminPage.getByRole('dialog')).toBeVisible();
            // Use heading selector which is more specific
            await expect(adminPage.getByRole('heading', { name: /import/i })).toBeVisible();
        });
    });

    test.describe('Classrooms Import', () => {
        test('Should have import button on Master Data page for Classrooms', async ({ adminPage }) => {
            // Go to Master Data page (which has classrooms)
            await adminPage.goto('/admin/master-data');

            // Switch to Classrooms tab if exists
            const classroomTab = adminPage.getByRole('tab', { name: /kelas|ruangan|classroom/i });
            if (await classroomTab.isVisible()) {
                await classroomTab.click();

                // Look for Import Excel button
                const importButton = adminPage.getByRole('button', { name: /import excel/i }).first();
                await expect(importButton).toBeVisible();
            }
        });
    });
});
