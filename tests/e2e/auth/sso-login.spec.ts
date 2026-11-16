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

  test('mencegah pengguna kembali ke halaman login dan mengekspos kredensial saat menekan tombol Back setelah login berhasil', async ({ page }) => {
    const loginResponse = await page.goto('/login');
    expect(loginResponse?.headers()['cache-control']).toContain('no-store');

    const nipInput = page.locator('input#nip');
    const passwordInput = page.locator('input#password');

    await expect(nipInput).toHaveAttribute('autocomplete', 'off');
    await expect(passwordInput).toHaveAttribute('autocomplete', 'off');

    await nipInput.fill('1672010896');
    await passwordInput.fill('123456');

    // Simulasikan pengguna sempat menampilkan kata sandi sebelum menekan tombol masuk
    await page.getByRole('button', { name: /Tampilkan kata sandi/i }).click();
    await expect(passwordInput).toHaveAttribute('type', 'text');

    await page.getByRole('button', { name: /Masuk ke Sistem|Masuk ke Aplikasi/i }).click();
    await page.waitForURL((url) => !url.pathname.includes('/login'));

    // Tekan tombol Back di browser setelah berhasil login
    await page.goBack();

    // Pengguna tidak boleh kembali melihat form login maupun kredensial yang diketik sebelumnya
    await expect(page).not.toHaveURL(/.*\/login/);
    await expect(page.locator('input#password')).toHaveCount(0);
  });

  test('menolak percobaan SQL Injection pada form login tanpa membocorkan error database', async ({ page }) => {
    await page.goto('/login');

    await page.locator('input#nip').fill("5508150824' OR '1'='1");
    await page.locator('input#password').fill("' OR '1'='1 --");
    await page.getByRole('button', { name: /Masuk ke Sistem|Masuk ke Aplikasi/i }).click();

    await expect(page).toHaveURL(/.*\/login/);
    const alertBox = page.locator('[role="alert"]');
    await expect(alertBox).toBeVisible();
    await expect(alertBox).toContainText(/NIP atau kata sandi SSO yang Anda masukkan tidak sesuai/i);
  });

  test('merender token CSRF yang sinkron dan menolak permintaan POST login lintas situs dengan token tidak valid (HTTP 419)', async ({ page }) => {
    await page.goto('/login');

    const metaToken = await page.locator('meta[name="csrf-token"]').getAttribute('content');
    const inputToken = await page.locator('form input[name="_token"]').inputValue();

    expect(metaToken).toBeTruthy();
    expect(inputToken).toBe(metaToken);

    const forgedResponse = await page.request.post('/login', {
      headers: {
        'Sec-Fetch-Site': 'cross-site',
        Origin: 'https://attacker.example.com',
      },
      form: {
        _token: 'forged-invalid-csrf-token',
        nip: '1672010896',
        password: '123456',
      },
    });

    expect(forgedResponse.status()).toBe(419);
  });
});
