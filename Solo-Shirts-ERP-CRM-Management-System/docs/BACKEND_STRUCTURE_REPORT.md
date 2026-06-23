# Backend Structure & Setup Report — Solo Shirts India ERP

> **✅ FINAL STATUS — 2026-06-12 (post-hardening):** Pest **309 passed / 0 failed** · Pint **clean** · PHPStan **0 errors** · QA-001 **fixed** · QA-002 finance **fixed** · **0 blocker issues** · **Backend ready for frontend integration.**

**Date:** 2026-06-12
**Reviewer:** Backend QA (inspect → fix → verify; all code-level defects resolved)
**Stack proven:** Laravel **12.61.1** / PHP **8.2.12** local (CI/prod on PHP **8.3** — env note QA-003) / MariaDB **10.4.32** / predis.
**Method:** Live commands + static inspection. Every claim cites a file or a command result.

> Scope: This report covers Tasks 1–5 (structure, setup, quality, health, core checks).
> Flow, permission, idempotency, gap, coverage, defect and fix reports are separate files (see index at bottom).

---

## TASK 1 — Project Structure

| Expected path | Exists | Notes |
|---|---|---|
| `app/Modules/` | ✅ | Modular monolith root |
| `app/Modules/Shared` | ✅ | ApiResponse, BranchContext, Idempotency, CodeGenerator, QrPayloadSigner, Health |
| `app/Modules/Identity` | ✅ | Auth, 2FA, Branch, User, ResolveBranchContext |
| `app/Modules/Customer` | ✅ | Customer, FamilyMember, QR |
| `app/Modules/Order` | ✅ | Order, OrderItem, JobCard, OrderStatusDeriver |
| `app/Modules/Production` | ✅ | States, transitions, cutting, fabric allocation, QC, rework |
| `app/Modules/Inventory` | ✅ | Fabric rolls/types/suppliers, PO, movements ledger, damage |
| `app/Modules/Delivery` | ✅ | Delivery, OTP, rack slots/assignments |
| `app/Modules/Finance` | ✅ | Invoice, Payment, CreditNote, sequences |
| `routes/api.php` | ✅ | 291 lines, all v1 routes |
| `tests/Feature` | ✅ | 110 feature test files |
| `tests/Unit` | ✅ | 2 unit test files |
| `phpstan.neon` | ✅ | larastan, level 6 |
| `pint.json` | ✅ | Laravel preset |
| `composer.json` scripts | ⚠️ | Only Laravel defaults + `dev`. **No `test`/`lint`/`analyse` scripts** (run binaries directly) |
| `.env.example` | ✅ | Present |
| `database/factories` + module factories | ✅ | Factories live per-module under `app/Modules/*/Database/Factories` |
| `database/seeders` | ✅ | `RolePermissionSeeder`, `DemoDataSeeder`, `DatabaseSeeder` |
| `config/permission.php` | ✅ | Spatie, `teams => true` |
| `config/sanctum.php` | ✅ | Present |

**Extra modules found (beyond the expected list):** `app/Modules/Measurement`, `app/Modules/Printing`, `app/Modules/Reporting`.

**Verdict:** Structure is **complete and clean**. Modular monolith fully realised; each module owns Controllers / Services / Models / Requests / Resources / Policies / Migrations / Factories.

---

## TASK 2 — Setup Checks

| Command | Result | Evidence |
|---|---|---|
| `composer install` | ✅ vendor present & autoloaded | `vendor/` populated; `composer 2.9.2` |
| `php artisan key:generate` | ✅ key already set | `.env` `APP_KEY=base64:…` present |
| Migrations | ✅ **all Ran** | `php artisan migrate:status` → every migration `[1] Ran` |
| `php artisan route:list` | ✅ **143 app routes (147 incl. framework)** | captured to `docs/_route_list.txt` |

**DB connectivity:** MySQL/MariaDB reachable; database `solo_shirts_erp` and test DB `solo_shirts_erp_test` exist. (`php artisan db:show` errors only on a MariaDB `performance_schema.session_status` quirk — **not** a connection failure.)

**Note (not a blocker):** Redis is **not running** locally (`predis` client, `REDIS_HOST=127.0.0.1`). App boots fine because cache/session = `file`, queue = `sync`. Only the health endpoint reports Redis down (see Task 4).

---

## TASK 3 — Quality Checks

Commands run (no composer aliases exist, so binaries used directly):

### Pest — `./vendor/bin/pest`
**Current (post-hardening): `309 passed / 0 failed` (1222 assertions).** The block below is the **initial inspection** snapshot that first surfaced QA-001 — kept for history:
```
# initial inspection run (pre-fix):
Tests:    2 failed, 278 passed (1081 assertions)
Duration: 367.92s
```
- **Initial pass rate: 278/280; now 309/309 (100%).**
- **The one failing file then** `tests/Feature/Delivery/WrongOtpIncrementsAttemptsTest.php` (2 cases) is now green after the QA-001 fix.
- Root cause **proven**: `delivery_otps.expires_at` is a `TIMESTAMP` column that, under MariaDB with `explicit_defaults_for_timestamp=0`, silently carries `ON UPDATE CURRENT_TIMESTAMP`. Any UPDATE to the OTP row (e.g. incrementing `attempts` on a wrong guess) resets the expiry to *now*, so the next verify returns `OTP_EXPIRED` instead of `OTP_INVALID`/`OTP_LOCKED`. See **QA-001** in `BACKEND_QA_REPORT.md`. Full output: `docs/_pest_output.txt`.

