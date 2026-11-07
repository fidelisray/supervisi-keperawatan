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

  test('siklus penuh: tambah definisi role, ubah hak akses (permissions), assign ke pegawai, ubah peran pegawai, cabut hak akses, dan hapus definisi role', async ({ page }) => {
    const TEST_ROLE_ID = '88';
    const TEST_ROLE_NAME = 'AUDITOR MUTU KLINIS E2E';

    await page.goto('/admin/roles');
    await expect(page.getByRole('heading', { name: /Manajemen Role & Hak Akses Pengguna/i })).toBeVisible();

    // Pembersihan awal jika role uji dari sesi sebelumnya masih tertinggal
    const roleDefTable = page.locator('table').first();
    const existingRoleRow = roleDefTable.locator('tr').filter({ hasText: TEST_ROLE_NAME });
    if (await existingRoleRow.count() > 0) {
      // Hapus assignment pegawai dengan role 88 terlebih dahulu jika ada
      const usersTable = page.locator('table').last();
      const usersWithRole = usersTable.locator('tr').filter({ has: page.locator(`span.rounded-full:has-text("${TEST_ROLE_NAME}")`) });
      const userCount = await usersWithRole.count();
      for (let i = 0; i < userCount; i++) {
        page.once('dialog', async dialog => await dialog.accept());
        await usersWithRole.first().getByRole('button', { name: /Hapus/i }).click();
        await page.waitForLoadState('networkidle');
      }
      page.once('dialog', async dialog => await dialog.accept());
      await existingRoleRow.getByRole('button', { name: /Hapus/i }).click();
      await page.waitForLoadState('networkidle');
    }

    // =========================================================================
    // 1. TAMBAH DEFINISI ROLE BARU (POST /admin/roles/definitions)
    // =========================================================================
    const roleIdInput = page.locator('input#role_id');
    const roleNameInput = page.locator('input#role_name');
    await roleIdInput.scrollIntoViewIfNeeded();
    await roleIdInput.fill(TEST_ROLE_ID);
    await roleNameInput.fill(TEST_ROLE_NAME);

    // Pilih semua hak akses awal pada form definisi role
    const selectAllNewRoleBtn = page.getByRole('button', { name: 'Pilih Semua' }).first();
    await selectAllNewRoleBtn.click();

    // Simpan definisi role baru
    const saveRoleDefBtn = page.getByRole('button', { name: /Simpan Definisi Role/i });
    await saveRoleDefBtn.scrollIntoViewIfNeeded();
    await saveRoleDefBtn.click();

    // Verifikasi alert sukses dan keberadaan role baru di tabel definisi role (Panel 1)
    await expect(page.locator('div[role="alert"]')).toContainText(new RegExp(`Definisi role \\[${TEST_ROLE_ID} - ${TEST_ROLE_NAME}\\]`, 'i'));
    const roleDefRow = roleDefTable.locator('tr').filter({ hasText: TEST_ROLE_NAME });
    await expect(roleDefRow).toBeVisible();

    // =========================================================================
    // 2. UBAH HAK AKSES ROLE (PERMISSIONS) VIA MODAL ALPINE.JS (PUT /admin/roles/definitions/{id}/permissions)
    // =========================================================================
    const editPermBtn = roleDefRow.getByRole('button', { name: /Hak Akses/i });
    await editPermBtn.scrollIntoViewIfNeeded();
    await editPermBtn.click();

    // Verifikasi dialog modal terbuka
    const permModal = page.locator('div[role="dialog"]');
    await expect(permModal).toBeVisible();
    await expect(permModal.getByRole('heading', { name: new RegExp(`Kelola Hak Akses Role: ${TEST_ROLE_NAME}`, 'i') })).toBeVisible();

    // Kosongkan semua izin, kemudian pilih secara selektif 2 hak akses
    await permModal.getByRole('button', { name: /Kosongkan/i }).click();
    await permModal.locator('input[value="create_report"]').check();
    await permModal.locator('input[value="view_analytics"]').check();

    // Simpan perubahan hak akses
    const savePermBtn = permModal.getByRole('button', { name: /Simpan Perubahan Hak Akses/i });
    await savePermBtn.click();

    // Verifikasi modal tertutup, alert sukses tampil, dan jumlah izin pada badge terupdate
    await expect(permModal).not.toBeVisible();
    await expect(page.locator('div[role="alert"]')).toContainText(new RegExp(`Hak akses \\(permission\\) untuk role \\[${TEST_ROLE_ID} - ${TEST_ROLE_NAME}\\] berhasil diperbarui`, 'i'));
    await expect(roleDefRow.getByText(/2 Izin/i)).toBeVisible();

    // =========================================================================
    // 3. ASSIGN ROLE KE PEGAWAI BARU (POST /admin/roles/users)
    // =========================================================================
    const empSearchInput = page.locator('input#assign_employee_search');
    await empSearchInput.scrollIntoViewIfNeeded();
    await empSearchInput.click();

    // Tunggu dropdown autocomplete muncul dan klik pegawai pertama yang belum terdaftar
    const firstEmpOption = page.locator('div[x-show="isOpen"] button').first();
    await firstEmpOption.waitFor({ state: 'visible', timeout: 5000 });
    const selectedEmpText = await firstEmpOption.innerText();
    const nipMatch = selectedEmpText.match(/NIP:\s*(\w+)/);
    const assignedNip = nipMatch ? nipMatch[1] : '';
    await firstEmpOption.click();

    // Pilih role baru yang baru dibuat (88)
    const assignRoleSelect = page.locator('select#assign_role');
    await assignRoleSelect.selectOption(TEST_ROLE_ID);

    // Submit penugasan role pegawai
    const assignBtn = page.getByRole('button', { name: /\+ Daftarkan & Berikan Role Pegawai/i });
    await assignBtn.scrollIntoViewIfNeeded();
    await assignBtn.click();

    // Verifikasi alert sukses dan pegawai muncul di Tabel 3 (Daftar Pengguna Terdaftar)
    await expect(page.locator('div[role="alert"]')).toContainText(/Hak akses untuk .* berhasil disimpan/i);
    const usersTable = page.locator('table').last();
    const assignedUserRow = usersTable.locator('tr').filter({ has: page.locator(`td:has-text("${assignedNip}")`) });
    await expect(assignedUserRow).toBeVisible();
    await expect(assignedUserRow.locator('span.rounded-full')).toContainText(TEST_ROLE_NAME);

    // =========================================================================
    // 4. UBAH ROLE PEGAWAI TERDAFTAR (PATCH /admin/roles/users/{appRole})
    // =========================================================================
    // Ubah peran pegawai dari 88 menjadi 1 (Supervisor Keperawatan)
    const userRoleSelect = assignedUserRow.locator('select[name="app_roles"]');
    await userRoleSelect.scrollIntoViewIfNeeded();
    await userRoleSelect.selectOption('1');

    const saveUserRoleBtn = assignedUserRow.getByRole('button', { name: /Simpan/i });
    await saveUserRoleBtn.click();

    // Verifikasi alert sukses dan badge role pegawai berubah menjadi Supervisor Keperawatan
    await expect(page.locator('div[role="alert"]')).toContainText(/Role untuk .* berhasil diperbarui/i);
    await expect(assignedUserRow.locator('span.rounded-full')).toContainText(/Supervisor Keperawatan/i);

    // =========================================================================
    // 5. CABUT & HAPUS HAK AKSES PEGAWAI (DELETE /admin/roles/users/{appRole})
    // =========================================================================
    page.once('dialog', async dialog => {
      await dialog.accept();
    });
    const deleteUserBtn = assignedUserRow.getByRole('button', { name: /Hapus/i });
    await deleteUserBtn.scrollIntoViewIfNeeded();
    await deleteUserBtn.click();

    // Verifikasi alert sukses dan pegawai terhapus dari Tabel 3
    await expect(page.locator('div[role="alert"]')).toContainText(/berhasil dicabut dan dihapus/i);
    await expect(usersTable.locator('tr').filter({ has: page.locator(`td:has-text("${assignedNip}")`) })).not.toBeVisible();

    // =========================================================================
    // 6. HAPUS DEFINISI ROLE BARU (DELETE /admin/roles/definitions/{roleId})
    // =========================================================================
    page.once('dialog', async dialog => {
      await dialog.accept();
    });
    const deleteRoleBtn = roleDefRow.getByRole('button', { name: /Hapus/i });
    await deleteRoleBtn.scrollIntoViewIfNeeded();
    await deleteRoleBtn.click();

    // Verifikasi alert sukses dan definisi role 88 hilang dari Panel 1
    await expect(page.locator('div[role="alert"]')).toContainText(new RegExp(`Definisi role dengan kode \\[${TEST_ROLE_ID}\\] berhasil dihapus`, 'i'));
    await expect(roleDefTable.locator('tr').filter({ hasText: TEST_ROLE_NAME })).not.toBeVisible();
  });
});
