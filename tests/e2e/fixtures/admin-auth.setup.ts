import { test as setup, expect } from '@playwright/test';
import * as fs from 'fs';
import * as path from 'path';

const authDir = path.resolve('tests/e2e/.auth');
const adminAuthFile = path.join(authDir, 'admin.json');

setup('authenticate as administrator', async ({ page }) => {
  if (!fs.existsSync(authDir)) {
    fs.mkdirSync(authDir, { recursive: true });
  }

  // Buka halaman login SSO
  await page.goto('/login');
  await expect(page.getByText('E-Supervisi Keperawatan')).toBeVisible();

  // Masukkan kredensial administrator (NIP: 5508150824 / Purwanto Ali, App Role: 0)
  await page.locator('input#nip').fill('5508150824');
  await page.locator('input#password').fill('123456');
  await page.getByRole('button', { name: /Masuk ke Sistem|Masuk ke Aplikasi/i }).click();

  // Tunggu redirect setelah login berhasil
  await page.waitForURL((url) => !url.pathname.includes('/login'), { timeout: 10000 });

  // Simpan session cookie & browser state untuk administrator
  await page.context().storageState({ path: adminAuthFile });
});
