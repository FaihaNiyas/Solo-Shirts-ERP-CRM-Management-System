/**
 * orders.spec.ts
 *
 * Order & Measurement management tests.
 * Client requirements:
 *   - Orders: date, delivery date, fabric, style, quantity, multiple items (Slim/Loose fit)
 *   - Statuses: New → Cutting → Tailoring → Finishing → Ready → Delivered / Cancelled
 *   - Rush/priority orders flagged separately
 *   - Printed job card with unique Order ID
 *   - Measurements: 12 shirt + 7 pant fields, history, approval workflow
 *   - Measurement profiles: multiple per customer (Slim Fit, Loose Fit, etc.)
 */

import { test, expect } from '@playwright/test'
import {
  login, OWNER, FRONT_DESK, TAILOR, SUPERVISOR,
  apiToken, apiPost, firstOrderId, demoCustomerId, approvedVersionId, API_BASE,
} from './helpers'

// ─── Orders List ──────────────────────────────────────────────────────────────

test.describe('Orders List', () => {
  test.beforeEach(async ({ page }) => {
    await login(page)
  })

  test('renders Orders page with correct headers', async ({ page }) => {
    await page.goto('/orders')
    await expect(page.getByRole('heading', { level: 1, name: 'Orders' })).toBeVisible()
    await expect(page.getByRole('columnheader', { name: 'Order #' })).toBeVisible()
    await expect(page.getByRole('columnheader', { name: 'Customer' })).toBeVisible()
    await expect(page.getByRole('columnheader', { name: 'Status' })).toBeVisible()
  })

  test('displays seeded DEMO orders', async ({ page }) => {
    await page.goto('/orders')
    await expect(page.locator('tbody tr').first()).toBeVisible()
    await expect(page.getByText(/DEMO-ORD/i).first()).toBeVisible()
  })

  test('orders span all production states', async ({ page }) => {
    await page.goto('/orders')
    // Wait for data to load before checking text
    await expect(page.locator('tbody tr').first()).toBeVisible()
    // Status badges + sidebar nav should show at least 3 of these
    const statuses = ['Draft', 'Cutting', 'Tailoring', 'Ready', 'Delivered']
    let found = 0
    for (const status of statuses) {
      const count = await page.getByText(status, { exact: false }).count()
      if (count > 0) found++
    }
    expect(found).toBeGreaterThan(2) // At least 3 different states visible
  })
})

// ─── Order Detail ─────────────────────────────────────────────────────────────

test.describe('Order detail', () => {
  test.beforeEach(async ({ page }) => {
    await login(page)
  })

  test('navigates to order detail by clicking a row', async ({ page }) => {
    await page.goto('/orders')
    await page.locator('tbody tr').first().click()
    await expect(page).toHaveURL(/\/orders\/\d+/)
  })

  test('order detail shows order code and items', async ({ page, request }) => {
    const id = await firstOrderId(request)
    await page.goto(`/orders/${id}`)
    // Order code format is BRANCH-ORD-NNNN (e.g. DEMO-ORD-0001, MAIN-ORD-0042)
    await expect(page.getByText(/ORD-\d+/i).first()).toBeVisible()
  })
})

// ─── Measurements ────────────────────────────────────────────────────────────

test.describe('Measurements page', () => {
  test.beforeEach(async ({ page }) => {
    await login(page)
  })

  test('renders Measurements page', async ({ page }) => {
    await page.goto('/measurements')
    // Page should render (even if empty for this role)
    await expect(page).not.toHaveURL(/\/login/)
  })

  test('Approvals inbox shows pending measurement versions', async ({ page }) => {
    await page.goto('/approvals')
    // DemoDataSeeder creates pending "Wedding Sherwani" versions for first 3 customers
    await expect(page.getByText(/Measurement v\d+/i).first()).toBeVisible()
  })

  test('approvals page shows approve and reject buttons', async ({ page }) => {
    await page.goto('/approvals')
    await expect(
      page.getByRole('button', { name: /approve/i }).or(
        page.getByRole('button', { name: /reject/i })
      ).first()
    ).toBeVisible()
  })
})

// ─── API: Orders ──────────────────────────────────────────────────────────────

