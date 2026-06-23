# Solo Shirts India ERP — Per-Role Scenario Catalogue

**Date:** 2026-06-12 · **Source of truth:** `database/seeders/RolePermissionSeeder.php` (the `MATRIX`). **Verification:** full suite **309 passed / 0 failed**.
**How to read:** for each of the 14 roles — who they are, what they **✅ CAN** do (allowed scenarios with endpoint), and what they **🚫 CANNOT** do (returns **403**). All staff are **branch-bound**; only **Owner** crosses branches. Owner is granted everything via `Gate::before` (no row in the matrix).

> Mechanics: permission denial → **403** standard envelope (`success:false`, `code`, `request_id`). Cross-branch data → **404** (the `BranchScope` removes the row before policy, so existence never leaks). Idempotent writes need an `Idempotency-Key` (🔑).

---

## Capability matrix (at a glance)

`✅` = allowed · `–` = 403 · `R` = read-only (`.view` but not act)

| Capability | Owner | Admin | Front Desk | Measure Staff | Prod Supervisor | Cutting Master | Tailor | Kaja Button | QC Supervisor | Ironing Master | Re-Worker | Inventory Mgr | Accountant | Delivery Staff |
|---|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|
| Users / roles admin | ✅ | ✅ | – | – | – | – | – | – | – | – | – | – | – | – |
| Branch create/update | ✅ | R | – | – | – | – | – | – | – | – | – | – | – | – |
| **Switch branch** | ✅ | – | – | – | – | – | – | – | – | – | – | – | – | – |
| Customers view/create | ✅ | ✅ | ✅ | R | – | – | – | – | – | – | – | – | R | – |
| Measurement create | ✅ | ✅ | – | ✅ | – | – | – | – | – | – | – | – | – | – |
| Measurement **approve** | ✅ | ✅ | – | – | ✅ | – | – | – | ✅ | – | – | – | – | – |
| Orders create/cancel | ✅ | ✅ | ✅ | – | – | – | – | – | – | – | – | – | R | – |
| Production board | ✅ | ✅ | R | – | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | – | ✅ |
| Prod transitions | ✅ | all | – | – | all | alloc/cut | tailoring | kaja | qc/rework/pack | finishing | rework | – | – | ready/deliv |
| Fabric allocate / cutting | ✅ | ✅ | – | – | ✅ | ✅ | – | – | – | – | – | ✅ | – | – |
| Tailoring assign | ✅ | ✅ | – | – | ✅ | – | work only | – | – | – | – | – | – | – |
| QC inspect | ✅ | ✅ | – | – | ✅ | – | – | – | ✅ | – | – | – | – | – |
| Inventory / PO | ✅ | ✅ | – | – | – | – | – | – | – | – | – | ✅ | – | – |
| Damage **approve** | ✅ | ✅ | – | – | create | – | – | – | – | – | – | create | – | – |
| Rack assign/release | ✅ | ✅ | – | – | ✅ | – | – | – | – | – | – | – | – | ✅ |
| Delivery dispatch/confirm | ✅ | ✅ | – | – | ✅ | – | – | – | – | – | – | – | – | ✅ |
| **Finance** | ✅ | ✅ | – | – | – | – | – | – | – | – | – | – | ✅ | – |
| Reports / dashboard | ✅ | ✅ | – | – | ✅ | – | – | – | – | – | – | – | ✅ | – |
| Audit log | ✅ | ✅ | – | – | transitions | – | – | – | – | – | – | – | – | – |
| Printing / QR | ✅ | ✅ | ✅ | – | ✅ | – | – | – | – | – | – | – | – | – |

---

## 1. Owner
**Is:** proprietor. **Lands:** global dashboard (all branches). **Holds:** everything (`Gate::before`). **Only role that crosses branches.**
| ✅ Allowed scenario | Endpoint |
|--|--|
| Do anything any role can do | every `/api/v1/*` |
| Switch active branch, then see that branch's data | `POST /auth/switch-branch` → reads scoped to it |
| Create/update branches, manage users & roles | `POST/PUT /branches`, `/users…` |
| Approve damage write-offs (owner-grade) | `POST /damage-reports/{d}/approve` 🔑 |

