'use client'

// Token lives in sessionStorage (cleared on tab close), never localStorage.
// Server components must read from cookie headers — this module is client-only.

const TOKEN_KEY = 'ss_token'
const USER_KEY = 'ss_user'

function isBrowser(): boolean {
  return typeof window !== 'undefined'
}

export function getToken(): string | null {
  if (!isBrowser()) return null
  return sessionStorage.getItem(TOKEN_KEY)
}

export function setToken(token: string): void {
  if (!isBrowser()) return
  sessionStorage.setItem(TOKEN_KEY, token)
}

export function getStoredUser<T = unknown>(): T | null {
  if (!isBrowser()) return null
  const raw = sessionStorage.getItem(USER_KEY)
  if (!raw) return null
  try {
    return JSON.parse(raw) as T
  } catch {
    return null
  }
}

export function setStoredUser(user: unknown): void {
  if (!isBrowser()) return
  sessionStorage.setItem(USER_KEY, JSON.stringify(user))
}

export function clearSession(): void {
  if (!isBrowser()) return
  sessionStorage.removeItem(TOKEN_KEY)
  sessionStorage.removeItem(USER_KEY)
  sessionStorage.removeItem('ss_branch_id')
}

export function isAuthenticated(): boolean {
  return Boolean(getToken())
}
