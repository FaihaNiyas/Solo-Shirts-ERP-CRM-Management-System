import { test, expect } from '@playwright/test'
import { apiToken, API_BASE, DELIVERY } from './helpers'

/**
 * FE — delivery confirmation is OTP-gated. The OTP is never returned by the API
 * (stored hashed), so the testable contract is the failure path: a wrong OTP is
 * rejected with a structured error + request_id (and the backend increments the
 * attempt counter — covered by WrongOtpIncrementsAttemptsTest server-side).
 */
test.describe('Delivery OTP', () => {
  test('a wrong OTP on confirm is rejected with a request_id', async ({ request }) => {
    const token = await apiToken(request, DELIVERY)
    const list = await request.get(`${API_BASE}/api/v1/deliveries?per_page=10`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    const rows = (await list.json()).data as Array<{ id: number; status?: string }>
    const target = rows?.find((r) => r.status === 'dispatched' || r.status === 'out_for_delivery') ?? rows?.[0]
    test.skip(!target, 'no delivery in the seed to confirm against')

    const res = await request.post(`${API_BASE}/api/v1/deliveries/${target!.id}/confirm`, {
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'Idempotency-Key': `e2e-otp-${target!.id}`,
      },
      data: { otp: '000000' }, // deliberately wrong
    })
    expect(res.ok()).toBeFalsy()
    const body = await res.json()
    expect(body.success).toBe(false)
    expect(body.request_id ?? res.headers()['x-request-id']).toBeTruthy()
  })
})
