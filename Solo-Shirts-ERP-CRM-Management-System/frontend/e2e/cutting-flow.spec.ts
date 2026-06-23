import { test, expect } from '@playwright/test'
import { login, apiToken, API_BASE, CUTTER } from './helpers'

/**
 * FE-002 — the cutting screen drives the REAL cutting/fabric endpoints
 * (allocate-fabric / start-cutting / complete-cutting), not the generic
 * production-transition shortcut. Assumes both servers are running and the
 * demo data is seeded (per playwright.config.ts). Data-dependent steps skip
 * gracefully when the seed has no draft cutting item / fabric roll.
 */
test.describe('Cutting flow (FE-002)', () => {
  test('cutting queue renders for the Cutting Master without an error state', async ({ page }) => {
    await login(page, CUTTER)
    await page.goto('/cutting')
    await expect(page.getByRole('heading', { name: 'Cutting Queue' })).toBeVisible()
    // The queue resolves to a table or the empty state — never the error state.
    await expect(page.getByText('Something went wrong')).toHaveCount(0)
  })

  test('the page calls GET /cutting/queue (not the production board) for the queue', async ({ page }) => {
    const calls: string[] = []
    page.on('request', (r) => {
      if (r.url().includes('/api/v1/')) calls.push(`${r.method()} ${new URL(r.url()).pathname}`)
    })
    await login(page, CUTTER)
    await page.goto('/cutting')
    await page.waitForLoadState('networkidle')
    expect(calls.some((c) => c.includes('/api/v1/cutting/queue'))).toBeTruthy()
  })

  test('allocate-fabric is idempotent — same Idempotency-Key does not duplicate', async ({ request }) => {
    const token = await apiToken(request, CUTTER)
    const headers = (key?: string) => ({
      Authorization: `Bearer ${token}`,
      Accept: 'application/json',
      'Content-Type': 'application/json',
      ...(key ? { 'Idempotency-Key': key } : {}),
    })

    const queue = await (await request.get(`${API_BASE}/api/v1/cutting/queue`, { headers: headers() })).json()
    const items = (queue.data ?? []) as Array<{ id: number; state: string }>
    const draft = items.find((i) => i.state === 'draft')
    const rolls = (await (await request.get(`${API_BASE}/api/v1/inventory/fabric-rolls?per_page=5`, { headers: headers() })).json()).data as Array<{ id: number }>
    test.skip(!draft || !rolls?.length, 'seed has no draft cutting item or fabric roll')

    const key = `e2e-cut-${Date.now()}`
    const body = { roll_id: rolls[0].id, metres: 1 }
    const r1 = await (await request.post(`${API_BASE}/api/v1/cutting/items/${draft!.id}/allocate-fabric`, { headers: headers(key), data: body })).json()
    const r2 = await (await request.post(`${API_BASE}/api/v1/cutting/items/${draft!.id}/allocate-fabric`, { headers: headers(key), data: body })).json()

    expect(r1.success).toBeTruthy()
    // The replayed request returns the SAME allocation — no second reservation.
    expect(r2.data?.id).toBe(r1.data?.id)
  })

  test('an invalid allocation returns the standard error envelope with request_id', async ({ request }) => {
    const token = await apiToken(request, CUTTER)
    const headers = {
      Authorization: `Bearer ${token}`,
      Accept: 'application/json',
      'Content-Type': 'application/json',
      'Idempotency-Key': `e2e-bad-${Date.now()}`,
    }
    const queue = await (await request.get(`${API_BASE}/api/v1/cutting/queue`, { headers })).json()
    const items = (queue.data ?? []) as Array<{ id: number }>
    test.skip(!items?.length, 'seed has no cutting items')

    const res = await request.post(`${API_BASE}/api/v1/cutting/items/${items[0].id}/allocate-fabric`, {
      headers,
      data: { roll_id: 999999999, metres: 1 }, // non-existent roll → validation error
    })
    const body = await res.json()
    expect(res.status()).toBeGreaterThanOrEqual(400)
    expect(body.success).toBe(false)
    expect(typeof body.request_id).toBe('string')
    expect((body.request_id as string).length).toBeGreaterThan(0)
  })
})
