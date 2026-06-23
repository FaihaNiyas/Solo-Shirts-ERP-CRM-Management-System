/**
 * admin.spec.ts
 *
 * Tests for Admin / User Management and Branch Management.
 * Covers: user creation, role assignment, branch listing, RBAC (non-Owner blocked).
 *
 * Client requirement: Owner has full access to all modules, settings, reports, financials.
 *                     Non-admin roles must be blocked from admin screens.
 */

import { test, expect } from '@playwright/test'
import { login, OWNER, FRONT_DESK, TAILOR, SUPERVISOR, apiToken, API_BASE } from './helpers'

// ─── Branch Management ────────────────────────────────────────────────────────

test.describe('Branch Management (Owner)', () => {
  test.beforeEach(async ({ page }) => {
    await login(page, OWNER)
  })

  test('renders the Branches page with the HQ branch', async ({ page }) => {
    await page.goto('/admin/branches')
    await expect(page.getByRole('heading', { level: 1, name: 'Branches' })).toBeVisible()
    // Seeded HQ branch must be listed
    await expect(page.getByText('Head Office').or(page.getByText('HQ')).first()).toBeVisible()
  })

  test('branch list shows HQ branch code', async ({ page }) => {
    await page.goto('/admin/branches')
    // Branches are displayed as cards (not a table), each showing the code
    await expect(page.getByText('HQ').first()).toBeVisible()
  })
})

// ─── User Management ─────────────────────────────────────────────────────────

test.describe('User Management (Owner)', () => {
  test.beforeEach(async ({ page }) => {
    await login(page, OWNER)
  })

  test('renders the Users page with seeded staff', async ({ page }) => {
    await page.goto('/admin/users')
    await expect(page.getByRole('heading', { level: 1, name: 'Users' })).toBeVisible()
    // Table headers
    await expect(page.getByRole('columnheader', { name: 'Name' })).toBeVisible()
    await expect(page.getByRole('columnheader', { name: 'Email' })).toBeVisible()
    await expect(page.getByRole('columnheader', { name: 'Role' })).toBeVisible()
  })

  test('shows all seeded staff in the user list', async ({ page }) => {
    await page.goto('/admin/users')
    // All DemoDataSeeder users should be visible
    await expect(page.getByText('tailor1@soloshirts.test')).toBeVisible()
    await expect(page.getByText('frontdesk@soloshirts.test')).toBeVisible()
    await expect(page.getByText('accountant@soloshirts.test')).toBeVisible()
    await expect(page.getByText('supervisor@soloshirts.test')).toBeVisible()
    await expect(page.getByText('qc@soloshirts.test')).toBeVisible()
    await expect(page.getByText('inventory@soloshirts.test')).toBeVisible()
    await expect(page.getByText('delivery@soloshirts.test')).toBeVisible()
  })

  test('owner can open the Add User drawer', async ({ page }) => {
    await page.goto('/admin/users')
    await page.getByRole('button', { name: 'Add User' }).click()
    // The drawer should show the creation form
    await expect(page.getByLabel('Full Name')).toBeVisible()
    await expect(page.getByLabel('Email')).toBeVisible()
    await expect(page.getByLabel('Initial Password')).toBeVisible()
    await expect(page.getByLabel('Role')).toBeVisible()
  })

  test('owner can create a new user with a specific role', async ({ page }) => {
    await page.goto('/admin/users')
    await page.getByRole('button', { name: 'Add User' }).click()

    const unique = `e2e_admin_${Date.now()}@soloshirts.test`
    await page.getByLabel('Full Name').fill('E2E Admin Test')
    await page.getByLabel('Email').fill(unique)
    await page.getByLabel('Initial Password').fill('password123')

    // Select the "Front Desk" role from the dropdown
    const roleSelect = page.getByLabel('Role')
    await roleSelect.selectOption({ label: 'Front Desk' })

    await page.getByRole('button', { name: 'Create User' }).click()

    // Success toast and user appears in the list
    await expect(page.getByText(/created|success/i).first()).toBeVisible({ timeout: 10_000 })
    await expect(page.getByText(unique)).toBeVisible()
  })

  test('create user form validates required fields', async ({ page }) => {
    await page.goto('/admin/users')
    await page.getByRole('button', { name: 'Add User' }).click()
    // Submit button should be disabled when required fields are empty
    await expect(page.getByRole('button', { name: 'Create User' })).toBeDisabled()
  })

  test('owner can view a user detail page', async ({ page }) => {
    await page.goto('/admin/users')
    // Click the first row to go to user detail
    await page.locator('tbody tr').first().click()
    await expect(page).toHaveURL(/\/admin\/users\/\d+/)
  })
})

