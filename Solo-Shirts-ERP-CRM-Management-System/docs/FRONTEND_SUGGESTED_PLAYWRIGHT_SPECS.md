# Suggested Playwright Specs — Role / Sidebar / Form / Guards

**Date:** 2026-06-13 · Runs against live stack (`next dev :3000` + Laravel `:8000` + `DemoDataSeeder`). Helpers: `e2e/helpers.ts`.

## e2e/sidebar-by-role.spec.ts — **implemented this pass**
- **Purpose:** assert each seeded role's sidebar shows exactly its permission-justified nav and none of the forbidden items.
- **Roles:** Owner, Front Desk, Cutting Master, Tailor, QC Supervisor, Production Supervisor, Inventory Manager, Accountant, Delivery Staff, Ironing Master (seeded). (Admin, Measurement Staff, Kaja, Re-Worker — pending seed users.)
- **Screens:** the shell sidebar after login.
- **Assertions:** per role, `nav` contains expected labels (e.g. Production Supervisor sees Cutting/Tailoring/Quality/Reports/Approvals) and excludes forbidden ones (e.g. Tailor has no Customers/Finance/Admin).

## e2e/button-permissions.spec.ts — follow-up
- **Purpose:** action buttons render only for permitted roles.
- **Roles:** Accountant (no production transitions), Inventory Mgr (no invoice create), Tailor (no QC inspect).
- **Screens:** orders/[id], finance, qc, production.
- **Assertions:** button present/absent by role; click by unpermitted role (where reachable) → error drawer with 403 + request_id.

## e2e/form-fields-contract.spec.ts — follow-up
- **Purpose:** submitting each create form sends backend-accepted payload keys (catches FF-01..13).
- **Roles:** Front Desk (order/customer), Accountant (payment/credit note), QC (inspect).
- **Assertions:** intercept the request body; assert keys (`otp` not `code`, `bundle_id` not `production_item_id`, `amount_paise` not `amount`, per-item `product_type`/`measurement_version_id`, `reason_code` enum) and that the response is 2xx (not 422).

## e2e/route-guards.spec.ts — follow-up
- **Purpose:** direct URL to a forbidden route shows AccessDenied (after §3 guard work) and the API 403s.
- **Roles:** Tailor → /finance, Front Desk → /audit, Accountant → production transition.
- **Assertions:** AccessDenied rendered; direct API 403.

## e2e/permission-negative-ui.spec.ts — follow-up
- **Purpose:** the negative matrix in [the negative UI plan](FRONTEND_PERMISSION_NEGATIVE_UI_TEST_PLAN.md) (API-403 + sidebar-absence assertions, branch isolation).
- **Roles:** all seeded; branch isolation needs a 2-branch seed.
