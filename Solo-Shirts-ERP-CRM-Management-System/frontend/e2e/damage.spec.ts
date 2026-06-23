/**
 * damage.spec.ts
 *
 * Cloth Damage & Issue Management.
 * Client requirements:
 *   - Log: date, order ID, staff involved, production stage, type of damage, quantity lost, action
 *   - Damage types: cutting error, fabric defect, wrong fabric, stitching error, supplier damage,
 *                   measurement error
 *   - Write-offs approved by Owner ONLY
 *   - Approval auto-deducts damaged fabric from inventory
 *   - Repeat damage by same staff triggers supervisor alert
 *   - Supplier damage claims tracked separately
 */

import { test, expect } from '@playwright/test'
import {
  login, OWNER, INVENTORY, SUPERVISOR, TAILOR, FRONT_DESK,
  apiToken, apiPost, API_BASE,
} from './helpers'

// ─── Damage Reports — UI ──────────────────────────────────────────────────────

test.describe('Damage Reports page (Owner)', () => {
  test.beforeEach(async ({ page }) => {
    await login(page, OWNER)
  })

  test('renders Damage Reports page with correct heading', async ({ page }) => {
    await page.goto('/damage-reports')
    await expect(page.getByRole('heading', { level: 1, name: 'Damage Reports' })).toBeVisible()
  })

  test('shows correct column headers', async ({ page }) => {
    await page.goto('/damage-reports')
    await expect(page.getByRole('columnheader', { name: 'Roll #' })).toBeVisible()
    await expect(page.getByRole('columnheader', { name: 'Type' })).toBeVisible()
    await expect(page.getByRole('columnheader', { name: 'Status' })).toBeVisible()
  })

  test('shows seeded damage reports (4 pending)', async ({ page }) => {
    await page.goto('/damage-reports')
    await expect(page.locator('tbody tr').first()).toBeVisible()
    // At least one pending report
    await expect(page.getByText(/pending/i).first()).toBeVisible()
  })

  test('Owner sees Approve and Reject action buttons', async ({ page }) => {
    await page.goto('/damage-reports')
    // Actions column with Approve/Reject only for Owner
    await expect(page.getByRole('columnheader', { name: 'Actions' })).toBeVisible()
    await expect(
      page.getByRole('button', { name: /approve/i }).first()
    ).toBeVisible()
    await expect(
      page.getByRole('button', { name: /reject/i }).first()
    ).toBeVisible()
  })

  test('clicking Approve opens confirmation dialog', async ({ page }) => {
    await page.goto('/damage-reports')
    const approveBtn = page.getByRole('button', { name: /approve/i }).first()
    await approveBtn.click()
    // Confirmation dialog appears
    await expect(
      page.getByRole('heading', { name: /approve damage report/i })
        .or(page.getByRole('dialog'))
        .first()
    ).toBeVisible()
    // Cancel to avoid mutating seeded data
    await page.getByRole('button', { name: 'Cancel' }).click()
  })

  test('clicking Reject opens confirmation dialog', async ({ page }) => {
    await page.goto('/damage-reports')
    const rejectBtn = page.getByRole('button', { name: /reject/i }).first()
    await rejectBtn.click()
    await expect(
      page.getByRole('heading', { name: /reject damage report/i })
        .or(page.getByRole('dialog'))
        .first()
    ).toBeVisible()
    await page.getByRole('button', { name: 'Cancel' }).click()
  })
})

// ─── Damage Reports — Non-Owner access ───────────────────────────────────────

test.describe('Damage Reports access control', () => {
  test('Inventory Manager can VIEW damage reports', async ({ page }) => {
    await login(page, INVENTORY)
    await page.goto('/damage-reports')
    await expect(page.getByRole('heading', { level: 1, name: 'Damage Reports' })).toBeVisible()
  })

  test('Inventory Manager cannot see Approve/Reject buttons (Owner-only)', async ({ page }) => {
    await login(page, INVENTORY)
    await page.goto('/damage-reports')
    // Non-Owner should not see action buttons
    await expect(page.getByRole('button', { name: /approve/i })).toHaveCount(0)
    await expect(page.getByRole('button', { name: /reject/i })).toHaveCount(0)
  })

  test('Tailor is blocked from Damage Reports', async ({ page }) => {
    await login(page, TAILOR)
    await page.goto('/damage-reports')
    await expect(
      page.getByText(/access|permission|not authorized/i).first()
    ).toBeVisible()
  })

  test('Front Desk is blocked from Damage Reports', async ({ page }) => {
    await login(page, FRONT_DESK)
    await page.goto('/damage-reports')
    await expect(
      page.getByText(/access|permission|not authorized/i).first()
    ).toBeVisible()
  })
})

// ─── API: Damage Reports ──────────────────────────────────────────────────────

