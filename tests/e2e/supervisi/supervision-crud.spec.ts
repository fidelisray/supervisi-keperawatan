import { test, expect } from '@playwright/test';

test.describe('Supervisi Keperawatan - Siklus Pembuatan & Resolusi Insiden (CRUD)', () => {
  test.use({ storageState: 'tests/e2e/.auth/supervisor.json' });

  test('dapat membuat laporan supervisi lengkap hingga tersimpan di database dan update status insiden', async ({ page }) => {
    await page.goto('/supervisi/create');
    await expect(page.getByRole('heading', { name: /Logbook Supervisi Ruang Perawatan/i })).toBeVisible();

    // 1. Pilih Ruangan melalui combobox autocomplete
    const roomInput = page.locator('input#room_search_input');
    await roomInput.click();
    await roomInput.fill('FRANSISKUS');
    await roomInput.press('Enter');

    // 2. Tambah Kolaborator Perawat
    const collabInput = page.locator('input#collaborator_search_input');
    await collabInput.click();
    await collabInput.fill('Cicilia');
    // Tekan tombol Tambah Kolaborator atau Enter
    const addCollabBtn = page.getByRole('button', { name: /\+ Tambah Kolaborator/i });
    await addCollabBtn.click();

    // Verifikasi kolaborator berhasil ditambahkan (1 dipilih)
    await expect(page.getByText('1 dipilih')).toBeVisible();

    // Tutup dropdown kolaborator jika masih aktif
    await page.keyboard.press('Escape');

    // 3. Tambahkan Butir Kejadian / Masalah via Modal
    const openModalBtn = page.getByRole('button', { name: /Tambah Kejadian Baru|\+ Tambah Kejadian/i }).first();
    await openModalBtn.click({ force: true });

    const modalHeading = page.getByRole('heading', { name: /Tambah Kejadian Ruangan/i });
    await expect(modalHeading).toBeVisible();

    // Pilih kategori masalah (radio button pertama)
    const radioKategori = page.locator('input[type="radio"]').first();
    await radioKategori.check({ force: true });

    // Isi deskripsi kejadian
    const descInput = page.getByPlaceholder(/Jelaskan secara ringkas dan objektif/i);
    await descInput.fill('[E2E-TEST] Lampu ruang tindakan perlu penggantian.');

    // Simpan butir kejadian ke form logbook
    const addIncidentBtn = page.getByRole('button', { name: /Tambahkan ke Logbook/i });
    await addIncidentBtn.click();
    await expect(modalHeading).not.toBeVisible();

    // 4. Isi Catatan Khusus Evaluasi
    const notesInput = page.locator('textarea[name="general_notes"]');
    await notesInput.fill('[E2E-TEST] Supervisi malam berlangsung tertib.');

    // 5. Submit Form Laporan Supervisi
    const submitBtn = page.getByRole('button', { name: /Simpan Laporan Supervisi/i });
    await submitBtn.click({ force: true });

    // 6. Verifikasi Redirect ke Halaman Detail Laporan (/supervisi/{id})
    await expect(page).toHaveURL(/.*supervisi\/\d+/, { timeout: 15000 });
    await expect(page.getByText(/Laporan supervisi keperawatan berhasil disimpan/i)).toBeVisible();
    await expect(page.getByRole('heading', { name: /Detail Laporan Supervisi Keperawatan/i })).toBeVisible();
    await expect(page.getByText('FRANSISKUS')).toBeVisible();
    await expect(page.getByText('[E2E-TEST] Lampu ruang tindakan perlu penggantian.')).toBeVisible();

    // 7. Update Status Insiden: Transisi dari Sedang Ditangani -> Selesai Ditangani
    const updateStatusBtn = page.getByRole('button', { name: /Update Tindak Lanjut/i });
    await updateStatusBtn.scrollIntoViewIfNeeded();
    await updateStatusBtn.click();

    const statusSelect = page.locator('select[name="status"]');
    await expect(statusSelect).toBeVisible();
    await statusSelect.selectOption('resolved');

    const saveStatusBtn = page.getByRole('button', { name: /Simpan Status Insiden/i });
    await saveStatusBtn.click();

    // Verifikasi badge status berubah menjadi Selesai
    await expect(page.getByText(/Selesai/i).first()).toBeVisible();
  });
});
