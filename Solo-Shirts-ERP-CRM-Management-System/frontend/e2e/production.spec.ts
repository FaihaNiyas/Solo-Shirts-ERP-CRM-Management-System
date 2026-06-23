/**
 * production.spec.ts
 *
 * Production Workflow, Cutting, Tailoring, QC, Delivery, and Rack tests.
 * Client requirements:
 *   - 7-stage workflow: Order Received → Fabric Allocation → Cutting → Tailoring
 *                       → Finishing → Packing → Delivered
 *   - Manual confirmation at each stage
 *   - Kanban-style production board
 *   - QC failure → Rework section
 *   - Rack slot assignment for Ready-for-Delivery orders
 *   - OTP-confirmed delivery
 */

import { test, expect } from '@playwright/test'
import {
  login, OWNER, SUPERVISOR, CUTTER, TAILOR, QC, DELIVERY,
  apiToken, API_BASE, firstOrderId,
} from './helpers'

// ─── Production Board ─────────────────────────────────────────────────────────

test.describe('Production Board (Owner)', () => {
  test.beforeEach(async ({ page }) => {
    await login(page, OWNER)
  })

  test('renders the Production Board with metric cards', async ({ page }) => {
    await page.goto('/production')
    await expect(page.getByRole('heading', { level: 1, name: 'Production Board' })).toBeVisible()
    await expect(page.getByText('Active Items')).toBeVisible()
    await expect(page.getByText('In Rework')).toBeVisible()
    await expect(page.getByText('Ready for Delivery')).toBeVisible()
  })

  test('Kanban board shows order items in production stages', async ({ page }) => {
    await page.goto('/production')
    // Wait for metric cards to confirm page + data have loaded
    await expect(page.getByText('Active Items')).toBeVisible()
    // At least one stage column should be visible (seeded data covers all states)
    const stageLabels = ['Cutting', 'Tailoring', 'Finishing', 'QC', 'Ready']
    let found = 0
    for (const label of stageLabels) {
      if (await page.getByText(label, { exact: false }).count() > 0) found++
    }
    expect(found).toBeGreaterThan(2)
  })
})

test.describe('Production Board (Supervisor)', () => {
  test('Supervisor can view the production board', async ({ page }) => {
    await login(page, SUPERVISOR)
    await page.goto('/production')
    await expect(page.getByRole('heading', { level: 1, name: 'Production Board' })).toBeVisible()
  })
})

// ─── Cutting Queue ────────────────────────────────────────────────────────────

test.describe('Cutting page', () => {
  test('Owner can view Cutting page', async ({ page }) => {
    await login(page, OWNER)
    await page.goto('/cutting')
    await expect(page).not.toHaveURL(/\/login/)
    // Page should render without crashing
    await expect(page.getByRole('main')).toBeVisible()
  })

  test('Cutter can view cutting queue', async ({ page }) => {
    await login(page, CUTTER)
    await page.goto('/cutting')
    await expect(page).not.toHaveURL(/\/login/)
  })
})

// ─── Tailoring ────────────────────────────────────────────────────────────────

test.describe('Tailoring page', () => {
  test('Owner can view Tailoring page', async ({ page }) => {
    await login(page, OWNER)
    await page.goto('/tailoring')
    await expect(page).not.toHaveURL(/\/login/)
    await expect(page.getByRole('main')).toBeVisible()
  })

  test('Tailor can view tailoring page', async ({ page }) => {
    await login(page, TAILOR)
    await page.goto('/tailoring')
    await expect(page).not.toHaveURL(/\/login/)
  })
})

// ─── QC / Finishing ──────────────────────────────────────────────────────────

test.describe('QC page', () => {
  test('Owner can view QC page', async ({ page }) => {
    await login(page, OWNER)
    await page.goto('/qc')
    await expect(page).not.toHaveURL(/\/login/)
    await expect(page.getByRole('main')).toBeVisible()
  })

  test('QC Supervisor can view QC page', async ({ page }) => {
    await login(page, QC)
    await page.goto('/qc')
    await expect(page).not.toHaveURL(/\/login/)
  })
})

