# Frontend Permission Negative UI Test Plan — Solo Shirts India ERP

**Date:** 2026-06-13 · **Mode:** plan (no specs written here except the sidebar fix).
**Runs against:** live stack (`next dev :3000` + Laravel `:8000` + `DemoDataSeeder`). Seeded role users: owner, frontdesk, cutter, tailor1-3, ironing, qc, supervisor, inventory, accountant, delivery (password `password`). **Not seeded:** Admin, Measurement Staff, Kaja Button, Re-Worker — those negative cases are **pending seed users** (note, don't assert).

## Test matrix

| # | Test case | Role | UI expected | API expected | Spec | Status |
|---|---|---|---|---|---|---|
| 1a | No Finance in sidebar | Tailor | `Finance` link absent | — | sidebar-by-role | to add |
| 1b | No Admin/User Mgmt | Tailor | `Admin` link absent | — | sidebar-by-role | to add |
| 1c | Direct /finance | Tailor | renders today (no guard) → after route fix: AccessDenied | `GET finance/*` 403 | permission-negative-ui | to add |
| 1d | Direct finance API | Tailor | — | `POST invoices` 403 | permission-negative-ui | to add |
| 2a | No Measurement Approval | Front Desk | `Approvals` link absent | — | sidebar-by-role | to add |
| 2b | No Finance / Branch switch | Front Desk | `Finance` absent; no branch switcher | — | sidebar-by-role | to add |
| 2c | Direct approve action | Front Desk | approve hidden/denied | `POST approve` 403 | permission-negative-ui | to add |
| 3a | No invoice create | Inventory Mgr | `Finance` link absent | — | sidebar-by-role | to add |
| 3b | Direct invoice create API | Inventory Mgr | — | `POST invoices` 403 | permission-negative-ui | to add |
| 4a | No production transition buttons | Accountant | no transition actions; `Reports` **present** (has perm) | — | sidebar-by-role | to add |
| 4b | Direct transition API | Accountant | — | `POST production transition` 403 | permission-negative-ui | to add |
| 5a | Delivery actions only | Delivery Staff | sees Deliveries, Rack, Production; **no** Finance/Admin | — | sidebar-by-role | to add |
| 6a | Branch A cannot see Branch B customer | branch-scoped staff | B customer absent from search | `GET customers/{B}` 404/403 | permission-negative-ui | to add (needs 2-branch seed) |
| 7a | Branch switcher hidden | any non-owner | switcher not rendered | — | sidebar-by-role | to add |
| 7b | Direct switch-branch API | non-owner | — | `POST switch-branch` 403 | permission-negative-ui | to add |

## Notes
- Cases that assert "direct route shows AccessDenied" are **only valid after** the route-guard follow-up (today most routes render). Until then assert the **API 403** (already true) and the **sidebar absence** (true after the sidebar fix).
- API-403 assertions reuse the existing `apiToken`/`apiPost` helpers (already proven in the FE-017 suite).
- Branch-isolation cases (6a) need a two-branch seed with a Branch-B customer; mark pending if the demo seed is single-branch.
