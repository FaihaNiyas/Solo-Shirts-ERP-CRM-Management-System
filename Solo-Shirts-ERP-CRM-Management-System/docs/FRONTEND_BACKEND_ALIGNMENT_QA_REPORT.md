# Frontend ↔ Backend Alignment QA Report — Solo Shirts India ERP

**Date:** 2026-06-12 · **Engagement:** inspect → first-group fix.
**Backend:** stable (309 passed / 0 failed). **Frontend toolchain (post fix-group-1):** type-check ✅, **build ✅ (now passes)**, lint ⚠️ still unconfigured.

> **✅ Fix group 1 (2026-06-12):** FE-001, FE-005, FE-006 fixed; FE-003/004 disabled (backend gap).
> **✅ Fix group 2 — API client + cache + idempotency (2026-06-12):** FE-024, FE-009, FE-010 fixed; FE-007 fixed (stable keys); FE-008 infra shipped, validation deferred (FE-025).
> **✅ Fix group 3 — Cutting real backend flow (2026-06-12):** FE-002 + FE-022 fixed.
> **✅ Fix group 4 — Role/permission UX (2026-06-13):** FE-011, FE-012, FE-013 fixed.
> **✅ Fix group 5 — Schema alignment (2026-06-13):** **FE-025 — all 7 entity schemas re-derived from the backend resources**; **FE-008 Zod validation now live on 6/7** (Orders, Finance, Inventory, Delivery, Customer, Measurement). Production schema aligned (superset) but raw-validation deferred (its `selectBoard` transform). type-check + build green.

**Tally (open):** **0 Blocker · 0 High · 3 Medium · 6 Low** (was 1/9/8/6). Fixed: FE-001, FE-002, FE-005, FE-006, FE-007, FE-008 (6/7), FE-009, FE-010, FE-011, FE-012, FE-013, FE-022, FE-024, **FE-025 (schemas aligned)**. Deferred-backend: FE-003, FE-004. Open Medium: FE-014, FE-015, FE-016, FE-017. (Production raw-validation = small follow-up.)
**Categories:** FE gap / Backend gap — needs confirmation / Contract mismatch / Permission mismatch / Idempotency mismatch / UX mismatch / Test gap.

---

**FE-001 — `next build` fails on `/login` prerender**
Category: Frontend gap (build) · Module: Build · Screen: Login · Severity: **Blocker**
Backend source: n/a · Frontend file: `src/app/(auth)/login/page.tsx`
Expected: `npm run build` succeeds. Actual (was): SSG of `/login` threw `Could not find the module "…login/page.tsx#default" in the React Client Manifest` (Next **15.5.19**). Root cause: client-only auth pages failed the static-export pass. **Fix applied:** added a server `src/app/(auth)/layout.tsx` exporting `export const dynamic = 'force-dynamic'` (route-segment config is only honoured on a server module — the page-level export was ignored). login/2fa now render dynamically. **Verified:** `npm run build` → ✓ Compiled, ✓ 37/37 static pages, exit 0. Status: ✅ **Fixed (2026-06-12).**

**FE-002 — Cutting UI bypasses cutting/fabric endpoints**
Category: Contract mismatch / Workflow · Module: Production/Cutting · Screen: `/cutting` · Severity: **High**
Backend source: `BACKEND_WORKFLOWS.md` E2E-3, `endpoints.allocateFabric/startCutting/completeCutting` · Frontend file: `src/app/(shell)/cutting/page.tsx:6,73`
Expected: cutting drives `/cutting/items/{id}/allocate-fabric|start-cutting|complete-cutting` (2-phase stock + bundles). Actual (was): only `useTransitionItem` → `/production/transition`. **Fix applied (2026-06-12):** new `lib/api/hooks/useCutting.ts` (`useCuttingQueue`, `useAllocateFabric`, `useReleaseFabric`, `useStartCutting`, `useCompleteCutting`, `useBundle`, `useBundleByQr`, `useAllocatableRolls` — centralized endpoints, stable Idempotency-Keys, invalidate cutting-queue/board/fabric-rolls/movements/tailoring). `cutting/page.tsx` rewritten to a state-driven flow (Allocate/Release/Start/Complete drawers + buttons); the production-transition shortcut is removed. `e2e/cutting-flow.spec.ts` added. Status: ✅ **Fixed.**

**FE-003 — `/auth/change-password` not in backend**
Category: Backend gap — needs confirmation · Module: Settings · Severity: **High**
Backend: OpenAPI has no such route · Frontend: `settings/profile/page.tsx:61`, `endpoints.ts:16`
Expected: change password works. Actual: 404. **Mitigated (2026-06-12):** the change-password action in `settings/profile/page.tsx` is **disabled** with a "Backend gap — needs confirmation" banner; the form no longer submits to the missing route. Status: ⏸️ **Deferred — awaiting backend decision** (add route, or keep disabled).

