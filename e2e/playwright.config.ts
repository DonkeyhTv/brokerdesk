import { defineConfig } from '@playwright/test'

export default defineConfig({
  testDir: './tests',
  // The tests share one database and the login rate limit.
  workers: 1,
  timeout: 60_000,
  expect: { timeout: 15_000 },
  reporter: [['list']],
  use: {
    baseURL: 'http://localhost:5173',
    locale: 'fr-BE',
    viewport: { width: 1360, height: 900 },
    trace: 'retain-on-failure',
  },
})
