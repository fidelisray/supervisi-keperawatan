import { test, expect } from '@playwright/test';

test.describe('Supervisi Keperawatan - Form Pengisian Logbook & Interaksi Modal', () => {
  // Gunakan sesi login supervisor yang disimpan oleh auth.setup.ts
  test.use({ storageState: 'tests/e2e/.auth/supervisor.json' });

  test('dapat membuka form pembuatan logbook supervisi', async ({ page }) => {
    await page.goto('/supervisi/create');

    // Pastikan halaman form supervisi terbuka
    await expect(page).toHaveURL(/.*supervisi\/create/);
    await expect(page.getByRole('heading', { name: /Logbook Supervisi Ruang Perawatan/i })).toBeVisible();

    // Verifikasi tanggal supervisi dan data operasional
    await expect(page.locator('input#report_date')).toBeVisible();
    await expect(page.locator('input#room_search_input')).toBeVisible();
  });

  test('validasi Alpine.js mencegah submit jika ruangan belum dipilih', async ({ page }) => {
    await page.goto('/supervisi/create');

    // Klik tombol submit langsung
    const submitBtn = page.getByRole('button', { name: /Simpan Laporan Supervisi/i });
    await submitBtn.scrollIntoViewIfNeeded();
    await submitBtn.click();

    // Validasi client-side Alpine.js untuk ruangan harus muncul
    await expect(page.getByText(/Ruangan \/ Instalasi perawatan wajib dipilih/i)).toBeVisible();
  });

  test('dapat membuka modal pencatatan kejadian baru dan menambahkan ke logbook', async ({ page }) => {
    await page.goto('/supervisi/create');

    // Klik tombol "Tambah Kejadian Baru"
    const openModalBtn = page.getByRole('button', { name: /Tambah Kejadian Baru/i });
    await openModalBtn.scrollIntoViewIfNeeded();
    await openModalBtn.click();

    // Verifikasi dialog modal terbuka melalui heading modal
    const modalHeading = page.getByRole('heading', { name: /Tambah Kejadian Ruangan/i });
    await expect(modalHeading).toBeVisible();

    // Isi deskripsi kejadian
    const descInput = page.getByPlaceholder(/Jelaskan secara ringkas dan objektif/i);
    await descInput.fill('Uji coba otomasi Playwright: Kendala flow regulator infus.');

    // Klik tombol Tambahkan ke Logbook
    const addBtn = page.getByRole('button', { name: /Tambahkan ke Logbook/i });
    await addBtn.click();

    // Verifikasi modal tertutup dan butir kejadian muncul di daftar
    await expect(modalHeading).not.toBeVisible();
    await expect(page.getByText('Uji coba otomasi Playwright: Kendala flow regulator infus.')).toBeVisible();
    await expect(page.getByText('Total: 1 Butir Kejadian')).toBeVisible();
  });
});
