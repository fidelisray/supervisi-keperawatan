import { test, expect } from '@playwright/test';

test.describe('Modul Administrator - Manajemen Role & Hak Akses (RBAC)', () => {
  test.use({ storageState: 'tests/e2e/.auth/admin.json' });

  test('administrator dapat melihat daftar role, melakukan pencarian NIP, dan filter peran', async ({ page }) => {
    await page.goto('/admin/roles');

    // Verifikasi header halaman
    await expect(page.getByRole('heading', { name: /Manajemen Role & Hak Akses Pengguna/i })).toBeVisible();
    await expect(page.getByText('1. Daftar & Buat Role Baru')).toBeVisible();
    await expect(page.getByText('2. Berikan Hak Akses kepada Pegawai Baru')).toBeVisible();

    // 1. Pencarian Pegawai berdasarkan NIP
    const searchInput = page.locator('input[name="search"]');
    await searchInput.fill('5508150824');
    await page.getByRole('button', { name: /Cari/i }).click();

    // Verifikasi hasil pencarian menampilkan nama administrator Purwanto Ali
    await expect(page.locator('table').getByText('5508150824')).toBeVisible();
    await expect(page.locator('table').getByText(/Purwanto Ali/i)).toBeVisible();

    // 2. Filter berdasarkan Jenis Role (misal Supervisor Keperawatan)
    const roleFilter = page.locator('select[name="role_filter"]');
    await roleFilter.selectOption('1');
    await page.getByRole('button', { name: /Cari/i }).click();

    // Verifikasi tabel menampilkan badge role supervisor
    await expect(page.locator('table').getByText(/Supervisor Keperawatan/i).first()).toBeVisible();
  });
});
