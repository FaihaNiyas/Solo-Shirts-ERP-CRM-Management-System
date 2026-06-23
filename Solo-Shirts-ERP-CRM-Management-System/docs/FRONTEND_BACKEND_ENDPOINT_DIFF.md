# Frontend ↔ Backend Endpoint Diff — Solo Shirts India ERP

**Date:** 2026-06-12 · **Backend source of truth:** `docs/openapi.json` (111 paths) vs `frontend/src/lib/api/endpoints.ts`.
**Good news:** all endpoint strings live in `endpoints.ts` (no hardcoded `/api/` URLs in components — verified across `src/`). The issues are **contract mismatches** (FE constant points at a path/method the backend doesn't expose).

> **✅ Fix group 1 (2026-06-12):** job-card (FE-005) and QR scan (FE-006) **fixed**; change-password + PUT /auth/me (FE-003/004) **disabled in the UI** pending backend confirmation.

| Type | Backend Endpoint | Frontend Endpoint/Usage | Method | File | Issue | Severity | Fix Recommendation |
|--|--|--|--|--|--|--|--|
| B (FE→missing) | *(none — no route)* | `auth.changePassword` `/auth/change-password` | POST | `settings/profile/page.tsx` | Backend has **no** change-password route → 404 | **High** | ⏸️ **Disabled in UI** (Backend gap — needs confirmation) |
| D/E (wrong method) | `GET /auth/me` only | `auth.updateProfile` `PUT /auth/me` | PUT | `settings/profile/page.tsx` | `/auth/me` is **GET-only** → 405/404 | **High** | ⏸️ **Disabled in UI** (Backend gap — needs confirmation) |
| F (wrong route) | `GET /orders/{order}/job-card` | `jobCardPdf` → `/orders/{id}/job-card` | GET | `endpoints.ts:52` | ~~FE appended `.pdf`~~ | **High** | ✅ **Fixed** — `.pdf` dropped |
| B (FE→missing) | `GET /customers/by-qr/{payload}` | `scan/page.tsx` → `customerByQr` | GET | `scan/page.tsx`, `endpoints.ts` | ~~FE posted to `/scan`~~ | **High** | ✅ **Fixed** — repointed to by-qr; invalid `scan` constant removed |
| A (BE→missing in FE) | `GET /qc/defects/analytics` | *(not referenced)* | GET | — | Backend defect-analytics endpoint unused by FE | Low | Add when QC analytics screen is built (**Frontend gap**) |
| B (FE show route absent) | index only: `GET /tailoring/assignments` | `tailoringAssignment(id)` `/tailoring/assignments/{id}` | GET | `endpoints.ts:71` | Backend has **no** single-assignment show route | Low | Remove unused constant or confirm backend adds it |
| C (header, not route) | branch via token `active_branch_id` | `X-Branch-Id` header injected | — | `client.ts:26-31` | Backend `ResolveBranchContext` reads the **token**, not this header → header is **dead/ignored** | Low | Remove the header injection or confirm backend should honor it (**Contract mismatch — cosmetic**) |

## A) Backend endpoints missing from frontend (informational)
Mostly intentional: `qc/defects/analytics`, `qc/defects/categories` POST (create category), `damage-reports/photos` download, signed download routes (`documents/{id}/download` is present), `cutting/release-fabric` constant exists but **no UI calls it** (see FE-015). No *required-workflow* backend endpoint is entirely absent from `endpoints.ts` — the gap is in **hooks/usage**, not constants.

## B) Frontend endpoints not present in backend OpenAPI — the real defects
1. `/auth/change-password` (FE-003)
2. `PUT /auth/me` profile update (FE-004)
3. `/orders/{id}/job-card.pdf` — wrong suffix (FE-005)
4. `/api/v1/scan` (FE-006)

## C–G summary
- **C) Hardcoded endpoints in components:** none (✅ all via `ENDPOINTS`).
- **D) Wrong param names in dynamic builders:** none material (builders use ids consistently).
- **E) Wrong HTTP method:** `updateProfile` PUT vs backend GET-only `/auth/me` (FE-004).
- **F) Old/wrong route names:** `job-card.pdf` (FE-005); `/scan` (FE-006).
- **G) Endpoints missing Idempotency-Key support:** the **client always sends a key** (auto-generated), so no endpoint *lacks* a key — but the key is **not stable per submit** (see `FRONTEND_IDEMPOTENCY_ALIGNMENT.md`, FE-007). Several **mutation endpoints have no hook** and are called inline (FE-015).

**Verdict:** endpoint centralization is excellent; **4 High contract mismatches** (change-password, PUT /auth/me, job-card.pdf, /scan) will break their screens against the real backend, and the **cutting endpoints are defined but never called** (see production report).
</content>
