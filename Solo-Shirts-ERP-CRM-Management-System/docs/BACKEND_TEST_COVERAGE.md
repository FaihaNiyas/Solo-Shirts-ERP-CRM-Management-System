# Backend Test Coverage Report — Solo Shirts India ERP

> **✅ FINAL STATUS — 2026-06-12 (post-hardening):** Pest **309 passed / 0 failed** · Pint **clean** · PHPStan **0 errors** · QA-001 **fixed** · QA-002 finance **fixed** · **0 blocker issues** · **Backend ready for frontend integration.**

**Date:** 2026-06-12
**Suite:** 119 test files (117 Feature + 2 Unit). Last clean run: **309 passed, 0 failed, 1222 assertions, 279s.** (Baseline was 278 passed / 2 failed.)

> **Update 2026-06-12 (hardening to 100):** added — `Security/PermissionNegativeFlowTest` (6), `Shared/IdempotencyFullFlowTest` (5), `Flow/FullFrontDeskToDeliveryFlowTest` (1/37 assertions), `Order/IdempotentAddItemTest` (2), `Shared/NoUnintendedOnUpdateTimestampsTest` (1 schema-invariant guard), `Shared/CodeGeneratorTest` (3 unit), `Order/OrderStatusDeriverTest` (5 unit). Shared payload helpers (`orderPayload`, `invoicePayload`) centralised in `tests/Pest.php`.
**Shared helpers:** `tests/Pest.php` (`seedRoles`, `makeBranch/User`, `productionItem`, `fabricRoll`, `deliverableOrder`, `makeDelivery/Invoice`, `dispatchDelivery/confirmDelivery`, `bearer`), `tests/Support/FakeNotificationDispatcher.php` (captures OTPs).

## Coverage by Module

| Module | Required tests | Existing tests | Missing tests | Priority |
|--|--|--|--|--|
| Identity/Auth/2FA/Branch | login, 2fa, throttle, token expiry, role-revoke, switch, isolation | 7 ✅ | — | — |
| Shared (envelope/idem/health/exc) | envelope, validation, idempotency, health, domain-exc | 4 ✅ | universal-idempotency assertion | Med |
| Customer | create, dup-phone, encryption, code, QR, search, isolation | 7 ✅ | — | — |
| Measurement | profile, versioning, append-only, approve, reject, usable-for-order, isolation | 6 ✅ | — | — |
| Order | create, idempotent-create, measurement-validation, lifecycle | 4 ✅ | — | — |
| Production | valid/invalid transitions, append-only, idempotency, concurrency, rework-bound, auth, event, history, kanban | 10 ✅ | — | — |
| Cutting | allocate, idempotent, concurrent, release, reserve, consume, bundle-qr, over-consume, cross-branch | 8 ✅ | — | — |
| QC | inspect-pass, rework, defect-photo, analytics, signed-url | 5 ✅ | — | — |
| Tailoring | assign, reassign, cross-branch, duplicate-active, metrics | 5 ✅ | — | — |
| Inventory | append-only, available-formula, check-constraint, concurrent, low-stock, receive, reconcile, adjust-approval | 8 ✅ | — | — |
| Damage | create, approval-flow, atomic, photo | 4 ✅ | — | — |
| Finance | invoice-gen, immutable, gap-free, fy-rollover, concurrency, gst, balance, payment-idempotent, credit-note, rbac, upi-encrypt | 10 ✅ | **invoice/credit-note idempotency** | High |
| Delivery | dispatch-otp, confirm-otp, confirm-idempotent, wrong-otp, expired-otp, attempt, courier | 7 ✅ | **wrong-otp FAILING (QA-001)** | **Blocker** |
| Rack | assign-release, auto-assign, duplicate-slot, one-active, release-on-delivered | 5 ✅ | — | — |
| Printing | all-kinds, dedup, job-card, large-queued, qr-roundtrip, signed-url-expiry | 6 ✅ | — | — |
| Reporting | dashboard-rollups, notification-idempotent, report-failed, lifecycle, scheduled-jobs, whatsapp-ratelimit | 6 ✅ | — | — |
| Audit | activity-logged, append-only-grant, endpoint | 3 ✅ | — | — |
| Security | backup-drill, health-deep, headers | 3 ✅ | — | — |
| Alignment | sprint-1 endpoints | 1 ✅ | — | — |
| Unit / pure-logic | ApiResponse, example, **CodeGenerator (3), OrderStatusDeriver (5)** | ✅ added | — | — |

