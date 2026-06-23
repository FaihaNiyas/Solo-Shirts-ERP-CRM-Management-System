# Solo Shirts India ERP — Frontend Prompt Pack
### Phase-by-phase for Claude Design / Claude Code

**Use phase by phase.** Paste the **Master Prompt** first, then paste the **Phase Prompt** under it. One phase per Claude session — do not feed the whole file at once.

All phases are aligned to the completed Laravel 11 backend: versioned measurements, stock ledger, two-phase fabric reservation, branch-scoped permissions, gap-free invoice numbering, append-only audit, idempotency, and QR/PDF download controls.

---

## 📌 MASTER PROMPT (paste on top of every phase)

```text
You are a principal frontend architect, senior product designer, and React engineer
building the enterprise frontend for "Solo Shirts India ERP."

This is NOT a website redesign. This is a premium garment/tailoring ERP frontend for a
serious commercial project. The backend is a fully completed Laravel 11 API-first modular
monolith with strict business rules. Your job is to design and implement a frontend that
fits that backend exactly — no inventing endpoints, no guessing business logic.

=== STACK (LOCKED — do not propose alternatives) ===
- React + TypeScript
- Next.js App Router (file-based layouts, Server + Client Components)
- Tailwind CSS (mobile-first, container queries)
- shadcn/ui (owned component library, not consumed blindly)
- Motion for React (purposeful animation only)
- TanStack Query (server state, mutations, optimistic UI)
- TanStack Table (headless data grids)
- Zod (TypeScript-first schema validation at API/form boundaries)
- Storybook (component testing + accessibility)
- Playwright (end-to-end critical workflow automation)

=== BUSINESS CONTEXT ===
Garment tailoring ERP serving these roles (all branch-scoped except Owner):
Owner/Admin, Front Desk, Measurement Staff, Production Supervisor, Cutting Master,
Tailor, Kaja Button, QC Supervisor, Ironing Master, Re-Worker, Inventory Manager,
Accountant, Delivery Staff.

The frontend must feel premium, fast, trustworthy, and operationally clear on desktop,
tablet, and mobile. It must feel like a bespoke product, not a generic admin template.

=== BACKEND CONTRACT — FOLLOW STRICTLY ===
- Standard API envelope: { success, message, data, request_id } on every response.
- Every write endpoint requires an Idempotency-Key header. Frontend must generate and send it.
- Authorization is branch-scoped. Owner can work across branches; all others cannot.
- Measurements are APPEND-ONLY VERSIONED. Orders FK to measurement_version_id, never to
  a mutable measurement row. Never model measurements as edit-in-place forms.
- Production state lives on order_items, not orders. Order status is derived.
- Stock is a LEDGER. Always show remaining / reserved / available as three distinct values.
- Fabric allocation (reserve → consume / release) is handled by the CUTTING module,
  not the inventory module. Never call inventory endpoints to reserve fabric.
- Finance is append-only: invoice create, payment record, credit note. Never edit or delete.
- QR payloads are signed. Always validate through the backend — never parse client-side.
- Invoice numbers come from a row-locked counter. Gap-free. Never editable.
- Audit tables are INSERT-only at DB level. UI must reflect history, not editable state.
- Every error response carries a stable machine-readable code and a request_id.
- Never invent an endpoint. If something appears missing, mark it as
  "Backend gap — needs confirmation" and do not implement a placeholder.

=== ENDPOINT REFERENCE ===
Use these exact routes. No abbreviations, no variations.

Auth:
  POST /api/v1/auth/login
  POST /api/v1/auth/logout
  POST /api/v1/auth/refresh
  GET  /api/v1/auth/me
  POST /api/v1/auth/2fa/enable                          (returns QR setup data)
  POST /api/v1/auth/2fa/confirm
  POST /api/v1/auth/2fa/disable
  POST /api/v1/auth/switch-branch                       (Owner only)
  GET  /api/v1/branches
  POST /api/v1/branches                                 (Owner)
  PUT  /api/v1/branches/{id}                            (Owner)
  GET  /api/v1/users
  POST /api/v1/users                                    (Admin)
  PUT  /api/v1/users/{id}                               (Admin within branch; Owner all)
  DELETE /api/v1/users/{id}                             (Owner)
  POST /api/v1/users/{id}/assign-role

Customers:
  GET    /api/v1/customers?search=&page=
  POST   /api/v1/customers                                  (Idempotency-Key)
  GET    /api/v1/customers/{id}
  PUT    /api/v1/customers/{id}
  DELETE /api/v1/customers/{id}                             (soft delete)
  GET    /api/v1/customers/by-qr/{payload}
  GET    /api/v1/customers/{id}/family-members
  POST   /api/v1/customers/{id}/family-members              (Idempotency-Key)
  PUT    /api/v1/customers/{id}/family-members/{fid}
  DELETE /api/v1/customers/{id}/family-members/{fid}

Measurements:
  GET  /api/v1/customers/{cid}/measurements
  POST /api/v1/customers/{cid}/measurements                 (creates profile)
  GET  /api/v1/measurements/profiles/{id}/versions
  POST /api/v1/measurements/profiles/{id}/versions          (Idempotency-Key)
  GET  /api/v1/measurements/versions/{id}
  POST /api/v1/measurements/versions/{id}/approve           (Idempotency-Key)
  POST /api/v1/measurements/versions/{id}/reject
  GET  /api/v1/measurements/pending-approval

Orders:
  GET    /api/v1/orders?status=&customer=&from=&to=
  POST   /api/v1/orders                                     (Idempotency-Key)
  GET    /api/v1/orders/{id}
  PUT    /api/v1/orders/{id}
  POST   /api/v1/orders/{id}/items                          (Idempotency-Key)
  PUT    /api/v1/orders/{id}/items/{itemId}
  DELETE /api/v1/orders/{id}/items/{itemId}
  POST   /api/v1/orders/{id}/cancel
  GET    /api/v1/orders/{id}/job-card.pdf

Production:
  GET  /api/v1/production/board                             (branch-scoped via middleware)
  GET  /api/v1/production/items/{id}
  POST /api/v1/production/items/{id}/transition             (Idempotency-Key)
  GET  /api/v1/production/items/{id}/history

Cutting:
  GET  /api/v1/cutting/queue
  POST /api/v1/cutting/items/{id}/allocate-fabric           (Idempotency-Key)
  POST /api/v1/cutting/items/{id}/release-fabric
  POST /api/v1/cutting/items/{id}/start-cutting
  POST /api/v1/cutting/items/{id}/complete-cutting          (Idempotency-Key)
  GET  /api/v1/cutting/bundles/{id}
  GET  /api/v1/cutting/bundles/by-qr/{payload}

Tailoring:
  GET  /api/v1/tailoring/assignments?tailor=&status=
  POST /api/v1/tailoring/assignments                        (Idempotency-Key)
  POST /api/v1/tailoring/assignments/{id}/start
  POST /api/v1/tailoring/assignments/{id}/complete
  POST /api/v1/tailoring/assignments/{id}/reassign
  GET  /api/v1/tailoring/performance/{tailorId}?from=&to=

QC:
  POST /api/v1/qc/items/{id}/inspect                        (Idempotency-Key)
  POST /api/v1/qc/photos
  GET  /api/v1/qc/items/{id}/history
  POST /api/v1/qc/items/{id}/rework-override                (requires production.rework.override)
  GET  /api/v1/qc/defects/categories

Inventory:
  GET  /api/v1/inventory/fabric-rolls?type=&colour=&status=
  POST /api/v1/inventory/fabric-rolls
  GET  /api/v1/inventory/fabric-rolls/{id}
  POST /api/v1/inventory/fabric-rolls/{id}/adjust
  GET  /api/v1/inventory/fabric-rolls/by-qr/{payload}
  GET  /api/v1/inventory/movements?roll_id=&from=&to=
  GET  /api/v1/inventory/low-stock
  GET  /api/v1/inventory/fabric-types
  POST /api/v1/inventory/fabric-types
  PUT  /api/v1/inventory/fabric-types/{id}
  GET  /api/v1/inventory/suppliers
  POST /api/v1/inventory/suppliers
  PUT  /api/v1/inventory/suppliers/{id}
  GET  /api/v1/inventory/purchase-orders
  POST /api/v1/inventory/purchase-orders
  POST /api/v1/inventory/purchase-orders/{id}/place
  POST /api/v1/inventory/purchase-orders/{id}/cancel
  POST /api/v1/inventory/purchase-orders/{id}/receive

Damage Reports:
  POST /api/v1/damage-reports                               (Idempotency-Key)
  POST /api/v1/damage-reports/photos
  GET  /api/v1/damage-reports?status=&from=&to=
  GET  /api/v1/damage-reports/{id}
  POST /api/v1/damage-reports/{id}/approve                  (Idempotency-Key, Owner only)
  POST /api/v1/damage-reports/{id}/reject

Rack:
  GET  /api/v1/rack/slots
  POST /api/v1/rack/slots
  PUT  /api/v1/rack/slots/{id}
  POST /api/v1/rack/items/{itemId}/assign                   (Idempotency-Key)
  POST /api/v1/rack/items/{itemId}/release
  GET  /api/v1/rack/items/{itemId}/current-slot

Deliveries:
  GET  /api/v1/deliveries?status=&from=&to=
  POST /api/v1/deliveries
  POST /api/v1/deliveries/{id}/dispatch                     (Idempotency-Key)
  POST /api/v1/deliveries/{id}/confirm                      (Idempotency-Key)
  POST /api/v1/deliveries/{id}/attempt
  POST /api/v1/deliveries/{id}/cancel

Finance:
  GET  /api/v1/finance/invoices?from=&to=&status=
  POST /api/v1/finance/invoices                             (Idempotency-Key)
  GET  /api/v1/finance/invoices/{id}
  GET  /api/v1/finance/invoices/{id}/pdf
  POST /api/v1/finance/invoices/{id}/credit-note            (Idempotency-Key)
  GET  /api/v1/finance/payments?invoice_id=
  POST /api/v1/finance/payments                             (Idempotency-Key)
  GET  /api/v1/finance/orders/{id}/outstanding-balance
  GET  /api/v1/finance/dashboard/summary

QR:
  GET  /api/v1/qr/sign?type=&id=                       (qr.sign — server-side signed payload)
  GET  /api/v1/qr/decode/{payload}                     (qr.decode — verify and decode)

Documents:
  GET  /api/v1/documents/{id}/download
  POST /api/v1/documents/regenerate

Reports:
  GET  /api/v1/reports                                  (list available report kinds)
  POST /api/v1/reports/run
  GET  /api/v1/reports/jobs/{id}
  GET  /api/v1/reports/jobs/{id}/download

Notifications:
  GET  /api/v1/notifications?status=&channel=

Audit:
  GET  /api/v1/audit/activities?subject_type=&subject_id=&actor=&from=&to=
  GET  /api/v1/audit/transitions/{order_item_id}

=== UX GOALS ===
- Premium enterprise experience. Not a SaaS template. Not a Bootstrap admin panel.
- Front Desk workstation is the most important screen in the entire app.
- Measurement entry must include an animated garment visualizer using SVG + Motion.
- Clicking Full Sleeve / Half Sleeve / fit type / collar / cuff / pocket / pant must
  visually animate the garment, not just change a label.
- Motion clarifies state changes. It does not decorate every element.
- Accessibility: WCAG 2.2 AA minimum. Keyboard complete. Visible focus. Touch-friendly.
- Performance: LCP ≤ 2.5s, INP ≤ 200ms, CLS ≤ 0.1 at 75th percentile.

=== DESIGN DIRECTION ===
- Visual tone: premium tailoring craftsmanship + manufacturing precision.
- Elegant spacing, confident typography, restrained color palette, clear status colors.
- Design system built on top of shadcn/ui — owned and branded, not default.
- Core patterns: tables, filters, drawers, modals, steppers, status badges, approval chips,
  timelines, scan feedback flows, split-view workstations.

=== ARCHITECTURE RULES ===
- Prefer Server Components for read-heavy pages and shell composition.
- Use "use client" only where interactivity is required.
- Use loading.js, error.js, and empty states deliberately on every route.
- TanStack Query for all server state. Never useState + fetch.
- TanStack Table for all dense data grids.
- Zod at every API response boundary and every form submission.
- Motion only for meaningful transitions and micro-interactions.
- Business logic stays out of presentational components.
- Backend is source of truth for authorization. Frontend gates are UX, not security.
- Never store auth/session tokens in localStorage. Prefer HttpOnly cookie sessions.
- Every error modal must expose request_id so support can trace without screenshots.
- Mutations must be retry-safe, double-click-safe, and idempotency-key-aware.
- All endpoint strings must live in lib/api/endpoints.ts — never hardcoded in components.
- All API responses must be validated through Zod schemas in lib/api/schemas/.

=== DOMAIN MODULES ===
auth | users-branches | customers | family-members | measurements |
orders | production | cutting | tailoring | qc | inventory |
damage | rack | delivery | finance | documents | reports | notifications | audit

=== OUTPUT FORMAT (use in every phase) ===
Phase understood:
Tables / endpoints consumed (from existing backend):
Files to create:
Implementation summary (show only what changed or new):
Acceptance checklist:
Next recommended step:

When a section is N/A, write "N/A — <one-line reason>".

Now read the PHASE PROMPT below and start.
```

---

## 📌 PHASE 0 — Screenshot to Screen Design (Claude Design Workflow)

> **Run this before any code phase.** This is how you use Claude Design with screenshots
> to produce your UI before building it.

```text
PHASE 0: Screenshot-Driven Screen Design

GOAL
Use the uploaded screenshot(s) to design the exact screen layout before any code is written.
Output a complete screen specification that the build phases can implement directly.

HOW TO USE
1. Paste the Master Prompt first (as always).
2. Paste this Phase 0 prompt.
3. Upload your screenshot(s) — reference designs, existing UI, competitor screens, or
   your own rough sketches.
4. Claude will analyze the screenshot and produce a full screen spec.

WHAT TO UPLOAD
Upload one or more of:
- A screenshot of an existing screen you want to replace or improve
- A reference screenshot from another app for inspiration
- A rough sketch or wireframe photo
- A Figma export or mockup image

FOR EACH UPLOADED SCREENSHOT, DELIVER

1. SCREEN IDENTIFICATION
   - What screen / workflow does this represent in Solo Shirts ERP?
   - Which role(s) use this screen?
   - Which backend module and endpoints does it consume?

2. LAYOUT ANALYSIS
   - Describe the zone structure (left nav, content area, side panel, etc.)
   - What the key interactive areas are
   - What data is being displayed (from which backend fields?)
   - What actions are available (map to which POST/PUT/PATCH endpoints?)

3. IMPROVED SCREEN DESIGN
   Describe the Solo Shirts version of this screen:
   - Layout zones (desktop 3-column / tablet 2-column / mobile stacked)
   - Component list with shadcn/ui primitives used
   - Data fields and their backend field names
   - Status colors and badge logic
   - Motion spec: what transitions apply to this screen
   - Empty state
   - Loading skeleton
   - Error state with request_id display

4. COMPONENT SPEC
   For each major component on this screen:
   - Component name (PascalCase)
   - Props interface (TypeScript)
   - Which TanStack Query hook feeds it
   - Which Zod schema validates its data
   - Storybook story name

5. ACCESSIBILITY NOTES
   - Focus order
   - ARIA labels needed
   - Keyboard shortcuts if applicable

6. MOBILE VARIANT
   - How does this screen change on mobile?
   - Which zones collapse or stack?
   - Which actions move to a bottom sheet or FAB?

OUTPUT FORMAT
Produce a structured screen spec document.
Be concrete. Use real field names from the backend (measurement_version_id, not "version").
Do not invent endpoints that do not exist in the backend.
Do not produce code in Phase 0 — spec only.

After this phase is approved, proceed to the relevant build phase.
```

---

## 📌 PHASE 1 — Project Shell, Auth & Branch Context

```text
PHASE 1: Project Shell, Auth & Branch Context

GOAL
Bootstrap the Next.js App Router project, wire authentication, branch context, permission
gates, API client, and the core design tokens. This is the foundation every later phase
builds on. Nothing is hardcoded; all auth/session state comes from the backend.

BUSINESS REQUIREMENTS
- Developer can clone and run locally in under 10 minutes.
- Login, logout, token refresh, 2FA confirm all wired to backend endpoints.
- Branch context is visible in the shell. Owner sees branch switcher. Others do not.
- Permission gates prevent route access AND hide actions, not just hide actions.
- A failed request always shows request_id in the error state.

BACKEND ENDPOINTS CONSUMED
POST  /api/v1/auth/login
POST  /api/v1/auth/logout
POST  /api/v1/auth/refresh
GET   /api/v1/auth/me
POST  /api/v1/auth/2fa/confirm
POST  /api/v1/auth/switch-branch   (Owner only)
GET   /api/v1/branches             (Owner, Admin)

FILES TO CREATE
app/
  (auth)/
    login/page.tsx
    2fa/page.tsx
  (shell)/
    layout.tsx                     ← desktop shell (nav, topbar, branch indicator)
    loading.tsx
    error.tsx
  layout.tsx                       ← root layout (fonts, providers, global CSS)

lib/
  api/
    client.ts                      ← Axios or fetch wrapper: envelope parsing,
                                     request_id capture, idempotency-key injection,
                                     error normalization, retry on 401
    endpoints.ts                   ← typed endpoint constants
    types.ts                       ← ApiEnvelope<T>, ApiError, RequestId types

  auth/
    session.ts                     ← session helpers (cookie-based, no localStorage)
    permissions.ts                 ← hasPermission(), hasRole(), usePermission() hook
    branch-context.ts              ← useBranchContext() hook, BranchProvider

  query/
    keys.ts                        ← TanStack Query key factory (all keys in one place)
    client.ts                      ← QueryClient config (stale times, retry, error handler)

components/
  shell/
    AppShell.tsx
    TopBar.tsx
    SideNav.tsx
    BranchSwitcher.tsx
    NotificationBell.tsx
    UserMenu.tsx
    ErrorDrawer.tsx                ← shows request_id + error code on any failed request
  ui/                              ← shadcn/ui component re-exports (owned, branded)
  providers/
    QueryProvider.tsx
    BranchProvider.tsx

DESIGN SYSTEM FOUNDATION (also in Phase 1)
tailwind.config.ts:
  - Custom color tokens: brand, surface, muted, status-pending, status-active,
    status-complete, status-error, status-warning
  - Font: Inter or Geist (system-stack fallback)
  - Radius, shadow, spacing scale consistent with premium ERP feel

globals.css:
  - CSS custom properties for all tokens
  - Reduced-motion media query mapped to Motion's useReducedMotion
  - Data-density utilities: data-density="compact" | "default" | "comfortable"

SHELL STRUCTURE — DESKTOP
┌──────────────────────────────────────────────────────────┐
│ TopBar: Logo | Branch Indicator | Search | Notif | User  │
├────────────┬─────────────────────────────────────────────┤
│  SideNav   │  Page Content (route-level layout)          │
│  (role-    │                                             │
│  shaped)   │  loading.tsx skeleton                       │
│            │  error.tsx with request_id                  │
└────────────┴─────────────────────────────────────────────┘

SHELL STRUCTURE — MOBILE
┌──────────────────────┐
│ TopBar (compact)     │
├──────────────────────┤
│ Page Content         │
├──────────────────────┤
│ Bottom Nav (4 tabs,  │
│ role-shaped)         │
└──────────────────────┘

ROLE → LANDING ROUTES
Owner/Admin          → /dashboard
Front Desk           → /front-desk
Measurement Staff    → /measurements
Production Supervisor→ /production
Cutting Master       → /cutting
Tailor               → /tailoring
Kaja Button          → /tailoring (filtered view)
QC Supervisor        → /qc
Ironing Master       → /qc/ironing
Re-Worker            → /qc/rework
Inventory Manager    → /inventory
Accountant           → /finance
Delivery Staff       → /delivery

PERMISSION GATE PATTERN
// In any server component:
import { requirePermission } from "@/lib/auth/permissions";
await requirePermission("customers.view"); // redirects to /unauthorized if denied

// In any client component:
const { can } = usePermission();
{can("customers.create") && <Button>New Customer</Button>}

API CLIENT RULES
- Every request attaches: Authorization header, X-Branch-Id header, Accept: application/json
- Every mutation generates and attaches Idempotency-Key (UUID v4, stored per-action in memory)
- Every response parsed through ApiEnvelope<T> type guard
- 401 → trigger silent refresh → retry once → redirect to /login
- 422 → Zod parse errors → map to form field errors
- 4xx/5xx → normalize to { message, code, request_id } → surface in ErrorDrawer

TESTS (Playwright)
- Login → correct 2FA → lands on correct role page
- Login → wrong password → shows error
- Owner → sees branch switcher
- Non-owner → branch switcher not rendered
- Expired session → auto-refresh → continues without user seeing logout

ACCEPTANCE CHECKLIST
[ ] npm run dev works on clean clone
[ ] Login + 2FA flow complete
[ ] Auth me loaded into context on every page
[ ] Branch switcher works for Owner only
[ ] SideNav shows only role-appropriate items
[ ] All requests include branch header
[ ] Idempotency key generated per mutation
[ ] Error drawer shows request_id on any failed request
[ ] Token never stored in localStorage
[ ] Tailwind tokens + CSS variables defined
[ ] reduced-motion respected by Motion components
[ ] Offline banner renders when navigator.onLine = false
[ ] Slow-network state triggers after 5s on pending request
[ ] Unsaved-changes warning blocks navigation when form is dirty
```