**FE-004 — `PUT /auth/me` (profile update) not in backend (GET-only)**
Category: Backend gap — needs confirmation · Module: Settings · Severity: **High**
Backend: `/auth/me` GET-only · Frontend: `settings/profile/page.tsx:47`, `endpoints.ts:15`
Expected: profile save works. Actual: 405/404. **Mitigated (2026-06-12):** the profile "Save Changes" action is **disabled** with a "Backend gap — needs confirmation" banner; the form no longer PUTs to `/auth/me`. Status: ⏸️ **Deferred — awaiting backend decision.**

**FE-005 — Job card uses `/orders/{id}/job-card.pdf` (backend: `/job-card`)**
Category: Contract mismatch · Module: Orders · Screen: order detail · Severity: **High**
Backend: `GET /orders/{order}/job-card` · Frontend: `endpoints.ts:52`
Expected: job card opens. **Fix applied:** `jobCardPdf` now returns `/orders/${id}/job-card` (dropped `.pdf`). Status: ✅ **Fixed (2026-06-12).**

**FE-006 — QR scan posts to `/api/v1/scan` (absent)**
Category: Contract mismatch · Module: Customer/Scan · Screen: `/scan` · Severity: **High**
Backend: no `/scan`; QR via `GET /customers/by-qr/{payload}` · Frontend: `scan/page.tsx`
Expected: scan loads customer; tamper → INVALID_QR_SIGNATURE+request_id. **Fix applied:** removed the invalid `scan` constant from `endpoints.ts`; `scan/page.tsx` now `apiGet(ENDPOINTS.customerByQr(encodeURIComponent(payload)))` (server-side validation, no client decode — rule 21). Status: ✅ **Fixed (2026-06-12).**

