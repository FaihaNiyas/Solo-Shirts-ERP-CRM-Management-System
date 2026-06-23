# Backend Fix Plan — Solo Shirts India ERP

> **✅ FINAL STATUS — 2026-06-12 (post-hardening):** Pest **309 passed / 0 failed** · Pint **clean** · PHPStan **0 errors** · QA-001 **fixed** · QA-002 finance **fixed** · **0 blocker issues** · **Backend ready for frontend integration.** Every code-level item in this plan is ✅ done; only env items (PHP 8.3 + Redis locally) remain.

**Date:** 2026-06-12 · **Order follows the QA brief's fix priority. Test-first for each item.**
**No code has been changed.** This is the recommended sequence only.

> There are **no boot/migration/auth/branch/permission blockers** — those layers are green. The plan starts at the first real defects: a data-integrity/security bug (QA-001) and an idempotency gap (QA-002), then environment parity, then cosmetics and coverage.

---

### 1. QA-001 — OTP `expires_at` ON-UPDATE reset *(High — data integrity / security)* — ✅ DONE 2026-06-12
- **Files changed:** added migration `app/Modules/Delivery/Database/Migrations/2026_06_12_000000_alter_delivery_otps_expires_at_drop_on_update.php`; added regression assertion in `tests/Feature/Delivery/WrongOtpIncrementsAttemptsTest.php`.
- **Test first (TDD):** added `expect($otp->expires_at->equalTo($issuedExpiry))->toBeTrue()` — failed first (`false is true`, line 40), passed after fix.
- **Fix applied:** `expires_at` redefined as `DATETIME` (no implicit `ON UPDATE CURRENT_TIMESTAMP`); `ALTER … MODIFY` preserved existing values; raw OTP still never stored.
- **Verified:** `pest tests/Feature/Delivery/` → 13 passed (68 assertions); `SHOW COLUMNS` → `type=datetime, extra=""`.
- **Audit follow-up — ✅ ALSO DONE (2026-06-12):** the whole `ON UPDATE` column class was eliminated. Migration `2026_06_12_000001_drop_on_update_from_first_timestamp_columns.php` converted **all 14** first-`TIMESTAMP` columns (incl. the latent-risk `invoices.issued_at`, `fabric_allocations.reserved_at`, `rack_assignments.assigned_at`, `tailor_assignments.assigned_at`, `report_jobs.requested_at`, `delivery_attempts.attempted_at`) to `DATETIME`. Schema now has **zero** `ON UPDATE` columns, guarded by `Shared/NoUnintendedOnUpdateTimestampsTest`.

### 2. QA-002 — Idempotency-Key on writes *(High for finance, Medium overall)* — ✅ FULLY DONE 2026-06-12
- **Files changed:** `routes/api.php` (added `->middleware('idempotent')` to invoice-create, credit-note-create, **and add-order-item**); added `Finance/InvoiceIdempotentTest`, `Finance/CreditNoteIdempotentTest`, `Order/IdempotentAddItemTest`; updated `InvoiceGenerationTest`/`CreditNoteCreatesCreditNoTest` to send an `Idempotency-Key`.
- **Test first (TDD):** new tests failed first (replays duplicated / conflicts returned 201), passed after the route change.
- **Verified:** `pest tests/Feature/Finance/` → 29 passed; full idempotency policy documented in `BACKEND_IDEMPOTENCY_REPORT.md`.
- **Complete:** every write is now duplicate-safe — `idempotent` middleware where a new row/number is minted; state-guard or DB-unique constraint elsewhere (`qc inspect`, `measurement approve`, `rack assign`, `cutting actions`, `customer create`). **No unprotected write remains.**
- **Risk:** low; middleware is battle-tested. Frontend must send `Idempotency-Key` on the idempotent writes.

### 3. QA-003 — PHP 8.3 parity — ✅ CI DONE / local pending
- **CI:** `.github/workflows/ci.yml` already pins `php-version: '8.3'` (both jobs) on `mysql:8.0` — verified, no change needed.
- **Local:** dev machine on XAMPP PHP 8.2.12; team to install PHP 8.3+ for parity (env action, outside edit scope).

### 4. QA-004 — Pint *(Low)* — ✅ DONE
- `./vendor/bin/pint --test` → `passed` (the 4 files were reformatted). Verified clean.

### 5. QA-005 — PHPStan *(Low)* — ✅ DONE
- `./vendor/bin/phpstan analyse` → **No errors** (was 6). Verified after `clear-result-cache`.

### 6. QA-006 — Test coverage *(Low)* — ✅ DONE
- Added (all green): `Security/PermissionNegativeFlowTest` (6), `Shared/IdempotencyFullFlowTest` (5), `Flow/FullFrontDeskToDeliveryFlowTest` (1 / 37 assertions), `Order/IdempotentAddItemTest` (2), `Shared/NoUnintendedOnUpdateTimestampsTest` (1), and unit tests `Shared/CodeGeneratorTest` (3) + `Order/OrderStatusDeriverTest` (5). Centralised `orderPayload`/`invoicePayload` in `tests/Pest.php`.
- **Optional (nice-to-have only):** the remaining `Flow/*` per-stage journeys (Owner dashboard, cutting-stock) for extra regression depth — every stage is already covered piecemeal + by the end-to-end journey.

### 7. QA-007 — Redis (env, Low) — start Redis locally / confirm in prod; no code change.

---

## Suggested verification command after fixes 1–2
```
./vendor/bin/pest tests/Feature/Delivery tests/Feature/Finance --colors=never
./vendor/bin/pint --test
./vendor/bin/phpstan analyse --no-progress
./vendor/bin/pest   # full suite — expect 0 failed
```

## Risk-ordered summary
| Order | ID | Severity | Effort | Risk of fix |
|--|--|--|--|--|
| 1 | QA-001 ✅ done | High | S (1 migration + assert) | Low |
| 2 | QA-002 ✅ finance done | High(fin)/Med | S (2 routes + 2 tests) | Low |
| 3 | QA-003 ✅ CI / local pending | Medium | S (env/CI) | Low |
| 4 | QA-004 ✅ done | Low | XS | None |
| 5 | QA-005 ✅ done | Low | XS | None |
| 6 | QA-006 ✅ done | Low | M (new tests) | None |
| 7 | QA-007 (env) | Low | XS (env) | None |

## Hardening pass (2026-06-12) — to reach 100
- **ON-UPDATE column class — ✅ done:** all 14 first-`TIMESTAMP` columns converted to `DATETIME` (incl. the genuinely-risky `invoices.issued_at`); schema now has **zero** `ON UPDATE` columns, guarded by `NoUnintendedOnUpdateTimestampsTest`.
- **QA-002 idempotency — ✅ fully closed:** `add-order-item` now idempotent (`Order/IdempotentAddItemTest`); every other write proven duplicate-safe by state guard / DB unique constraint (policy table in `BACKEND_IDEMPOTENCY_REPORT.md`).
- **Unit coverage — ✅ done:** `Shared/CodeGeneratorTest` (3), `Order/OrderStatusDeriverTest` (5).

## Remaining (env / nice-to-have only — no code defects)
- **QA-003 (env):** install PHP 8.3+ on dev machines (CI/prod already on 8.3).
- **QA-007 (env):** run Redis locally for full health-endpoint parity (CI/prod have Redis).
- **Coverage (optional):** remaining `Flow/*` per-stage journeys (Owner dashboard, cutting-stock, tailoring-qc-rework) for extra regression depth.
