import { test, expect, type Page } from '@playwright/test'
import {
  login,
  OWNER, FRONT_DESK, CUTTER, TAILOR, QC, SUPERVISOR,
  INVENTORY, ACCOUNTANT, DELIVERY, IRONING, type Creds,
} from './helpers'

/**
 * Sidebar-by-role contract (SB-01..SB-05). Each role's sidebar must show exactly
 * the nav items its backend permissions justify (RolePermissionSeeder::MATRIX)
 * and hide the rest. Drives the real UI against the seeded role users.
 *
 * `present` = labels that MUST be visible; `absent` = labels that MUST NOT be.
 * (Settings is ungated and always present; Owner sees everything.)
 */
const SIDEBAR = 'nav'

async function navLabels(page: Page) {
  const sidebar = page.locator(SIDEBAR).filter({ hasText: 'Solo Shirts' })
  await expect(sidebar).toBeVisible()
  return sidebar
}

async function assertNav(page: Page, present: string[], absent: string[]) {
  const sidebar = await navLabels(page)
  for (const label of present) {
    // .first() disambiguates 'Settings' (also a footer gear link w/ aria-label).
    await expect(sidebar.getByRole('link', { name: label, exact: true }).first()).toBeVisible()
  }
  for (const label of absent) {
    await expect(sidebar.getByRole('link', { name: label, exact: true })).toHaveCount(0)
  }
}

const CASES: Array<{ name: string; creds: Creds; present: string[]; absent: string[] }> = [
  {
    name: 'Owner sees management + system nav',
    creds: OWNER,
    present: ['Dashboard', 'Finance', 'Reports', 'Audit', 'Admin', 'Customers', 'Settings'],
    absent: [],
  },
  {
    name: 'Front Desk',
    creds: FRONT_DESK,
    present: ['Front Desk', 'Orders', 'Measurements', 'Production', 'Scan', 'Customers', 'Settings'],
    absent: ['Dashboard', 'Deliveries', 'Finance', 'Admin', 'Cutting', 'Approvals'],
  },
  {
    name: 'Cutting Master',
    creds: CUTTER,
    present: ['Production', 'Cutting', 'Settings'],
    absent: ['Customers', 'Front Desk', 'Orders', 'Deliveries', 'Scan', 'Finance', 'Dashboard'],
  },
  {
    name: 'Tailor',
    creds: TAILOR,
    present: ['Production', 'Tailoring', 'Settings'],
    absent: ['Customers', 'Front Desk', 'Orders', 'Finance', 'Deliveries', 'Scan', 'Dashboard'],
  },
  {
    name: 'QC Supervisor',
    creds: QC,
    present: ['Approvals', 'Measurements', 'Production', 'Quality', 'Settings'],
    absent: ['Customers', 'Front Desk', 'Orders', 'Deliveries', 'Finance', 'Dashboard'],
  },
  {
    name: 'Production Supervisor',
    creds: SUPERVISOR,
    present: ['Dashboard', 'Approvals', 'Measurements', 'Production', 'Cutting', 'Tailoring', 'Quality', 'Damage', 'Rack', 'Deliveries', 'Scan', 'Reports', 'Settings'],
    absent: ['Front Desk', 'Orders', 'Customers', 'Finance', 'Admin', 'Inventory'],
  },
  {
    name: 'Inventory Manager',
    creds: INVENTORY,
    present: ['Production', 'Inventory', 'Damage', 'Settings'],
    absent: ['Customers', 'Front Desk', 'Orders', 'Finance', 'Deliveries', 'Dashboard'],
  },
  {
    name: 'Accountant',
    creds: ACCOUNTANT,
    present: ['Dashboard', 'Orders', 'Customers', 'Finance', 'Reports', 'Settings'],
    absent: ['Front Desk', 'Deliveries', 'Scan', 'Production', 'Cutting', 'Admin'],
  },
  {
    name: 'Delivery Staff',
    creds: DELIVERY,
    present: ['Production', 'Rack', 'Deliveries', 'Settings'],
    absent: ['Customers', 'Front Desk', 'Orders', 'Scan', 'Finance', 'Dashboard'],
  },
  {
    name: 'Ironing Master',
    creds: IRONING,
    present: ['Production', 'Settings'],
    absent: ['Customers', 'Front Desk', 'Orders', 'Finance', 'Dashboard'],
  },
]

test.describe('Sidebar by role', () => {
  for (const c of CASES) {
    test(c.name, async ({ page }) => {
      await login(page, c.creds)
      await assertNav(page, c.present, c.absent)
    })
  }
})
