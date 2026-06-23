import { test, expect } from '@playwright/test'
import { login, OWNER, TAILOR } from './helpers'

/**
 * RBAC management (role + permission CRUD). Owner can reach the Roles and
 * Permissions screens and see the seeded data; a non-admin role cannot.
 * Drives the real UI against the live backend.
 */
test.describe('Admin · Roles & Permissions', () => {
  test('owner sees the Roles screen with seeded roles', async ({ page }) => {
    await login(page, OWNER)
    await page.goto('/admin/roles')
    await expect(page.getByRole('heading', { name: 'Roles' })).toBeVisible()
    await expect(page.getByRole('button', { name: 'Add Role' })).toBeVisible()
    // A seeded system role is listed and tagged System (the cell holds the name
    // plus an inline "System" badge, so match by substring not exact).
    await expect(page.getByRole('cell', { name: 'Tailor' }).first()).toBeVisible()
    await expect(page.getByText('System').first()).toBeVisible()
  })

  test('owner sees the Permissions catalogue', async ({ page }) => {
    await login(page, OWNER)
    await page.goto('/admin/permissions')
    await expect(page.getByRole('heading', { name: 'Permissions' })).toBeVisible()
    await expect(page.getByText('orders.view', { exact: true })).toBeVisible()
  })

  test('non-admin (Tailor) is denied the Roles screen', async ({ page }) => {
    await login(page, TAILOR)
    await page.goto('/admin/roles')
    await expect(page.getByText('Owner or Admin access required')).toBeVisible()
    await expect(page.getByRole('button', { name: 'Add Role' })).toHaveCount(0)
  })
})
