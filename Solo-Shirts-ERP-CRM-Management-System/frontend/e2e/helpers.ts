import { type Page, type APIRequestContext, expect } from '@playwright/test'

export const API_BASE = process.env.E2E_API_URL ?? 'http://localhost:8000'

// ─── Role credentials (all seeded by DemoDataSeeder, password = "password") ───
export const OWNER       = { email: 'owner@soloshirts.test',      password: 'password' }
export const FRONT_DESK  = { email: 'frontdesk@soloshirts.test',  password: 'password' }
export const CUTTER      = { email: 'cutter@soloshirts.test',     password: 'password' }
export const TAILOR      = { email: 'tailor1@soloshirts.test',    password: 'password' }
export const TAILOR2     = { email: 'tailor2@soloshirts.test',    password: 'password' }
export const IRONING     = { email: 'ironing@soloshirts.test',    password: 'password' }
export const QC          = { email: 'qc@soloshirts.test',         password: 'password' }
export const SUPERVISOR  = { email: 'supervisor@soloshirts.test', password: 'password' }
export const INVENTORY   = { email: 'inventory@soloshirts.test',  password: 'password' }
export const ACCOUNTANT  = { email: 'accountant@soloshirts.test', password: 'password' }
export const DELIVERY    = { email: 'delivery@soloshirts.test',   password: 'password' }

export type Creds = { email: string; password: string }

/**
 * Log in through the real UI. Token is stored in sessionStorage so the
 * AuthGuard rehydrates on every subsequent page.goto() within the context.
 */
export async function login(page: Page, creds: Creds = OWNER): Promise<void> {
  await page.goto('/login')
  // Wait for React hydration so onSubmit handler is attached before filling/clicking
  await page.waitForLoadState('networkidle')
  await page.locator('#email').fill(creds.email)
  await page.locator('#password').fill(creds.password)
  await page.getByRole('button', { name: 'Sign in' }).click()
  await page.waitForURL((url) => !url.pathname.endsWith('/login'), { timeout: 20_000 })
}

/** Obtain a Bearer token directly from the API (used in test setup / API tests).
 *  Also calls switch-branch so the token has branch context for write operations.
 */
export async function apiToken(request: APIRequestContext, creds: Creds = OWNER): Promise<string> {
  const res = await request.post(`${API_BASE}/api/v1/auth/login`, {
    headers: { Accept: 'application/json' },
    data: creds,
  })
  expect(res.ok()).toBeTruthy()
  const body = await res.json()
  const token = body.data.token as string
  const branchId: number = body.data.user?.branch_id ?? 1

  // Activate branch context on the token — required for write operations
  await request.post(`${API_BASE}/api/v1/auth/switch-branch`, {
    headers: {
      Authorization: `Bearer ${token}`,
      Accept: 'application/json',
      'Content-Type': 'application/json',
    },
    data: { branch_id: branchId },
  })
  return token
}

/** Authenticated GET helper — returns parsed JSON body. */
export async function apiGet(
  request: APIRequestContext,
  path: string,
  creds: Creds = OWNER,
): Promise<Record<string, unknown>> {
  const token = await apiToken(request, creds)
  const res = await request.get(`${API_BASE}${path}`, {
    headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
  })
  return res.json()
}

/** Authenticated POST helper — returns parsed JSON body. */
export async function apiPost(
  request: APIRequestContext,
  path: string,
  data: Record<string, unknown>,
  creds: Creds = OWNER,
): Promise<{ status: number; body: Record<string, unknown> }> {
  const token = await apiToken(request, creds)
  const res = await request.post(`${API_BASE}${path}`, {
    headers: {
      Authorization: `Bearer ${token}`,
      Accept: 'application/json',
      'Content-Type': 'application/json',
      'Idempotency-Key': `e2e-${Date.now()}-${Math.random().toString(36).slice(2)}`,
    },
    data,
  })
  return { status: res.status(), body: await res.json() }
}

// ─── Seeded record helpers ─────────────────────────────────────────────────

/** First customer id from the seeded set. */
export async function firstCustomerId(request: APIRequestContext): Promise<number> {
  const body = await apiGet(request, '/api/v1/customers')
  const rows = body.data as Array<{ id: number }>
  return rows[0].id
}

/** First customer id whose code matches DEMO-CUST prefix. */
export async function demoCustomerId(request: APIRequestContext): Promise<number> {
  const body = await apiGet(request, '/api/v1/customers?per_page=5')
  const rows = body.data as Array<{ id: number; customer_code: string }>
  const demo = rows.find((r) => r.customer_code?.startsWith('DEMO-'))
  return (demo ?? rows[0]).id
}

/** First order id from the seeded set. */
export async function firstOrderId(request: APIRequestContext): Promise<number> {
  const body = await apiGet(request, '/api/v1/orders?per_page=5')
  const rows = body.data as Array<{ id: number }>
  return rows[0].id
}

/** First invoice id from the seeded set (Owner/Accountant only). */
export async function firstInvoiceId(request: APIRequestContext): Promise<number> {
  const body = await apiGet(request, '/api/v1/finance/invoices?per_page=5')
  const rows = body.data as Array<{ id: number }>
  return rows[0].id
}

/** First fabric roll id from the seeded set. */
export async function firstFabricRollId(request: APIRequestContext): Promise<number> {
  const body = await apiGet(request, '/api/v1/inventory/fabric-rolls?per_page=5')
  const rows = body.data as Array<{ id: number }>
  return rows[0].id
}

/** First approved measurement version ID for a customer (used when creating orders). */
export async function approvedVersionId(
  request: APIRequestContext,
  customerId: number,
  creds: Creds = OWNER,
): Promise<number> {
  const profilesBody = await apiGet(request, `/api/v1/customers/${customerId}/measurements`, creds)
  const profiles = profilesBody.data as Array<{ id: number }>
  const versionsBody = await apiGet(request, `/api/v1/measurements/profiles/${profiles[0].id}/versions`, creds)
  const versions = versionsBody.data as Array<{ id: number; status: string }>
  const approved = versions.find((v) => v.status === 'approved')
  return (approved ?? versions[0]).id
}
