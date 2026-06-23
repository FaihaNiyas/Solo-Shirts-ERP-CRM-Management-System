# Backend Branch Isolation Report — Solo Shirts India ERP

> **✅ FINAL STATUS — 2026-06-12 (post-hardening):** Pest **309 passed / 0 failed** · Pint **clean** · PHPStan **0 errors** · QA-001 **fixed** · QA-002 finance **fixed** · **0 blocker issues** · **Backend ready for frontend integration.**

**Date:** 2026-06-12
**Mechanism:** A global `BranchScope` is auto-applied to every transactional model via the `BelongsToBranch` trait; the active branch comes from `BranchContext`, which returns staff's home `branch_id` (immutable) or the Owner's per-token `active_branch_id` override.

| Component | File | Behaviour |
|---|---|---|
| Trait | `app/Modules/Shared/Traits/BelongsToBranch.php:24-34` | `addGlobalScope(new BranchScope)`; auto-stamps `branch_id` on create |
| Scope | `app/Modules/Shared/Scopes/BranchScope.php:20-27` | `where(branch_id = current)`; no-op when context is null (Owner, all-branches) |
| Context | `app/Modules/Shared/Services/BranchContext.php:20-55` | staff → home branch; Owner → null or token override |
| Switch | `app/Modules/Identity/Http/Controllers/Api/V1/AuthController.php:66-75` | `abort_unless(Owner)`; sets token `active_branch_id` only |
| Middleware | `app/Modules/Identity/Http/Middleware/ResolveBranchContext.php:21-43` | pins Spatie permission team to `branch_id`; seeds active branch |

---

## Isolation Test Scenario

| Step | Actor | branch_id | Endpoint | Expected | Actual | Status |
|--|--|--|--|--|--|--|
| 1 | Branch A staff | A | `POST /customers` | customer created, `branch_id=A` auto-stamped | Auto-stamp via trait | **Pass** |
| 2 | Branch B staff | B | `GET /customers?q=<A-code>` | not found (scoped out) | `BranchScope` excludes A rows | **Pass** |
| 3 | Branch B staff | B | `GET /customers/{A-id}` | 403/404 | **404** (row removed by scope before policy) | **Pass** |
| 4 | Owner | switch→A | `POST /auth/switch-branch {A}` then `GET /customers` | sees Branch A data | token `active_branch_id=A`; context returns A | **Pass** |
| 5 | Owner | switch→B | `POST /auth/switch-branch {B}` then `GET /customers` | sees Branch B data | context returns B | **Pass** |
| 6 | Branch B staff | B | `POST /auth/switch-branch` | 403 | `abort_unless(Owner)` | **Pass** |

**Evidence (passing tests):**
- `tests/Feature/Identity/BranchIsolationTest.php` — cross-branch user visibility + Owner exceptions
- `tests/Feature/Identity/SwitchBranchTest.php` — Owner branch context switching scopes reads
- `tests/Feature/Customer/BranchIsolationOnCustomersTest.php` — cross-branch customer hiding
- `tests/Feature/Measurement/BranchIsolationOnMeasurementsTest.php` — profiles/versions hidden cross-branch
- `tests/Feature/Cutting/CrossBranchRollRejectedTest.php` — cross-branch fabric roll/item rejected
- `tests/Feature/Tailoring/CrossBranchAssignmentRejectedTest.php` — cross-branch bundle/tailor rejected

## Notes & residual risk
- **Owner all-branches default:** when an Owner has not switched, `BranchContext::current()` is null and the scope is a **no-op** (Owner sees all branches). This is intended; ensure reports/exports honour an explicit branch filter when needed.
- **Unscoped queries:** `CustomerService::assertPhoneUnique` deliberately calls `withoutGlobalScope(BranchScope::class)` but **re-applies `where('branch_id', $branchId)`** (`CustomerService:144-158`) — safe. Any future `withoutGlobalScope` usage must likewise re-pin the branch; flag in code review.

**Verdict:** Branch isolation is **correctly enforced and well-tested across modules**. No isolation defects found.
</content>
