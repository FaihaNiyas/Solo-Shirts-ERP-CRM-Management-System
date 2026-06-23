import { test, expect } from '@playwright/test'
import { login, OWNER, TAILOR } from './helpers'

test.describe('Authentication', () => {
  test('logs in as the owner and reaches the dashboard', async ({ page }) => {
    await login(page, OWNER)
    await expect(page).toHaveURL(/\/dashboard/)
    // The shell brand is always present once authenticated.
    await expect(page.getByText('Solo Shirts').first()).toBeVisible()
  })

  test('rejects invalid credentials with an inline error', async ({ page }) => {
    await page.goto('/login')
    await page.locator('#email').fill('owner@soloshirts.test')
    await page.locator('#password').fill('wrong-password')
    await page.getByRole('button', { name: 'Sign in' }).click()
    // Stays on the login page and surfaces an error message.
    await expect(page).toHaveURL(/\/login/)
    await expect(page.getByText(/invalid|failed|incorrect|credentials/i)).toBeVisible()
  })

  test('logs in as a tailor (staff role)', async ({ page }) => {
    await login(page, TAILOR)
    await expect(page).not.toHaveURL(/\/login/)
  })
})
