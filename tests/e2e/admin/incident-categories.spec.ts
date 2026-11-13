import { test, expect } from '@playwright/test';

test.describe('Modul Administrator - Manajemen Kategori Insiden', () => {
  test.use({ storageState: 'tests/e2e/.auth/admin.json' });

  test('administrator dapat menambah kategori masalah baru, melakukan toggle aktif, dan menghapusnya', async ({ page }) => {
    // Siapkan handler dialog konfirmasi browser untuk aksi hapus
    page.on('dialog', async (dialog) => {
      await dialog.accept();
    });

    await page.goto('/admin/incident-categories');
    await expect(page.getByRole('heading', { name: /Manajemen Kategori Masalah/i })).toBeVisible();

    const uniqueCategoryName = `[E2E] Kategori-${Date.now().toString().slice(-4)}`;

    // 1. Tambah Kategori Baru via Modal
    await page.getByRole('button', { name: /\+ Tambah Kategori Baru/i }).click();
    const createCategoryModal = page.locator('div[role="dialog"]').filter({ has: page.locator('#create-category-modal-title') });
    await expect(createCategoryModal).toBeVisible();

    await createCategoryModal.locator('input#incident_category').fill(uniqueCategoryName);
    await createCategoryModal.locator('textarea#description').fill('Kategori masalah sementara dibuat oleh automation testing Playwright.');
    await createCategoryModal.locator('select#is_active').selectOption('true');

    await createCategoryModal.getByRole('button', { name: /\+ Simpan Kategori Baru/i }).click();

    // Verifikasi kategori baru muncul di daftar tabel
    await expect(page.getByText(uniqueCategoryName, { exact: true })).toBeVisible();

    // 2. Toggle Status Aktif / Sembunyikan pada baris spesifik kategori tersebut
    const categoryCard = page.locator('div[x-data*="editing"]').filter({ hasText: uniqueCategoryName });
    await expect(categoryCard.getByText('Aktif', { exact: true })).toBeVisible();

    const toggleBtn = categoryCard.getByRole('button', { name: /Sembunyikan/i });
    await expect(toggleBtn).toBeVisible();
    await toggleBtn.click();

    // Verifikasi badge status berubah menjadi Disembunyikan
    await expect(categoryCard.getByText('Disembunyikan', { exact: true })).toBeVisible();

    // 3. Hapus Kategori Uji untuk Menjaga Kebersihan Database
    const deleteBtn = categoryCard.getByRole('button', { name: /Hapus/i });
    await expect(deleteBtn).toBeVisible();
    await deleteBtn.click();

    // Verifikasi kategori terhapus dari tampilan
    await expect(page.getByText(uniqueCategoryName, { exact: true })).not.toBeVisible();
  });
});