**🚫 Cannot:** nothing is forbidden. **Edge:** 2FA mandatory in prod; the `['*']` token is powerful — keep 2FA on.

## 2. Admin
**Is:** branch manager. **Holds:** everything **except** branch-switch and branch create/update is effectively Owner-only; Admin is **branch-bound**.
| ✅ Allowed | Endpoint |
|--|--|
| Manage users & roles, view branches | `/users…`, `GET /branches` |
| Full customer→order→production→QC→rack→delivery→finance within the branch | all module writes |
| Approve measurements, approve damage, run finance, read audit | `/measurements/{v}/approve`, `/damage-reports/{d}/approve` 🔑, `/finance/*`, `/audit/*` |
| **🚫 Switch branch** | `POST /auth/switch-branch` → **403** |
| **🚫 Create/update a branch** | `POST/PUT /branches` → **403** (needs `branches.create/update`, Owner-only) |
| **🚫 See another branch's data** | direct ID → **404** |

## 3. Front Desk
**Is:** reception. **Lands:** front desk. **Holds:** customers (view/create/update), family members, measurements **view-only**, orders (create/cancel/job-card), production **view**, printing.
| ✅ Allowed | Endpoint |
|--|--|
| Create / search customer, scan QR | `POST /customers`, `GET /customers?q=`, `/by-qr/{p}` |
| Manage family members | `/customers/{c}/family-members` |
| Create order, add item, cancel, print job card | `POST /orders` 🔑, `/orders/{o}/items` 🔑, `/cancel`, `GET /job-card` |
| View production board, sign/scan QR | `GET /production/board`, `/qr/*` |
| **🚫 Approve a measurement** | `POST /measurements/{v}/approve` → **403** *(PermissionNegativeFlowTest)* |
| **🚫 Touch finance / record payment** | `/finance/*` → **403** |
| **🚫 Run a production transition / allocate fabric** | `/production/items/{i}/transition`, `/cutting/…` → **403** |
| **🚫 Switch branch** | `/auth/switch-branch` → **403** *(PermissionNegativeFlowTest)* |

## 4. Measurement Staff
**Is:** measurement specialist. **Holds:** customers **view**, measurements **view + create** (not approve).
| ✅ Allowed | Endpoint |
|--|--|
| View customer, view measurement profiles/versions | `GET /customers/{c}`, `/measurements/…` |
| Create a profile / new version (pending) | `POST /customers/{c}/measurements`, `/profiles/{p}/versions` |
| **🚫 Approve / reject own version** | `/measurements/{v}/approve|reject` → **403** (separation of duties) |
| **🚫 Create order / customer** | `POST /orders`, `POST /customers` → **403** |
| **🚫 Finance, production, delivery** | those routes → **403** |

## 5. Production Supervisor
**Is:** shop-floor lead. **Holds:** measurements approve/reject, **all** production transitions + rework override, fabric (allocate/release/over-consume), cutting, tailoring (assign/start/complete/reassign), qc inspect/override, damage create, rack, **all deliveries**, printing, reporting, `audit.transitions.view`.
| ✅ Allowed | Endpoint |
|--|--|
| Approve measurements | `POST /measurements/{v}/approve` 🔑 |
| Drive any production transition; override rework | `POST /production/items/{i}/transition` 🔑 |
| Allocate/release/over-consume fabric, start/complete cutting | `/cutting/…` 🔑 |
| Assign/start/complete/reassign tailoring; view performance | `/tailoring/…` |
| Inspect QC, override | `POST /qc/items/{i}/inspect` |
| Manage rack, create/dispatch/confirm deliveries | `/rack/…`, `/deliveries/…` 🔑 |
| Run reports, view item transition audit | `/reports/*`, `GET /audit/transitions/{i}` |
| **🚫 Finance (invoice/payment)** | `/finance/*` → **403** |
| **🚫 Approve damage / adjust-out / POs** | `/damage-reports/{d}/approve`, inventory POs → **403** |
| **🚫 Manage users / switch branch** | `/users…`, `/auth/switch-branch` → **403** |

