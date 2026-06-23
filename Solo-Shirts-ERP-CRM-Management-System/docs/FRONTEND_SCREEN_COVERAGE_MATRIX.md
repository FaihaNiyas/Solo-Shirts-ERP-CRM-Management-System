# Frontend Screen Coverage vs Backend Workflow — Solo Shirts India ERP

**Date:** 2026-06-12 · **Source of truth:** `BACKEND_WORKFLOWS.md`. Routes under `frontend/src/app/(shell|auth)/`.
**Legend:** Exists ✅/❌ · API Hook (real API, not mock) · UI Action (form/button) · PW = Playwright test exists.

| # | Backend Workflow Step | Frontend Route/Screen | Exists | API Hook | UI Action | PW | Status | Issue |
|--|--|--|--|--|--|--|--|--|
| 1 | Auth/Login | `(auth)/login/page.tsx` | ✅ | ✅ real | ✅ | ✅ | Pass | build prerender fails (FE-001) |
| 2 | 2FA | `(auth)/2fa/page.tsx` | ✅ | ✅ real | ✅ | ⚠️ | Pass | |
| 3 | Dashboard | `(shell)/dashboard/page.tsx` | ✅ | ✅ real | read-only | ⚠️ | Pass | |
| 4 | Front Desk | `(shell)/front-desk/page.tsx` | ✅ | ✅ real | ✅ | ⚠️ | Pass | core flow ok |
| 5 | Customer search | `customers/page.tsx` | ✅ | ✅ real | ✅ | ✅ | Pass | |
| 6 | Customer QR scan | `scan/page.tsx` | ✅ | ❌ **broken** | ✅ | ❌ | **Fail** | calls `/scan` (FE-006) |
| 7 | Customer profile | `customers/[id]/page.tsx` | ✅ | ✅ real | ✅ | ✅ | Pass | |
| 8 | Family members | `customers/[id]` (inline) | ✅ | ✅ real | ⚠️ display-only | ❌ | Partial | no add/edit UI (FE-023) |
| 9 | Measurement profile | `measurements/[profileId]/page.tsx` | ✅ | ✅ real | ✅ | ❌ | Pass | |
| 10 | Measurement version create | `measurements/[profileId]` | ✅ | ✅ real | ✅ | ❌ | Pass | append-only |
| 11 | Measurement approve/reject | `measurements/approvals/page.tsx` | ✅ | ✅ real | ✅ | ⚠️ | Pass | |
| 12 | Order create | `front-desk/page.tsx` | ✅ | ✅ real | ✅ | ✅(api) | Pass | |
| 13 | Add order items | `front-desk` (OrderItemBuilder) | ✅ | ✅ real | ✅ | ❌ | Pass | invalidation gap (FE-014) |
| 14 | Job card PDF | `orders/[id]/page.tsx` | ✅ | ❌ **broken** | ✅ | ❌ | **Fail** | `job-card.pdf` (FE-005) |
| 15 | Production board | `production/page.tsx` | ✅ | ✅ real | ✅ | ⚠️ | Pass | |
| 16 | Production item history | `production` (inline) | ⚠️ | ✅ | ❌ no timeline | ❌ | Partial | history endpoint exists, no screen |
| 17 | Cutting queue | `cutting/page.tsx` | ✅ | ✅ `useCuttingQueue` (`/cutting/queue`) | ✅ | ✅ `cutting-flow.spec` | Pass | loading/empty/error+request_id |
| 18 | Fabric allocation | `cutting/page.tsx` | ✅ | ✅ `useAllocateFabric` | ✅ drawer | ✅ | **Pass** | ~~FE-002~~ fixed (real `/cutting/allocate-fabric`) |
| 19 | Cutting start/complete | `cutting/page.tsx` | ✅ | ✅ `useStartCutting`/`useCompleteCutting` | ✅ | ✅ | **Pass** | ~~FE-002~~ fixed; release-fabric too |
| 20 | Tailoring assignments | `tailoring/page.tsx` | ✅ | ✅ real | ✅ | ⚠️ | Pass | |
| 21 | Tailor start/complete | `tailoring/page.tsx` | ⚠️ | ⚠️ | ❌ no buttons | ❌ | Partial | start/complete not wired |
| 22 | QC inspect | `qc/page.tsx` | ✅ | ✅ real | ✅ | ⚠️ | Pass | |
| 23 | Rework flow | `qc/page.tsx` | ✅ | ✅ real | ✅ | ❌ | Pass | |
| 24 | Ironing/finishing | `production` (inline) | ⚠️ | ⚠️ | ❌ | ❌ | Partial | stage visible, no action |
| 25 | Rack slot assign/release | `rack/page.tsx` | ✅ | ✅ real | ✅ | ⚠️ | Pass | |
| 26 | Delivery list/create | `deliveries/page.tsx` | ✅ | ✅ real | ✅ | ❌ | Pass | |
| 27 | Delivery dispatch | `deliveries/page.tsx` | ✅ | ✅ real | ✅ | ❌ | Pass | |
| 28 | Delivery OTP confirm | `deliveries/page.tsx` | ✅ | ✅ real | ✅ (423 lockout) | ❌ | Pass | no PW (FE-017) |
| 29 | Finance dashboard | `finance/page.tsx` | ✅ | ✅ real | read-only | ✅ | Pass | |
| 30 | Invoice create | `finance/invoices/page.tsx` | ✅ | ✅ real | ✅ | ⚠️ | Pass | invalidation gap (FE-014) |
| 31 | Invoice PDF | `finance/invoices/[id]/page.tsx` | ✅ | ✅ real | ✅ | ❌ | Pass | uses `/pdf` (correct) |
| 32 | Payment record | `finance/invoices/[id]` | ✅ | ✅ real | ✅ | ❌ | Pass | |
| 33 | Credit note | `finance/invoices/[id]` | ✅ | ✅ real | ✅ | ❌ | Pass | |
| 34 | Inventory fabric rolls | `inventory/fabric-rolls/page.tsx` | ✅ | ✅ real | ✅ (rem/res/avail) | ✅ | Pass | rule 25 ✅ |
| 35 | Inventory movements | `inventory/fabric-rolls/[id]` | ✅ | ✅ real | ✅ adjust | ❌ | Pass | |
| 36 | Low stock | `inventory/page.tsx` | ✅ | ✅ real | ✅ | ❌ | Pass | |
| 37 | Purchase orders | `inventory/purchase-orders/page.tsx` | ✅ | ✅ real | ✅ place/receive/cancel | ❌ | Pass | |
| 38 | Reports | `reports/page.tsx` | ✅ | ✅ real | ✅ (job poll) | ⚠️ | Pass | |
| 39 | Audit | `audit/page.tsx` | ✅ | ✅ real | read-only | ✅ | Pass | rule 28 ✅ |
| 40 | Notifications | NotificationBell (no page) | ⚠️ | ✅ real | bell only | ❌ | Partial | no `/notifications` page (FE-023) |
| 41 | Settings | `settings/*` | ✅ | ⚠️ | ✅ | ❌ | Partial | profile/change-pw broken (FE-003/004) |

## Summary
- **Screens present:** 39/41 have a real route+UI. Strong coverage.
- **Real API (not mock):** ✅ all screens use real hooks/`apiGet`/`apiMutate` — **no mock data in workflow screens** (verified — rule 9 PASS). Only `login/page.tsx` has `DEMO_USERS` quick-login buttons (dev convenience, not workflow data).
- **Broken / wrong-endpoint screens:** ~~QR scan (FE-006), Job card (FE-005), Cutting allocate/start/complete (FE-002)~~ — **all fixed (2026-06-12).**
- **Partial (display-only / action missing):** family members add/edit, production history timeline, tailor start/complete, ironing action, notifications page.
- **Missing screen:** dedicated Notifications page (bell exists).
</content>
