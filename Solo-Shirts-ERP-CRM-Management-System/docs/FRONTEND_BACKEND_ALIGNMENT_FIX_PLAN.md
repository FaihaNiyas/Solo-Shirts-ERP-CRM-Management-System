# Frontend ↔ Backend Alignment Fix Plan — Solo Shirts India ERP

**Date:** 2026-06-12 · Ordered by the prompt's priority. Test-first per item.

> **✅ Fix group 1 DONE (2026-06-12):** FE-001, FE-005, FE-006 fixed; FE-003/FE-004 disabled (backend gap). Files: `(auth)/layout.tsx` (new), `lib/api/endpoints.ts`, `scan/page.tsx`, `settings/profile/page.tsx`.
> **✅ Fix group 2 DONE (2026-06-12) — API client + cache + idempotency:** FE-024, FE-007, FE-009, FE-010 fixed; FE-008 infra shipped, throwing-validation deferred (FE-025 schema drift). `type-check` ✅ + `build` ✅. Files: `lib/api/client.ts`, `lib/api/idempotency.ts` (new), `hooks/{useOrders,useFinance,useProduction,useDeliveries}.ts`, `providers/BranchProvider.tsx`, `shell/UserMenu.tsx`.
> **New finding:** **FE-025** — FE Zod schemas/types drift from backend resources (blocks safe FE-008 validation; needs a dedicated schema-alignment task).
> **✅ Fix group 3 DONE (2026-06-12) — Cutting real backend flow:** FE-002 + FE-022 fixed. Files: `lib/api/hooks/useCutting.ts` (new), `app/(shell)/cutting/page.tsx` (rewrite), `lib/query/keys.ts`, `e2e/cutting-flow.spec.ts` (new).
> **✅ Fix group 4 DONE (2026-06-13) — Role/permission UX:** FE-011, FE-012, FE-013 fixed. `type-check` ✅ + `build` ✅. Files: `lib/auth/permissions.ts`, `lib/auth/landing.ts` (new), `(auth)/login/page.tsx`, `(auth)/2fa/page.tsx`, `components/shell/AccessDenied.tsx` (new), `components/shell/RequireRole.tsx` (new), `(shell)/forbidden/page.tsx` (new), `(shell)/cutting/page.tsx`.
> **✅ Fix group 5 DONE (2026-06-13) — "complete all" sweep:** FE-014, FE-015, FE-016, FE-017, FE-018, FE-020, FE-021 + Production schema/transform closed. `type-check` 0 errors · `lint` clean · `build` ✅. Files: `hooks/{useOrders,useFinance,useProduction}.ts` (cross-entity invalidations — FE-014), `hooks/{useRack,useQc,useTailoring}.ts` (new — FE-015), `.eslintrc.json` (new — FE-016), `lib/api/client.ts` (drop dead `X-Branch-Id` — FE-018), `.env.example` (new — FE-020), `lib/query/keys.ts` + `hooks/{useCustomers,useMeasurements}.ts` (centralized keys — FE-021), `schemas/production.ts` + `hooks/useProduction.ts` (centralized `transformProductionItem` + raw-item `parseApiData` — FE-025 Production). New specs (FE-017): `e2e/{branch-switch,customer-qr,qc-rework,delivery-otp,idempotency-double-submit,error-request-id,mobile-flow}.spec.ts` — **executed against the live stack: 12 passed / 0 skipped / 0 failed.** FE-008 throwing-validation confirmed live across all 7 entity hook families. New finding **FE-001b** (prod-runtime `/login` RSC manifest 500 under `next start`; dev + build fine). **Out of scope (unchanged):** FE-003/FE-004 (backend gap), FE-019/FE-023/Storybook (net-new features).

### 1. Build / type / lint blockers
- **FE-001 (Blocker) — ✅ DONE.** Added server `src/app/(auth)/layout.tsx` with `export const dynamic = 'force-dynamic'` (page-level export on the client page was ignored). login/2fa now dynamic; build passes. Verified: `npm run build` exit 0.
- **FE-001b (NEW, Medium — found 2026-06-13 during e2e execution)** — `next start` (production runtime) 500s on `/login`: `Could not find the module ".../(auth)/login/page.tsx#default" in the React Client Manifest` (reproduced after a clean `.next` rebuild). The static build (`38/38` pages) and `next dev` both render `/login` correctly — only the production server's RSC SSR of the `force-dynamic` auth layout fails. **Does not affect dev or the e2e run** (config targets `next dev`). **Action:** revisit the auth route-group dynamic strategy before a production deploy (candidate: move `force-dynamic` off the layout, or make the login page a server shell wrapping a client form).
- **FE-016 — ✅ DONE (2026-06-13).** Added `frontend/.eslintrc.json` (`{ "extends": "next/core-web-vitals" }`). `npm run lint` → "✔ No ESLint warnings or errors".

