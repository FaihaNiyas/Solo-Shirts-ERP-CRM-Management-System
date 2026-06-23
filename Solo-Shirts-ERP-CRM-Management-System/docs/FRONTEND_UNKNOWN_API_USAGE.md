# Frontend Calls Not Covered by Backend — Solo Shirts India ERP

**Date:** 2026-06-12 · Scanned `frontend/src` for `/api/`, `fetch(`, `axios.`, `useQuery(`, `useMutation(`, mock/TODO, hardcoded perms. Backend = `openapi.json` (111 paths).

## A. Endpoints called by FE but ABSENT from backend OpenAPI (the real defects)
| File | Code Pattern | Problem | Backend Match | Severity | Fix Recommendation |
|--|--|--|--|--|--|
| `endpoints.ts:136` + `scan/page.tsx` | `scan: ${V1}/scan` (POST) | calls `/api/v1/scan` | ❌ absent | **High** | repoint to `/customers/by-qr/{payload}` or `/qr/decode/{payload}` (FE-006) |
| `endpoints.ts:16` + `settings/profile/page.tsx:61` | `changePassword: ${V1}/auth/change-password` | POST change-password | ❌ absent | **High** | confirm backend route or remove UI (FE-003) |
| `endpoints.ts:15` + `settings/profile/page.tsx:47` | `updateProfile: PUT ${V1}/auth/me` | PUT /auth/me | ❌ GET-only | **High** | confirm backend profile-update route (FE-004) |
| `endpoints.ts:52` + `orders/[id]/page.tsx` | `jobCardPdf: …/job-card.pdf` | wrong suffix | ⚠️ `/job-card` exists | **High** | drop `.pdf` (FE-005) |
| `endpoints.ts:71` | `tailoringAssignment(id)` GET | single-show | ❌ index-only | Low | remove/confirm (FE) |

## B. Direct API calls bypassing the client — **none**
✅ No raw `fetch(`, no `axios.` (other than the `apiClient` instance in `client.ts`), no `XMLHttpRequest`. All calls go through `apiGet/apiMutate/apiPost/apiPut` or hooks.

## C. Hardcoded API URLs in components — **none**
✅ No `/api/` literals outside `endpoints.ts`/`client.ts`. No inline path template strings in components.

## D. Mock data in workflow screens — **none material**
✅ No `mock`/`fake`/`dummy`/`sampleData` arrays driving workflow tables; no `Math.random` fabricating domain data. Only:
| File | Pattern | Problem | Severity |
|--|--|--|--|
| `(auth)/login/page.tsx:23-33` | `const DEMO_USERS = [...]` quick-login buttons | dev credentials in login UI | Low | guard behind env flag for prod (FE-020) |

## E. TODO / @ts-ignore / as any in API/data code
✅ None found in the API/data layer (no `@ts-ignore`/`@ts-expect-error`; type-check passes clean).

## F. Hardcoded permission/role strings (should use constants)
| File | Code | Problem | Severity |
|--|--|--|--|
| `audit/page.tsx:36` | `user?.roles?.includes('Owner')\|\|…('Admin')` | inline role strings | Low |
| `admin/users/page.tsx:19-34`, `[id]/page.tsx:16-31` | local `const ROLES=['Owner',…]` arrays (×2) | duplicates `permissions.ts` ROLES | Low |
| `admin/branches/page.tsx:35` | `user?.roles?.includes('Owner')` | inline | Low |
**Distinct strings:** `'Owner'`, `'Admin'` (+ full 14-role arrays in admin pages). All exist in the backend role matrix — the issue is duplication, not invalid names. → **FE-013** (also Kaja Button/Ironing Master/Re-Worker absent from the `ROLES` constant object).

## G. Idempotency-key construction in components
✅ `crypto.randomUUID()` / `uuid` lives in `client.ts` and `idempotency-guard.tsx` only — not scattered in components.

## Summary counts
| Problem type | Count |
|--|--|
| FE endpoint absent in backend | **4 High** (scan, change-password, PUT /auth/me, job-card.pdf) + 1 Low |
| raw-fetch / hardcoded-url / inline-path | **0** ✅ |
| mock-data in workflow | **0** ✅ (1 dev-only DEMO_USERS) |
| todo / ts-ignore / as-any in API code | **0** ✅ |
| hardcoded-perm strings | 6 (Low) |

**Verdict:** API hygiene is **excellent** (centralized, no raw fetch, no mocks). The only "unknown API usage" is the **4 High contract mismatches** where FE calls endpoints the backend doesn't expose — these are real and will fail at runtime.
</content>
