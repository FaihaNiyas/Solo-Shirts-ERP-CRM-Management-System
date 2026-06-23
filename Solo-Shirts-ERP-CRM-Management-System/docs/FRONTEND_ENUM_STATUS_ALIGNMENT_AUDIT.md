# Frontend Enum / Status / Dropdown Alignment Audit — Solo Shirts India ERP

**Date:** 2026-06-13 · **Mode:** inspect-only.
**Backend source of truth:** PHP enums, model const arrays (`METHODS`, `REASON_CODES`, `MODES`, `TREATMENTS`, status constants), state machine, enum migration columns. **FE:** `<select>`/option arrays, status-badge maps, zod enums in `lib/api/schemas/`.

| Dropdown/Status | Backend enum (values) | FE options (values) | Missing in FE | Extra in FE | Match? |
|---|---|---|---|---|---|
| User role | 14 roles | same 14 (`ROLES`) | — | — | ✅ |
| Notification channel | `whatsapp`,`email`,`sms` | `whatsapp`,`email`,`sms` | — | — | ✅ |
| Defect severity | `minor`,`major`,`critical` | `minor`,`major`,`critical` | — | — | ✅ |
| Production state | `draft`,`fabric_allocated`,`cutting`,`tailoring`,`kaja_button`,`finishing`,`qc`,`rework`,`packing`,`ready_for_delivery`,`delivered`,`cancelled` | PascalCase equivalents (`Draft`,`FabricAllocated`,…) | — | — | ✅ (intentional case map via `transformProductionItem`) |
| Customer gender | *(no backend enum — text col)* | `Male`,`Female`,`Other` | — | (FE-only) | ⚠️ partial — backend accepts `male/female/other` lowercase on family member; **case mismatch** |
| Product/garment type | `shirt`,`pant`,`combo` | `Shirt`,`Pant`,`Sherwani`,`Combo`,`Kurta`,`Other` | — | **`Sherwani`,`Kurta`,`Other`** + case | ❌ EN-01 |
| Payment method | `cash`,`upi`,`bank_transfer` | `Cash`,`UPI`,`Card`,`Bank Transfer`,`Cheque` | `cash`,`upi`,`bank_transfer` (case) | **`Card`,`Cheque`** | ❌ EN-02 |
| QC disposition | `pass`,`pass_with_note`,`rework`,`reject` | `pass`,`fail`,`rework` (uses `result`/`defect_codes`) | `pass_with_note`,`reject` | **`fail`** | ❌ EN-03 |
| Delivery mode (Order) | `pickup`,`home`,`courier` | NO FE DROPDOWN | all | — | ❌ EN-04 (also missing form FF-02) |
| Delivery mode (Delivery entity) | `pickup`,`home_delivery`,`courier` | NO FE DROPDOWN | all | — | ❌ EN-04 + **backend self-inconsistency** (`home` vs `home_delivery`) |
| Order source | `walk_in`,`phone`,`whatsapp`,`online` | NO FE DROPDOWN | all | — | ❌ EN-05 |
| Delivery attempt reason_code | `customer_unavailable`,`wrong_address`,`refused`,`payment_pending`,`other` | NO FE DROPDOWN (free text) | all | — | ❌ EN-06 (= FF-08) |
| Dispatch channel | `sms`,`whatsapp` | NO FE DROPDOWN | all | — | ⚠️ (optional field) |
| GST treatment | `regular`,`composition`,`unregistered` | NO FE DROPDOWN | all | — | ❌ EN-07 (= FF-11) |
| Invoice status | `issued`,`partially_paid`,`paid`,`credited` | status badge only, no selector | — | — | ⚠️ display-only OK |
| Measurement status | `draft`,`pending_approval`,`approved`,`rejected` | badge only | — | — | ⚠️ display-only OK |
| Measurement profile type | `shirt`,`pant`,`both` | NO FE DROPDOWN | all | — | ❌ (= profile form gap) |
| Order status | (workflow states) | badge map | — | — | ⚠️ display-only OK |
| Rack status | slot occupied/free | badge | — | — | ✅ display |
| Delivery status | `scheduled`,`dispatched`,`attempted`,`delivered`,`failed`,`cancelled` | badge only | — | — | ⚠️ display-only OK |
| Fabric roll status | `active`,`depleted`,`written_off` | badge only | — | — | ⚠️ display-only OK |
| Purchase order status | `draft`,`placed`,`partial_received`,`received`,`cancelled` | badge only | — | — | ⚠️ display-only OK |
| Damage report status | `pending`,`approved`,`rejected` | badge / filter | — | — | ✅ display |
| Damage stage | `receiving`,`cutting`,`tailoring`,`qc`,`ironing`,`packing` | NO FE DROPDOWN | all | — | ❌ (= damage create form gap) |
| Damage type | `tear`,`stain`,`color_bleed`,`mis_cut`,`machine_oil`,`other` | NO FE DROPDOWN | all | — | ❌ (= damage create form gap) |
| Fabric roll adjust type | `adjust_in`,`adjust_out` | `adjust_in`,`adjust_out` | — | — | ✅ |
| Report kind | live from `/reports` (`r.data.kinds`) | fetched live | — | — | ✅ (always in sync) |

## Findings
- **Blocking enum mismatches** that break writes today: **EN-01** product type (capitalized + invalid `Sherwani`/`Kurta`/`Other`), **EN-02** payment method (label vs slug + invalid `Card`/`Cheque`), **EN-03** QC disposition (`fail` is not a backend value; `pass_with_note`/`reject` unavailable in UI), **EN-04/05/06/07** missing required selectors (delivery mode, order source, attempt reason_code, gst_treatment) — these overlap the form audit (FF-02/08/11).
- **Backend self-inconsistency (EN-04):** Order uses delivery mode `home`; the Delivery entity uses `home_delivery`. **Backend gap — needs confirmation** (cannot fix from FE).
- **Display-only badges** (invoice/measurement/order/delivery/fabric-roll/PO status) read backend values correctly and need no selector — marked OK.
- **Report kind** is the gold pattern: fetched live so it can never drift.

> **Out of scope for the current fix** (role-constants + sidebar). Logged for the forms/enums alignment task.
