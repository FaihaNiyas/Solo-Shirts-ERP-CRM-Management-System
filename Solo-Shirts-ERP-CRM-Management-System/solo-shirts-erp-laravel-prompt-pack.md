# Solo Shirts India ERP — Laravel Backend Prompt Pack

**Use phase by phase.** For every phase, paste the **Master Prompt** first, then paste the **Phase Prompt** under it. Do not feed the whole file in one shot — token usage will explode and quality will drop.

All phase prompts already bake in the fixes from the architecture review (versioned measurements, stock ledger, two-phase fabric reservation, branch-scoped permissions, gap-free invoice numbering, append-only audit, idempotency, rack-slot DB-level uniqueness, etc.). You don't need to remember the review notes — they're already inside.

---

## 📌 MASTER PROMPT (reuse on top of every phase)

```text
You are a senior backend architect and full-stack Laravel engineer with 20+ years of
experience building production ERPs. You are working on "Solo Shirts India ERP" — a
garment/tailoring ERP. You will work strictly in TDD and follow every rule below.

=== TECH STACK (LOCKED — do not propose alternatives) ===
- Laravel 11.x, PHP 8.3+
- MySQL 8 or PostgreSQL 15+
- Redis 7+ (cache + queue + locks)
- Laravel Sanctum (API auth, token abilities)
- Spatie Laravel Permission (with teams feature = branch)
- Spatie Laravel Activitylog (general audit)
- Spatie Laravel Model States (state machines)
- Pest for tests, Pint for style, Larastan level 6 for static analysis
- Laravel Horizon for queues
- DomPDF or Browsershot for PDF, Maatwebsite Excel for exports
- SimpleSoftwareIO QrCode for QR codes

=== ARCHITECTURE (NON-NEGOTIABLE) ===
- API-first modular monolith. Folder layout:
  app/Modules/{Customer,Order,Production,Inventory,Delivery,Finance,Identity,Shared}/
    {Http,Services,Models,Database/{Migrations,Seeders},Resources,Policies,Events,Listeners}
- Controllers thin. Business logic lives in Services or Actions inside the module.
- Validation via Form Requests. Responses via API Resources.
- Authorization via Policies + Spatie permissions (branch-scoped).
- All multi-step writes wrapped in DB::transaction with lockForUpdate where rows are mutated.
- All write endpoints accept an "Idempotency-Key" header; duplicates return the original result.
- Cross-module communication via Service interfaces or Domain Events — NO cross-module DB joins.

=== CRITICAL INVARIANTS (apply to every phase) ===
1. branch_id NOT NULL on every transactional table. A global Eloquent scope enforces branch
   isolation. Only Owner role may cross branches (explicit bypass).
2. Measurements are append-only versioned (measurements + measurement_versions). Orders FK
   to measurement_version_id, never to a mutable measurement row.
3. Stock is a LEDGER. fabric_movements is append-only. fabric_rolls.remaining_metres is a
   denormalized cache, recomputed inside the same transaction as each movement.
4. Fabric allocation is two-phase: reserve → consume (or release on cancel). Reservation
   reduces "available" (= remaining − reserved), never "remaining".
5. Production state lives on order_items, not orders. Order status is derived. Transitions
   use spatie/laravel-model-states, run inside DB::transaction with lockForUpdate, and are
   idempotent via the Idempotency-Key header + an idempotency unique index.
6. GST invoice numbers come from a row-locked invoice_sequences(branch_id, fiscal_year,
   last_number) counter. No MAX()+1 anywhere. Invoices are append-only; corrections via
   credit notes.
7. Audit tables (activity_log, production_transitions, fabric_movements, payment_ledger)
   are INSERT-only at the DB grant level for the app user.
8. Sensitive PII (phone, UPI, bank account) uses Laravel's encrypted cast.
9. Rack slot uniqueness is enforced at DB level (partial unique index or rack_slots table).
10. Every job is idempotent — assume it may run twice.

=== STANDARD API ENVELOPE ===
Success:
{ "success": true, "message": "...", "data": {}, "request_id": "<uuid>" }
Error:
{ "success": false, "message": "...", "code": "DOMAIN_ERROR_CODE",
  "errors": { "field": ["..."] }, "request_id": "<uuid>" }

Every response includes request_id. Every domain error has a stable machine-readable code.

=== TDD WORKFLOW (FOLLOW STRICTLY FOR EVERY TASK) ===
1. Issue understood: restate in one paragraph.
2. Root cause: name the design or code reason.
3. Test/check before fix: write failing Pest test(s) FIRST.
4. Confirm what is currently failing (paste assertion message).
5. Files to change: list only.
6. Fix summary: minimum required change. Show only modified sections, not full files.
7. Test/check after fix: paste passing test output.
8. Refactor: only if needed and explain why.
9. Remaining issues: enumerate.
10. Next recommended step.

=== OUTPUT RULES (TOKEN DISCIPLINE) ===
- Do not print full files unless I ask. Show only changed parts or short summaries.
- Do not rewrite unrelated code.
- Do not add packages outside the locked stack without asking.
- If you find multiple issues, fix them one at a time, not all at once.
- If something is unclear, make the safest assumption, state it, and continue.
- Do not over-engineer. Smallest correct change wins.

=== ROLES (used across all phases) ===
Owner/Admin, Front Desk, Measurement Staff, Production Supervisor, Cutting Master, Tailor,
Kaja Button, QC Supervisor, Ironing Master, Re-Worker, Inventory Manager, Accountant,
Delivery Staff. All permissions are branch-scoped except Owner.

=== PER-PHASE OUTPUT FORMAT ===
For each task in the phase, follow exactly:
  Issue understood:
  Root cause:
  Test/check before fix:
  Files to change:
  Fix summary:
  Test/check after fix:
  Remaining issues:
  Next recommended step:

When a section is N/A for the current phase, write "N/A — <one-line reason>".

Now read the PHASE PROMPT below and start.
```

---

## 📌 PHASE 1 — Laravel Backend Environment Setup

```text
PHASE 1: Laravel Backend Environment Setup

GOAL
Bootstrap Laravel 11.x for Solo Shirts India ERP, install the locked package set, create
the app/Modules layout, wire CI tooling (Pint, Larastan, Pest), and ship a verifiable
/health endpoint that proves DB + Redis + Queue all work.

BUSINESS REQUIREMENTS
- New developer can clone and run locally in under 15 minutes.
- Health endpoint verifies DB, Redis, queue, returns deployed commit SHA.
- CI runs Pint, Larastan, Pest on every push and PR.

TABLES REQUIRED
N/A — Laravel defaults only.

APIS REQUIRED
GET /api/v1/health   (public, throttle:60,1)
  200 → { success, data:{ php, laravel, db, redis, queue, commit }, request_id }
  503 → { success:false, code:"HEALTH_DEPENDENCY_DOWN", data:{...} }

BACKEND SERVICES REQUIRED
app/Modules/Shared/Services/HealthService.php
  checkDb(): bool, checkRedis(): bool, checkQueue(): bool, snapshot(): array
  Each downstream check times out at 1s.

CONTROLLERS REQUIRED
app/Modules/Shared/Http/Controllers/Api/V1/HealthController.php

FORM REQUESTS / API RESOURCES / PERMISSIONS / SEEDERS
N/A or HealthResource only.

MIDDLEWARE REQUIRED
- AssignRequestId — UUID per request, attaches to log context + response header + body.
- ForceJsonResponse — sets Accept: application/json on /api/*.
- Global exception handler returns the standard envelope for /api/* (no HTML leaks).

TESTS REQUIRED (write FIRST)
tests/Feature/Shared/HealthCheckTest.php
- 200 with standard envelope and request_id when all deps up
- 503 with code HEALTH_DEPENDENCY_DOWN when DB ping mocked to fail
- 503 when Redis ping mocked to fail
- 61st request in a minute returns 429
- X-Request-Id header equals JSON body request_id

COMMANDS
composer create-project laravel/laravel solo-shirts-erp "11.*"
composer require laravel/sanctum spatie/laravel-permission spatie/laravel-activitylog \
  spatie/laravel-model-states spatie/laravel-data predis/predis ramsey/uuid
composer require --dev pestphp/pest pestphp/pest-plugin-laravel laravel/pint \
  larastan/larastan nunomaduro/collision
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider"
./vendor/bin/pest --init

FOLDER STRUCTURE (.gitkeep in each)
app/Modules/{Customer,Order,Production,Inventory,Delivery,Finance,Identity,Shared}/
  {Http/{Controllers/Api/V1,Requests,Resources,Middleware},
   Services, Models, Database/{Migrations,Seeders,Factories},
   Policies, Events, Listeners, Exceptions}

composer.json psr-4:  "App\\Modules\\": "app/Modules/"
Then: composer dump-autoload

CONFIG FILES
.env.example — DB_*, REDIS_*, QUEUE_CONNECTION=redis, CACHE_DRIVER=redis,
  SESSION_DRIVER=redis, APP_TIMEZONE=Asia/Kolkata, APP_LOCALE=en, APP_COMMIT
pint.json — PSR-12 + Laravel preset
phpstan.neon — level 6, paths: [app]
.github/workflows/ci.yml — composer install, pint --test, phpstan analyse, pest
README.md — setup, env vars, run commands, test commands

ROUTES
routes/api.php → Route::prefix('v1')->middleware(['api','request.id','throttle:60,1'])

EDGE CASES
- Health never throws 500 when Redis/DB unreachable; catches and returns structured 503.
- Each downstream ping times out at 1s.
- request_id propagated to Laravel log context.
- APP_COMMIT missing → fall back to `git rev-parse --short HEAD`, cached for process lifetime.

ACCEPTANCE CHECKLIST
[ ] composer install succeeds on clean clone
[ ] php artisan migrate:fresh runs clean
[ ] curl /api/v1/health returns documented envelope
[ ] Killing Redis → 503 with documented envelope
[ ] All Pest tests pass
[ ] pint --test clean
[ ] phpstan analyse clean at level 6
[ ] CI workflow green on first push
[ ] README covers setup, env, tests
[ ] app/Modules/* skeleton exists for all 8 modules
[ ] composer.json psr-4 autoload includes App\Modules\

TDD WORKFLOW
1. Write HealthCheckTest first → fails (endpoint missing)
2. Add route → controller → service → resource → passes happy path
3. Add failure-mode tests with mocks → implement timeouts and structured errors
4. Add rate-limit test → confirm middleware
5. Add request_id middleware + test
6. Re-run full suite → green
7. Refactor HealthService only if > ~40 lines

OUTPUT EXPECTED
Use per-task output format from Master Prompt. Don't paste full composer.json or
untouched scaffold files. Show only what you created or changed. Confirm acceptance
checklist line-by-line at end of phase.
```

---

## 📌 PHASE 2 — Core Architecture, API Standard, Base Classes

