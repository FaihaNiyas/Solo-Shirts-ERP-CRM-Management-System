# Inventory / Finance / Delivery Alignment Audit — Solo Shirts India ERP

**Date:** 2026-06-12 · Routes under `inventory/*`, `finance/*`, `deliveries/page.tsx`; `hooks/useInventory.ts`, `useFinance.ts`, `useDeliveries.ts`.

## Inventory
| Check | Backend Endpoint | Actual | Status | Issue |
|--|--|--|--|--|
| Fabric rolls list/show/create | /inventory/fabric-rolls | `useFabricRolls`, detail, create | Pass | |
| Movements | /inventory/movements | `useInventoryMovements(rollId)` | Pass | |
| Low stock | /inventory/low-stock | `lowStock` query + alert | Pass | |
| Purchase orders | /inventory/purchase-orders | list + place/cancel | Pass | |
| Receive PO → roll + GRN | /purchase-orders/{id}/receive | `useReceivePO` (invalidates fabricRolls) | Pass | |
| QR lookup | /inventory/fabric-rolls/by-qr/{payload} | `fabricRollByQr` defined | Partial | verify scan wiring |
| **No reservation from inventory endpoint** | reserve only via cutting | inventory screens only adjust/receive; **no reserve call** | Pass | ✅ rule 26 (but cutting also doesn't reserve — FE-002) |
| remaining/reserved/available separate | 3 fields | ✅ shown distinctly | Pass | rule 25 ✅ |

## Finance
| Check | Backend Endpoint | Actual | Status | Issue |
|--|--|--|--|--|
| Invoice list | /finance/invoices | `useInvoices` | Pass | |
| Invoice create | POST /finance/invoices | `useCreateInvoice` | Partial | non-stable key (FE-007); missing dashboard/orders invalidation (FE-014) |
| Invoice PDF | GET /finance/invoices/{id}/pdf | `window.open(invoicePdf)` | Pass | ✅ correct route (unlike job-card) |
| Credit note | POST /…/credit-note | `useIssueCreditNote` | Partial | non-stable key; missing dashboard invalidation |
| Payment record | POST /finance/payments | `useRecordPayment` | Partial | non-stable key; missing orders invalidation |
| Outstanding balance | /finance/outstanding, /orders/{id}/outstanding-balance | `useFinance` queries | Pass | |
| Dashboard summary | /finance/dashboard/summary | `useFinanceDashboard` | Pass | |
| **No invoice edit/delete UI** | none (backend has none) | only create/pdf/credit-note/payment — **no edit/delete** | Pass | ✅ rule 27 |
| All finance writes use Idempotency-Key | required | header always sent (auto) | Partial | not **stable** (FE-007) |
| Payment > balance error handled | 4xx domain error | normalizeError surfaces message/request_id | Partial | verify inline display of code (e.g. exceeds balance) |

## Delivery
| Check | Backend Endpoint | Actual | Status | Issue |
|--|--|--|--|--|
| Delivery list | /deliveries | `useDeliveries` | Pass | |
| Create delivery | POST /deliveries | `useCreateDelivery` | Pass | |
| Dispatch (OTP issued) | POST /deliveries/{id}/dispatch | `useDispatchDelivery` | Pass | |
| Confirm OTP | POST /deliveries/{id}/confirm | `useConfirmDelivery({otp})` | Partial | non-stable key (FE-007); missing orders/rack invalidation (FE-014) |
| Wrong OTP | 422 OTP_INVALID | error surfaced | Partial | verify message/attempts shown |
| Expired OTP | 422 OTP_EXPIRED | error surfaced | Partial | |
| Locked OTP (5 tries) | 423 OTP_LOCKED | UI "handles 423 lock-out" per OTP dialog | Pass | ✅ good |
| Cancel / attempt | /deliveries/{id}/cancel\|attempt | wired | Pass | |
| Rack release after delivery | listener releases slot backend-side | FE does not invalidate rack/current-slot after confirm | Partial | FE-014 (rack not refetched) |

## Findings
- **Inventory:** strong — separate remaining/reserved/available (rule 25), no reservation from inventory endpoints (rule 26 on the inventory side). PO receive → roll/GRN wired.
- **Finance:** strong contract fit — **no invoice edit/delete UI (rule 27 ✅)**, invoice PDF route correct, all the read screens present. Gaps are non-stable idempotency keys on the 3 financial creates (FE-007) and missing dashboard/orders invalidation (FE-014).
- **Delivery:** OTP confirm incl. **423 lockout handling is implemented** ✅; gaps are non-stable key (FE-007) and not refetching orders/rack after confirm (FE-014).

**Verdict:** Inventory + Finance + Delivery are **largely aligned and contract-correct**; the recurring issues are the cross-cutting FE-007 (stable idempotency key) and FE-014 (invalidation), not module-specific breakage.
</content>