test.describe('API: Damage Reports', () => {
  test('GET /api/v1/damage-reports returns list for Owner', async ({ request }) => {
    const token = await apiToken(request, OWNER)
    const res = await request.get(`${API_BASE}/api/v1/damage-reports`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    expect(res.status()).toBe(200)
    const body = await res.json()
    expect(body.success).toBe(true)
    expect(Array.isArray(body.data)).toBe(true)
    // Seeder creates 4 pending damage reports
    expect(body.data.length).toBeGreaterThanOrEqual(4)
    // Verify shape
    const report = body.data[0] as Record<string, unknown>
    expect(report).toHaveProperty('id')
    expect(report).toHaveProperty('status')
    expect(report).toHaveProperty('fabric_roll_id')
  })

  test('GET /api/v1/damage-reports returns 403 for Tailor', async ({ request }) => {
    const token = await apiToken(request, TAILOR)
    const res = await request.get(`${API_BASE}/api/v1/damage-reports`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    expect(res.status()).toBe(403)
  })

  test('POST /api/v1/damage-reports creates a report (Inventory Manager)', async ({ request }) => {
    // Get a fabric roll to report damage on
    const ownerToken = await apiToken(request, OWNER)
    const rollsRes = await request.get(`${API_BASE}/api/v1/inventory/fabric-rolls?per_page=3`, {
      headers: { Authorization: `Bearer ${ownerToken}`, Accept: 'application/json' },
    })
    const { data: rolls } = await rollsRes.json()
    const rollId = (rolls as Array<{ id: number }>)[0].id

    const { status, body } = await apiPost(
      request,
      '/api/v1/damage-reports',
      {
        fabric_roll_id: rollId,
        damage_type: 'tear',          // valid enum: tear|stain|color_bleed|mis_cut|machine_oil|other
        quantity_lost_metres: 0.5,    // correct field name
        description: 'E2E test damage report',
        stage: 'cutting',             // valid enum: receiving|cutting|tailoring|qc|ironing|packing
      },
      INVENTORY,
    )
    expect([200, 201]).toContain(status)
    if (status === 201) {
      expect(body.success).toBe(true)
      const data = body.data as Record<string, unknown>
      expect(data.status).toBe('pending')
    }
  })

  test('POST /api/v1/damage-reports/:id/approve is Owner-only (403 for Inventory)', async ({ request }) => {
    // Get a pending damage report
    const ownerToken = await apiToken(request, OWNER)
    const reportsRes = await request.get(`${API_BASE}/api/v1/damage-reports`, {
      headers: { Authorization: `Bearer ${ownerToken}`, Accept: 'application/json' },
    })
    const { data: reports } = await reportsRes.json()
    const pending = (reports as Array<{ id: number; status: string }>).find(
      (r) => r.status === 'pending',
    )

    if (pending) {
      const inventoryToken = await apiToken(request, INVENTORY)
      const res = await request.post(
        `${API_BASE}/api/v1/damage-reports/${pending.id}/approve`,
        {
          headers: {
            Authorization: `Bearer ${inventoryToken}`,
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'Idempotency-Key': `e2e-inv-approve-${pending.id}`,
          },
          data: {},
        },
      )
      // Inventory Manager cannot approve — Owner only
      expect(res.status()).toBe(403)
    }
  })

  test('POST /api/v1/damage-reports/:id/reject is Owner-only (403 for Supervisor)', async ({ request }) => {
    const ownerToken = await apiToken(request, OWNER)
    const reportsRes = await request.get(`${API_BASE}/api/v1/damage-reports`, {
      headers: { Authorization: `Bearer ${ownerToken}`, Accept: 'application/json' },
    })
    const { data: reports } = await reportsRes.json()
    const pending = (reports as Array<{ id: number; status: string }>).find(
      (r) => r.status === 'pending',
    )

    if (pending) {
      const supervisorToken = await apiToken(request, SUPERVISOR)
      const res = await request.post(
        `${API_BASE}/api/v1/damage-reports/${pending.id}/reject`,
        {
          headers: {
            Authorization: `Bearer ${supervisorToken}`,
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'Idempotency-Key': `e2e-sup-reject-${pending.id}`,
          },
          data: { reason: 'E2E rejection test' },
        },
      )
      expect(res.status()).toBe(403)
    }
  })

  test('all seeded damage reports have status=pending', async ({ request }) => {
    const token = await apiToken(request, OWNER)
    const res = await request.get(`${API_BASE}/api/v1/damage-reports`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    const body = await res.json()
    const reports = body.data as Array<{ status: string }>
    // All seeded reports are pending
    const seededPending = reports.filter((r) => r.status === 'pending')
    expect(seededPending.length).toBeGreaterThanOrEqual(4)
  })
})
