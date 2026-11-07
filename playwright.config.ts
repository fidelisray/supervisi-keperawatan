import { defineConfig, devices } from '@playwright/test';

/**
 * Konfigurasi Playwright E2E Testing
 * Supervisi Keperawatan RS. St. Elisabeth Semarang
 */
export default defineConfig({
  testDir: './tests/e2e',
  timeout: 30 * 1000,
  expect: {
    timeout: 5000,
  },
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: 1,
  reporter: [
    ['html', { open: 'never' }],
    ['list'],
  ],
  use: {
    baseURL: process.env.APP_URL || 'http://supervisi-keperawatan.test',
    ignoreHTTPSErrors: true,
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
  },
  projects: [
    // 1. Setup Auth Fixture: Login sekali & simpan session state
    {
      name: 'setup',
      testMatch: /.*\.setup\.ts/,
    },
    // 2. Desktop Chrome (Viewport 1366x768)
    {
      name: 'Desktop Chrome',
      use: {
        ...devices['Desktop Chrome'],
        viewport: { width: 1366, height: 768 },
      },
      dependencies: ['setup'],
    },
    // 3. Mobile Chrome (Pixel 5 Viewport)
    {
      name: 'Mobile Chrome',
      use: {
        ...devices['Pixel 5'],
      },
      dependencies: ['setup'],
    },
  ],
});
