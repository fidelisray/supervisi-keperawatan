import { test, expect } from '@playwright/test';

test.describe('Supervisi Keperawatan - Riwayat Laporan & Filter Monitoring', () => {
  test.use({ storageState: 'tests/e2e/.auth/supervisor.json' });

  test('menampilkan metrik statistik dan formulir filter dengan benar', async ({ page }) => {
    await page.goto('/supervisi');

    // Cek header halaman
    await expect(page.getByRole('heading', { name: /Monitoring Supervisi Ruang Perawatan/i })).toBeVisible();

    // Cek 4 kartu statistik
    await expect(page.getByText('Laporan Hari Ini')).toBeVisible();
    await expect(page.getByText('Total Kejadian')).toBeVisible();
    await expect(page.getByText('Komplain Pasien')).toBeVisible();
    await expect(page.getByText('Terselesaikan')).toBeVisible();

    // Cek filter bar
    await expect(page.locator('select#room')).toBeVisible();
    await expect(page.locator('input#date_from')).toBeVisible();
    await expect(page.locator('input#date_to')).toBeVisible();
    await expect(page.getByRole('button', { name: /Filter/i })).toBeVisible();
  });

  test('dapat memfilter riwayat berdasarkan pilihan ruangan dan mereset filter', async ({ page }) => {
    await page.goto('/supervisi');

    // Pilih ruangan dari dropdown filter
    const roomSelect = page.locator('select#room');
    await roomSelect.selectOption({ index: 1 }); // Pilih ruangan pertama yang tersedia

    // Submit filter
    await page.getByRole('button', { name: /Filter/i }).click();

    // Verifikasi URL mengandung query string room
    await expect(page).toHaveURL(/.*room=.*/);

    // Verifikasi tombol Reset filter muncul dan dapat diklik
    const resetLink = page.getByRole('link', { name: /Reset/i });
    await expect(resetLink).toBeVisible();
    await resetLink.click();

    // URL kembali bersih tanpa parameter filter
    await expect(page).toHaveURL(/.*supervisi$/);
  });
});
