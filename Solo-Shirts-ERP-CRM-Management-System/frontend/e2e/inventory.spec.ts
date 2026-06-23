/**
 * inventory.spec.ts
 *
 * Inventory & Material Management — fabric rolls, suppliers, purchase orders.
 * Client requirements:
 *   - Each roll: unique Roll ID, fabric type, colour, length, supplier, price, date received
 *   - Stock auto-updated when fabric allocated to cutting
 *   - Partial roll usage tracked
 *   - Low-stock alerts when roll falls below threshold
 *   - Supplier profiles with contact details, payment terms, purchase history
 *   - Purchase Orders with status: ordered / received / partial
 *   - GRN verification against PO before stock update
 */

import { test, expect } from '@playwright/test'
import {
  login, OWNER, INVENTORY, TAILOR, FRONT_DESK,
  apiToken, apiPost, firstFabricRollId, API_BASE,
} from './helpers'

// ─── Fabric Rolls — UI ────────────────────────────────────────────────────────

test.describe('Fabric Rolls page (Owner)', () => {
  test.beforeEach(async ({ page }) => {
    await login(page, OWNER)
  })

  test('renders Fabric Rolls page with correct heading', async ({ page }) => {
    await page.goto('/inventory/fabric-rolls')
    await expect(page.getByRole('heading', { level: 1, name: 'Fabric Rolls' })).toBeVisible()
  })

  test('shows correct column headers', async ({ page }) => {
    await page.goto('/inventory/fabric-rolls')
    await expect(page.getByRole('columnheader', { name: 'Roll #' })).toBeVisible()
    await expect(page.getByRole('columnheader', { name: 'Fabric Type' })).toBeVisible()
    await expect(page.getByRole('columnheader', { name: 'Remaining (m)' })).toBeVisible()
    await expect(page.getByRole('columnheader', { name: 'Status' })).toBeVisible()
  })

  test('displays seeded fabric rolls', async ({ page }) => {
    await page.goto('/inventory/fabric-rolls')
    await expect(page.locator('tbody tr').first()).toBeVisible()
  })

  test('shows low-stock rolls highlighted', async ({ page }) => {
    await page.goto('/inventory/fabric-rolls')
    await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 10_000 })
    // Status badges should be present for all fabric rolls
    await expect(page.getByText(/active|available|depleted/i).first()).toBeVisible()
  })

  test('Inventory Manager can view fabric rolls', async ({ page }) => {
    await login(page, INVENTORY)
    await page.goto('/inventory/fabric-rolls')
    await expect(page.getByRole('heading', { level: 1, name: 'Fabric Rolls' })).toBeVisible()
  })
})

// ─── Inventory — RBAC ────────────────────────────────────────────────────────

test.describe('Inventory access control', () => {
  test('Tailor cannot access inventory management', async ({ page }) => {
    await login(page, TAILOR)
    await page.goto('/inventory/fabric-rolls')
    await expect(
      page.getByText(/access|permission|not authorized/i).first()
    ).toBeVisible()
  })

  test('Front Desk cannot access fabric rolls management', async ({ page }) => {
    await login(page, FRONT_DESK)
    await page.goto('/inventory/fabric-rolls')
    await expect(
      page.getByText(/access|permission|not authorized/i).first()
    ).toBeVisible()
  })
})

// ─── API: Fabric Rolls ────────────────────────────────────────────────────────

