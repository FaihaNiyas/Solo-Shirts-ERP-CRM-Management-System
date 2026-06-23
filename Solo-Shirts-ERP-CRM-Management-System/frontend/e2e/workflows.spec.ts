import { test, expect } from '@playwright/test'
import { login, firstCustomerId } from './helpers'

test.describe('Workflows (owner)', () => {
  test('global search finds seeded orders', async ({ page }) => {
    await login(page)
    // Open the palette via its header trigger (more deterministic than the
    // Ctrl+K shortcut, which can race page hydration).
    await page.getByRole('button', { name: 'Search customers, orders, invoices' }).click()
    const input = page.getByPlaceholder('Search customers, orders, invoices…')
    await expect(input).toBeVisible()
    await input.fill('DEMO-ORD')
    await expect(page.getByText(/DEMO-ORD-\d+/).first()).toBeVisible()
  })

  test('customer 360 shows tabs and order history', async ({ page, request }) => {
    const id = await firstCustomerId(request)
    await login(page)
    await page.goto(`/customers/${id}`)
    await expect(page.getByText('Total Orders')).toBeVisible()
    // Tab strip — exact match so we don't also hit the "…orders…" search button.
    await expect(page.getByRole('button', { name: 'Orders', exact: true })).toBeVisible()
    await expect(page.getByRole('button', { name: 'Balance', exact: true })).toBeVisible()
    // Switch to the Balance tab — outstanding panel renders.
    await page.getByRole('button', { name: 'Balance', exact: true }).click()
    await expect(page.getByText('Total Outstanding')).toBeVisible()
  })

  test('admin can create a user (write + idempotency path)', async ({ page }) => {
    await login(page)
    await page.goto('/admin/users')
    await page.getByRole('button', { name: 'Add User' }).click()

    const unique = `e2e_${Date.now()}@soloshirts.test`
    await page.getByLabel('Full Name').fill('E2E Test User')
    await page.getByLabel('Email').fill(unique)
    await page.getByLabel('Initial Password').fill('password123')

    await page.getByRole('button', { name: 'Create User' }).click()
    await expect(page.getByText('User created')).toBeVisible()
    await expect(page.getByText(unique)).toBeVisible()
  })
})
