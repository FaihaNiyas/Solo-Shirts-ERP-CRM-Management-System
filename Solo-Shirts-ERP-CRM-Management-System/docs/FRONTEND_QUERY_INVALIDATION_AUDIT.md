# Frontend Query Key + Cache Invalidation Audit — Solo Shirts India ERP

**Date:** 2026-06-12 (updated 2026-06-13) · Files: `src/lib/query/keys.ts`, `lib/query/client.ts`, `lib/api/hooks/*.ts`, `BranchSwitcher.tsx`, `UserMenu.tsx`.

> **✅ FE-014 + FE-021 closed (2026-06-13).** Cross-entity invalidations added (order→`customer`, add-item→`orders`, transition→`auditTransitions`, invoice/credit-note→`financeDashboard`). All inline keys moved into the `queryKeys` factory (`customerByQr`, `measurementVersion`, `cuttingBundle`, `bundleByQr`).

## Query-key hygiene
- ✅ Keys centralized in `lib/query/keys.ts` (`queryKeys` factory) — used across hooks.
- ✅ **Inline keys (FE-021 — DONE):** `measurementVersion(versionId)` and `customerByQr(payload)` factory entries replace the former string-literal keys in `useMeasurements.ts`/`useCustomers.ts`.

| Mutation | Expected Invalidations | Actual Invalidations | Status | Issue |
|--|--|--|--|--|
| customer create | customers (list) | ✅ customers | Pass | |
| customer update | customer(id), customers | ✅ both | Pass | |
| family member create | familyMembers(cid), customer(cid) | ✅ familyMembers only | Partial | missing customer detail |
| measurement version create | versions(profile), pendingApprovals, **measurements(customer)** | versions, pendingApprovals | Partial | missing customer measurements list (FE-014) |
| measurement approve | pendingApprovals, version, profile versions | ✅ pendingApprovals, version | Partial | missing profile list |
| **order create** | orders, **customer(id)**, **dashboard** | orders only | **Fail** | missing customer detail + dashboard (FE-014) |
| **add order item** | order(id), **orders**, orderItems | order(id) only | **Fail** | missing orders list refresh (FE-014) |
| cancel order | order(id), orders | ✅ both | Pass | |
| **production transition** | board, item, history, **auditTransitions** | board, item, history | Partial | missing audit trail (FE-014) |
| **allocate fabric** | cutting queue, board, fabric roll, movements | ✅ all four (`useAllocateFabric`) | **Pass** | ~~FE-002~~ fixed |
| **complete cutting** | board, fabric roll, movements, tailoring | ✅ cuttingQueue+board+fabricRolls+movements+tailoring (`useCompleteCutting`) | **Pass** | ~~FE-002~~ fixed |
| **invoice create** | invoices, **dashboard**, **orders** | invoices only | **Fail** | missing dashboard + orders (FE-014) |
| **payment record** | invoice, payments, dashboard, **orders** | invoice, payments, dashboard | Partial | missing orders (status may change) |
| credit note | invoice, invoices, **dashboard** | invoice, invoices | Partial | missing dashboard |
| **delivery confirm** | delivery, deliveries, **orders**, **rack slot** | delivery, deliveries | **Fail** | missing orders + rack (FE-014) |
| fabric roll adjust | fabricRoll(id), fabricRolls, movements | ✅ all three | Pass | |
| receive PO | purchaseOrders, fabricRolls | ✅ both | Pass | |

> **✅ Fix group 2 (2026-06-12):** **FE-009 + FE-010 fixed** — the two system-level gaps below are resolved (branch switch + logout now `queryClient.clear()`). Per-mutation cross-entity gaps (FE-014) remain for a later group.

## System-level invalidation gaps (High)
| Event | Expected | Actual | Status | Issue |
|--|--|--|--|--|
| **Branch switch** | invalidate / clear **all** branch-scoped queries (new token = new branch data) | ✅ `BranchProvider.switchBranch` now calls `queryClient.clear()` after the token update | **Pass** | ~~FE-009~~ fixed |
| **Logout** | `queryClient.clear()` to drop all cached data | ✅ `UserMenu.handleLogout` now calls `queryClient.clear()` after `clearSession()`+`reset()` | **Pass** | ~~FE-010~~ fixed |

## Findings
- **FE-009 (High):** branch switch must invalidate branch-scoped queries; today the new token is set but TanStack Query keeps the old branch's cached lists/details (rule 19 — branch-bound data). A manual refresh/refetch is required to see the correct branch.
- **FE-010 (High):** logout doesn't clear the query cache; a subsequent login on the same tab could momentarily read another user's cached data before refetch. Add `queryClient.clear()` on logout.
- **FE-014 (Medium):** ~8 mutations under-invalidate (order/add-item/invoice/payment/credit-note/transition/delivery-confirm/measurement-version), so dependent screens (dashboard, customer detail, orders list, rack, audit) can show stale data until manual refresh.

**Verdict:** keys are centralized and most CRUD invalidations are correct, but **branch-switch and logout do not reset the cache** (High) and several cross-entity invalidations are missing (Medium).
</content>