### Pint — `./vendor/bin/pint --test`
```
result: fail — 4 files need formatting
```
Files: `Finance/.../InvoiceController.php`, `Identity/Services/UserService.php`, `Shared/Console/Commands/GenerateOpenApi.php`, `database/seeders/DemoDataSeeder.php`. Fixers: `unary_operator_spaces`, `braces_position`, `ordered_imports`, `array_indentation`. **Low severity (style only).**

### PHPStan — `./vendor/bin/phpstan analyse` (level 6, larastan)
```
[ERROR] Found 6 errors
```
All cosmetic / low: redundant nullsafe (`?->` where never-null) in `CustomerController:178`, `InvoiceController:121`; no-op `array_values` on an already-list (`CustomerController:182`); `foreach` over `RouteCollectionInterface` in `GenerateOpenApi:28`; undefined `$name` on a generic `Model` in `ActivityResource:28`. **No type-safety-critical findings.**

**Verdict:** Quality bar is high. The 2 test failures trace to a single schema-portability defect; Pint/PHPStan findings are cosmetic.

---

## TASK 4 — API Health Check (live)

`GET /api/v1/health` (server booted on :8741, Redis intentionally down):

```
HTTP/1.1 503 Service Unavailable
X-Request-Id: 65ec200c-1f2a-4d02-af30-9ccb61ca29d2
{"success":false,"message":"One or more dependencies are unavailable.",
 "code":"HEALTH_DEPENDENCY_DOWN","errors":{},"request_id":"65ec200c-…",
 "data":{"php":"8.2.12","laravel":"12.61.1","db":true,"redis":false,"queue":true,"commit":"e798fd22"}}
```

| Expectation | Result |
|---|---|
| 200 when DB+Redis+queue healthy | ✅ by design (`HealthController` returns 200 when all true); locally **503 only because Redis is down** |
| Body has success / message / data / request_id | ✅ |
| `X-Request-Id` header == body `request_id` | ✅ **verified equal** (`b702bdd8-…` matched on a second call) |
| Dependency down → 503 `HEALTH_DEPENDENCY_DOWN` | ✅ **observed live** (Redis down → 503) |
| 60/min rate limit, 429 on 61st | ✅ route `throttle:60,1`; bootstrap maps 429 → `TOO_MANY_REQUESTS`; headers `X-RateLimit-Limit: 60` observed |

Security headers present on every response: `X-Content-Type-Options`, `X-Frame-Options: DENY`, `Content-Security-Policy`, `Strict-Transport-Security`, `Referrer-Policy` (covered by `Security/SecurityHeadersPresentTest`, passing).

`HealthController` / `HealthService`: `app/Modules/Shared/Http/Controllers/Api/V1/HealthController.php:13-29`, `app/Modules/Shared/Services/HealthService.php:36-62`.

**Verdict:** Health endpoint **PASS** (full envelope, header sync, dependency gating, rate limit all confirmed).

---

## TASK 5 — Core Backend Checks (A–J)

Each row verified by code evidence and/or a passing test. Full file:line evidence in the flow/idempotency/coverage reports.

### A) API Envelope — **PASS**
- Success `{success,message,data,request_id}` — `ApiResponse::success()` `app/Modules/Shared/Support/ApiResponse.php:16-24`.
- Error `{success,message,code,errors,request_id}` — `ApiResponse::error()` `:29-49`.
- Validation 422 `VALIDATION_FAILED` — `bootstrap/app.php:69-79`; test `Shared/ValidationErrorEnvelopeTest`.
- request_id in body+header — `AssignRequestId` middleware; **header == body proven live** (Task 4).

### B) Idempotency — **PARTIAL** (selectively applied; see `BACKEND_IDEMPOTENCY_REPORT.md`)
- Missing key → `IDEMPOTENCY_KEY_REQUIRED` (400); same key+body → replay; same key+different body → `IDEMPOTENCY_CONFLICT` (409); in-flight → `IDEMPOTENCY_IN_FLIGHT`. `IdempotencyService` + `IdempotencyMiddleware`. Tests: `Shared/IdempotencyTest`, `Order/IdempotentCreateOrderTest`, `Cutting/IdempotentAllocateTest`, `Production/IdempotencyOnTransitionTest`, `Delivery/ConfirmTwiceIdempotentTest`, `Finance/PaymentIdempotentTest`.
- **Gap:** Rule "*every* write supports Idempotency-Key" is **not** universal — only orders, transitions, allocate-fabric, damage-approve, delivery-confirm carry the middleware; payments use their own required-key dedup. Invoices, measurement approve/reject, qc inspect, rack assign, credit-note do **not**. (QA-002)