### 2. Unknown / missing endpoint blockers (contract mismatches)
- **FE-005 — ✅ DONE.** `endpoints.ts:52` now `/orders/${id}/job-card` (dropped `.pdf`).
- **FE-006 — ✅ DONE.** Removed invalid `scan` constant; `scan/page.tsx` now `apiGet(ENDPOINTS.customerByQr(encodeURIComponent(payload)))` (server-side validation, no client decode).
- **FE-003 / FE-004 — ⏸️ DEFERRED (backend confirmation).** Settings profile-update + change-password actions **disabled** with a "Backend gap — needs confirmation" banner; forms no longer call the missing routes. **Action for backend team:** decide whether to add `PUT /auth/me` (or a profile endpoint) and `/auth/change-password`, then re-enable.
- **FE-018 — ✅ DONE (2026-06-13).** Removed the dead `X-Branch-Id` injection from `lib/api/client.ts` request interceptor (backend resolves branch from the token's `active_branch_id`). **FE-019 / FE-023** (QC analytics, Notifications page) remain out of scope — net-new features. Low.

### 3. API client / envelope blockers
- **FE-024 — ✅ DONE.** `client.ts` `ensureSuccess()` rejects `success!==true` (applied in apiGet/apiMutate/apiPost/apiPut); `normalizeError` falls back to `X-Request-Id` header.

### 4. Zod schema blockers
- **FE-008 — ✅ DONE (2026-06-13).** `parseApiData(envelope, schema)` helper (controlled `FRONTEND_SCHEMA_MISMATCH`) is now **live** across all 7 entity hook families (`useOrders`, `useFinance`, `useInventory`, `useDeliveries`, `useCustomers`, `useMeasurements`, `useProduction`) — the FE-025 schema-drift blocker is resolved (schemas re-derived from `*Resource.php`), so validation no longer falsely breaks valid responses. (The action-only hooks `useCutting`/`useQc`/`useRack`/`useTailoring` have no entity-detail schema to validate — N/A.)
- **FE-025 (NEW, Medium) — ✅ SCHEMAS ALIGNED (2026-06-13):** **all 7 entity schemas re-derived from `*Resource.php`**; FE-008 `parseApiData` validation **live on 6/7** (Orders, Finance, Inventory, Delivery, Customer, Measurement). Production now closed (2026-06-13): the raw→FE mapping is centralized in `transformProductionItem` (used by both `selectBoard` and `useProductionItem`), and `useProductionItem` validates the raw item via `parseApiData(env, RawProductionItemSchema)` — so the detail no longer drifts and the response is schema-checked. Orders: `schemas/orders.ts` re-derived; components updated to real fields; `parseApiData` on order hooks. Finance: `schemas/finance.ts` re-derived from Invoice/Payment/CreditNote resources + dashboard shape; enums relaxed to strings; `parseApiData` on `useInvoice`/`useFinanceDashboard`/`useCreateInvoice`/`useRecordPayment`/`useIssueCreditNote` (zero component breakage). **Recipe per entity:** read `*Resource.php` → align schema (keep genuine gaps optional + flag) → fix component refs (type-check lists them) → enable `parseApiData`. **Remaining:** none — all 7 entity schemas aligned and `parseApiData` live across detail/mutation hooks. **Backend gaps to confirm:** orders `customer_name`/`total_amount`; payment/credit-note rupee-vs-`_paise` display formatting.

### 5. Auth / session blockers
- **FE-010 — ✅ DONE.** `UserMenu.tsx` logout now calls `queryClient.clear()` after `clearSession()`+`reset()`, before redirect.

### 6. Branch context blockers
- **FE-009 — ✅ DONE.** `BranchProvider.switchBranch` calls `queryClient.clear()` after the new token is set.

### 7. Permission-gate mismatches — ✅ DONE (2026-06-13)
- **FE-011 — ✅** `AccessDenied` + `RequireRole` guard + `/forbidden` route; wired into `/cutting`.
- **FE-012 — ✅** `landingRouteForRoles()` in login + 2FA (Owner/Admin keep `/dashboard`).
- **FE-013 — ✅** all 14 role constants in `ROLES`. (Admin-page hardcoded-array dedupe remains optional polish.)

### 8. Idempotency / double-submit mismatches
- **FE-007 — ✅ DONE (high-risk hooks).** `lib/api/idempotency.ts` `useStableIdempotencyKey()` (in-memory ref, rotates on success) threaded into `useCreateOrder`, `useAddOrderItem`, `useTransitionItem`, `useCreateInvoice`, `useRecordPayment`, `useIssueCreditNote`, `useConfirmDelivery`. All target hooks now exist (cutting via FE-002; rack/qc/tailoring via FE-015) and use stable keys. Test shipped: `e2e/idempotency-double-submit.spec.ts` (FE-017).
- **FE-015 — ✅ DONE (2026-06-13).** Added `hooks/useRack.ts` (slots/current-slot/assign/release), `hooks/useQc.ts` (history/defect-categories/inspect/rework-override), `hooks/useTailoring.ts` (assignments/create/start/complete/reassign) — all using `useStableIdempotencyKey` + consistent invalidation of `productionBoard` and their own keys. (Cutting hooks already shipped in FE-002.)

### 9. Front Desk workflow gaps
- Depends on FE-005 (job card) + FE-006 (QR scan) above. Verify family-member CRUD + duplicate-phone 422 mapping live.

### 10. Measurement / order gaps
- **FE-014 — ✅ DONE (2026-06-13).** `useCreateOrder` → invalidates `customer(customer_id)`; `useAddOrderItem` → invalidates `orders()`; `useTransitionItem` → also invalidates `auditTransitions(itemId)`.

### 11. Production / cutting / QC gaps
- **FE-002 — ✅ DONE (2026-06-12).** `useCutting.ts` (queue/allocate/release/start/complete/bundle/bundleByQr) + rewritten `cutting/page.tsx` drive the real cutting endpoints with stable keys + correct invalidations; transition shortcut removed. `e2e/cutting-flow.spec.ts` added. Verified: type-check + build green.
- **FE-022 — ✅ DONE.** Cutting hooks now at component top level (RowActions/AllocateDrawer/CompleteDrawer).
- Still open: production item-history timeline (#16); tailor start/complete + ironing actions (#21/#24); QC photo upload wiring.

### 12. Inventory / finance / delivery gaps
- **FE-014 — ✅ DONE (2026-06-13).** `useCreateInvoice` + `useIssueCreditNote` now invalidate `financeDashboard()`; delivery/payment hooks already invalidated orders. (No invoice edit/delete — keep, rule 27.)

### 13. Query invalidation gaps
- Covered by FE-009/010/014 above. **FE-021 — ✅ DONE (2026-06-13):** added `customerByQr`, `measurementVersion`, `cuttingBundle`, `bundleByQr` to `lib/query/keys.ts`; replaced the inline array keys in `useCustomers`/`useMeasurements` with the factory.

### 14. Playwright coverage gaps
- **FE-017 — ✅ DONE (2026-06-13).** Added `e2e/{branch-switch,customer-qr,qc-rework,delivery-otp,idempotency-double-submit,error-request-id}.spec.ts`. Each asserts the relevant contract against the seeded backend (idempotency replay + IDEMPOTENCY_CONFLICT; QR sign→lookup round-trip + tampered-payload rejection; OTP wrong-code rejection; QC role-guard; `request_id` on 404/422). `mobile-flow.spec.ts` also added (Pixel-5 viewport smoke for owner + cutter landing). **Executed green against the live stack (2026-06-13): 12 passed / 0 skipped / 0 failed.** The customer-qr happy-path round-trip was dropped as API-untestable (a customer's `qr_payload` is a scan-only opaque token, not exposed by `CustomerResource` — see FE-021 note); the tamper-rejection contract is asserted instead. Surfaced **FE-001b** (prod-runtime `/login` manifest 500).

### 15. UX / accessibility polish
- **FE-020 — ✅ DONE (2026-06-13).** Added `frontend/.env.example` (`NEXT_PUBLIC_API_URL`, `E2E_API_URL`). Storybook dropped from the stack (not in deps — no action). Remaining polish (reduced-motion guard, family-member CRUD UI, notifications page) are net-new features, out of scope.

## Risk-ordered summary
| Order | ID | Severity | Effort | Risk |
|--|--|--|--|--|
| 1 | FE-001 | Blocker | S | low |
| 2 | FE-005, FE-006 | High | S | low |
| 3 | FE-003, FE-004 | High | S (needs backend decision) | low |
| 4 | FE-002 | High | M | med (workflow rewire) |
| 5 | FE-007 | High | M | med |
| 6 | FE-008 | High | M | low |
| 7 | FE-009, FE-010 | High | S | low |
| 8 | FE-011…FE-017, FE-024 | Medium | M | low |
| 9 | FE-018…FE-023 | Low | S | none |

## Suggested verification after fixes 1–7
```
cd frontend
npm run type-check && npm run lint && npm run build   # all green
npx playwright test e2e/idempotency-double-submit.spec.ts e2e/customer-qr.spec.ts
```
</content>
