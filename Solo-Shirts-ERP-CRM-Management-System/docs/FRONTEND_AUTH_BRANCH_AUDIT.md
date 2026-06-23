# Frontend Auth / Session / Branch Context Audit — Solo Shirts India ERP

**Date:** 2026-06-12 · Files: `lib/auth/session.ts`, `store.ts`, `useAuth.ts`, `branch-context.ts`; `components/shell/{AuthGuard,BranchSwitcher,TopBar,UserMenu}.tsx`; `client.ts`; `(auth)/{login,2fa}/page.tsx`.

| Flow | Expected | Actual | File | Status | Issue |
|--|--|--|--|--|--|
| Unauthenticated → /login | redirect | `if(!isAuthenticated()) router.replace('/login')` | `AuthGuard.tsx:43-45` | Pass | |
| login | POST /auth/login | `apiMutate('post', auth.login)` | `login/page.tsx:51` | Pass | |
| 2FA confirm | POST /auth/2fa/confirm | `apiMutate('post', auth['2faConfirm'],{code})` | `2fa/page.tsx:52-56` | Pass | |
| /auth/me after login/refresh | load user from /auth/me | user taken from **login response**, not a separate `/auth/me` call | `login/page.tsx` | Partial | works, but `/auth/me` not re-validated on cold load (verify `useAuth`) |
| logout calls backend + clears state | POST /auth/logout + clear | `apiMutate('post',auth.logout)` then `clearSession()`+`reset()`+**`queryClient.clear()`**+redirect | `UserMenu.tsx` | Pass | ✅ ~~FE-010~~ fixed (cache cleared) |
| refresh retry once | single retry | `_retry` flag + queue; one refresh; failure → clear+/login | `client.ts:50-104` | Pass | |
| **token not in localStorage** | sessionStorage/cookie/memory only | **sessionStorage** (`ss_token`) — never localStorage | `session.ts:6,15,20` | **Pass** | ✅ rule 17 |
| change password | backend endpoint | `apiPost(auth.changePassword)` → **/auth/change-password not in backend** | `settings/profile/page.tsx:61` | **Fail** | FE-003 |
| update profile | backend endpoint | `apiPut(auth.updateProfile)` → **PUT /auth/me not in backend (GET-only)** | `settings/profile/page.tsx:47` | **Fail** | FE-004 |
| **Owner sees branch switcher** | switcher for Owner only | `if(!is(ROLES.OWNER)\|\|branches<=1) → read-only tag` | `BranchSwitcher.tsx:14-24` | **Pass** | ✅ rule 18 |
| Admin/non-owner no switcher | read-only | read-only branch tag for non-Owner | `BranchSwitcher.tsx` | Pass | rule 19 |
| branch switch calls API | POST /auth/switch-branch | `apiMutate('post',auth.switchBranch,{branch_id})` + new token saved | `branch-context.ts:11-22` | Pass | |
| branch switch → refetch/invalidate | invalidate branch-scoped queries | ✅ `queryClient.clear()` after switch | `BranchProvider.tsx` | **Pass** | ~~FE-009~~ fixed |
| branch context propagation | token `active_branch_id` (backend) | FE also injects **`X-Branch-Id`** header from sessionStorage | `client.ts:26-31` | Partial | header **ignored** by backend (token-based) — FE-018 |
| branch name in TopBar | visible | `<BranchSwitcher/>` shows active branch name | `TopBar.tsx:37` | Pass | |
| cross-branch UI leak | no leak | scoped by token; but stale cache after switch (FE-009) | — | Partial | FE-009 |

## Findings
- ✅ **Token storage (rule 17), refresh-once, login/2FA/logout, Owner-only branch switcher (rule 18)** are all correct.
- **FE-003 / FE-004 (High):** Settings → profile update and change password call endpoints the backend doesn't expose (`PUT /auth/me`, `/auth/change-password`). These two settings actions will 404/405. Classify **Backend gap — needs confirmation** (does the team intend to add them, or remove the UI?).
- ~~**FE-009 (High):** branch switch updates the token but does not invalidate cached queries.~~ **✅ Fixed (2026-06-12):** `BranchProvider.switchBranch` calls `queryClient.clear()` after the token update.
- ~~**FE-010 (High):** logout does not clear the TanStack Query cache.~~ **✅ Fixed (2026-06-12):** `UserMenu.handleLogout` calls `queryClient.clear()`.
- **FE-018 (Low):** `X-Branch-Id` header is dead weight — backend resolves branch from the token's `active_branch_id`, not this header. Harmless but misleading; remove or confirm.

**Verdict:** auth fundamentals are **solid and secure** (sessionStorage, refresh-once, Owner-gated switcher). The defects are: two non-existent settings endpoints (FE-003/004), and cache not reset on branch-switch/logout (FE-009/010).
</content>
