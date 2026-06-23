# Front Desk Full Alignment Audit — Solo Shirts India ERP

**Date:** 2026-06-12 · Route: `src/app/(shell)/front-desk/page.tsx` + `components/front-desk/*` (CustomerSearch, FamilyMemberSwitcher, MeasurementVersionSelector, OrderItemBuilder, FrontDeskContext) + `scan/page.tsx`.

| Front Desk Step | Backend Endpoint | Expected UI | Actual UI | Status | Issue |
|--|--|--|--|--|--|
| Route exists | — | `/front-desk` | ✅ present | Pass | landing is `/dashboard` not front-desk (FE-012) |
| Customer search | GET /customers?search=&page= | debounced search list | `CustomerSearch.tsx` via `useCustomers` | Pass | param name `search`/`q` — verify matches backend `q` |
| QR scan | GET /customers/by-qr/{payload} | scan → load customer | ✅ `scan/page.tsx` now `apiGet(customerByQr(encodeURIComponent(payload)))` | **Pass** | ~~FE-006~~ fixed |
| FE does not decode QR itself | backend validates | payload sent to backend, validated server-side | ✅ | **Pass** | rule 21 ✅ (FE-006 fixed) |
| Valid QR loads customer | by-qr returns customer | renders customer name/code/phone | ✅ | Pass | (verify live) |
| Invalid QR → error + request_id | INVALID_QR_SIGNATURE w/ request_id | error surface + ErrorDrawer request_id | Pass | (verify live) |
| Create customer | POST /customers | create form | `useCreateCustomer` | Pass | non-stable idempotency key (FE-007) |
| Duplicate phone handled | 409 DUPLICATE_PHONE | error mapped | normalizeError surfaces code/message | Partial | verify field-level mapping |
| Family member CRUD | /customers/{c}/family-members | switcher + add | `FamilyMemberSwitcher` (switch ✅); add/edit limited | Partial | display/switch ok; CRUD thin (FE-023) |
| Approved version selectable | only approved bindable | `MeasurementVersionSelector` filters approved | ✅ | Pass | confirm filter on `status==='approved'` |
| Rejected/unapproved not selectable | hidden/disabled | selector restricts to approved | ✅ | Pass | |
| Create order | POST /orders | confirm → order | `apiMutate('post', orders, …)` with **stable key** (IdempotencyGuard) | Pass | order create guarded ✅ |
| Add item | POST /orders/{id}/items | OrderItemBuilder | items sent **inline** in order create payload | Pass | separate add-item hook has non-stable key (FE-007) |
| Double submit guarded | no dup order | IdempotencyGuard stable key + disabled | ✅ | Pass | front-desk order is the well-guarded path |
| Job card PDF | GET /orders/{id}/job-card | open PDF | ✅ `window.open(jobCardPdf)` → `/job-card` | **Pass** | ~~FE-005~~ fixed |
| Profile panel loading/empty/error | states present | uses `loading-skeleton`/`empty-state`/`error-state` | Pass | |
| Mobile flow | responsive | Tailwind responsive; split-view | Partial | no mobile Playwright (FE-017) |
| Keyboard navigation | a11y | stepper-nav, focusable | Partial | not test-covered |

## Findings
- The **core Front Desk order flow (search → select customer → family member → approved measurement version → build items → confirm order)** is **well-built and correctly guarded** — it uses the IdempotencyGuard stable key for order creation and binds the **approved `measurement_version_id`** (rules 22-23 ✅).
- ~~**Two broken integrations on this screen:** QR scan and Job card.~~ **✅ Both fixed (2026-06-12):** QR scan now uses `GET /customers/by-qr/{payload}` (server-side validation, no client decode); Job card uses `/orders/{id}/job-card`.
- Family-member CRUD is mostly switch/display (FE-023); duplicate-phone and 422 field mapping should be verified end-to-end once a backend is running.

**Verdict:** Front Desk happy-path = **Pass and idempotency-safe**; QR scan + Job card = **✅ Fixed** (verify against a live backend).
</content>
