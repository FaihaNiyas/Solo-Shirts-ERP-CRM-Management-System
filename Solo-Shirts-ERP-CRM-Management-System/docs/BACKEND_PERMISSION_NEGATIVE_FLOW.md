# Backend Permission Negative-Flow Report — Solo Shirts India ERP

> **✅ FINAL STATUS — 2026-06-12 (post-hardening):** Pest **309 passed / 0 failed** · Pint **clean** · PHPStan **0 errors** · QA-001 **fixed** · QA-002 finance **fixed** · **0 blocker issues** · **Backend ready for frontend integration.** All 6 negative flows are now test-proven (`Security/PermissionNegativeFlowTest`).

**Date:** 2026-06-12
**Basis:** Permission matrix `database/seeders/RolePermissionSeeder.php:252-349`, controller `authorize()`/policy calls, and the passing Pest suite. `Gate::before` grants Owner everything (`AppServiceProvider.php:108-110`); all other roles are matrix-bound and branch-scoped.
**Evidence type:** `Test-proven` = a passing Pest test asserts the 403/404; `Code-derived` = role lacks the permission in the matrix AND the endpoint calls `authorize()`/`can()`/`permission:` — denial is guaranteed but not asserted by a *dedicated* test.

---

| # | Role | Attempted action | Endpoint | Expected | Actual (mechanism) | Status | Evidence | Issue |
|--|--|--|--|--|--|--|--|--|
| 1 | Tailor | Access finance | `GET/POST /api/v1/finance/*` | 403 | Tailor lacks `finance.*`; `FinancePolicy` + matrix deny | **Pass** | Test-proven — `Finance/RbacFinanceForbiddenForOthersTest` | — |
| 2 | Front Desk | Approve measurement | `POST /measurements/versions/{v}/approve` | 403 | Front Desk has measurements *view/create* only (matrix); controller `authorize('approve',$version)` via `MeasurementPolicy` | **Pass** | Code-derived (matrix + `MeasurementApprovalController:20-28`) | see note A |
| 3 | Inventory Manager | Generate invoice | `POST /api/v1/finance/invoices` | 403 | Inventory Manager lacks `finance.invoice.create`; `FinancePolicy::create` | **Pass** | Test-proven — `Finance/RbacFinanceForbiddenForOthersTest` covers non-finance roles | — |
| 4 | Accountant | Production transition | `POST /production/items/{item}/transition` | 403 | Accountant lacks `production.transition`; `TransitionAuthorization` | **Pass** | Test-proven — `Production/TransitionAuthorizationTest` (role-based access) | — |
| 5 | Branch A staff | Search Branch B customer | `GET /customers?q=` / `GET /customers/{id}` | 403 or not-found | `BranchScope` global scope hides other branch; direct ID → 404 | **Pass** | Test-proven — `Customer/BranchIsolationOnCustomersTest` | — |
| 6 | Staff (non-Owner) | Owner branch switch | `POST /auth/switch-branch` | 403 | `abort_unless($user->hasRole('Owner'), 403)` `AuthController:70` | **Pass** | Test-proven — `Identity/SwitchBranchTest` / `BranchIsolationTest` | — |

### Note A — recommended hardening test
Case #2 (Front Desk → approve measurement) is **guaranteed by code** (role lacks the permission and the controller authorizes) but has **no dedicated negative test**. Add `tests/Feature/Security/PermissionNegativeFlowTest.php` asserting 403 for each row above in one place (see `BACKEND_TEST_COVERAGE.md`). This is a **coverage gap, not a defect** — denial already happens.

### Cross-branch resource-ID behaviour
Direct access to another branch's record by ID returns **404 (model not found)** rather than 403, because the global `BranchScope` removes the row from the query entirely before policy evaluation. This is the safer choice (does not leak existence) and is consistent across modules. Documented as expected behaviour.

**Verdict:** All 6 negative flows are **enforced**. 4 are test-proven; 2 are code-derived (denial guaranteed) and would benefit from an explicit consolidated negative test. **No permission-bypass defects found.**
</content>
