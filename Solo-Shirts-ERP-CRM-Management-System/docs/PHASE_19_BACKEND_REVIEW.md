# Phase 19 — Final Backend Architecture Review

> **⚠️ HISTORICAL — pre-final-hardening (2026-06-09).** This review predates the QA inspection-and-fix pass of **2026-06-12**. Its **83/100** score and any "needs verification / must fix before go-live" notes reflect the state *before* QA-001 and QA-002 were fixed and the idempotency / ON-UPDATE-timestamp hardening was completed. **For the current state see `BACKEND_QA_FULL_REPORT.md` and `BACKEND_QA_REPORT.md`:** Pest **309 passed / 0 failed**, Pint clean, PHPStan 0, **0 blocker issues**, backend ready for frontend integration. This file is retained for architectural history only.

**System:** Solo Shirts India ERP — Laravel 12 modular-monolith API backend
**Reviewed:** 2026-06-09, after Phases 1–18 *(historical snapshot — see banner above)*
**Method:** Evidence-based. Each area cites concrete files and tests. Where a property
could not be proven from code/tests, it is marked **needs verification** rather than assumed.

> Scope note: this review does not modify code (per the Phase 19 contract). It records
> what the implementation proves, what is risky, and what must be fixed before go-live.

---

## Production Readiness Score: **83 / 100**

| # | Area | Score |
|---|------|------:|
| 1 | Modular monolith structure | 5/5 |
| 2 | API-first architecture | 3/5 |
| 3 | Authentication & authorization | 3/5 |
| 4 | Role & permission coverage | 4/5 |
| 5 | Multi-branch support | 5/5 |
| 6 | Customer & measurement design | 5/5 |
| 7 | Order workflow | 5/5 |
| 8 | Production transition safety | 5/5 |
| 9 | Inventory stock accuracy | 5/5 |
| 10 | Fabric roll locking & 2-phase reservation | 4/5 |
| 11 | QC & rework correctness | 4/5 |
| 12 | Delivery & rack slot correctness | 5/5 |
| 13 | Finance data security | 5/5 |
| 14 | Reports & dashboard performance | 4/5 |
| 15 | Audit log completeness | 4/5 |
| 16 | Validation & error handling | 5/5 |
| 17 | Test coverage | 3/5 |
| 18 | Database indexes | 4/5 |
| 19 | Queue & scheduler setup | 3/5 |
| 20 | Deployment readiness | 2/5 |
| | **Total** | **83/100** |

The backend's **domain core is production-grade**: ledgers, state machines, gap-free
numbering, DB-enforced invariants, and branch isolation are all correct and tested. The
gaps are **operational/observability** (Horizon, Sentry, OpenAPI, measured coverage,
2FA enforcement) — deliberate local-stack substitutions that must be closed before go-live.

---

