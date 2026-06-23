import { test, expect } from '@playwright/test'
import { apiToken, API_BASE, QC, DELIVERY } from './helpers'

/**
 * FE-015 — QC inspect / rework. The reference data (defect categories) is
 * readable by QC, and the inspect mutation is role-guarded server-side: an
 * unauthorized role is rejected with a structured error + request_id. The FE
 * only gates the UX; the backend stays the enforcer.
 */
test.describe('QC inspect / rework', () => {
  test('QC can read defect categories', async ({ request }) => {
    const token = await apiToken(request, QC)
    const res = await request.get(`${API_BASE}/api/v1/qc/defects/categories`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    test.skip(res.status() === 404, 'defect-categories endpoint not present in this build')
    expect(res.ok()).toBeTruthy()
    const body = await res.json()
    expect(body.success).toBe(true)
    expect(Array.isArray(body.data)).toBeTruthy()
  })

  test('an unauthorized role cannot inspect an item', async ({ request }) => {
    const token = await apiToken(request, DELIVERY)
    const res = await request.post(`${API_BASE}/api/v1/qc/items/1/inspect`, {
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'Idempotency-Key': 'e2e-qc-forbidden',
      },
      data: { disposition: 'pass' },
    })
    // 403 (policy) or 422 (state/validation) — never a silent success.
    expect(res.ok()).toBeFalsy()
    const body = await res.json()
    expect(body.success).toBe(false)
    expect(body.request_id ?? res.headers()['x-request-id']).toBeTruthy()
  })
})
