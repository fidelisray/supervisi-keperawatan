import { test, expect } from '@playwright/test';

test.describe('Analisis & Grafik Supervisi Keperawatan', () => {
  test.use({ storageState: 'tests/e2e/.auth/supervisor.json' });

  test('menampilkan dasbor analitik, filter data, dan dapat mengunduh berkas ekspor CSV', async ({ page }) => {
    await page.goto('/supervisi/analytics');

    // 1. Verifikasi Header & Indikator Halaman
    await expect(page.getByRole('heading', { name: /Analisis & Grafik Kejadian Ruangan/i })).toBeVisible();

    // 2. Verifikasi 4 Kartu Metrik Ringkasan Eksekutif (default filter: Hari Ini)
    await expect(page.getByText('Logbook Hari Ini')).toBeVisible();
    await expect(page.getByText('Total Kejadian Hari Ini')).toBeVisible();
    await expect(page.getByText('Komplain Pasien Hari Ini')).toBeVisible();
    await expect(page.getByText('Terselesaikan', { exact: true })).toBeVisible();

    // 2b. Chip "Semua Data" mengubah judul kartu menjadi total keseluruhan
    await page.getByRole('link', { name: 'Semua Data', exact: true }).click();
    await expect(page).toHaveURL(/preset=all/);
    await expect(page.getByText('Total Logbook')).toBeVisible();
    await expect(page.getByText('Total Kejadian', { exact: true })).toBeVisible();

    // 3. Verifikasi Filter Controls
    await expect(page.getByRole('combobox', { name: /Ruangan \/ Bangsal/i })).toBeVisible();
    await expect(page.getByRole('button', { name: /Terapkan/i })).toBeVisible();

    // 4. Verifikasi Alur Ekspor Data
    const exportBtn = page.getByRole('button', { name: /Ekspor ke Excel \/ CSV/i });
    await expect(exportBtn).toBeVisible();
    await exportBtn.click();

    // Verifikasi modal pilihan opsi ekspor terbuka
    await expect(page.getByText(/Ekspor Laporan Supervisi/i)).toBeVisible();

    // Tangkap event unduhan berkas (download event)
    const downloadPromise = page.waitForEvent('download', { timeout: 15000 });
    await page.getByText(/Data Sesuai Filter Aktif/i).click();

    const download = await downloadPromise;
    expect(download.suggestedFilename()).toMatch(/\.(csv|xlsx?)$/i);
  });
});
