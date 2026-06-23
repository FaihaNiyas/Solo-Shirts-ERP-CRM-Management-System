# Solo Shirts India ERP

A custom **ERP & CRM** for Solo Shirts — a garment/tailoring business. It manages
customers, measurements, orders, fabric stock, stitching status, billing, delivery
tracking, and customer follow-ups in one centralized, API-first platform.

Built as an **API-first modular monolith** on Laravel 12. This repository is being
delivered phase-by-phase (see [`solo-shirts-erp-laravel-prompt-pack.md`](solo-shirts-erp-laravel-prompt-pack.md)).
**Current status: Phase 1 — environment, scaffold, and `/health` endpoint.**

---

## Tech stack (locked)

| Layer            | Choice                                                        |
| ---------------- | ------------------------------------------------------------- |
| Framework        | Laravel 12.x, PHP 8.3+ (upgraded from the spec's 11.x to patch CVE-2026-48019) |
| Database         | MySQL 8 / PostgreSQL 15+ (SQLite for local/CI convenience)     |
| Cache/Queue/Lock | Redis 7+ (via `predis`)                                       |
| Auth             | Laravel Sanctum                                               |
| Permissions      | spatie/laravel-permission (teams = branch)                    |
| Audit            | spatie/laravel-activitylog                                    |
| State machines   | spatie/laravel-model-states                                   |
| Tests / Style / Static | Pest 3 · Laravel Pint · Larastan (PHPStan level 6)      |

---

## Requirements

- PHP **8.3+** with extensions: `pdo_mysql` (or `pdo_pgsql`), `pdo_sqlite`, `mbstring`,
  `openssl`, `curl`, `bcmath`, `gd`, `zip`, `fileinfo`
- Composer 2.x
- (Production) MySQL 8 / PostgreSQL 15+ and Redis 7+

Redis and a DB server are **not required for local dev or tests** — see below.

---

## Local setup (under 15 minutes)

```bash
git clone <repo-url> solo-shirts-erp
cd solo-shirts-erp

composer install
cp .env.example .env
php artisan key:generate

# Zero-dependency local DB: switch .env to SQLite
#   DB_CONNECTION=sqlite
#   DB_DATABASE=database/database.sqlite
touch database/database.sqlite      # (Windows: New-Item database/database.sqlite)
php artisan migrate

php artisan serve
curl http://127.0.0.1:8000/api/v1/health
```

### Running with the full locked stack (MySQL + Redis)

`.env.example` defaults to the production stack. Provision MySQL 8 and Redis 7, set the
`DB_*` and `REDIS_*` values, keep `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis`,
`SESSION_DRIVER=redis`, then `php artisan migrate`. The `/health` endpoint will report
`redis: true` once Redis is reachable.

---

## Environment variables

| Variable                          | Purpose                                                            |
| --------------------------------- | ----------------------------------------------------------------- |
| `APP_TIMEZONE`                    | `Asia/Kolkata`                                                     |
| `APP_COMMIT`                      | Deployed commit SHA shown by `/health`; falls back to `git rev-parse --short HEAD` |
| `DB_CONNECTION`, `DB_*`           | Database connection (mysql/pgsql/sqlite)                           |
| `REDIS_CLIENT`                    | `predis` (pure PHP) or `phpredis`                                  |
| `REDIS_HOST`, `REDIS_PORT`        | Redis connection                                                  |
| `REDIS_TIMEOUT`, `REDIS_READ_TIMEOUT` | 1s connect/read ceiling so `/health` never hangs (default 1.0) |
| `CACHE_STORE`, `QUEUE_CONNECTION`, `SESSION_DRIVER` | `redis` in prod                                  |

---

## The `/health` endpoint

```
GET /api/v1/health        (public, throttled 60/min)
```

Verifies DB, Redis, and the queue backend (each probe is bounded and never throws).

- **200** — all dependencies up:
  ```json
  { "success": true, "message": "Service healthy",
    "data": { "php": "8.3.x", "laravel": "11.x", "db": true, "redis": true,
              "queue": true, "commit": "abc1234" },
    "request_id": "<uuid>" }
  ```
- **503** `HEALTH_DEPENDENCY_DOWN` — one or more dependencies down (same `data` shape with
  the failing probe `false`).

Every response carries a `request_id` (also in the `X-Request-Id` header) propagated to the
log context.

---

## Commands

```bash
# Tests (Pest) — uses in-memory SQLite, array cache, sync queue (no services needed)
./vendor/bin/pest
./vendor/bin/pest --filter HealthCheck

# Code style (Laravel Pint)
./vendor/bin/pint            # fix
./vendor/bin/pint --test     # check only (CI gate)

# Static analysis (Larastan / PHPStan level 6)
./vendor/bin/phpstan analyse

# Database
php artisan migrate
php artisan migrate:fresh
```

---

## Architecture

API-first modular monolith. Each domain lives under `app/Modules/` (psr-4
`App\Modules\`) and is internally layered:

```
app/Modules/{Customer,Order,Production,Inventory,Delivery,Finance,Identity,Shared}/
  Http/{Controllers/Api/V1, Requests, Resources, Middleware}
  Services, Models, Database/{Migrations, Seeders, Factories}
  Policies, Events, Listeners, Exceptions
```

Cross-module communication happens via service interfaces and domain events — **no
cross-module DB joins**. Controllers are thin; business logic lives in module Services.

### Standard API envelope

```json
// success
{ "success": true, "message": "...", "data": {}, "request_id": "<uuid>" }
// error
{ "success": false, "message": "...", "code": "DOMAIN_ERROR_CODE",
  "errors": {}, "request_id": "<uuid>" }
```

---

## CI

`.github/workflows/ci.yml` runs on every push and PR: **Pint → PHPStan (level 6) → Pest**.
A separate `coverage` job runs Pest under pcov and fails below the overall
threshold (`--min=70`). Per-area targets (enforced in review): **100%** on state
transitions, the stock ledger, and invoice numbering; **90%** on finance.

---

## Security & Deployment Hardening (Phase 18)

### Append-only data + DB grants
`activity_log`, `production_transitions`, `fabric_movements`, `payments`,
`credit_notes` are append-only. This is enforced **twice**:

1. **Triggers** (`BEFORE UPDATE`/`DELETE` → `SIGNAL SQLSTATE '45000'`) in every
   environment, via migrations.
2. **DB grants** in staging/production only — the app runtime user
   (`solo_app`) has `SELECT, INSERT` (no `UPDATE`/`DELETE`) on those tables; see
   [`database/grants/append_only_grants.sql`](database/grants/append_only_grants.sql).
   A separate `solo_owner` identity holds DDL and runs migrations on deploy.

> Grants are **not** applied in local/CI (the test framework must reset the DB);
> the triggers provide the same guarantee there.

### Audit trail
`spatie/laravel-activitylog` logs create/update on Customer, Order, OrderItem,
MeasurementVersion, FabricRoll, Invoice, Payment, DamageReport, DeliveryAttempt
(via the `AuditsChanges` trait, log name `audit`). Encrypted/secret columns
(phone, UPI id, QR payloads) are excluded. Read it at
`GET /api/v1/audit/activities` and `GET /api/v1/audit/transitions/{item}`
(Owner/Admin; supervisors may read transitions).

### Security headers
Every `/api/*` response carries `X-Content-Type-Options: nosniff`,
`X-Frame-Options: DENY`, `Content-Security-Policy: default-src 'none'`,
`Strict-Transport-Security: max-age=31536000; includeSubDomains`, and
`Referrer-Policy: no-referrer` (`SecurityHeaders` middleware).

### Secrets policy
No plaintext `.env` in the repo. Secrets live in a vault (AWS Secrets Manager /
Doppler / encrypted `.env`), rotated quarterly, encrypted at rest. Rotating the
Sanctum token secret revokes all issued tokens (planned, intentional).

### Backups & restore drill
Nightly DB dump + S3 sync. `php artisan backup:verify` runs a restore drill into
a temp DB and asserts core invariants (customers present, no negative stock, no
orphan invoices); it pages on-call on failure. A failed nightly backup blocks the
next day's reconciliation until acknowledged.

### Zero-downtime deploy
Atomic symlink swap (Envoyer / Deployer / GitHub Actions). Migrations are
reversible and tested on a staging snapshot. **Two-deploy pattern** for
renames/drops: never ship a destructive migration in the same release as code
that still depends on the old column.

### Disaster recovery runbook
RPO 24h, RTO 4h. Restore the latest dump into a fresh instance, run
`backup:verify`, repoint the app, smoke-test `/api/v1/health`. On-call: solo,
paged via Sentry → email/WhatsApp.

### Observability
Sentry (errors + performance traces), structured JSON logs carrying `request_id`,
`/api/v1/health` deep dependency check, slow-query log enabled in staging.