```text
PHASE 2: Core Architecture, API Standard, Base Classes

GOAL
Establish the project-wide primitives every later phase depends on: standard API
envelope helpers, base controller, base service, base form request, base resource,
idempotency handling, domain exception hierarchy, and the Shared module's BranchContext.

BUSINESS REQUIREMENTS
- Every API response (success or error) follows the locked envelope.
- Every write endpoint supports Idempotency-Key header (replay returns original response).
- Every domain error carries a stable machine-readable code.
- All later phases inherit these base classes, eliminating boilerplate and drift.

TABLES REQUIRED
- idempotency_keys(id, key UNIQUE per user, user_id, method, path, request_hash,
  response_status, response_body, created_at)
  TTL: 24 hours via scheduled prune job.

APIS REQUIRED
N/A — no new endpoints, but every later endpoint will use these primitives.

BACKEND SERVICES REQUIRED
- app/Modules/Shared/Services/BranchContext.php
    current(): int|null, setCurrent(int $branchId), isOwner(): bool
- app/Modules/Shared/Services/IdempotencyService.php
    rememberOrExecute(string $key, Closure $fn): array
- app/Modules/Shared/Support/ApiResponse.php
    static success($data, $message, $status=200), static error($message, $code, $errors=[], $status=400)

CONTROLLERS REQUIRED
- app/Http/Controllers/Api/V1/BaseApiController.php
    Provides protected respond() helper that always returns the standard envelope.

FORM REQUESTS REQUIRED
- app/Modules/Shared/Http/Requests/BaseFormRequest.php
    Overrides failedValidation() to throw a 422 with the standard error envelope.

API RESOURCES REQUIRED
- app/Modules/Shared/Http/Resources/BaseResource.php
    Standardizes wrapping and date formatting (ISO-8601 with timezone).

PERMISSIONS REQUIRED
N/A — defined in Phase 3.

SEEDERS REQUIRED
N/A.

MIDDLEWARE REQUIRED
- IdempotencyMiddleware — on POST/PUT/PATCH/DELETE, requires Idempotency-Key header
  for whitelisted "side-effectful" routes; persists request hash + response; replays on hit.
- HandleDomainExceptions — converts domain exceptions to standardized JSON errors.

DOMAIN EXCEPTION HIERARCHY
app/Modules/Shared/Exceptions/
  DomainException.php (abstract, has $code, $status, $errors)
  InvalidStateTransitionException.php
  InsufficientStockException.php
  BranchIsolationException.php
  ApprovalRequiredException.php
  IdempotencyConflictException.php

TESTS REQUIRED (write FIRST)
- tests/Unit/Shared/ApiResponseTest.php — success and error shapes
- tests/Feature/Shared/IdempotencyTest.php — same key + same body returns cached response;
  same key + different body returns 409 IDEMPOTENCY_CONFLICT; missing key on whitelisted
  route returns 400 IDEMPOTENCY_KEY_REQUIRED
- tests/Feature/Shared/ValidationErrorEnvelopeTest.php — invalid input returns 422 with the
  exact documented shape including code "VALIDATION_FAILED"
- tests/Feature/Shared/DomainExceptionHandlerTest.php — throwing InsufficientStockException
  results in 409 with code "INSUFFICIENT_STOCK"

COMMANDS
php artisan make:migration create_idempotency_keys_table
php artisan make:middleware IdempotencyMiddleware

EDGE CASES
- Idempotency: same key + same hash + still processing → return 409 IDEMPOTENCY_IN_FLIGHT.
- Idempotency record older than 24h → treated as new.
- Anonymous endpoints (no user) cannot use idempotency (require auth).
- A DomainException raised inside a queued job must not leak to HTTP — caught by handler.

ACCEPTANCE CHECKLIST
[ ] idempotency_keys migration applied
[ ] IdempotencyMiddleware registered and unit-tested
[ ] All 4 Pest test files green
[ ] BaseApiController, BaseFormRequest, BaseResource exist and are extended by a
    smoke-test controller in Shared module
[ ] DomainException hierarchy implemented, handler wired
[ ] Every error path produces the standard envelope with a code
[ ] phpstan + pint clean

TDD WORKFLOW
1. Write ApiResponseTest → fails → implement ApiResponse helper.
2. Write ValidationErrorEnvelopeTest → fails → override BaseFormRequest failedValidation.
3. Write DomainExceptionHandlerTest → fails → implement HandleDomainExceptions middleware.
4. Write IdempotencyTest → fails → implement migration + service + middleware.
5. Re-run full suite → green. Refactor only if duplication > 2 sites.

OUTPUT EXPECTED
Show only new/changed files. Do not echo Laravel scaffolds. Confirm checklist at end.
```

---

## 📌 PHASE 3 — Auth, Users, Roles, Permissions, Branch Foundation

```text
PHASE 3: Auth, Users, Roles, Permissions, Branch Foundation

GOAL
Stand up authentication (Sanctum), users, branches, branch-scoped roles & permissions
(Spatie with teams=branch), 2FA for finance roles, login throttling, and the global
Eloquent scope that enforces branch isolation on every transactional model.

BUSINESS REQUIREMENTS
- 13 roles as defined in Master Prompt. All branch-scoped except Owner.
- Login throttled: 5 failed attempts → 15 min lockout per email+IP.
- 2FA mandatory for Owner, Admin, Accountant (TOTP via google2fa).
- Sanctum tokens expire in 24h; refresh endpoint issues new token.
- Changing a user's role/branch revokes all their existing tokens immediately.
- Every login (success + fail) logged with IP and user agent.
- Owner can switch branch context; staff cannot.

TABLES REQUIRED
- branches(id, code UNIQUE, name, address, gst_number, phone, is_active, created_at, updated_at)
- users(id, branch_id FK, name, email UNIQUE, phone (encrypted), password,
  two_factor_secret (encrypted, nullable), two_factor_confirmed_at, is_active,
  remember_token, created_at, updated_at, deleted_at)
- Spatie tables published with teams enabled (teams = branches).
- login_attempts(id, email, ip, user_agent, success bool, attempted_at) — indexed on (email, attempted_at), (ip, attempted_at)

APIS REQUIRED
POST   /api/v1/auth/login              (email, password, otp?)  → token, user, abilities
POST   /api/v1/auth/logout             (auth)
POST   /api/v1/auth/refresh            (auth)
GET    /api/v1/auth/me                 (auth)
POST   /api/v1/auth/2fa/enable         (auth)  → returns QR data
POST   /api/v1/auth/2fa/confirm        (auth, otp)
POST   /api/v1/auth/2fa/disable        (auth, password, otp)
POST   /api/v1/auth/switch-branch      (Owner only) (branch_id)
GET    /api/v1/branches                (Owner, Admin)
POST   /api/v1/branches                (Owner)
PUT    /api/v1/branches/{id}           (Owner)
GET    /api/v1/users                   (Admin within branch; Owner all)
POST   /api/v1/users                   (Admin)
PUT    /api/v1/users/{id}              (Admin within branch; Owner all)
DELETE /api/v1/users/{id}              (Owner)
POST   /api/v1/users/{id}/assign-role  (Admin within branch; Owner all)

BACKEND SERVICES REQUIRED
- Identity/Services/AuthService.php — login, logout, refresh, throttle check, log attempt
- Identity/Services/TwoFactorService.php — enable, confirm, verify (google2fa)
- Identity/Services/UserService.php — create, update, assignRole, revokeTokens
- Identity/Services/BranchService.php — CRUD, switch context
- Shared/Scopes/BranchScope.php — global scope skipped when BranchContext::isOwner()
- Shared/Traits/BelongsToBranch.php — trait that applies BranchScope and casts branch_id

CONTROLLERS REQUIRED
- Identity/Http/Controllers/Api/V1/{AuthController,TwoFactorController,UserController,BranchController}.php

FORM REQUESTS REQUIRED
- LoginRequest, RefreshRequest, EnableTwoFactorRequest, ConfirmTwoFactorRequest,
  SwitchBranchRequest, CreateUserRequest, UpdateUserRequest, AssignRoleRequest,
  CreateBranchRequest, UpdateBranchRequest

API RESOURCES REQUIRED
- UserResource, BranchResource, LoginResource (token + user + abilities)

PERMISSIONS REQUIRED (created in seeder)
For each module: {view, create, update, delete, approve} where applicable.
Examples: users.view, users.create, branches.view, branches.create,
finance.view, finance.approve_writeoff, measurements.approve, etc.
Role → permission map defined in RolePermissionSeeder.

SEEDERS REQUIRED
- BranchSeeder — seeds at least one branch ("HQ").
- RolePermissionSeeder — creates the 13 roles and assigns permissions deterministically.
- OwnerUserSeeder — seeds one Owner user from env vars (OWNER_EMAIL, OWNER_PASSWORD).

PACKAGES TO ADD (within locked spirit, ask if uncertain)
- pragmarx/google2fa-laravel (TOTP)

TESTS REQUIRED (write FIRST)
- LoginTest — happy path, wrong password, inactive user, locked out after 5 fails,
  2FA required for Accountant, 2FA OTP wrong returns 401 INVALID_OTP
- TokenExpiryTest — token older than 24h returns 401
- RoleAssignmentRevokesTokensTest — assigning new role invalidates previous tokens
- BranchIsolationTest — Branch-A Tailor gets 403 fetching a Branch-B user; Owner sees both
- SwitchBranchTest — Owner can switch context and subsequent reads scope to new branch
- TwoFactorFlowTest — enable → confirm → next login requires OTP

COMMANDS
composer require pragmarx/google2fa-laravel
php artisan vendor:publish --provider="PragmaRX\Google2FALaravel\ServiceProvider"
php artisan make:migration create_branches_table
php artisan make:migration create_login_attempts_table
php artisan make:migration add_branch_and_2fa_to_users_table
php artisan db:seed --class=BranchSeeder
php artisan db:seed --class=RolePermissionSeeder
php artisan db:seed --class=OwnerUserSeeder

EDGE CASES
- Login throttle keyed on (email + ip) so attacker rotating IP doesn't bypass.
- "Soft-deleted" or is_active=false users cannot log in.
- 2FA disable requires current password AND current OTP — never password alone.
- Owner switching branch must NOT change their underlying branch_id; it sets
  BranchContext only for the session.
- Token refresh issues a new token and revokes the old one in the same transaction.

ACCEPTANCE CHECKLIST
[ ] All migrations applied, all seeders idempotent
[ ] 13 roles seeded; permission matrix matches RolePermissionSeeder
[ ] All tests green
[ ] Branch isolation verified by feature test on at least 3 endpoints
[ ] 2FA enforced for Owner/Admin/Accountant by config + test
[ ] Login attempts table populated on every login attempt
[ ] phpstan + pint clean

TDD WORKFLOW
1. Branches migration + model + test (CRUD).
2. Users migration update + factory + LoginTest happy path → fails → AuthService stub → passes.
3. Login throttle test → fails → implement throttle on (email,ip) → passes.
4. Role+permission seeder + BranchIsolationTest → fails → implement BranchScope + middleware → passes.
5. 2FA flow tests → fails → integrate google2fa → passes.
6. Switch-branch test (Owner) → fails → implement BranchContext override → passes.
7. Run full suite → green.

OUTPUT EXPECTED
Show only created/changed files. Don't paste Spatie's published files. Confirm checklist.
```

---

## 📌 PHASE 4 — Customer and Family Member Module

```text
PHASE 4: Customer and Family Member Module

GOAL
Build customer master and family member sub-records with unique customer ID, QR code,
encrypted phone, full search, and complete branch isolation.

BUSINESS REQUIREMENTS
- Customer stores: name, phone (encrypted), address, preferred_fabric, special_notes,
  customer_code (unique, human-friendly per branch like SSI-HQ-000123), qr_payload.
- One customer may have many family members (name, relation, dob, gender, notes).
- Soft-deletes for customers (compliance: DPDP Act — keep audit while supporting "forget").
- Full-text-like search on name + phone (last 4 of decrypted phone).
- QR payload = signed string (HMAC) identifying customer; scanning loads customer screen.
- Front Desk creates/edits; Owner/Admin can delete.

TABLES REQUIRED
- customers(id, branch_id FK, customer_code UNIQUE, name, phone (encrypted),
  phone_last4 (plaintext, indexed for search), address, preferred_fabric_id (nullable),
  special_notes, qr_payload UNIQUE, created_by, updated_by, created_at, updated_at, deleted_at)
- family_members(id, customer_id FK, name, relation, dob nullable, gender enum, notes,
  created_at, updated_at, deleted_at)
- INDEX: (branch_id, name), (branch_id, phone_last4)

APIS REQUIRED
GET    /api/v1/customers?search=&page=    list, paginated, with last_measurement_at if any
POST   /api/v1/customers                  create
GET    /api/v1/customers/{id}             show with family_members & latest measurements summary
PUT    /api/v1/customers/{id}             update
DELETE /api/v1/customers/{id}             soft delete (Owner/Admin only)
GET    /api/v1/customers/by-qr/{payload}  resolve QR → customer
POST   /api/v1/customers/{id}/family-members
PUT    /api/v1/customers/{id}/family-members/{fid}
DELETE /api/v1/customers/{id}/family-members/{fid}

BACKEND SERVICES REQUIRED
- Customer/Services/CustomerService.php — create (assigns customer_code + qr_payload),
  update, softDelete, search
- Customer/Services/FamilyMemberService.php — CRUD scoped to a customer
- Shared/Services/CodeGenerator.php — branch-prefixed sequential codes
  using row-locked counter table customer_sequences(branch_id, last_number)
- Shared/Services/QrPayloadSigner.php — sign(payload):string, verify(string):array

CONTROLLERS REQUIRED
CustomerController, FamilyMemberController

FORM REQUESTS REQUIRED
CreateCustomerRequest, UpdateCustomerRequest,
CreateFamilyMemberRequest, UpdateFamilyMemberRequest

API RESOURCES REQUIRED
CustomerResource (hides full phone, exposes phone_masked like "******1234"),
CustomerListResource (lighter), FamilyMemberResource

PERMISSIONS REQUIRED
customers.view, customers.create, customers.update, customers.delete,
family_members.manage

SEEDERS REQUIRED
None production. CustomerFactory + FamilyMemberFactory for tests.

TESTS REQUIRED (write FIRST)
- CustomerCreateTest — assigns unique code, qr_payload, encrypts phone, sets branch_id
- CustomerCodeUniqueTest — concurrent creates from same branch never collide (use ParallelTesting or transaction lock test)
- BranchIsolationOnCustomersTest — Branch-A user cannot read Branch-B customer
- CustomerSearchTest — by phone last 4, by partial name
- QrLookupTest — valid signed payload → customer; tampered → 422 INVALID_QR_SIGNATURE
- FamilyMemberCrudTest — happy paths + cannot attach to other-branch customer
- PhoneIsEncryptedAtRestTest — DB row stores ciphertext; phone_last4 is plaintext digits

COMMANDS
php artisan make:migration create_customers_table
php artisan make:migration create_family_members_table
php artisan make:migration create_customer_sequences_table

EDGE CASES
- Creating customer with phone identical to existing one in same branch returns
  409 DUPLICATE_PHONE with the existing customer_id in errors (front desk dedupes).
- QR payload contains branch_id; scanning a QR from a different branch returns 403
  unless caller is Owner.
- Soft-deleted customer still appears in audit/order history but never in active list/search.

ACCEPTANCE CHECKLIST
[ ] All tests green
[ ] Phone encryption verified by direct DB inspection in test
[ ] Customer code is branch-prefixed, gap-free, concurrency-safe
[ ] QR payload tamper-proof (HMAC verified)
[ ] Branch isolation verified
[ ] phpstan + pint clean

OUTPUT EXPECTED
Show only new files. Confirm checklist.
```

