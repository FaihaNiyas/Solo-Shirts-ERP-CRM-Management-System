import { test, expect } from '@playwright/test'
import { apiToken, API_BASE, FRONT_DESK, demoCustomerId, approvedVersionId } from './helpers'

/**
 * FE-007 — a double-submit with the same Idempotency-Key must not create a
 * duplicate record. Drives the backend directly (the FE sends a stable key per
 * form via useStableIdempotencyKey). Assumes both servers + demo seed are up.
 */
test.describe('Idempotency — double-submit', () => {
  test('order create replays on the same Idempotency-Key (no duplicate)', async ({ request }) => {
    const token = await apiToken(request, FRONT_DESK)
    const customerId = await demoCustomerId(request)
    const versionId = await approvedVersionId(request, customerId, FRONT_DESK)
    test.skip(!customerId || !versionId, 'seed has no customer / approved version')

    const key = `e2e-idem-${customerId}-${versionId}`
    const payload = {
      customer_id: customerId,
      source: 'walk_in',
      delivery_mode: 'pickup',
      items: [{ product_type: 'shirt', quantity: 1, measurement_version_id: versionId }],
    }
    const headers = {
      Authorization: `Bearer ${token}`,
      Accept: 'application/json',
      'Content-Type': 'application/json',
      'Idempotency-Key': key,
    }

    const r1 = await (await request.post(`${API_BASE}/api/v1/orders`, { headers, data: payload })).json()
    const r2 = await (await request.post(`${API_BASE}/api/v1/orders`, { headers, data: payload })).json()

    expect(r1.success).toBeTruthy()
    // Same key + same body → replay of the first order, not a second one.
    expect(r2.data?.id).toBe(r1.data?.id)
  })

  test('same key + different body is rejected with IDEMPOTENCY_CONFLICT', async ({ request }) => {
    const token = await apiToken(request, FRONT_DESK)
    const customerId = await demoCustomerId(request)
    const versionId = await approvedVersionId(request, customerId, FRONT_DESK)
    test.skip(!customerId || !versionId, 'seed has no customer / approved version')

    const key = `e2e-idem-conflict-${customerId}-${Date.now()}`
    const headers = {
      Authorization: `Bearer ${token}`,
      Accept: 'application/json',
      'Content-Type': 'application/json',
      'Idempotency-Key': key,
    }
    const base = {
      customer_id: customerId,
      source: 'walk_in',
      delivery_mode: 'pickup',
      items: [{ product_type: 'shirt', quantity: 1, measurement_version_id: versionId }],
    }
    await request.post(`${API_BASE}/api/v1/orders`, { headers, data: base })
    const conflict = await request.post(`${API_BASE}/api/v1/orders`, {
      headers,
      data: { ...base, source: 'phone' },
    })
    const body = await conflict.json()
    expect(conflict.status()).toBe(409)
    expect(body.code).toBe('IDEMPOTENCY_CONFLICT')
  })
})
