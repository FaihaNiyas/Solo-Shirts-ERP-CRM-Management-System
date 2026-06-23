# Frontend Action Button Permission Audit — Solo Shirts India ERP

**Date:** 2026-06-13 · **Mode:** inspect-only.
**Backend source of truth:** route policies + `RolePermissionSeeder::MATRIX`. **FE:** `(shell)/**` pages + hooks.

## Pattern observed
The FE **does not gate most action buttons by permission** — buttons render for anyone who can open the page, and unauthorized clicks are stopped by **backend 403**. This is *secure* (backend enforces) but is a **UX permission mismatch**: a role sees an action it cannot perform. Only finance/audit/admin/damage pages use inline `can()`/`is()` around some actions. Because the sidebar/route fix will keep unauthorized roles off these pages, most of these become moot — but the action-level gate is still the defense-in-depth recommendation.

Legend: **Should-see** = roles holding the backing permission (Owner always, via `*`).

| Screen | Action | Endpoint | Permission | Should see | Should NOT see | Actual visibility | Correct | Issue |
|---|---|---|---|---|---|---|---|---|
| Customers | New Customer | POST customers | `customers.create` | Admin, Front Desk | Tailor, QC, Accountant, … | shown to all on page | ❌ | UX mismatch (AB-01) |
| Customers | Add Family Member | POST family-members | `family_members.manage` | Admin, Front Desk | most | shown to all | ❌ | UX mismatch |
| Customers | Edit/Delete Customer | PUT/DELETE customers | `customers.update`/`delete` | Admin (delete: Admin) | — | **no UI** | ⛔ | NO FE form |
| Measurements | Create Profile/Version | POST measurements | `measurements.create` | Admin, Front Desk, Measurement Staff | Tailor, QC, … | version: shown; profile: no UI | ⚠️ | profile form missing |
| Approvals | Approve / Reject | POST approve/reject | `measurements.approve` | Admin, QC Sup, Prod Sup | Front Desk, Measurement Staff | shown to all on page | ❌ | UX mismatch (AB-02) |
| Orders | Create Order | POST orders | `orders.create` | Admin, Front Desk | Tailor, QC, Accountant | shown to all | ❌ | UX mismatch |
| Orders | Add Item / Cancel | POST items / cancel | `orders.update`/`cancel` | Admin, Front Desk | — | cancel shown; add-item no UI | ⚠️ | |
| Orders | Download Job Card | GET job-card | `orders.print_job_card` | Admin, Front Desk | — | shown | ⚠️ | |
| Cutting | Allocate/Release/Start/Complete | POST cutting/* | `fabric.allocate`,`cutting.start/complete` | Admin, Prod Sup, Cutting Master | Tailor, QC | page is RequireRole-guarded ✅ | ✅ | best-practice |
| Tailoring | Assign | POST tailoring-assignments | `tailoring.assign` | Admin, Prod Sup | Tailor | shown (inline-bypass payload) | ⚠️ | FF-05 payload bug |
| Tailoring | Start / Complete | POST start/complete | `tailoring.start/complete` | Admin, Prod Sup, Tailor | — | hooks unwired | ⛔ | NO FE form |
| QC | Inspect | POST qc inspect | `qc.inspect` | Admin, Prod Sup, QC Sup | Tailor, Front Desk | shown to all on page | ❌ | UX mismatch (AB-03) |
| QC | Rework Override | POST rework-override | `qc.override` | Admin, Prod Sup, QC Sup | — | hook unwired | ⛔ | NO FE form |
| QC | Upload Photo | POST qc photos | `qc.inspect` | QC Sup, … | — | no UI | ⛔ | NO FE form |
| Rack | Assign / Release | POST rack assign/release | `rack.assign`/`release` | Admin, Prod Sup, Delivery Staff | — | assign shown (FF-06 bug); release unwired | ⚠️ | |
| Deliveries | Dispatch / Confirm OTP / Attempt / Cancel | POST deliveries/* | `deliveries.dispatch/confirm/attempt/cancel` | Admin, Prod Sup, Delivery Staff | Tailor, Accountant | shown to all on page | ❌ | UX mismatch (AB-04) |
| Deliveries | Create | POST deliveries | `deliveries.create` | Admin, Prod Sup, Delivery Staff | — | no UI | ⛔ | NO FE form |
| Finance | Create Invoice | POST invoices | `finance.invoice.create` | Admin, Accountant | Inventory Mgr, Prod Sup | inline `can()` on some actions | ⚠️ | partial gate (AB-05) |
| Finance | Record Payment / Credit Note | POST payment/credit-note | `finance.payment.record`/`credit_note.issue` | Admin, Accountant | — | shown on invoice detail | ⚠️ | FF-09/10 payload bugs |
| Finance | Download Invoice PDF | GET invoice pdf | `finance.view` | Admin, Accountant | — | shown | ✅ | |
| Inventory | Create Supplier | POST suppliers | `inventory.suppliers.manage` | Admin, Inventory Mgr | — | shown (FF-12 missing code) | ⚠️ | |
| Inventory | Adjust Fabric Roll | POST adjust | `inventory.fabric_rolls.adjust` | Admin, Inventory Mgr | — | shown | ✅ | |
| Inventory | Fabric Type / PO create / Place / Receive | POST … | `inventory.*` | Admin, Inventory Mgr | — | place shown; create/receive no/empty UI | ⛔/⚠️ | NO FE form / FF-13 |
| Damage | Approve / Reject | POST approve/reject | `damage_reports.approve/reject` | Admin, **Owner** (Prod Sup: create/view only) | Inventory Mgr | inline `can('damage_reports.view')` gate | ⚠️ | gate uses *view* not *approve* (AB-06) |
| Damage | Create Report | POST damage-reports | `damage_reports.create` | Admin, Prod Sup, Inventory Mgr | — | no UI | ⛔ | NO FE form |
| Reports | Run / Download | POST reports | `reports.run` | Admin, Accountant, Prod Sup | shop-floor | shown to all on page | ❌ | UX mismatch (AB-07) |
| Audit | View | GET audit | `audit.view` | Admin | others | inline `is(Owner)||is(Admin)` ✅ | ✅ | |
| Admin | Manage Users/Branches | … | `users.*`/`branches.*` | Admin (branches: Owner) | others | inline `is()` ✅ | ✅ | |

## Findings
- **AB-01..AB-07 (UX permission mismatch, Medium):** action buttons rendered to roles lacking the permission. Backend 403 prevents the action, but the UX is wrong. After the sidebar/route fix most offending roles won't reach the page; the residual recommendation is to wrap each action in `usePermission().can(...)`.
- **AB-06 (Medium):** damage approve/reject is gated by `damage_reports.view` (read) rather than `damage_reports.approve`. Inventory Manager has `view` but **not** `approve` → would see the approve button and 403 on click. Backend gap-safe but UX-wrong.
- **AB-05 (Medium):** finance actions are only partially gated inline; standardize.
- Many actions have **NO FE form/button at all** (⛔) — these are coverage gaps tracked in the form audit, not permission mismatches.

> **Out of scope for the current fix** (role-constants + sidebar). Logged for the action-gating / forms task.
