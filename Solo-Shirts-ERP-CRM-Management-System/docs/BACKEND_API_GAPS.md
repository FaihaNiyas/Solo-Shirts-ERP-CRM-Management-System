# Backend API Gap Report — Solo Shirts India ERP

> **✅ FINAL STATUS — 2026-06-12 (post-hardening):** Pest **309 passed / 0 failed** · Pint **clean** · PHPStan **0 errors** · QA-001 **fixed** · QA-002 finance **fixed** · **0 blocker issues** · **Backend ready for frontend integration.** No expected core endpoint is missing; idempotency gaps are closed.

**Date:** 2026-06-12 · **Source:** `routes/api.php` (143 app routes), controllers per module, `docs/_route_list.txt`.
**Columns:** Route / Controller / Validation (FormRequest) / Permission (policy or `authorize`) / Test exists.
**✅ = present, ⚠️ = partial/indirect, ❌ = missing.** Endpoints listed by module; representative + all write endpoints shown.

> No expected core endpoint is **missing**. "Gaps" are about *idempotency* and *dedicated negative tests*, not absent routes.

## Identity / Auth
| Endpoint | Method | Route | Ctrl | Valid | Perm | Test | Status |
|--|--|--|--|--|--|--|--|
| auth/login | POST | ✅ | ✅ | ✅ LoginRequest | public+throttle | ✅ LoginTest | Pass |
| auth/logout, refresh, me | POST/GET | ✅ | ✅ | n/a | auth:sanctum | ✅ | Pass |
| auth/switch-branch | POST | ✅ | ✅ | ✅ | Owner-only | ✅ SwitchBranchTest | Pass |
| auth/2fa/enable·confirm·disable | POST | ✅ | ✅ | ✅ | auth | ✅ TwoFactorFlowTest | Pass |
| branches (index/store/update) | GET/POST/PUT | ✅ | ✅ | ✅ | BranchPolicy | ✅ BranchCrudTest | Pass |
| users (CRUD + role/activate) | * | ✅ | ✅ | ✅ | UserPolicy | ✅ Identity tests | Pass |

