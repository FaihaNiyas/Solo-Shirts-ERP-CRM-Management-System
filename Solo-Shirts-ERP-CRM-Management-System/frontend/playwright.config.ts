import { defineConfig, devices } from '@playwright/test'

/**
 * E2E config for the Solo Shirts ERP frontend.
 *
 * Assumes both servers are already running and the demo data is seeded:
 *   1. Backend:  php artisan serve --port=8000   (MySQL up; `php artisan db:seed` + `db:seed --class=DemoDataSeeder`)
 *   2. Frontend: npm run dev                      (on :3000, with NEXT_PUBLIC_API_URL=http://localhost:8000)
 *
 * Run with:  npm run e2e
 */
export default defineConfig({
  testDir: './e2e',
  fullyParallel: false,
  workers: 1,
  retries: 1,
  timeout: 40_000,
  expect: { timeout: 10_000 },
  reporter: [['list']],
  use: {
    baseURL: process.env.E2E_BASE_URL ?? 'http://localhost:3000',
    trace: 'on-first-retry',
    actionTimeout: 12_000,
    navigationTimeout: 20_000,
  },
  projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
})