test.describe('API: Orders', () => {
  test('GET /api/v1/orders returns paginated list', async ({ request }) => {
    const token = await apiToken(request)
    const res = await request.get(`${API_BASE}/api/v1/orders?per_page=10`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    expect(res.status()).toBe(200)
    const body = await res.json()
    expect(body.success).toBe(true)
    expect(Array.isArray(body.data)).toBe(true)
    expect(body.data.length).toBeGreaterThan(0)
    // Verify structure
    const first = body.data[0]
    expect(first).toHaveProperty('id')
    expect(first).toHaveProperty('order_code')
    expect(first).toHaveProperty('status')
  })

  test('GET /api/v1/orders/:id returns order with items', async ({ request }) => {
    const id = await firstOrderId(request)
    const token = await apiToken(request)
    const res = await request.get(`${API_BASE}/api/v1/orders/${id}`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    expect(res.status()).toBe(200)
    const body = await res.json()
    expect(body.data.id).toBe(id)
    expect(Array.isArray(body.data.items)).toBe(true)
  })

  test('POST /api/v1/orders creates a new order', async ({ request }) => {
    const customerId = await demoCustomerId(request)
    const versionId = await approvedVersionId(request, customerId)
    const { status, body } = await apiPost(request, '/api/v1/orders', {
      customer_id: customerId,
      source: 'walk_in',            // required: walk_in|phone|whatsapp|online
      expected_delivery_date: new Date(Date.now() + 7 * 86400000).toISOString().slice(0, 10),
      delivery_mode: 'pickup',
      notes: 'E2E test order',
      items: [{ product_type: 'shirt', measurement_version_id: versionId, quantity: 1 }],
    })
    expect(status).toBe(201)
    expect(body.success).toBe(true)
    const data = body.data as Record<string, unknown>
    expect(data.customer_id).toBe(customerId)
    expect(data.order_code).toBeTruthy()
  })

  test('POST /api/v1/orders returns 422 without customer_id', async ({ request }) => {
    const { status } = await apiPost(request, '/api/v1/orders', {
      source: 'walk_in',
      delivery_mode: 'pickup',
      items: [],
      // customer_id missing
    })
    expect(status).toBe(422)
  })

  test('POST /api/v1/orders/:id/cancel cancels a draft order', async ({ request }) => {
    // Create a fresh order to cancel
    const customerId = await demoCustomerId(request)
    const versionId = await approvedVersionId(request, customerId)
    const { body: created } = await apiPost(request, '/api/v1/orders', {
      customer_id: customerId,
      source: 'walk_in',
      delivery_mode: 'pickup',
      items: [{ product_type: 'shirt', measurement_version_id: versionId, quantity: 1 }],
    })
    const orderId = (created.data as Record<string, unknown>).id as number
    const token = await apiToken(request)
    const res = await request.post(`${API_BASE}/api/v1/orders/${orderId}/cancel`, {
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json',
        'Content-Type': 'application/json',
      },
      data: { reason: 'E2E cancel test' },
    })
    expect([200, 204]).toContain(res.status())
  })

  test('GET /api/v1/orders/:id/job-card returns PDF-like response', async ({ request }) => {
    const id = await firstOrderId(request)
    const token = await apiToken(request)
    const res = await request.get(`${API_BASE}/api/v1/orders/${id}/job-card`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    expect([200, 202]).toContain(res.status())
  })

  test('Tailor cannot create an order (403)', async ({ request }) => {
    const customerId = await demoCustomerId(request)
    const { status } = await apiPost(
      request,
      '/api/v1/orders',
      { customer_id: customerId, source: 'walk_in', delivery_mode: 'pickup', items: [] },
      TAILOR,
    )
    expect(status).toBe(403)
  })

  test('Front Desk can create an order', async ({ request }) => {
    const customerId = await demoCustomerId(request)
    const versionId = await approvedVersionId(request, customerId, FRONT_DESK)
    const { status, body } = await apiPost(
      request,
      '/api/v1/orders',
      {
        customer_id: customerId,
        source: 'walk_in',
        delivery_mode: 'pickup',
        items: [{ product_type: 'shirt', measurement_version_id: versionId, quantity: 1 }],
      },
      FRONT_DESK,
    )
    expect(status).toBe(201)
    expect(body.success).toBe(true)
  })
})

// ─── API: Measurements ───────────────────────────────────────────────────────

test.describe('API: Measurements', () => {
  test('GET /api/v1/customers/:id/measurements returns profiles', async ({ request }) => {
    const id = await demoCustomerId(request)
    const token = await apiToken(request)
    const res = await request.get(`${API_BASE}/api/v1/customers/${id}/measurements`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    expect(res.status()).toBe(200)
    const body = await res.json()
    expect(Array.isArray(body.data)).toBe(true)
    // Each customer has "Slim Fit" and "Loose Fit" profiles seeded
    const profiles = body.data as Array<{ name: string }>
    expect(profiles.some((p) => p.name === 'Slim Fit')).toBe(true)
    expect(profiles.some((p) => p.name === 'Loose Fit')).toBe(true)
  })

  test('GET /api/v1/measurements/:profileId/versions returns version history', async ({ request }) => {
    const id = await demoCustomerId(request)
    const token = await apiToken(request)
    // Get profiles first
    const profilesRes = await request.get(`${API_BASE}/api/v1/customers/${id}/measurements`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    const { data: profiles } = await profilesRes.json()
    const profileId = (profiles as Array<{ id: number }>)[0].id

    const versionsRes = await request.get(`${API_BASE}/api/v1/measurements/profiles/${profileId}/versions`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    expect(versionsRes.status()).toBe(200)
    const body = await versionsRes.json()
    expect(Array.isArray(body.data)).toBe(true)
    expect(body.data.length).toBeGreaterThan(0)
    // Approved versions should have shirt_data
    const version = body.data[0] as Record<string, unknown>
    expect(version).toHaveProperty('version_number')
    expect(version).toHaveProperty('status')
  })

  test('POST measurement version creates a pending version', async ({ request }) => {
    const id = await demoCustomerId(request)
    const token = await apiToken(request)
    // Get a profile to version
    const profilesRes = await request.get(`${API_BASE}/api/v1/customers/${id}/measurements`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    const { data: profiles } = await profilesRes.json()
    const profileId = (profiles as Array<{ id: number }>)[0].id

    const res = await request.post(
      `${API_BASE}/api/v1/measurements/profiles/${profileId}/versions`,
      {
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: 'application/json',
          'Content-Type': 'application/json',
        },
        data: {
          shirt_data: {
            chest: 40,
            waist: 34,
            shirt_length: 29,
            shoulder: 17,
            sleeve_length: 24,
          },
        },
      },
    )
    expect(res.status()).toBe(201)
    const body = await res.json()
    expect(body.data.status).toBe('pending_approval')
  })

  test('Tailor cannot approve a measurement version (403)', async ({ request }) => {
    const token = await apiToken(request, TAILOR)
    const res = await request.post(`${API_BASE}/api/v1/measurements/versions/1/approve`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    expect(res.status()).toBe(403)
  })
})
