import { test, expect } from '@playwright/test';

test.describe('SSO Authentication Flow', () => {
  // Selalu jalankan tes autentikasi dalam keadaan bersih tanpa session tersimpan
  test.use({ storageState: { cookies: [], origins: [] } });

  test('menampilkan elemen form login SSO dengan benar', async ({ page }) => {
    await page.goto('/login');

    // Cek header dan identitas aplikasi
    await expect(page.getByText('E-Supervisi Keperawatan')).toBeVisible();
    await expect(page.getByText('Pencatatan Logbook & Monitoring Mutu Asuhan Keperawatan')).toBeVisible();

    // Cek field input NIP & Password
    const nipInput = page.locator('input#nip');
    const passwordInput = page.locator('input#password');
    const submitBtn = page.getByRole('button', { name: /Masuk ke Sistem|Masuk ke Aplikasi/i });

    await expect(nipInput).toBeVisible();
    await expect(passwordInput).toBeVisible();
    await expect(submitBtn).toBeVisible();
  });

  test('menolak login dan menampilkan pesan kesalahan jika kata sandi salah', async ({ page }) => {
    await page.goto('/login');

    await page.locator('input#nip').fill('1672010896');
    await page.locator('input#password').fill('kata_sandi_palsu');
    await page.getByRole('button', { name: /Masuk ke Sistem|Masuk ke Aplikasi/i }).click();

    // Verifikasi alert pesan kesalahan muncul
    const alertBox = page.locator('[role="alert"]');
    await expect(alertBox).toBeVisible();
    await expect(alertBox).toContainText(/NIP atau kata sandi SSO yang Anda masukkan tidak sesuai/i);
  });

  test('mengarahkan pengguna belum login (guest) kembali ke /login saat mengakses rute supervisi', async ({ page }) => {
    await page.goto('/supervisi');

    // Harus diarahkan kembali ke login
    await expect(page).toHaveURL(/.*login/);
    await expect(page.locator('input#nip')).toBeVisible();
  });
});