---

## 📌 PHASE 5 — Measurement Management Module (Versioned)

```text
PHASE 5: Measurement Management Module

GOAL
Append-only versioned measurements supporting multiple profiles (fitted/casual/slim/loose),
shirt + pant fields, supervisor approval workflow, and significant-change alerts.
Orders FK to a specific measurement_version_id — never to a mutable row.

BUSINESS REQUIREMENTS
- One customer can have multiple measurement profiles (named, e.g. "Daily fit").
- Every edit creates a new version; previous version is never mutated.
- Versions enter pending_approval if change > configured threshold, else auto-approved.
- Only QC Supervisor / Production Supervisor / Owner can approve.
- Significant-change config per field (e.g., chest ±2 in, length ±1.5 in) in config/measurements.php.
- Shirt fields: chest, waist, hip, shoulder, sleeve_length, shirt_length, collar, cuff,
  arm_round, neck, front_chest, cross_back, dart, bicep, wrist, plus 5 free-text notes.
- Pant fields: waist, hip, thigh, knee, bottom, length, in_seam, out_seam, crotch, fly,
  plus 5 free-text notes.
- Each measurement_version stores who created, who approved, when, and the diff vs prior.

TABLES REQUIRED
- measurement_profiles(id, customer_id FK, family_member_id FK nullable, branch_id FK,
  name, type enum[shirt,pant,both], is_default, created_at, updated_at, deleted_at)
- measurement_versions(id, profile_id FK, version_number,
  status enum[draft,pending_approval,approved,rejected],
  shirt_data JSON nullable, pant_data JSON nullable,
  effective_from, effective_to nullable,
  diff_json nullable, significant_change bool,
  created_by, approved_by nullable, approved_at nullable,
  rejection_reason nullable, created_at, updated_at)
  INDEX: (profile_id, version_number) UNIQUE
  INDEX: (profile_id, status, effective_from)
- measurement_alerts(id, version_id FK, fields_changed JSON, threshold_breached JSON,
  acknowledged_by nullable, acknowledged_at nullable, created_at)

APIS REQUIRED
GET    /api/v1/customers/{cid}/measurements
POST   /api/v1/customers/{cid}/measurements                 create profile
GET    /api/v1/measurements/profiles/{id}/versions          version history
POST   /api/v1/measurements/profiles/{id}/versions          create new version (auto or pending)
POST   /api/v1/measurements/versions/{id}/approve           approve
POST   /api/v1/measurements/versions/{id}/reject            reject (reason required)
GET    /api/v1/measurements/versions/{id}                   show
GET    /api/v1/measurements/pending-approval                supervisor inbox

BACKEND SERVICES REQUIRED
- Measurement/Services/MeasurementService.php
    createProfile(), createVersion(profileId, payload, actor):
      computes diff, flags significant_change, sets status, persists
    approve(versionId, actor): closes prior version's effective_to, sets new approved
    reject(versionId, reason, actor)
- Measurement/Services/SignificantChangeDetector.php
    detect(prevData, newData, config): {fields_changed, threshold_breached, isSignificant}

CONTROLLERS REQUIRED
MeasurementProfileController, MeasurementVersionController, MeasurementApprovalController

FORM REQUESTS REQUIRED
CreateProfileRequest, CreateVersionRequest, ApproveVersionRequest, RejectVersionRequest
(All shirt/pant field rules: numeric, min 0, max 100, optional unless profile.type requires.)

API RESOURCES REQUIRED
MeasurementProfileResource, MeasurementVersionResource (includes diff_json on demand),
PendingApprovalResource

PERMISSIONS REQUIRED
measurements.view, measurements.create, measurements.approve, measurements.reject

SEEDERS REQUIRED
None.

TESTS REQUIRED (write FIRST)
- CreateProfileTest — first version auto-approved (no prior to diff against)
- VersionAppendOnlyTest — updating a version returns 405 / not supported; only new versions
- SignificantChangeTest — chest change of 3 inches > threshold 2 sets significant_change=true
  AND status=pending_approval
- AutoApproveBelowThresholdTest — chest 0.5 in change → status=approved immediately
- ApproveClosesPriorVersionTest — approving v2 sets v1.effective_to = v2.effective_from
- RejectionTest — rejected version cannot become effective; rejection reason required
- OrderUsesVersionIdNotProfileTest — confirms order_items.measurement_version_id FK exists
  and rejects FK to a draft/rejected version
- BranchIsolationOnMeasurementsTest

COMMANDS
php artisan make:migration create_measurement_profiles_table
php artisan make:migration create_measurement_versions_table
php artisan make:migration create_measurement_alerts_table

EDGE CASES
- Concurrent version creates on the same profile must produce sequential, gap-free
  version_numbers — use row-locked profile counter or DB unique constraint with retry.
- Approving an already-approved version returns 409 ALREADY_APPROVED (idempotent).
- Rejecting an approved version is forbidden; corrections go through new version.
- An order in production locked to v3 must NOT be affected if v4 is approved later.
- Null shirt_data + null pant_data is invalid; at least one required matching profile.type.

ACCEPTANCE CHECKLIST
[ ] All measurement tables created
[ ] No code path mutates measurement_versions after creation (verified by phpstan rule or
    test that asserts no setter exists for shirt_data/pant_data on persisted versions)
[ ] All tests green
[ ] Diff computation correct for all shirt + pant fields
[ ] Approval workflow enforced; non-approved versions cannot be referenced by orders
[ ] phpstan + pint clean

OUTPUT EXPECTED
Show only new files. Confirm checklist.
```

---

## 📌 PHASE 6 — Order Core Module

```text
PHASE 6: Order Core Module

GOAL
Create orders with multiple items, each item linked to a measurement_version, idempotent
creation, branch-scoped, derived order status, and a printable job card endpoint.

BUSINESS REQUIREMENTS
- Sources: walk_in, phone, whatsapp, online.
- One order has multiple order_items (shirts/pants). Each item references a specific
  measurement_version_id.
- Each order gets a unique human-friendly order_code per branch (e.g., SSI-HQ-ORD-000123).
- order.status is DERIVED from all item.status (no separate writable column).
- Job card PDF endpoint returns customer + items + measurements + fabric placeholder.
- Edit allowed only before fabric_allocated stage for that item.
- Cancellation: free before any item enters cutting; partial-refund logic deferred to Phase 15.
- Order create is idempotent via Idempotency-Key header.

TABLES REQUIRED
- orders(id, branch_id FK, order_code UNIQUE, customer_id FK,
  source enum, channel_notes, expected_delivery_date,
  delivery_mode enum[pickup,home,courier],
  delivery_charges_paise (integer), notes,
  created_by, updated_by, created_at, updated_at, deleted_at)
- order_items(id, order_id FK, branch_id FK, item_code,
  product_type enum[shirt,pant,combo], quantity int default 1,
  measurement_version_id FK,
  fabric_preference_text, design_notes JSON,
  state varchar(40) (managed by spatie/laravel-model-states),
  cancelled_at nullable, cancel_reason nullable,
  created_at, updated_at)
  INDEX: (branch_id, state), (order_id), (measurement_version_id)
- order_sequences(branch_id, fiscal_year, last_number)

APIS REQUIRED
GET    /api/v1/orders?status=&customer=&from=&to=
POST   /api/v1/orders                          create with items (Idempotency-Key required)
GET    /api/v1/orders/{id}                     show with items, customer, measurements
PUT    /api/v1/orders/{id}                     update header fields only
POST   /api/v1/orders/{id}/items               add item
PUT    /api/v1/orders/{id}/items/{itemId}      edit item (only if state allows)
DELETE /api/v1/orders/{id}/items/{itemId}      cancel item
POST   /api/v1/orders/{id}/cancel              cancel order (all items cancellable)
GET    /api/v1/orders/{id}/job-card.pdf        PDF job card

BACKEND SERVICES REQUIRED
- Order/Services/OrderService.php — createOrder(payload, idempotencyKey, actor),
    addItem, updateItem, cancelItem, cancelOrder
- Order/Services/OrderCodeGenerator.php — row-locked counter per branch per fiscal year
- Order/Services/OrderStatusDeriver.php — given items, returns derived order status
- Order/Services/JobCardPdfRenderer.php — calls Phase 16 PDF service

CONTROLLERS REQUIRED
OrderController, OrderItemController, JobCardController

FORM REQUESTS REQUIRED
CreateOrderRequest (validates each item.measurement_version_id is APPROVED and belongs to
the customer), UpdateOrderRequest, AddItemRequest, UpdateItemRequest, CancelOrderRequest

API RESOURCES REQUIRED
OrderResource, OrderListResource, OrderItemResource, JobCardResource

PERMISSIONS REQUIRED
orders.view, orders.create, orders.update, orders.cancel, orders.print_job_card

SEEDERS REQUIRED
None.

TESTS REQUIRED (write FIRST)
- CreateOrderHappyPathTest — order + items persisted, codes generated, state=draft
- IdempotentCreateOrderTest — same Idempotency-Key returns same order_id; different body
  with same key returns 409 IDEMPOTENCY_CONFLICT
- CannotUseUnapprovedMeasurementTest — FK validation rejects pending/rejected version
- CrossBranchMeasurementRejectedTest
- OrderCodeUniquenessUnderConcurrencyTest — 50 parallel creates produce 50 distinct codes
- DerivedStatusTest — when all items=ready, order.status=ready; one item=cutting, order=in_production
- EditAfterFabricAllocatedTest — 409 INVALID_STATE_FOR_EDIT
- CancelBeforeCuttingTest — items cancelled; CancelAfterQcPassTest — 409

COMMANDS
php artisan make:migration create_orders_table
php artisan make:migration create_order_items_table
php artisan make:migration create_order_sequences_table

EDGE CASES
- Idempotency-Key body hash mismatch → 409 IDEMPOTENCY_CONFLICT.
- Customer soft-deleted while creating order → 422 INVALID_CUSTOMER.
- Order with zero items → 422 ORDER_REQUIRES_ITEM.
- Fiscal year rollover (Apr 1 in India) resets the per-branch counter to 1.

ACCEPTANCE CHECKLIST
[ ] All tests green including concurrency test for order_code
[ ] Order status is computed, never stored as a writable column
[ ] Idempotency works on POST /orders
[ ] Job card PDF generates (Phase 16 may stub the actual rendering)
[ ] phpstan + pint clean

OUTPUT EXPECTED
Show only new files. Confirm checklist.
```

