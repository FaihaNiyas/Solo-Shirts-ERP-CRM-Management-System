# Frontend Form Field Alignment Audit — Solo Shirts India ERP

**Date:** 2026-06-13 · **Mode:** inspect-only (no form fixes — per instruction).
**Backend source of truth:** `app/Modules/*/Http/Requests/*.php` (`rules()`). **FE:** `(shell)/**` pages, `components/**`, zod in `lib/api/schemas/`, hooks in `lib/api/hooks/`.
**Idempotency:** the API client auto-attaches an `Idempotency-Key` to every POST/PUT/PATCH; high-risk write hooks additionally use a **stable** key (`useStableIdempotencyKey`). So all writes carry a key; "Idempotency OK" below = stable key present.

> ⚠️ Several pages bypass their typed hook with an inline `apiMutate` using a **different payload shape** than the hook — each blocking row below was confirmed against the actual object literal in the page, not just the hook type.

## Per-field detail (key mismatches only)

| Form | Field | Backend payload name | Required by backend | Exists in UI | UI key sent | Backend match | Issue |
|---|---|---|---|---|---|---|---|
| 2FA confirm | otp | `otp` | ✅ | ✅ | **`code`** | ❌ | Wrong key → 422 (FF-01) |
| Order create | source | `source` | ✅ | ❌ | — | ❌ | Missing required (FF-02) |
| Order create | delivery_mode | `delivery_mode` | ✅ | ❌ | — | ❌ | Missing required (FF-02) |
| Order create | item type | `items.*.product_type` | ✅ | ✅ | **`garment_type`** | ❌ | Wrong key + wrong nesting (FF-02) |
| Order create | version | `items.*.measurement_version_id` | ✅ | ✅ (order-level) | order-level `measurement_version_id` | ❌ | Wrong nesting — must be per-item (FF-02) |
| Measurement version | values | `shirt_data.*` / `pant_data.*` | ✅ | ✅ (flat) | flat `chest`,`waist`… + `notes`,`unit` | ❌ | Flat vs nested → all values dropped (FF-03) |
| Reject measurement | reason | `reason` (min:3) | ✅ | ✅ | **`note`** | ❌ | Wrong key → 422 (FF-04) |
| Tailoring assign | bundle | `bundle_id` | ✅ | ✅ | **`production_item_id`** | ❌ | Wrong key → 422 (FF-05) |
| Rack assign | slot | `slot_code` (string) | optional | ✅ | **`slot_id`** (number) | ❌ | Wrong key → wrong/auto slot, silent (FF-06) |
| QC inspect | defects | `defects[]{category_id,severity}` | optional | ✅ | **`defect_codes[]`** (flat ids) | ❌ | Wrong shape → defects silently lost (FF-07) |
| Delivery attempt | reason | `reason_code` (enum) | ✅ | ✅ | **`reason`** (free text) | ❌ | Wrong key/type → 422 (FF-08) |
| Record payment | amount | `amount_paise` (int) | ✅ | ✅ | **`amount`** (rupees) | ❌ | Wrong key+unit → 422 (FF-09) |
| Record payment | reference | `reference_no` | optional | ✅ | **`reference`** | ❌ | Wrong key (FF-09) |
| Record payment | method | `method` (slug enum) | ✅ | ✅ | label `Cash`/`UPI`/`Card`… | ❌ | Label vs slug; extra Card/Cheque (FF-09) |
| Credit note | total | `total` (int paise) | ✅ | ✅ | **`amount`** (rupees) | ❌ | Wrong key+unit → 422 (FF-10) |
| Invoice create | gst_treatment | `gst_treatment` | ✅ | ❌ | — | ❌ | Missing required (FF-11) |
| Invoice create | lines | `lines[]` (min:1) | ✅ | ❌ | — | ❌ | Missing required (FF-11) |
| Supplier create | code | `code` | ✅ | ❌ | — | ❌ | Missing required (FF-12) |
| PO receive | lines | `lines[]` (min:1) | ✅ | ❌ | `{}` | ❌ | Missing required GRN lines (FF-13) |
| Customer create | email | — (no such column) | — | ✅ | `email` | ⚠️ | Extra unsupported field (FF-14, Low) |

## Form-level summary