---

## 📌 PHASE 1A — API Contract Alignment Matrix

```text
PHASE 1A: API Contract Alignment Matrix

GOAL
Before building any screen, create a complete frontend-to-backend API contract map.
This document is the single reference that prevents invented endpoints, missing
permissions, and unhandled error codes. Build it once; keep it updated each phase.

BUSINESS REQUIREMENTS
- No screen is implemented until its endpoints are mapped here.
- Every mutation endpoint documents Idempotency-Key requirement.
- Every error state surfaces request_id.
- Backend gaps are marked explicitly — never silently invented.

DELIVERABLE
Create /docs/api-contract-map.md with this table structure:

| Frontend Screen | User Action | Backend Endpoint | Method | Required Permission | Idempotency-Key | Error Codes To Handle | Query Keys To Invalidate |
|---|---|---|---|---|---|---|---|
| /front-desk | Customer QR scan | /api/v1/customers/by-qr/{payload} | GET | customers.view | No | CUSTOMER_NOT_FOUND, QR_INVALID | — |
| /front-desk | Create order | /api/v1/orders | POST | orders.create | Yes | IDEMPOTENCY_CONFLICT, VALIDATION_FAILED, MEASUREMENT_NOT_APPROVED | orders.list, customers.detail, dashboard.summary |
| /measurements | Create version | /api/v1/measurements/profiles/{id}/versions | POST | measurements.create | Yes | IDEMPOTENCY_CONFLICT, APPROVAL_REQUIRED | measurements.versions, measurements.pending |
| /production | Transition item | /api/v1/production/items/{id}/transition | POST | production.transition.{state} | Yes | INVALID_STATE_TRANSITION, IDEMPOTENCY_CONFLICT | production.board, production.item, orders.detail |
| /inventory/fabric-rolls | Allocate fabric | /api/v1/cutting/items/{id}/allocate-fabric | POST | fabric.allocate | Yes | INSUFFICIENT_AVAILABLE_STOCK, IDEMPOTENCY_CONFLICT | inventory.fabricRoll, cutting.queue |
| /finance/invoices | Record payment | /api/v1/finance/payments | POST | finance.payment.record | Yes | PAYMENT_EXCEEDS_BALANCE, IDEMPOTENCY_CONFLICT | finance.invoice, finance.payments, finance.outstanding |
| /delivery | Confirm OTP | /api/v1/deliveries/{id}/confirm | POST | deliveries.confirm | Yes | WRONG_OTP, OTP_EXPIRED, OTP_LOCKED | delivery.queue, production.board |

RULES
- Backend prompt pack is the source of truth. Use the Endpoint Alignment Rule above.
- If a backend endpoint is missing, mark column as: "Backend gap — needs confirmation"
- Never implement a frontend call against a guessed endpoint.
- Every POST/PUT/PATCH/DELETE must specify Idempotency-Key behavior.
- Every error code must map to a user-readable message in the UI error handler.

STARTER ROWS TO COMPLETE (one row per backend endpoint)
Auth: login, logout, refresh, me, 2fa confirm, switch-branch
Customers: list, create, show, by-qr, family-members CRUD
Measurements: profiles list, create profile, versions list, create version, approve, reject
Orders: list, create, show, update, add item, cancel item, cancel order, job-card.pdf
Production: board, item show, transition, history
Cutting: queue, allocate-fabric, release-fabric, start, complete, bundles by-qr
Tailoring: assignments, start, complete, reassign, performance
QC: inspect, upload photo, history, rework-override, defect categories
Inventory: fabric-rolls list/show/create/adjust/by-qr, movements, low-stock,
           fabric-types, suppliers, purchase-orders, receive
Damage: create, upload photo, list, show, approve, reject
Rack: slots list/create/update, assign, release, current-slot
Delivery: list, create, dispatch, confirm, attempt, cancel
Finance: invoices, pdf, credit-note, payments, outstanding-balance, dashboard
Documents: download, regenerate
Reports: run, poll, download
Notifications: list
Audit: activities, transitions

ACCEPTANCE CHECKLIST
[ ] /docs/api-contract-map.md exists and is committed
[ ] Every phase endpoint is mapped before implementation begins
[ ] All mutation endpoints marked with Idempotency-Key requirement
[ ] All permissions mapped per action
[ ] All error codes listed per endpoint
[ ] No "invented" endpoint exists in the map
[ ] Query invalidation documented per mutation
```

---

## 📌 PHASE 1B — Typed API Client & Zod Contract

```text
PHASE 1B: Typed API Client & Zod Contract

GOAL
Prevent endpoint spelling mistakes and response mismatches by centralizing all API
calls, endpoint constants, and Zod schemas. No page component ever hardcodes a URL
string or calls fetch directly.

BUSINESS REQUIREMENTS
- All API calls go through the typed client, never raw fetch in components.
- All response shapes are Zod-validated at the boundary.
- All endpoint strings live in one file.
- TanStack Query hooks consume typed API functions, not raw strings.
- If an OpenAPI spec is generated from the backend, generate TypeScript types from it.
  Until then, maintain Zod schemas manually in lib/api/schemas/.

FILES TO CREATE

lib/api/
  endpoints.ts         ← all endpoint path constants and builders
  client.ts            ← Axios/fetch wrapper: envelope parsing, idempotency key
                          injection, error normalization, 401 refresh, request_id
  types.ts             ← ApiEnvelope<T>, ApiError, RequestId, PaginatedResponse<T>
  schemas/
    auth.ts            ← LoginResponse, UserSchema, BranchSchema
    customers.ts       ← CustomerSchema, FamilyMemberSchema
    measurements.ts    ← ProfileSchema, VersionSchema, PendingApprovalSchema
    orders.ts          ← OrderSchema, OrderItemSchema, JobCardUrlSchema
    production.ts      ← ProductionItemSchema, TransitionSchema, BoardSchema
    cutting.ts         ← AllocationSchema, BundleSchema
    qc.ts              ← InspectionSchema, DefectSchema, PhotoSchema
    inventory.ts       ← FabricRollSchema, MovementSchema, SupplierSchema, POSchema
    damage.ts          ← DamageReportSchema
    rack.ts            ← RackSlotSchema, AssignmentSchema
    delivery.ts        ← DeliverySchema
    finance.ts         ← InvoiceSchema, PaymentSchema, CreditNoteSchema, BalanceSchema
    documents.ts       ← DocumentSchema, SignedUrlSchema
    reports.ts         ← ReportJobSchema
    notifications.ts   ← NotificationSchema
    audit.ts           ← ActivitySchema, TransitionHistorySchema
  hooks/               ← one file per domain, e.g. useCustomers.ts, useOrders.ts
                          all hooks use typed API functions + TanStack Query

ENDPOINT CONSTANTS PATTERN
// lib/api/endpoints.ts
export const API = {
  auth: {
    login:        () => `/api/v1/auth/login`,
    logout:       () => `/api/v1/auth/logout`,
    refresh:      () => `/api/v1/auth/refresh`,
    me:           () => `/api/v1/auth/me`,
    twoFaEnable:  () => `/api/v1/auth/2fa/enable`,
    twoFaConfirm: () => `/api/v1/auth/2fa/confirm`,
    twoFaDisable: () => `/api/v1/auth/2fa/disable`,
    switchBranch: () => `/api/v1/auth/switch-branch`,
  },
  branches: {
    list:   () => `/api/v1/branches`,
    create: () => `/api/v1/branches`,
    update: (id: string) => `/api/v1/branches/${id}`,
  },
  users: {
    list:       () => `/api/v1/users`,
    create:     () => `/api/v1/users`,
    update:     (id: string) => `/api/v1/users/${id}`,
    delete:     (id: string) => `/api/v1/users/${id}`,
    assignRole: (id: string) => `/api/v1/users/${id}/assign-role`,
  },
  qr: {
    sign:   (type: string, id: string) => `/api/v1/qr/sign?type=${type}&id=${id}`,
    decode: (payload: string) => `/api/v1/qr/decode/${payload}`,
  },
  customers: {
    list:    () => `/api/v1/customers`,
    create:  () => `/api/v1/customers`,
    show:    (id: string) => `/api/v1/customers/${id}`,
    update:  (id: string) => `/api/v1/customers/${id}`,
    delete:  (id: string) => `/api/v1/customers/${id}`,
    byQr:    (payload: string) => `/api/v1/customers/by-qr/${payload}`,
    familyMembers: (id: string) => `/api/v1/customers/${id}/family-members`,
  },
  measurements: {
    profiles: (cid: string) => `/api/v1/customers/${cid}/measurements`,
    versions: (profileId: string) => `/api/v1/measurements/profiles/${profileId}/versions`,
    version:  (vId: string) => `/api/v1/measurements/versions/${vId}`,
    approve:  (vId: string) => `/api/v1/measurements/versions/${vId}/approve`,
    reject:   (vId: string) => `/api/v1/measurements/versions/${vId}/reject`,
    pending:  () => `/api/v1/measurements/pending-approval`,
  },
  orders: {
    list:     () => `/api/v1/orders`,
    create:   () => `/api/v1/orders`,
    show:     (id: string) => `/api/v1/orders/${id}`,
    cancel:   (id: string) => `/api/v1/orders/${id}/cancel`,
    jobCard:  (id: string) => `/api/v1/orders/${id}/job-card.pdf`,
    items:    (id: string) => `/api/v1/orders/${id}/items`,
  },
  production: {
    board:      () => `/api/v1/production/board`,
    item:       (id: string) => `/api/v1/production/items/${id}`,
    transition: (id: string) => `/api/v1/production/items/${id}/transition`,
    history:    (id: string) => `/api/v1/production/items/${id}/history`,
  },
  cutting: {
    queue:         () => `/api/v1/cutting/queue`,
    allocate:      (id: string) => `/api/v1/cutting/items/${id}/allocate-fabric`,
    release:       (id: string) => `/api/v1/cutting/items/${id}/release-fabric`,
    startCutting:  (id: string) => `/api/v1/cutting/items/${id}/start-cutting`,
    completeCutting:(id: string) => `/api/v1/cutting/items/${id}/complete-cutting`,
    bundle:        (id: string) => `/api/v1/cutting/bundles/${id}`,
    bundleByQr:    (payload: string) => `/api/v1/cutting/bundles/by-qr/${payload}`,
  },
  qc: {
    inspect:         (id: string) => `/api/v1/qc/items/${id}/inspect`,
    uploadPhoto:     () => `/api/v1/qc/photos`,
    history:         (id: string) => `/api/v1/qc/items/${id}/history`,
    reworkOverride:  (id: string) => `/api/v1/qc/items/${id}/rework-override`,
    defectCategories:() => `/api/v1/qc/defects/categories`,
  },
  inventory: {
    fabricRolls:   () => `/api/v1/inventory/fabric-rolls`,
    fabricRoll:    (id: string) => `/api/v1/inventory/fabric-rolls/${id}`,
    fabricRollByQr:(payload: string) => `/api/v1/inventory/fabric-rolls/by-qr/${payload}`,
    movements:     () => `/api/v1/inventory/movements`,
    lowStock:      () => `/api/v1/inventory/low-stock`,
    suppliers:     () => `/api/v1/inventory/suppliers`,
    purchaseOrders:() => `/api/v1/inventory/purchase-orders`,
    poReceive:     (id: string) => `/api/v1/inventory/purchase-orders/${id}/receive`,
  },
  damage: {
    create:  () => `/api/v1/damage-reports`,
    photo:   () => `/api/v1/damage-reports/photos`,
    list:    () => `/api/v1/damage-reports`,
    show:    (id: string) => `/api/v1/damage-reports/${id}`,
    approve: (id: string) => `/api/v1/damage-reports/${id}/approve`,
    reject:  (id: string) => `/api/v1/damage-reports/${id}/reject`,
  },
  rack: {
    slots:       () => `/api/v1/rack/slots`,
    slot:        (id: string) => `/api/v1/rack/slots/${id}`,
    assign:      (itemId: string) => `/api/v1/rack/items/${itemId}/assign`,
    release:     (itemId: string) => `/api/v1/rack/items/${itemId}/release`,
    currentSlot: (itemId: string) => `/api/v1/rack/items/${itemId}/current-slot`,
  },
  deliveries: {
    list:     () => `/api/v1/deliveries`,
    create:   () => `/api/v1/deliveries`,
    dispatch: (id: string) => `/api/v1/deliveries/${id}/dispatch`,
    confirm:  (id: string) => `/api/v1/deliveries/${id}/confirm`,
    attempt:  (id: string) => `/api/v1/deliveries/${id}/attempt`,
    cancel:   (id: string) => `/api/v1/deliveries/${id}/cancel`,
  },
  finance: {
    invoices:   () => `/api/v1/finance/invoices`,
    invoice:    (id: string) => `/api/v1/finance/invoices/${id}`,
    invoicePdf: (id: string) => `/api/v1/finance/invoices/${id}/pdf`,
    creditNote: (id: string) => `/api/v1/finance/invoices/${id}/credit-note`,
    payments:   () => `/api/v1/finance/payments`,
    outstanding:(orderId: string) => `/api/v1/finance/orders/${orderId}/outstanding-balance`,
    dashboard:  () => `/api/v1/finance/dashboard/summary`,
  },
  reports: {
    list:     () => `/api/v1/reports`,
    run:      () => `/api/v1/reports/run`,
    job:      (id: string) => `/api/v1/reports/jobs/${id}`,
    download: (id: string) => `/api/v1/reports/jobs/${id}/download`,
  },
  notifications: {
    list: () => `/api/v1/notifications`,
  },
  audit: {
    activities:  () => `/api/v1/audit/activities`,
    transitions: (itemId: string) => `/api/v1/audit/transitions/${itemId}`,
  },
  documents: {
    download:   (id: string) => `/api/v1/documents/${id}/download`,
    regenerate: () => `/api/v1/documents/regenerate`,
  },
} as const;

IDEMPOTENCY KEY GENERATION
// lib/api/client.ts
import { v4 as uuid } from 'uuid';
// Store per-action key in memory (not localStorage — not auth data but avoids cross-tab confusion)
const idempotencyKeys = new Map<string, string>();
export function getIdempotencyKey(actionId: string): string {
  if (!idempotencyKeys.has(actionId)) {
    idempotencyKeys.set(actionId, uuid());
  }
  return idempotencyKeys.get(actionId)!;
}
export function clearIdempotencyKey(actionId: string): void {
  idempotencyKeys.delete(actionId);
}
// Usage: on successful mutation, clearIdempotencyKey(actionId) so next action gets fresh key.
// On retry of same failed action, same key is reused (idempotent behavior).

ZOD USAGE RULE
// Every API response must go through:
const data = ApiEnvelopeSchema(CustomerSchema).parse(rawResponse);
// Never trust raw response shapes. A Zod parse failure means backend API changed —
// surface it as a structured error with request_id, not an uncaught exception.

ACCEPTANCE CHECKLIST
[ ] lib/api/endpoints.ts exists with all domains
[ ] No raw URL string in any page or component file
[ ] lib/api/schemas/ has a file per domain
[ ] Every API response validated through Zod schema
[ ] ApiEnvelope<T> parsed centrally in client.ts
[ ] request_id and error code normalized in client.ts error handler
[ ] Idempotency key generator in client.ts
[ ] All TanStack Query hooks in lib/api/hooks/ use typed API functions
[ ] TypeScript: no `any` in API layer
```

---

## 📌 PHASE 2 — Design System & Shared Component Library

```text
PHASE 2: Design System & Shared Component Library

GOAL
Build the owned component library on top of shadcn/ui. Every component is branded,
accessible, and Storybook-documented. No raw shadcn/ui primitives in page code —
always the wrapped, themed version.

BUSINESS REQUIREMENTS
- Every component reflects the premium tailoring / manufacturing visual tone.
- Status badges, approval chips, and timeline items are first-class components.
- Dense data mode (compact tables for production queues, inventory) available.
- All components pass WCAG 2.2 AA in Storybook accessibility addon.

BACKEND ENDPOINTS CONSUMED
N/A — this phase is pure component library.

COMPONENT LIST (all in components/ui/ or components/shared/)

DATA DISPLAY
- DataTable          ← TanStack Table wrapper: sortable, filterable, paginated, row-select
- StatusBadge        ← variant: pending | active | complete | error | warning | info
- ApprovalChip       ← pending_approval | approved | rejected with icon
- Timeline           ← vertical event list with actor, timestamp, note, status dot
- MetricCard         ← large number + label + trend indicator
- InfoGrid           ← key-value grid used in customer/order detail panels
- QRCodeDisplay      ← renders signed QR, with copy + download actions

FORMS & INPUT
- FormField          ← wraps shadcn/ui FormItem + Zod error display
- SearchInput        ← debounced, with clear button and loading spinner
- PhoneInput         ← India phone format, masked
- MeasurementInput   ← number with unit (cm/inch), min/max guard, threshold warning
- DatePicker         ← calendar popover with business-date awareness
- FilterBar          ← horizontal set of Select + DateRange + Search, collapsible on mobile
- StepperNav         ← multi-step form navigation (used in order create, measurement entry)

FEEDBACK & STATUS
- LoadingSkeleton    ← page-level and card-level variants
- EmptyState         ← icon + message + optional action button
- ErrorState         ← message + request_id copy button + retry action
- ConfirmDialog      ← destructive / warning / info variants with reason input option
- ToastNotification  ← success | error | warning | info with request_id on error

NAVIGATION & LAYOUT
- PageHeader         ← title + breadcrumb + action buttons slot
- SplitView          ← left panel + right panel layout (used in front desk, inventory)
- DrawerPanel        ← right-side sheet for detail/edit without full navigation
- ModalDialog        ← center modal with accessible focus trap
- SectionCard        ← card with header, content, optional footer
- CollapsibleSection ← used in measurement form groups and order summary

OPERATIONAL PATTERNS
- ScanFeedback            ← QR scan result overlay: success (green pulse) / error (red shake)
- IdempotencyGuard        ← wraps any mutation button, prevents double submit, shows retry state
- VersionDiffView         ← side-by-side or inline diff of two measurement versions,
                             changed fields highlighted with Motion animation
- ApprovalInbox           ← list of pending approval items with quick approve/reject actions
- BranchTag               ← shows branch name, used in Owner cross-branch views
- MeasurementGuideAnimator← animated human silhouette + tape measure guide per measurement
                             field. SVG + Motion. Bidirectional (click body part → focus input).
                             Guide toggle for veteran staff. Full spec in Phase 4.
- HelpTooltip             ← contextual help overlay for any field or action. Props: title,
                             body, placement. Triggered by ? icon. Dismissed on click-outside.
                             Used for first-time guidance on measurement form, production board,
                             and finance screens. Does not block the UI.
- OnboardingChecklist     ← role-based first-login checklist shown as a collapsible sidebar
                             card. Checks off as staff completes each action (create customer,
                             take measurement, create order, etc.). Dismissed permanently after
                             all items complete. State stored in preferences.
- CurrencyDisplay         ← formats all ₹ amounts using Indian locale (en-IN):
                             ₹1,42,500 not ₹142,500. Used on every price/amount in the UI.
                             Never hardcode number formatting — always use this component.
- OverdueTag              ← amber/red badge shown on production cards and order rows when
                             item has exceeded its stage SLA. Shows "X days overdue".

MOTION RULES (enforced in this phase)
- Page transitions: fade + slight upward translate (80ms). Skip for reduced-motion.
- Drawer: slide from right (200ms ease-out).
- Modal: scale from 0.97 to 1.0 + fade (150ms).
- Toast: slide in from bottom-right (150ms), auto-dismiss 4s.
- Skeleton → content: opacity cross-fade (100ms).
- Status badge change: brief background flash (120ms, ease-in-out).
- Do NOT animate: table rows on scroll, form labels, nav items on hover.

STORYBOOK SETUP
- One story per component: Default, Loading, Empty, Error, Dark variants.
- Accessibility addon: a11y plugin, every story must pass.
- Interaction addon: test key user interactions (click, keyboard nav) in stories.
- Run: npx storybook build in CI, fail on a11y errors.

ACCEPTANCE CHECKLIST
[ ] All components in Storybook with Default + state variants
[ ] All components pass a11y addon checks
[ ] StatusBadge covers all production states from backend state machine
[ ] VersionDiffView animates changed fields correctly
[ ] DataTable handles empty, loading, error, and paginated states
[ ] IdempotencyGuard prevents double submission in Storybook interaction test
[ ] Motion respects prefers-reduced-motion across all animated components
[ ] All components use Tailwind design tokens, no hardcoded hex colors
```

