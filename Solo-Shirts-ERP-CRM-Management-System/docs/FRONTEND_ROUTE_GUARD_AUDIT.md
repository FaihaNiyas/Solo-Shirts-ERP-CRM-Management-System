# Frontend Route Guard Audit — Solo Shirts India ERP

**Date:** 2026-06-13 · **Mode:** inspect-only.
**FE:** `src/app/(shell)/**`, guards in `AuthGuard.tsx`, `RequireRole.tsx`, inline `usePermission()`.
**Backend source of truth:** route policies + `RolePermissionSeeder::MATRIX`. Backend 403 is the real enforcer; client guards are UX only.

## Guard mechanics
- **AuthGuard** wraps the whole `(shell)` layout → unauthenticated → redirect `/login`. Restores user from sessionStorage on refresh, falls back to `/auth/me`.
- **RequireRole** (`roles[]`) → renders `<AccessDenied/>` (no data fetch) if no role matches.
- **Inline gates** use `is(role)`/`can(perm)` and either render a restricted panel or set `enabled:false` on the query.
- Auth routes (`/login`, `/2fa`) are outside AuthGuard (dynamic layout).

## Route table

| Route | AuthGuard | Client role/perm guard | Allowed (backend perm) | Unauthorized behavior | Status |
|---|---|---|---|---|---|
| /login, /2fa | No (public) | — | all | renders form | ✅ |
| /dashboard | Yes | **none** | `dashboard.view` (Admin, Accountant, Prod Sup, Owner) | renders for all | ⚠️ RG-DASH |
| /front-desk | Yes | **none** | `orders.create` (Admin, Front Desk, Owner) | renders for all | ⚠️ RG-FD |
| /scan | Yes | **none** | `qr.sign` (Admin, Front Desk, Prod Sup, Owner) | renders for all | ⚠️ |
| /customers, /customers/[id] | Yes | **none** | `customers.view` | renders for all | ⚠️ RG-CUST (PII) |
| /measurements | Yes | **none** | `measurements.view` | renders for all | ⚠️ |
| /measurements/approvals (+ /approvals) | Yes | **none** | `measurements.approve` | renders for all | ⚠️ RG-APPR |
| /measurements/[profileId] | Yes | **none** | `measurements.create` | renders for all | ⚠️ |
| /orders, /orders/[id] | Yes | **none** | `orders.view` | renders for all | ⚠️ |
| /production | Yes | **none** | `production.view` | renders for all | ⚠️ |
| /cutting | Yes | **RequireRole [Owner, Admin, Cutting Master, Production Supervisor]** | `cutting.start` | `<AccessDenied/>` | ✅ (best-practice example) |
| /tailoring | Yes | inline `is()` — renders supervisor vs tailor view | `tailoring.start` | conditional view (no denial) | ⚠️ RG-TAIL |
| /qc | Yes | **none** | `qc.inspect` | renders for all | ⚠️ |
| /rack | Yes | **none** | `rack.view` | renders for all | ⚠️ |
| /inventory (+ fabric-rolls, suppliers, purchase-orders) | Yes | **none** | `inventory.view` | renders for all | ⚠️ |
| /damage-reports | Yes | inline `can('damage_reports.view') \|\| is('Owner')`, `enabled:hasAccess` | `damage_reports.view` | inline denial panel, no fetch | ✅ |
| /deliveries | Yes | **none** | `deliveries.view` | renders for all | ⚠️ |
| /finance (+ invoices, gst, outstanding) | Yes | partial inline `can()` on some actions | `finance.view` | renders list for all | ⚠️ RG-FIN |
| /reports | Yes | **none** | `reports.view` | renders for all | ⚠️ |
| /audit | Yes | inline `is('Owner') \|\| is('Admin')`, `enabled:isAllowed` | `audit.view` | restricted panel, no fetch | ✅ |
| /admin/users | Yes | inline `is('Owner') \|\| is('Admin')` | `users.view` | restricted panel, no fetch | ✅ |
| /admin/branches | Yes | inline `is('Owner')` | `branches.*` (Owner) | restricted panel, no fetch | ✅ |
| /settings/* | Yes | none (every user) | — | renders | ✅ |
| /documents | Yes | **none** | `documents.view` | renders for all | ⚠️ |
| /forbidden | Yes | — | — | renders `<AccessDenied/>` | ✅ |

## Findings
- **~31 of ~40 routes have NO client-side guard** and rely solely on backend 403. This is **functionally safe** (every mutating endpoint enforces permission server-side) but is a **UX defect**: unauthorized roles can open the page, see a shell/loading/empty table, and only discover the wall when an action 403s.
- **Guard style is inconsistent** — three patterns coexist: `RequireRole` (cutting), inline `can()`+`enabled` (damage/audit/admin), and nothing (most). Recommend standardizing on `RequireRole` with a **permission** prop.
- **RG-DASH / RG-FD / RG-CUST / RG-APPR (Medium):** the routes most worth guarding because they currently render to roles with no business there — Dashboard (shop-floor), Front Desk (non-desk roles), Customers (PII to Tailor/Kaja/Ironing/Re-Worker), Approvals (non-approvers). All are **read-mostly** so no data leaks beyond what the list query returns (and that query is itself branch+permission scoped server-side), but they should show `<AccessDenied/>`.
- **RG-TAIL (Low):** `/tailoring` branches on `is()` to pick a view but never denies; a role with neither sub-view sees an empty page.
- **No wrong allowed-role sets** were found in the existing guards — `/cutting`'s `RequireRole` list matches the `cutting.start` holders exactly; `/audit` and `/admin/users` match `audit.view`/`users.view`; `/admin/branches` matches Owner-only.

## Recommendation (not applied here)
Add a `permission` prop to `RequireRole` and wrap each `(shell)` route in `<RequireRole permission="…">`, using the same permission keys the sidebar will use after the [sidebar fix](FRONTEND_SIDEBAR_BUTTON_AUDIT.md). This is **out of scope for the current role-constants+sidebar fix** and is logged as a follow-up (see fix plan §3).