---

## 📌 PHASE 7 — Production Workflow Engine

```text
PHASE 7: Production Workflow Engine

GOAL
Implement the production state machine on order_items using spatie/laravel-model-states.
Transitions are concurrency-safe, idempotent, audited, and emit events for downstream
modules (notifications, dashboards). Kanban board API serves the UI.

BUSINESS REQUIREMENTS
- States: Draft → FabricAllocated → Cutting → Tailoring → KajaButton → Finishing → QC
  → Packing → ReadyForDelivery → Delivered. Branch: QC → Rework → QC.
- Allowed transitions are defined in code; everything else is rejected with
  INVALID_STATE_TRANSITION (409).
- Every transition records actor, from, to, occurred_at, idempotency_key, notes.
- Cancellation can occur before Cutting; after Cutting, only via supervisor approval.
- Kanban board groups items by state with branch_id scope.
- Each transition emits OrderItemStateChanged event for listeners (notification, audit).

TABLES REQUIRED
- order_items.state column already exists (Phase 6).
- production_transitions(id, order_item_id FK, branch_id FK,
  from_state, to_state, actor_id, idempotency_key UNIQUE,
  notes, metadata JSON, occurred_at, created_at)
  INDEX: (order_item_id, occurred_at), (branch_id, occurred_at)
  This table is INSERT-only (DB grant).

APIS REQUIRED
POST /api/v1/production/items/{id}/transition        body: { to, notes, metadata }
                                                    headers: Idempotency-Key required
GET  /api/v1/production/items/{id}/history
GET  /api/v1/production/board                        kanban grouped by state, branch-scoped
GET  /api/v1/production/items/{id}                   item detail with current state

BACKEND SERVICES REQUIRED
- Production/States/{Draft,FabricAllocated,Cutting,Tailoring,KajaButton,Finishing,QC,
    Rework,Packing,ReadyForDelivery,Delivered,Cancelled}.php — spatie state classes
- Production/Services/StateTransitionService.php
    transition(itemId, toState, actor, idempotencyKey, notes, metadata):
      DB::transaction → lockForUpdate on order_item → state machine validates →
      insert production_transitions → set new state → dispatch OrderItemStateChanged event
- Production/Services/KanbanBoardService.php — board(branch, filters) returns grouped data

CONTROLLERS REQUIRED
ProductionTransitionController, KanbanBoardController, ProductionItemController

FORM REQUESTS REQUIRED
TransitionRequest (validates to-state value, requires notes when transitioning to Rework
or Cancelled)

API RESOURCES REQUIRED
ProductionItemResource (current state + allowed next states),
ProductionTransitionResource, KanbanBoardResource

PERMISSIONS REQUIRED
production.view, production.transition.{cutting,tailoring,kaja,finishing,qc,rework,
packing,ready_for_delivery,delivered,cancel}
Map permissions to roles (e.g., Tailor → transition.tailoring → finishing).

SEEDERS REQUIRED
Extend RolePermissionSeeder with these permission rows.

EVENTS / LISTENERS
- Events: OrderItemStateChanged(itemId, from, to, actor, occurredAt, metadata)
- Listeners (queued):
    NotifyOnReadyForDelivery → sends WhatsApp/email (Phase 17)
    AppendActivityLog → audit
    RecomputeOrderDerivedStatus → recomputes order status cache if needed

TESTS REQUIRED (write FIRST)
- ValidTransitionsTest — every allowed edge passes
- InvalidTransitionRejectedTest — e.g., Draft → Delivered → 409 INVALID_STATE_TRANSITION
- ConcurrentTransitionsTest — two simultaneous requests for the same item: one succeeds,
  the other returns 409 (lockForUpdate). Use parallel HTTP test or pcntl_fork.
- IdempotencyOnTransitionTest — same Idempotency-Key returns the original 200 response,
  no duplicate transition row inserted
- TransitionEmitsEventTest — OrderItemStateChanged dispatched once per successful transition
- ProductionHistoryTest — history endpoint returns transitions in occurred_at order
- KanbanBoardScopedToBranchTest

COMMANDS
php artisan make:migration create_production_transitions_table

EDGE CASES
- Item already in target state + same Idempotency-Key → return previous response 200.
- Item already in target state + new key → 409 INVALID_STATE_TRANSITION (it's already there).
- Cancelled is a terminal state; no transitions out.
- Delivered is terminal.
- Rework loop bounded: max 3 visits to Rework before requiring QC Supervisor override
  (override permission: production.rework.override).

ACCEPTANCE CHECKLIST
[ ] All transitions defined in code and enumerable
[ ] production_transitions table is append-only (test attempts UPDATE, expects DB error)
[ ] Concurrency test passes deterministically
[ ] Event dispatched and at least one listener registered (audit)
[ ] Kanban board returns correct grouping under branch scope
[ ] phpstan + pint clean

OUTPUT EXPECTED
Show only new files including all state classes. Confirm checklist.
```

---

## 📌 PHASE 8 — Cutting and Fabric Allocation Module

```text
PHASE 8: Cutting and Fabric Allocation Module

GOAL
Two-phase fabric allocation (reserve → consume), cutting workflow with bundle QR labels,
fabric consumption tracked against the inventory ledger (Phase 11 provides the ledger
table; this phase consumes its API), and concurrency-safe roll locking.

BUSINESS REQUIREMENTS
- Cutting Master sees: order item, measurements, fabric preference, available rolls.
- Allocate fabric to an item = create a RESERVE movement on a fabric_roll.
- Begin Cutting = transitions item Draft/FabricAllocated → Cutting via Phase 7 engine.
- Complete Cutting = converts reserve to CONSUME movement, creates cut_bundles, transitions
  item to Tailoring.
- Cancelling before Cutting = release reservation (matching positive movement).
- Each cut_bundle gets a QR code so tailors scan to start work.
- Fabric reservation reduces "available" (= remaining_metres − sum(active_reserves)),
  not remaining_metres.

TABLES REQUIRED
- fabric_allocations(id, order_item_id FK, fabric_roll_id FK, branch_id FK,
  reserved_metres decimal(8,2), consumed_metres decimal(8,2) nullable,
  status enum[reserved, consumed, released],
  reserved_at, reserved_by, consumed_at nullable, consumed_by nullable,
  released_at nullable, released_by nullable, release_reason nullable,
  idempotency_key UNIQUE, created_at, updated_at)
  INDEX: (order_item_id), (fabric_roll_id, status)
- cut_bundles(id, order_item_id FK, fabric_roll_id FK, branch_id FK,
  bundle_code UNIQUE, qr_payload UNIQUE, pieces_count, notes,
  created_by, created_at, updated_at)
  INDEX: (order_item_id)

APIS REQUIRED
GET    /api/v1/cutting/queue                                items needing fabric/cutting
POST   /api/v1/cutting/items/{id}/allocate-fabric           body: { roll_id, metres }
                                                            Idempotency-Key required
POST   /api/v1/cutting/items/{id}/release-fabric            release reservation (with reason)
POST   /api/v1/cutting/items/{id}/start-cutting             transitions to Cutting
POST   /api/v1/cutting/items/{id}/complete-cutting          body: { actual_metres,
                                                                     bundles:[{pieces}] }
GET    /api/v1/cutting/bundles/{id}                         show bundle
GET    /api/v1/cutting/bundles/by-qr/{payload}              scan QR

BACKEND SERVICES REQUIRED
- Production/Services/FabricAllocationService.php
    reserve(itemId, rollId, metres, actor, key): DB::transaction → lockForUpdate on
      fabric_rolls → check available ≥ metres → insert fabric_allocation(status=reserved)
      → insert fabric_movement(type=reserve) via Inventory module service
    release(allocationId, reason, actor): inverse movement, sets status=released
    consume(allocationId, actualMetres, actor): converts reserve to out; if actualMetres
      < reserved, inserts a small release for the difference; if greater, requires
      Inventory Manager permission and additional check
- Production/Services/CuttingService.php
    startCutting, completeCutting (creates bundles, generates QR payloads, calls
    Phase 7 StateTransitionService to move state)

CROSS-MODULE DEPENDENCY
This module CALLS Inventory/Services/StockLedgerService.php (Phase 11) — never writes to
fabric_movements directly. Define an interface
Inventory/Contracts/StockLedgerInterface.php so Phase 11 implements it.

CONTROLLERS REQUIRED
CuttingQueueController, FabricAllocationController, CuttingActionController, BundleController

FORM REQUESTS REQUIRED
AllocateFabricRequest, ReleaseFabricRequest, CompleteCuttingRequest (bundles array required)

API RESOURCES REQUIRED
CuttingQueueItemResource, FabricAllocationResource, CutBundleResource

PERMISSIONS REQUIRED
fabric.allocate, fabric.release, cutting.start, cutting.complete, bundles.view

SEEDERS REQUIRED
None.

TESTS REQUIRED (write FIRST)
- ReserveReducesAvailableNotRemainingTest
- ConcurrentReservationsTest — two requests for last 5m on the same roll: one succeeds,
  one returns 409 INSUFFICIENT_AVAILABLE_STOCK
- ReleaseRestoresAvailableTest
- CompleteCuttingConsumesAndCreatesBundlesTest
- ActualGreaterThanReservedRequiresPermissionTest
- IdempotentAllocateTest — same key returns prior allocation, no duplicate movement
- CrossBranchRollRejectedTest — cannot reserve from another branch's roll
- BundleQrSignedTest — qr_payload verified by signer (HMAC)

EDGE CASES
- Reserving on a roll that is currently being damaged/written-off (Phase 12) → 409.
- Item already in Cutting + new allocation → 409 ALREADY_ALLOCATED.
- Release after consume → 409.

ACCEPTANCE CHECKLIST
[ ] All tests green
[ ] All fabric writes flow through StockLedgerService (no direct fabric_movements writes
    from this module)
[ ] Concurrency test passes deterministically
[ ] Bundle QR payload tamper-proof
[ ] phpstan + pint clean

OUTPUT EXPECTED
Show only new files including the StockLedgerInterface. Confirm checklist.
```

---

## 📌 PHASE 9 — Tailoring Assignment Module

```text
PHASE 9: Tailoring Assignment Module

GOAL
Assign cut bundles to tailors, track in-progress and completion, compute tailor
performance metrics (daily/weekly/monthly) without N+1, and prevent re-assignment of
already-completed bundles.

BUSINESS REQUIREMENTS
- Production Supervisor assigns bundles to a Tailor.
- Tailor scans bundle QR to start; system records started_at.
- Tailor marks complete; system records completed_at, transitions item Cutting →
  Tailoring → KajaButton via Phase 7.
- A bundle can be re-assigned only if not yet started.
- Performance metrics: pieces completed, avg minutes per piece, on-time %, rework count
  (from Phase 10).
- Daily/weekly/monthly rollups via scheduled jobs (Phase 17 implements scheduler).

TABLES REQUIRED
- tailor_assignments(id, bundle_id FK, order_item_id FK, branch_id FK,
  tailor_id FK (users), assigned_by FK, assigned_at,
  started_at nullable, completed_at nullable,
  status enum[assigned,in_progress,completed,reassigned],
  notes, created_at, updated_at)
  INDEX: (tailor_id, completed_at), (branch_id, status), (bundle_id) UNIQUE WHERE
  status IN ('assigned','in_progress','completed')
- tailor_daily_stats(id, branch_id, tailor_id, on_date,
  bundles_completed, avg_minutes_per_piece, rework_count,
  created_at, updated_at) UNIQUE(branch_id, tailor_id, on_date)

APIS REQUIRED
GET    /api/v1/tailoring/assignments?tailor=&status=
POST   /api/v1/tailoring/assignments                       assign bundle to tailor
POST   /api/v1/tailoring/assignments/{id}/start
POST   /api/v1/tailoring/assignments/{id}/complete
POST   /api/v1/tailoring/assignments/{id}/reassign         only if not yet started
GET    /api/v1/tailoring/performance/{tailorId}?from=&to=

BACKEND SERVICES REQUIRED
- Production/Services/TailorAssignmentService.php — assign, start, complete, reassign
- Production/Services/TailorPerformanceService.php — query rollups; compute on demand if
  rollup row missing

CONTROLLERS REQUIRED
TailorAssignmentController, TailorPerformanceController

FORM REQUESTS REQUIRED
AssignBundleRequest, StartAssignmentRequest, CompleteAssignmentRequest, ReassignRequest

API RESOURCES REQUIRED
AssignmentResource, AssignmentListResource, TailorPerformanceResource

PERMISSIONS REQUIRED
tailoring.assign, tailoring.start, tailoring.complete, tailoring.reassign,
tailoring.performance.view

SEEDERS REQUIRED
None.

TESTS REQUIRED (write FIRST)
- AssignmentHappyPathTest — assigns, starts, completes → state advances
- CannotReassignAfterStartTest
- DuplicateActiveAssignmentRejectedTest — partial unique index enforces one active per bundle
- PerformanceMetricsCorrectTest — given fixtures, returns expected counts and averages
- CrossBranchAssignmentRejectedTest

COMMANDS
php artisan make:migration create_tailor_assignments_table
php artisan make:migration create_tailor_daily_stats_table

EDGE CASES
- Tailor inactive → cannot be assigned new work.
- Completing assignment where bundle's order_item is cancelled → 409.
- Reassign emits an audit entry.

ACCEPTANCE CHECKLIST
[ ] All tests green
[ ] Partial unique index prevents duplicate active assignments on the same bundle
[ ] Performance endpoint returns sub-200ms response on 10k assignments fixture
[ ] phpstan + pint clean

OUTPUT EXPECTED
Show only new files. Confirm checklist.
```