---

## 📌 PHASE 2B — Global Settings & User Preferences

```text
PHASE 2B: Global Settings & User Preferences

GOAL
Build the settings area that controls every user-facing preference in the ERP:
theme, colors, font size, data density, accessibility, animation, measurement unit,
and per-module defaults. This phase runs AFTER Phase 2 (design system is ready)
and BEFORE Phase 3 (domain phases consume these preferences).

All domain phases that follow (Phase 3 onward) must READ from the preferences context,
not set their own isolated localStorage keys. This is the single source of truth for
all non-sensitive UI preferences.

BUSINESS REQUIREMENTS
- Every user can customize their own UI experience.
- Brand color change is Owner/Admin only (permission-gated).
- Preferences persist after refresh and across sessions.
- On a shared terminal (multiple staff on the same device), each user's preferences
  are stored under their own user_id — one user's "compact" mode does not affect another.
- If a backend user preferences API is added later, the frontend syncs automatically.
  Until then, localStorage with user-scoped keys is the storage layer.
- No auth tokens or session data ever stored alongside preferences in localStorage.

BACKEND ENDPOINTS CONSUMED
N/A — no backend preference API exists in the current backend pack.
Storage: localStorage, key pattern: "prefs:{user_id}:{preference_key}"
When backend adds GET/POST /api/v1/users/preferences, wire useUserPreferences hook
to sync with it. Hook interface stays the same for all consumers.

ROUTES
/settings                       → redirect to /settings/appearance
/settings/appearance            → theme, colors, font, density, radius
/settings/preferences           → language, units, currency, date format, defaults
/settings/accessibility         → motion, contrast, focus, touch, text size
/settings/notifications         → WhatsApp | SMS | Email preferences
/settings/front-desk            → Front Desk module defaults
/settings/production            → Production board defaults
/settings/inventory             → Inventory view defaults
/settings/finance               → Finance display defaults

GLOBAL PREFERENCE CONTEXT
Create: lib/preferences/PreferencesContext.tsx
Exports: usePreferences() hook — all domain components read from this, never from
         raw localStorage directly.
Schema (Zod-validated on load):
{
  theme: "light" | "dark" | "system"          // default: "system"
  brandColor: string                           // hex, Owner/Admin only, default: brand token
  accentColor: string                          // hex, default: accent token
  sidebarStyle: "compact" | "expanded"         // default: "expanded"
  fontSize: "small" | "default" | "large"      // default: "default"
  dataDensity: "compact" | "default" | "comfortable"  // default: "default"
  borderRadius: "soft" | "rounded" | "sharp"   // default: "rounded"
  language: "en" | "ta" | "hi" | "kn"         // default: "en" — others are placeholders
  dateFormat: "DD/MM/YYYY" | "MM/DD/YYYY" | "YYYY-MM-DD"  // default: "DD/MM/YYYY"
  currency: "INR"                              // default: INR, only INR supported now
  measurementUnit: "cm" | "inches"             // default: "cm"
  animationEnabled: boolean                    // default: true
  reducedMotion: boolean                       // default: false (auto-detects prefers-reduced-motion)
  soundFeedback: boolean                       // default: false
  autoSaveDraft: boolean                       // default: true
  notificationChannel: "whatsapp" | "email" | "sms"     // default: "sms" (backend channel enum)
  frontDesk: {
    defaultSearchMethod: "name" | "phone" | "qr"    // default: "name"
    defaultGarmentType: string                        // default: "shirt"
    defaultSleeveOption: "full" | "half"              // default: "full"
    defaultFitOption: "slim" | "regular" | "loose"    // default: "regular"
    showGarmentVisualizer: boolean                    // default: true
    showMeasurementGuide: boolean                     // default: true
  }
  production: {
    defaultView: "kanban" | "list"                    // default: "kanban"
    showOnlyAssigned: boolean                         // default: false
    autoRefreshInterval: 15 | 30 | 60 | 0            // default: 30 (0 = off)
    highlightOverdue: boolean                         // default: true
  }
  inventory: {
    showLowStockAlert: boolean                        // default: true
    defaultRollView: "card" | "table"                 // default: "table"
    alwaysShowStockBreakdown: boolean                 // default: true
  }
  finance: {
    currencyPlacement: "before" | "after"             // default: "before"
    showBalanceWarningBadges: boolean                 // default: true
    confirmBeforePayment: boolean                     // default: true
  }
  dashboard: {
    pinnedWidgets: string[]                           // widget ids pinned to top row
    hiddenWidgets: string[]                           // widget ids hidden entirely
    widgetOrder: string[]                             // ordered list of widget ids
    compactMode: boolean                              // default: false
    defaultLanding: string                            // route, determined by role default
  }
}

STORAGE RULES
- localStorage key: "prefs:{user_id}" — one JSON object per user.
- Never store: tokens, session IDs, passwords, branch-scoped business data.
- On user switch (logout → new login): load the new user's prefs, not the previous user's.
- On first login (no prefs found): use schema defaults. Write to localStorage immediately.
- Validate with Zod on load. If validation fails (corrupt data): reset to defaults silently.

THEME IMPLEMENTATION
- Theme is applied via a data-theme attribute on <html>: data-theme="dark" | "light"
- CSS custom properties switch based on data-theme: --color-bg, --color-surface,
  --color-text, --color-border, --color-muted, etc.
- All shadcn/ui components, charts, tables, modals, drawers, garment SVG, and
  measurement guide silhouette must consume CSS variables only. No hardcoded hex anywhere.
- System mode: detect prefers-color-scheme via matchMedia, update on change.
- No flash on load: set data-theme in a blocking inline script in <head> before
  React hydrates. Read from localStorage synchronously in that script.

BRAND COLOR CUSTOMIZATION
- Owner/Admin only (usePermission guard: "settings.brand_color").
- Color picker: allow hex input OR a preset palette of 8 curated colors.
- Live preview: changes update CSS variable --color-brand INSTANTLY on the settings page.
- "Apply globally" button: writes to preferences, updates across full app.
- "Reset to default" button: reverts to original brand token.
- Do NOT apply color changes until user explicitly clicks "Apply globally."
  Preview is local to the settings panel only.

FONT SIZE IMPLEMENTATION
- Small: --font-scale: 0.875
- Default: --font-scale: 1
- Large: --font-scale: 1.125
- All font-size values use: calc(var(--font-base) * var(--font-scale))
- This single variable scales the entire UI proportionally.

DATA DENSITY IMPLEMENTATION
- Applied via data-density attribute on <html> or a wrapper div.
- Compact: tighter padding, smaller row heights, smaller card padding.
- Default: standard spacing.
- Comfortable: more whitespace, larger touch targets (useful on tablets).
- TanStack Table row heights, SectionCard padding, form field spacing all respond to
  the data-density attribute via Tailwind data variant: data-[density=compact]:py-1

LANGUAGE / i18n
- Use react-i18next as the base.
- English (en) is the only fully implemented locale at this phase.
- Tamil (ta), Hindi (hi), Kannada (kn) are PLACEHOLDERS: locale files exist but
  contain English strings. Teams can fill translations later.
- No UI text should be hardcoded as string literals in JSX.
  Always: const { t } = useTranslation(); → {t("key")}
- This phase sets up the infrastructure only. Translation strings can be added
  incrementally without touching component code.

ANIMATION & MOTION PREFERENCE
- animationEnabled = false: wraps all Motion components in a MotionConfig with
  { transition: { duration: 0 } }. Effectively instant.
- reducedMotion = true: same as animationEnabled = false PLUS respects
  prefers-reduced-motion media query automatically via useReducedMotion() from Motion.
- soundFeedback = true: plays a short audio beep (Web Audio API, not a file dependency)
  on QR scan success (440Hz, 80ms) and scan failure (220Hz, 80ms).
  Must be user-initiated first (browser autoplay policy): first scan in a session
  that has sound enabled will prompt for permission if not already granted.

SETTINGS UI LAYOUT
- Left sidebar nav: links to each settings sub-route.
- Right content area: the settings form for that route.
- Each settings page has: title, description, the form controls, [Save] button,
  and a live preview strip (shows a mini version of the affected component).
- [Reset to defaults] button on each page.
- Mobile: sidebar becomes a dropdown or tab row at the top.

SETTINGS PAGES IN DETAIL

/settings/appearance
  Theme selector: three cards (Light / Dark / System) with icon + label, no dropdown.
  Brand color (Owner/Admin only): color swatch + picker + preset palette + reset.
  Accent color: same.
  Sidebar style: two cards (Compact / Expanded) with illustration.
  Font size: slider with three stops + live text preview.
  Data density: three cards + live table row preview.
  Border radius: three cards (Soft / Rounded / Sharp) + live button preview.

/settings/preferences
  Language: dropdown (English + 3 placeholders marked "(coming soon)").
  Date format: radio group with example dates.
  Currency: INR only (static label, no selector needed yet).
  Measurement unit: toggle switch Inches | CM — applies to all measurement inputs.
  Notification channel: radio group.
  Default landing page: shown as read-only (determined by role, not user-settable).
  Auto-save draft: toggle switch.

/settings/accessibility
  Reduced motion: toggle (overrides animationEnabled globally).
  High contrast mode: toggle (applies data-contrast="high" on <html>).
  Larger text: toggle (shortcut for fontSize = "large").
  Always visible focus ring: toggle (data-focus-always="true" on <html>,
    adds a forced focus ring via CSS regardless of mouse/keyboard input mode).
  Touch-friendly mode: toggle (increases data-density to "comfortable" on tablets,
    enlarges all interactive targets to 48px minimum).
  Keyboard shortcuts: [View shortcuts] button → modal with full keyboard reference.

/settings/notifications
  Per-event toggles:
    Measurement approval needed → WhatsApp | SMS | Email | Off
    Low stock alert             → WhatsApp | SMS | Email | Off
    Rework exceeded             → SMS | Email | Off
    Delivery failed             → WhatsApp | SMS | Email | Off
  (WhatsApp/Email channels are stored as preferences; backend decides actual delivery.)

/settings/front-desk
  Default customer search method: radio group.
  Default garment type: dropdown.
  Default sleeve option: toggle.
  Default fit option: radio group.
  Show garment visualizer panel: toggle.
  Enable measurement guide animation: toggle — THIS IS THE CANONICAL CONTROL.
  (Phase 4's quick-toggle in the form header syncs TO this setting via usePreferences().)

/settings/production
  Default board view: Kanban | List (radio + illustration).
  Show only assigned items by default: toggle.
  Auto-refresh interval: segmented control (15s / 30s / 1min / Off).
  Highlight overdue items: toggle + color swatch for overdue highlight color.

/settings/inventory
  Show low-stock alert: toggle.
  Default roll view: Card | Table (radio).
  Always show remaining/reserved/available: toggle (cannot be turned off for Inventory Manager role).

/settings/finance
  Currency symbol placement: Before (₹1,000) | After (1,000₹) — radio.
  Show balance warning badges: toggle.
  Confirm before every payment entry: toggle (recommended ON for all roles).

RECONCILIATION WITH PHASE 4 GUIDE TOGGLE
Phase 4's MeasurementGuideAnimator has a quick toggle in the form header.
That toggle must:
  - READ initial value from: usePreferences().frontDesk.showMeasurementGuide
  - WRITE changes back to: updatePreference("frontDesk.showMeasurementGuide", value)
  It does NOT maintain its own localStorage key. The Settings module is canonical.
  The quick toggle in the form is a convenience shortcut, not a separate setting.

RECONCILIATION WITH PHASE 1 DESIGN TOKENS
Phase 1 set up CSS variables in globals.css. This phase takes over responsibility for:
  - Updating --color-brand dynamically via JS (document.documentElement.style.setProperty)
  - Setting data-theme on <html>
  - Setting data-density on <html>
  - Setting data-contrast on <html>
  - Setting --font-scale on <html>
All of these are wired through PreferencesContext — Phase 1 tokens are the defaults,
this phase makes them dynamic.

TESTS (Playwright)
- Login as Owner → /settings/appearance → change brand color → apply → all buttons update
- Login as Front Desk → /settings/appearance → brand color picker not visible (permission gate)
- Toggle dark mode → UI switches instantly → refresh → dark mode persists
- Toggle fontSize to Large → text visibly larger → all pages affected
- Toggle measurementUnit to Inches → measurement form inputs show "in" unit
- Toggle animationEnabled OFF → measurement guide shows static (no Motion animation)
- Login as User A → set compact density → logout → login as User B → density is default
  (confirms preferences are user-scoped, not device-scoped)
- Front Desk settings: set defaultSearchMethod to QR → open /front-desk → QR scan is default
- Production settings: set autoRefresh to 30s → /production board auto-refreshes

ACCEPTANCE CHECKLIST
[ ] /settings routes all exist and are role-accessible
[ ] Theme: Light / Dark / System all work
[ ] No white flash on dark mode load (blocking script in <head>)
[ ] All components use CSS variables — no hardcoded hex colors anywhere
[ ] Brand color change restricted to Owner/Admin
[ ] Brand color updates full UI: buttons, nav active, badges, charts, focus rings
[ ] Live preview on settings page BEFORE applying globally
[ ] Reset to default theme works
[ ] Font size setting scales all UI proportionally
[ ] Data density setting changes row height, card padding across all tables and cards
[ ] Measurement unit preference updates all MeasurementInput units (cm / inches)
[ ] animationEnabled OFF: all Motion animations instant
[ ] reducedMotion respects both toggle AND prefers-reduced-motion media query
[ ] soundFeedback: scan beep plays on success/failure when enabled
[ ] Preferences stored under "prefs:{user_id}" — user-scoped
[ ] Switching users reloads correct user's preferences
[ ] Corrupt localStorage pref data → silent reset to defaults
[ ] Front Desk settings: all defaults applied when opening /front-desk
[ ] MeasurementGuideAnimator toggle in form header syncs to Settings (not independent)
[ ] Production auto-refresh works at 15s / 30s / 1min / Off
[ ] Language selector renders (English works; others show coming-soon label)
[ ] react-i18next installed, all UI strings use t() — no hardcoded JSX text
[ ] Settings pages fully usable on mobile
[ ] Keyboard shortcuts modal opens from /settings/accessibility
[ ] Storybook: ColorPicker, ThemeSelector, DensitySelector, FontSizeSlider stories
```

---

## 📌 PHASE 3A — Customer Search & Quick Create

```text
PHASE 3A: Customer Search & Quick Create

GOAL
Build the customer search panel only. This is the leftmost zone of the Front Desk
workstation. Staff must find any customer in under 3 seconds by name, phone, or QR.

BACKEND ENDPOINTS CONSUMED
GET  /api/v1/customers?search=&page=          (customers.view)
POST /api/v1/customers                        (customers.create, Idempotency-Key required)
GET  /api/v1/customers/{id}                   (customers.view)
GET  /api/v1/customers/by-qr/{payload}        (customers.view)

SEARCH BEHAVIOR
- Debounced 300ms on input. Searches by partial name AND phone last-4 simultaneously.
- Results list: customer name | phone last-4 | last order date | pending-approval badge.
- QR scan button: opens camera (mobile) or file input (desktop) → decodes signed payload
  → calls GET /customers/by-qr/{payload} → loads customer.
- No results state: "No customer found" + [Create new customer] button inline.
- Recent history: last 5 customers shown when search input is empty.
- Selecting a customer fires onCustomerSelect(customer) to parent context.

QUICK CREATE FLOW
- Triggered from "Create new customer" inline CTA.
- Opens a DrawerPanel (not a full page navigation).
- Required fields: name, phone.
- Optional fields: email, address, notes.
- POST /api/v1/customers with Idempotency-Key.
- On success: customer auto-selected, drawer closes, search panel shows new customer.
- Draft auto-saved to localStorage under "drafts:{user_id}:customer_create" while typing.
- Draft cleared on successful save.

ERROR STATES
- QR scan failure: ScanFeedback component, red shake animation, "Invalid QR code" message with request_id.
- Create customer 422: field-level Zod-mapped errors from backend envelope.
- Network error: ErrorState with request_id + retry button.

ACCEPTANCE CHECKLIST
[ ] Search debounced 300ms, searches name and phone last-4
[ ] QR scan wired to GET /api/v1/customers/by-qr/{payload}
[ ] Recent history shown when input empty
[ ] Quick create opens in DrawerPanel (not full page)
[ ] Create uses Idempotency-Key
[ ] Draft preserved during create if browser closes unexpectedly
[ ] Draft cleared on successful save
[ ] All error states surface request_id
[ ] Playwright: search by name → select customer
[ ] Playwright: QR scan (mocked payload) → correct customer loaded
[ ] Playwright: create customer → auto-selected in search panel
```

---

## 📌 PHASE 3B — Family Member & Measurement Version Selector

```text
PHASE 3B: Family Member & Measurement Version Selector

GOAL
Once a customer is selected (from Phase 3A), show the family member switcher and
measurement version selector in the center zone. These are the prerequisites for
any order creation.

BACKEND ENDPOINTS CONSUMED
GET  /api/v1/customers/{id}                               (customers.view)
GET  /api/v1/customers/{id}/family-members                (customers.view)
POST /api/v1/customers/{id}/family-members                (family_members.manage, Idempotency-Key)
PUT  /api/v1/customers/{id}/family-members/{fid}          (family_members.manage)
GET  /api/v1/customers/{cid}/measurements                 (measurements.view)
GET  /api/v1/measurements/profiles/{id}/versions          (measurements.view)
GET  /api/v1/measurements/versions/{id}                   (measurements.view)

FAMILY MEMBER SWITCHER
- Horizontal tab strip: "Main" tab + one tab per family member (first name).
- Active tab highlighted. Overflow: scroll horizontally on narrow screens.
- [+ Add] tab at the end → opens small DrawerPanel for family member create.
- Switching tab: resets the measurement version selector below.
- Add family member: name, relation, dob (optional), gender → POST with Idempotency-Key.

MEASUREMENT VERSION SELECTOR
- Shows measurement profiles for the selected customer/family member.
- Each profile shows its latest version status: Approved / Pending / Rejected.
- Expanding a profile shows version history as cards:
    "v3 — 15 Jan 2025 — Approved ✓"
    "v2 — 02 Jan 2025 — Rejected"
    "v1 — 20 Dec 2024 — Approved ✓"
- Selecting a version: fires onVersionSelect(version) to parent.
- Pending-approval versions selectable but show: ⚠ "This version is pending approval."
- Rejected versions: greyed out, not selectable.
- [Compare versions] button → opens VersionDiffView in DrawerPanel.
- [Take new measurement] button → navigates to /measurements (Phase 4).

NO MEASUREMENTS STATE
- "No measurements recorded for this customer."
- [Take Measurement] CTA button.

ACCEPTANCE CHECKLIST
[ ] Family member tab strip renders for all members
[ ] Add family member drawer works with Idempotency-Key
[ ] Version cards show correct status badge
[ ] Pending-approval version selectable with warning banner
[ ] Rejected versions not selectable
[ ] Version compare drawer opens VersionDiffView
[ ] No measurements empty state with CTA
[ ] Switching family member tab resets version selection
[ ] Playwright: select family member → version list updates
[ ] Playwright: select pending version → warning banner visible
```

---

## 📌 PHASE 3C — Order Item Builder

```text
PHASE 3C: Order Item Builder

GOAL
Build the order creation form. Staff selects garment types, sets quantities, notes,
delivery dates, and submits with a measurement_version_id. The Idempotency-Key prevents
duplicate orders on double-click.

BACKEND ENDPOINTS CONSUMED
POST /api/v1/orders                           (orders.create, Idempotency-Key required)
GET  /api/v1/orders/{id}                      (orders.view)

ORDER ITEM FORM
- Minimum 1 item required before confirm.
- Add item: garment type (Shirt / Pant / Sherwani / Combo / Other), quantity, notes.
- Each item row: garment type badge | qty | expected delivery date | notes | [Remove].
- Fabric preference: optional free text field per item.
- measurement_version_id: locked to selected version from Phase 3B. Read-only display.
  Shows: "Using v3 — 15 Jan 2025 — Approved" with link to compare.
- Cannot confirm order if no version selected (button disabled with tooltip).
- Cannot confirm order if version is pending/rejected (button disabled with warning).

DRAFT AUTO-SAVE
- Every change to the order form auto-saved to:
  localStorage key "drafts:{user_id}:order_builder"
- On page load: if draft exists for current user, restore it with a banner:
  "You have an unsaved draft. [Restore] [Discard]"
- Draft cleared immediately on successful order confirm.

CONFIRM ORDER FLOW
1. Staff clicks [Confirm Order] → ConfirmDialog shows order summary.
2. Dialog: garment count, delivery date, measurement version, estimated total.
3. [Confirm] → POST /api/v1/orders with Idempotency-Key.
4. Optimistic UI: NOT used. Wait for backend confirmation.
5. On success: navigate to /orders/{id}, toast "Order created".
6. On failure: ErrorState with request_id, keep form data, allow retry.
7. Double-click protection: IdempotencyGuard prevents duplicate submission.

ACCEPTANCE CHECKLIST
[ ] Add/remove items in order builder
[ ] measurement_version_id locked to selected version (read-only display)
[ ] Cannot confirm with no version or pending/rejected version
[ ] ConfirmDialog shows order summary before submit
[ ] POST /api/v1/orders uses Idempotency-Key
[ ] No optimistic UI on order confirm
[ ] Draft auto-saved and restorable
[ ] Draft cleared on successful submit
[ ] Error state preserves form data and shows request_id
[ ] Double-click guard active on confirm button
[ ] Playwright: add 2 items → confirm → navigate to order detail
[ ] Playwright: double-click confirm → only one order created
```