## 1. Modular monolith structure — ✅ Strong
- **Correct:** Clean `app/Modules/{Identity,Customer,Measurement,Order,Production,Inventory,Delivery,Finance,Printing,Reporting,Shared}` boundaries, PSR-4 `App\Modules\`. Each module owns migrations under `Database/Migrations`, auto-discovered by `AppServiceProvider::loadModuleMigrations()`. Cross-module coupling goes through seams (`StockLedgerInterface`, `NotificationDispatcher`, events).
- **Risky:** A few cross-module reads bypass seams (e.g. `DefectAnalyticsReport` uses raw `DB::table('qc_defects')` joins). Acceptable for read-only reports but couples to another module's schema.
- **Must fix:** None.
- **Improve:** Consider a `ModuleServiceProvider` per module to localize bindings/policies (currently all in `AppServiceProvider`).
- **Priority:** Low.

## 2. API-first architecture — ⚠ Adequate
- **Correct:** `/api/v1` versioned prefix; uniform envelope via `ApiResponse`; `request_id` propagated by `AssignRequestId` and echoed in body + `X-Request-Id` (`HealthCheckTest`); machine-readable error codes via the global handler in `bootstrap/app.php`; idempotency via the `idempotent` middleware + `idempotency_keys` table.
- **Risky:** **Idempotency is not universal.** Only `orders.store`, production `transition`, `allocate-fabric`, `damage-reports/approve`, and `deliveries/confirm` carry the middleware; payments self-dedupe on a column. Many writes (`customers`, `branches`, `users`, `fabric-types`, `suppliers`, rack/slot create) are **not** idempotent, contradicting the "all write endpoints" invariant.
- **Must fix:** No **OpenAPI/Swagger** spec exists — clients have no contract. **needs verification** that any consumer relies on it; for go-live it should exist.
- **Improve:** Generate OpenAPI from routes/resources; add `Idempotency-Key` to the remaining unsafe writes (or document the deliberate subset).
- **Priority:** Medium (OpenAPI), Medium (idempotency coverage).

## 3. Authentication & authorization — ⚠ Adequate
- **Correct:** Sanctum bearer tokens; `auth:sanctum` on all protected routes; `ResolveBranchContext` per request; policies registered via `Gate::policy`, Owner bypass via `Gate::before`; 2FA endpoints exist (`TwoFactorController` enable/confirm/disable, Phase 3).
- **Risky:** **2FA enforcement** — the invariant "2FA mandatory for Owner/Admin/Accountant" is **needs verification**: enable/confirm endpoints exist, but a login-time guard that *rejects* those roles without confirmed 2FA was not located. Token **expiry** and **role-change token revocation** were not found — **needs verification / likely missing**.
- **Must fix:** Enforce 2FA at login for privileged roles; revoke tokens on role change; set Sanctum token TTL.
- **Improve:** Short-lived access tokens + refresh; device/session listing.
- **Priority:** **High** (2FA enforcement + role-change revocation).

## 4. Role & permission coverage — ✅ Strong
- **Correct:** 14 roles, ~90 granular permissions in `RolePermissionSeeder`; **approval permissions are separate from edit** (e.g. `measurements.approve` vs `measurements.create`, `damage_reports.approve` vs `.create`, `inventory.fabric_rolls.adjust_out_approve`). Permissions seeded in the global team context; data isolation is by `branch_id`, not team scope.
- **Risky:** Permission matrix is large and hand-maintained; drift risk as features grow.
- **Must fix:** None.
- **Improve:** A test asserting every policy ability maps to a seeded permission would prevent orphan abilities.
- **Priority:** Low.

## 5. Multi-branch support — ✅ Strong
- **Correct:** `BelongsToBranch` adds a global `BranchScope` + auto-stamps `branch_id`; `BranchContext::current()` (null = Owner/all). Branch-scoped composite indexes on hot tables. Owner bypass verified across suites.
- **Risky:** Jobs run with no branch context → must `withoutGlobalScopes()` deliberately (done in rollup/report jobs). Easy to forget in future jobs.
- **Must fix:** None.
- **Improve:** A lint/test that flags queries in `Jobs/` lacking explicit scope handling.
- **Priority:** Low.

## 6. Customer & measurement design — ✅ Strong
- **Correct:** `Customer.phone` `encrypted` cast + `phone_last4` for search; `UpiIdEncryptedAtRestTest`-style raw-DB inspection proves encryption. Measurements are **append-only versioned** (`MeasurementVersion`, immutable fields guarded in `booted()`), with an approval workflow; orders FK to **versions, not profiles**.
- **Risky:** Customer dedupe relies on `phone_last4` + name — collisions possible at scale.
- **Must fix:** None.
- **Improve:** Blind-index (HMAC) on phone for exact-match search without decrypting.
- **Priority:** Low.

## 7. Order workflow — ✅ Strong
- **Correct:** Per-item state on `order_items`; **order status is derived** (`OrderStatusDeriver`), never stored; cancellation rules gated by `CANCELLABLE_STATES`; idempotent order create; `OrderCodeGenerator` gap-free per (branch, FY).
- **Risky:** None material.
- **Must fix:** None.
- **Priority:** —

## 8. Production transition safety — ✅ Strong
- **Correct:** `spatie/laravel-model-states` machine on `order_items.state`; `StateTransitionService` does `lockForUpdate` + validates the edge + writes an append-only `production_transitions` row + emits `OrderItemStateChanged`, all in one transaction; idempotency key per transition; rework capped at 3 with override. Triggers make `production_transitions` append-only.
- **Risky:** The State cast is an object — several call sites must `(string)`-cast (documented gotcha); future code may reintroduce the recursion bug.
- **Must fix:** None.
- **Improve:** A helper accessor returning the state name to discourage raw comparisons.
- **Priority:** Low.

## 9. Inventory stock accuracy — ✅ Strong
- **Correct:** Stock is a **ledger** (`fabric_movements`), never a counter; `remaining_metres` is a cache updated in the same `lockForUpdate` txn; `CHECK (remaining_metres >= 0)`; append-only trigger; `ReconcileStockJob` recomputes from the ledger and logs drift.
- **Risky:** Cache/ledger divergence is only caught nightly; no real-time assertion.
- **Must fix:** None.
- **Improve:** Periodic in-request reconcile sampling, or a generated-column remaining.
- **Priority:** Low.

## 10. Fabric roll locking & two-phase reservation — ✅ Good
- **Correct:** Two-phase `reserve → consume`; `available ≠ remaining`; row locks serialize concurrent cutters.
- **Risky:** Reservation release on abandoned cuts is **needs verification** for a timeout path (stale reservations could hold stock).
- **Must fix:** None blocking.
- **Improve:** A scheduled sweep to expire stale reservations.
- **Priority:** Medium.

## 11. QC & rework correctness — ✅ Good
- **Correct:** `qc_inspections` + dispositions drive Phase 7 transitions; rework bounded (cap 3 → 403 without override); defect photos via signed URLs; defect analytics.
- **Risky:** Defect photo cleanup relies on `PruneOrphanQcPhotosJob` (deletes DB rows; the S3 object deletion path is **needs verification**).
- **Must fix:** None.
- **Improve:** Delete the storage object, not just the row, on prune.
- **Priority:** Low.

## 12. Delivery & rack slot correctness — ✅ Strong
- **Correct:** Rack single-occupancy is **DB-enforced** (partial-unique via stored generated columns + nullable composite unique); OTP is **hashed at rest** (`Hash::make`), constant-time verify, 10-min expiry, 5-attempt lock; the wrong-attempt counter persists because verify runs **outside** the confirm transaction.
- **Risky:** None material.
- **Must fix:** None.
- **Priority:** —

## 13. Finance data security — ✅ Strong
- **Correct:** **Gap-free invoice/credit numbering** per (branch, FY) from a row-locked counter, never `MAX()+1` (`GapFreeNumberingUnderConcurrencyTest`); invoices financially immutable (partial-immutability trigger); payments append-only + idempotent + encrypted UPI; finance gated to Owner/Admin/Accountant (`RbacFinanceForbiddenForOthersTest`).
- **Risky:** Concurrency test is deterministic-sequential (project convention), not true multi-process.
- **Must fix:** None.
- **Improve:** A CI job that hammers numbering with parallel processes against MySQL.
- **Priority:** Low.

## 14. Reports & dashboard performance — ✅ Good
- **Correct:** Dashboard reads **only** `daily_branch_stats` rollups, proven by `DashboardReadsRollupsTest` (asserts no OLTP table is queried); cached 60s; reports run on the queue and produce content-addressed documents.
- **Risky:** Reports are **CSV, not Excel** (maatwebsite/excel substituted); rollups depend on the nightly job actually running (no Horizon yet).
- **Must fix:** None blocking.
- **Improve:** Add `maatwebsite/excel`; enforce eager-loading via a model `preventLazyLoading` in non-prod.
- **Priority:** Medium.

## 15. Audit log completeness — ✅ Good
- **Correct:** `spatie/activitylog` on 9 key models via `AuditsChanges` (encrypted/secret columns excluded; `state` cast excluded); service-layer `AuditService`; `activity_log` append-only by trigger; audit read API gated to Owner/Admin.
- **Risky:** **DB grants are documented but not applied locally** (`database/grants/append_only_grants.sql`) — append-only at grant level is **needs verification** in staging/prod until the deploy script runs it. Logging captures create/dirty-update only; reads are not audited.
- **Must fix:** Wire the grants SQL into the deploy pipeline and verify on staging.
- **Improve:** Add login-audit (success/failure) entries.
- **Priority:** Medium.

## 16. Validation & error handling — ✅ Strong
- **Correct:** `BaseFormRequest` → uniform 422 `VALIDATION_FAILED`; `DomainException` hierarchy with stable `errorCode`/status rendered by the global handler; machine-readable codes throughout (`PAYMENT_EXCEEDS_BALANCE`, `OTP_LOCKED`, `RACK_SLOT_OCCUPIED`, …).
- **Risky:** None material.
- **Must fix:** None.
- **Priority:** —

## 17. Test coverage — ⚠ Adequate
- **Correct:** ~270 passing feature/unit tests; strong invariant coverage (numbering concurrency, append-only, OTP lock, rollup isolation, RBAC). PHPStan level 6 + Pint green.
- **Risky:** **Coverage is not measured** — the `--min=70` gate exists in CI but no pcov run was executed here, so the 100%/90%/70% targets are **needs verification**.
- **Must fix:** Run a real coverage report; confirm 100% on state transitions / stock ledger / invoice numbering and 90% on finance.
- **Improve:** Add coverage badges and per-path thresholds.
- **Priority:** **High** (measure before go-live).

## 18. Database indexes — ✅ Good
- **Correct:** Composite indexes on hot paths (`(branch_id, status)`, `(branch_id, issued_at)`, `(order_item_id, occurred_at)`, dedupe uniques, partial-unique generated columns). InnoDB + `defaultStringLength(191)`.
- **Risky:** No slow-query-log review performed; some report queries (`limit(5000)`) are unbounded scans on large branches.
- **Must fix:** None blocking.
- **Improve:** Enable slow-query log in staging; add covering indexes for report queries; paginate reports.
- **Priority:** Medium.

## 19. Queue & scheduler setup — ⚠ Adequate
- **Correct:** Scheduler defined in `routes/console.php` (IST times), asserted by `ScheduledJobsRegisteredTest`; jobs are idempotent (rollups upsert, prunes are set-based); heavy work (PDF render, reports) is queued.
- **Risky:** **No Horizon** (Redis substituted) — queue priorities (high/default/low) and per-class **retry/backoff (3, exponential)** are **not configured**. Notification retry policy is documented but not wired to a backoff schedule.
- **Must fix:** Install Horizon + Redis; set queue connections, retry counts, and backoff per job class.
- **Improve:** Move scheduled jobs onto dedicated queues.
- **Priority:** **High** (for production throughput/resilience).

## 20. Deployment readiness — ⚠ Weak (operational gaps)
- **Correct:** README appendix documents zero-downtime two-deploy pattern, secrets policy, DR runbook (RPO 24h/RTO 4h), grants policy; `backup:verify` restore-drill command + test; CI runs Pint/PHPStan/Pest with a coverage job.
- **Risky:** **Sentry not installed**; structured-JSON logging not configured; backups/restore are documented but **not automated/scheduled in infra**; append-only grants not yet applied to any environment.
- **Must fix:** Install Sentry (errors + perf); enable JSON logging with `request_id`; schedule nightly dump + S3 + monthly restore drill; apply grants on staging.
- **Improve:** Envoyer/Deployer wiring; staging deploy rehearsal.
- **Priority:** **High**.

---

## Prioritized fix list

### High (must do before go-live)
| Fix | Files / area | Effort |
|-----|--------------|:------:|
| Enforce 2FA at login for Owner/Admin/Accountant | `Identity` auth flow, login middleware | M |
| Revoke Sanctum tokens on role change + set token TTL | `UserController::assignRole`, Sanctum config | M |
| Install Horizon + Redis; queue priorities + retry/backoff | queue config, job classes | L |
| Install Sentry + structured JSON logging | `config/logging`, Sentry publish | M |
| Measure coverage; confirm 100/90/70 targets | CI `coverage` job (pcov) | S |
| Automate backups + scheduled restore drill; apply grants on staging | infra, deploy script, `append_only_grants.sql` | M |

### Medium
| Fix | Effort |
|-----|:------:|
| Generate OpenAPI spec from routes/resources | M |
| Extend `Idempotency-Key` to remaining unsafe writes (or document the subset) | S |
| Stale fabric-reservation sweep | S |
| Excel export (`maatwebsite/excel`) + report pagination | M |
| Enable slow-query log in staging; covering indexes for reports | S |

### Low
- Per-module service providers; orphan-ability test; blind-index phone search; state-name accessor; storage-object deletion on QC prune.

---

## Top 7 to fix before go-live (with exact targets)

1. **Enforce 2FA** for Owner/Admin/Accountant — add a post-login guard in the `Identity` auth path (`AuthController::login` / a `RequiresTwoFactor` middleware on the auth group). *Invariant #8.*
2. **Role-change token revocation + token TTL** — in `app/Modules/Identity/Http/Controllers/Api/V1/UserController.php::assignRole`, delete the user's tokens; set `sanctum.expiration`.
3. **Queue/Horizon** — `composer require laravel/horizon`, configure high/default/low queues + `tries=3` + exponential `backoff()` on `RenderPdfJob`, `RunReportJob`, notification dispatch.
4. **Observability** — `composer require sentry/sentry-laravel`; JSON log channel carrying `request_id`; verify `/api/v1/health` in the deploy smoke test.
5. **Coverage proof** — run `pest --coverage --min=70` (the `coverage` job in `.github/workflows/ci.yml`); add per-path assertions for `StateTransitionService`, `StockLedgerService`, `InvoiceNumberService`.
6. **Backups + grants in infra** — schedule nightly dump→S3 + monthly `backup:verify`; apply `database/grants/append_only_grants.sql` on staging and confirm `AppendOnlyGrantTest` semantics hold under the restricted user.
7. **OpenAPI contract** — generate and publish a spec for the `/api/v1` surface so clients have a stable contract.

---

## Evidence index (representative)
- Branch isolation & RBAC: `tests/Feature/**` across modules; `RolePermissionSeeder`.
- Ledger & reconcile: `StockLedgerService`, `ReconcileStockJob`, CHECK constraint migration.
- State machine: `StateTransitionService`, `production_transitions` trigger migration.
- Numbering: `InvoiceNumberService`, `GapFreeNumberingUnderConcurrencyTest`, `FiscalYearRolloverResetsCounterTest`.
- Append-only: `AppendOnlyGrantTest`, payment/credit/activity_log/transition/movement triggers.
- Delivery/OTP/rack: `tests/Feature/Delivery/**`, rack partial-unique migrations.
- Dashboard isolation: `DashboardReadsRollupsTest`.
- Security headers / audit: `tests/Feature/Security/**`, `tests/Feature/Audit/**`.

*Quality gates at review time: Pint clean, PHPStan level 6 clean, full Pest suite green — **270 passed (1059 assertions)** on MySQL.*
