# Frontend Role Constants Audit — Solo Shirts India ERP

**Date:** 2026-06-13 · **Mode:** inspect-only (no fixes in this doc).
**Backend source of truth:** `database/seeders/RolePermissionSeeder.php` → `ROLES` (14) + `MATRIX` (role→permission map). Login/`/auth/me` return `roles[]` + `abilities[]`.
**Frontend files inspected:** `src/lib/auth/permissions.ts` (ROLES constant + `usePermission`/`hasRole`/`hasPermission`), `src/lib/auth/landing.ts`, `src/components/shell/SideNav.tsx`, `src/components/shell/RequireRole.tsx`, `src/app/(shell)/cutting/page.tsx`, `src/app/(shell)/tailoring/page.tsx`.

## Key mechanics confirmed
- Login (`(auth)/login/page.tsx:67`) and `/auth/me` hydration (`AuthGuard.tsx:51`) both map backend **`abilities` → `user.permissions`**. So FE permission checks (`can()`) work for all roles, not just Owner.
- Owner token carries `permissions: ['*']`; `usePermission().can()` short-circuits to `true` for Owner. Backend grants Owner everything via `Gate::before`.
- `hasRole()` compares against `user.roles` (array of exact role-name strings).

## Role constant table

| Backend Role | FE Constant Exists | FE Constant Key → Value | Used In Files | Correct | Issue |
|---|---|---|---|---|---|
| Owner | ✅ | `OWNER` → `'Owner'` | permissions, SideNav, landing, cutting, tailoring | ✅ | — |
| Admin | ✅ | `MANAGER` → `'Admin'` | SideNav, landing, cutting, tailoring | ⚠️ value OK | **Legacy key name `MANAGER`** for backend `Admin` (RC-01, Low) |
| Front Desk | ✅ | `FRONT_DESK` → `'Front Desk'` | landing | ✅ | — |
| Measurement Staff | ✅ | `MEASUREMENT` → `'Measurement Staff'` | landing | ✅ | — |
| Production Supervisor | ✅ | `PRODUCTION` → `'Production Supervisor'` | landing, cutting | ✅ | — |
| Cutting Master | ✅ | `CUTTER` → `'Cutting Master'` | SideNav, landing, cutting | ✅ | — |
| Tailor | ✅ | `TAILOR` → `'Tailor'` | SideNav, landing, tailoring | ✅ | — |
| Kaja Button | ✅ | `KAJA` → `'Kaja Button'` | landing | ✅ | — |
| QC Supervisor | ✅ | `QC` → `'QC Supervisor'` | SideNav, landing | ✅ | — |
| Ironing Master | ✅ | `IRONING` → `'Ironing Master'` | landing | ✅ | — |
| Re-Worker | ✅ | `REWORKER` → `'Re-Worker'` | landing | ✅ | — |
| Inventory Manager | ✅ | `INVENTORY` → `'Inventory Manager'` | SideNav, landing | ✅ | — |
| Accountant | ✅ | `CASHIER` → `'Accountant'` | SideNav, landing | ⚠️ value OK | **Legacy key name `CASHIER`** for backend `Accountant` (RC-02, Low) |
| Delivery Staff | ✅ | `DELIVERY` → `'Delivery Staff'` | landing | ✅ | — |

## Findings

- **All 14 backend roles are present** in `ROLES`, and every **value** matches the exact backend role-name string. There is **no functional role-name mismatch** — the strings sent to `hasRole()` are correct.
- **RC-01 / RC-02 (Low) — legacy constant keys.** Two constant *keys* use pre-rename names: `MANAGER` (→ `'Admin'`) and `CASHIER` (→ `'Accountant'`). The values are correct, so behaviour is unaffected, but the keys are misleading and violate the "no MANAGER/CASHIER naming" rule. Recommend renaming keys `MANAGER→ADMIN`, `CASHIER→ACCOUNTANT` and updating the ~11 references (SideNav ×7, landing ×2, cutting ×1, tailoring ×1).
- **No "Super Admin" role exists** in the frontend. Searched `src/` — no `Super Admin` / `SUPER_ADMIN` string. ✅ No contract mismatch on this axis.
- **No duplicate role arrays defined inside pages** beyond the intended guard arrays. `cutting/page.tsx` uses `RequireRole roles={[OWNER, MANAGER, CUTTER, PRODUCTION]}` (references the constants, not raw strings). ✅
- **No hardcoded role strings inside components** — every role reference goes through `ROLES.*`. ✅ (Verified by grep: no raw `'Tailor'`/`'Admin'` literals in components outside `permissions.ts`.)
- **Owner / Admin are correctly separated** in constants (distinct `OWNER` and `MANAGER` values) and in `landing.ts` (both → `/dashboard`). Branch-isolation bypass is Owner-only on the backend; see [FRONTEND_ROUTE_GUARD_AUDIT.md](FRONTEND_ROUTE_GUARD_AUDIT.md) for the branch-switch gate.
- **Branch switch = Owner only.** Verified in `UserMenu.tsx`/`BranchSwitcher` (Owner-gated); see route-guard + sidebar audits.

## Permission-availability note (informational)
Backend returns permissions under the key **`abilities`**; the FE remaps to `permissions` in **two** places (`login/page.tsx:67`, `AuthGuard.tsx:51`). Correct today, but the mapping is duplicated — a single normaliser would prevent drift (RC-03, Low; not a role-constant defect).

## Verdict
Role constants are **functionally correct (14/14 values match backend)**. Only cosmetic key-naming issues (RC-01/RC-02) remain. The real role-access problems live in the **sidebar gating logic**, not the constants — see [FRONTEND_SIDEBAR_BUTTON_AUDIT.md](FRONTEND_SIDEBAR_BUTTON_AUDIT.md).