---

## 📌 PHASE 3D — Garment Visualizer

```text
PHASE 3D: Garment Visualizer

GOAL
Build the animated SVG garment preview panel. This is the rightmost zone of the Front
Desk workstation. It reacts instantly to every option selection in the order item builder.

BACKEND ENDPOINTS CONSUMED
N/A — purely driven by local UI state from the order item form.

SVG GARMENT DESIGN
- Front-facing shirt + pant silhouette in one combined SVG.
- ViewBox: "0 0 280 480". Clean line art, no cartoon features.
- All colors via CSS variables (--color-garment, --color-tape, --color-accent).
- Dark mode: silhouette adapts automatically via CSS variable switch.
- SVG paths organized as named groups: #sleeve-left, #sleeve-right, #body, #collar,
  #cuff-left, #cuff-right, #pocket, #pant-left, #pant-right, #pant-hem.

OPTION → VISUAL CHANGE MAP
Full Sleeve    → #sleeve-left, #sleeve-right paths extend to wrist
                 Motion layout animation, 200ms ease-out
Half Sleeve    → sleeve paths retract to mid-arm, 200ms ease-in
Slim Fit       → #body path width narrows ~12%, Motion scale X 180ms
Regular Fit    → #body default width
Loose Fit      → #body widens ~10%, 180ms
Collar: Stand  → #collar path: standing collar SVG variant (AnimatePresence cross-fade 150ms)
Collar: Spread → #collar path: spread collar SVG variant
Collar: Button → #collar path: button-down variant
Cuff: Single   → #cuff-* path: single button cuff
Cuff: Double   → #cuff-* path: double fold cuff (120ms)
Pocket: Yes    → #pocket path: opacity 1 (100ms)
Pocket: No     → #pocket path: opacity 0
Pant: Slim     → #pant-* paths: tapered leg
Pant: Regular  → #pant-* paths: straight leg
Pant: Wide     → #pant-* paths: wide leg

MOTION RULES
- All animations: respect usePreferences().animationEnabled and useReducedMotion().
- If animation disabled OR reduced-motion: instant state switch, no interpolation.
- Do NOT animate on every keystroke. Animate on option selection (button click).
- Never loop animations.

SUMMARY PANEL (below garment preview)
- Item count: "2 items"
- Garment types listed: "Shirt, Pant"
- Measurement version: "v3 — Approved"
- Estimated delivery: earliest date across items

ACCEPTANCE CHECKLIST
[ ] SVG garment renders cleanly in light and dark mode
[ ] Full sleeve / half sleeve transition animates correctly
[ ] Fit type changes body width with Motion scale
[ ] Collar type swap uses AnimatePresence
[ ] Pocket shows/hides with opacity
[ ] All animations respect animationEnabled preference
[ ] Reduced-motion: instant state change, no frames
[ ] Summary panel updates when items are added/removed
[ ] Storybook: stories for each option state combination
[ ] Playwright: click Full Sleeve → sleeve SVG path extends
```

---

## 📌 PHASE 3E — Full Front Desk Integration

```text
PHASE 3E: Full Front Desk Integration

GOAL
Combine Phase 3A through 3D into the complete three-zone Front Desk workstation.
Add the desktop shell layout, mobile stepped flow, sticky action bar, and the full
Playwright end-to-end workflow test.

BACKEND ENDPOINTS CONSUMED
All endpoints from Phase 3A, 3B, 3C, 3D.
Additionally:
GET  /api/v1/orders/{id}/job-card.pdf         (orders.print_job_card)

DESKTOP THREE-ZONE LAYOUT
┌────────────────┬───────────────────────────┬───────────────────┐
│  LEFT (280px)  │  CENTER (flex-grow)        │  RIGHT (300px)    │
│  Phase 3A      │  Phase 3B (top half)       │  Phase 3D         │
│  Customer      │  Family/Version            │  Garment          │
│  Search        │                            │  Visualizer       │
│                │  Phase 3C (bottom half)    │                   │
│                │  Order Item Builder        │  Summary Panel    │
└────────────────┴───────────────────────────┴───────────────────┘
                           ↑
         Sticky action bar (always visible at bottom):
         [Save Draft]  [Print Job Card]  [Confirm Order]

STICKY ACTION BAR
- Save Draft: always available. Saves order builder state to draft.
  Uses Idempotency-Key. Shows spinner during save.
- Print Job Card: available only after an order is confirmed.
  GET /api/v1/orders/{id}/job-card.pdf → open in new tab.
- Confirm Order: primary action. Disabled until version selected + items added.
  Opens ConfirmDialog (from Phase 3C). Uses Idempotency-Key.

KEYBOARD SHORTCUTS — FRONT DESK (power user mode)
All shortcuts active when user is on any /front-desk route.
  Ctrl+F (or /)   → Focus customer search input immediately
  Ctrl+N          → Open new customer quick-create drawer
  Ctrl+M          → Jump to measurement version selector
  Ctrl+O          → Add a new order item row
  Ctrl+Enter      → Open confirm order dialog (if order ready)
  Ctrl+P          → Print job card (if order confirmed)
  Ctrl+S          → Save draft
  Escape          → Cancel current drawer/dialog, go back one step
  Arrow Up/Down   → Navigate search results list
  Enter           → Select highlighted search result
  Tab             → Move to next form field in order builder

Keyboard shortcut help modal: accessible from ⌨ icon in topbar.
All shortcuts shown in /settings/accessibility keyboard shortcut help section.
Shortcuts respect the user's OS (Ctrl on Windows/Linux, Cmd on Mac).

MOBILE STEPPED FLOW (replaces three-zone on small screens)
Step 1: Customer Search (Phase 3A full screen)
Step 2: Family Member + Version (Phase 3B full screen)
Step 3: Order Item Builder (Phase 3C full screen)
Step 4: Garment Preview (Phase 3D as review screen)
Step 5: Review + Confirm (ConfirmDialog inline)
Navigation: [Back] [Next] persistent bottom bar. Step indicator at top.
Draft auto-saves at each step transition.

BRANCH CONTEXT
- X-Branch-Id header injected by API client on every request.
- Branch name visible in TopBar context indicator.
- If user's branch changes mid-session, invalidate all branch-scoped query cache.

VERTICAL SLICE VALIDATION
Before considering this phase done, validate this complete flow:
Login → Customer Search → QR Scan → Family Member Select →
Measurement Version Select → Add 2 Order Items → Confirm Order →
Print Job Card PDF (new tab)
This is the most critical workflow in the entire ERP.

TESTS (Playwright)
- Full workflow: login → search customer → select family member → select version →
  add items → confirm order → view order detail
- QR scan (mocked): customer loads and populates all zones
- Double-click confirm: only one order created (Idempotency-Key)
- Mobile stepped flow: complete all 5 steps
- Draft restore: start order, refresh page, draft restored with banner
- Keyboard: Ctrl+F focuses search, Enter selects result, Ctrl+Enter opens confirm dialog
- Branch switch during session: cache invalidated, search results refresh

ACCEPTANCE CHECKLIST
[ ] Three-zone desktop layout renders correctly
[ ] All zones communicate through shared context (not prop drilling)
[ ] Customer zone state persists when interacting with center or right zone
[ ] Sticky action bar always visible, buttons correctly enabled/disabled
[ ] Print Job Card opens /api/v1/orders/{id}/job-card.pdf in new tab
[ ] Mobile stepped flow complete with back/next navigation
[ ] Vertical slice validation passes end-to-end
[ ] All Playwright tests pass
[ ] No hardcoded endpoint strings — all use API.* constants from Phase 1B
[ ] All mutation responses validated through Zod schemas from Phase 1B
```

---

## 📌 PHASE 4 — Measurement Desk

```text
PHASE 4: Measurement Desk

GOAL
The measurement entry workstation. Append-only versioned. Staff cannot edit an existing
version — they create a new one. Threshold warnings, approval inbox, and version compare
are first-class features. The animated garment preview from Phase 3 extends here.

BUSINESS REQUIREMENTS
- Measurements are NEVER edited in place. "Save" always creates a new version.
- Threshold crossing (e.g., chest > 5cm change from last version) shows a warning
  and requires an approval note. Backend enforces the approval flow.
- Approval inbox shows all pending measurement versions for Measurement Staff and Admin.
- Version history is always accessible in the right panel.
- Measurement form is grouped logically: Shirt fields / Pant fields / Notes.

BACKEND ENDPOINTS CONSUMED
GET  /api/v1/customers/{cid}/measurements                   (measurements.view)
GET  /api/v1/measurements/profiles/{id}/versions            (measurements.view)
POST /api/v1/measurements/profiles/{id}/versions            (measurements.create, Idempotency-Key)
GET  /api/v1/measurements/versions/{id}                     (measurements.view)
GET  /api/v1/measurements/pending-approval                  (measurements.approve)
POST /api/v1/measurements/versions/{id}/approve             (measurements.approve, Idempotency-Key)
POST /api/v1/measurements/versions/{id}/reject              (measurements.approve)

DESKTOP LAYOUT
┌──────────────────────────────────────────────────────────────────────────┐
│  Context strip: Customer name | Family member | Version badge | [Guide ⚙]│
├──────────────────────┬─────────────────────────┬────────────────────────┤
│  LEFT                │  CENTER                 │  RIGHT                 │
│  Version History     │  Measurement Form       │  MeasurementGuide      │
│  (240px)             │  (flex-grow)            │  Animator (300px)      │
│                      │                         │                        │
│  v3 — Active         │  [SHIRT GROUP]          │  Human silhouette SVG  │
│  v2 — Approved       │  Chest, Shoulder,       │  (gender-neutral,      │
│  v1 — Approved       │  Sleeve, Length,        │   abstract outline)    │
│                      │  Collar, Cuff, etc.     │                        │
│  [Compare] button    │                         │  Tape measure SVG      │
│                      │  [PANT GROUP]           │  animating on active   │
│                      │  Waist, Hip, Thigh,     │  field                 │
│                      │  Knee, Pant Length,     │                        │
│                      │  Inseam, Outseam, etc.  │  Helper text           │
│                      │                         │  (1–2 lines, clear)    │
│                      │  [NOTES]                │                        │
│                      │                         │  [↺ Replay] button     │
│                      │  ⚠ Threshold warning    │                        │
└──────────────────────┴─────────────────────────┴────────────────────────┘
              ↑ Sticky bottom bar: [Save as New Version] [Cancel]

MEASUREMENT PRE-FILL — USER-FRIENDLINESS RULE
When creating a new version, the form must pre-fill with the last approved version's values.
Staff only changes what's different. This is the default behaviour — not opt-in.

Pre-fill source: GET /api/v1/measurements/versions/{last_approved_version_id}
Pre-fill trigger: when "Save as New Version" flow begins.

Visual treatment of pre-filled fields:
- Fields with pre-filled values: shown in normal text.
- Fields the staff has changed: highlighted with amber left border.
- At the top of the form: "Pre-filled from v3 — 15 Jan 2025 (approved). Change any field to
  create a new version."
- If no previous version exists (first ever measurement): form is blank.

This means an alteration visit for a single chest change takes 20 seconds, not 5 minutes.
The diff computed by the backend (diff_json) is based on what changed from the pre-fill,
so the threshold detection still works correctly.

FORM FIELD GROUPS — SHIRT (exact backend field names from shirt_data JSON)
chest | waist | hip | shoulder | sleeve_length | shirt_length | collar | cuff | arm_round
neck | front_chest | cross_back | dart | bicep | wrist
+ 5 free-text notes fields (note_1 through note_5)

FORM FIELD GROUPS — PANT (exact backend field names from pant_data JSON)
waist | hip | thigh | knee | bottom | length | in_seam | out_seam | crotch | fly
+ 5 free-text notes fields
Display label for `length`: "Pant Length".

THRESHOLD WARNING BEHAVIOR
- On every field blur: compare entered value against latest approved version.
- If delta > threshold (fetched from backend or config): show inline ⚠ per field.
- Show summary banner: "3 fields exceed change thresholds. An approval note is required."
- Approval note field becomes required. Cannot save without it.
- After save: version status is "pending_approval" not "approved".

APPROVAL INBOX SCREEN (/measurements/approvals)
- Table: customer name | version | changed fields summary | delta | submitted by | date
- Row expand: shows field-by-field diff with VersionDiffView component
- Actions: [Approve] [Reject with note]
- After action: row disappears from inbox (optimistic) or updates status badge

VERSION COMPARE VIEW
- Side-by-side: "v2 (current)" vs "v1 (previous)"
- Changed fields highlighted in amber with Motion animation (flash on mount)
- Unchanged fields shown in muted text
- "Use v1 as base for new entry" shortcut button

GARMENT SVG LIVE PREVIEW (in this phase)
- Same SVG component as Phase 3 but driven by form field values
- Sleeve length → SVG sleeve path length changes as user types
- Fit type → SVG body width changes on blur
- No animation on every keystroke — animate on field blur only

MEASUREMENT GUIDE ANIMATOR — FULL SPEC
File: components/measurements/MeasurementGuideAnimator.tsx

PURPOSE
Show staff exactly HOW to measure each body part: an animated human silhouette
with a tape measure that traces the correct measurement path. This is a training
and accuracy tool — it reduces measurement errors from new staff and serves as
a quick reminder for experienced staff.

This is SEPARATE from the garment preview. The garment preview shows what the
garment looks like. The guide animator shows where on the body to place the tape.

SILHOUETTE DESIGN
- Gender-neutral, abstract human outline. Front view for shirt/upper body fields.
  Side + front view for pant/lower body fields.
- No face features, no hair, no gender-specific details.
- Clean premium line art — think anatomy diagram, not cartoon.
- Color: single muted tone (CSS var --color-silhouette) on white/light background.
- The body region being measured is highlighted in brand amber while that field is active.
- SVG is fully responsive. ViewBox: "0 0 200 420" for full body.

TAPE MEASURE SVG
- Tape is an SVG path rendered on top of the silhouette.
- Animation technique: stroke-dasharray + stroke-dashoffset, animated from full
  offset (invisible) to 0 (fully drawn). Duration: 900ms, ease-out.
- Tape color: warm gold/amber (CSS var --color-tape).
- Tape has small tick marks at regular intervals (realistic tape look).
- After animation completes: tape stays visible in final position. Does NOT loop.
  Does NOT disappear. Staff can see the final position clearly.
- [↺ Replay] button replays the animation on demand.

GUIDE STATES AND TAPE PATHS (all 23 backend measurement fields)

SHIRT FIELDS:
chest        → front torso. Tape wraps horizontally around fullest chest line.
               Helper: "Around the fullest part of the chest. Tape flat across the back."
waist        → tape wraps around the torso at shirt waist height (for fitted shirts).
               Helper: "Around the torso at the waist. Used for fitted/slim shirt cut."
hip          → tape wraps around the widest hip point on the torso (for long shirts/kurtas).
               Helper: "Around the widest part of the hip area. Used for kurtas and long shirts."
shoulder     → front/back. Tape from left shoulder edge to right shoulder edge.
               Helper: "From shoulder edge to edge, across the back. Not over the arms."
sleeve_length→ arm slightly bent. Tape from shoulder seam down to wrist bone.
               Helper: "Arm slightly bent. From shoulder seam to wrist bone."
shirt_length → tape from back neck base straight down to desired hem.
               Helper: "From base of neck (back) to desired shirt length."
collar       → tape wraps around neck at collar stand height.
               Helper: "Around the neck where the collar sits. Add 1–2 cm for comfort."
neck         → tape wraps at base of neck, widest point.
               Helper: "Around the base of the neck, at the widest point."
cuff         → tape wraps around wrist at wrist bone.
               Helper: "Around the wrist at the wrist bone."
arm_round    → tape wraps around fullest part of upper arm.
               Helper: "Around the fullest part of the upper arm."
front_chest  → tape across front only, from shoulder seam to shoulder seam.
               Helper: "Front chest only — shoulder seam to shoulder seam across front."
cross_back   → tape across upper back, between shoulder blades.
               Helper: "Across the upper back, between the shoulder blades."
dart         → tape from shoulder point diagonally to chest point.
               Helper: "From shoulder tip to the chest point. Used for tailored fit."
bicep        → tape wraps around fullest part of bicep.
               Helper: "Around the bicep at its fullest. Measure relaxed."
wrist        → tape wraps around wrist joint.
               Helper: "Around the wrist joint, over the wrist bone."

PANT FIELDS:
waist        → tape wraps around natural waist (narrowest torso point).
               Helper: "Around the natural waist — narrowest part of the torso."
hip          → tape wraps around fullest hip/seat point.
               Helper: "Around the fullest part of the hips and seat."
thigh        → tape wraps around fullest part of upper thigh.
               Helper: "Around the fullest part of the upper thigh."
knee         → tape wraps around knee (leg straight).
               Helper: "Around the knee. Measure with leg straight."
bottom       → tape wraps around trouser hem (leg opening width).
               Helper: "Around the leg at the ankle (trouser hem width)."
length       → tape from waist side down to ankle. Display label: "Pant Length".
               Helper: "From waist to ankle, outside of the leg."
in_seam      → tape from crotch point to ankle, inside leg.
               Helper: "From crotch to ankle, inside leg."
out_seam     → tape from waist to ankle, outside leg.
               Helper: "From waist to ankle, outside leg. Longer than in_seam."
crotch       → tape from front waist, through crotch, up to back waist.
               Helper: "Front waist, through crotch, to back waist. Sit upright."
fly          → tape from waistband to crotch seam (front rise only).
               Helper: "From waistband down to the crotch seam (front rise)."

STORYBOOK STORIES (one story per guide state — 25 + 2 shirt = 27 stories)
Shirt: Chest | ShirtWaist | ShirtHip | Shoulder | SleeveLength | ShirtLength | Collar | Neck | Cuff
       ArmRound | FrontChest | CrossBack | Dart | Bicep | Wrist
Pant:  Waist | Hip | Thigh | Knee | Bottom | PantLength | InSeam | OutSeam | Crotch | Fly
Special: ReducedMotion | GuideOff
- A toggle switch in the form header: "Measurement Guide  [ON | OFF]"
- This toggle reads from and writes to: usePreferences().frontDesk.showMeasurementGuide
  It does NOT maintain its own localStorage key. Phase 2B Settings is canonical.
  The form-header toggle is a convenience shortcut only.
- Veteran staff who know every measurement can turn it OFF. The right panel collapses.
- New staff: guide is ON by default (schema default in Phase 2B).
- When OFF: the right panel hides. Version history takes full right column width.
- Permanent OFF can be set in /settings/front-desk so it applies on every login.

ANIMATION BEHAVIOR — USER-FRIENDLINESS RULES
1. Animation plays ONCE on first focus of a field in a session.
   Subsequent focus of the same field → shows static tape in final position. No re-animation.
2. [↺ Replay] button → replays animation for that field.
3. Animation does NOT loop. Looping is distracting during data entry.
4. Switching to a different field → new tape animates in (cross-fade: old tape fades
   out at 200ms, new tape animates in over 900ms).
5. prefers-reduced-motion: skip all animation. Show static tape immediately in final position.
   Still highlight the correct body region in amber.

BIDIRECTIONAL INTERACTION — USER-FRIENDLINESS RULE
Clicking a highlighted body region on the silhouette scrolls the form to the matching
input field AND focuses it. This allows staff to navigate the form by tapping the body
part they want to measure, not by scrolling through the form.
Example: tap the chest region on the silhouette → chest input field gets focused and
scrolled into view in the form center panel.

MOBILE BEHAVIOR
- On mobile, the guide animator does NOT auto-open on every field focus.
  Auto-open on every field = too intrusive on a small screen.
- Instead: each MeasurementInput has a small "?" icon button on the right.
- Tapping "?" → opens the guide in a bottom sheet for that specific field.
- First time a new staff member uses the form (preferences.frontDesk.showMeasurementGuide
  is true AND "guide_seen:{user_id}" localStorage flag is false):
  the guide auto-opens for "chest" only, with a banner:
  "Tap ? on any field to see a measurement guide."
  "Got it" dismisses and sets the flag. Never auto-opens again.
- Bottom sheet: 60% screen height, swipe-to-dismiss, contains the full guide panel.
- Staff can tap the "?" while typing — they return to the input without losing value.
- If preferences.frontDesk.showMeasurementGuide is OFF: "?" icons are hidden entirely.

GUIDE TOGGLE — USER-FRIENDLINESS RULE
- Single column form with CollapsibleSection for Shirt / Pant / Notes groups
- Each MeasurementInput has a "?" icon that opens the guide bottom sheet for that field
- Version history in a bottom drawer (separate from guide)
- Guide bottom sheet: 60% height, swipe-to-dismiss
- First-time auto-open (chest only) with "Got it" banner
- Guide toggle accessible from form header (⚙ icon → toggle switch)

TESTS (Playwright)
- Enter measurement → save → new version created → appears in history
- Change chest by large amount → threshold warning appears → approval note required
- Approve a pending version from inbox → version status changes to approved
- Compare v1 vs v2: changed fields highlighted
- Focus chest input → chest guide animates (tape appears over 900ms)
- Focus waist input → waist guide animates (cross-fade from previous guide)
- Click chest region on silhouette → chest input focuses in form
- Guide toggle OFF → right panel collapses, form widens
- Reduced-motion: focus chest → static tape shown instantly, no animation frames
- Mobile: tap "?" on sleeve_length → bottom sheet opens with correct guide

ACCEPTANCE CHECKLIST
[ ] Save always creates new version (never PATCH existing)
[ ] Threshold warnings appear per field and in summary banner
[ ] Approval note required when threshold crossed
[ ] Approval inbox shows all pending, with diff view
[ ] Version history panel always visible on desktop
[ ] VersionDiffView highlights changed fields with Motion
[ ] Garment SVG (from Phase 3) updates on field blur in form
[ ] Mobile collapsible form groups work
[ ] All saves use Idempotency-Key
[ ] MeasurementGuideAnimator: all 12 guide states implemented with correct tape paths
[ ] Tape animation: 900ms ease-out, plays once per field per session, stays visible after
[ ] [↺ Replay] button replays animation on demand
[ ] Guide toggle ON/OFF persisted in localStorage (not auth data)
[ ] Bidirectional: clicking silhouette body region focuses matching input
[ ] Cross-fade on field switch: old tape fades, new tape animates
[ ] Reduced-motion: static tape shown immediately, body region still highlighted
[ ] Helper text shown for every guide state (1–2 lines, clear language)
[ ] Mobile "?" icon on each input opens correct guide in bottom sheet
[ ] First-time auto-open (chest only) with "Got it" dismiss
[ ] Silhouette is gender-neutral, clean, professional — no cartoonish features
[ ] Storybook stories for all 12 guide states + reduced-motion + guide-off variants
[ ] All 12 guide Storybook stories pass a11y addon checks
```

