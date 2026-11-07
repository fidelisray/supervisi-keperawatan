import { test, expect } from '@playwright/test';

test.describe('Otorisasi Role & Keamanan Akses (RBAC)', () => {
  test.describe('Supervisor (Non-Admin)', () => {
    test.use({ storageState: 'tests/e2e/.auth/supervisor.json' });

    test('supervisor dilarang mengakses rute admin (/admin/incident-categories)', async ({ page }) => {
      const response = await page.goto('/admin/incident-categories');
      // Harusnya status HTTP 403 Forbidden
      expect(response?.status()).toBe(403);
    });

    test('supervisor dilarang mengakses rute admin (/admin/roles)', async ({ page }) => {
      const response = await page.goto('/admin/roles');
      // Harusnya status HTTP 403 Forbidden
      expect(response?.status()).toBe(403);
    });

    test('menu navigasi admin tidak tampil di sidebar supervisor', async ({ page }) => {
      await page.goto('/supervisi');

      // Buka menu mobile jika di viewport ponsel
      const mobileNav = page.getByRole('button', { name: /Buka menu navigasi/i });
      if (await mobileNav.isVisible()) {
        await mobileNav.click();
      }

      await expect(page.getByText('Manajemen Kategori Insiden')).not.toBeVisible();
      await expect(page.getByText('Manajemen Role')).not.toBeVisible();
    });
  });

  test.describe('Administrator (Super-Admin)', () => {
    test.use({ storageState: 'tests/e2e/.auth/admin.json' });

    test('administrator dapat mengakses dashboard admin incident categories', async ({ page }) => {
      const response = await page.goto('/admin/incident-categories');
      expect(response?.status()).toBe(200);
      await expect(page.getByRole('heading', { name: /Manajemen Kategori Masalah/i })).toBeVisible();
    });

    test('administrator dapat mengakses dashboard admin roles', async ({ page }) => {
      const response = await page.goto('/admin/roles');
      expect(response?.status()).toBe(200);
      await expect(page.getByRole('heading', { name: /Manajemen Role & Hak Akses/i })).toBeVisible();
    });
  });

  test.describe('Alur Logout Sesi Mandiri', () => {
    // Jalankan dalam sesi login terisolasi agar tidak menghancurkan session shared fixture
    test.use({ storageState: { cookies: [], origins: [] } });

    test('dapat login lalu logout dan diarahkan kembali ke form login', async ({ page }) => {
      await page.goto('/login');
      await page.locator('input#nip').fill('1672010896');
      await page.locator('input#password').fill('123456');
      await page.getByRole('button', { name: /Masuk ke Sistem|Masuk ke Aplikasi/i }).click();

      await page.waitForURL((url) => !url.pathname.includes('/login'));

      // Buka menu hamburger jika di mobile viewport
      const mobileNav = page.getByRole('button', { name: /Buka menu navigasi/i });
      if (await mobileNav.isVisible()) {
        await mobileNav.click();
      }

      // Klik tombol logout/keluar
      const logoutBtn = page.getByRole('button', { name: /Keluar/i }).first();
      await logoutBtn.scrollIntoViewIfNeeded();
      await logoutBtn.click();

      // Harus dialihkan kembali ke login
      await expect(page).toHaveURL(/.*login/);
      await expect(page.locator('input#nip')).toBeVisible();

      // Pastikan rute supervisi tidak dapat diakses lagi
      await page.goto('/supervisi/create');
      await expect(page).toHaveURL(/.*login/);
    });
  });
});