**FE-007 — Idempotency key not stable across double-submit**
Category: Idempotency mismatch · Module: cross-cutting · Severity: **High**
Backend: `BACKEND_IDEMPOTENCY_REPORT.md` (key-required writes) · Frontend: `client.ts:133,153,168`, hooks
Expected: same key for a given submit so a double-click replays. Actual (was): fresh `crypto.randomUUID()` per call → double-click = duplicate. **Fix applied (2026-06-12):** new `lib/api/idempotency.ts` (`useStableIdempotencyKey` — in-memory ref, never localStorage; rotates on success, kept on error for idempotent retry). Threaded into the high-risk hooks: `useCreateOrder`, `useAddOrderItem`, `useTransitionItem`, `useCreateInvoice`, `useRecordPayment`, `useIssueCreditNote`, `useConfirmDelivery`. A double-click now sends one key. (allocate-fabric/complete-cutting hooks don't exist yet — FE-015/FE-002.) Status: ✅ **Fixed (high-risk hooks).**

**FE-008 — Zod schemas defined but never validate responses**
Category: Contract mismatch (data integrity) · Module: API layer · Severity: **High**
Backend: standard envelope · Frontend: `schemas/*.ts` exist; `hooks/*.ts` cast `as T`, 0/27 `.parse()`
Expected: validate `data` with Zod (rule 13). **Fixed (2026-06-13):** `parseApiData()` infra + **validation now live on 6/7 entities** (Orders, Finance, Inventory, Delivery, Customer, Measurement) after the FE-025 schema alignment — detail queries + key mutations run `parseApiData` (controlled `FRONTEND_SCHEMA_MISMATCH` on real drift). Production validation deferred pending its transform-layer refactor. Status: ✅ **Live on 6/7 (Production = follow-up).**

**FE-009 — Branch switch does not invalidate branch-scoped queries**
Category: Contract/UX mismatch · Module: Branch · Severity: **High**
Backend: token `active_branch_id` · Frontend: `BranchSwitcher.tsx`, `branch-context.ts:11-22`
Expected: switch → invalidate all branch-scoped queries (rule 19). Actual (was): token updated, cache stale. **Fix applied (2026-06-12):** `BranchProvider.switchBranch` now calls `queryClient.clear()` after the new token is set — every query refetches under the new branch. Status: ✅ **Fixed.**

**FE-010 — Logout does not clear the query cache**
Category: Auth/UX mismatch · Module: Auth · Severity: **High**
Frontend: `UserMenu.tsx:18-28` (no `queryClient.clear()`)
Expected: logout drops cached data. Actual (was): cache persists. **Fix applied (2026-06-12):** `UserMenu.handleLogout` now calls `queryClient.clear()` after `clearSession()` + store `reset()`, before redirect. Status: ✅ **Fixed.**

**FE-011 — No 403 / forbidden page** · Permission/UX · **Medium** · ✅ **Fixed (2026-06-13).** Added `components/shell/AccessDenied.tsx` + `RequireRole` guard + `(shell)/forbidden/page.tsx`; wired into `/cutting`. Existing admin/audit/finance ad-hoc gates kept (preserve `rbac.spec` text) — adopt `RequireRole` incrementally.

**FE-012 — No per-role landing route** · UX · **Medium** · ✅ **Fixed (2026-06-13).** `lib/auth/landing.ts` `landingRouteForRoles()` used in login + 2FA; Owner/Admin keep `/dashboard` (auth.spec safe), others land on their workspace.

**FE-013 — ROLES constant holds 11/14 roles** · Permission · **Medium** · ✅ **Fixed (2026-06-13).** Added `KAJA`/`IRONING`/`REWORKER` to `permissions.ts` ROLES (all 14 now named). (Admin-page hardcoded arrays dedupe is optional polish.)

**FE-014 — Mutation invalidation gaps (~8)**
Category: Contract/UX mismatch · Severity: **Medium** · Frontend: `hooks/*.ts`
Expected/Actual gaps: order-create (−customer,−dashboard), add-item (−orders), invoice (−dashboard,−orders), payment (−orders), credit-note (−dashboard), transition (−audit), delivery-confirm (−orders,−rack), measurement-version (−customer list). Fix: add invalidations. Status: Not fixed.

**FE-015 — 11 mutation hooks missing (inline mutations)**
Category: Frontend gap · Severity: **Medium** · Frontend: `hooks/*`
Missing: allocate/release fabric, start/complete cutting, QC inspect, rework override, tailor start/complete/reassign, rack assign/release. Components call `apiMutate` inline → inconsistent keys/invalidation. Fix: add hooks. Status: Not fixed.

**FE-016 — `npm run lint` not configured**
Category: Tooling · Severity: **Medium** · Frontend: no `.eslintrc`
Actual: `next lint` prompts interactively, exits 1 → unrunnable in CI. Fix: add ESLint flat config (next/core-web-vitals). Status: Not fixed.

**FE-017 — Playwright gaps (request_id, idempotency, several flows, mobile)**
Category: Test gap · Severity: **Medium** · Frontend: `e2e/*`
Missing: branch-switch, customer-qr, qc-rework, delivery-otp, idempotency-double-submit, error-request-id, mobile; no request_id/double-submit assertions. Fix: add specs. Status: Not fixed.

**FE-024 — Envelope `success:false` not enforced; request_id body-only**
Category: Contract mismatch · Severity: **Medium** · Frontend: `client.ts:107-141`
Expected: reject `success===false`; request_id fallback to `X-Request-Id` header. Actual (was): returned raw data; request_id body-only. **Fix applied (2026-06-12):** `client.ts` `ensureSuccess()` now throws a normalized `ApiError` on any non-true `success` (applied in `apiGet`/`apiMutate`/`apiPost`/`apiPut`); `normalizeError` falls back to the `X-Request-Id` header when the body lacks request_id. Status: ✅ **Fixed.**

**FE-018 — `X-Branch-Id` header ignored by backend** · Category: Contract mismatch · **Low** · `client.ts:26-31`. Backend uses token; remove or confirm.
**FE-019 — `qc/defects/analytics` backend endpoint unused** · Frontend gap · **Low**.
**FE-020 — No `.env.example`; no Storybook** · Frontend gap/Tooling · **Low**.
**FE-021 — Inline query keys (measurements versions, customers by-qr)** · Frontend gap · **Low** · `useMeasurements.ts:29,63,75`, `useCustomers.ts:36`.
**FE-022 — `useTransitionItem` called inside a callback (rules-of-hooks)** · Frontend bug · **Low** · `cutting/page.tsx:73`. ✅ **Fixed (2026-06-12)** as part of FE-002 — the page was rewritten; all hooks are now at component top level.
**FE-023 — No Notifications page (bell only); family members display-only** · Frontend gap · **Low**.

**FE-025 — FE Zod schemas / types drift from backend resources (NEW — found wiring FE-008)**
Category: Contract mismatch · Module: API layer · Severity: **Medium**
Backend source: `app/Modules/*/Http/Resources/*Resource.php` · Frontend: `lib/api/schemas/*.ts` + components
Expected: FE schema fields match backend resource fields so Zod can validate. Actual: significant drift, e.g. backend `OrderItem` exposes `product_type`,`state`,`item_code` (no `order_id`/`created_at`) but FE `OrderItemSchema` requires `garment_type`,`production_state`,`order_id`,`created_at`; backend `Order` has no `measurement_version_id`/`created_by`/`updated_at` at the order level but FE `OrderSchema` requires them. The FE compensates with ad-hoc field remapping inside `select` (e.g. `useProduction.selectBoard`: `garment_type: it.product_type`, `production_state: toPascalState(it.state)`). Root cause: schemas/types were authored speculatively, not derived from the backend resources. Consequence: (a) enabling strict Zod `.parse()` on raw responses would falsely throw; (b) re-aligning schemas would cascade-break components that read the old field names. Fix recommendation: a **dedicated schema-alignment task** — re-derive each Zod schema from the backend `*Resource.php`, centralize the field remapping, update component references, then enable `parseApiData` validation in hooks. Test needed: per-entity contract tests vs a seeded backend.
**Progress (2026-06-13) — ORDERS + FINANCE + INVENTORY + DELIVERY entities aligned (the pattern):**
- **Inventory:** `schemas/inventory.ts` re-derived from `FabricRollResource`/`MovementResource`/`SupplierResource`/`FabricTypeResource` (metre values tolerate number-or-string; enums→string; FE `*_meters`/`roll_number` kept optional). `parseApiData` on `useFabricRoll`/`useAdjustFabricRoll`.
- **Delivery:** `schemas/delivery.ts` re-derived from `DeliveryResource` (added `mode`/`courier_partner`/`tracking_no`/`*_at`; status→string; FE-only optional). `parseApiData` on `useDelivery`/`useCreateDelivery`.
- **Orders:** `schemas/orders.ts` re-derived from `OrderResource`/`OrderListResource`/`OrderItemResource`; components read the **real** fields (`product_type`/`state`/`design_notes`/`expected_delivery_date`) so the orders list/detail show real data instead of the previously-**blank** drifted fields; genuine gaps (`customer_name`/`total_amount`/`branch_name`/`assigned_tailor_name`) kept optional+flagged (`customer_name`→`Customer #id`); `parseApiData` on `useOrder`/`useCreateOrder`/`useAddOrderItem`.
- **Finance:** `schemas/finance.ts` re-derived from `InvoiceResource`/`PaymentResource`/`CreditNoteResource` + the dashboard summary shape; over-strict enums relaxed to strings (backend `status`/`method` values never falsely fail); FE-only `_paise`-vs-rupee aliases kept optional. `parseApiData` enabled on `useInvoice`/`useFinanceDashboard`/`useCreateInvoice`/`useRecordPayment`/`useIssueCreditNote` (zero component breakage — clean widening). type-check + build green.

- **Customer / Measurement:** `schemas/customers.ts` re-derived from `CustomerResource`/`FamilyMemberResource` (note: raw phone is encrypted → backend returns `phone_masked`; `special_notes`/`customer_code` added; FE-only optional). `schemas/measurements.ts` re-derived from `MeasurementVersionResource`/`MeasurementProfileResource` (status→string; `significant_change`/`effective_from`/`rejection_reason` added). `parseApiData` on `useCustomer`/`useCustomerByQr`/`useCreateCustomer` and `useMeasurementVersion`/`useCreateMeasurementVersion`.
- **Production:** `schemas/production.ts` made a superset (raw `ProductionItemResource` fields `product_type`/`state`/`item_code`/etc. added as optional). **Validation deferred** because the board response is raw snake_case and the FE derives `garment_type`/`production_state` in `useProduction.selectBoard` — needs the transform centralized first (documented sub-task).

**All 7 entity schemas aligned (2026-06-13).** Validation **active on 6/7** (Orders, Finance, Inventory, Delivery, Customer, Measurement); Production validation deferred (transform layer). Genuine **backend gaps to confirm**: orders `customer_name`/`total_amount`; rupee-vs-`_paise`/`_metres` display formatting. Status: ✅ **Schema alignment complete; validation live on 6/7 (Production transform = follow-up).**

---

## Severity tally (updated after fix group 2)
| Severity | Open | IDs |
|--|--|--|
| Blocker | **0** | ~~FE-001 ✅~~ |
| High | **1** | FE-002 (~~FE-005,006,007,009,010 ✅~~; FE-003/004 ⏸️ deferred-backend; FE-008 ⏸️ infra-done/deferred) |
| Medium | 8 | FE-011, FE-012, FE-013, FE-014, FE-015, FE-016, FE-017, **FE-025** (~~FE-024 ✅~~) |
| Low | 6 | FE-018…FE-023 |
**Fixed:** FE-001, FE-005, FE-006, FE-007, FE-009, FE-010, FE-024. **Deferred:** FE-003/004 (backend), FE-008 (schema-align via FE-025). **Open High:** FE-002 (cutting).

**Headline:** The frontend is **well-architected** (centralized endpoints, typed envelope, Zod schemas present, sessionStorage tokens, Owner-only branch switcher, real-backend e2e, no mock workflows). Alignment risk concentrates in: **1 build blocker (FE-001)**, **4 contract mismatches** that 404 against the real backend (FE-003/004/005/006), the **cutting endpoints never called (FE-002)**, **non-stable idempotency keys (FE-007)**, **unused Zod validation (FE-008)**, and **cache not reset on branch-switch/logout (FE-009/010)**.
</content>