---

## 📌 PHASE 5 — Orders & Job Cards

```text
PHASE 5: Orders & Job Cards

GOAL
Order list, order detail, order create (from Front Desk), and job card PDF flow.
Orders are never edited after confirmation. Corrections go through new orders or
backend-supported amendment flows. Order status is always derived from item states.

BUSINESS REQUIREMENTS
- Order list is filterable by branch, date range, status, customer name.
- Order detail shows all items with their individual production states.
- Derived order status is computed from item states, not a direct field.
- Job card PDF is a signed download, not a client-side rendered document.
- Order cancellation requires a reason and goes through backend — not a UI toggle.

BACKEND ENDPOINTS CONSUMED
GET   /api/v1/orders?status=&customer=&from=&to=   (orders.view — branch-scoped via middleware)
GET   /api/v1/orders/{id}                          (orders.view — includes items, customer, measurements)
POST  /api/v1/orders                               (orders.create, from Front Desk Phase 3)
PUT   /api/v1/orders/{id}                          (orders.update)
POST  /api/v1/orders/{id}/items                    (orders.update, Idempotency-Key)
PUT   /api/v1/orders/{id}/items/{itemId}           (orders.update)
DELETE /api/v1/orders/{id}/items/{itemId}          (orders.update)
POST  /api/v1/orders/{id}/cancel                   (orders.cancel)
GET   /api/v1/orders/{id}/job-card.pdf             (orders.print_job_card — signed PDF)

SCREENS

/orders — Order List
- DataTable: order number | customer | branch | item count | derived status | delivery date | total
- FilterBar: branch (Owner only) | status multi-select | date range | customer search
- Row click → /orders/{id}
- Status badge colors: pending (amber) | in_production (blue) | ready (green) | delivered (muted)

/orders/{id} — Order Detail
- PageHeader: order number + customer name + derived status badge + [Print Job Card] button
- InfoGrid: branch, created by, created at, delivery date, measurement version (with link)
- Items table: garment type | qty | current production state | assigned tailor | notes
- Each item has an expandable row with production transition history (Timeline component)
- Finance summary: estimated price, deposit paid, balance

/orders/{id}/job-card.pdf — GET to signed URL, opens PDF in new tab. No client-side PDF rendering.

ORDER STATUS DERIVATION (frontend display logic, not business logic)
  All items Draft                → Order: Pending
  Any item Cutting or beyond     → Order: In Production
  All items Packing or ReadyForDelivery → Order: Ready
  Any item Delivered             → Order: Partially Delivered
  All items Delivered            → Order: Delivered
  Any item Cancelled             → show per-item status
Note: Derive from the item states as returned by the backend. Never compute separately.

CANCEL ORDER FLOW
- [Cancel Order] button → ConfirmDialog with required reason input
- POST /api/v1/orders/{id}/cancel with reason
- On success: toast + status badge updates
- Cannot cancel if any item already in delivered state (backend enforces, show backend error)

TESTS (Playwright)
- Order list loads with filters
- Order detail shows correct items and their production states
- Print Job Card opens PDF in new tab
- Cancel order requires reason, shows error if cancelled state not allowed

ACCEPTANCE CHECKLIST
[ ] Order list DataTable sortable, filterable, paginated
[ ] Derived status badge computed from item states
[ ] Order detail shows items with individual production states
[ ] Timeline shows production history per item (expandable row)
[ ] Job card opens signed PDF in new tab (no client-side PDF render)
[ ] Cancel requires reason, uses Idempotency-Key
[ ] All error states show request_id
```

---

## 📌 PHASE 6 — Production Board

```text
PHASE 6: Production Board

GOAL
Item-level production state management. A Kanban-style board focused on order_items,
not orders. Transitions must show allowed next states only. Every transition needs a
confirmation. History timeline per item. Rework visibility.

BUSINESS REQUIREMENTS
- Board shows order_items grouped by production state column.
- Only allowed next states (from backend state machine) are shown as action buttons.
- State transitions require a confirmation dialog with optional notes.
- Rework items are visually distinct (amber border) and show rework count.
- Production Supervisor sees all items in their branch. Tailor sees only their assigned items.
- Cutting Master sees cutting queue only.

BACKEND ENDPOINTS CONSUMED
GET  /api/v1/production/board                         (production.view — branch-scoped via middleware)
GET  /api/v1/production/items/{id}                    (production.view)
POST /api/v1/production/items/{id}/transition         (production.transition.{state}, Idempotency-Key)
GET  /api/v1/production/items/{id}/history            (production.view)
GET  /api/v1/audit/transitions/{order_item_id}        (audit.transitions.view — used in item detail drawer)
GET  /api/v1/cutting/queue                            (fabric.allocate)

PRODUCTION STATE MACHINE
Backend states (spatie/laravel-model-states PascalCase class names):
Draft → FabricAllocated → Cutting → Tailoring → KajaButton → Finishing → QC
→ Packing → ReadyForDelivery → Delivered
Branch: QC → Rework → QC (bounded rework attempts; 4th requires production.rework.override)
Cancellation: allowed before Cutting; after Cutting requires supervisor.

IMPORTANT — TRANSITION VALUES
The frontend must NEVER hardcode the state string sent in the transition { to } field.
The board API response includes an allowed_transitions array per item — use those values
directly for transition buttons. Display labels (e.g. "Send to Cutting") can be mapped
from these values, but the value sent to the API must always come from allowed_transitions.
This makes the UI resilient to any backend state machine changes.

BOARD COLUMNS
Map each backend state to a board column using display labels:
  Draft              → "Draft"
  FabricAllocated    → "Fabric Ready"
  Cutting            → "Cutting"
  Tailoring          → "Tailoring"
  KajaButton         → "Kaja & Button"
  Finishing          → "Finishing"
  QC                 → "QC"
  Rework             → "Rework"
  Packing            → "Packing"
  ReadyForDelivery   → "Ready for Delivery"
  Delivered          → "Delivered"

BOARD LAYOUT — DESKTOP
- Horizontal scrollable columns, each column = one production state
- Each card: order number | customer | garment type | tailor assigned | time in state
- Cards sorted: overdue first (red border), then by time-in-state descending.
- Rework cards: amber left border + "Rework #N" badge
- Transition action: [allowed_transitions] buttons on card, opens ConfirmDialog
- ConfirmDialog shows: target state display label, notes field (required for Rework), [Confirm]
- Role filter: Supervisor sees all; Tailor sees assigned only; Cutting Master sees cut queue

OVERDUE / SLA ALERT SYSTEM
Every production stage has a configurable SLA (lib/config/production-sla.ts — not hardcoded):
  FabricAllocated: 1 day  |  Cutting: 1 day  |  Tailoring: 3 days
  KajaButton: 1 day       |  Finishing: 1 day |  QC: 1 day
  Rework: 2 days          |  Packing: 1 day

When an item exceeds its stage SLA:
- Card: red left border + OverdueTag badge "2 days overdue".
- Production Supervisor's role dashboard shows overdue count at the top.
When order delivery date has passed (item not yet Delivered):
- Amber "Delivery overdue" badge on the card — separate from stage SLA.
SLA thresholds are editable in config without code changes.

MOTION SPEC FOR BOARD
- Card transition between columns: shared-element animation via Motion layout
  (card slides from source column to target column, 250ms ease-out)
- On transition confirm: card briefly scales to 1.04 then moves (100ms pulse)
- Rework return: card appears with amber flash (300ms, then settles)
- New card arriving (from polling/refresh): fade-in from top of column

CUTTING QUEUE (/cutting)
- Table view (not Kanban): order | garment | fabric roll allocated | metres | bundle status
- [Allocate Fabric] → opens drawer → POST /api/v1/cutting/items/{id}/allocate-fabric
- [Start Cutting] → POST /api/v1/cutting/items/{id}/start-cutting
- [Complete Cutting] → POST /api/v1/cutting/items/{id}/complete-cutting
- Bundle QR: GET /api/v1/cutting/bundles/by-qr/{payload} → ScanFeedback + print button

ITEM DETAIL DRAWER
- Clicking any card opens a right DrawerPanel
- Shows: customer, order, garment, current state, assigned tailor, fabric allocated
- Timeline: GET /api/v1/production/items/{id}/history — full history with actor, timestamp, notes
- Transition history: GET /api/v1/audit/transitions/{order_item_id}
- Rework history: each rework cycle with reason, rework count badge
- Action buttons: transition (from allowed_transitions), reassign tailor

ROLE-BASED VISIBILITY (permissions from backend)
- Production Supervisor: full board + all transition buttons (production.transition.*)
- Tailor: only assigned cards + tailoring-stage transitions (production.transition.tailoring, finishing)
- Cutting Master: cutting queue only + cutting transitions (production.transition.cutting)
- Kaja Button: KajaButton state cards + kaja transition (production.transition.kaja)
- Ironing Master: Finishing state cards + finishing transition (production.transition.finishing)
- QC Supervisor: QC/Rework cards + qc transitions + override (qc.inspect, production.rework.override)
- Re-Worker: Rework cards + rework-to-QC transition (production.transition.rework)

TESTS (Playwright)
- Board loads all state columns with correct display labels
- Transition flow: allowed_transitions buttons rendered, confirm dialog, card moves to new column
- Rework card shows amber border and rework count badge
- Tailor sees only assigned items (permission test)
- Cutting queue loads with allocate-fabric action

ACCEPTANCE CHECKLIST
[ ] Board columns match all backend states with correct display labels
[ ] Transition buttons populated from allowed_transitions array (never hardcoded)
[ ] Transition { to } value sent from allowed_transitions, not from frontend string map
[ ] Transition requires confirmation dialog; notes required for Rework
[ ] Motion: card slides between columns on transition
[ ] Rework items visually distinct (amber border, count badge)
[ ] Item detail drawer shows production history + audit transitions
[ ] Role-based visibility enforced — permissions from backend permission list
[ ] Cutting queue is separate table view, not Kanban
[ ] All transitions use Idempotency-Key
```

---

## 📌 PHASE 7 — Inventory, Fabric Rolls & Suppliers

```text
PHASE 7: Inventory, Fabric Rolls & Suppliers

GOAL
Fabric roll management with ledger-style movement history, QR-based roll lookup,
purchase orders, GRN, supplier management, low-stock alerts, and the two-phase
reservation flow (reserve → consume or release).

BUSINESS REQUIREMENTS
- Every roll shows three values: remaining, reserved, and available (never just "stock").
- Movement history is a ledger (append-only list), never an editable table.
- Low-stock rolls are highlighted automatically based on threshold.
- QR scan on a roll opens its detail instantly.
- Purchase orders and GRN are create-only (append-only, like finance).

BACKEND ENDPOINTS CONSUMED
GET  /api/v1/inventory/fabric-rolls?type=&colour=&status=   (inventory.view)
GET  /api/v1/inventory/fabric-rolls/{id}                    (inventory.view)
POST /api/v1/inventory/fabric-rolls                         (inventory.fabric_rolls.create)
POST /api/v1/inventory/fabric-rolls/{id}/adjust             (inventory.fabric_rolls.adjust)
GET  /api/v1/inventory/fabric-rolls/by-qr/{payload}         (inventory.view)
GET  /api/v1/inventory/movements?roll_id=&from=&to=         (inventory.view)
GET  /api/v1/inventory/low-stock                            (inventory.low_stock.view)
GET  /api/v1/inventory/fabric-types                         (inventory.view)
GET  /api/v1/inventory/suppliers                            (inventory.suppliers.manage)
POST /api/v1/inventory/suppliers                            (inventory.suppliers.manage)
PUT  /api/v1/inventory/suppliers/{id}                       (inventory.suppliers.manage)
GET  /api/v1/inventory/purchase-orders                      (inventory.purchase_orders.create)
POST /api/v1/inventory/purchase-orders                      (inventory.purchase_orders.create)
POST /api/v1/inventory/purchase-orders/{id}/place           (inventory.purchase_orders.place)
POST /api/v1/inventory/purchase-orders/{id}/receive         (inventory.purchase_orders.receive)
POST /api/v1/inventory/purchase-orders/{id}/cancel
GET  /api/v1/damage-reports?status=&from=&to=               (damage_reports.view)
POST /api/v1/damage-reports                                 (damage_reports.create, Idempotency-Key)
POST /api/v1/damage-reports/photos                          (damage_reports.create)
POST /api/v1/damage-reports/{id}/approve                    (damage_reports.approve, Owner only, Idempotency-Key)
POST /api/v1/damage-reports/{id}/reject                     (damage_reports.reject, Owner only)

NOTE: Fabric reservation and consumption belong to the cutting workflow, not to the
inventory module. The inventory roll detail shows roll data and movement history only.
The [Allocate Fabric] action on a roll detail navigates to the cutting queue or opens
a cutting context drawer, which calls: POST /api/v1/cutting/items/{id}/allocate-fabric

SCREENS

/inventory/fabric-rolls — Roll List
- DataTable: roll code | fabric type | colour | supplier | remaining | reserved | available | status
- Status: in_stock | low_stock | exhausted (computed from remaining vs threshold)
- FilterBar: fabric type | colour | supplier | low_stock toggle | branch
- [Scan QR] button → GET /api/v1/inventory/fabric-rolls/by-qr/{payload}
- Click row → /inventory/fabric-rolls/{id}

/inventory/fabric-rolls/{id} — Roll Detail
- InfoGrid: roll code, fabric type, colour, supplier, total metres, threshold
- MetricCards: Remaining | Reserved | Available (three cards, visually distinct)
- Movement History: GET /api/v1/inventory/movements?roll_id={id}
  Timeline: type | metres | actor | date (ledger — no edit controls)
- Action buttons (role-gated):
  [Adjust] → opens drawer for adjust_in/adjust_out → POST /adjust (Owner/Admin only for out)
  [Report Damage] → opens damage report create drawer
- QR display: ScanFeedback component with print button (QR is from roll.qr_payload)

/inventory/suppliers — Supplier list + create/edit drawer

/inventory/purchase-orders — PO list + create flow
- PO states: draft → placed → partial_received → received | cancelled
- [Receive GRN] → POST /inventory/purchase-orders/{id}/receive (creates GRN + fabric rolls)

/inventory/damage — Damage Report List
- Route: /damage-reports (separate from inventory nav)
- List: roll | damage type | metres | reported by | status | date
- Status: pending_approval | approved | rejected
- [Approve] / [Reject] — Owner only

LOW-STOCK DASHBOARD WIDGET
- "Low Stock Rolls" card with count + link to /inventory/fabric-rolls?status=low_stock
- Each roll below threshold: StatusBadge "Low Stock" in warning amber

DAMAGE REPORT (/inventory/damage)
- List of damage reports: roll | damage type | metres | reported by | status | date
- Status: pending_approval | approved | rejected
- Owner/Admin can approve or reject
- Approval changes roll's remaining_metres via backend (no direct frontend calculation)

ACCEPTANCE CHECKLIST
[ ] Three MetricCards (remaining / reserved / available) on every roll detail
[ ] Movement history is read-only ledger Timeline (no edit controls)
[ ] Reserve → Consume / Release flow works with Idempotency-Key
[ ] QR scan opens correct roll detail
[ ] Low-stock rolls highlighted in roll list
[ ] Damage report list with approval actions (Owner only)
[ ] PO and GRN are create-only (no edit on confirmed records)
[ ] All screens show request_id on error
```

---

## 📌 PHASE 8 — QC, Rework, Tailoring & Delivery

