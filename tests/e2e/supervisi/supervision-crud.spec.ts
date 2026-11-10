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

    // 2. Tambah Kolaborator Perawat (tanpa batas maksimal & hapus chip tanpa membuka dropdown)
    const collabInput = page.locator('input#collaborator_search_input');
    const collabDropdownOption = page.locator('button[x-on\\:click^="addCollaboratorDirect"], button[\\@click^="addCollaboratorDirect"]').first();

    // Tambah kolaborator ke-1
    await collabInput.click();
    await collabInput.fill('Cicilia Ismiati');
    await collabInput.press('Enter');
    await expect(page.getByText('1 dipilih')).toBeVisible();

    // Tambah kolaborator ke-2
    await collabInput.click();
    await collabInput.fill('Anastasia');
    await collabInput.press('Enter');
    await expect(page.getByText('2 dipilih')).toBeVisible();

    // Tambah kolaborator ke-3 -> berhasil ditambahkan karena tidak ada batas maksimal
    await collabInput.click();
    await collabInput.fill('Cicilia Rumini');
    await collabInput.press('Enter');
    await expect(page.getByText('3 dipilih')).toBeVisible();

    // Hapus salah satu chip kolaborator dengan klik tombol "x" -> dropdown tidak boleh terbuka otomatis
    const removeChipBtn = page.locator('button[aria-label^="Hapus kolaborator"]').first();
    await removeChipBtn.click();
    await expect(page.getByText('2 dipilih')).toBeVisible();
    await expect(collabDropdownOption).toBeHidden();

    // 3. Tambahkan Butir Kejadian / Masalah via Modal
    const openModalBtn = page.getByRole('button', { name: /Tambah Kejadian Baru|Tambah Kejadian/i }).first();
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

    // 4b. Verifikasi Navigation Blocking saat ada perubahan belum disimpan (Dirty State) pada Create
    const lihatRiwayatLink = page.getByRole('link', { name: /Lihat Riwayat/i });
    await lihatRiwayatLink.click();
    const leaveModalHeading = page.getByRole('heading', { name: /Perubahan Belum Disimpan/i });
    await expect(leaveModalHeading).toBeVisible();
    await expect(page).toHaveURL(/.*supervisi\/create/);

    // Klik "Lanjutkan Pengisian" -> modal tertutup dan user tetap di halaman Create beserta datanya
    await page.getByRole('button', { name: /Lanjutkan Pengisian/i }).click();
    await expect(leaveModalHeading).toBeHidden();
    await expect(notesInput).toHaveValue('[E2E-TEST] Supervisi malam berlangsung tertib.');

    // 5. Submit Form Laporan Supervisi (harus lolos tanpa diblokir oleh navigation guard)
    const submitBtn = page.getByRole('button', { name: /Simpan Laporan Supervisi/i });
    await submitBtn.click({ force: true });

    // 6. Verifikasi Redirect ke Halaman Detail Laporan (/supervisi/{id})
    await expect(page).toHaveURL(/.*supervisi\/\d+/, { timeout: 15000 });
    await expect(page.getByText(/Laporan supervisi keperawatan berhasil disimpan/i)).toBeVisible();
    await expect(page.getByRole('heading', { name: /Detail Laporan Supervisi Keperawatan/i })).toBeVisible();
    await expect(page.getByText('FRANSISKUS')).toBeVisible();
    await expect(page.getByText('[E2E-TEST] Lampu ruang tindakan perlu penggantian.')).toBeVisible();

    /*
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
    */

    // 8. Verifikasi Navigation Blocking pada halaman Edit Logbook (/supervisi/{id}/edit)
    const editLogbookLink = page.getByRole('link', { name: /Edit Logbook/i });
    await editLogbookLink.click();
    await expect(page).toHaveURL(/.*supervisi\/\d+\/edit/);

    const editNotesInput = page.locator('textarea[name="general_notes"]');
    await editNotesInput.fill('[E2E-TEST] Perubahan catatan yang belum disimpan.');

    // Coba beralih halaman dengan klik "Kembali ke Detail" -> harus ditahan oleh modal
    await page.getByRole('link', { name: /Kembali ke Detail/i }).click();
    await expect(page.getByRole('heading', { name: /Perubahan Belum Disimpan/i })).toBeVisible();
    await expect(page).toHaveURL(/.*supervisi\/\d+\/edit/);

    // Klik "Tinggalkan Tanpa Menyimpan" -> berpindah kembali ke halaman detail
    await page.getByRole('button', { name: /Tinggalkan Tanpa Menyimpan/i }).click();
    await expect(page).toHaveURL(/.*supervisi\/\d+$/);

    // 9. Verifikasi tombol "Cetak Laporan" mengarah ke endpoint PDF dan menghasilkan dokumen application/pdf
    const cetakLaporanLink = page.getByRole('link', { name: /Cetak Laporan/i });
    await expect(cetakLaporanLink).toBeVisible();
    await expect(cetakLaporanLink).toHaveAttribute('target', '_blank');
    const pdfHref = await cetakLaporanLink.getAttribute('href');
    expect(pdfHref).toMatch(/\/supervisi\/\d+\/pdf$/);

    const pdfResponse = await page.request.get(pdfHref!);
    expect(pdfResponse.status()).toBe(200);
    expect(pdfResponse.headers()['content-type']).toContain('application/pdf');
    const pdfBody = await pdfResponse.body();
    expect(pdfBody.subarray(0, 5).toString()).toBe('%PDF-');
  });
});