---

## 📌 PHASE 10 — Finishing, QC, and Rework Module

```text
PHASE 10: Finishing, QC, and Rework Module

GOAL
Kaja Button, Ironing/Finishing, QC inspection with bounded rework loop, defect photos
on S3 (or compatible), structured defect categories for analytics.

BUSINESS REQUIREMENTS
- After Tailoring: Kaja Button → Finishing → QC.
- QC dispositions: pass, pass_with_note, rework, reject.
- Rework attempts capped at 3; 4th requires production.rework.override permission.
- Each QC entry can attach multiple defect photos (max 5MB each), stored on disk 's3'
  with signed URL retrieval. Thumbnails generated by queued job.
- Defect categories are a managed table for analytics (most frequent defects).
- Reject is terminal-ish for the item; routes to Cancelled with refund flag for Phase 15.

TABLES REQUIRED
- qc_inspections(id, order_item_id FK, branch_id FK,
  attempt_number int, previous_inspection_id FK nullable,
  disposition enum[pass,pass_with_note,rework,reject],
  inspector_id FK, notes, inspected_at, created_at, updated_at)
  INDEX: (order_item_id, attempt_number)
- defect_categories(id, code UNIQUE, name, is_active, created_at, updated_at)
- qc_defects(id, qc_inspection_id FK, defect_category_id FK, severity enum[minor,major,critical],
  notes, created_at)
- qc_defect_photos(id, qc_defect_id FK, disk, path, thumb_path nullable,
  size_bytes, uploaded_by, created_at)

APIS REQUIRED
POST /api/v1/qc/items/{id}/inspect       body: { disposition, defects:[{category_id,
                                                  severity, notes, photo_ids:[]}], notes }
POST /api/v1/qc/photos                   multipart upload, returns photo_id (pre-inspection)
GET  /api/v1/qc/items/{id}/history
GET  /api/v1/qc/defects/categories       admin manage defect categories
POST /api/v1/qc/items/{id}/rework-override   requires production.rework.override

BACKEND SERVICES REQUIRED
- Production/Services/QcInspectionService.php — inspect(itemId, payload, actor):
    enforce attempt cap; transition state via Phase 7 engine; persist defects
- Production/Services/DefectPhotoService.php — store, generate thumbnail (queued)
- Production/Services/ReworkOverrideService.php

CONTROLLERS REQUIRED
QcInspectionController, QcPhotoController, DefectCategoryController, ReworkOverrideController

FORM REQUESTS REQUIRED
InspectRequest, UploadPhotoRequest (mime jpeg/png/webp, max 5MB), CreateDefectCategoryRequest

API RESOURCES REQUIRED
QcInspectionResource, QcDefectResource (signed URL for photos), DefectCategoryResource

PERMISSIONS REQUIRED
qc.inspect, qc.override, qc.defect_categories.manage

SEEDERS REQUIRED
DefectCategorySeeder — common categories (stitch_open, color_mismatch, size_off,
fabric_damage, button_loose, hem_uneven, etc.)

TESTS REQUIRED (write FIRST)
- InspectionPassTransitionsToPackingTest
- InspectionReworkTransitionsBackTest — item goes back to the appropriate prior stage
- ReworkCapAt3Test — 4th rework without override permission → 403 REWORK_LIMIT
- ReworkOverrideAllowedWithPermissionTest
- DefectPhotoUploadVirusScanStubTest — non-image rejected; oversize rejected
- SignedUrlForPhotoTest — public URL not exposed
- DefectAnalyticsQueryTest — top 5 defect categories last 30 days

COMMANDS
php artisan make:migration create_defect_categories_table
php artisan make:migration create_qc_inspections_table
php artisan make:migration create_qc_defects_table
php artisan make:migration create_qc_defect_photos_table
php artisan db:seed --class=DefectCategorySeeder

EDGE CASES
- Photo uploaded but never referenced by an inspection within 24h → pruned by job.
- Photo size > 5MB → 413 PAYLOAD_TOO_LARGE.
- Disposition "reject" requires a rejection_reason in notes; otherwise 422.

ACCEPTANCE CHECKLIST
[ ] All tests green including override flow
[ ] Photos behind signed URLs only
[ ] Thumbnails generated on a queue (low priority queue)
[ ] Defect categories seeded
[ ] phpstan + pint clean

OUTPUT EXPECTED
Show only new files. Confirm checklist.
```

---

## 📌 PHASE 11 — Inventory, Fabric Roll, Supplier, Purchase Order Module

```text
PHASE 11: Inventory, Fabric Roll, Supplier, Purchase Order Module

GOAL
Append-only stock ledger, fabric rolls with QR codes, suppliers, purchase orders with
GRN, low-stock alerts, and a nightly reconciliation job. This phase is the source of
truth for stock; Phase 8 calls into it via StockLedgerInterface.

BUSINESS REQUIREMENTS
- Fabric roll: roll_code, fabric_type, colour, length_metres (received), supplier,
  unit_price_paise, received_date, rack_location, qr_payload, remaining_metres (cached).
- Stock is an APPEND-ONLY ledger. fabric_rolls.remaining_metres is recomputed inside
  the same transaction as every movement insert. CHECK constraint forbids negative.
- Movement types: receive, reserve, release, out (consume), adjust (in/out, requires
  approval for out), damage_writeoff (Phase 12).
- Low-stock thresholds per fabric_type (configurable, not hard-coded 30/3).
- Nightly job reconciles cached remaining_metres against the ledger sum; mismatches
  alert Owner.
- Suppliers + PO + GRN workflow.

TABLES REQUIRED
- suppliers(id, branch_id FK, code UNIQUE per branch, name, gstin, phone (encrypted),
  email, address, payment_terms, is_active, created_at, updated_at, deleted_at)
- fabric_types(id, code UNIQUE, name, low_stock_threshold_metres decimal(8,2),
  is_active, created_at, updated_at)
- fabric_rolls(id, branch_id FK, roll_code UNIQUE, fabric_type_id FK,
  colour, supplier_id FK nullable, received_length_metres decimal(8,2),
  remaining_metres decimal(8,2) (cached),
  unit_price_paise integer, received_date, rack_location, qr_payload UNIQUE,
  status enum[active,depleted,written_off], created_at, updated_at, deleted_at)
  CHECK (remaining_metres >= 0)
- fabric_movements(id, fabric_roll_id FK, branch_id FK,
  type enum[receive,reserve,release,out,adjust_in,adjust_out,damage_writeoff],
  metres decimal(8,2),   -- positive for additions to remaining, negative for deductions
  reason, reference_type, reference_id,
  idempotency_key UNIQUE, actor_id FK, occurred_at, created_at)
  INDEX: (fabric_roll_id, occurred_at), (branch_id, occurred_at), (reference_type,
  reference_id)
  INSERT-only at DB grant level.
- purchase_orders(id, branch_id FK, po_code UNIQUE, supplier_id FK,
  status enum[draft,placed,partial_received,received,cancelled],
  total_paise, notes, placed_at, created_by, created_at, updated_at)
- purchase_order_items(id, purchase_order_id FK, fabric_type_id, colour,
  quantity_metres, unit_price_paise, received_metres default 0)
- grn(id, purchase_order_id FK, received_at, received_by, notes)
- grn_items(id, grn_id FK, purchase_order_item_id FK, fabric_roll_id FK,
  metres_received decimal(8,2))

APIS REQUIRED
GET    /api/v1/inventory/fabric-rolls?type=&colour=&status=
POST   /api/v1/inventory/fabric-rolls                   manual create (emergency only)
GET    /api/v1/inventory/fabric-rolls/{id}
POST   /api/v1/inventory/fabric-rolls/{id}/adjust       body:{ type:adjust_in|adjust_out,
                                                              metres, reason }
                                                        adjust_out requires owner approval
GET    /api/v1/inventory/fabric-rolls/by-qr/{payload}
GET    /api/v1/inventory/low-stock
GET    /api/v1/inventory/movements?roll_id=&from=&to=

GET    /api/v1/inventory/fabric-types
POST   /api/v1/inventory/fabric-types
PUT    /api/v1/inventory/fabric-types/{id}

GET    /api/v1/inventory/suppliers
POST   /api/v1/inventory/suppliers
PUT    /api/v1/inventory/suppliers/{id}

GET    /api/v1/inventory/purchase-orders
POST   /api/v1/inventory/purchase-orders
POST   /api/v1/inventory/purchase-orders/{id}/place
POST   /api/v1/inventory/purchase-orders/{id}/cancel
POST   /api/v1/inventory/purchase-orders/{id}/receive    creates GRN + fabric rolls +
                                                          receive movements

BACKEND SERVICES REQUIRED
- Inventory/Services/StockLedgerService.php implements StockLedgerInterface
    record(rollId, type, metres, reason, ref, actor, key): one row + cache update,
    wrapped in DB::transaction + lockForUpdate on fabric_rolls
    available(rollId): remaining_metres − sum(active reserves)
- Inventory/Services/FabricRollService.php — create, adjust (calls ledger), getByQr
- Inventory/Services/SupplierService.php — CRUD
- Inventory/Services/PurchaseOrderService.php — draft, place, cancel, receive (transactional
  GRN + rolls creation + receive movements)
- Inventory/Jobs/ReconcileStockJob.php — nightly: for each roll, sum ledger, compare cache
- Inventory/Jobs/LowStockAlertJob.php — daily morning: scans, dispatches notifications

CONTROLLERS REQUIRED
FabricRollController, FabricTypeController, SupplierController, PurchaseOrderController,
LowStockController, MovementController

FORM REQUESTS REQUIRED
CreateFabricRollRequest, AdjustRollRequest (adjust_out requires reason ≥ 10 chars),
CreateSupplierRequest, CreatePoRequest, PlacePoRequest, ReceivePoRequest, etc.

API RESOURCES REQUIRED
FabricRollResource (includes available_metres),
SupplierResource, PoResource, MovementResource, LowStockResource

PERMISSIONS REQUIRED
inventory.view, inventory.fabric_rolls.create, inventory.fabric_rolls.adjust,
inventory.fabric_rolls.adjust_out_approve, inventory.suppliers.manage,
inventory.purchase_orders.create, inventory.purchase_orders.place,
inventory.purchase_orders.receive, inventory.low_stock.view

SEEDERS REQUIRED
FabricTypeSeeder — white(30m), black(3m), navy(3m), grey(3m), etc.

TESTS REQUIRED (write FIRST)
- AppendOnlyMovementsTest — UPDATE on fabric_movements is denied (DB grant or test stub)
- CheckConstraintNegativeBlockedTest — direct attempt to set remaining_metres < 0 fails
- ReceiveCreatesRollAndMovementTest
- AdjustOutRequiresApprovalTest — non-approver gets 403 INVENTORY_APPROVAL_REQUIRED
- AvailableEqualsRemainingMinusReservesTest
- ReconciliationDetectsDriftTest — manually corrupt cache → job flags mismatch
- LowStockAlertFiresAtThresholdTest
- ConcurrentMovementsTest — 10 parallel record() calls produce coherent state

COMMANDS
php artisan make:migration create_fabric_types_table
php artisan make:migration create_suppliers_table
php artisan make:migration create_fabric_rolls_table
php artisan make:migration create_fabric_movements_table
php artisan make:migration create_purchase_orders_table
php artisan make:migration create_purchase_order_items_table
php artisan make:migration create_grn_tables
php artisan make:job ReconcileStockJob
php artisan make:job LowStockAlertJob

EDGE CASES
- Receiving a PO with quantity higher than ordered → flagged, requires owner approval.
- Cancel PO already received → 409.
- Adjust on a written-off roll → 409.
- Reservation against a roll currently in damage write-off pending approval → 409.

ACCEPTANCE CHECKLIST
[ ] All tests green
[ ] fabric_movements has DB-level INSERT-only grant or equivalent enforcement
    (documented in README)
[ ] StockLedgerInterface implemented; Phase 8 references the interface, not the class
[ ] Reconciliation job scheduled (Phase 17 wires the scheduler)
[ ] Low-stock thresholds per fabric_type (configurable, not 30/3 hard-coded)
[ ] phpstan + pint clean

OUTPUT EXPECTED
Show only new files. Confirm checklist.
```