```text
PHASE 8: QC, Rework, Tailoring & Delivery

GOAL
QC inspection with defect photos, rework tracking, tailor assignment and performance,
rack assignment, OTP delivery confirmation, and delivery attempt logs.

BUSINESS REQUIREMENTS
- QC inspection creates a record per item, not per order.
- Defect photos are uploaded and linked to the inspection record.
- Rework has a bounded attempt count; override requires QC Supervisor.
- Tailor assignment is per order item. Performance stats visible to Supervisor.
- Rack slot uniqueness is enforced at DB level; frontend must handle the conflict error.
- OTP is sent by backend; frontend only confirms it after the customer shares it.
- Delivery attempts are logged even on failed attempts.

BACKEND ENDPOINTS CONSUMED

QC:
POST /api/v1/qc/items/{id}/inspect                  (qc.inspect, Idempotency-Key required)
POST /api/v1/qc/photos                              (qc.inspect — pre-upload, returns photo_id)
GET  /api/v1/qc/items/{id}/history                  (qc.inspect)
POST /api/v1/qc/items/{id}/rework-override          (production.rework.override)
GET  /api/v1/qc/defects/categories                  (qc.inspect)
GET  /api/v1/production/board                       (QC queue = board items in QC state)

Tailoring:
GET  /api/v1/tailoring/assignments?tailor=&status=   (orders.view)
POST /api/v1/tailoring/assignments                   (orders.update, Idempotency-Key)
POST /api/v1/tailoring/assignments/{id}/start        (Idempotency-Key)
POST /api/v1/tailoring/assignments/{id}/complete     (Idempotency-Key)
POST /api/v1/tailoring/assignments/{id}/reassign     (orders.update)
GET  /api/v1/tailoring/performance/{tailorId}?from=&to=

Rack:
GET  /api/v1/rack/slots                              (rack.view)
POST /api/v1/rack/slots                              (rack.slots.manage)
PUT  /api/v1/rack/slots/{id}                         (rack.slots.manage)
POST /api/v1/rack/items/{itemId}/assign              (rack.assign, Idempotency-Key)
POST /api/v1/rack/items/{itemId}/release             (rack.release)
GET  /api/v1/rack/items/{itemId}/current-slot        (rack.view)

Delivery:
GET  /api/v1/deliveries?status=&from=&to=            (deliveries.view)
POST /api/v1/deliveries                              (deliveries.create)
POST /api/v1/deliveries/{id}/dispatch               (deliveries.dispatch, Idempotency-Key)
POST /api/v1/deliveries/{id}/confirm                (deliveries.confirm, Idempotency-Key)
POST /api/v1/deliveries/{id}/attempt                (deliveries.attempt)
POST /api/v1/deliveries/{id}/cancel                 (deliveries.cancel)

SCREENS

/qc — QC Inspection Queue
- Card list of items in QC state (from production board, filtered to QC state)
- Each card: customer | order | garment type | tailor | rework count badge
- [Start Inspection] → opens inspection drawer
- Inspection flow:
  1. Upload photos FIRST: POST /api/v1/qc/photos → returns photo_id array
  2. Select defects: multi-select from GET /api/v1/qc/defects/categories
     Each defect: category + severity (minor/major/critical) + notes + photo_ids[]
  3. Choose disposition: pass | pass_with_note | rework | reject
  4. Submit: POST /api/v1/qc/items/{id}/inspect with { disposition, defects[], notes }
     Idempotency-Key required.
- Pass → item moves to Packing state (Motion: card exits with green flash)
- Rework → item returns to appropriate prior stage (Motion: amber flash)
- Override (production.rework.override): visible only after rework_count ≥ 3

/tailoring/assignments — Tailor Assignment Board
- Items in FabricAllocated state needing tailor assignment
- [Assign Tailor] → select tailor → POST /api/v1/tailoring/assignments (Idempotency-Key)
- Tailor performance: GET /api/v1/tailoring/performance/{tailorId}

/rack — Rack Slot Management
- Visual grid: rows × slots. Each slot: order number or empty.
- Click empty slot → assign drawer → POST /rack/items/{itemId}/assign (Idempotency-Key)
- 409 RACK_SLOT_OCCUPIED: slot taken at DB level → show inline error with request_id
- 409 ITEM_ALREADY_ASSIGNED: item already has a slot → show error with release option

/delivery — Delivery Queue
- List: order | customer | rack slot | delivery staff | status
- [Dispatch] → POST /deliveries/{id}/dispatch (Idempotency-Key) → OTP sent
- [Confirm OTP] → text input → POST /deliveries/{id}/confirm (Idempotency-Key)
- [Log Failed Attempt] → reason required → POST /deliveries/{id}/attempt
- OTP locked (423) after 5 wrong attempts → re-dispatch required

/tailoring/performance — Tailor Performance Dashboard
- Summary cards row: Best tailor this week | Avg completion time | Branch rework rate
- DataTable: tailor name | completed | avg time/item | rework count | rework % | on-time % | active now
- FilterBar: date range | branch
- Row click → tailor detail view with item-level history
- Colour coding: rework % > 15% = amber row, > 25% = red row
- [Assign Work] quick action: pre-fills assignment drawer with this tailor selected
- Export button (uses backend export endpoint, not client-side)

Assignment helper: when supervisor opens [Assign Tailor] on a production item, show
performance cards for all available tailors side by side — speed vs quality tradeoff
visible before assigning. This is the primary use of the performance data.

/rack — Rack Slot Management
- Visual rack grid: rows × slots, each slot shows order number or empty
- Click empty slot → [Assign Order] drawer
- Conflict: if slot already taken (409 from backend), show inline error with request_id
- Cannot assign: no duplicate allowed at DB level, frontend surfaces error clearly

/delivery — Delivery Queue
- Table: order | customer | rack slot | assigned delivery staff | status
- [Dispatch] → POST /api/v1/deliveries/{id}/dispatch (Idempotency-Key) → OTP sent to customer, status moves to dispatched
- [Confirm OTP] → text input for OTP customer shared → POST /confirm
- [Log Failed Attempt] → reason required → POST /attempt
- Failed attempts logged and visible in order detail timeline

DEFECT PHOTO UPLOAD
- Accept: image/jpeg, image/png, max 5MB per photo, max 3 photos per inspection
- Upload progress shown via progress bar
- Photo thumbnails shown after upload, removable before save
- On save, photos linked to inspection record in backend

ACCEPTANCE CHECKLIST
[ ] QC inspection drawer with Pass / Fail / rework flow
[ ] Defect photo upload with progress and preview
[ ] Rework count badge visible on item cards
[ ] Override visible only to QC Supervisor role
[ ] Tailor assignment uses Idempotency-Key
[ ] Tailor performance report filterable by date range
[ ] Rack visual grid with slot occupancy
[ ] Rack conflict (409) shows clear error with request_id
[ ] Delivery OTP flow: dispatch → OTP entry → confirm
[ ] Failed delivery attempts logged and visible in order timeline
```

---

## 📌 PHASE 9 — Finance & Documents

```text
PHASE 9: Finance & Documents

GOAL
Invoice list and detail, payment recording, credit note flow, outstanding balance
tracking, and document download hub. Finance is append-only: no editing invoices or
payments, corrections via credit notes only.

BUSINESS REQUIREMENTS
- Invoice numbers are gap-free (backend-generated, row-locked). Never show as editable.
- Payment is recorded as an append entry; not updating an existing payment row.
- Credit note is a new document, not an edit to the original invoice.
- Outstanding balance is computed from the backend, not the frontend.
- Documents (job cards, invoices, QR) are signed downloads, never client-rendered.
- Accountant role required for all write actions in this module.

BACKEND ENDPOINTS CONSUMED
GET  /api/v1/finance/invoices?from=&to=&status=           (finance.view)
GET  /api/v1/finance/invoices/{id}                        (finance.view)
POST /api/v1/finance/invoices                             (finance.invoice.create, Idempotency-Key)
GET  /api/v1/finance/invoices/{id}/pdf                    (finance.view — signed URL)
POST /api/v1/finance/invoices/{id}/credit-note            (finance.credit_note.issue, Idempotency-Key)
GET  /api/v1/finance/payments?invoice_id=                 (finance.view)
POST /api/v1/finance/payments                             (finance.payment.record, Idempotency-Key)
GET  /api/v1/finance/orders/{id}/outstanding-balance      (finance.view)
GET  /api/v1/finance/dashboard/summary                    (finance.dashboard.view)
GET  /api/v1/documents/{id}/download                      (documents.view — signed URL)
POST /api/v1/documents/regenerate                         (documents.regenerate)
GET  /api/v1/orders/{id}/job-card.pdf                     (orders.print_job_card)
GET  /api/v1/qr/sign?type=&id=                            (qr.sign — generate signed QR payload)
GET  /api/v1/qr/decode/{payload}                          (qr.decode — verify payload)

SCREENS

/finance/invoices — Invoice List
- DataTable: invoice number | customer | date | total | paid | balance | status
- Status: issued | partially_paid | paid | credited
- FilterBar: date range | status | customer search | branch (Owner)
- [Generate Invoice] button → select order → confirm → POST (Idempotency-Key)

/finance/invoices/{id} — Invoice Detail
- InfoGrid: invoice number (read-only), customer, order, branch, date, GST, total
- MetricCards: Invoice Total | Paid | Balance Due
- Payment history: Timeline of all payments (append-only list, no edit/delete controls)
- [Record Payment] button → drawer: amount, method (Cash/UPI/Card/Bank), reference, date
  → POST /api/v1/finance/payments with Idempotency-Key
- [Issue Credit Note] button → drawer: reason, amount
  → POST /api/v1/finance/invoices/{id}/credit-note with Idempotency-Key
- [Download PDF] → GET /api/v1/finance/invoices/{id}/pdf → open signed URL in new tab
- Credit notes section: list of credit notes linked to this invoice with PDF download

/finance/outstanding — Outstanding Balances per order
- Each row: order | customer | invoice total | paid | balance
- Balance from: GET /api/v1/finance/orders/{id}/outstanding-balance per order
- Filterable by branch, amount range, date
- Click row → /finance/invoices/{id}

/documents — Document Download Hub
- List: recent job cards, invoices, QR downloads per branch
- All downloads via GET /api/v1/documents/{id}/download (signed URL, time-limited)
- [Regenerate] button: POST /api/v1/documents/regenerate { kind, reference_id }
- No document is rendered client-side

UI RULES FOR APPEND-ONLY FINANCE
- Invoice number field: always read-only, never an input
- Payment records: shown in read-only Timeline, no edit/delete button
- "Correct an invoice" → user sees info banner: "Corrections are made via credit notes"
- All record payment and credit note forms have a warning: "This cannot be undone"
- Every write action requires Idempotency-Key
- All confirmation dialogs show what will be created (amount, type) before submitting

GST QUARTERLY SUMMARY (/finance/gst)
India-specific compliance screen. Accountant uses this for quarterly GST filing.
FilterBar: fiscal quarter (Q1 Apr-Jun | Q2 Jul-Sep | Q3 Oct-Dec | Q4 Jan-Mar) | branch

MetricCards row:
  Total Taxable Value | CGST Collected | SGST Collected | IGST Collected | Total GST

Breakdown table: GST rate (5% | 12% | 18%) | taxable | CGST | SGST | IGST | invoices count

Export button: Download as Excel (formatted for CA/tax filing)
Note: CGST+SGST = intra-state transactions. IGST = inter-state. Backend computes both.

FISCAL YEAR INDICATOR
Every finance screen shows the active fiscal year in the page header:
  "FY 2024-25 (Apr 2024 – Mar 2025)"
When the fiscal year rolls over (Apr 1 IST), the indicator updates automatically.
Invoice numbering resets per branch per fiscal year — the UI makes this visible.

ACCEPTANCE CHECKLIST
[ ] Invoice number always read-only
[ ] Generate invoice linked to order (not free-form)
[ ] Payment recorded as new entry (Timeline, no edit controls)
[ ] Credit note creates new document (no edit to invoice)
[ ] Outstanding balance from backend MetricCards
[ ] All PDF downloads are signed URLs opened in new tab
[ ] Record payment and credit note forms have Idempotency-Key
[ ] Accountant role gates enforced on all write actions
[ ] GST quarterly summary loads correct totals by rate
[ ] Fiscal year indicator visible on all finance pages
[ ] Indian currency format: ₹1,42,500 not ₹142,500 (CurrencyDisplay component)
[ ] Error states show request_id
```

---

## 📌 PHASE 10 — Reports, Dashboard & Notifications

```text
PHASE 10: Reports, Dashboard & Notifications

GOAL
Owner/Admin dashboard with KPI metrics, report generation with queue-based jobs,
notification center for approval events and alerts, and audit log viewer.

BUSINESS REQUIREMENTS
- Dashboard loads fast (cached aggregates from backend, not live queries).
- Reports are generated as background jobs; frontend polls for completion.
- Notification center shows: pending measurement approvals, low-stock alerts,
  failed delivery attempts, rework loops exceeding limit.
- Audit log is read-only; Owner/Admin only.

BACKEND ENDPOINTS CONSUMED
GET  /api/v1/dashboard/summary                  (dashboard.view — branch-scoped, cached 60s)
GET  /api/v1/finance/dashboard/summary          (finance.dashboard.view)
GET  /api/v1/reports                            (reports.view — list available report kinds)
POST /api/v1/reports/run                        (reports.run)
GET  /api/v1/reports/jobs/{id}                  (reports.view — poll status)
GET  /api/v1/reports/jobs/{id}/download         (reports.view — signed URL when succeeded)
GET  /api/v1/notifications?status=&channel=     (notifications.view — not ?unread=true&page=)
GET  /api/v1/audit/activities?subject_type=&subject_id=&actor=&from=&to=  (audit.view)
GET  /api/v1/audit/transitions/{order_item_id}  (audit.transitions.view)

DASHBOARD (/dashboard) — Owner / Admin only
MetricCard row:
  Today's Orders | Active Production Items | Ready for Delivery | Outstanding Balance (₹)

Chart row (Recharts):
  Orders by status (bar chart, last 30 days)
  Revenue by branch (line chart, Owner only — multi-branch)
  Production throughput (items completed per day)

Alert widgets:
  Low Stock Rolls count → link to /inventory/fabric-rolls?status=low_stock
  Pending Measurement Approvals → link to /measurements/approvals
  Failed Deliveries Today → link to /deliveries?status=failed
  Overdue Production Items → link to /production (board filtered to overdue)

BRANCH COMPARISON VIEW (Owner only — /dashboard/branches)
Side-by-side table of all branches for the current month:

| Metric            | HQ      | Anna Nagar | Velachery |
|-------------------|---------|------------|-----------|
| Orders taken      | 142     | 89         | 61        |
| Revenue           | ₹4.2L   | ₹2.8L      | ₹1.9L     |
| Pending delivery  | 23      | 31 ⚠       | 14        |
| Overdue items     | 2       | 7 ⚠        | 1         |
| Rework rate       | 8%      | 15% ⚠      | 6%        |
| Outstanding dues  | ₹42,000 | ₹88,000 ⚠  | ₹21,000   |

⚠ = above branch average. Click any cell → navigate to that branch filtered view.
Period selector: This week | This month | This quarter.
Owner can switch branch context directly from this table.

WHATSAPP / SMS SENT LOG (in notification drawer)
When a notification was sent to a customer via WhatsApp/SMS (order confirmed, ready for
delivery, OTP), show a "Sent to customer" log entry in the notification drawer.
This tells staff "yes, the customer was informed" without them having to check manually.
Data source: GET /api/v1/notifications?status=sent&channel=whatsapp (filtered).

REPORT GENERATION FLOW
1. Report type selector populated from GET /api/v1/reports (list of available kinds)
2. User sets filters (date range, branch)
3. POST /api/v1/reports/run → returns { job_id, status: "pending" }
4. Frontend polls GET /api/v1/reports/jobs/{id} every 5s (TanStack Query refetchInterval)
5. Status: pending → running → succeeded | failed
6. On succeeded: [Download] → GET /api/v1/reports/jobs/{id}/download → new tab
7. On failed: ErrorState with request_id
8. ReportJobTracker component: shows all recent jobs with status badges

NOTIFICATION CENTER
- Bell icon in TopBar shows unread count (count from GET /notifications?status=unread)
- Drawer opens on click with scrollable notification list
- GET /api/v1/notifications?status=&channel=
- Notification types: measurement_approval_needed | low_stock | rework_exceeded | delivery_failed
- Each notification: icon (type-colored) | message | time | [Mark read] | optional link
- [Mark all read] button (batch action)
- After marking read: optimistic count decrement (low-risk for optimistic UI)

NOTIFICATION MARK-READ GAP RULE
The backend notification endpoint currently only supports GET (list).
If a mark-read endpoint is not yet available:
  - Show "read" state locally (localStorage key: "notif_read:{user_id}:{notif_id}").
  - Do NOT fake a POST call to a non-existent endpoint.
  - Mark the gap in Phase 1A contract map: "notifications.markRead — Backend gap, needs confirmation."
  - When backend adds the endpoint, wire it up without changing the UI.

AUDIT LOG (/audit)
- Owner/Admin only (route guard + permission gate)
- DataTable: actor | action | subject type | subject ID | branch | timestamp
- FilterBar: actor search | subject type select | date range
- Row expand: full JSON diff or description (from activitylog)
- /audit/transitions/{item_id}: production state machine history (used from order detail)

ACCEPTANCE CHECKLIST
[ ] Dashboard KPI metrics load fast (< 500ms on cached data)
[ ] Branch comparison table visible to Owner only
[ ] Branch comparison ⚠ highlights work correctly
[ ] Recharts charts render correctly with real backend data
[ ] Report job polling with refetchInterval, stops on complete/failed
[ ] Report download is signed URL (not client-rendered)
[ ] Notification bell shows unread count
[ ] Notification drawer: mark read, mark all read
[ ] Notification mark-read gap rule applied if endpoint missing
[ ] WhatsApp/SMS sent log visible in notification drawer
[ ] Audit log read-only, Owner/Admin gated
[ ] Audit filter by actor, subject, date range
[ ] All error states show request_id
```

---

## 📌 PHASE 11 — Testing, Performance & Security Hardening

```text
PHASE 11: Testing, Performance & Security Hardening

GOAL
Close all testing gaps, enforce performance budgets, lock down security, and prepare
for production deployment. This phase does not add new screens — it hardens what exists.

BUSINESS REQUIREMENTS
- Playwright covers all critical workflows end-to-end.
- Storybook a11y checks pass on all components.
- Core Web Vitals targets met on all main routes.
- Session tokens never in localStorage.
- CSP headers set.
- All sensitive actions (finance write, approve, cancel order) have Playwright coverage.

PLAYWRIGHT TEST COVERAGE TARGETS
Critical (must pass before deploy):
  [ ] Login + 2FA + branch switch (Owner)
  [ ] Settings: Owner changes brand color → full UI updates
  [ ] Settings: Front Desk role cannot see brand color picker (permission gate)
  [ ] Settings: toggle dark mode → refresh → dark mode persists, no white flash
  [ ] Settings: animationEnabled OFF → measurement guide static (no Motion frames)
  [ ] Settings: measurementUnit = inches → measurement form inputs show "in"
  [ ] Settings: user A sets compact density → logout → user B sees default density
  [ ] Customer search → QR scan → front desk full workflow
  [ ] Keyboard shortcuts: Ctrl+F focuses search, Ctrl+Enter opens confirm dialog
  [ ] Measurement version create + pre-fill from last version + threshold warning + approval
  [ ] MeasurementGuideAnimator: chest field focus → correct guide animates
  [ ] MeasurementGuideAnimator: sleeve_length focus → guide changes (cross-fade)
  [ ] MeasurementGuideAnimator: waist field focus → waist guide animates
  [ ] MeasurementGuideAnimator: click silhouette chest region → chest input focused
  [ ] MeasurementGuideAnimator: guide toggle OFF → panel collapses
  [ ] MeasurementGuideAnimator: reduced-motion → static tape, no animation
  [ ] MeasurementGuideAnimator: mobile "?" tap → bottom sheet opens with correct guide
  [ ] Order create → job card download
  [ ] Production transition + rework loop
  [ ] Production board: overdue item shows red border and OverdueTag badge
  [ ] Cutting reservation → consume
  [ ] QC pass + QC fail → rework → re-pass
  [ ] Delivery OTP dispatch → confirm
  [ ] Packing checklist: all items scanned before dispatch
  [ ] Invoice generate → payment record → credit note
  [ ] GST quarterly summary loads correct totals
  [ ] Report job trigger → poll → download
  [ ] Double-click idempotency guard on every critical mutation

PERMISSION TESTS (run as separate suite)
  [ ] Login as Tailor → navigate to /finance → access denied page shown
  [ ] Login as Front Desk → navigate to /measurements/approvals → approve button not rendered
  [ ] Login as Inventory Manager → navigate to /finance/invoices → access denied
  [ ] Login as Tailor → search for another branch's customer → 0 results (branch isolation)
  [ ] Login as Accountant → try POST /production/items/{id}/transition → 403 from backend
  [ ] Login as Front Desk → settings appearance → brand color picker not visible
  [ ] Login as QC Supervisor → rework override visible only after 3rd rework attempt

BRANCH ISOLATION TESTS
  [ ] Branch A staff searches Branch B customer code → not found
  [ ] Branch A staff opens /production/board → only Branch A items shown
  [ ] Owner switches branch → all query cache cleared → new branch data loaded
  [ ] Owner views branch comparison → both branches shown in single table

LOAD TESTS (k6 or Artillery — run in staging)
  [ ] 100 concurrent users on /front-desk — page loads within 2.5s
  [ ] 50 concurrent users on /production/board — board renders within 3s
  [ ] 20 concurrent production transitions — no duplicate transitions, no 500 errors
  [ ] Production board polling (30s interval, 50 users) — no memory leak over 30 minutes

MOBILE RESPONSIVE TESTS (Playwright mobile viewport)
  [ ] Front Desk 5-step mobile flow: complete order from search to confirm
  [ ] Measurement form: CollapsibleSections work, guide bottom sheet opens
  [ ] Production board: columns scroll horizontally, cards readable
  [ ] Settings pages: all controls usable on 375px viewport
  [ ] Delivery dispatch and OTP confirm flow on mobile

OFFLINE / DRAFT RECOVERY TESTS
  [ ] Fill order form → disconnect network → submit fails → form data preserved
  [ ] Fill measurement form → close browser → reopen → draft restored with banner
  [ ] Offline banner appears within 1s of network disconnect
  [ ] Retry button on failed mutation uses same Idempotency-Key (no duplicate)
  [ ] Draft cleared after successful submit

SECURITY TESTS
  [ ] No auth token found in localStorage after login (confirmed via Playwright check)
  [ ] Console.log contains no tokens or sensitive data in production build
  [ ] CSP header present on all responses — verified via network tab
  [ ] Direct navigation to /admin/users as Tailor → redirect to /unauthorized
  [ ] XSS: input field with <script>alert(1)</script> → rendered as plain text
  [ ] QR payload tampered → backend returns 422, frontend shows error (not crash)

ACCESSIBILITY TESTS (Storybook a11y + Playwright)
  [ ] All interactive elements reachable by Tab key
  [ ] All buttons have accessible labels (no icon-only buttons without aria-label)
  [ ] Focus ring visible on all interactive elements (data-focus-always="true" mode)
  [ ] Screen reader: DataTable announces column headers
  [ ] All form fields have associated labels
  [ ] Colour is not the only way to convey status (OverdueTag has text, not just red border)

REGRESSION TESTS (run before every release)
  [ ] Vertical slice: Login → Customer → Measurement → Order → Job Card PDF
  [ ] Finance: Invoice → Payment → Credit Note → Outstanding Balance correct
  [ ] Production: Order created → transitions to Delivered → order status = Delivered
  [ ] Inventory: GRN received → roll created → allocated to cutting → consumed

ACCEPTANCE / UAT TESTS (real shop workflow)
  [ ] New customer walks in → order taken → job card printed → under 3 minutes
  [ ] Returning customer alteration → measurement updated → new order → 90 seconds
  [ ] QC failure → rework assigned → re-QC pass → ready for delivery
  [ ] Complete delivery: OTP sent → packing checked → OTP confirmed → delivered

STORYBOOK COVERAGE TARGETS
  [ ] All components in components/ui/ and components/shared/ have stories
  [ ] All stories pass @storybook/addon-a11y
  [ ] Interaction tests for: IdempotencyGuard, ConfirmDialog, VersionDiffView, DataTable
  [ ] MeasurementGuideAnimator: stories for all 27 guide states
  [ ] MeasurementGuideAnimator: ReducedMotion story (static tape, no animation)
  [ ] MeasurementGuideAnimator: GuideOff story (panel hidden)
  [ ] HelpTooltip: Default, LongContent, Mobile variants
  [ ] OverdueTag: StageOverdue, DeliveryOverdue, BothOverdue variants
  [ ] CurrencyDisplay: Standard, Lakhs, Crores variants (Indian locale)

PERFORMANCE TARGETS (Lighthouse CI in GitHub Actions)
  LCP ≤ 2.5s — measure on: /front-desk, /production, /inventory/fabric-rolls, /finance/invoices
  INP ≤ 200ms — measure on: garment option click, table filter, Kanban card expand
  CLS ≤ 0.1 — measure on all routes

PERFORMANCE TECHNIQUES
  - loading.js skeletons on every data route (eliminates CLS on load)
  - Server Components for all read-heavy pages (smaller client bundles)
  - TanStack Table virtualization for tables > 100 rows
  - Image/icon: next/image for any photography, inline SVG for icons
  - Motion: lazy-import heavy animation components, skip on reduced-motion
  - Bundle analysis: @next/bundle-analyzer, flag any chunk > 150KB (gzipped)

SECURITY CHECKLIST
  [ ] No auth token in localStorage (verified by Playwright check)
  [ ] Sanctum cookie-based session: HttpOnly, Secure, SameSite=Lax
  [ ] CSP header set via Next.js headers() config (disallow inline scripts, frame)
  [ ] HSTS header on HTTPS deployment
  [ ] All forms use Zod; no raw user input passed to URLs or query strings
  [ ] File uploads: type + size validated client-side AND backend-enforced
  [ ] QR payload validation: always pass to backend for verification, never parse client-side
  [ ] Role-change → all tokens revoked → user sees login page (Playwright test)
  [ ] Owner branch-switch invalidates branch-scoped cache (TanStack Query reset)

DEPLOYMENT CHECKLIST
  [ ] next build passes with 0 TypeScript errors
  [ ] next lint passes
  [ ] Storybook build passes
  [ ] Playwright all critical tests green
  [ ] Lighthouse CI green on performance targets
  [ ] Environment variables documented in .env.example
  [ ] No sensitive values in .env committed
  [ ] Error monitoring configured (e.g., Sentry for frontend)
```