// ─── Rack Management ─────────────────────────────────────────────────────────

test.describe('Rack Management', () => {
  test.beforeEach(async ({ page }) => {
    await login(page, OWNER)
  })

  test('renders Rack Management page', async ({ page }) => {
    await page.goto('/rack')
    await expect(page.getByRole('heading', { level: 1, name: 'Rack Management' })).toBeVisible()
  })

  test('shows occupied and free slots from seeded data', async ({ page }) => {
    await page.goto('/rack')
    await expect(page.getByRole('heading', { level: 1, name: 'Rack Management' })).toBeVisible()
    // 12 seeded slots render as buttons in the rack visual map once API data loads
    await expect(page.locator('main button').first()).toBeVisible({ timeout: 10_000 })
  })
})

// ─── Deliveries ───────────────────────────────────────────────────────────────

test.describe('Deliveries page', () => {
  test.beforeEach(async ({ page }) => {
    await login(page, OWNER)
  })

  test('renders Deliveries page with correct columns', async ({ page }) => {
    await page.goto('/deliveries')
    await expect(page.getByRole('heading', { level: 1, name: 'Deliveries' })).toBeVisible()
    await expect(page.getByRole('columnheader', { name: 'Delivery #' })).toBeVisible()
    await expect(page.getByRole('columnheader', { name: 'Order #' })).toBeVisible()
    await expect(page.getByRole('columnheader', { name: 'Status' })).toBeVisible()
  })

  test('shows seeded deliveries in various statuses', async ({ page }) => {
    await page.goto('/deliveries')
    await expect(page.locator('tbody tr').first()).toBeVisible()
  })

  test('Dispatch button opens dialog for pending deliveries', async ({ page }) => {
    await page.goto('/deliveries')
    const dispatchBtn = page.getByRole('button', { name: 'Dispatch' }).first()
    if (await dispatchBtn.count() > 0) {
      await dispatchBtn.click()
      await expect(page.getByRole('heading', { name: 'Dispatch Delivery' })).toBeVisible()
      // Cancel to avoid mutating seeded data
      await page.getByRole('button', { name: 'Cancel' }).click()
    }
  })

  test('Confirm OTP button opens OTP dialog for dispatched deliveries', async ({ page }) => {
    await page.goto('/deliveries')
    const confirmBtn = page.getByRole('button', { name: 'Confirm OTP' }).first()
    if (await confirmBtn.count() > 0) {
      await confirmBtn.click()
      await expect(page.getByRole('heading', { name: 'Confirm OTP' })).toBeVisible()
      await expect(page.getByPlaceholder('6-digit OTP')).toBeVisible()
      await page.getByRole('button', { name: 'Cancel' }).click()
    }
  })

  test('Delivery Staff can view deliveries', async ({ page }) => {
    await login(page, DELIVERY)
    await page.goto('/deliveries')
    await expect(page.getByRole('heading', { level: 1, name: 'Deliveries' })).toBeVisible()
  })
})

// ─── API: Production ─────────────────────────────────────────────────────────

