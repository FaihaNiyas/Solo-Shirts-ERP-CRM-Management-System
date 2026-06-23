import { test, expect, devices } from '@playwright/test'
import { login, OWNER, CUTTER } from './helpers'

/**
 * FE-017 — mobile viewport smoke. The shop-floor roles use phones/tablets, so
 * the core surfaces must render and the nav must be reachable at a small
 * viewport. Runs the real UI under an emulated Pixel 5.
 */
test.use({ ...devices['Pixel 5'] })

test.describe('Mobile viewport', () => {
  test('owner can log in and reach the dashboard on a phone', async ({ page }) => {
    await login(page, OWNER)
    await expect(page).toHaveURL(/\/dashboard/)
    // The shell renders its primary heading at mobile width.
    await expect(page.getByRole('heading', { level: 1 })).toBeVisible()
  })

  test('cutter lands on a usable workspace on a phone', async ({ page }) => {
    await login(page, CUTTER)
    // Non-owner roles land on their workspace, not /dashboard.
    await expect(page).not.toHaveURL(/\/login/)
    await expect(page.getByRole('heading', { level: 1 })).toBeVisible()
  })
})
