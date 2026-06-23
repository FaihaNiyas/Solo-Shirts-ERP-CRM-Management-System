# Frontend Sidebar Button Audit — Solo Shirts India ERP

**Date:** 2026-06-13 · **Mode:** inspect-only.
**Source of truth:** `RolePermissionSeeder::MATRIX`. **FE under test:** `src/components/shell/SideNav.tsx` (`NAV_ITEMS`).
Combines Task 2 (button audit) + Task 3 (per-role rules).

## How the current sidebar gates items (`SideNav.tsx`)
Each `NAV_ITEM` is visible if it has **no** `roles`/`permissions` (shown to everyone), OR the user satisfies its `roles` (role-name match) OR its `permissions` (`can()` — works because `abilities→permissions` is mapped at login).

| Nav item | Current gate | Type |
|---|---|---|
| Dashboard | — none — | **UNGATED** |
| Front Desk | — none — | **UNGATED** |
| Orders | — none — | **UNGATED** |
| Approvals | roles `[Admin, Owner]` | role |
| Measurements | perm `measurements.view` | perm ✅ |
| Production | perm `production.view` | perm ✅ |
| Cutting | roles `[Cutting Master, Admin, Owner]` | role |
| Tailoring | roles `[Tailor, Admin, Owner]` | role |
| Quality (QC) | roles `[QC Supervisor, Admin, Owner]` | role |
| Inventory | roles `[Inventory Manager, Admin, Owner]` | role |
| Damage | perm `damage_reports.view` | perm ✅ |
| Rack | perm `rack.view` | perm ✅ |
| Deliveries | — none — | **UNGATED** |
| Scan | — none — | **UNGATED** |
| Customers | — none — | **UNGATED** |
| Finance | roles `[Accountant, Admin, Owner]` | role |
| Reports | roles `[Admin, Owner]` | role |
| Audit | roles `[Admin, Owner]` | role |
| Admin | roles `[Owner]` | role |
| Settings | — none — | **UNGATED** |

