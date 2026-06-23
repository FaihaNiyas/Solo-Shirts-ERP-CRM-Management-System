import { test, expect } from '@playwright/test'
import { login } from './helpers'

test.describe('Seeded data (owner)', () => {
  test.beforeEach(async ({ page }) => {
    await login(page)
  })

  test('admin users list shows seeded staff', async ({ page }) => {
    await page.goto('/admin/users')
    await expect(page.getByText('tailor1@soloshirts.test')).toBeVisible()
  })

  test('admin branches list shows the HQ branch', async ({ page }) => {
    await page.goto('/admin/branches')
    await expect(page.getByText('Head Office')).toBeVisible()
  })

  test('outstanding receivables lists customers with balances', async ({ page }) => {
    await page.goto('/finance/outstanding')
    // The summary banner shows the grand total in INR.
    await expect(page.getByText('Total Outstanding')).toBeVisible()
    await expect(page.getByText(/₹/).first()).toBeVisible()
  })

  test('approvals inbox lists pending measurement versions', async ({ page }) => {
    await page.goto('/approvals')
    await expect(page.getByText(/Measurement v\d+/).first()).toBeVisible()
  })

  test('reports page exposes the backend report kinds', async ({ page }) => {
    await page.goto('/reports')
    const select = page.locator('select').first()
    await expect(select).toBeVisible()
    // "orders" → "Orders", "finance_summary" → "Finance Summary", etc.
    await expect(select.locator('option', { hasText: 'Orders' })).toHaveCount(1)
  })

  test('customers list renders rows', async ({ page }) => {
    await page.goto('/customers')
    await expect(page.getByRole('heading', { level: 1, name: 'Customers' })).toBeVisible()
    await expect(page.locator('tbody tr').first()).toBeVisible()
  })
})