| Form | Missing required | Wrong field names | Extra unsupported | Validation gaps | Idempotency stable | Status |
|---|---|---|---|---|---|---|
| Login | — | — | — | pw min:6 (stricter, OK) | n/a | ✅ |
| 2FA confirm | — | `code`→`otp` | — | — | n/a | ❌ FF-01 |
| Customer create | — | — | `email` | phone min:10 (stricter) | ✗ (auto key) | ⚠️ FF-14 |
| Customer edit | — | — | — | — | — | ⛔ NO FE FORM |
| Family member create | — | — | — | — | ✗ | ✅ |
| Measurement profile create | name/type/family_member_id | — | — | — | — | ⛔ NO FE FORM |
| Measurement version create | shirt_data/pant_data nesting | flat keys | `notes`,`unit` | values dropped | ✓ | ❌ FF-03 |
| Approve measurement | — | — | — | — | ✓ | ✅ |
| Reject measurement | — | `note`→`reason` | — | min:3 not enforced | ✓ | ❌ FF-04 |
| Order create | source, delivery_mode, per-item product_type+version | `garment_type`,nesting | capitalized enums, per-item delivery_date | broad | ✓ | ❌ FF-02 (Blocker) |
| Add order item | — | — | — | — | ✓ | ⛔ NO FE FORM |
| Cancel order | — | — | — | — | ✓ | ✅ |
| Cutting allocate/release/start/complete | — | — | — | — | ✓ | ✅ |
| Tailoring assign | — | `production_item_id`→`bundle_id` | — | — | inline bypass | ❌ FF-05 |
| Tailoring start/complete/reassign | reassign tailor_id | — | — | — | hooks unwired | ⛔ NO FE FORM |
| QC inspect | — | `defect_codes`→`defects[]` | — | no `pass_with_note` | ✓ | ❌ FF-07 |
| QC photo upload | photo | — | — | — | — | ⛔ NO FE FORM |
| QC rework override | — | — | — | — | hook unwired | ⛔ NO FE FORM |
| Fabric roll create | fabric_type_id, received_length_metres | — | — | — | — | ⛔ NO FE FORM |
| Fabric roll adjust | — | — | — | adjust_out reason min:10 not enforced | ✗ | ⚠️ |
| Fabric type create/edit | code, name | — | — | — | — | ⛔ NO FE FORM |
| Supplier create | code | — | — | — | ✗ | ❌ FF-12 |
| PO create | supplier_id, items[] | — | — | — | — | ⛔ NO FE FORM |
| PO place | — | — | — | — | ✗ | ✅ |
| PO receive | lines[] | — | — | — | ✗ | ❌ FF-13 |
| Damage report create | fabric_roll_id, stage, damage_type, quantity | — | — | — | — | ⛔ NO FE FORM |
| Damage approve | — | — | — | — | ✗ | ✅ |
| Damage reject | — | — | — | reason min:10 not enforced | ✗ | ⚠️ |
| Rack slot create/edit | slot_code | — | — | — | — | ⛔ NO FE FORM |
| Rack assign | — | `slot_id`→`slot_code` | — | — | inline bypass | ❌ FF-06 |
| Rack release | — | — | — | — | hook unwired | ⛔ NO FE FORM |
| Delivery create | order_id, mode | — | — | — | — | ⛔ NO FE FORM |
| Delivery dispatch | — | — | — | — | ✗ | ✅ |
| Delivery confirm OTP | — | — | — | — | ✓ | ✅ |
| Delivery attempt | — | `reason`→`reason_code` | — | enum missing | ✗ | ❌ FF-08 |
| Delivery cancel | — | — | — | — | hook unwired | ⛔ NO FE FORM |
| Invoice create | gst_treatment, lines[] | — | — | — | ✓ | ❌ FF-11 |
| Record payment | upi_id (if upi) | `amount`→`amount_paise`,`reference`→`reference_no` | `notes` | method labels | ✓ | ❌ FF-09 (Blocker) |
| Credit note | — | `amount`→`total` (paise) | — | — | ✓ | ❌ FF-10 |
| Run report | — | — | — | kinds fetched live (OK) | ✗ | ✅ |
| Settings profile / change password | — | — | — | submit disabled (FE-003/004 backend gap) | — | ⛔ STUBBED |
| Preferences/Appearance/Accessibility | — | — | — | local-only, no network | — | ✅ |

## Highest-impact (would 422 / lose data today)
FF-01 2FA `code`→`otp` · FF-02 Order create (multiple) · FF-03 Measurement version nesting · FF-04 Reject `note`→`reason` · FF-05 Tailoring `production_item_id`→`bundle_id` · FF-06 Rack `slot_id`→`slot_code` · FF-07 QC `defect_codes`→`defects[]` · FF-08 Delivery `reason`→`reason_code` · FF-09 Payment paise/keys · FF-10 Credit note `amount`→`total` · FF-11 Invoice gst_treatment+lines · FF-12 Supplier `code` · FF-13 PO receive lines[].

## Cross-cutting checks
- **branch_id never manually editable** by staff — confirmed (customer create exposes no branch field; backend resolves from token). ✅
- **422 field errors + request_id** surfaced — the API client normalizes both (FE-024); forms render them via the error drawer. ✅ (where forms exist)
- **measurement_version_id used (not measurement_id)** — ✅ at the key level, but mis-nested at order level (FF-02).

> **Out of scope for the current fix** (role-constants + sidebar). These form mismatches are logged for a dedicated forms-alignment task; do not fix here.
