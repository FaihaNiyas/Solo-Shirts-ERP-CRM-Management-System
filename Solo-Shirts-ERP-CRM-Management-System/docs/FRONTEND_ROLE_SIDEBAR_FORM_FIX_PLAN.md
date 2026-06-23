# Frontend Role / Sidebar / Form Fix Plan — Solo Shirts India ERP

**Date:** 2026-06-13 · Ordered by priority. Test-first. Source of truth: `RolePermissionSeeder::MATRIX`.

> **This pass implements only §1 + §2 (role constants + sidebar).** §3–§10 are logged follow-ups; do not touch in this pass.

## 1. Role constant mismatches (RC-01, RC-02) — Low
- **Files:** `src/lib/auth/permissions.ts`; refs in `SideNav.tsx`, `landing.ts`, `cutting/page.tsx`, `tailoring/page.tsx`.
- **Test first:** none new (type-check is the guard — renamed keys must compile).
- **Fix:** rename `MANAGER→ADMIN`, `CASHIER→ACCOUNTANT`; keep values `'Admin'`/`'Accountant'`; update all `ROLES.MANAGER`/`ROLES.CASHIER` references.
- **Verify:** `npm run type-check` exit 0.

## 2. Sidebar button mismatches (SB-01..SB-05) — High/Medium
- **File:** `src/components/shell/SideNav.tsx` (`NAV_ITEMS`).
- **Test first:** `e2e/sidebar-by-role.spec.ts` — assert each seeded role's expected vs forbidden nav labels (will FAIL on current code).
- **Fix:** replace every role-array gate and every ungated item with the backing **permission** gate:

  | Item | New gate (`permissions:`) |
  |---|---|
  | Dashboard | `dashboard.view` |
  | Front Desk | `orders.create` |
  | Orders | `orders.view` |
  | Approvals | `measurements.approve` |
  | Measurements | `measurements.view` *(unchanged)* |
  | Production | `production.view` *(unchanged)* |
  | Cutting | `cutting.start` |
  | Tailoring | `tailoring.start` |
  | Quality | `qc.inspect` |
  | Inventory | `inventory.view` |
  | Damage | `damage_reports.view` *(unchanged)* |
  | Rack | `rack.view` *(unchanged)* |
  | Deliveries | `deliveries.view` |
  | Scan | `qr.sign` |
  | Customers | `customers.view` |
  | Finance | `finance.view` |
  | Reports | `reports.view` |
  | Audit | `audit.view` |
  | Admin | `users.view` |
  | Settings | *(ungated — every user)* |

- **Verify:** `npm run type-check` + `npm run build` exit 0; `sidebar-by-role.spec.ts` green; existing `auth`/`rbac`/`production`/`finance` specs still green.

## 3. Route guard mismatches (RG-*) — Medium — FOLLOW-UP
Add a `permission` prop to `RequireRole`; wrap each `(shell)` route using the same permission keys as §2. Test: `route-guards.spec.ts`.

## 4. High-risk action button permission mismatches (AB-01..07) — Medium — FOLLOW-UP
Wrap action buttons in `usePermission().can(...)`; fix AB-06 (`damage_reports.approve`). Test: `button-permissions.spec.ts`.

## 5. Forms missing required backend fields (FF-02/09/11/12/13) — Blocker — FOLLOW-UP
Add `source`+`delivery_mode`+per-item `product_type`/`measurement_version_id` (order); `gst_treatment`+`lines` (invoice); supplier `code`; PO receive `lines[]`; payment `amount_paise`/`upi_id`.

## 6. Wrong form field names (FF-01/04/05/06/10) — High — FOLLOW-UP
`code→otp`, `note→reason`, `production_item_id→bundle_id`, `slot_id→slot_code`, `amount→total`.

## 7. Form validation mismatch (FF-03/07) — High — FOLLOW-UP
Measurement `shirt_data/pant_data` nesting; QC `defects[]{category_id,severity}`.

## 8. Dropdown/enum mismatch (EN-01..07) — High — FOLLOW-UP
Product type, payment method, QC disposition; add missing selectors (delivery mode, order source, reason_code, gst_treatment). Confirm backend `home` vs `home_delivery` with backend team.

## 9. Negative permission Playwright tests — FOLLOW-UP
`permission-negative-ui.spec.ts` per [negative UI plan](FRONTEND_PERMISSION_NEGATIVE_UI_TEST_PLAN.md).

## 10. Low-priority UI label/icon cleanup — FOLLOW-UP
RC-03 dedupe `abilities→permissions` normaliser; standardize guard style.