---

## 📌 PHASE 12 — Final Frontend Architecture Review

```text
PHASE 12: Final Frontend Architecture Review

GOAL
Independently review the implemented frontend against the areas below and produce a
Production Readiness Score out of 100, with concrete remediation actions.

REVIEW AREAS — for each deliver: What is correct | What is risky | Must fix | Priority

 1. Route structure and App Router usage (Server vs Client Component split)
 2. API client (envelope parsing, error normalization, request_id surfacing)
 3. Idempotency handling (all mutations, double-click guard, retry behavior)
 4. Auth and session security (no localStorage, cookie handling, token refresh)
 5. Branch context (header on every request, Owner switcher, cache invalidation)
 6. Permission gates (route-level AND action-level, not just UI hiding)
 7. Settings & preferences (CSS variables, no hardcoded colors, user-scoped storage,
    dark mode no-flash, brand color permission gate, preferences consumed by all phases)
 8. Measurement versioning (append-only UX enforced, no edit-in-place anywhere)
 9. Production state machine UI (allowed states only, transitions confirmed, rework visible)
10. Inventory ledger display (three values: remaining / reserved / available)
11. Finance append-only UX (invoice read-only, payment ledger, credit note flow)
12. Garment visualizer (SVG + Motion, reduced-motion fallback, all options covered)
12a. MeasurementGuideAnimator (all 12 guide states, tape animation, bidirectional click,
     guide toggle syncs to Settings, mobile bottom sheet, reduced-motion static,
     helper text present, animationEnabled preference respected)
13. Component library (branded, a11y passing, Storybook coverage)
14. Data fetching (TanStack Query everywhere, no useState+fetch, proper invalidation)
15. Form validation (Zod at every boundary, error messages from backend mapped to fields)
16. Performance (loading skeletons, Server Components, virtualization, bundle size)
17. Accessibility (WCAG 2.2 AA, keyboard complete, visible focus, non-color status)
18. Testing coverage (Playwright critical paths, Storybook a11y, interaction tests)
19. Security headers (CSP, HSTS, no XSS vectors, file upload validation)
20. Error states (request_id visible, machine-readable codes handled, retry paths)
21. Mobile usability (stepped flows, bottom nav, touch targets, offline feedback)
22. i18n infrastructure (react-i18next installed, all strings via t(), no hardcoded JSX text)
23. Role dashboards (every role has focused today view, no generic admin panel for all)
24. Customer 360 (all 4 tabs, family filter, QR display, balance, timeline)
25. Admin management (user CRUD, 2FA status, session revoke, branch management)
26. Global search (all entity types, branch isolation, recent history)
27. Production overdue alerts (SLA config, red border, OverdueTag badge)
28. Measurement pre-fill (last approved version pre-loaded, changed fields highlighted)
29. Packing checklist (scan to verify, cannot dispatch before complete)
30. Scanner mode (auto-focus, correct routing, beep, duplicate warning)
31. GST quarterly summary (correct totals by rate, fiscal year indicator, export)
32. Branch comparison (Owner only, ⚠ highlights, period selector)
33. Currency format (Indian locale ₹1,42,500 via CurrencyDisplay, no raw numbers)

DELIVERABLES
- Review document with 20 sections in the format above
- Prioritized fix list (High / Medium / Low) with effort estimate (S/M/L)
- Production Readiness Score / 100
- "Top 7 to fix before go-live" with exact file paths to change

OUTPUT
Review document only. Do not modify code in this phase. Cite specific file paths
and component names as evidence. Where evidence is missing, mark "needs verification."
```

---

---

## 📌 PHASE 13 — Admin Management

```text
PHASE 13: Admin Management

GOAL
Build the admin area for managing users, roles, branches, 2FA status, login history,
and active sessions. This is Owner/Admin only. Every action here is branch-scoped
except for Owner who can manage across all branches.

BACKEND ENDPOINTS CONSUMED
GET    /api/v1/users                                  (users.view)
POST   /api/v1/users                                  (users.create, Idempotency-Key)
PUT    /api/v1/users/{id}                             (users.create)
DELETE /api/v1/users/{id}                             (Owner only)
POST   /api/v1/users/{id}/assign-role                 (users.create, Idempotency-Key)
GET    /api/v1/branches                               (branches.view)
POST   /api/v1/branches                               (Owner, Idempotency-Key)
PUT    /api/v1/branches/{id}                          (Owner)
POST   /api/v1/auth/2fa/enable                        (auth — for managed 2FA setup)
POST   /api/v1/auth/2fa/disable                       (auth)
GET    /api/v1/audit/activities?subject_type=User     (audit.view — login history)

ROUTES
/admin                  → redirect to /admin/users
/admin/users            → user list + create
/admin/users/{id}       → user detail + edit + role + sessions
/admin/roles            → role list with permission summary (read-only view)
/admin/branches         → branch list + create + edit (Owner only)
/admin/sessions         → all active sessions across users (Owner/Admin)

SCREENS

/admin/users — User List
- DataTable: name | email | role | branch | 2FA status | last active | status (active/inactive)
- FilterBar: role | branch | status | search by name/email
- [Create User] → DrawerPanel: name, email, phone, branch, role → POST (Idempotency-Key)
- Row click → /admin/users/{id}
- Status toggle: active ↔ inactive → PUT /users/{id} with is_active

/admin/users/{id} — User Detail
- InfoGrid: name, email, phone (masked), role, branch, created at, last login
- 2FA Section:
    If enabled: green badge "2FA Active" + [Disable 2FA] button (Admin can disable for user)
    If not enabled: amber badge "2FA Not Set" + [Send Setup Link] or [Enable on their behalf]
    Note: Owner/Admin/Accountant must have 2FA — cannot deactivate it for these roles.
- Login History: last 10 logins with IP, user agent, success/fail, timestamp
    Source: GET /api/v1/audit/activities?subject_type=User&subject_id={id}
- Active Sessions: list of current tokens with device, last used, [Revoke] button
    Revoking calls DELETE /api/v1/users/{id} token endpoint (backend token revocation)
- [Change Role] → modal: select new role + branch → POST /assign-role (Idempotency-Key)
- [Deactivate User] → ConfirmDialog → PUT with is_active: false

/admin/roles — Role Overview (read-only)
- Shows all 13 roles with their permission summary
- Not editable — permissions are set by backend RolePermissionSeeder
- Useful for admin reference: "What can a QC Supervisor do?"

/admin/branches — Branch Management (Owner only)
- DataTable: branch name | code | address | GST number | status | users count
- [Create Branch] → DrawerPanel: name, code, address, GST number → POST (Idempotency-Key)
- Row click → edit drawer → PUT /branches/{id}
- Cannot delete a branch with active orders or users (backend enforces)

/admin/sessions — Active Session Management (Owner/Admin)
- DataTable: user name | role | device | last active | branch
- [Revoke] button per row → confirms + revokes that user's tokens
- [Revoke All Except Mine] emergency button → useful if device is compromised
- Sessions longer than 24h flagged in amber (tokens expire at 24h but refresh extends)

ACCEPTANCE CHECKLIST
[ ] /admin routes all gated to Owner/Admin only
[ ] User create/edit/deactivate working with Idempotency-Key
[ ] 2FA status visible per user, enable/disable available
[ ] Login history visible (last 10 from audit log)
[ ] Active sessions list with revoke per session
[ ] Branch create/edit (Owner only)
[ ] Role overview shows permissions (read-only)
[ ] Deactivated user cannot log in
[ ] Playwright: login as Tailor → /admin → access denied
```

---

## 📌 PHASE 14 — Role Dashboards, Customer 360 & Global Search

```text
PHASE 14: Role Dashboards, Customer 360 & Global Search

GOAL
Three things in one phase: (1) Every role gets a today-focused landing dashboard that
shows only what's relevant to their work. (2) The customer profile page — the full
360 view of a customer. (3) The universal topbar search.

BACKEND ENDPOINTS CONSUMED
GET /api/v1/dashboard/summary                (dashboard.view)
GET /api/v1/production/board                 (production.view — for role-filtered cards)
GET /api/v1/cutting/queue                    (fabric.allocate)
GET /api/v1/tailoring/assignments?tailor=me  (orders.view)
GET /api/v1/tailoring/performance/{tailorId} (tailoring.performance.view)
GET /api/v1/deliveries?status=dispatched     (deliveries.view)
GET /api/v1/finance/invoices?from=today      (finance.view)
GET /api/v1/customers/{id}                   (customers.view)
GET /api/v1/customers/{cid}/measurements     (measurements.view)
GET /api/v1/orders?customer={id}             (orders.view)
GET /api/v1/finance/orders/{id}/outstanding-balance (finance.view)

=== ROLE DASHBOARDS ===

Every role lands on a focused "Today" view. Not a generic admin panel.
Each dashboard has: summary metric cards (top), main work table (center), quick actions.

OWNER / ADMIN DASHBOARD (/dashboard)
Metric cards: Today's orders | Active production items | Ready for delivery |
              Pending approvals | Outstanding dues | Low stock rolls
Charts: Revenue trend (30 days) | Production throughput | Branch comparison table
Alerts: Overdue items | Pending approvals | Failed deliveries | Low stock

FRONT DESK (/front-desk)
Already specced in Phase 3E. This is the workstation, not a dashboard.
Top summary strip: Orders today: 12 | Pending in queue: 4 | New customers today: 3

MEASUREMENT STAFF (/measurements)
Metric cards: Pending approvals | Measurements taken today | Threshold warnings active
Main table: Pending approval queue (same as /measurements/approvals)
Quick actions: [Take New Measurement] for a specific customer

PRODUCTION SUPERVISOR (/production)
Already specced in Phase 6 (the Kanban board).
Metric cards above board: Total active | Overdue | In rework | Ready for delivery today
Alert row: items stuck > SLA threshold highlighted

CUTTING MASTER (/cutting)
Metric cards: Waiting for fabric: 8 | Fabric allocated: 12 | Completed today: 6 | Shortages: 2
Main table: Cutting queue (Phase 6 cutting queue section)
Alert: "4 items have no fabric allocated — fabric shortage"

TAILOR (/tailoring/my-work)
Metric cards: Assigned to me: 8 | In progress: 2 | Completed today: 4 | Rework returned: 1
Main table: My items — order code | garment | customer | fabric | due date | status | action
Actions per row: [Start Work] [Mark Complete] [View Measurement] [View Job Card] [Report Issue]
Rework item: amber left border + "Returned from QC: sleeve length issue"
Tailor should NOT see: finance, payment, customer phone, other tailors' items

KAJA BUTTON (/tailoring/my-work?stage=KajaButton)
Same as Tailor view but filtered to KajaButton-stage items only.
Metric cards: Pending kaja/button work | Completed today
Actions: [Start] [Complete]

QC SUPERVISOR (/qc)
Already specced in Phase 8. Metric cards above queue:
Pending QC: 10 | Passed today: 7 | Failed today: 2 | Rework rate this week: 11%

IRONING MASTER (/qc/ironing)
Metric cards: Pending ironing | Completed today
Main table: Items in Finishing stage assigned to ironing
Actions: [Mark Ironing Done]

RE-WORKER (/qc/rework)
Metric cards: Assigned rework items | Completed rework today
Main table: Rework items with QC failure reason
Actions: [Start Rework] [Mark Complete] [View QC Defect Photo]

INVENTORY MANAGER (/inventory)
Already specced in Phase 7. Metric cards above rolls list:
Low stock rolls | Pending POs | Movements today | Damage reports pending approval

ACCOUNTANT (/finance)
Metric cards: Today's collections | Pending invoices | Outstanding balance (total) |
              Invoices due this week
Main table: Recent payment activity (today's payments)
Quick actions: [Record Payment] [Generate Invoice] [View GST Summary]

DELIVERY STAFF (/delivery)
Metric cards: Ready for dispatch: 6 | Dispatched today: 4 | Confirmed delivered: 3 | Failed: 1
Main table: Delivery queue (Phase 8 delivery section)
Actions: [Dispatch] [Confirm OTP] [Log Failed Attempt]

=== CUSTOMER 360 VIEW (/customers/{id}) ===

This is the full customer profile page. Opened from search results, order detail, or
measurement approval. The most-viewed page after the front desk workstation.

LAYOUT — DESKTOP (two-column)
Left column (320px):
  Customer avatar (initials circle) + name + customer code
  Phone (masked: ****2345) | Email | Address
  QR code: full-screen QR display button (for customer to scan on their own phone)
  Family members: horizontal chips, click to filter right column to that member

Right column (flex-grow):
  Tabs: Orders | Measurements | Balance | Timeline

ORDERS TAB
- DataTable: order code | date | items | status | delivery date | total | balance
- Status badge with color. Overdue delivery date in red.
- Click row → /orders/{id}
- Quick stats above table: Total orders | This year | Lifetime spend | Last visit

MEASUREMENTS TAB
- Profile list (shirt / pant / both) with latest version status
- Click profile → version history as cards
- [Take New Measurement] button → navigates to measurement desk with customer pre-selected

BALANCE TAB
- Outstanding balance total (prominent MetricCard)
- Invoice list: invoice no | date | total | paid | balance | status
- [Record Payment] quick action → opens payment drawer

TIMELINE TAB
- Chronological feed: order created, measurement taken, delivery completed, payment recorded
- All activity visible in one scroll — no jumping between tabs for history

MOBILE LAYOUT
- Accordion-style: customer info at top, tabs become collapsible sections
- QR display as full-screen button

=== GLOBAL SEARCH ===

Universal search in the TopBar. Keyboard shortcut: Ctrl+K (or / from anywhere).

WHAT IT SEARCHES (debounced 300ms, searches all types simultaneously):
  customer_name     → customer profile
  phone_last_4      → customer profile
  customer_code     → customer profile
  order_code        → order detail
  invoice_no        → invoice detail
  fabric_roll_code  → fabric roll detail
  bundle_qr         → bundle detail
  rack_slot_code    → rack slot detail (e.g. "RACK-A12")
  delivery_code     → delivery detail

SEARCH RESULT GROUPINGS
Results grouped by type with icons:
  👤 Customers (2)        → Ramesh Kumar — ****2345 — Customer SSI-C-001
  📦 Orders (1)           → SSI-HQ-ORD-00125 — Ramesh Kumar — In Tailoring
  🧾 Invoices (1)         → INV-2425-00045 — ₹3,200 — Partially Paid
  🧵 Fabric Rolls (1)     → LIN-WHT-009 — 18m available

Click any result → navigate to that entity's page.
Recent searches: last 5 shown when search box is empty (stored in localStorage per user).
No results: "Nothing found for 'XYZ'" + quick links to create customer / new order.

SEARCH BACKEND NOTE
Global search calls multiple endpoints simultaneously using Promise.all:
  GET /api/v1/customers?search={query}&page=1 (top 3 results)
  GET /api/v1/orders?customer={query}&page=1 (top 3 results)
  GET /api/v1/finance/invoices?status=&from=&to= filtered locally if small dataset
Fabric rolls, bundles, rack slots matched by QR/code pattern client-side from cached data.
If a dedicated /search endpoint is added to the backend later, replace Promise.all with it.

ACCEPTANCE CHECKLIST
[ ] Every role lands on their focused today view (not the owner dashboard)
[ ] Tailor sees only assigned items — no finance, no other tailors' work
[ ] Accountant sees today's collection metrics + GST quick link
[ ] Delivery sees dispatched queue with OTP confirm action
[ ] Customer 360: all 4 tabs working (Orders, Measurements, Balance, Timeline)
[ ] Customer 360: QR full-screen display works
[ ] Customer 360: family member filter applies to tabs
[ ] Global search: Ctrl+K opens search from anywhere
[ ] Global search: results grouped by type
[ ] Global search: customer, order, invoice, fabric roll all return correct results
[ ] Global search: recent searches shown when empty
[ ] Branch isolation: search only returns current branch results
[ ] Playwright: login as Tailor → /tailoring/my-work → only assigned items shown
[ ] Playwright: search customer by phone last 4 → correct result
[ ] Playwright: search order code → order detail page opens
```

---

## 📌 PHASE 15 — Central Approval Inbox, Packing Checklist & Scanner Mode