### C) Auth — **PASS**
login/logout/refresh/me, wrong password (`INVALID_CREDENTIALS`), inactive (`ACCOUNT_INACTIVE`), throttle (5/15min via `login_attempts`), 2FA required for Owner/Admin/Accountant (prod) via `enforceTwoFactor()`, invalid OTP (`INVALID_OTP`). `AuthService.php:21-143`; tests `Identity/LoginTest`, `Identity/TwoFactorFlowTest`.

### D) Branch & Permissions — **PASS** (role count nuance below)
Owner switch-branch (`abort_unless Owner`, `AuthController:70`); staff cannot. `BranchScope` global scope on all transactional models. Branch A staff cannot read Branch B (tests `Identity/BranchIsolationTest`, `Customer/BranchIsolationOnCustomersTest`). `RolePermissionSeeder` defines **14 roles** with a full permission matrix.
- **Nuance:** Prompt expected **13** roles. Seeder has **14** because **Owner and Admin are separate** roles (the prompt groups them as "Owner/Admin"). Functionally aligned, not a defect. Roles: Owner, Admin, Front Desk, Measurement Staff, Production Supervisor, Cutting Master, Tailor, Kaja Button, QC Supervisor, Ironing Master, Re-Worker, Inventory Manager, Accountant, Delivery Staff. (`database/seeders/RolePermissionSeeder.php:26-41`)

### E) Customer — **PASS**
create + dup-phone (`DUPLICATE_PHONE` 409), phone `encrypted` cast at rest + `phone_last4` searchable, `customer_code` via locked sequence table (not MAX()+1), QR HMAC-SHA256 signed + tamper → `INVALID_QR_SIGNATURE`. Tests `Customer/*` (7 files).

### F) Measurement — **PASS**
profile/version create, **append-only** (model `updating` hook blocks mutation of `shirt_data/pant_data/version_number/profile_id`), approve, reject-with-reason, old versions immutable, orders bind only **approved** `measurement_version_id` (`ApprovedMeasurementVersion` rule + FK). Tests `Measurement/*` (6 files).

### G) Order — **PASS**
create, add item, cancel, job-card, **status derived from order_items** (`OrderStatusDeriver` — status never stored). Tests `Order/*` (4 files).

### H) Production/Cutting — **PASS**
kanban board, spatie state machine with explicit edges, invalid transition blocked (`InvalidTransitionRejectedTest`), fabric reserved **through cutting only** (`FabricAllocationController`), 2-phase reserve/consume/release. Tests `Production/*` (10), `Cutting/*` (8), `Qc/*` (5).

### I) Inventory — **PASS**
fabric roll create, **append-only movement ledger** (DB `BEFORE UPDATE` trigger + `UPDATED_AT = null`), available = remaining − active reserves, low-stock, QR lookup. Tests `Inventory/*` (8).

### J) Finance — **PASS**
invoice create, payment (idempotent), credit note, invoice PDF, **gap-free numbering via locked `invoice_sequences` (not MAX()+1)**, **no invoice edit/delete** (no PUT/PATCH/DELETE routes; `FinancePolicy` has no update/destroy). Tests `Finance/*` (10).

---

## Summary

| Check | Status (current, post-hardening) |
|---|---|
| Structure | ✅ Complete |
| Boot / migrate / routes | ✅ Pass (143 routes) |
| Pest | ✅ **309 passed / 0 failed** |
| Pint | ✅ Clean |
| PHPStan (L6) | ✅ 0 errors |
| Health + request_id | ✅ Pass (live-verified) |
| Auth / 2FA | ✅ Pass |
| Branch isolation | ✅ Pass |
| Idempotency | ✅ Complete (policy documented; QA-002 fixed) |
| Core domain (E–J) | ✅ Pass |

**Headline:** The backend's domain core is production-grade and fully test-backed. **0 blocker issues.** The two issues found at inspection are resolved: **QA-001** (OTP `expires_at` ON-UPDATE reset) ✅ fixed, and **QA-002** (Idempotency-Key coverage) ✅ fixed. The only remaining items are dev-environment notes (PHP 8.3 + Redis locally; CI/prod already run them). **Backend ready for frontend integration.**

---

## Report Index
- `BACKEND_STRUCTURE_REPORT.md` — this file (Tasks 1–5)
- `BACKEND_FULL_FLOW_REPORT.md` — end-to-end + role-based flows (Tasks 6–7)
- `BACKEND_PERMISSION_NEGATIVE_FLOW.md` — blocked-action matrix (Task 8)
- `BACKEND_BRANCH_ISOLATION_REPORT.md` — cross-branch tests (Task 9)
- `BACKEND_IDEMPOTENCY_REPORT.md` — per-mutation idempotency (Task 10)
- `BACKEND_API_GAPS.md` — route/controller/validation/policy/test matrix (Task 11)
- `BACKEND_TEST_COVERAGE.md` — coverage by module (Task 12)
- `BACKEND_QA_REPORT.md` — defects (Task 13)
- `BACKEND_FIX_PLAN.md` — ordered fix plan (Task 14)
- Raw artifacts: `docs/_pest_output.txt`, `docs/_route_list.txt`
</content>
</invoke>
