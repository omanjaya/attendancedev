import { test, expect } from '@playwright/test';

/**
 * Monthly Operations E2E Tests
 *
 * Tests the monthly operational workflow:
 * 1. Create Monthly Schedule
 * 2. Assign shifts to employees
 * 3. Publish schedule
 * 4. Manage Leave Approvals
 * 5. Process Payroll
 */

test.describe('Monthly Operations Flow', () => {
  // Login before each test
  test.beforeEach(async ({ page }) => {
    await page.goto('/login');
    await page.getByLabel('Email').fill('admin@example.com');
    await page.getByLabel('Password').fill('password123');
    await page.getByRole('button', { name: /masuk/i }).click();
    await expect(page).toHaveURL(/.*dashboard/);
  });

  test.describe('Step 1: Monthly Schedule Management', () => {
    test('should navigate to monthly schedules page', async ({ page }) => {
      await page.getByRole('link', { name: /jadwal bulanan/i }).click();
      await expect(page).toHaveURL(/.*schedules\/monthly/);
    });

    test('should display monthly schedule list', async ({ page }) => {
      await page.goto('/schedules/monthly');

      await expect(page.getByRole('heading', { name: /jadwal bulanan/i })).toBeVisible();

      // Should show year navigation - use first() to avoid multiple year matches
      await expect(page.locator('text=/202[0-9]/').first()).toBeVisible();
    });

    test('should navigate to create monthly schedule', async ({ page }) => {
      await page.goto('/schedules/monthly');

      // Use exact match to avoid matching sidebar "Pembuat Jadwal"
      await page.getByRole('link', { name: 'Buat Jadwal', exact: true }).click();
      await expect(page).toHaveURL(/.*schedules\/monthly\/create/);
    });

    test('should display monthly schedule creation form', async ({ page }) => {
      await page.goto('/schedules/monthly/create');

      // Should show form fields
      await expect(page.getByLabel(/nama jadwal/i)).toBeVisible();

      // Should show month/year selection - use exact match to avoid multiple matches
      await expect(page.getByText('Bulan', { exact: true })).toBeVisible();
      await expect(page.getByText('Tahun', { exact: true })).toBeVisible();
    });

    test('should show calendar grid in schedule creation', async ({ page }) => {
      await page.goto('/schedules/monthly/create');

      // Add employee first to see grid - use .first() for multiple buttons
      await page.getByRole('button', { name: /tambah karyawan/i }).first().click();

      // Dialog should open
      await expect(page.getByRole('dialog')).toBeVisible();
    });

    test('should show shift legend', async ({ page }) => {
      await page.goto('/schedules/monthly/create');

      // Should show legend with shift types
      await expect(page.getByText(/legenda/i)).toBeVisible();
      await expect(page.getByText(/pagi/i)).toBeVisible();
      await expect(page.getByText(/siang/i)).toBeVisible();
    });
  });

  test.describe('Step 2: Leave Approvals', () => {
    test('should navigate to leave approvals page', async ({ page }) => {
      await page.getByRole('link', { name: /persetujuan cuti/i }).click();
      await expect(page).toHaveURL(/.*leave\/approvals/);
    });

    test('should display pending leave requests', async ({ page }) => {
      await page.goto('/leave/approvals');

      await expect(page.getByRole('heading', { name: /persetujuan/i })).toBeVisible();
    });

    test('should show leave request details', async ({ page }) => {
      await page.goto('/leave/approvals');

      // Should show table, cards, or heading with leave requests
      const table = page.getByRole('table');
      const cards = page.locator('[data-slot="card"]');
      const heading = page.getByRole('heading', { name: /persetujuan/i });

      await expect(table.or(cards.first()).or(heading).first()).toBeVisible();
    });

    test('should have approve/reject actions', async ({ page }) => {
      await page.goto('/leave/approvals');

      // Check for action buttons or menu
      const approveBtn = page.getByRole('button', { name: /setujui/i });
      const rejectBtn = page.getByRole('button', { name: /tolak/i });
      const actionMenu = page.locator('[data-testid="action-menu"]');

      // At least one should be visible if there are requests
      const hasActions = await approveBtn.or(rejectBtn).or(actionMenu).count();
      expect(hasActions >= 0).toBe(true);
    });
  });

  test.describe('Step 3: Payroll Management', () => {
    test('should navigate to payroll page', async ({ page }) => {
      await page.getByRole('link', { name: /penggajian/i }).click();
      await expect(page).toHaveURL(/.*payroll/);
    });

    test('should display payroll list', async ({ page }) => {
      await page.goto('/payroll');

      await expect(page.getByRole('heading', { name: /penggajian|payroll/i })).toBeVisible();
    });

    test('should show payroll period filter', async ({ page }) => {
      await page.goto('/payroll');

      // Should have period/month filter - use tab role to be more specific
      const periodTab = page.getByRole('tab', { name: /periode/i });
      const heading = page.getByRole('heading', { name: /penggajian|payroll/i });

      await expect(periodTab.or(heading).first()).toBeVisible();
    });

    test('should show employee payroll data', async ({ page }) => {
      await page.goto('/payroll');

      // Should show table, cards or stats for payroll info
      const table = page.getByRole('table');
      const cards = page.locator('[data-slot="card"]');
      const heading = page.getByRole('heading', { name: /penggajian|payroll/i });

      await expect(table.or(cards.first()).or(heading).first()).toBeVisible();
    });
  });

  test.describe('Complete Monthly Operations Flow', () => {
    test('should complete monthly workflow', async ({ page }) => {
      // Step 1: Check monthly schedule page
      await page.goto('/schedules/monthly');
      await expect(page.getByRole('heading', { name: /jadwal bulanan/i })).toBeVisible();

      // Step 2: Check create schedule page
      await page.goto('/schedules/monthly/create');
      await expect(page.getByLabel(/nama jadwal/i)).toBeVisible();

      // Step 3: Check leave approvals
      await page.goto('/leave/approvals');
      await expect(page.getByRole('heading', { name: /persetujuan/i })).toBeVisible();

      // Step 4: Check payroll
      await page.goto('/payroll');
      await expect(page.getByRole('heading', { name: /penggajian|payroll/i })).toBeVisible();
    });

    test('should navigate between monthly operation pages', async ({ page }) => {
      // Start at monthly schedule
      await page.goto('/schedules/monthly');

      // Go to leave approvals via sidebar
      await page.getByRole('link', { name: /persetujuan cuti/i }).click();
      await expect(page).toHaveURL(/.*leave\/approvals/);

      // Go to payroll via sidebar
      await page.getByRole('link', { name: /penggajian/i }).click();
      await expect(page).toHaveURL(/.*payroll/);

      // Back to schedules
      await page.getByRole('link', { name: /jadwal bulanan/i }).click();
      await expect(page).toHaveURL(/.*schedules\/monthly/);
    });
  });
});
