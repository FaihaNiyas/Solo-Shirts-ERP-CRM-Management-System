import { test, expect } from '@playwright/test'
import { login, apiToken, API_BASE, OWNER, FRONT_DESK } from './helpers'

/**
 * FE-018 — the active branch lives on the token (active_branch_id), not on a
 * client-injected X-Branch-Id header. switch-branch re-scopes subsequent
 * authenticated reads; the UI never sends a branch header.
 */
test.describe('Branch context', () => {
  test('switch-branch succeeds and the re-scoped token still reads', async ({ request }) => {
    // apiToken() already calls switch-branch to the user's home branch.
    const token = await apiToken(request, OWNER)
    const me = await request.get(`${API_BASE}/api/v1/auth/me`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    expect(me.ok()).toBeTruthy()
    const body = await me.json()
    expect(body.success).toBe(true)
    // Reads work after the branch was activated on the token.
    const customers = await request.get(`${API_BASE}/api/v1/customers?per_page=1`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    expect(customers.ok()).toBeTruthy()
  })

  test('the FE never sends an X-Branch-Id header on authenticated calls', async ({ page }) => {
    const branchHeaders: string[] = []
    page.on('request', (req) => {
      const h = req.headers()
      if (h['x-branch-id']) branchHeaders.push(req.url())
    })
    await login(page, FRONT_DESK)
    await page.goto('/orders')
    await page.waitForLoadState('networkidle')
    expect(branchHeaders).toEqual([])
  })
})
