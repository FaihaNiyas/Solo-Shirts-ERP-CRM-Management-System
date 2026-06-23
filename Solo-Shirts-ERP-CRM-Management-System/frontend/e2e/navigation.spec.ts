import { test, expect } from '@playwright/test'
import { login } from './helpers'

// Each major screen renders behind the authenticated shell with an <h1> title.
const ROUTES: { path: string; heading: string }[] = [
  { path: '/customers', heading: 'Customers' },
  { path: '/orders', heading: 'Orders' },
  { path: '/production', heading: 'Production Board' },
  { path: '/finance/outstanding', heading: 'Outstanding Balances' },
  { path: '/reports', heading: 'Reports' },
  { path: '/audit', heading: 'Audit Log' },
  { path: '/admin/users', heading: 'Users' },
  { path: '/admin/branches', heading: 'Branches' },
  { path: '/documents', heading: 'Documents' },
  { path: '/approvals', heading: 'Approvals' },
]

test.describe('Navigation (owner)', () => {
  test.beforeEach(async ({ page }) => {
    await login(page)
  })

  for (const { path, heading } of ROUTES) {
    test(`renders ${path}`, async ({ page }) => {
      await page.goto(path)
      await expect(page.getByRole('heading', { level: 1, name: heading })).toBeVisible()
    })
  }
})