---

## 📌 PHASE 12 — Cloth Damage and Issue Management Module

```text
PHASE 12: Cloth Damage and Issue Management Module

GOAL
Record fabric damage with full context, route write-offs through owner approval, and
deduct stock only on approval — through the same StockLedgerService as everywhere else.

BUSINESS REQUIREMENTS
- Damage report fields: date, order_id (optional), fabric_roll_id, staff_id, stage,
  damage_type, quantity_lost_metres, action_taken, photos.
- Stages: receiving, cutting, tailoring, qc, ironing, packing.
- Damage types: tear, stain, color_bleed, mis_cut, machine_oil, other (free text).
- Owner approval is mandatory before stock is deducted.
- Approved write-off creates a damage_writeoff movement (negative).
- Rejected write-off keeps roll stock untouched and records reason.

TABLES REQUIRED
- damage_reports(id, branch_id FK, fabric_roll_id FK,
  order_id FK nullable, order_item_id FK nullable,
  reported_by FK, stage enum, damage_type enum, damage_type_other nullable,
  quantity_lost_metres decimal(8,2), action_taken,
  status enum[pending,approved,rejected],
  approved_by FK nullable, approved_at nullable, approval_notes nullable,
  rejected_by FK nullable, rejected_at nullable, rejection_reason nullable,
  reported_at, created_at, updated_at)
- damage_report_photos(id, damage_report_id FK, disk, path, thumb_path,
  size_bytes, uploaded_by, created_at)

APIS REQUIRED
POST   /api/v1/damage-reports                       create (pending)
POST   /api/v1/damage-reports/photos                upload (pre-create)
GET    /api/v1/damage-reports?status=&from=&to=
GET    /api/v1/damage-reports/{id}
POST   /api/v1/damage-reports/{id}/approve          owner only; Idempotency-Key required
POST   /api/v1/damage-reports/{id}/reject           owner only (reason required)

BACKEND SERVICES REQUIRED
- Inventory/Services/DamageReportService.php — report, approve (calls StockLedgerService
  with type=damage_writeoff inside one transaction), reject
- Inventory/Services/DamagePhotoService.php — same pattern as QC photos

CONTROLLERS REQUIRED
DamageReportController, DamageReportPhotoController, DamageReportApprovalController

FORM REQUESTS REQUIRED
CreateDamageReportRequest, ApproveDamageRequest, RejectDamageRequest (reason required ≥ 10 chars)

API RESOURCES REQUIRED
DamageReportResource, DamageReportListResource

PERMISSIONS REQUIRED
damage_reports.create, damage_reports.view, damage_reports.approve, damage_reports.reject
Map approve/reject to Owner only (or Owner+Admin if business prefers).

SEEDERS REQUIRED
None.

TESTS REQUIRED (write FIRST)
- CreateDamageReportPendingTest
- NonOwnerCannotApproveTest
- ApprovalDeductsStockTest — fabric_roll remaining_metres reduced by quantity_lost; one
  damage_writeoff movement inserted
- RejectionLeavesStockUntouchedTest
- ApproveOnAlreadyApprovedReportTest — 409 ALREADY_APPROVED (idempotent)
- ApprovalAtomicTest — simulate stockLedger failure; damage_report should NOT be marked
  approved (transaction rolls back together)

COMMANDS
php artisan make:migration create_damage_reports_table
php artisan make:migration create_damage_report_photos_table

EDGE CASES
- quantity_lost_metres > roll.remaining_metres → approval returns 409 INSUFFICIENT_STOCK
  for write-off.
- Damage on a roll already written off → 409.
- Reject after approval → forbidden; corrections via "reverse adjust" (manual ledger
  entry by Owner).

ACCEPTANCE CHECKLIST
[ ] All tests green including atomicity test
[ ] No stock deduction path exists outside StockLedgerService
[ ] Owner-only approval enforced by permission + policy
[ ] phpstan + pint clean

OUTPUT EXPECTED
Show only new files. Confirm checklist.
```

---

## 📌 PHASE 13 — Ready for Delivery Rack System

```text
PHASE 13: Ready for Delivery Rack System

GOAL
Assign a rack slot to each item when it enters ReadyForDelivery, enforce DB-level slot
uniqueness, and free the slot on delivery or cancellation.

BUSINESS REQUIREMENTS
- Each branch maintains a finite set of rack slots (configurable).
- One slot holds one order_item at a time.
- Assignment happens automatically when item transitions to ReadyForDelivery; staff can
  override to a specific slot if needed.
- Slot is released on Delivered or Cancelled transition.
- Uniqueness enforced at DB, not in application code.

TABLES REQUIRED
- rack_slots(id, branch_id FK, slot_code, label, is_active,
  current_order_item_id FK nullable, occupied_at nullable,
  created_at, updated_at)
  UNIQUE(branch_id, slot_code)
  Partial unique index on (branch_id, current_order_item_id) WHERE current_order_item_id IS NOT NULL
- rack_assignments(id, rack_slot_id FK, order_item_id FK, branch_id FK,
  assigned_at, assigned_by FK,
  released_at nullable, released_by FK nullable, release_reason nullable,
  created_at, updated_at)
  Partial unique index: (rack_slot_id) WHERE released_at IS NULL
  Partial unique index: (order_item_id) WHERE released_at IS NULL

APIS REQUIRED
GET    /api/v1/rack/slots                            list with occupancy
POST   /api/v1/rack/slots                            create slot
PUT    /api/v1/rack/slots/{id}                       update label / active
POST   /api/v1/rack/items/{itemId}/assign            body:{ slot_code? } auto-pick if absent
POST   /api/v1/rack/items/{itemId}/release           release current slot
GET    /api/v1/rack/items/{itemId}/current-slot

BACKEND SERVICES REQUIRED
- Delivery/Services/RackSlotService.php
    assign(itemId, slotCode?, actor): DB::transaction → lockForUpdate on slot →
      ensure unoccupied → insert assignment → set current_order_item_id
    release(itemId, reason, actor): set released_at on active assignment,
      null current_order_item_id on the slot

LISTENERS
- OnReadyForDeliveryAssignSlot — auto-assign on state transition (Phase 7 event)
- OnDeliveredOrCancelledReleaseSlot

CONTROLLERS REQUIRED
RackSlotController, RackAssignmentController

FORM REQUESTS REQUIRED
CreateSlotRequest, UpdateSlotRequest, AssignSlotRequest, ReleaseSlotRequest

API RESOURCES REQUIRED
RackSlotResource (with occupant), RackAssignmentResource

PERMISSIONS REQUIRED
rack.view, rack.slots.manage, rack.assign, rack.release

SEEDERS REQUIRED
RackSlotSeeder for seed branch — e.g., R-A-01 through R-A-50.

TESTS REQUIRED (write FIRST)
- AssignReleaseHappyPathTest
- DuplicateSlotAssignmentRejectedAtDbTest — second concurrent assign on same slot →
  unique constraint violation surfaced as 409 RACK_SLOT_OCCUPIED
- OneItemOneActiveSlotTest — assigning a second slot to same item without releasing
  first → 409 ITEM_ALREADY_ASSIGNED
- ReleaseOnDeliveredTest — event listener clears slot
- AutoAssignOnReadyForDeliveryTest — first available slot picked

COMMANDS
php artisan make:migration create_rack_slots_table
php artisan make:migration create_rack_assignments_table

EDGE CASES
- All slots full → auto-assign returns 409 NO_SLOT_AVAILABLE; supervisor must free a slot.
- Manual reassignment to a specific occupied slot → 409.
- Slot deactivated while occupied → 409; staff must release first.

ACCEPTANCE CHECKLIST
[ ] DB uniqueness enforces single-occupancy under concurrency
[ ] Event listeners assign on ready, release on delivered/cancelled
[ ] All tests green
[ ] phpstan + pint clean

OUTPUT EXPECTED
Show only new files. Confirm checklist.
```

---

## 📌 PHASE 14 — Delivery Management Module

```text
PHASE 14: Delivery Management Module

GOAL
Manage pickup, home, and courier deliveries; OTP confirmation; failed-attempt tracking;
delivery charges flow into the invoice (Phase 15 finalises the invoice line).

BUSINESS REQUIREMENTS
- Modes: pickup, home_delivery, courier.
- OTP sent (SMS/WhatsApp) at the moment of dispatch / handover; 6-digit, 10-min expiry,
  max 5 verify attempts.
- Failed delivery attempts recorded with structured reason codes:
  customer_unavailable, wrong_address, refused, payment_pending, other.
- Delivery confirmation: enter OTP → item transitions to Delivered → rack slot released.
- Delivery charges saved on order (Phase 6) and surface separately on invoice.

TABLES REQUIRED
- deliveries(id, order_id FK, branch_id FK,
  mode enum, address_snapshot, courier_partner nullable, tracking_no nullable,
  scheduled_at nullable, dispatched_at nullable, completed_at nullable,
  status enum[scheduled,dispatched,attempted,delivered,failed,cancelled],
  delivery_charges_paise integer, created_by FK, created_at, updated_at)
- delivery_attempts(id, delivery_id FK, attempted_at, attempted_by FK,
  reason_code enum, reason_notes, created_at)
- delivery_otps(id, delivery_id FK, otp_hash, expires_at, attempts int default 0,
  used_at nullable, created_at)
  INDEX (delivery_id, used_at)

APIS REQUIRED
GET    /api/v1/deliveries?status=&from=&to=
POST   /api/v1/deliveries                          create
POST   /api/v1/deliveries/{id}/dispatch            triggers OTP
POST   /api/v1/deliveries/{id}/confirm             body:{ otp } → marks delivered
POST   /api/v1/deliveries/{id}/attempt             body:{ reason_code, notes }
POST   /api/v1/deliveries/{id}/cancel              with reason

BACKEND SERVICES REQUIRED
- Delivery/Services/DeliveryService.php — create, dispatch (generates OTP), confirm
  (verifies OTP, transitions items to Delivered via Phase 7), attempt, cancel
- Delivery/Services/OtpService.php — generate (6-digit), hash, verify (constant-time),
  expire
- Shared/Services/NotificationDispatcher.php — abstract send(channel, to, payload)

CONTROLLERS REQUIRED
DeliveryController, DeliveryAttemptController, DeliveryConfirmationController

FORM REQUESTS REQUIRED
CreateDeliveryRequest, DispatchRequest, ConfirmDeliveryRequest, RecordAttemptRequest,
CancelDeliveryRequest

API RESOURCES REQUIRED
DeliveryResource, DeliveryAttemptResource

PERMISSIONS REQUIRED
deliveries.view, deliveries.create, deliveries.dispatch, deliveries.confirm,
deliveries.attempt, deliveries.cancel

SEEDERS REQUIRED
None.

TESTS REQUIRED (write FIRST)
- DispatchGeneratesOtpTest — otp_hash stored, raw otp not stored
- ConfirmWithCorrectOtpTransitionsItemsTest — items to Delivered, rack released
- WrongOtpIncrementsAttemptsTest — 5th wrong → 423 LOCKED, requires re-dispatch
- ExpiredOtpRejectedTest
- AttemptRecordedWithReasonTest
- ConfirmTwiceIdempotentTest — second confirm with same Idempotency-Key returns prior result
- CourierModeRecordsTrackingTest

COMMANDS
php artisan make:migration create_deliveries_table
php artisan make:migration create_delivery_attempts_table
php artisan make:migration create_delivery_otps_table

EDGE CASES
- Confirm without dispatch → 409 NOT_DISPATCHED.
- Confirm on cancelled delivery → 409.
- OTP must be hashed (never store plaintext). Use Hash::make with a fast algo or HMAC.

ACCEPTANCE CHECKLIST
[ ] All tests green
[ ] OTPs hashed at rest; raw OTP only transmitted via notification channel
[ ] Items transition to Delivered atomically with rack release
[ ] phpstan + pint clean

OUTPUT EXPECTED
Show only new files. Confirm checklist.
```

