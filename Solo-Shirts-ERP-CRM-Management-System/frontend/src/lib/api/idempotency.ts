'use client'

import { useCallback, useRef } from 'react'
import { generateIdempotencyKey } from '@/lib/api/client'

/**
 * FE-007 — stable Idempotency-Key support.
 *
 * The client auto-generates a fresh key per call, so a rapid double-click would
 * send two *different* keys and the backend would treat them as two distinct
 * actions (duplicate records). These helpers keep a SINGLE key stable for one
 * logical action until it succeeds, so a double-submit sends one key and the
 * backend de-duplicates.
 *
 * Keys live only in memory (a module Map / a ref) — never localStorage.
 */

const actionKeys = new Map<string, string>()

/** Reuse (or create) a stable key for a named action id. */
export function getIdempotencyKey(actionId: string): string {
  let key = actionKeys.get(actionId)
  if (!key) {
    key = generateIdempotencyKey()
    actionKeys.set(actionId, key)
  }
  return key
}

/** Release the key after the action succeeds (next attempt gets a fresh key). */
export function clearIdempotencyKey(actionId: string): void {
  actionKeys.delete(actionId)
}

/**
 * Per-mounted-form stable key. The same key is reused for every submit attempt
 * (including double-clicks and idempotent retries of a failed attempt) until
 * `reset()` is called — typically in the mutation's `onSuccess`.
 */
export function useStableIdempotencyKey(): { readonly current: string; reset: () => void } {
  const ref = useRef<string>(generateIdempotencyKey())
  const reset = useCallback(() => {
    ref.current = generateIdempotencyKey()
  }, [])
  return {
    get current() {
      return ref.current
    },
    reset,
  }
}