```text
PHASE 15: Central Approval Inbox, Packing Checklist & Scanner Mode

GOAL
Three standalone workflows that don't fit neatly into existing phases:
(1) A single /approvals screen unifying all approval types.
(2) A packing checklist before delivery dispatch.
(3) A hardware scanner mode for shops using barcode/QR scanners.

BACKEND ENDPOINTS CONSUMED
GET  /api/v1/measurements/pending-approval               (measurements.approve)
POST /api/v1/measurements/versions/{id}/approve          (measurements.approve, Idempotency-Key)
POST /api/v1/measurements/versions/{id}/reject           (measurements.approve)
GET  /api/v1/damage-reports?status=pending               (damage_reports.view)
POST /api/v1/damage-reports/{id}/approve                 (damage_reports.approve, Idempotency-Key)
POST /api/v1/damage-reports/{id}/reject                  (damage_reports.reject)
POST /api/v1/qc/items/{id}/rework-override               (production.rework.override, Idempotency-Key)
GET  /api/v1/orders/{id}                                 (orders.view — items for packing)
GET  /api/v1/rack/items/{itemId}/current-slot            (rack.view)
GET  /api/v1/cutting/bundles/by-qr/{payload}             (bundles.view)
GET  /api/v1/customers/by-qr/{payload}                   (customers.view)
GET  /api/v1/inventory/fabric-rolls/by-qr/{payload}      (inventory.view)

=== CENTRAL APPROVAL INBOX (/approvals) ===

One screen. All pending approvals visible to the logged-in user based on their role.
Role determines which tabs are visible:
  Owner/Admin: all 4 tabs
  QC Supervisor / Production Supervisor: Measurements + Rework
  Inventory Manager: Damage Reports

TABS

Measurements tab (measurements.approve)
- Table: customer | family member | version | changed fields | delta summary | submitted by | date
- Each row expands to show full VersionDiffView (changed fields highlighted in amber)
- Actions: [Approve] [Reject with note]
- After approve/reject: row exits table (Motion fade-out)

Damage Reports tab (damage_reports.approve — Owner only)
- Table: fabric roll | damage type | metres lost | photo | reported by | date
- Each row expands: damage photo, notes, roll before/after stock
- Actions: [Approve Damage] [Reject]
- Approve: deducts metres from fabric roll

Rework Overrides tab (production.rework.override)
- Table: item | customer | garment | QC fail count | tailor | last failure reason
- Each row expands: QC inspection history, defect photos
- Actions: [Approve Override] [Reject]
- Context: "This item has failed QC 3 times. Override allows a 4th rework attempt."

Finance Alerts tab (finance.dashboard.view — Owner/Admin/Accountant)
- High-value payment warnings: payment amount exceeds invoice total → needs confirmation
- Credit notes above threshold → needs Owner review
- Not a blocking workflow — informational with [Acknowledge] action stored locally

BADGE COUNTS
TopBar notification bell badge includes counts from all approval types, not just
measurement approvals. UnifiedApprovalCount = sum of all pending tabs.

=== PACKING CHECKLIST (/delivery/{id}/packing) ===

Before a delivery can be dispatched, staff physically verifies every item.
This screen is accessed from the delivery queue by clicking [Start Packing] before [Dispatch].

WORKFLOW
1. Delivery staff clicks [Start Packing] on a delivery record.
2. Packing checklist opens: shows all order items with their rack slots.
3. Each item row: garment type | customer name | rack slot | [Scan to Verify] | status
4. Staff scans the rack slot QR (or manually marks each item):
   GET /api/v1/rack/items/{itemId}/current-slot → confirms item is at expected slot.
5. All items verified → [Packing Complete] button activates → proceed to [Dispatch].

SCAN VERIFICATION
- Scan rack QR → matches expected slot → green check on that row.
- Scan rack QR → item not in this slot → red error: "Expected RACK-A12, found RACK-B04."
- All items checked without scanning: [Mark All Verified] manual override (requires supervisor role).

PACKING SLIP
After all items verified, auto-generate packing slip link:
GET /api/v1/documents/regenerate body:{ kind: "packing_slip", reference_id: order_id }
→ signed PDF download. Delivery staff prints or shows on phone.

If an item is missing from its rack slot: "Item not found at RACK-A12. Contact supervisor
before dispatch." Cannot proceed to dispatch until resolved.

MOBILE LAYOUT
- Single column list. Each item is a scannable card.
- Large scan area at top for camera scan or hardware scanner.
- [Scan] button per row for item-by-item confirmation on mobile.

=== HARDWARE SCANNER MODE (/scan) ===

Shops using USB/Bluetooth barcode or QR scanners need a dedicated mode. Hardware scanners
behave like a keyboard — they type characters and auto-press Enter. The UI must be ready for
this input pattern, not just camera-based scanning.

SCANNER MODE SCREEN
- Full-screen interface. Minimal UI. One large input field: always focused.
- Header: "Scanner Ready" with a blinking indicator dot.
- No mouse needed. Keyboard-first.

AUTO-FOCUS BEHAVIOUR
- Input field is focused on page load.
- After each scan result is processed, input is re-focused automatically.
- If user accidentally clicks elsewhere, input regains focus after 500ms.
- This mimics how a POS system works.

SCAN ROUTING
The scanner decodes the QR payload and routes to the correct action:
  bundle QR    → GET /cutting/bundles/by-qr/{payload} → show bundle, [Send to Tailoring]
  customer QR  → GET /customers/by-qr/{payload}       → open customer 360 view
  fabric roll  → GET /inventory/fabric-rolls/by-qr/{payload} → open roll detail
  rack slot    → GET /rack/items/{itemId}/current-slot → show what's in this slot
  delivery QR  → GET /deliveries?code={payload}        → open delivery record

SCAN FEEDBACK
- Success: green flash on screen + short beep (if soundFeedback preference is ON)
  Web Audio API: 880Hz, 80ms duration, sine wave
- Failure: red shake animation + lower beep: 220Hz, 200ms duration
- Duplicate scan (same QR within 10 seconds): amber warning:
  "Already scanned [bundle code] 8 seconds ago." [Continue Anyway] [Cancel]

RECENT SCAN HISTORY
Last 10 scans shown as a scrollable list below the input:
  timestamp | type | code | result | action taken
Useful for catching scanner errors without having to rescan.

PERMISSION CONTEXT
Scanner mode routes to whatever screen the logged-in role has access to.
If a Tailor scans a finance QR: "You do not have permission to view this item."

ACCEPTANCE CHECKLIST
[ ] /approvals shows correct tabs per role
[ ] Measurement approval diff visible inline before approving
[ ] Damage report approval deducts stock (verified by checking roll after approve)
[ ] Rework override shows QC history and defect photos
[ ] Unified approval badge count in TopBar
[ ] Packing checklist accessible from delivery queue
[ ] Scan to verify each rack slot item
[ ] Packing slip generated after all items verified
[ ] Cannot dispatch before packing complete (button disabled)
[ ] Scanner mode: input always focused, auto-refocus after scan
[ ] Scanner mode: correct routing per QR type
[ ] Scanner mode: beep on success (if sound enabled)
[ ] Scanner mode: duplicate scan warning within 10 seconds
[ ] Recent scan history shows last 10 scans
[ ] Hardware scanner (keyboard emulation) tested with USB scanner device
[ ] Playwright: approval → approve measurement → row exits table
[ ] Playwright: scanner duplicate scan → amber warning shown
```

---

## 📌 PHASE 16 — Future Work (Deferred — needs backend additions first)

```text
PHASE 16: Future Work — do not implement until backend phases are added

These features are valuable but require backend endpoints or jobs not yet specced
in the Laravel backend prompt pack. Add them after the backend is extended.

BULK IMPORT / EXPORT
What's needed in backend first:
  - POST /api/v1/import/customers (async job, returns job_id)
  - POST /api/v1/import/fabric-rolls (async job)
  - GET  /api/v1/import/jobs/{id} (poll status)
  - GET  /api/v1/import/jobs/{id}/error-report (failed rows as CSV)
Without the async validation job, the frontend cannot show row-level errors safely.

Frontend scope once backend is ready:
  Download sample CSV → upload CSV → preview (valid rows / error rows) →
  import valid rows only → download error report for failed rows.

ORDER TRACKING LINK (CUSTOMER-FACING)
What's needed in backend first:
  - GET /api/v1/track/{signed_token} — public endpoint, no auth required
  - Returns: order status, items, expected delivery, branch phone — no sensitive data
Without this public endpoint, there is no safe way to expose order status without login.

Frontend scope once backend is ready:
  Read-only page at /track/{token}. No login. Shows: order items, current stage (step
  indicator), expected delivery date, branch contact. Sent to customer via WhatsApp
  when order is confirmed.
```

---

## 📦 How to use this pack

Do not ask Claude Code to build the entire frontend in one prompt. One phase per session.

**Recommended build order (final):**

```
1.  Phase 0   — Screenshot to Screen Design (run when you have reference screenshots)
2.  Phase 1   — Project Shell, Auth & Branch Context
3.  Phase 1A  — API Contract Alignment Matrix  ← do this before any screen
4.  Phase 1B  — Typed API Client & Zod Contract
5.  Phase 2   — Design System & Shared Component Library
6.  Phase 2B  — Global Settings & User Preferences
7.  Phase 3A  — Customer Search & Quick Create
8.  Phase 3B  — Family Member & Measurement Version Selector
9.  Phase 3C  — Order Item Builder
10. Phase 3D  — Garment Visualizer
11. Phase 3E  — Full Front Desk Integration  ← vertical slice here
12. Phase 4   — Measurement Desk + Guide Animator
13. Phase 5   — Orders & Job Cards
14. Phase 6   — Production Board
15. Phase 7   — Inventory, Fabric Rolls & Suppliers
16. Phase 8   — QC, Rework, Tailoring & Delivery
17. Phase 9   — Finance & Documents
18. Phase 10  — Reports, Dashboard & Notifications
19. Phase 11  — Testing, Performance & Security Hardening
20. Phase 12  — Final Frontend Architecture Review
21. Phase 13  — Admin Management
22. Phase 14  — Role Dashboards, Customer 360 & Global Search
23. Phase 15  — Central Approval Inbox, Packing Checklist & Scanner Mode
24. Phase 16  — Future Work reference (do not build — backend not ready)
```

**Vertical slice validation — do this after Phase 3E before continuing:**
> Login → Customer Search → QR Scan → Measurement Version Select →
> Order Create → Job Card PDF (opens in new tab)
>
> Only after this full slice works end-to-end, continue to Phase 4 onward.

**Rules:**
- Always paste the Master Prompt first, then the phase prompt. Never skip the Master Prompt.
- Check off every acceptance checklist item before moving to the next phase.
- Commit and tag between every phase: `v0.1-fe-phase-1`, `v0.1-fe-phase-3e`, etc.
- Phase 12 runs last — it is the audit, not the build.
- If Claude invents an endpoint not in the Endpoint Alignment Rule, stop it immediately.

---

## 🔐 UI PERMISSION MATRIX

Create `/docs/ui-permission-matrix.md` during Phase 1A. Update it each phase.

Table format: `Screen | Action | Required Permission | Visible To | Hidden From | Backend Endpoint`

| Screen | Action | Required Permission | Visible To | Hidden From | Endpoint |
|--------|--------|-------------------|------------|-------------|----------|
| /settings/appearance | Change brand color | settings.brand_color | Owner, Admin | All others | — (CSS var) |
| /measurements/approvals | Approve version | measurements.approve | Owner, QC Supervisor, Prod. Supervisor | All others | POST /measurements/versions/{id}/approve |
| /production | Transition item | production.transition.{state} | Role-matched | Others | POST /production/items/{id}/transition |
| /qc | Rework override | production.rework.override | Owner, QC Supervisor | All others | POST /qc/items/{id}/rework-override |
| /inventory/fabric-rolls | Adjust stock out | inventory.fabric_rolls.adjust_out_approve | Owner, Admin | All others | POST /fabric-rolls/{id}/adjust |
| /damage-reports | Approve/Reject | damage_reports.approve | Owner | All others | POST /damage-reports/{id}/approve |
| /rack | Assign slot | rack.assign | Owner, Admin, Delivery Staff | Tailor, Front Desk | POST /rack/items/{itemId}/assign |
| /finance/invoices | Record payment | finance.payment.record | Owner, Admin, Accountant | Front Desk, Tailor | POST /finance/payments |
| /finance/invoices | Issue credit note | finance.credit_note.issue | Owner, Admin, Accountant | Front Desk, Tailor | POST /invoices/{id}/credit-note |
| /audit | View all activity | audit.view | Owner, Admin | All others | GET /audit/activities |

Rules:
- Route-level guard required (server-side redirect for unauthorized users).
- Action-level UI hide required (button not rendered, not just disabled).
- Unauthorized 403 page: friendly message + role-specific context. No technical error shown.
- Backend remains source of truth. Frontend gates are UX, never security.
- Owner branch switch must not leak cached data from previous branch.

```text
Acceptance checklist:
[ ] /docs/ui-permission-matrix.md exists and covers all screens
[ ] Every route has requirePermission() guard
[ ] Every write button has can() check before render
[ ] 403 page shows friendly message with role context
[ ] Branch switch clears all branch-scoped query cache
```

---

## 🔄 TANSTACK QUERY CACHE INVALIDATION MATRIX

Create `/docs/query-invalidation-map.md` during Phase 1A.

Rules:
- Every mutation must list query keys it invalidates on success.
- Finance, stock, invoice, payment, irreversible production transitions: NO optimistic success.
- Optimistic UI only for: notification read/unread, UI-only toggles.
- Branch switch: `queryClient.clear()` — wipe all cached data.
- Logout: `queryClient.clear()` — wipe all cached data.

| Mutation | Invalidates On Success | Optimistic Allowed? |
|----------|----------------------|---------------------|
| Create customer | customers.list | No |
| Create order | orders.list, customers.detail, dashboard.summary | No |
| Create measurement version | measurements.versions(profileId), measurements.pending, customers.detail | No |
| Approve measurement version | measurements.versions(profileId), measurements.pending | No |
| Production transition | production.board, production.item(id), production.history(id), orders.detail(orderId), dashboard.summary | No |
| Cutting allocate-fabric | inventory.fabricRoll(rollId), inventory.movements(rollId), cutting.queue | No |
| Cutting release-fabric | inventory.fabricRoll(rollId), inventory.movements(rollId), cutting.queue | No |
| QC inspect | production.board, qc.history(itemId), orders.detail(orderId) | No |
| Rack assign | rack.slots, rack.currentSlot(itemId) | No |
| Delivery dispatch | delivery.list, delivery.detail(id) | No |
| Delivery confirm | delivery.list, production.board, rack.slots | No |
| Create invoice | finance.invoices, orders.detail(orderId), finance.outstanding | No |
| Record payment | finance.invoice(id), finance.payments(id), finance.outstanding, dashboard.summary | No |
| Issue credit note | finance.invoice(id), finance.outstanding | No |
| Mark notification read | notifications.list | Yes (low risk) |
| Mark all read | notifications.list | Yes (low risk) |
| Branch switch | ALL — queryClient.clear() | No |
| Logout | ALL — queryClient.clear() | No |

---

## 📡 REALTIME STRATEGY

Initial release: polling via TanStack Query `refetchInterval`. No WebSocket dependency.

| Screen | Polling Interval | Notes |
|--------|-----------------|-------|
| Production board | User setting: 15s / 30s / 60s / Off | Configurable in /settings/production |
| Notification bell | 30s | Background refetch, no loading spinner |
| Dashboard | 60s or manual refresh button | Show "Last updated X min ago" |
| Cutting queue | 30s | Active operations change fast |
| Delivery queue | 30s | OTP dispatches are time-sensitive |
| Finance screens | Off while form is active | Never refresh while user is entering payment |

Future upgrade path (no code change needed in UI components):
- Create `NotificationTransport` interface in `lib/transport/`.
- Current impl: `PollingTransport` using TanStack Query refetchInterval.
- Future impl: `WebSocketTransport` (Laravel Reverb / Pusher) behind the same interface.
- UI components depend only on the interface, not the transport.
- Swapping polling for WebSocket = one file change in `lib/transport/index.ts`.

```text
Acceptance checklist:
[ ] Production board polling interval reads from user preferences
[ ] Notification polling active at 30s
[ ] Dashboard has manual refresh button
[ ] Finance input forms disable auto-refresh while focused
[ ] NotificationTransport interface exists in lib/transport/
[ ] Transport can be swapped without touching UI components
```

---

## 🌐 OFFLINE, UNSAVED DRAFT & NETWORK RESILIENCE

Add to Phase 1 foundation. All domain phases consume these utilities.

OFFLINE DETECTION
- `useNetworkStatus()` hook: wraps `navigator.onLine` + online/offline events.
- Global `<OfflineBanner>` in AppShell: shows when offline.
  Text: "No internet connection. Changes will be saved when you reconnect."
- Slow network: if any fetch takes > 5s, show `<SlowNetworkBanner>`.
  Text: "Slow connection. Please wait or try again."

UNSAVED CHANGES PROTECTION
- `useFormGuard(isDirty: boolean)` hook.
- When `isDirty = true` and user navigates away: browser beforeunload + Next.js router.beforePopState.
- Dialog: "You have unsaved changes. Leave and discard?" [Stay] [Leave]
- Apply to: measurement form, order item builder, QC inspection drawer, damage report form.

DRAFT AUTO-SAVE
- Storage key pattern: `drafts:{user_id}:{module}`
  e.g. `drafts:user_42:order_builder`, `drafts:user_42:measurement_form`
- Auto-save on every significant change (debounced 2s).
- On load: if draft exists for current user → banner: "You have an unsaved draft. [Restore] [Discard]"
- Draft cleared on: successful submit, explicit discard.
- Draft validation: validate against Zod schema on restore; if invalid (stale shape), discard silently.

Modules requiring draft:
- Order builder (Phase 3C)
- Measurement form (Phase 4)
- QC inspection (Phase 8)
- Damage report (Phase 7)
- Customer quick-create (Phase 3A)

MUTATION FAILURE RECOVERY
- On mutation failure: ErrorState with request_id + [Retry] button.
- Form data preserved exactly as submitted (do not reset on failure).
- [Retry] reuses the same Idempotency-Key (correct — backend deduplicates).
- Finance, payment, and order confirm mutations: NEVER auto-retry silently.
  User must explicitly click [Retry] after seeing the error.

```text
Acceptance checklist:
[ ] Offline banner renders when navigator.onLine = false
[ ] Slow network banner after 5s on pending fetch
[ ] Unsaved changes warning blocks navigation when form is dirty
[ ] Draft auto-saved under drafts:{user_id}:{module}
[ ] Draft restored on page load with banner
[ ] Draft cleared on successful submit
[ ] Draft key is user-scoped (user A's draft not shown to user B)
[ ] Form data preserved after failed mutation
[ ] No finance/payment mutation auto-retried silently
[ ] Retry uses same Idempotency-Key
```

---

## 🌍 i18n PRACTICAL RULES

Infrastructure: `react-i18next`. English (en) is the only complete locale.

Rules:
- English is fully implemented first. Tamil (ta), Hindi (hi), Kannada (kn) are placeholder
  locale files. They contain English strings until translations are provided.
- No hardcoded JSX text strings in reusable components. All strings use `t("key")`.
- Page-level temporary English strings are allowed during early build phases, but
  Phase 11 hardening must move ALL user-facing strings into locale files.
- Translation keys must be human-readable and module-namespaced.

Key naming convention: `{module}.{screen}.{element}`

Examples:
```
frontDesk.search.placeholder          → "Search by name or phone..."
frontDesk.search.noResults            → "No customer found"
frontDesk.search.createCta            → "Create new customer"
measurements.guide.chestHelper        → "Around the fullest part of the chest."
measurements.form.thresholdWarning    → "{count} fields exceed change thresholds."
measurements.form.approvalRequired    → "An approval note is required."
finance.payment.confirmWarning        → "This payment cannot be undone."
finance.invoice.appendOnlyNote        → "Corrections are made via credit notes."
production.transition.confirmTitle    → "Move to {state}?"
errors.requestId                      → "Error reference: {requestId}"
common.save                           → "Save"
common.cancel                         → "Cancel"
common.retry                          → "Try again"
```

```text
Acceptance checklist:
[ ] react-i18next installed and configured
[ ] locales/en.json exists with all strings
[ ] locales/ta.json, hi.json, kn.json exist (English stubs, marked "(coming soon)" in UI)
[ ] No hardcoded user-facing string in any reusable component
[ ] Phase 11 check: grep for JSX string literals, fail if found in components/
```

---

## ⚠️ Frontend invariants — never break these

- Measurements are append-only. Never render an edit-in-place measurement form.
- Production state is on `order_items`, not `orders`. Order status is derived in the frontend.
- Stock shows three values: remaining, reserved, available. Never just one "stock" number.
- Fabric reserve/consume/release is in the CUTTING module. Do not call inventory endpoints for this.
- Finance write actions are append-only. No edit buttons on invoices or payments.
- Invoice numbers come from the backend. Never editable, never computed client-side.
- Idempotency-Key on every mutation. No exceptions.
- Session tokens never in localStorage.
- Backend is source of truth for authorization. Frontend gates are UX convenience only.
- QR payloads are validated by the backend, not parsed client-side.
- Every error state exposes `request_id` so support staff can trace without screenshots.
- Reports are triggered with `POST /reports/run`, not `POST /reports/jobs`.
- Delivery routes use `/deliveries`, not `/delivery`.
- Inventory fabric rolls use `/fabric-rolls`, not `/rolls`.
- Damage reports are at `/damage-reports`, not `/inventory/damage`.

If Claude tries to simplify any of these into a generic CRUD pattern or invents an
endpoint, point it back to the "Backend Endpoint Alignment Rule" at the top of this file.

