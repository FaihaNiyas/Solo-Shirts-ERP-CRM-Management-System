# Production / Cutting / QC Alignment Audit — Solo Shirts India ERP

**Date:** 2026-06-12 · Routes: `production/page.tsx`, `cutting/page.tsx`, `qc/page.tsx`, `rack/page.tsx`; `components/production/{KanbanBoard,ProductionCard}.tsx`; `hooks/useProduction.ts`.

## Production
| Check | Backend Endpoint | Actual | Status | Issue |
|--|--|--|--|--|
| Board | GET /production/board | `useProductionBoard` (polling) | Pass | |
| Item detail | GET /production/items/{id} | `productionItem(id)` | Pass | |
| Transition | POST /production/items/{id}/transition | `useTransitionItem` (key per call) | Pass | non-stable key (FE-007) |
| Transition history | GET /production/items/{id}/history | `productionHistory(id)` | Partial | endpoint wired, **no history timeline screen** (#16) |
| Invalid transition error | 4xx domain error | normalizeError surfaces code/message | Pass | |
| State shown on order_items | per-item state | Kanban cards show per-item state (rule 24) | Pass | ✅ |

## Cutting — **✅ FIXED (FE-002, 2026-06-12)**
| Check | Backend Endpoint | Actual | Status | Issue |
|--|--|--|--|--|
| Queue | GET /cutting/queue | ✅ `useCuttingQueue()` → `GET /cutting/queue` (loading/empty/error states; error shows request_id) | **Pass** | ~~FE-002~~ |
| Allocation uses **cutting** endpoint (not inventory) | POST /cutting/items/{id}/allocate-fabric | ✅ `useAllocateFabric` (stable Idempotency-Key) via Allocate drawer | **Pass** | rule 26 ✅ |
| Release fabric | POST /cutting/items/{id}/release-fabric | ✅ `useReleaseFabric` | **Pass** | |
| Start cutting | POST /cutting/items/{id}/start-cutting | ✅ `useStartCutting` | **Pass** | |
| Complete cutting | POST /cutting/items/{id}/complete-cutting | ✅ `useCompleteCutting` (actual_metres + bundles) — consumes fabric, creates bundles | **Pass** | |
| Stock remaining/reserved/available | separate fields | ✅ shown in the Allocate drawer for the selected roll (backend-accurate `remaining_metres`/`available_metres`, reserved computed) | Pass | rule 25 ✅ |
| Idempotency-Key on allocate/complete | required/policy | ✅ stable key (`useStableIdempotencyKey`) on all 4 mutations | Pass | FE-007 pattern |
| Bundle QR lookup via backend | GET /cutting/bundles/by-qr/{payload} | ✅ `useBundleByQr` (server-side validation, no client decode) | Pass | |
| No production-transition shortcut | — | ✅ `useTransitionItem` **removed** from cutting actions | Pass | rule: no shortcut |
| Rules-of-hooks | — | ✅ all hooks at component top level (RowActions/AllocateDrawer/CompleteDrawer children) | Pass | ~~FE-022~~ fixed |

**FE-002 fix:** new `lib/api/hooks/useCutting.ts` (queue, allocatable-rolls, allocate, release, start, complete, bundle, bundleByQr — all via centralized `ENDPOINTS`, stable keys, correct invalidations). `cutting/page.tsx` rewritten to a real state-driven flow (draft→Allocate, fabric_allocated→Release/Start, cutting→Complete) using existing design-system components; the generic `/production/transition` shortcut and the in-callback `useTransitionItem` (FE-022) are gone.

## QC
| Check | Backend Endpoint | Actual | Status | Issue |
|--|--|--|--|--|
| Inspect | POST /qc/items/{id}/inspect | `apiMutate` via IdempotencyGuard (stable key) | Pass | ✅ |
| Defect categories load | GET /qc/defects/categories | `defectCategories` | Pass | |
| Rework flow visible | disposition=rework, rework_count | rework option + count badge | Pass | |
| Override permission-gated | `production.rework.override` | rework-override gated | Pass | |
| Photo upload | POST /qc/photos | `qcPhotos` endpoint defined | Partial | verify upload UI wired |
| Defect analytics | GET /qc/defects/analytics | **not referenced** | Partial | FE-019 (FE gap, low) |

## Rack
| Check | Backend Endpoint | Actual | Status |
|--|--|--|--|
| Assign / release / current slot | /rack/items/{id}/assign\|release\|current-slot | grid UI + IdempotencyGuard assign | Pass |
| One active slot per item | DB-unique enforced backend | UI assigns; backend rejects dup | Pass |

## Verdict
Production board (rule 24), QC/Rack, **and now Cutting** are aligned. **FE-002 is fixed (2026-06-12):** the cutting screen drives the real `allocate-fabric → start-cutting → complete-cutting` (+ `release-fabric`) endpoints with stable Idempotency-Keys and correct invalidations, so 2-phase fabric reservation/consumption and bundle creation happen from the UI. The production-transition shortcut and the rules-of-hooks bug (FE-022) are removed. type-check + build green.
</content>