test.describe('API: Production Board', () => {
  test('GET /api/v1/production/board returns kanban data', async ({ request }) => {
    const token = await apiToken(request, OWNER)
    const res = await request.get(`${API_BASE}/api/v1/production/board`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    expect(res.status()).toBe(200)
    const body = await res.json()
    expect(body.success).toBe(true)
    // Board should have data keyed by state
    expect(body.data).toBeTruthy()
  })

  test('GET /api/v1/cutting/queue returns cutting queue', async ({ request }) => {
    const token = await apiToken(request, CUTTER)
    const res = await request.get(`${API_BASE}/api/v1/cutting/queue`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    expect(res.status()).toBe(200)
  })

  test('GET /api/v1/tailoring/assignments returns assignments', async ({ request }) => {
    const token = await apiToken(request, SUPERVISOR)
    const res = await request.get(`${API_BASE}/api/v1/tailoring/assignments`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    expect(res.status()).toBe(200)
  })

  test('GET /api/v1/tailoring/performance/:tailor returns metrics', async ({ request }) => {
    // Get tailor user ID from users list
    const token = await apiToken(request, OWNER)
    const usersRes = await request.get(`${API_BASE}/api/v1/users`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    const { data: users } = await usersRes.json()
    const tailor = (users as Array<{ id: number; email: string }>).find(
      (u) => u.email === 'tailor1@soloshirts.test',
    )
    if (tailor) {
      const res = await request.get(`${API_BASE}/api/v1/tailoring/performance/${tailor.id}`, {
        headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
      })
      expect(res.status()).toBe(200)
    }
  })
})

// ─── API: Deliveries ─────────────────────────────────────────────────────────

test.describe('API: Deliveries', () => {
  test('GET /api/v1/deliveries returns delivery list', async ({ request }) => {
    const token = await apiToken(request, OWNER)
    const res = await request.get(`${API_BASE}/api/v1/deliveries`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    expect(res.status()).toBe(200)
    const body = await res.json()
    expect(body.success).toBe(true)
    expect(Array.isArray(body.data)).toBe(true)
    expect(body.data.length).toBeGreaterThan(0)
    // Verify shape
    const first = body.data[0] as Record<string, unknown>
    expect(first).toHaveProperty('id')
    expect(first).toHaveProperty('status')
  })

  test('GET /api/v1/deliveries returns 403 for Tailor', async ({ request }) => {
    const token = await apiToken(request, TAILOR)
    const res = await request.get(`${API_BASE}/api/v1/deliveries`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    expect(res.status()).toBe(403)
  })

  test('POST /api/v1/deliveries/:id/confirm with wrong OTP returns error', async ({ request }) => {
    const token = await apiToken(request, OWNER)
    // Get a dispatched delivery
    const listRes = await request.get(`${API_BASE}/api/v1/deliveries?status=dispatched`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    const { data } = await listRes.json()
    const dispatched = (data as Array<{ id: number; status: string }>).find(
      (d) => d.status === 'dispatched',
    )
    if (dispatched) {
      const res = await request.post(
        `${API_BASE}/api/v1/deliveries/${dispatched.id}/confirm`,
        {
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: 'application/json',
            'Content-Type': 'application/json',
          },
          data: { otp: '000000' }, // Wrong OTP
        },
      )
      // Wrong OTP should be rejected with 422 or 400
      expect([400, 422]).toContain(res.status())
    }
  })
})

// ─── API: Rack ────────────────────────────────────────────────────────────────

test.describe('API: Rack Slots', () => {
  test('GET /api/v1/rack/slots returns slot list', async ({ request }) => {
    const token = await apiToken(request, OWNER)
    const res = await request.get(`${API_BASE}/api/v1/rack/slots`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    expect(res.status()).toBe(200)
    const body = await res.json()
    expect(Array.isArray(body.data)).toBe(true)
    expect(body.data.length).toBeGreaterThan(0)
    // Seeded slots: A-01 through A-12
    const slots = body.data as Array<{ slot_code: string }>
    expect(slots.some((s) => s.slot_code?.startsWith('A-'))).toBe(true)
  })

  test('GET /api/v1/rack/slots shows occupied and free slots', async ({ request }) => {
    const token = await apiToken(request, OWNER)
    const res = await request.get(`${API_BASE}/api/v1/rack/slots`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    const body = await res.json()
    const slots = body.data as Array<{ current_order_item_id: number | null }>
    const occupied = slots.filter((s) => s.current_order_item_id !== null)
    const free = slots.filter((s) => s.current_order_item_id === null)
    // Seeded data assigns some slots
    expect(occupied.length).toBeGreaterThan(0)
    expect(free.length).toBeGreaterThan(0)
  })
})
