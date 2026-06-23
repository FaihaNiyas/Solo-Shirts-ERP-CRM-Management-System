# Frontend Role / Sidebar / Form QA Report — Solo Shirts India ERP

**Date:** 2026-06-13 · **Mode:** inspect-only defect catalog. Source of truth: `RolePermissionSeeder::MATRIX`.
Companion docs: [role constants](FRONTEND_ROLE_CONSTANTS_AUDIT.md) · [sidebar](FRONTEND_SIDEBAR_BUTTON_AUDIT.md) · [route guards](FRONTEND_ROUTE_GUARD_AUDIT.md) · [action buttons](FRONTEND_ACTION_BUTTON_PERMISSION_AUDIT.md) · [forms](FRONTEND_FORM_FIELD_ALIGNMENT_AUDIT.md) · [enums](FRONTEND_ENUM_STATUS_ALIGNMENT_AUDIT.md).

---
### SB-01 — Admin role cannot see Admin / User Management
- **Category:** Sidebar mismatch · **Severity:** High
- **Module:** Shell nav · **Role:** Admin · **Screen:** sidebar · **Button:** Admin
- **Backend source:** `MATRIX['Admin']` has `users.*`, `roles.assign`. **Frontend file:** `SideNav.tsx:71` (`roles: [ROLES.OWNER]`).
- **Expected:** Admin sees Admin/User Management. **Actual:** gated to Owner only → hidden for Admin.
- **Root cause:** role-array gate omits Admin; should gate by `users.view`.
- **Fix:** gate Admin item by permission `users.view`. **Test:** sidebar-by-role (Admin sees Admin). **Status:** Not fixed

### SB-02 — Production Supervisor missing Cutting / Tailoring / Quality / Reports / Approvals
- **Category:** Sidebar mismatch · **Severity:** High
- **Role:** Production Supervisor · **Screen:** sidebar
- **Backend source:** `MATRIX['Production Supervisor']` holds `cutting.start`, `tailoring.start`, `qc.inspect`, `reports.view`, `measurements.approve`. **Frontend:** `SideNav.tsx:55-57,67,50` role-arrays exclude Production Supervisor.
- **Expected:** all five visible. **Actual:** all five hidden.
- **Root cause:** hardcoded role arrays omit permission-holder. **Fix:** gate by `cutting.start`/`tailoring.start`/`qc.inspect`/`reports.view`/`measurements.approve`. **Test:** sidebar-by-role. **Status:** Not fixed

### SB-03 — Accountant missing Reports
- **Category:** Sidebar mismatch · **Severity:** Medium
- **Role:** Accountant · **Backend:** `MATRIX['Accountant']` has `reports.run`/`reports.view`. **Frontend:** `SideNav.tsx:67` `roles:[Admin,Owner]`.
- **Expected:** Reports visible. **Actual:** hidden. **Fix:** gate Reports by `reports.view`. **Status:** Not fixed

### SB-04 — QC Supervisor missing Approvals
- **Category:** Sidebar mismatch · **Severity:** Medium
- **Role:** QC Supervisor · **Backend:** holds `measurements.approve`. **Frontend:** `SideNav.tsx:50` `roles:[Admin,Owner]`.
- **Expected:** Approvals visible. **Actual:** hidden. **Fix:** gate Approvals by `measurements.approve`. **Status:** Not fixed

### SB-05 — Ungated nav shown to every role (Customers PII to shop-floor)
- **Category:** Sidebar mismatch · **Severity:** High (Customers/PII) / Medium (others)
- **Roles:** all non-management · **Screen:** sidebar · **Buttons:** Dashboard, Front Desk, Orders, Deliveries, Scan, Customers
- **Backend source:** these have backing permissions (`dashboard.view`,`orders.create`,`orders.view`,`deliveries.view`,`qr.sign`,`customers.view`) most shop-floor roles lack. **Frontend:** `SideNav.tsx:47-49,61,62,65` have no `roles`/`permissions`.
- **Expected:** each hidden unless the role holds the permission. **Actual:** shown to all (Tailor/Kaja/Ironing/Re-Worker see **Customers**).
- **Root cause:** missing gates. **Fix:** add the permission gate to each. **Test:** sidebar-by-role (extras absent). **Status:** Not fixed

### RC-01 / RC-02 — Legacy role-constant keys MANAGER / CASHIER
- **Category:** Role constant mismatch · **Severity:** Low
- **Frontend:** `permissions.ts:74,78` keys `MANAGER`→`'Admin'`, `CASHIER`→`'Accountant'` (values correct).
- **Expected:** keys named for backend roles. **Actual:** legacy keys. **Root cause:** pre-rename leftover. **Fix:** rename keys `ADMIN`/`ACCOUNTANT`, update ~11 refs. **Status:** Not fixed

### RC-03 — `abilities→permissions` mapping duplicated
- **Category:** Backend gap — confirmation / Low · `login/page.tsx:67` + `AuthGuard.tsx:51`. Works; dedupe into one normaliser. **Status:** Not fixed (out of scope)

---
## Cataloged elsewhere (NOT in scope for the current fix — see linked docs)
- **Route guards:** RG-DASH/FD/CUST/APPR + ~31 unguarded routes → [route guard audit](FRONTEND_ROUTE_GUARD_AUDIT.md). Severity Medium (UX; backend 403 holds).
- **Action buttons:** AB-01..AB-07 UX permission mismatches → [action button audit](FRONTEND_ACTION_BUTTON_PERMISSION_AUDIT.md). Medium.
- **Forms (Blocker/High):** FF-01..FF-14 payload/key/nesting/paise mismatches → [form audit](FRONTEND_FORM_FIELD_ALIGNMENT_AUDIT.md). Many would 422 today.
- **Enums:** EN-01..EN-07 → [enum audit](FRONTEND_ENUM_STATUS_ALIGNMENT_AUDIT.md). Includes backend self-inconsistency `home` vs `home_delivery` (**backend gap — needs confirmation**).

## Counts
- Roles checked: **14** · Sidebar buttons checked: **20** · Roles with correct sidebar: **1** (Owner) · wrong/partial: **13**
- Action buttons checked: **~40** · action permission mismatches: **7** (AB-01..07) + AB-06 wrong-permission
- Forms checked: **~40** · with missing required fields: **8** · wrong field names: **9** · enum/dropdown mismatches: **7**
- Route guard mismatches: **~31 unguarded** (4 priority)
- **Blocker:** Form group FF-02/FF-09 (order create, payment) · **High:** SB-01, SB-02, SB-05, + form FF-01/03/04/05/06/07/08/10/11/12/13

## First 10 to fix (this pass = SB + RC only)
1. SB-01 Admin menu → `users.view`
2. SB-02 Prod Sup Cutting/Tailoring/Quality → perm gates
3. SB-02 Prod Sup Reports/Approvals → perm gates
4. SB-04 QC Approvals → `measurements.approve`
5. SB-03 Accountant Reports → `reports.view`
6. SB-05 ungate Customers (PII) → `customers.view`
7. SB-05 ungate Front Desk/Orders → `orders.create`/`orders.view`
8. SB-05 ungate Deliveries/Scan → `deliveries.view`/`qr.sign`
9. SB-05 ungate Dashboard → `dashboard.view`
10. RC-01/02 rename MANAGER/CASHIER keys
