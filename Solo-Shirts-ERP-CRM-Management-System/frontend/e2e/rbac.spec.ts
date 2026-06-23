import { test, expect } from '@playwright/test'
import { login, TAILOR } from './helpers'

test.describe('Role-based access (tailor)', () => {
  test.beforeEach(async ({ page }) => {
    await login(page, TAILOR)
  })

  test('blocks a tailor from the Branches admin screen', async ({ page }) => {
    await page.goto('/admin/branches')
    await expect(page.getByText('Owner access required')).toBeVisible()
    // The management action must not render.
    await expect(page.getByRole('button', { name: 'Add Branch' })).toHaveCount(0)
  })

  test('blocks a tailor from the Users admin screen', async ({ page }) => {
    await page.goto('/admin/users')
    await expect(page.getByText('Owner or Admin access required')).toBeVisible()
  })

  test('blocks a tailor from the audit log', async ({ page }) => {
    await page.goto('/audit')
    await expect(page.getByText('Owner or Admin access required')).toBeVisible()
  })
})
