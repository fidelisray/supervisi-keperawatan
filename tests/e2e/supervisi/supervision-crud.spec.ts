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

    // 7. Verifikasi Halaman Detail bersifat Read-Only (Tombol Update Tindak Lanjut tidak lagi ditampilkan di halaman Detail)
    const updateStatusBtn = page.getByRole('button', { name: /Update Tindak Lanjut/i });
    await expect(updateStatusBtn).toBeHidden();

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

    // 10. Verifikasi Modal Edit Insiden: Penguncian Kategori 1 Kali, Modal Konfirmasi, & Status Penanganan Tersembunyi
    await page.getByRole('link', { name: /Edit Logbook/i }).click();
    await expect(page).toHaveURL(/.*supervisi\/\d+\/edit/);

    // Buka modal edit insiden untuk butir insiden pertama
    const editIncidentBtn = page.locator('button[title="Ubah butir kejadian"]').first();
    await editIncidentBtn.click();

    const editIncidentHeading = page.getByRole('heading', { name: /Ubah Butir Kejadian/i });
    await expect(editIncidentHeading).toBeVisible();

    // Verifikasi kontrol Status Penanganan tersedia di modal edit logbook beserta informasi kuota 0/3
    const modalStatusSelect = page.locator('select[x-model="modalData.status"]');
    await expect(modalStatusSelect).toBeVisible();
    await expect(page.getByText(/Perubahan status telah dilakukan/i)).toBeVisible();

    // Verifikasi radio kategori insiden dan status tercentang awal
    const categoryRadios = page.locator('input[name="modal_category"]');
    const radioCount = await categoryRadios.count();
    expect(radioCount).toBeGreaterThan(1);
    await expect(categoryRadios.first()).toBeChecked();

    // Klik opsi kategori kedua (berbeda dari kategori awal)
    await categoryRadios.nth(1).click({ force: true });

    // Verifikasi modal konfirmasi muncul dengan peringatan penggantian hanya 1 kali
    const categoryConfirmModal = page.locator('[role="alertdialog"]').filter({ hasText: /Konfirmasi Perubahan Kategori/i });
    await expect(categoryConfirmModal).toBeVisible();
    await expect(page.getByText(/Penggantian kategori kejadian hanya dapat dilakukan sekali saja, mohon periksa dan pastikan kembali bahwa informasi yang diberikan sudah tepat/i)).toBeVisible();

    // Uji klik "Periksa Kembali" -> modal konfirmasi tertutup dan pilihan kembali ke kategori awal
    await categoryConfirmModal.getByRole('button', { name: /Periksa Kembali/i }).click();
    await expect(categoryConfirmModal).toBeHidden();
    await expect(categoryRadios.first()).toBeChecked();

    // Klik opsi kategori kedua lagi dan konfirmasi perubahan
    await categoryRadios.nth(1).click({ force: true });
    await expect(categoryConfirmModal).toBeVisible();
    await categoryConfirmModal.getByRole('button', { name: /Ya, Ubah Kategori/i }).click();
    await expect(categoryConfirmModal).toBeHidden();
    await expect(categoryRadios.nth(1)).toBeChecked();

    // Verifikasi bug fix: saat berada di kategori kedua, klik kategori ketiga harus tetap memicu modal konfirmasi
    if (radioCount > 2) {
      await categoryRadios.nth(2).click({ force: true });
      await expect(categoryConfirmModal).toBeVisible();
      await expect(page.getByText(/Penggantian kategori kejadian hanya dapat dilakukan sekali saja, mohon periksa dan pastikan kembali bahwa informasi yang diberikan sudah tepat/i)).toBeVisible();

      // Klik "Periksa Kembali" -> pilihan tetap bertahan di kategori kedua
      await categoryConfirmModal.getByRole('button', { name: /Periksa Kembali/i }).click();
      await expect(categoryConfirmModal).toBeHidden();
      await expect(categoryRadios.nth(1)).toBeChecked();

      // Klik kembali ke kategori awal (indeks 0) -> langsung kembali tanpa pop-up modal konfirmasi
      await categoryRadios.first().click({ force: true });
      await expect(categoryConfirmModal).toBeHidden();
      await expect(categoryRadios.first()).toBeChecked();

      // Klik kembali kategori kedua dan konfirmasi perubahan untuk disimpan ke logbook
      await categoryRadios.nth(1).click({ force: true });
      await expect(categoryConfirmModal).toBeVisible();
      await categoryConfirmModal.getByRole('button', { name: /Ya, Ubah Kategori/i }).click();
      await expect(categoryConfirmModal).toBeHidden();
      await expect(categoryRadios.nth(1)).toBeChecked();
    }

    // Ubah status penanganan menjadi Selesai Ditangani (perubahan ke-1 dari 3)
    await modalStatusSelect.selectOption('resolved');

    // Klik Simpan pada modal -> memicu pop-up konfirmasi kuota perubahan status (1 / 3)
    await page.getByRole('button', { name: 'Simpan', exact: true }).click();
    const statusConfirmModal = page.locator('[role="alertdialog"]').filter({ hasText: /Konfirmasi Perubahan Status|Peringatan Batas Maksimal Perubahan Status/i });
    await expect(statusConfirmModal).toBeVisible();
    await expect(statusConfirmModal.getByText(/1 \/ 3/)).toBeVisible();

    // Konfirmasi simpan perubahan status
    await statusConfirmModal.getByRole('button', { name: /Ya, Simpan Perubahan/i }).click();
    await expect(statusConfirmModal).toBeHidden();
    await expect(editIncidentHeading).toBeHidden();

    // Simpan pembaruan logbook ke backend
    const saveLogbookBtn = page.getByRole('button', { name: /Simpan Perubahan Logbook/i });
    await saveLogbookBtn.click({ force: true });

    // Verifikasi sukses dan redirect ke halaman detail laporan
    await expect(page).toHaveURL(/.*supervisi\/\d+$/);
    await expect(page.getByText(/Perubahan logbook supervisi keperawatan berhasil disimpan/i)).toBeVisible();

    // Buka kembali halaman Edit Logbook untuk memverifikasi kategori terkunci permanen dan kuota status tercatat 1/3
    await page.getByRole('link', { name: /Edit Logbook/i }).click();
    await expect(page).toHaveURL(/.*supervisi\/\d+\/edit/);

    // Buka kembali modal insiden
    await page.locator('button[title="Ubah butir kejadian"]').first().click();
    await expect(editIncidentHeading).toBeVisible();

    // Verifikasi teks peringatan terkunci muncul dan seluruh radio kategori berstatus disabled
    await expect(page.getByText(/Kategori insiden telah dikunci dan tidak dapat diubah kembali/i)).toBeVisible();
    for (let i = 0; i < radioCount; i++) {
      await expect(categoryRadios.nth(i)).toBeDisabled();
    }
    await expect(page.getByText(/Perubahan status telah dilakukan 1\/3 kali/i)).toBeVisible();

    // Tutup modal edit insiden
    await page.getByRole('button', { name: /Batal/i }).click();
    await expect(editIncidentHeading).toBeHidden();
  });
});