## 6. Cutting Master
**Is:** cutting room. **Holds:** production view, transitions **fabric_allocated + cutting**, fabric allocate/release (no over-consume), cutting start/complete, bundles.
| ✅ Allowed | Endpoint |
|--|--|
| See cutting queue & board | `GET /cutting/queue`, `/production/board` |
| Allocate fabric (reserve), release | `POST /cutting/items/{i}/allocate-fabric` 🔑, `/release-fabric` |
| Start & complete cutting → bundles | `/start-cutting`, `/complete-cutting` |
| Transition draft→fabric_allocated, →cutting | `POST /production/items/{i}/transition` 🔑 |
| Scan bundle QR | `GET /cutting/bundles/by-qr/{p}` |
| **🚫 Over-consume beyond reserve** | `/complete-cutting` over-amount → **403** (needs `fabric.over_consume`) *(ActualGreaterThanReservedRequiresPermissionTest)* |
| **🚫 Tailoring / QC / finance / later transitions** | those routes → **403** |

## 7. Tailor
**Is:** stitching. **Holds:** production view, transition **tailoring**, bundles, tailoring **start/complete** (not assign/reassign).
| ✅ Allowed | Endpoint |
|--|--|
| Start / complete own assignment | `POST /tailoring/assignments/{a}/start|complete` |
| Transition into tailoring; view bundles | `/production/items/{i}/transition` 🔑, `/cutting/bundles/{b}` |
| **🚫 Start another tailor's assignment** | `/start` → **403/409** *(AssignmentHappyPathTest)* |
| **🚫 Assign / reassign work** | `POST /tailoring/assignments`, `/reassign` → **403** |
| **🚫 Access finance** | `/finance/*` → **403** *(PermissionNegativeFlowTest)* |

## 8. Kaja Button
**Is:** buttonhole/button station. **Holds:** production view, transition **kaja** only.
| ✅ Allowed | Endpoint |
|--|--|
| Receive items at kaja_button, transition →finishing | `POST /production/items/{i}/transition` 🔑 (`to: finishing`) |
| View board | `GET /production/board` |
| **🚫 Any other transition / cutting / qc / finance** | → **403** (only `production.transition.kaja`) |

## 9. QC Supervisor
**Is:** quality gate. **Holds:** measurements approve/reject, production view, transitions **qc/rework/packing/cancel**, rework override, qc inspect/override, defect categories.
| ✅ Allowed | Endpoint |
|--|--|
| Inspect: pass→packing / reject→cancelled / rework | `POST /qc/items/{i}/inspect` |
| Override rework limit | `POST /qc/items/{i}/rework-override` |
| Manage defect categories; view analytics | `/qc/defects/*` |
| Approve/reject measurements | `/measurements/{v}/approve|reject` 🔑 |
| Cancel an item | `/production/items/{i}/transition` (`to: cancelled`) 🔑 |
| **🚫 Allocate fabric / cut / assign tailoring** | those routes → **403** |
| **🚫 Finance / delivery / inventory** | → **403** |

## 10. Ironing Master
**Is:** finishing/ironing. **Holds:** production view, transition **finishing** only.
| ✅ Allowed | Endpoint |
|--|--|
| Transition finishing→qc | `POST /production/items/{i}/transition` 🔑 (`to: qc`) |
| View board | `GET /production/board` |
| **🚫 Everything else** | → **403** |

## 11. Re-Worker
**Is:** rework station. **Holds:** production view, transition **rework** only.
| ✅ Allowed | Endpoint |
|--|--|
| Transition rework→qc (after fixing) | `POST /production/items/{i}/transition` 🔑 (`to: qc`) |
| View board | `GET /production/board` |
| **🚫 Inspect QC / override / anything else** | → **403** (override is QC's, not Re-Worker's) |