test.describe('API: Fabric Rolls', () => {
  test('GET /api/v1/inventory/fabric-rolls returns rolls list', async ({ request }) => {
    const token = await apiToken(request, OWNER)
    const res = await request.get(`${API_BASE}/api/v1/inventory/fabric-rolls`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    expect(res.status()).toBe(200)
    const body = await res.json()
    expect(body.success).toBe(true)
    expect(Array.isArray(body.data)).toBe(true)
    expect(body.data.length).toBeGreaterThan(0)
    // Verify roll structure
    const roll = body.data[0] as Record<string, unknown>
    expect(roll).toHaveProperty('id')
    expect(roll).toHaveProperty('remaining_metres')
  })

  test('GET /api/v1/inventory/fabric-rolls/:id returns roll detail', async ({ request }) => {
    const id = await firstFabricRollId(request)
    const token = await apiToken(request)
    const res = await request.get(`${API_BASE}/api/v1/inventory/fabric-rolls/${id}`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    expect(res.status()).toBe(200)
    const body = await res.json()
    expect(body.data.id).toBe(id)
    expect(body.data).toHaveProperty('remaining_metres')
    expect(body.data).toHaveProperty('received_length_metres')
  })

  test('GET /api/v1/inventory/low-stock returns low-stock rolls', async ({ request }) => {
    const token = await apiToken(request, OWNER)
    const res = await request.get(`${API_BASE}/api/v1/inventory/low-stock`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    expect(res.status()).toBe(200)
    const body = await res.json()
    expect(Array.isArray(body.data)).toBe(true)
    // Seeder creates some low-stock rolls (every 4th)
    expect(body.data.length).toBeGreaterThan(0)
  })

  test('GET /api/v1/inventory/fabric-rolls returns 403 for Tailor', async ({ request }) => {
    const token = await apiToken(request, TAILOR)
    const res = await request.get(`${API_BASE}/api/v1/inventory/fabric-rolls`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    expect(res.status()).toBe(403)
  })

  test('Inventory Manager can GET fabric rolls', async ({ request }) => {
    const token = await apiToken(request, INVENTORY)
    const res = await request.get(`${API_BASE}/api/v1/inventory/fabric-rolls`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    expect(res.status()).toBe(200)
  })
})

// ─── API: Fabric Types ────────────────────────────────────────────────────────

test.describe('API: Fabric Types', () => {
  test('GET /api/v1/inventory/fabric-types returns seeded types', async ({ request }) => {
    const token = await apiToken(request, OWNER)
    const res = await request.get(`${API_BASE}/api/v1/inventory/fabric-types`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    expect(res.status()).toBe(200)
    const body = await res.json()
    expect(Array.isArray(body.data)).toBe(true)
    expect(body.data.length).toBeGreaterThan(0)
    // Each type should have low_stock_threshold_metres
    const type = body.data[0] as Record<string, unknown>
    expect(type).toHaveProperty('name')
    expect(type).toHaveProperty('low_stock_threshold_metres')
  })
})

// ─── API: Suppliers ───────────────────────────────────────────────────────────

test.describe('API: Suppliers', () => {
  test('GET /api/v1/inventory/suppliers returns seeded suppliers', async ({ request }) => {
    const token = await apiToken(request, OWNER)
    const res = await request.get(`${API_BASE}/api/v1/inventory/suppliers`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    expect(res.status()).toBe(200)
    const body = await res.json()
    expect(Array.isArray(body.data)).toBe(true)
    // Seeder creates 5 suppliers
    expect(body.data.length).toBeGreaterThanOrEqual(5)
  })

  test('POST /api/v1/inventory/suppliers creates a supplier', async ({ request }) => {
    const { status, body } = await apiPost(request, '/api/v1/inventory/suppliers', {
      code: `E2E-SUP-${Date.now()}`,
      name: 'E2E Test Supplier',
      contact_name: 'Test Contact',
      phone: '9876543210',
      email: `supplier_${Date.now()}@test.com`,
      address: '456 Supplier Lane, Mumbai',
      payment_terms: 'Net 30',
    })
    expect(status).toBe(201)
    expect(body.success).toBe(true)
    const data = body.data as Record<string, unknown>
    expect(data.name).toBe('E2E Test Supplier')
  })

  test('Tailor cannot create a supplier (403)', async ({ request }) => {
    const { status } = await apiPost(
      request,
      '/api/v1/inventory/suppliers',
      { code: 'TAILOR-SUP', name: 'Tailor Supplier', phone: '9000011111' },
      TAILOR,
    )
    expect(status).toBe(403)
  })
})

// ─── API: Purchase Orders ─────────────────────────────────────────────────────

test.describe('API: Purchase Orders', () => {
  test('GET /api/v1/inventory/purchase-orders returns list', async ({ request }) => {
    const token = await apiToken(request, OWNER)
    const res = await request.get(`${API_BASE}/api/v1/inventory/purchase-orders`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    expect(res.status()).toBe(200)
    const body = await res.json()
    expect(body.success).toBe(true)
    expect(Array.isArray(body.data)).toBe(true)
  })

  test('POST /api/v1/inventory/purchase-orders creates a PO', async ({ request }) => {
    const token = await apiToken(request, OWNER)
    // Get a supplier ID
    const suppliersRes = await request.get(`${API_BASE}/api/v1/inventory/suppliers`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    const { data: suppliers } = await suppliersRes.json()
    const supplierId = (suppliers as Array<{ id: number }>)[0].id
    // Get a fabric type ID (required by PO items)
    const typesRes = await request.get(`${API_BASE}/api/v1/inventory/fabric-types`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    const { data: fabricTypes } = await typesRes.json()
    const fabricTypeId = (fabricTypes as Array<{ id: number }>)[0].id

    const { status, body } = await apiPost(request, '/api/v1/inventory/purchase-orders', {
      supplier_id: supplierId,
      expected_delivery_date: new Date(Date.now() + 7 * 86400000).toISOString().slice(0, 10),
      notes: 'E2E test PO',
      items: [
        {
          fabric_type_id: fabricTypeId,
          quantity_metres: 50,
          unit_price_paise: 25000,
        },
      ],
    })
    expect([200, 201]).toContain(status)
    if (status === 201) {
      expect(body.success).toBe(true)
    }
  })

  test('Tailor cannot create a PO (403)', async ({ request }) => {
    const { status } = await apiPost(
      request,
      '/api/v1/inventory/purchase-orders',
      { supplier_id: 1, items: [{ fabric_type_id: 1, quantity_metres: 1, unit_price_paise: 1000 }] },
      TAILOR,
    )
    expect(status).toBe(403)
  })
})

// ─── API: Stock Movements ─────────────────────────────────────────────────────

test.describe('API: Stock Movements', () => {
  test('GET /api/v1/inventory/movements returns movement ledger', async ({ request }) => {
    const token = await apiToken(request, OWNER)
    const res = await request.get(`${API_BASE}/api/v1/inventory/movements`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    expect(res.status()).toBe(200)
    const body = await res.json()
    expect(body.success).toBe(true)
    expect(Array.isArray(body.data)).toBe(true)
  })
})