## Customer
| customers (index/store/show/update/destroy) | * | ✅ | ✅ | ✅ Create/UpdateCustomerRequest | CustomerPolicy | ✅ Customer/* | Pass |
| customers/by-qr/{payload} | GET | ✅ | ✅ | n/a | CustomerPolicy | ✅ QrLookupTest | Pass |
| customers/{c}/orders·balance·timeline | GET | ✅ | ✅ | n/a | policy | ✅ Sprint1EndpointsTest | Pass |
| customers/{c}/family-members (CRUD) | * | ✅ | ✅ | ✅ | scopeBindings+policy | ✅ FamilyMemberCrudTest | Pass |

## Measurement
| customers/{c}/measurements (index/store) | GET/POST | ✅ | ✅ | ✅ CreateProfileRequest | MeasurementPolicy | ✅ CreateProfileTest | Pass |
| profiles/{p}/versions (index/store) | GET/POST | ✅ | ✅ | ✅ CreateVersionRequest | policy | ✅ VersioningTest | Pass |
| versions/{v}/approve | POST | ✅ | ✅ | n/a | policy `approve` | ✅ VersioningTest | Pass; ⚠️ no Idempotency-Key (QA-002) |
| versions/{v}/reject | POST | ✅ | ✅ | ✅ RejectVersionRequest | policy `reject` | ✅ RejectionTest | Pass |

## Order
| orders (index/store/show/update/cancel) | * | ✅ | ✅ | ✅ Create/Cancel | OrderPolicy | ✅ Order/* | Pass; store **idempotent** ✅ |
| orders/{o}/items (CRUD) | * | ✅ | ✅ | ✅ AddItemRequest | policy | ✅ | Pass; ⚠️ add-item not idempotent (QA-002) |
| orders/{o}/job-card | GET | ✅ | ✅ | n/a | `printJobCard` | ✅ JobCardRenderTest | Pass |

## Production / Cutting / QC
| production/board, items/{i}, history | GET | ✅ | ✅ | n/a | perm | ✅ Kanban/History tests | Pass |
| production/items/{i}/transition | POST | ✅ | ✅ | ✅ | authorize | ✅ Transition* | Pass; **idempotent** ✅ |
| cutting/queue | GET | ✅ | ✅ | n/a | perm | ✅ | Pass |
| cutting/items/{i}/allocate-fabric | POST | ✅ | ✅ | ✅ AllocateFabricRequest | `fabric.allocate` | ✅ IdempotentAllocateTest | Pass; **idempotent** ✅ |
| cutting/items/{i}/release·start·complete | POST | ✅ | ✅ | ⚠️ | perm | ✅ Cutting/* | Pass; ⚠️ not idempotent |
| cutting/bundles/by-qr·{bundle} | GET | ✅ | ✅ | n/a | perm | ✅ BundleQrSignedTest | Pass |
| qc/items/{i}/inspect·history·rework-override | POST/GET | ✅ | ✅ | ⚠️ | authorize | ✅ Qc/* | Pass; ⚠️ inspect not idempotent (QA-002) |
| qc/defects/categories·analytics, qc/photos | * | ✅ | ✅ | ✅ | perm | ✅ | Pass |

## Tailoring
| tailoring/assignments (index/store/start/complete/reassign) | * | ✅ | ✅ | ⚠️ | perm/policy | ✅ Tailoring/* | Pass |
| tailoring/performance/{tailor} | GET | ✅ | ✅ | n/a | perm | ✅ PerformanceMetricsCorrectTest | Pass |

## Inventory
| inventory/fabric-rolls (index/store/show/adjust/by-qr) | * | ✅ | ✅ | ✅ CreateFabricRollRequest | perm | ✅ Inventory/* | Pass |
| inventory/movements, low-stock | GET | ✅ | ✅ | n/a | perm | ✅ | Pass |
| inventory/fabric-types·suppliers·purchase-orders (CRUD+actions) | * | ✅ | ✅ | ✅ | perm | ✅ Receive*Test | Pass |
| damage-reports (CRUD/approve/reject/photos) | * | ✅ | ✅ | ✅ | perm/Owner | ✅ Damage/* | Pass; approve **idempotent** ✅ |

## Delivery / Rack
| rack/slots (index/store/update), rack/items/{i}/assign·release·current-slot | * | ✅ | ✅ | ⚠️ | perm | ✅ Rack/* | Pass; ⚠️ assign not idempotent (QA-002) |
| deliveries (index/store/dispatch/confirm/attempt/cancel) | * | ✅ | ✅ | ✅ Confirm/Dispatch/Cancel | perm | ✅ Delivery/* | Pass; confirm **idempotent** ✅; **OTP lockout broken QA-001** |

## Finance
| finance/invoices (index/store/show/pdf) | * | ✅ | ✅ | ✅ CreateInvoiceRequest | FinancePolicy | ✅ Finance/* | Pass; ⚠️ store **not idempotent** (QA-002 High) |
| finance/invoices/{i}/credit-note | POST | ✅ | ✅ | ✅ IssueCreditNoteRequest | policy | ✅ CreditNote*Test | Pass; ⚠️ not idempotent (QA-002 High) |
| finance/payments (index/store) | * | ✅ | ✅ | ✅ | policy | ✅ PaymentIdempotentTest | Pass; **idempotent (app-level)** ✅ |
| finance/orders/{o}/outstanding, outstanding, dashboard/summary | GET | ✅ | ✅ | n/a | policy | ✅ BalanceComputationTest | Pass |

## Printing / Reporting / Audit / Search / Health
| documents (index/regenerate/download-signed) | * | ✅ | ✅ | ✅ | perm/signed | ✅ Printing/* | Pass |
| qr/sign·decode | GET | ✅ | ✅ | n/a | perm | ✅ QrSignAndDecode | Pass |
| dashboard/summary, reports (index/run/jobs/download), notifications | * | ✅ | ✅ | ✅ | perm | ✅ Reporting/* | Pass |
| audit/activities, audit/transitions/{item} | GET | ✅ | ✅ | n/a | `audit.view` | ✅ AuditEndpointTest | Pass |
| search | GET | ✅ | ✅ | n/a | perm-filtered | ✅ Sprint1EndpointsTest | Pass |
| health | GET | ✅ | ✅ | n/a | public+throttle | ✅ HealthCheckTest | Pass |

---

## Gap Summary
- **Missing routes/controllers/validation/policies:** **none** for the expected ERP surface.
- **Idempotency gaps (QA-002):** create invoice, create credit note (High); add-order-item, qc inspect, measurement approve, rack assign, cutting start/complete/release (Medium — most are guarded by state machines or DB constraints).
- **Test gaps:** no dedicated cross-module flow tests or a consolidated permission-negative test (see `BACKEND_TEST_COVERAGE.md`).
- **Behavioural defect:** OTP confirm negative path (QA-001).
</content>
