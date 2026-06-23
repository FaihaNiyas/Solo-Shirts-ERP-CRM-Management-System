/**
 * api-smoke.spec.ts
 *
 * Direct API smoke tests — verifies every major endpoint returns the correct
 * HTTP status code and response shape. No browser involved.
 *
 * Covers: health, auth, dashboard, reports, audit, search, notifications,
 *         QC defect categories, documents, QR sign/decode.
 *
 * Module-specific endpoints (customers, orders, finance, inventory, etc.)
 * are tested in their own spec files.
 */

import { test, expect } from '@playwright/test'
import {
  login, apiToken, OWNER, FRONT_DESK, TAILOR, ACCOUNTANT, SUPERVISOR,
  API_BASE,
} from './helpers'

// ─── Health ───────────────────────────────────────────────────────────────────

test.describe('API: Health', () => {
  test('GET /api/v1/health returns system status', async ({ request }) => {
    const res = await request.get(`${API_BASE}/api/v1/health`, {
      headers: { Accept: 'application/json' },
    })
    // Either 200 (all up) or 503 (dependency down — Redis not running in dev)
    expect([200, 503]).toContain(res.status())
    const body = await res.json()
    expect(body).toHaveProperty('data')
    expect(body.data).toHaveProperty('db')
    expect(body.data.db).toBe(true) // DB must always be up
    expect(body.data).toHaveProperty('php')
    expect(body.data).toHaveProperty('laravel')
  })
})

// ─── Authentication ───────────────────────────────────────────────────────────

