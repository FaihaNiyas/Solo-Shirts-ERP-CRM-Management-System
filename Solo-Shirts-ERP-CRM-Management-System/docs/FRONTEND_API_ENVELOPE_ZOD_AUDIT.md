# Frontend API Envelope + Zod Validation Audit — Solo Shirts India ERP

**Date:** 2026-06-12 · Files: `src/lib/api/client.ts`, `types.ts`, `schemas/*.ts`, `components/shell/ErrorDrawer.tsx`, `components/ui/error-state.tsx`, hooks.

> **✅ Fix group 2 (2026-06-12):** **FE-024 fixed** — `ensureSuccess()` rejects `success!==true`; `normalizeError` adds `X-Request-Id` header fallback. `parseApiData()` helper shipped.
> **✅ Fix group 5 (2026-06-13):** **FE-008 validation now LIVE on 6/7 entities** (Orders, Finance, Inventory, Delivery, Customer, Measurement) after the FE-025 schema alignment — detail queries + key mutations run `parseApiData`. Production validation deferred (its `selectBoard` transform).

| Area | Expected | Actual | File | Status | Issue |
|--|--|--|--|--|--|
| Success envelope parsed | `{success,message,data,request_id}` typed & data extracted | Typed as `ApiEnvelope<T>`; helpers return `data` (envelope) — callers read `.data.data` | `client.ts:118-141`, `types.ts` | Partial | typed but no runtime check |
| Error envelope parsed | `{success:false,message,code,errors,request_id}` normalized | `normalizeError` maps message/code/request_id/errors from `error.response.data` | `client.ts:107-115` | Pass | |
| `success:false` never treated as success | reject if `success===false` even on 2xx | **No check** — a 2xx with `success:false` would pass through as success | `client.ts:118-141` | **Fail** | FE-024 |
| request_id captured | from body and/or `X-Request-Id` header | from **body only** (`data.request_id`); no header fallback | `client.ts:112` | Partial | request_id `''` if body lacks it (500/HTML/network) |
| request_id shown in UI | visible in error surface | **Shown in `ErrorDrawer`** (`req:…` + copy) | `ErrorDrawer.tsx:99-104` | Pass | ✅ rule 14 |
| request_id in inline errors | shown in `error-state` too | `error-state.tsx` does **not** render request_id | `error-state.tsx` | Partial | only ErrorDrawer shows it |
| 422 → form fields | `errors{field:[msg]}` mapped to inputs | `normalizeError` keeps `errors`; forms (react-hook-form + `form-field`) can map; not uniformly wired | `client.ts:113`, forms | Partial | verify per-form mapping |
| 401 handled | silent refresh once, else → /login | `_retry` flag, single refresh, queue; failure → clearSession + `/login` | `client.ts:50-104` | Pass | ✅ refresh-once |
| 403 shown | forbidden UI | normalized error only; **no dedicated 403 page** | — | Partial | FE-011 |
| 404 shown | not-found UI | generic error surface | — | Partial | |
| 429 shown | rate-limit UI | generic error (code surfaces as `TOO_MANY_REQUESTS`) | — | Partial | message shown, no special UI |
| 500 + request_id | server error with request_id | ErrorDrawer shows message+request_id (if body present) | `ErrorDrawer.tsx` | Partial | 500 HTML → no request_id |
| Zod schemas exist | per domain | 12 schema files (auth, customers, measurements, orders, production, cutting, qc, inventory, delivery, finance, audit, notifications) | `schemas/*.ts` | Pass | |
| Zod used to validate responses | hooks `.parse()` data | **0 / 27 hooks validate** — responses cast `as T`, never parsed | `hooks/*.ts` | **Fail** | FE-008 (rule 13) |
| Zod parse failure → controlled error | graceful, no blank crash | N/A — parsing not invoked | — | **Fail** | FE-008 |

## Key findings
- **FE-008 (High) — Zod schemas defined but unused.** All 12 schema files exist and are exported, but no mutation/query hook calls `.parse()`/`.safeParse()` on the response `data`. Responses are TypeScript-cast only. A backend shape change would flow through silently as malformed data (rule 13 violated). The infrastructure is one line per hook away from compliance, but today it's **not wired**.
- **FE-024 (Medium) — envelope `success` not enforced.** `apiGet`/`apiMutate` return the raw axios `data` without asserting `success===true`. Backend errors arrive as non-2xx (so axios throws → `normalizeError`), which masks the gap in practice; but a 200 + `success:false` (or unexpected payload) is not defended against. request_id is read from body only — add an `X-Request-Id` header fallback for 500/HTML/network errors.
- ✅ **request_id is surfaced** in the `ErrorDrawer` with a copy button (rule 14) — good. Extend to inline `error-state` for full coverage.
- ✅ **401 refresh-once** and **/login redirect** are correctly implemented.

**Verdict:** Envelope typing + error normalization + request_id display are **good**; the **two real gaps** are (1) Zod validation never executed (FE-008, High — data integrity) and (2) no `success:false`/header-fallback guard (FE-024, Medium).
</content>
