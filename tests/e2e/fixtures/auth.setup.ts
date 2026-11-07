import { test as setup, expect } from '@playwright/test';
import * as fs from 'fs';
import * as path from 'path';

const authDir = path.resolve('tests/e2e/.auth');
const authFile = path.join(authDir, 'supervisor.json');

setup('authenticate as supervisor', async ({ page }) => {
  // Pastikan folder .auth ada
  if (!fs.existsSync(authDir)) {
    fs.mkdirSync(authDir, { recursive: true });
  }

  // Buka halaman login SSO
  await page.goto('/login');

  // Pastikan halaman login dimuat
  await expect(page.getByText('E-Supervisi Keperawatan')).toBeVisible();

  // Masukkan kredensial supervisor (NIP terdaftar di App ID: 6)
  await page.locator('input#nip').fill('1672010896');
  await page.locator('input#password').fill('123456');
  await page.getByRole('button', { name: /Masuk ke Sistem|Masuk ke Aplikasi/i }).click();

  // Tunggu redirect setelah login berhasil (menuju form supervisi atau dashboard)
  await page.waitForURL((url) => !url.pathname.includes('/login'), { timeout: 10000 });

  // Simpan session cookie & browser state
  await page.context().storageState({ path: authFile });
});
