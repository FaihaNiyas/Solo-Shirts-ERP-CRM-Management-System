# Playwright Coverage Against Backend Flow — Solo Shirts India ERP

**Date:** 2026-06-12 (updated 2026-06-13) · Specs: `frontend/e2e/*.spec.ts` (now 16) + `e2e/helpers.ts` + `playwright.config.ts`.
**Config:** baseURL `http://localhost:3000`, backend assumed at `http://localhost:8000`, **real backend** (helpers log in + seed); workers=1, retries=1, **Desktop Chrome only**. *Tests were NOT executed here* (require both live servers + seeded demo data); reviewed statically.

> **✅ FE-017 gap closed + EXECUTED GREEN (2026-06-13)** — added 7 contract specs (`idempotency-double-submit`, `error-request-id`, `branch-switch`, `customer-qr`, `delivery-otp`, `qc-rework`, `mobile-flow`) and **ran them against the live stack** (Laravel `:8000` + `next dev :3000` + `DemoDataSeeder`): **12 passed / 0 skipped / 0 failed (40.9s)**. They fill the previously-empty **request_id**, **Idempotency double-submit**, QR/OTP/branch, and mobile columns below.
>
> **Two findings surfaced while executing:**
> 1. **customer-qr happy-path is API-untestable** — a customer's `qr_payload` is a pre-generated opaque token (`qr_…`) minted at creation, matched by `CustomerService::findByQr` against the stored column; it is **not** the `qr/sign` `{type,id}` document and is **not exposed by `CustomerResource`** (scan-only). So the sign→lookup round-trip can't be driven via the public API (it's exercised by the in-app scanner). The spec now asserts only the security-critical contract: a tampered/unsigned payload → structured error + `request_id`. ✅
> 2. **FE-001 is fixed for `build`/`next dev` but `next start` (production runtime) still 500s on `/login`** — `Could not find the module ".../(auth)/login/page.tsx#default" in the React Client Manifest` (reproduced after a clean `.next` rebuild). The static build + dev server render `/login` fine; only the production server's RSC SSR of the `force-dynamic` auth layout fails. **Tracked as a follow-up (FE-001b)** — the e2e suite runs against `next dev` as the config intends.
>
> **How to reproduce the run:** seed (`php artisan db:seed --class=DemoDataSeeder`), start backend (`php artisan serve`), start `npm run dev`, then `E2E_API_URL=http://localhost:8000 npx playwright test --workers=1`. Note: `php artisan serve` is single-threaded on Windows — UI-login specs need warm routes (`next dev` first-compile) to avoid the 20s `waitForURL` timeout; pre-warm with a `curl` of `/login`,`/dashboard` first.

## Existing specs (13) → real backend & assertions
| Spec | Covers | Real backend? | Envelope/request_id | RBAC 403 | Idempotency |
|--|--|--|--|--|--|
| `auth.spec.ts` | login valid/invalid, tailor login | ✅ via UI | ❌ | ❌ | ❌ |
| `admin.spec.ts` | branch/user list, create user, RBAC | ✅ | ✅ `success` (not request_id) | ✅ 403 users (Tailor) | ❌ |
| `rbac.spec.ts` | Tailor blocked from admin/branches/users/audit | ✅ | ❌ | ✅ | ❌ |
| `navigation.spec.ts` | 10 routes render h1 | FE only | ❌ | ❌ | ❌ |
| `customers.spec.ts` | list/search, 360, create, 401 | ✅ | ✅ `success`/`data` | ⚠️ | ❌ |
| `orders.spec.ts` | list/detail/create (Idempotency-Key header) | ✅ | ✅ `success`,`data.items` | ❌ | ⚠️ key sent, not double-submit |
| `production.spec.ts` | board/cutting/tailoring/qc/rack render per role | ✅ | ❌ | ⚠️ role access | ❌ |
| `finance.spec.ts` | invoices/outstanding, generate/payment/credit, RBAC | ✅ | ✅ `success` | ✅ 403 (Front Desk) | ❌ |
| `inventory.spec.ts` | fabric rolls, RBAC | ✅ | ✅ `success` | ✅ 403 (Tailor) | ❌ |
| `damage.spec.ts` | damage reports, Owner-only approve | ✅ | ❌ | ✅ owner-only buttons | ❌ |
| `api-smoke.spec.ts` | health, login all roles, /auth/me, KPIs, reports, audit, search, notifications, QR | ✅ direct HTTP | ✅ `success`/`data` extensive | ✅ 403 tests | ❌ |
| `workflows.spec.ts` | global search, customer 360, admin create | ✅ | ❌ | ❌ | ❌ |
| `data.spec.ts` | seeded-data sanity | ✅ | ⚠️ | ❌ | ❌ |

## Expected specs (prompt) → exists/missing
| Expected spec | Status | Closest existing |
|--|--|--|
| auth | ✅ | `auth.spec.ts` |
| branch-switch | ❌ Missing | (admin branch mgmt only; no switch+context test) |
| permission-negative | ✅ | `rbac.spec.ts` + 403s in admin/finance/inventory |
| front-desk-flow | ⚠️ Partial | `customers.spec.ts` (search); no end-to-end front-desk order |
| customer-qr | ❌ Missing | (QR not e2e-tested; and FE-006 endpoint is broken anyway) |
| measurement-flow | ⚠️ Partial | approvals touched; no version create/approve e2e |
| order-flow | ✅ | `orders.spec.ts` (api-level) |
| production-flow | ⚠️ Partial | `production.spec.ts` renders only; no transitions clicked |
| cutting-flow | ⚠️ Partial | renders; no allocate/cut actions (and FE-002) |
| qc-rework-flow | ❌ Missing | QC renders; no inspect+rework e2e |
| inventory-flow | ⚠️ Partial | list+RBAC; no PO place/receive e2e |
| finance-flow | ⚠️ Partial | list+forms+RBAC; no full invoice→payment txn |
| delivery-flow | ❌ Missing | no dispatch→OTP confirm e2e |
| idempotency-double-submit | ❌ Missing | key sent in helpers; **no double-submit rejection test** |
| error-request-id | ❌ Missing | errors checked, **request_id never asserted** |
| mobile-flow | ❌ Missing | Desktop Chrome only (no mobile viewport project) |

## Findings (FE-017, Medium — Test gaps)
- ✅ Good foundation: 13 specs hit a **real backend**, assert `success===true` in several, and cover **RBAC 403** across admin/finance/inventory/damage.
- **Gaps:** no test asserts **`request_id`**; no **double-submit/idempotency** test; no **branch-switch**, **customer-qr**, **qc-rework**, **delivery-otp**, **mobile** specs; production/cutting specs only render pages without exercising transitions. The cutting flow can't be meaningfully tested until FE-002 is resolved.
- Many flows are tested at the **API layer** in `api-smoke.spec.ts` (good) but not through the **UI actions** (limited button-click coverage).

**Verdict:** solid real-backend RBAC/smoke coverage; **missing** the contract-critical assertions (request_id, idempotency double-submit) and several end-to-end UI flows.
</content>
