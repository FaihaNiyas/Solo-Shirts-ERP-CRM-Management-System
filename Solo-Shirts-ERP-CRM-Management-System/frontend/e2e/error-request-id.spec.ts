import { test, expect } from '@playwright/test'
import { apiToken, API_BASE, OWNER, FRONT_DESK } from './helpers'

/**
 * FE-024 — every error envelope carries a request_id so the UI ErrorDrawer can
 * surface it for support. Verifies the contract at the API boundary.
 */
test.describe('Error envelope — request_id', () => {
  test('404 on a missing resource returns a request_id', async ({ request }) => {
    const token = await apiToken(request, OWNER)
    const res = await request.get(`${API_BASE}/api/v1/customers/999999999`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    expect(res.status()).toBe(404)
    const body = await res.json()
    expect(body.success).toBe(false)
    expect(body.request_id ?? res.headers()['x-request-id']).toBeTruthy()
  })

  test('422 on a bad payload returns request_id + field errors', async ({ request }) => {
    const token = await apiToken(request, FRONT_DESK)
    const res = await request.post(`${API_BASE}/api/v1/orders`, {
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'Idempotency-Key': `e2e-bad-${token.slice(-6)}`,
      },
      data: { source: 'walk_in' }, // missing customer_id + items
    })
    expect(res.status()).toBe(422)
    const body = await res.json()
    expect(body.success).toBe(false)
    expect(body.request_id ?? res.headers()['x-request-id']).toBeTruthy()
    expect(body.errors).toBeTruthy()
  })
})