## 12. Inventory Manager
**Is:** stores. **Holds:** production view, fabric allocate/release/over-consume, bundles, inventory (rolls/suppliers/POs/low-stock, **not** adjust-out approve), damage create/view.
| ✅ Allowed | Endpoint |
|--|--|
| Create/adjust fabric rolls, view movements & low-stock | `/inventory/fabric-rolls…`, `/movements`, `/low-stock` |
| Manage suppliers; place/receive POs → roll+GRN | `/inventory/suppliers`, `/purchase-orders/{po}/place|receive` |
| Raise a damage report | `POST /damage-reports` |
| **🚫 Approve adjust-out** | `/fabric-rolls/{r}/adjust` (out) → needs approver → **403** *(AdjustOutRequiresApprovalTest)* |
| **🚫 Approve a damage report** | `/damage-reports/{d}/approve` → **403** (owner-grade) |
| **🚫 Generate an invoice** | `POST /finance/invoices` → **403** *(PermissionNegativeFlowTest)* |

## 13. Accountant
**Is:** finance. **Lands:** finance dashboard (2FA enforced). **Holds:** customers.view, orders.view, **all finance**, all reporting.
| ✅ Allowed | Endpoint |
|--|--|
| Create invoice (GST, gap-free, idempotent) | `POST /finance/invoices` 🔑 |
| Record payment; issue credit note | `POST /finance/payments` 🔑, `/credit-note` 🔑 |
| View outstanding, finance dashboard, run reports | `/finance/outstanding`, `/dashboard/summary`, `/reports/*` |
| View customers & orders (read-only) | `GET /customers`, `GET /orders` |
| **🚫 Run a production transition** | `/production/items/{i}/transition` → **403** *(PermissionNegativeFlowTest)* |
| **🚫 Create a customer / order** | `POST /customers`, `POST /orders` → **403** |
| **🚫 Switch branch** | → **403** |

## 14. Delivery Staff
**Is:** dispatch/handover. **Holds:** production view, transitions **ready_for_delivery + delivered**, rack view/assign/release, **all deliveries**.
| ✅ Allowed | Endpoint |
|--|--|
| Assign/release rack slot, check current slot | `/rack/items/{i}/assign|release|current-slot` |
| Create / dispatch (OTP) / confirm (OTP) delivery | `/deliveries` , `/dispatch`, `/confirm` 🔑 |
| Record a failed attempt; cancel; courier tracking | `/deliveries/{d}/attempt|cancel`, `{mode:courier}` |
| Transition item ready_for_delivery / delivered | `/production/items/{i}/transition` 🔑 |
| **🚫 Confirm with a wrong/expired OTP** | `/confirm` → 422 `OTP_INVALID`/`OTP_EXPIRED`, 5-try → 423 `OTP_LOCKED` *(WrongOtpIncrementsAttemptsTest)* |
| **🚫 Finance / earlier production stages / inventory** | those routes → **403** |

---

## Shared negative guarantees (apply to every role)
| Scenario | Result | Proof |
|--|--|--|
| Any staff opens another branch's record by ID | **404** (no existence leak) | `BranchIsolationOnCustomersTest`, E2E-6 |
| Any non-Owner calls switch-branch | **403** | `PermissionNegativeFlowTest` |
| Any write missing a required `Idempotency-Key` | **400 `IDEMPOTENCY_KEY_REQUIRED`** | `IdempotencyFullFlowTest` |
| Same key + different body | **409 `IDEMPOTENCY_CONFLICT`** | idempotency tests |
| Action without the permission | **403** standard envelope + `request_id` | `PermissionNegativeFlowTest` |
| Unauthenticated request to a protected route | **401** | Sanctum middleware |

## Separation-of-duties highlights (non-obvious, by design)
- **Front Desk creates measurements view-only** — it **cannot approve**; approval needs `measurements.approve` (Admin, Production Supervisor, **QC Supervisor**).
- **QC Supervisor can approve measurements** (quality owns the measurement sign-off) but **cannot allocate fabric or touch finance**.
- **Inventory Manager can reserve fabric** (`fabric.allocate`) yet **cannot approve its own adjust-out** (needs a separate approver) and **cannot approve damage** (owner-grade).
- **Accountant is read-only on customers/orders** and **cannot move production** — finance is isolated from the floor.
- **Each floor station holds exactly one transition** (Kaja→kaja, Ironing→finishing, Re-Worker→rework, Tailor→tailoring), so an item can only advance through the correct hands.
- **Branch create/update + branch-switch are Owner-only**; Admin manages within a branch but cannot create branches or cross them.

