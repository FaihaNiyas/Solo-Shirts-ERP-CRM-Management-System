# Frontend Idempotency Alignment — Solo Shirts India ERP

**Date:** 2026-06-12 · **Source of truth:** `BACKEND_IDEMPOTENCY_REPORT.md`.
**Core mechanic:** `client.ts:apiMutate/apiPost/apiPut` **auto-generate a fresh `crypto.randomUUID()` per call** when no key is passed (`client.ts:133,153,168`). Hooks never pass a stable key. So:
- ✅ An `Idempotency-Key` **header is always sent** on every mutation.
- ❌ It is a **new key every click** → backend sees two distinct keys on a double-submit → **two records**. Backend idempotency only protects a *retry of the same key* (e.g., network retry of one in-flight request), **not** a user double-click.
- `IdempotencyGuard` (`components/ui/idempotency-guard.tsx`) keeps a **stable `keyRef`** + disables the button — but it is **only wired into a few screens** (front-desk order, approvals, qc, rack), not the hooks.

> **✅ Fix group 2 (2026-06-12) — FE-007 fixed for high-risk hooks:** new `lib/api/idempotency.ts` (`useStableIdempotencyKey()` — in-memory ref, rotates on success, kept on error). Threaded into `useCreateOrder`, `useAddOrderItem`, `useTransitionItem`, `useCreateInvoice`, `useRecordPayment`, `useIssueCreditNote`, `useConfirmDelivery`. A double-click now sends one stable key. The rows below marked "non-stable key (hook)" for these actions are now **stable**. (allocate-fabric/complete-cutting still pending — those hooks don't exist yet, FE-002/FE-015.)

| Frontend Action | Endpoint | BE Requires Key | FE Sends Key | Double-Submit Guard | Query Invalidated | Status | Issue |
|--|--|--|--|--|--|--|--|
| create customer | POST /customers | no (DUPLICATE_PHONE) | ✅ fresh | button isPending only | customers | Partial | non-stable key (FE-007) |
| create family member | POST /customers/{c}/family-members | no | ✅ fresh | button | familyMembers | Partial | |
| create measurement profile | POST /customers/{c}/measurements | no | ✅ fresh | button | — | Partial | missing invalidation |
| create measurement version | POST /…/versions | no (append-only) | ✅ fresh | button | versions, pendingApprovals | Partial | missing parent list (FE-014) |
| approve measurement | POST /versions/{v}/approve | **yes** | ✅ (IdempotencyGuard) | ✅ stable key+disabled | pendingApprovals, version | **Pass** | |
| reject measurement | POST /versions/{v}/reject | no (state guard) | ✅ | guard | pendingApprovals, version | Pass | |
| **create order** | POST /orders | **yes** | ✅ (front-desk uses key) | ✅ guard (front-desk) | orders | Pass | invalidation gap (customer/dashboard) |
| **add order item** | POST /orders/{o}/items | **yes** | ✅ fresh (hook) | button only | order | **Fail** | non-stable key; no `orders` invalidation (FE-007/014) |
| cancel order | POST /orders/{o}/cancel | no | ✅ fresh | button | order, orders | Partial | |
| **production transition** | POST /production/items/{i}/transition | **yes** | ✅ fresh (hook) | confirm dialog | board, item, history | Partial | non-stable key (FE-007); cutting/page calls hook in callback (FE-022) |
| **allocate fabric** | POST /cutting/items/{i}/allocate-fabric | **yes** | ✅ stable (`useAllocateFabric`) | ✅ drawer + isPending | cuttingQueue, board, fabricRolls, movements | **Pass** | ~~FE-002~~ fixed |
| release fabric | POST /cutting/items/{i}/release-fabric | no | ✅ stable (`useReleaseFabric`) | ✅ isPending | cuttingQueue, board, fabricRolls | **Pass** | ~~FE-002~~ fixed |
| start cutting | POST /cutting/items/{i}/start-cutting | no | ✅ stable (`useStartCutting`) | ✅ isPending | cuttingQueue, board | **Pass** | ~~FE-002~~ fixed |
| **complete cutting** | POST /cutting/items/{i}/complete-cutting | no (state guard) | ✅ stable (`useCompleteCutting`) | ✅ drawer + isPending | cuttingQueue, board, fabricRolls, movements, tailoring | **Pass** | ~~FE-002~~ fixed |
| create tailoring assignment | POST /tailoring/assignments | no | ✅ fresh | drawer button | — | Partial | |
| tailor start/complete | POST /…/start, /complete | no | ⚠️ not wired | — | — | Partial | buttons missing (Screen #21) |
| QC inspect | POST /qc/items/{i}/inspect | no (state guard) | ✅ (guard) | ✅ stable | — | Pass | inline mutation |
| QC rework override | POST /qc/items/{i}/rework-override | no | ✅ | button | — | Partial | |
| rack assign | POST /rack/items/{i}/assign | no (DB unique) | ✅ (guard) | ✅ stable | — | Pass | |
| rack release | POST /rack/items/{i}/release | no | ✅ | button | — | Partial | |
| create delivery | POST /deliveries | no | ✅ fresh | button | deliveries | Partial | |
| dispatch delivery | POST /deliveries/{d}/dispatch | no | ✅ fresh | button | delivery, deliveries | Partial | |
| **confirm delivery** | POST /deliveries/{d}/confirm | **yes** | ✅ fresh (hook) | OTP dialog | delivery, deliveries | Partial | non-stable key; no `orders`/rack invalidation (FE-014) |
| **create invoice** | POST /finance/invoices | **yes** | ✅ fresh (hook) | button | invoices | **Fail** | non-stable key (FE-007); missing dashboard/orders invalidation |
| **record payment** | POST /finance/payments | **yes (app-level)** | ✅ fresh (hook) | button | invoice, payments, dashboard | Partial | non-stable key |
| **create credit note** | POST /finance/invoices/{i}/credit-note | **yes** | ✅ fresh (hook) | button | invoice, invoices | Partial | non-stable key; missing dashboard |
| create damage report | POST /damage-reports | no | ✅ fresh | button | — | Partial | |
| **approve damage report** | POST /damage-reports/{d}/approve | **yes** | ✅ fresh | button | — | Partial | non-stable key |
| run report | POST /reports/run | no | ✅ fresh | button | — | Pass | |

## Findings
- **FE-007 (High) — idempotency key is not stable across a double-submit.** For the 8 backend-key-required mutations (order, add-item, transition, allocate-fabric, delivery-confirm, damage-approve, invoice, credit-note) the FE sends a *new* key per click, so a fast double-click on **add-item / invoice / credit-note** can mint duplicates. The cure already exists in the repo (`IdempotencyGuard`'s `keyRef`) but is applied to only ~4 screens. The financial creates (invoice, credit-note, payment) are the highest risk.
- **FE-002 (High) — cutting endpoints (`allocate-fabric`, `start-cutting`, `complete-cutting`) are never called.** The Cutting UI advances state via `/production/transition` only, so the backend's 2-phase fabric reserve/consume is bypassed entirely.
- ✅ Where `IdempotencyGuard` is used (approve measurement, QC inspect, rack assign, front-desk order), idempotency + double-submit are correctly handled.

**Verdict:** the header is always present, but **stable-key double-submit protection is inconsistent** — fine on guarded screens, risky on the finance/add-item hooks, and the cutting endpoints aren't called at all.
</content>