// ─── RBAC: Non-Owner roles blocked from admin ─────────────────────────────────

test.describe('Admin access control', () => {
  test('Front Desk cannot access Branches admin', async ({ page }) => {
    await login(page, FRONT_DESK)
    await page.goto('/admin/branches')
    // Must show an access-denied message and no "Add Branch" button
    await expect(
      page.getByText(/access|permission|not authorized|owner/i).first()
    ).toBeVisible()
    await expect(page.getByRole('button', { name: /add branch/i })).toHaveCount(0)
  })

  test('Front Desk cannot access Users admin', async ({ page }) => {
    await login(page, FRONT_DESK)
    await page.goto('/admin/users')
    await expect(
      page.getByText(/access|permission|not authorized|owner/i).first()
    ).toBeVisible()
    await expect(page.getByRole('button', { name: 'Add User' })).toHaveCount(0)
  })

  test('Tailor cannot access Users admin', async ({ page }) => {
    await login(page, TAILOR)
    await page.goto('/admin/users')
    await expect(
      page.getByText(/access|permission|not authorized|owner/i).first()
    ).toBeVisible()
  })

  test('Production Supervisor cannot access Users admin', async ({ page }) => {
    await login(page, SUPERVISOR)
    await page.goto('/admin/users')
    await expect(
      page.getByText(/access|permission|not authorized|owner/i).first()
    ).toBeVisible()
  })
})

// ─── API: User Management endpoints ──────────────────────────────────────────

test.describe('API: User Management', () => {
  test('GET /api/v1/users returns paginated user list for Owner', async ({ request }) => {
    const token = await apiToken(request, OWNER)
    const res = await request.get(`${API_BASE}/api/v1/users`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    expect(res.status()).toBe(200)
    const body = await res.json()
    expect(body.success).toBe(true)
    expect(Array.isArray(body.data)).toBe(true)
    expect(body.data.length).toBeGreaterThan(0)
  })

  test('GET /api/v1/users returns 403 for Tailor', async ({ request }) => {
    const token = await apiToken(request, TAILOR)
    const res = await request.get(`${API_BASE}/api/v1/users`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    expect(res.status()).toBe(403)
  })

  test('POST /api/v1/users creates a user and returns 201', async ({ request }) => {
    const token = await apiToken(request, OWNER)
    const unique = `api_e2e_${Date.now()}@soloshirts.test`
    const res = await request.post(`${API_BASE}/api/v1/users`, {
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json',
        'Content-Type': 'application/json',
      },
      data: {
        name: 'API Test User',
        email: unique,
        password: 'password123',
        password_confirmation: 'password123',
        role: 'Front Desk',
      },
    })
    expect(res.status()).toBe(201)
    const body = await res.json()
    expect(body.success).toBe(true)
    expect(body.data.email).toBe(unique)
  })

  test('POST /api/v1/users returns 422 for duplicate email', async ({ request }) => {
    const token = await apiToken(request, OWNER)
    const res = await request.post(`${API_BASE}/api/v1/users`, {
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json',
        'Content-Type': 'application/json',
      },
      data: {
        name: 'Duplicate',
        email: 'owner@soloshirts.test', // already exists
        password: 'password123',
        password_confirmation: 'password123',
        role: 'Tailor',
      },
    })
    expect(res.status()).toBe(422)
  })

  test('GET /api/v1/branches returns branch list', async ({ request }) => {
    const token = await apiToken(request, OWNER)
    const res = await request.get(`${API_BASE}/api/v1/branches`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    expect(res.status()).toBe(200)
    const body = await res.json()
    expect(Array.isArray(body.data)).toBe(true)
    expect(body.data.length).toBeGreaterThan(0)
    // The HQ branch must exist
    const hq = (body.data as Array<{ code: string }>).find((b) => b.code === 'HQ')
    expect(hq).toBeTruthy()
  })
})