---

## 📌 PHASE 15 — Finance, Invoice, Payment Module

```text
PHASE 15: Finance, Invoice, Payment Module

GOAL
Gap-free GST invoice numbering per branch per fiscal year, append-only payment ledger,
outstanding balance computation, credit notes for corrections, and tight access control
(Owner/Admin/Accountant only).

BUSINESS REQUIREMENTS
- Invoice number sequence: per (branch, fiscal_year), gap-free, monotonic. No MAX()+1.
- Invoices append-only; corrections via credit_notes.
- Payment methods: cash, upi, bank_transfer (extensible).
- One order → one or more invoices; one invoice → many payments.
- Outstanding balance per order = sum(invoice totals) − sum(allocated payments).
- Sensitive fields (UPI ID, bank account) encrypted at rest.
- Only Owner/Admin/Accountant can view finance endpoints.

TABLES REQUIRED
- invoice_sequences(branch_id, fiscal_year, last_number) PRIMARY KEY(branch_id, fiscal_year)
- invoices(id, branch_id FK, invoice_no UNIQUE (per branch+fy), order_id FK,
  customer_id FK, gst_treatment enum[regular,composition,unregistered],
  subtotal_paise, cgst_paise, sgst_paise, igst_paise,
  delivery_charges_paise, discount_paise, total_paise,
  issued_at, issued_by FK, status enum[issued,partially_paid,paid,credited],
  pdf_path nullable, created_at, updated_at)
  INDEX (branch_id, issued_at)
- invoice_lines(id, invoice_id FK, order_item_id FK nullable,
  description, hsn_code, quantity, unit_price_paise, taxable_paise,
  gst_rate decimal(5,2), tax_paise)
- payments(id, branch_id FK, invoice_id FK, method enum,
  amount_paise, reference_no, paid_at, recorded_by FK,
  upi_id (encrypted) nullable, bank_account_last4 (plaintext) nullable,
  idempotency_key UNIQUE, created_at)
  INSERT-only at DB grant level.
- credit_notes(id, branch_id FK, credit_no UNIQUE (per branch+fy),
  invoice_id FK, reason, total_paise, issued_at, issued_by FK, created_at)

APIS REQUIRED
GET    /api/v1/finance/invoices?from=&to=&status=        (Owner/Admin/Accountant)
POST   /api/v1/finance/invoices                          generate from order
GET    /api/v1/finance/invoices/{id}
GET    /api/v1/finance/invoices/{id}/pdf
POST   /api/v1/finance/invoices/{id}/credit-note         body:{ reason, total }
GET    /api/v1/finance/payments?invoice_id=
POST   /api/v1/finance/payments                          Idempotency-Key required
GET    /api/v1/finance/orders/{id}/outstanding-balance
GET    /api/v1/finance/dashboard/summary                 (cached)

BACKEND SERVICES REQUIRED
- Finance/Services/InvoiceNumberService.php
    next(branchId, fiscalYear): DB::transaction → lockForUpdate on invoice_sequences row
    → increment → return number. Initialise row if absent.
- Finance/Services/InvoiceService.php — create, finalize, attach PDF (Phase 16),
  issueCreditNote
- Finance/Services/PaymentService.php — record (idempotent), reconcile invoice status
- Finance/Services/BalanceService.php — outstandingForOrder, outstandingForCustomer
- Finance/Services/GstCalculator.php — given lines + treatment, computes CGST/SGST/IGST

CONTROLLERS REQUIRED
InvoiceController, PaymentController, CreditNoteController, FinanceDashboardController

FORM REQUESTS REQUIRED
CreateInvoiceRequest, IssueCreditNoteRequest, RecordPaymentRequest

API RESOURCES REQUIRED
InvoiceResource, InvoiceLineResource, PaymentResource, CreditNoteResource,
OutstandingBalanceResource

PERMISSIONS REQUIRED
finance.view, finance.invoice.create, finance.payment.record, finance.credit_note.issue,
finance.dashboard.view

SEEDERS REQUIRED
None.

TESTS REQUIRED (write FIRST)
- GapFreeNumberingUnderConcurrencyTest — 100 parallel invoice creates → numbers 1..100,
  no duplicates, no gaps
- FiscalYearRolloverResetsCounterTest — Apr 1 IST starts at 1 again under (branch, fy)
- InvoiceImmutableTest — UPDATE on invoices.invoice_no denied
- PaymentIdempotentTest — same key returns prior payment
- BalanceComputationTest — invoice total − sum(payments) − credit_notes total
- GstCalculationTest — given subtotal + rate, CGST+SGST correct (intra-state) vs IGST
  (inter-state)
- RbacFinanceForbiddenForOthersTest — Tailor gets 403 on /finance/*
- CreditNoteCreatesCreditNoTest — gap-free per (branch, fy)
- UpiIdEncryptedAtRestTest

COMMANDS
php artisan make:migration create_invoice_sequences_table
php artisan make:migration create_invoices_table
php artisan make:migration create_invoice_lines_table
php artisan make:migration create_payments_table
php artisan make:migration create_credit_notes_table

EDGE CASES
- Generating invoice on cancelled order → 409.
- Payment exceeding outstanding balance → 422 PAYMENT_EXCEEDS_BALANCE (unless treated
  as advance — configurable).
- Credit note total exceeding invoice total → 422.

ACCEPTANCE CHECKLIST
[ ] Concurrency test for invoice numbering passes deterministically (use multiple processes)
[ ] All write paths idempotent
[ ] payments + invoices are INSERT-only at DB grant level (documented in README)
[ ] Finance endpoints gated behind Owner/Admin/Accountant
[ ] Encrypted casts verified by direct DB inspection in test
[ ] phpstan + pint clean

OUTPUT EXPECTED
Show only new files. Confirm checklist.
```

---

## 📌 PHASE 16 — Printing, QR Code, PDF Module

```text
PHASE 16: Printing, QR Code, PDF Module

GOAL
Centralized PDF rendering (job card, measurement card, invoice, packing slip) and QR
code generation (customer, order, fabric roll, bundle). Rendering happens asynchronously
on the queue for heavy documents; immediate for small documents.

BUSINESS REQUIREMENTS
- PDF documents: job_card, measurement_card, gst_invoice, packing_slip, delivery_receipt.
- QR codes: customer, order, fabric_roll, cut_bundle, rack_slot.
- All PDFs are stored on disk 's3' (or local in dev) with signed URLs and content-hash
  filenames for cacheability.
- Large PDFs (multi-page) generated on a queue; small PDFs synchronous.
- QR payloads always signed (HMAC) so scans are tamper-evident.

TABLES REQUIRED
- documents(id, branch_id FK, kind enum[job_card,measurement_card,gst_invoice,
  packing_slip,delivery_receipt], reference_type, reference_id,
  disk, path, content_hash, size_bytes, generated_by, generated_at, created_at)
  UNIQUE (kind, reference_type, reference_id, content_hash) — dedupes regenerations.

APIS REQUIRED
GET  /api/v1/documents/{id}/download              signed URL
POST /api/v1/documents/regenerate                 body:{ kind, reference_id }
GET  /api/v1/qr/sign?type=&id=                    server-side signed payload generator
GET  /api/v1/qr/decode/{payload}                  verify and decode

BACKEND SERVICES REQUIRED
- Shared/Services/PdfRenderer.php — render(view, data, filename): generates via DomPDF
  or Browsershot, computes content_hash, stores on disk, returns Document row
- Shared/Services/QrCodeGenerator.php — generate(payload): PNG bytes
- Shared/Services/QrPayloadSigner.php (already in Phase 4 — reused here)
- Shared/Jobs/RenderPdfJob.php — async wrapper for heavy PDFs

CONTROLLERS REQUIRED
DocumentController, QrCodeController

FORM REQUESTS REQUIRED
RegenerateDocumentRequest, SignPayloadRequest, DecodePayloadRequest

API RESOURCES REQUIRED
DocumentResource (with signed URL TTL 10 min)

PERMISSIONS REQUIRED
documents.view, documents.regenerate, qr.sign, qr.decode

SEEDERS REQUIRED
None.

VIEWS (Blade templates)
resources/views/pdfs/{job_card,measurement_card,gst_invoice,packing_slip,delivery_receipt}.blade.php

TESTS REQUIRED (write FIRST)
- JobCardRenderTest — given order, produces PDF, document row created, signed URL works
- ContentHashDedupesTest — re-rendering identical input doesn't duplicate file
- SignedUrlExpiresTest — TTL respected
- QrSignAndDecodeRoundtripTest — sign(p) → decode → p; tampered fails
- LargeInvoiceQueuedTest — invoice with > 50 lines goes to queue, status pending,
  notification emitted on completion

COMMANDS
composer require simplesoftwareio/simple-qrcode
composer require barryvdh/laravel-dompdf      # or spatie/browsershot if Puppeteer available
php artisan make:migration create_documents_table

EDGE CASES
- Disk 's3' misconfigured → controlled 503 with code STORAGE_UNAVAILABLE.
- Browser rendering fonts missing → fallback to DomPDF + log warning.
- QR payload version field included so future changes don't break old QRs.

ACCEPTANCE CHECKLIST
[ ] All five PDF kinds render under fixtures
[ ] QR signing round-trip test passes
[ ] Documents accessible only via signed URLs
[ ] Heavy PDFs queue correctly via Horizon
[ ] phpstan + pint clean

OUTPUT EXPECTED
Show only new files including Blade templates as concise summaries (NOT full HTML —
list sections only). Confirm checklist.
```

---

## 📌 PHASE 17 — Reports, Dashboard, Notifications Module