**Coverage is broad and deep** on invariants (append-only, immutability, concurrency, branch isolation, idempotency, RBAC, state machines). The weak spots are **end-to-end multi-module flows** and a **consolidated negative-permission test**.

---

## Recommended Test Files (from the QA brief) — existence check

| Recommended flow test | Status | Closest existing coverage |
|--|--|--|
| `Flow/FullFrontDeskToDeliveryFlowTest.php` | ✅ **Added (green)** | end-to-end customer→delivery, 37 assertions |
| `Flow/OwnerDashboardFlowTest.php` | ❌ Missing | `Reporting/DashboardReadsRollupsTest` |
| `Flow/CustomerQrFlowTest.php` | ❌ Missing | `Customer/QrLookupTest` |
| `Flow/MeasurementApprovalFlowTest.php` | ⚠️ Partial | `Measurement/VersioningTest` |
| `Flow/OrderToProductionFlowTest.php` | ⚠️ Partial | `Order/CreateOrderTest` + `Production/ValidTransitionsTest` |
| `Flow/CuttingStockReservationFlowTest.php` | ⚠️ Partial | `Cutting/IdempotentAllocateTest` + `CompleteCutting…Test` |
| `Flow/TailoringQcReworkFlowTest.php` | ⚠️ Partial | `Tailoring/AssignmentHappyPathTest` + `Qc/ReworkFlowTest` |
| `Flow/FinanceInvoicePaymentFlowTest.php` | ⚠️ Partial | `Finance/InvoiceGenerationTest` + `PaymentIdempotentTest` |
| `Flow/RackDeliveryFlowTest.php` | ⚠️ Partial | `Rack/AssignReleaseHappyPathTest` + `Delivery/ConfirmWithCorrectOtp…Test` |
| `Flow/ReportsAuditFlowTest.php` | ⚠️ Partial | `Reporting/ReportJobLifecycleTest` + `Audit/ActivityLogged…Test` |
| `Security/BranchIsolationFlowTest.php` | ⚠️ Partial | `Identity/BranchIsolationTest` + `Customer/BranchIsolationOnCustomersTest` |
| `Security/PermissionNegativeFlowTest.php` | ✅ **Added (green)** | 6 consolidated 403/404 denial cases |
| `Shared/IdempotencyFullFlowTest.php` | ✅ **Added (green)** | order/invoice/credit-note/payment replay + conflict + missing-key |

## Priority recommendations
1. ~~**Blocker:** fix `Delivery/WrongOtpIncrementsAttemptsTest` (QA-001)~~ — ✅ done (now passing, with an `expires_at`-stability assertion).
2. ~~**High:** add invoice/credit-note idempotency tests~~ — ✅ done (`InvoiceIdempotentTest`, `CreditNoteIdempotentTest`).
3. ~~**Med:** add `Security/PermissionNegativeFlowTest` and `Shared/IdempotencyFullFlowTest`~~ — ✅ done.
4. ~~**Low:** add end-to-end `Flow/*` journey test~~ — ✅ done. ~~unit tests for `CodeGenerator` and `OrderStatusDeriver`~~ — ✅ done. **Optional (nice-to-have):** the remaining `Flow/*` per-stage journeys (Owner dashboard, cutting-stock, tailoring-qc-rework) for extra regression depth — current coverage already exercises each stage piecemeal + one full end-to-end journey.
</content>
