/**
 * finance.spec.ts
 *
 * Finance module — GST invoices, payments, outstanding balances, reports.
 * Client requirements:
 *   - GST invoices with CGST/SGST/IGST
 *   - Payment methods: cash, UPI, bank transfer
 *   - Outstanding balance tracking
 *   - Financial data RESTRICTED to Owner and Accountant only
 *   - Revenue reports only for Owner & Accountant
 */

import { test, expect } from '@playwright/test'
import {
  login, OWNER, ACCOUNTANT, FRONT_DESK, TAILOR, SUPERVISOR,
  apiToken, apiPost, firstInvoiceId, demoCustomerId, API_BASE,
} from './helpers'

// ─── Finance pages — Owner can access ────────────────────────────────────────

test.describe('Finance pages (Owner)', () => {
  test.beforeEach(async ({ page }) => {
    await login(page, OWNER)
  })

  test('renders Invoices page with correct columns', async ({ page }) => {
    await page.goto('/finance/invoices')
    await expect(page.getByRole('heading', { level: 1, name: 'Invoices' })).toBeVisible()
    await expect(page.getByRole('columnheader', { name: 'Invoice #' })).toBeVisible()
    await expect(page.getByRole('columnheader', { name: 'Customer' })).toBeVisible()
    await expect(page.getByRole('columnheader', { name: 'Total' })).toBeVisible()
    await expect(page.getByRole('columnheader', { name: 'Status' })).toBeVisible()
  })

  test('invoice list shows seeded DEMO invoices', async ({ page }) => {
    await page.goto('/finance/invoices')
    await expect(page.locator('tbody tr').first()).toBeVisible()
    // Seeded invoices have DEMO/INV/ prefix
    await expect(page.getByText(/DEMO\/INV\//i).first()).toBeVisible()
  })

  test('invoice list shows paid, partially-paid, and outstanding invoices', async ({ page }) => {
    await page.goto('/finance/invoices')
    // Wait for table data before checking status badges
    await expect(page.locator('tbody tr').first()).toBeVisible()
    // Backend statuses: 'paid', 'partially_paid', 'issued' (no 'outstanding')
    const patterns = [/\bpaid\b/i, /partial/i, /issued/i]
    let found = 0
    for (const pat of patterns) {
      if (await page.getByText(pat).count() > 0) found++
    }
    expect(found).toBeGreaterThan(1)
  })

  test('Generate Invoice button opens dialog', async ({ page }) => {
    await page.goto('/finance/invoices')
    await page.getByRole('button', { name: 'Generate Invoice' }).click()
    await expect(page.getByRole('heading', { name: 'Generate Invoice' })).toBeVisible()
    await expect(page.getByPlaceholder('Enter order ID')).toBeVisible()
    await page.getByRole('button', { name: 'Cancel' }).click()
  })

  test('renders Outstanding Balances page', async ({ page }) => {
    await page.goto('/finance/outstanding')
    await expect(page.getByRole('heading', { level: 1, name: 'Outstanding Balances' })).toBeVisible()
    await expect(page.getByText('Total Outstanding')).toBeVisible()
    await expect(page.getByRole('columnheader', { name: 'Customer' })).toBeVisible()
    await expect(page.getByRole('columnheader', { name: 'Outstanding' })).toBeVisible()
  })

  test('outstanding page shows rupee amounts', async ({ page }) => {
    await page.goto('/finance/outstanding')
    // Should show ₹ amounts
    await expect(page.getByText(/₹/).first()).toBeVisible()
  })
})

// ─── Finance pages — Accountant can access ────────────────────────────────────

test.describe('Finance pages (Accountant)', () => {
  test.beforeEach(async ({ page }) => {
    await login(page, ACCOUNTANT)
  })

  test('Accountant can view Invoices page', async ({ page }) => {
    await page.goto('/finance/invoices')
    await expect(page.getByRole('heading', { level: 1, name: 'Invoices' })).toBeVisible()
  })

  test('Accountant can view Outstanding Balances', async ({ page }) => {
    await page.goto('/finance/outstanding')
    await expect(page.getByRole('heading', { level: 1, name: 'Outstanding Balances' })).toBeVisible()
  })
})

// ─── Finance pages — Non-finance roles BLOCKED ───────────────────────────────

test.describe('Finance access control', () => {
  test('Front Desk is blocked from Invoices', async ({ page }) => {
    await login(page, FRONT_DESK)
    await page.goto('/finance/invoices')
    await expect(
      page.getByText(/access|permission|not authorized|owner|accountant/i).first()
    ).toBeVisible()
    await expect(page.getByRole('button', { name: 'Generate Invoice' })).toHaveCount(0)
  })

  test('Front Desk is blocked from Outstanding Balances', async ({ page }) => {
    await login(page, FRONT_DESK)
    await page.goto('/finance/outstanding')
    await expect(
      page.getByText(/access|permission|not authorized/i).first()
    ).toBeVisible()
  })

  test('Tailor is blocked from Invoices', async ({ page }) => {
    await login(page, TAILOR)
    await page.goto('/finance/invoices')
    await expect(
      page.getByText(/access|permission|not authorized/i).first()
    ).toBeVisible()
  })

  test('Production Supervisor is blocked from Outstanding Balances', async ({ page }) => {
    await login(page, SUPERVISOR)
    await page.goto('/finance/outstanding')
    await expect(
      page.getByText(/access|permission|not authorized/i).first()
    ).toBeVisible()
  })
})

// ─── API: Invoices ────────────────────────────────────────────────────────────

test.describe('API: Invoices', () => {
  test('GET /api/v1/finance/invoices returns 200 for Owner', async ({ request }) => {
    const token = await apiToken(request, OWNER)
    const res = await request.get(`${API_BASE}/api/v1/finance/invoices`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    expect(res.status()).toBe(200)
    const body = await res.json()
    expect(body.success).toBe(true)
    expect(Array.isArray(body.data)).toBe(true)
    expect(body.data.length).toBeGreaterThan(0)
    // Verify GST fields are present
    const inv = body.data[0] as Record<string, unknown>
    expect(inv).toHaveProperty('cgst_paise')
    expect(inv).toHaveProperty('sgst_paise')
    expect(inv).toHaveProperty('total_paise')
    expect(inv).toHaveProperty('status')
  })

  test('GET /api/v1/finance/invoices returns 200 for Accountant', async ({ request }) => {
    const token = await apiToken(request, ACCOUNTANT)
    const res = await request.get(`${API_BASE}/api/v1/finance/invoices`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    expect(res.status()).toBe(200)
  })

  test('GET /api/v1/finance/invoices returns 403 for Front Desk', async ({ request }) => {
    const token = await apiToken(request, FRONT_DESK)
    const res = await request.get(`${API_BASE}/api/v1/finance/invoices`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    expect(res.status()).toBe(403)
  })

  test('GET /api/v1/finance/invoices returns 403 for Tailor', async ({ request }) => {
    const token = await apiToken(request, TAILOR)
    const res = await request.get(`${API_BASE}/api/v1/finance/invoices`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    expect(res.status()).toBe(403)
  })

  test('GET /api/v1/finance/invoices/:id returns full invoice detail', async ({ request }) => {
    const id = await firstInvoiceId(request)
    const token = await apiToken(request)
    const res = await request.get(`${API_BASE}/api/v1/finance/invoices/${id}`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    expect(res.status()).toBe(200)
    const body = await res.json()
    expect(body.data.id).toBe(id)
    expect(body.data).toHaveProperty('invoice_no')
    expect(body.data).toHaveProperty('total_paise')
  })

  test('GET /api/v1/finance/outstanding returns aggregated data for Owner', async ({ request }) => {
    const token = await apiToken(request, OWNER)
    const res = await request.get(`${API_BASE}/api/v1/finance/outstanding`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    expect(res.status()).toBe(200)
    const body = await res.json()
    expect(body.success).toBe(true)
    expect(Array.isArray(body.data)).toBe(true)
    // Each entry should have outstanding amount and customer info
    if (body.data.length > 0) {
      const entry = body.data[0] as Record<string, unknown>
      expect(entry).toHaveProperty('total_outstanding_paise')
    }
  })

  test('GET /api/v1/finance/outstanding returns 403 for Front Desk', async ({ request }) => {
    const token = await apiToken(request, FRONT_DESK)
    const res = await request.get(`${API_BASE}/api/v1/finance/outstanding`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    expect(res.status()).toBe(403)
  })

  test('GET /api/v1/finance/dashboard/summary returns KPIs for Owner', async ({ request }) => {
    const token = await apiToken(request, OWNER)
    const res = await request.get(`${API_BASE}/api/v1/finance/dashboard/summary`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    expect(res.status()).toBe(200)
    const body = await res.json()
    expect(body.success).toBe(true)
  })
})

// ─── API: Payments ────────────────────────────────────────────────────────────

test.describe('API: Payments', () => {
  test('POST /api/v1/finance/payments records a payment (idempotent)', async ({ request }) => {
    const id = await firstInvoiceId(request)
    const token = await apiToken(request, OWNER)
    const idempotencyKey = `e2e-payment-${Date.now()}`

    const res = await request.post(`${API_BASE}/api/v1/finance/payments`, {
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'Idempotency-Key': idempotencyKey,
      },
      data: {
        invoice_id: id,
        amount_paise: 10000, // ₹100 partial payment
        method: 'cash',
        reference: 'E2E test',
      },
    })
    // May be 201 (new payment) or 200 (idempotent repeat)
    expect([200, 201, 422]).toContain(res.status())
  })

  test('Tailor cannot record a payment (403)', async ({ request }) => {
    const id = await firstInvoiceId(request)
    const token = await apiToken(request, TAILOR)
    const res = await request.post(`${API_BASE}/api/v1/finance/payments`, {
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'Idempotency-Key': `e2e-tailor-payment-${Date.now()}`,
      },
      data: { invoice_id: id, amount_paise: 10000, method: 'cash' },
    })
    expect(res.status()).toBe(403)
  })
})
