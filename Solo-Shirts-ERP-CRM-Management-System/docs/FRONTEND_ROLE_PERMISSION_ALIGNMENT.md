# Frontend Role/Permission Alignment — Solo Shirts India ERP

**Date:** 2026-06-12 · **Source of truth:** `BACKEND_ROLE_SCENARIOS.md`. Gating: `src/components/shell/SideNav.tsx`, `lib/auth/permissions.ts`, `BranchSwitcher.tsx`.
**Rule 20:** FE gates are UX only; backend still enforces 403 (verified by `rbac.spec.ts`/`admin.spec.ts`/`finance.spec.ts` hitting real 403s).

## Cross-cutting findings (apply to all roles)
- **FE-012 — ✅ FIXED (2026-06-13).** Role-specific landing added: `lib/auth/landing.ts` `landingRouteForRoles()`, used in login + 2FA. Owner/Admin keep `/dashboard`; Front Desk→`/front-desk`, Accountant→`/finance`, Cutting Master→`/cutting`, Tailor→`/tailoring`, QC→`/qc`, Inventory→`/inventory`, Delivery→`/deliveries`, Measurement→`/measurements`, Production/Kaja/Ironing/Re-Worker→`/production`.
- **FE-011 — ✅ FIXED (2026-06-13).** Added a proper Access Denied surface: `components/shell/AccessDenied.tsx` + `(shell)/forbidden/page.tsx` route + a reusable `RequireRole` guard (`components/shell/RequireRole.tsx`) that renders Access Denied (and skips the children's data queries) when the role check fails. Wired into `/cutting` as the first adopter; backend still enforces 403 independently. *(Existing ad-hoc gates on admin/audit/finance keep their current text to preserve `rbac.spec`/`finance.spec` assertions; they can adopt `RequireRole` incrementally.)*
- **FE-013 — ✅ FIXED (2026-06-13).** All **14** roles are now named constants in `permissions.ts` ROLES (added `KAJA: 'Kaja Button'`, `IRONING: 'Ironing Master'`, `REWORKER: 'Re-Worker'`).
- **SideNav over-exposure.** Dashboard, **Front Desk, Orders, Customers, Scan**, Settings are shown to **every** role (`SideNav.tsx:45-72`). A Tailor/Kaja/Ironing/Re-Worker realistically needs only production/their stage. Not a security issue (backend 403s on action), but a **UX permission mismatch (Low/Medium).**
- ✅ **BranchSwitcher is Owner-only** (`BranchSwitcher.tsx:14-24`) — rule 18 PASS.

---

### Role: Owner
**Expected:** all modules; branch switcher; admin. **Actual:** lands `/dashboard`; sees all nav incl. Admin, Finance, Reports, Audit, Approvals; branch switcher visible. **Status: Pass.**

### Role: Admin
**Expected:** all-but-branch-switch within branch. **Actual:** sees Admin/Finance/Reports/Audit (via `MANAGER` constant); **no** branch switcher (read-only branch tag). **Status: Pass.** Note: FE shows Admin the branch *name* only — correct.

### Role: Front Desk
**Expected:** Landing `/front-desk`; see customer search, QR, create customer, create order; **cannot** see finance/branch-switcher/users/measurement-approval.
**Actual:** Landing `/dashboard` (FE-012); sees Dashboard, Front Desk, Orders, Customers, Scan, Settings; Approvals/Finance/Reports/Audit/Admin **hidden**; branch switcher hidden. Measurement *approval* (Approvals nav) hidden ✅.
**Status: Partial.** Issues: wrong landing (FE-012); Scan nav broken endpoint (FE-006).

### Role: Measurement Staff
**Expected:** customers view + measurements create; **cannot** approve.
**Actual:** sees Dashboard/Front Desk/Orders/Customers/Scan/Settings + Measurements (permission-gated); Approvals hidden ✅. **Status: Partial** — over-exposed Front Desk/Orders nav (can't actually create — backend 403); landing `/dashboard`.

### Role: Production Supervisor
**Expected:** production, cutting, tailoring, qc, rack, deliveries, reports, measurement approve.
**Actual:** Production/Cutting/Tailoring/Quality/Rack/Reports visible (role+permission gated); Finance/Admin hidden ✅. **Status: Pass** (cutting screen itself is FE-002-broken).

### Role: Cutting Master
**Expected:** cutting queue, allocate/release fabric, start/complete cutting.
**Actual:** Cutting nav visible (Owner/Admin/Cutter); Tailoring/QC/Finance hidden ✅. **Status: Partial** — the Cutting screen drives `/production/transition` instead of `/cutting/*` endpoints (FE-002), so fabric isn't reserved/consumed.

### Role: Tailor
**Expected:** own assignments, start/complete only.
**Actual:** Tailoring nav visible; Finance/Cutting/QC hidden ✅; backend blocks finance (`rbac.spec.ts`). **Status: Partial** — start/complete buttons not wired (Screen #21); over-exposed Front Desk/Orders nav.

### Role: Kaja Button / Ironing Master / Re-Worker (floor stations)
**Expected:** production board + their single transition only.
**Actual:** Production nav visible (permission-gated); **no named ROLES constant (FE-013)** so any role-string gating for these three is by literal string; no dedicated stage action UI (#21/#24). Over-exposed common nav. **Status: Partial.**

### Role: QC Supervisor
**Expected:** inspect, rework, override, defect categories, measurement approve.
**Actual:** Quality nav visible (Owner/Admin/QC); QC inspect screen real ✅. **Status: Pass.**

### Role: Inventory Manager
**Expected:** fabric rolls, movements, low stock, suppliers, POs; cannot approve damage/invoice.
**Actual:** Inventory nav visible (Owner/Admin/Inventory); Finance hidden ✅; Damage create visible, approve gated. **Status: Pass** (`inventory.spec.ts`/`damage.spec.ts` confirm 403s).

### Role: Accountant
**Expected:** Landing finance; invoices/payments/credit-notes/outstanding/dashboard/reports; read-only customers/orders; **cannot** move production.
**Actual:** Finance/Reports visible; Production/Admin hidden ✅; backend 403 on production (`finance.spec.ts` shows Front-Desk/Tailor blocked from finance — inverse confirmed). **Status: Partial** — landing `/dashboard` not finance (FE-012).

### Role: Delivery Staff
**Expected:** deliveries, dispatch, OTP confirm, rack assign/release.
**Actual:** Deliveries + Rack reachable; OTP confirm UI handles 423 lockout ✅. **Status: Pass.**

---

## Verdict
Role gating is **functional and permission/role-driven** (no role sees finance/admin it shouldn't; branch switcher Owner-only; backend enforces 403 — verified by Playwright). **FE-011, FE-012, FE-013 are now fixed (2026-06-13):** per-role landing, an Access Denied page + `RequireRole` guard, and all 14 role constants. Remaining UX polish: common nav still over-exposed to floor roles (Low), and `RequireRole` can be rolled out to more routes. No **security** permission bypass (backend remains the enforcer).
</content>
