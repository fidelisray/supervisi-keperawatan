import fs from 'fs';
import path from 'path';
import { spawn } from 'child_process';

const envPath = path.resolve('.env');

if (!fs.existsSync(envPath)) {
  console.error('File .env tidak ditemukan.');
  process.exit(1);
}

let originalEnv = fs.readFileSync(envPath, 'utf8');
const dbMatch = originalEnv.match(/DB_DATABASE=([^\r\n]+)/);
const originalDb = dbMatch ? dbMatch[1].trim() : 'nurse_supervision_auth';
const targetDb = 'nurse_supervision_test';

let restored = false;
function restoreEnv() {
  if (restored) return;
  restored = true;
  console.log(`\n[E2E Runner] Mengembalikan DB_DATABASE ke ${originalDb}...`);
  const currentEnv = fs.readFileSync(envPath, 'utf8');
  const restoredEnv = currentEnv.replace(/DB_DATABASE=[^\r\n]+/, `DB_DATABASE=${originalDb}`);
  fs.writeFileSync(envPath, restoredEnv, 'utf8');
  console.log('[E2E Runner] File .env berhasil dikembalikan.');
}

process.on('SIGINT', () => {
  restoreEnv();
  process.exit(130);
});
process.on('SIGTERM', () => {
  restoreEnv();
  process.exit(143);
});
process.on('uncaughtException', (err) => {
  console.error(err);
  restoreEnv();
  process.exit(1);
});

try {
  console.log(`[E2E Runner] Mengalihkan DB_DATABASE ke ${targetDb} di .env...`);
  const testEnv = originalEnv.replace(/DB_DATABASE=[^\r\n]+/, `DB_DATABASE=${targetDb}`);
  fs.writeFileSync(envPath, testEnv, 'utf8');

  // Menjalankan migrasi pgsql ke database testing
  console.log('[E2E Runner] Menjalankan migrasi database testing (pgsql)...');
  const migrateCmd = process.platform === 'win32' ? 'php.exe' : 'php';
  const migrateArgs = ['artisan', 'migrate', '--path=database/migrations/pgsql', '--force'];

  const migrateProc = spawn(migrateCmd, migrateArgs, { stdio: 'inherit' });
  migrateProc.on('close', (migrateCode) => {
    if (migrateCode !== 0) {
      console.warn('[E2E Runner] Peringatan: Migrasi pgsql selesai dengan kode non-nol.');
    }

    // Menjalankan Playwright test
    const userArgs = process.argv.slice(2);
    const npxCmd = process.platform === 'win32' ? 'npx.cmd' : 'npx';
    const pwArgs = ['playwright', 'test', ...userArgs];

    console.log(`[E2E Runner] Menjalankan Playwright: ${npxCmd} ${pwArgs.join(' ')}`);
    const pwProc = spawn(npxCmd, pwArgs, { stdio: 'inherit' });

    pwProc.on('close', (code) => {
      restoreEnv();
      process.exit(code ?? 0);
    });
  });
} catch (error) {
  console.error('[E2E Runner] Terjadi galat:', error);
  restoreEnv();
  process.exit(1);
}
