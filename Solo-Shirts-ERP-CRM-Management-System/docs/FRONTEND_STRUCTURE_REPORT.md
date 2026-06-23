# Frontend Structure & Build Report — Solo Shirts India ERP

**Date:** 2026-06-12 · **Engagement:** inspect-only (no fixes). **Frontend:** `frontend/` (Next.js 15.3.3, React 19, TanStack Query 5, axios, zod, zustand, Playwright).
**Method:** live commands + static inspection. Backend = source of truth (OpenAPI 111 paths, BACKEND_* docs).

> **Note:** the frontend uses a `src/` layout (`src/app`, `src/lib`, `src/components`) and `frontend/e2e/` for Playwright — so the prompt's expected paths map under `frontend/src/…`. This is a layout difference, **not** a defect.

## TASK 1 — Structure Check

| Expected Path | Exists | Status | Notes |
|---|---|---|---|
| package.json | ✅ | Pass | `frontend/package.json` |
| next.config.js/ts | ✅ | Pass | `next.config.ts` |
| tsconfig.json | ✅ | Pass | |
| tailwind.config.ts | ✅ | Pass | |
| app/ | ✅ | Pass | `src/app/` |
| app/layout.tsx | ✅ | Pass | `src/app/layout.tsx` |
| app/(auth)/login/page.tsx | ✅ | Pass | |
| app/(auth)/2fa/page.tsx | ✅ | Pass | |
| app/(shell)/layout.tsx | ✅ | Pass | wraps `AuthGuard` |
| app/(shell)/loading.tsx | ✅ | Pass | + per-route loading.tsx |
| app/(shell)/error.tsx | ✅ | Pass | generic error boundary |
| components/ui/ | ✅ | Pass | 30+ components incl. `idempotency-guard.tsx` |
| components/shell/ | ✅ | Pass | AppShell, AuthGuard, BranchSwitcher, ErrorDrawer, SideNav, TopBar… |
| components/providers/ | ✅ | Pass | QueryProvider, BranchProvider |
| lib/api/endpoints.ts | ✅ | Pass | centralized (no hardcoded URLs in components — verified) |
| lib/api/client.ts | ✅ | Pass | axios instance + interceptors |
| lib/api/types.ts | ✅ | Pass | `ApiEnvelope`, `ApiError` |
| lib/api/schemas/ | ✅ | Pass | 12 Zod schema files — **but unused by hooks (FE-008)** |
| lib/api/hooks/ | ✅ | Partial | 8 hook files — **11 mutation hooks missing (FE-015)** |
| lib/query/keys.ts | ✅ | Pass | centralized keys (a few inline exceptions — FE-021) |
| lib/auth/session.ts | ✅ | Pass | token in **sessionStorage** (rule 17 ✅) |
| lib/auth/permissions.ts | ✅ | Partial | ROLES holds 11/14 roles (FE-013) |
| lib/auth/branch-context.ts | ✅ | Pass | switch-branch logic |
| tests/e2e/ | ✅ | Pass | at `frontend/e2e/` (13 specs) |
| playwright.config.ts | ✅ | Pass | baseURL :3000, backend :8000 assumed |
| storybook config | ❌ | **Missing** | no `.storybook/`, no storybook dep/script (FE-020) |
| .env.example | ❌ | **Missing** | only `.env.local` present (FE-020) |

**Verdict:** structure is **strong and well-organized** — centralized endpoints, typed envelope, Zod schemas, auth/branch/permission modules, query keys, 30+ UI primitives, 13 e2e specs. Gaps: no Storybook, no `.env.example`, schemas unused, some hooks missing.

---

## TASK 15 — Build / Test Commands

`package.json` scripts: `dev`, `build`, `start`, `lint`, `type-check`, `e2e`, `e2e:ui` (no `typecheck`/`storybook`).

| Command | Result | Detail |
|---|---|---|
| `npm install` | ✅ present | `node_modules` already installed (354 pkgs), node v22.14 |
| `npm run type-check` (`tsc --noEmit`) | ✅ **PASS** (exit 0) | no type errors |
| `npm run lint` (`next lint`) | ⚠️ **NOT RUNNABLE** (exit 1) | `next lint` is unconfigured — prompts *"How would you like to configure ESLint?"* interactively and aborts. **No `.eslintrc`.** Lint cannot run in CI (FE-016). Not code lint errors — a tooling gap. |
| `npm run build` (`next build`) | ❌ **FAIL** (exit 1) — **BLOCKER** | Compiles OK, then static prerender of `/login` crashes: `Could not find the module ".../(auth)/login/page.tsx#default" in the React Client Manifest`. Next 15.3/React 19 RSC bundler error on the login page. **Build cannot complete (FE-001).** |
| `npm run e2e` (`playwright test`) | ⚠️ **not run here** | `playwright.config.ts` assumes **both** servers already running (frontend :3000 + backend :8000) and demo data seeded. Cannot execute headless without a live backend in this environment. Specs reviewed statically (Task 14). |
| `npx storybook build` | ❌ **N/A** | Storybook is not set up (no config/deps). The stack lists it but it was never implemented (FE-020). |

**Build headline:** **1 Blocker** — `next build` fails on `/login` prerender. Type-check is clean; lint is unconfigured; Storybook is absent.

---

## Report index (this audit)
- `FRONTEND_STRUCTURE_REPORT.md` — this file (Tasks 1, 15)
- `FRONTEND_BACKEND_ENDPOINT_DIFF.md` (2) · `FRONTEND_SCREEN_COVERAGE_MATRIX.md` (3) · `FRONTEND_ROLE_PERMISSION_ALIGNMENT.md` (4)
- `FRONTEND_API_ENVELOPE_ZOD_AUDIT.md` (5) · `FRONTEND_IDEMPOTENCY_ALIGNMENT.md` (6) · `FRONTEND_QUERY_INVALIDATION_AUDIT.md` (7)
- `FRONTEND_AUTH_BRANCH_AUDIT.md` (8) · `FRONTEND_FRONT_DESK_ALIGNMENT.md` (9) · `FRONTEND_MEASUREMENT_ALIGNMENT.md` (10)
- `FRONTEND_PRODUCTION_ALIGNMENT.md` (11) · `FRONTEND_OPERATIONAL_ALIGNMENT.md` (12) · `FRONTEND_UNKNOWN_API_USAGE.md` (13)
- `FRONTEND_PLAYWRIGHT_BACKEND_FLOW_COVERAGE.md` (14) · `FRONTEND_BACKEND_ALIGNMENT_QA_REPORT.md` (16) · `FRONTEND_BACKEND_ALIGNMENT_FIX_PLAN.md` (17)
- Artifacts: `docs/_fe_build.txt`, `docs/_openapi_paths.txt`
</content>
