# Measurement Alignment Audit — Solo Shirts India ERP

**Date:** 2026-06-12 · Routes: `measurements/page.tsx`, `measurements/[profileId]/page.tsx`, `measurements/approvals/page.tsx`; `components/measurements/MeasurementGuideAnimator.tsx`, `components/ui/version-diff-view.tsx`; `hooks/useMeasurements.ts`.

| Check | Expected | Actual | Status | Issue |
|--|--|--|--|--|
| Measurements are versioned | append-only versions | new version created via `POST /measurements/profiles/{id}/versions`; old versions read-only | Pass | rule 22 ✅ |
| No edit-in-place for old versions | create new version, never mutate | entry screen always **creates a new version** (`useCreateMeasurementVersion`) | Pass | ✅ |
| Create version endpoint | correct | `ENDPOINTS.measurementVersions(profileId)` POST | Pass | non-stable key (FE-007) |
| Approve/reject permission-gated | `measurements.approve` | Approvals screen gated; Front Desk hidden (rbac) | Pass | |
| Approve via correct ep | POST /versions/{v}/approve | `approveMeasurement(v)` with **IdempotencyGuard** | Pass | ✅ stable key |
| Reject with reason | reason required | reject drawer requires reason | Pass | |
| Threshold warning if backend sends it | render warning | depends on backend field; FE renders measurement alerts if present | Partial | verify field name mapping |
| Version diff shown | compare versions | `version-diff-view.tsx` present | Pass | |
| Order uses measurement_version_id | bind approved version id | front-desk selects approved version → order item `measurement_version_id` | Pass | rule 23 ✅ |
| Garment SVG visualizer doesn't override backend | display only | `MeasurementGuideAnimator` is a guide animation, not data source | Pass | ✅ |
| Form 422 → field errors | map errors | react-hook-form + `form-field`; backend `errors{}` available via normalizeError | Partial | per-field mapping not uniformly wired |
| request_id on error | shown | ErrorDrawer shows request_id | Pass | |
| Reduced motion respected | prefers-reduced-motion | accessibility settings page + framer-motion; verify guard on animator | Partial | confirm `useReducedMotion` in animator |
| Invalidation after version create | versions + pendingApprovals + customer measurements | versions + pendingApprovals only | Partial | FE-014 (missing parent list) |

## Findings
- ✅ **Append-only versioning (rule 22), approved-version-bound orders (rule 23), permission-gated approval with stable idempotency key** are all correctly implemented — this module is one of the better-aligned areas.
- Minor: non-stable key on version-create (FE-007), missing parent-list invalidation (FE-014), and 422 field mapping + reduced-motion guard should be verified live.

**Verdict:** **Pass** on the core measurement contract; minor invalidation/UX items only.
</content>