```text
PHASE 17: Reports, Dashboard, Notifications Module

GOAL
Pre-computed rollups for fast dashboards, on-demand reports with Excel/PDF export
running as queued jobs, scheduled jobs (reconciliation, low-stock, daily summaries),
and a notification dispatcher for WhatsApp and email.

BUSINESS REQUIREMENTS
- Dashboard is fast on 100k+ rows because it reads from rollup tables, not OLTP joins.
- Heavy reports (Excel) generate on the queue and email/notify the requester with a
  signed download URL.
- Scheduled jobs:
    nightly: ReconcileStockJob, ProductionRollupJob, TailorDailyStatsJob,
              BackupHealthCheck
    every morning: LowStockAlertJob, OutstandingBalanceDigest
    on event: NotifyOnReadyForDelivery, NotifyOnDelivered, NotifyOnPaymentReceived
- WhatsApp via Business API; email via SMTP. Both rate-limited per provider quota.

TABLES REQUIRED
- daily_branch_stats(branch_id, on_date, orders_received, orders_delivered,
  revenue_paise, defects, ...) UNIQUE(branch_id, on_date)
- report_jobs(id, branch_id FK, kind, params JSON, status enum[pending,running,
  succeeded,failed], document_id FK nullable, error, requested_by, requested_at,
  completed_at, created_at, updated_at)
- notifications(id, branch_id FK, channel enum[whatsapp,email,sms],
  recipient, payload JSON, status enum[queued,sent,failed], reference_type,
  reference_id, attempt_count, sent_at nullable, error nullable, created_at, updated_at)

APIS REQUIRED
GET  /api/v1/dashboard/summary                       branch-scoped, cached 60s
GET  /api/v1/reports                                 list available report kinds
POST /api/v1/reports/run                             body:{ kind, params } → report_job
GET  /api/v1/reports/jobs/{id}                       poll status
GET  /api/v1/reports/jobs/{id}/download              signed URL when succeeded
GET  /api/v1/notifications?status=&channel=

BACKEND SERVICES REQUIRED
- Reporting/Services/DashboardService.php — reads rollups, caches per (branch, role, range)
- Reporting/Services/ReportRunner.php — dispatches report kind to handler
- Reporting/Reports/{OrdersReport,FabricConsumptionReport,TailorPerformanceReport,
  FinanceSummaryReport,DefectAnalyticsReport}.php — each implements ReportInterface
- Reporting/Jobs/ProductionRollupJob.php, TailorDailyStatsJob.php, DailyBranchStatsJob.php
- Notification/Services/NotificationDispatcher.php — send(channel, to, payload)
- Notification/Channels/WhatsappChannel.php, EmailChannel.php — implement
  NotificationChannelInterface

CONTROLLERS REQUIRED
DashboardController, ReportController, ReportJobController, NotificationController

FORM REQUESTS REQUIRED
RunReportRequest

API RESOURCES REQUIRED
DashboardResource, ReportJobResource, NotificationResource

PERMISSIONS REQUIRED
dashboard.view, reports.run, reports.view, notifications.view

SEEDERS REQUIRED
None.

QUEUES (Horizon config)
- high: payments, order creation listeners, finance jobs
- default: PDF render, GRN, low-stock alerts
- low: thumbnails, notifications, analytics rollups
Set retry counts (3) and exponential backoff per job class.

SCHEDULER (app/Console/Kernel.php)
- ReconcileStockJob nightly 02:00 IST
- DailyBranchStatsJob nightly 02:30 IST
- TailorDailyStatsJob nightly 02:45 IST
- LowStockAlertJob daily 08:00 IST
- OutstandingBalanceDigest weekly Mon 09:00 IST
- PruneStaleIdempotencyKeysJob hourly
- PruneOrphanQcPhotosJob daily

TESTS REQUIRED (write FIRST)
- DashboardReadsRollupsTest — endpoint does not query OLTP joins (assert via DB query log)
- ReportJobLifecycleTest — pending → running → succeeded with document
- ReportFailedRecordsErrorTest
- ScheduledJobsRegisteredTest — Kernel exposes expected schedule
- NotificationSentIdempotentTest — same reference triggers one send under deduplication
- WhatsappRateLimitedTest — exceeds limit → queued for retry, not lost

COMMANDS
composer require laravel/horizon
composer require maatwebsite/excel
php artisan horizon:install
php artisan make:migration create_daily_branch_stats_table
php artisan make:migration create_report_jobs_table
php artisan make:migration create_notifications_table

EDGE CASES
- Horizon dashboard gated behind Owner/Admin Gate.
- Report params validated strictly (no arbitrary SQL via params).
- Notification retries: 3 attempts with exponential backoff (1m, 5m, 30m).
- WhatsApp template messages must use pre-approved template IDs.

ACCEPTANCE CHECKLIST
[ ] Dashboard sub-200ms on 100k-row fixture
[ ] Reports run on queue and produce documents
[ ] Scheduler registered and tested
[ ] Horizon gate restricts access
[ ] phpstan + pint clean

OUTPUT EXPECTED
Show only new files. Confirm checklist.
```

---

## 📌 PHASE 18 — Audit Log, Security, Testing, Deployment Hardening

```text
PHASE 18: Audit Log, Security, Testing, Deployment Hardening

GOAL
Lock down auditability, security, and deployment readiness. Activity log on every key
model, append-only DB grants, login audit, secrets policy, zero-downtime deploy
documentation, backup + tested restore, observability, and full test coverage gates.

BUSINESS REQUIREMENTS
- spatie/laravel-activitylog on Customer, Order, OrderItem, Measurement (read-only edits
  not allowed; still logs creates), FabricRoll, Invoice, Payment, DamageReport, DeliveryAttempt.
- Activity log + production_transitions + fabric_movements + payments are append-only at
  the database grant level. App DB user has INSERT only on these tables (no UPDATE/DELETE).
- Security headers (CSP-API, HSTS, X-Content-Type-Options, X-Frame-Options) on all responses.
- Secrets in vault (AWS Secrets Manager / Doppler / encrypted .env). .env never committed.
- Deployment: zero-downtime via Envoyer / Deployer / GitHub Actions; migrations reversible
  and tested on staging snapshot.
- Backups: daily DB dump + S3 sync; monthly automated restore drill into a temp DB and
  health check; alert on failure.
- Observability: Sentry (errors + performance), structured JSON logs with request_id,
  health check endpoint, slow-query log enabled in staging.
- Coverage gates: 100% on state transitions + stock ledger + invoice numbering,
  90% on finance, 70% overall, enforced in CI.

TABLES REQUIRED
- None new beyond Spatie's activity_log (already published).

APIS REQUIRED
GET /api/v1/audit/activities?subject_type=&subject_id=&actor=&from=&to=    Owner/Admin
GET /api/v1/audit/transitions/{order_item_id}                              Owner/Admin/Supervisor

BACKEND SERVICES REQUIRED
- Shared/Services/AuditService.php — wrapper around activitylog for business audit entries
- Shared/Middleware/SecurityHeaders.php
- Shared/Console/Commands/BackupVerify.php — restore drill into temp DB, run sanity checks

CONTROLLERS REQUIRED
AuditController

FORM REQUESTS REQUIRED
ListActivitiesRequest

API RESOURCES REQUIRED
ActivityResource

PERMISSIONS REQUIRED
audit.view, audit.transitions.view

SEEDERS REQUIRED
None.

TESTS REQUIRED (write FIRST)
- ActivityLoggedOnCriticalChangesTest — create/update on each key model produces a row
- AppendOnlyGrantTest (integration) — attempt UPDATE/DELETE on activity_log,
  fabric_movements, production_transitions, payments → fails at DB grant
- SecurityHeadersPresentTest — every /api response includes documented headers
- HealthEndpointDeepCheckTest — DB + Redis + queue (Phase 1 baseline)
- BackupRestoreDrillTest (CI-scheduled) — restores last night's dump into temp DB,
  runs assertions: customer count > 0, no negative stock, no orphan invoices
- CoverageGateTest — CI fails if coverage below targets

COMMANDS
composer require sentry/sentry-laravel
php artisan sentry:publish --dsn=...
php artisan make:command BackupVerify

DEPLOYMENT DOCS (README appendices)
- Zero-downtime deploy steps (Envoyer/Deployer/GHA atomic symlink swap)
- Migration safety rules (online migrations, never destructive in same release as code
  that depends on the old column, two-deploy pattern for renames/drops)
- Secrets policy (no plaintext .env in repo, rotate per quarter, encrypted at rest)
- Disaster recovery runbook (RPO 24h, RTO 4h, named contacts)
- On-call rotation (initial: solo, with paging via Sentry → email/WhatsApp)

DB GRANT POLICY (documented + applied in seed SQL for prod user)
- App user: SELECT/INSERT/UPDATE/DELETE on most tables.
- App user: SELECT/INSERT only on: activity_log, fabric_movements,
  production_transitions, payments, qc_inspections, delivery_attempts.
- Owner-only migration user: full DDL.

EDGE CASES
- Rotating Sanctum personal access token secret revokes all tokens (planned, documented).
- A failed nightly backup blocks the next day's reconciliation until acknowledged.
- Append-only grants must NOT be applied in local dev (else tests can't reset DB);
  applied only in staging + production via deploy script.

ACCEPTANCE CHECKLIST
[ ] Activity log captures all key model changes; verified by feature tests
[ ] Append-only grants applied in staging/prod, documented in README
[ ] Security headers present on every /api response
[ ] Sentry capturing errors + performance traces
[ ] Backups daily; monthly restore drill scheduled and tested
[ ] Coverage gates in CI: 100% / 90% / 70%
[ ] Slow query log enabled in staging
[ ] Zero-downtime deploy procedure documented and rehearsed in staging
[ ] phpstan + pint clean

OUTPUT EXPECTED
Show only new files. List CI workflow changes as a diff summary. Confirm checklist.
```

---

## 📌 PHASE 19 — Final Backend Architecture Review (run AFTER all 18 phases)

```text
PHASE 19: Final Backend Architecture Review

GOAL
Independently review the implemented Laravel backend against the 20 quality areas defined
below and produce a Production Readiness Score out of 100, with concrete remediation.

REVIEW AREAS — for each, deliver: What is correct, What is risky, What must be fixed,
Recommended improvement, Priority (High/Medium/Low).

 1. Modular monolith structure (boundaries, cross-module joins, autoload)
 2. API-first architecture (versioning, OpenAPI docs, idempotency, error codes,
    request_id propagation)
 3. Authentication and authorization (Sanctum, 2FA, token expiry, role-change token
    revocation)
 4. Role and permission coverage (branch-scoped, approval permissions separate from edit)
 5. Multi-branch support (global scope, Owner bypass, indexes)
 6. Customer and measurement design (encrypted PII, versioned measurements, approval flow)
 7. Order workflow (per-item state, derived order status, cancellation rules)
 8. Production stage transition safety (state machine, lockForUpdate, idempotency)
 9. Inventory stock accuracy (append-only ledger, denormalized cache, CHECK constraint,
    reconciliation)
10. Fabric roll locking and two-phase reservation
11. QC and rework correctness (bounded attempts, override, defect analytics)
12. Delivery and rack slot correctness (DB-level uniqueness, OTP hashing)
13. Finance data security (gap-free invoice numbering, append-only payments, encrypted
    PII, role gates)
14. Reports and dashboard performance (rollups, caching, eager loading enforced)
15. Audit log completeness (Spatie + service-layer logs, append-only grants)
16. Validation and error handling (BaseFormRequest, domain exceptions, machine-readable
    codes)
17. Test coverage (state machine 100%, stock ledger 100%, finance 90%, overall 70%)
18. Database indexes (composite, partial, slow query log review)
19. Queue and scheduler setup (Horizon queues separated, retry/backoff, idempotent jobs)
20. Deployment readiness (zero-downtime, secrets, backup + tested restore, observability)

DELIVERABLES
- Markdown report with 20 sections in the format above
- A prioritized fix list (High / Medium / Low) with effort estimates (S/M/L)
- A Production Readiness Score / 100 with explicit breakdown by area
- "Top 7 to fix before go-live" list with the exact files/commits to change

TDD WORKFLOW
1. Generate a baseline coverage report. Note gaps below targets.
2. For each review area, write or identify the test that proves the property holds.
   If the test fails, that's a "must fix" finding. If it passes, that's evidence of
   "what is correct."
3. For new gaps discovered, write a minimal failing test BEFORE recommending the fix —
   so the team has a regression check ready.

OUTPUT EXPECTED
Final review document only. Do not modify code in this phase. Cite specific file paths
and test names as evidence. Where evidence is missing, mark "needs verification" rather
than guessing.
```

---

## 📦 How to use this pack

1. **One phase at a time.** Open a fresh AI chat session per phase to keep context fresh and tokens predictable.
2. **Paste the Master Prompt first.** Then paste the phase prompt below it. Send.
3. **Let the AI follow the TDD workflow.** It will write failing tests, implement the minimum fix, and confirm green.
4. **Acceptance checklist gate.** Don't move to the next phase until the current phase's checklist is 100% green.
5. **Commit between phases.** Tag the commit with the phase number (`v0.1-phase-3`, etc.) so you can roll back cleanly.
6. **Run Phase 19 only after Phase 18 is green** — it's the audit, not the build.

## ⚠ Important warnings baked in

- Stock is a ledger, never a counter (Phase 11). Everything else calls the ledger.
- Measurements are append-only versioned (Phase 5). Orders FK to versions, never profiles.
- Invoice numbers come from a row-locked counter (Phase 15). No `MAX()+1` ever.
- State machines on `order_items`, not `orders` (Phase 7). Order status is derived.
- Branch isolation is a global scope + DB column (Phase 3). Not a per-query `where`.
- All write endpoints require an `Idempotency-Key` header (Phase 2). No exceptions.
- Audit, payments, ledger, transitions are INSERT-only at the DB grant level (Phase 18).
- 2FA mandatory for Owner / Admin / Accountant (Phase 3).
- Rack slot uniqueness enforced at DB, not in code (Phase 13).

If your AI assistant tries to skip any of these, point it back at the Master Prompt's "Critical Invariants" section.