## Two systemic root causes
1. **SB-UNGATED (High):** Dashboard, Front Desk, Orders, Deliveries, Scan, Customers are shown to **every** authenticated role regardless of backend permission → produces "Extra" buttons for almost every non-management role (incl. **Customers shown to Tailor/Kaja/Ironing/Re-Worker**, a customer-PII exposure path). Should be permission-gated: Dashboard→`dashboard.view`, Front Desk→`orders.create`, Orders→`orders.view`, Deliveries→`deliveries.view`, Scan→`qr.sign`, Customers→`customers.view`.
2. **SB-ROLELIST (High):** Approvals/Cutting/Tailoring/Quality/Reports/Admin gate by a **hardcoded role array** that omits roles which hold the equivalent backend permission → produces "Missing" buttons (e.g., Production Supervisor can't see Cutting/Tailoring/Quality/Reports/Approvals despite holding `cutting.start`/`tailoring.start`/`qc.inspect`/`reports.view`/`measurements.approve`). Should gate by permission.

## Summary table

| Role | Expected | Actual | Missing | Extra | Wrong routes | Status |
|---|--:|--:|---|---|---|---|
| Owner | 20 | 20 | — | — | — | ✅ Pass |
| Admin | 20 | 19 | Admin/User Mgmt | — | — | ⚠️ Partial |
| Front Desk | 7 | 9 | — | Dashboard, Deliveries | — | ⚠️ Partial |
| Measurement Staff | 3 | 8 | — | Dashboard, Front Desk, Orders, Deliveries, Scan | — | ❌ Fail |
| Production Supervisor | 13 | 11 | Approvals, Cutting, Tailoring, Quality, Reports | Front Desk, Orders, Customers | — | ❌ Fail |
| Cutting Master | 3 | 9 | — | Dashboard, Front Desk, Orders, Deliveries, Scan, Customers | — | ❌ Fail |
| Tailor | 3 | 9 | — | Dashboard, Front Desk, Orders, Deliveries, Scan, **Customers** | — | ❌ Fail |
| Kaja Button | 2 | 8 | — | Dashboard, Front Desk, Orders, Deliveries, Scan, **Customers** | — | ❌ Fail |
| QC Supervisor | 5 | 10 | Approvals | Dashboard, Front Desk, Orders, Deliveries, Scan, Customers | — | ❌ Fail |
| Ironing Master | 2 | 8 | — | Dashboard, Front Desk, Orders, Deliveries, Scan, **Customers** | — | ❌ Fail |
| Re-Worker | 2 | 8 | — | Dashboard, Front Desk, Orders, Deliveries, Scan, **Customers** | — | ❌ Fail |
| Inventory Manager | 4 | 10 | — | Dashboard, Front Desk, Orders, Deliveries, Scan, Customers | — | ❌ Fail |
| Accountant | 6 | 8 | Reports | Front Desk, Deliveries, Scan | — | ⚠️ Partial |
| Delivery Staff | 4 | 9 | — | Dashboard, Front Desk, Orders, Scan, Customers | — | ❌ Fail |

*Expected = nav items the role's backend permissions justify (+ Settings, always). No wrong-route links were found — every `href` matches its page route.*

## Per-role detail (landing route from `landing.ts`)

### Role: Owner — landing `/dashboard`
Expected: all modules. Actual: all. **Pass.** Owner wildcard `*` + Owner in every role list.

### Role: Admin — landing `/dashboard`
Expected: all 20 (Admin holds every gating permission incl `users.view`, `audit.view`, `finance.view`, `reports.view`). Actual: 19. **Partial.**
- Missing: **Admin / User Management** — the `Admin` nav item gates `roles: [Owner]` only, but Admin holds `users.*`/`roles.assign`. Front-end gap (SB-01, High).

### Role: Front Desk — landing `/front-desk`
Expected: Front Desk, Orders, Measurements, Production, Scan, Customers, Settings. Actual adds Dashboard + Deliveries. **Partial.**
- Extra: **Dashboard** (no `dashboard.view`), **Deliveries** (no `deliveries.view`).
- Correctly hidden: Finance, Approvals, Branch switch, Admin. ✅

### Role: Measurement Staff — landing `/measurements`
Expected: Measurements, Customers, Settings. Actual: + Dashboard, Front Desk, Orders, Deliveries, Scan. **Fail.**
- Extra (5): Dashboard, Front Desk, Orders, Deliveries, Scan (none of `dashboard.view`/`orders.*`/`deliveries.view`/`qr.sign`). Customers OK (`customers.view`).

### Role: Production Supervisor — landing `/production`
Expected: Dashboard, Approvals, Measurements, Production, Cutting, Tailoring, Quality, Damage, Rack, Deliveries, Scan, Reports, Settings (13). Actual: 11. **Fail — largest mismatch.**
- Missing (5): **Approvals** (`measurements.approve`), **Cutting** (`cutting.start`), **Tailoring** (`tailoring.start`), **Quality** (`qc.inspect`), **Reports** (`reports.view`) — all held but blocked by role-list gates.
- Extra (3): Front Desk, Orders, Customers (no `orders.*`/`customers.view`).

### Role: Cutting Master — landing `/cutting`
Expected: Production, Cutting, Settings. Actual 9. **Fail.** Extra (6): Dashboard, Front Desk, Orders, Deliveries, Scan, Customers.

### Role: Tailor — landing `/tailoring`
Expected: Production, Tailoring, Settings. Actual 9. **Fail.** Extra (6) incl **Customers (PII — High):** Tailor must not see customer private data per role rules; Customers nav is ungated.

### Role: Kaja Button — landing `/production`
Expected: Production, Settings (works the Production board filtered to Kaja stage — no dedicated nav needed). Actual 8. **Fail.** Extra (6) incl Customers.

### Role: QC Supervisor — landing `/qc`
Expected: Approvals, Measurements, Production, Quality, Settings (5). Actual 10. **Fail.**
- Missing: **Approvals** (holds `measurements.approve`).
- Extra (6): Dashboard, Front Desk, Orders, Deliveries, Scan, Customers.

### Role: Ironing Master — landing `/production`
Expected: Production, Settings. Actual 8. **Fail.** Extra (6) incl Customers.

### Role: Re-Worker — landing `/production`
Expected: Production, Settings. Actual 8. **Fail.** Extra (6) incl **Customers (sensitive data — role rule violation).**

### Role: Inventory Manager — landing `/inventory`
Expected: Production, Inventory, Damage, Settings (4). Actual 10. **Fail.** Extra (6): Dashboard, Front Desk, Orders, Deliveries, Scan, Customers. Finance correctly hidden ✅.

### Role: Accountant — landing `/finance`
Expected: Dashboard, Orders, Customers, Finance, Reports, Settings (6). Actual 8. **Partial/Fail.**
- Missing: **Reports** (holds `reports.view`/`reports.run`, but Reports gates `[Admin, Owner]`).
- Extra (3): Front Desk (no `orders.create`), Deliveries (no `deliveries.view`), Scan (no `qr.sign`).
- Production transition buttons correctly absent ✅.

### Role: Delivery Staff — landing `/deliveries`
Expected: Production, Rack, Deliveries, Settings (4). Actual 9. **Fail.** Extra (5): Dashboard, Front Desk, Orders, Scan, Customers. Finance/Admin correctly hidden ✅.

## Direct-route protection note
Hiding a sidebar link does **not** block direct URL access — most routes have no client guard (see [FRONTEND_ROUTE_GUARD_AUDIT.md](FRONTEND_ROUTE_GUARD_AUDIT.md)). Backend 403 is the real enforcer; the sidebar fix is a UX-correctness fix, not a security fix.

## Task-3 rule verification (condensed)
All 14 role rule-sets were checked against the matrix; every "Should NOT see Finance/Admin/Branch-switch" rule **passes** today (those are role-gated). The failures are all (a) **Extra** low-value nav from ungated items and (b) **Missing** nav from role-list gates that omit permission-holders — both fixed by moving every gate to the backing permission. Branch switcher is Owner-only ✅ (not in `NAV_ITEMS`; lives in `UserMenu`).