test.describe('API: Authentication', () => {
  test('POST /api/v1/auth/login returns token for valid credentials', async ({ request }) => {
    const res = await request.post(`${API_BASE}/api/v1/auth/login`, {
      headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
      data: { email: 'owner@soloshirts.test', password: 'password' },
    })
    expect(res.status()).toBe(200)
    const body = await res.json()
    expect(body.success).toBe(true)
    expect(body.data).toHaveProperty('token')
    expect(body.data).toHaveProperty('user')
    expect(body.data.user.email).toBe('owner@soloshirts.test')
  })

  test('POST /api/v1/auth/login returns 401 for wrong password', async ({ request }) => {
    const res = await request.post(`${API_BASE}/api/v1/auth/login`, {
      headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
      data: { email: 'owner@soloshirts.test', password: 'WRONG_PASSWORD' },
    })
    expect(res.status()).toBe(401)
  })

  test('POST /api/v1/auth/login returns 422 for missing email', async ({ request }) => {
    const res = await request.post(`${API_BASE}/api/v1/auth/login`, {
      headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
      data: { password: 'password' },
    })
    expect(res.status()).toBe(422)
  })

  test('GET /api/v1/auth/me returns current user', async ({ request }) => {
    const token = await apiToken(request, OWNER)
    const res = await request.get(`${API_BASE}/api/v1/auth/me`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    expect(res.status()).toBe(200)
    const body = await res.json()
    // Auth/me returns { data: { user: {...}, abilities: [...] } }
    expect(body.data.user.email).toBe('owner@soloshirts.test')
    expect(body.data.user.roles).toContain('Owner')
  })

  test('GET /api/v1/auth/me returns 401 without token', async ({ request }) => {
    const res = await request.get(`${API_BASE}/api/v1/auth/me`, {
      headers: { Accept: 'application/json' },
    })
    expect(res.status()).toBe(401)
  })

  test('all 11 seeded staff roles can log in', async ({ request }) => {
    const staffCredentials = [
      { email: 'frontdesk@soloshirts.test', role: 'Front Desk' },
      { email: 'cutter@soloshirts.test', role: 'Cutting Master' },
      { email: 'tailor1@soloshirts.test', role: 'Tailor' },
      { email: 'tailor2@soloshirts.test', role: 'Tailor' },
      { email: 'ironing@soloshirts.test', role: 'Ironing Master' },
      { email: 'qc@soloshirts.test', role: 'QC Supervisor' },
      { email: 'supervisor@soloshirts.test', role: 'Production Supervisor' },
      { email: 'inventory@soloshirts.test', role: 'Inventory Manager' },
      { email: 'accountant@soloshirts.test', role: 'Accountant' },
      { email: 'delivery@soloshirts.test', role: 'Delivery Staff' },
    ]

    for (const staff of staffCredentials) {
      const res = await request.post(`${API_BASE}/api/v1/auth/login`, {
        headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
        data: { email: staff.email, password: 'password' },
      })
      expect(res.status(), `Login failed for ${staff.email}`).toBe(200)
      const body = await res.json()
      expect(body.data.user.email).toBe(staff.email)
      expect(body.data.user.roles).toContain(staff.role)
    }
  })
})

// ─── Dashboard ────────────────────────────────────────────────────────────────

test.describe('API: Dashboard', () => {
  test('GET /api/v1/dashboard/summary returns KPIs for Owner', async ({ request }) => {
    const token = await apiToken(request, OWNER)
    const res = await request.get(`${API_BASE}/api/v1/dashboard/summary`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    expect(res.status()).toBe(200)
    const body = await res.json()
    expect(body.success).toBe(true)
    expect(body.data).toBeTruthy()
  })

  test('GET /api/v1/dashboard/summary accessible by Supervisor', async ({ request }) => {
    const token = await apiToken(request, SUPERVISOR)
    const res = await request.get(`${API_BASE}/api/v1/dashboard/summary`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    expect(res.status()).toBe(200)
  })

  test('GET /api/v1/dashboard/summary returns 403 for Front Desk (finance data restricted)', async ({ request }) => {
    // The dashboard/summary endpoint includes financial data restricted to Owner/Accountant
    const token = await apiToken(request, FRONT_DESK)
    const res = await request.get(`${API_BASE}/api/v1/dashboard/summary`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    // Front Desk cannot access finance dashboard summary
    expect(res.status()).toBe(403)
  })
})

// ─── Reports ─────────────────────────────────────────────────────────────────

test.describe('API: Reports', () => {
  test('GET /api/v1/reports returns available report kinds for Owner', async ({ request }) => {
    const token = await apiToken(request, OWNER)
    const res = await request.get(`${API_BASE}/api/v1/reports`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    expect(res.status()).toBe(200)
    const body = await res.json()
    expect(body.success).toBe(true)
    // Returns {kinds: [...]} — not a jobs list
    const kinds = body.data.kinds as string[]
    expect(Array.isArray(kinds)).toBe(true)
    expect(kinds).toContain('orders')
    expect(kinds).toContain('finance_summary')
  })

  test('POST /api/v1/reports/run queues a report job', async ({ request }) => {
    const token = await apiToken(request, OWNER)
    const res = await request.post(`${API_BASE}/api/v1/reports/run`, {
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json',
        'Content-Type': 'application/json',
      },
      data: {
        kind: 'orders',
        from: new Date(Date.now() - 30 * 86400000).toISOString().slice(0, 10),
        to: new Date().toISOString().slice(0, 10),
      },
    })
    expect([200, 201, 202]).toContain(res.status())
    const body = await res.json()
    expect(body.success).toBe(true)
    // Returns job with pending status
    expect(body.data).toHaveProperty('kind')
    expect(body.data).toHaveProperty('status')
  })

  test('GET /api/v1/reports returns 403 for Tailor', async ({ request }) => {
    const token = await apiToken(request, TAILOR)
    const res = await request.get(`${API_BASE}/api/v1/reports`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    expect(res.status()).toBe(403)
  })

  test('POST /api/v1/reports/run returns 403 for Tailor', async ({ request }) => {
    const token = await apiToken(request, TAILOR)
    const res = await request.post(`${API_BASE}/api/v1/reports/run`, {
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json',
        'Content-Type': 'application/json',
      },
      data: { kind: 'orders', from: '2024-01-01', to: '2024-12-31' },
    })
    expect(res.status()).toBe(403)
  })
})

// ─── Audit ────────────────────────────────────────────────────────────────────

test.describe('API: Audit', () => {
  test('GET /api/v1/audit/activities returns activity log for Owner', async ({ request }) => {
    const token = await apiToken(request, OWNER)
    const res = await request.get(`${API_BASE}/api/v1/audit/activities`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    expect(res.status()).toBe(200)
    const body = await res.json()
    expect(body.success).toBe(true)
    expect(Array.isArray(body.data)).toBe(true)
  })

  test('GET /api/v1/audit/activities returns 403 for Front Desk', async ({ request }) => {
    const token = await apiToken(request, FRONT_DESK)
    const res = await request.get(`${API_BASE}/api/v1/audit/activities`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    expect(res.status()).toBe(403)
  })

  test('GET /api/v1/audit/activities returns 403 for Tailor', async ({ request }) => {
    const token = await apiToken(request, TAILOR)
    const res = await request.get(`${API_BASE}/api/v1/audit/activities`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    expect(res.status()).toBe(403)
  })
})

// ─── Search ───────────────────────────────────────────────────────────────────

test.describe('API: Global Search', () => {
  test('GET /api/v1/search finds seeded customers', async ({ request }) => {
    const token = await apiToken(request, OWNER)
    const res = await request.get(`${API_BASE}/api/v1/search?q=DEMO-CUST`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    expect(res.status()).toBe(200)
    const body = await res.json()
    expect(body.success).toBe(true)
  })

  test('GET /api/v1/search finds seeded orders', async ({ request }) => {
    const token = await apiToken(request, OWNER)
    const res = await request.get(`${API_BASE}/api/v1/search?q=DEMO-ORD`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    expect(res.status()).toBe(200)
  })

  test('GET /api/v1/search requires authentication', async ({ request }) => {
    const res = await request.get(`${API_BASE}/api/v1/search?q=test`, {
      headers: { Accept: 'application/json' },
    })
    expect(res.status()).toBe(401)
  })
})

// ─── Notifications ────────────────────────────────────────────────────────────

test.describe('API: Notifications', () => {
  test('GET /api/v1/notifications returns list for Owner', async ({ request }) => {
    const token = await apiToken(request, OWNER)
    const res = await request.get(`${API_BASE}/api/v1/notifications`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    expect(res.status()).toBe(200)
    const body = await res.json()
    expect(body.success).toBe(true)
  })
})

// ─── QC Defect Categories ────────────────────────────────────────────────────

test.describe('API: QC Defect Categories', () => {
  test('GET /api/v1/qc/defects/categories returns defect types', async ({ request }) => {
    const token = await apiToken(request, OWNER)
    const res = await request.get(`${API_BASE}/api/v1/qc/defects/categories`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    expect(res.status()).toBe(200)
    const body = await res.json()
    expect(body.success).toBe(true)
    expect(Array.isArray(body.data)).toBe(true)
    expect(body.data.length).toBeGreaterThan(0)
  })

  test('GET /api/v1/qc/defects/analytics returns defect stats', async ({ request }) => {
    const token = await apiToken(request, OWNER)
    const res = await request.get(`${API_BASE}/api/v1/qc/defects/analytics`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    expect(res.status()).toBe(200)
  })
})

// ─── Documents ────────────────────────────────────────────────────────────────

test.describe('API: Documents', () => {
  test('GET /api/v1/documents returns document list for Owner', async ({ request }) => {
    const token = await apiToken(request, OWNER)
    const res = await request.get(`${API_BASE}/api/v1/documents`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    expect(res.status()).toBe(200)
    const body = await res.json()
    expect(body.success).toBe(true)
    expect(Array.isArray(body.data)).toBe(true)
  })
})

// ─── QR Sign / Decode ────────────────────────────────────────────────────────

test.describe('API: QR Sign/Decode', () => {
  test('GET /api/v1/qr/sign returns a signed payload', async ({ request }) => {
    const token = await apiToken(request, OWNER)
    const res = await request.get(
      `${API_BASE}/api/v1/qr/sign?type=customer&id=1`,
      { headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' } },
    )
    // May be 200 (returns payload) or 404 (customer 1 doesn't exist)
    expect([200, 404]).toContain(res.status())
    if (res.status() === 200) {
      const body = await res.json()
      expect(body.data).toHaveProperty('payload')
    }
  })

  test('GET /api/v1/qr/decode/:payload rejects invalid payload', async ({ request }) => {
    const token = await apiToken(request, OWNER)
    const res = await request.get(`${API_BASE}/api/v1/qr/decode/INVALID_GARBAGE`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    expect([400, 404, 422]).toContain(res.status())
  })
})

// ─── Dashboard UI: role-specific widget visibility ────────────────────────────
// Note: full dashboard UI tests are in their own navigation.spec.ts.
// These supplement with role-based widget visibility.

test.describe('Dashboard page (UI)', () => {
  test('Owner sees Revenue and Outstanding widgets on dashboard', async ({ page }) => {
    await login(page, OWNER)
    await page.goto('/dashboard')
    await expect(page.getByRole('heading', { level: 1, name: 'Dashboard' })).toBeVisible()
    // Owner-only financial widgets
    await expect(page.getByText('Revenue')).toBeVisible()
    await expect(page.getByText('Outstanding')).toBeVisible()
    // Production widgets visible to all
    await expect(page.getByText('In Production', { exact: true })).toBeVisible()
  })

  test('Front Desk can view the dashboard page', async ({ page }) => {
    await login(page, FRONT_DESK)
    await page.goto('/dashboard')
    await expect(page.getByRole('heading', { level: 1, name: 'Dashboard' })).toBeVisible()
    // Front Desk sees operational metrics
    await expect(page.getByText("Today's Orders")).toBeVisible()
  })

  test('Supervisor sees In Production metric on dashboard', async ({ page }) => {
    await login(page, SUPERVISOR)
    await page.goto('/dashboard')
    await expect(page.getByRole('heading', { level: 1, name: 'Dashboard' })).toBeVisible()
    await expect(page.getByText('In Production', { exact: true })).toBeVisible()
  })
})
