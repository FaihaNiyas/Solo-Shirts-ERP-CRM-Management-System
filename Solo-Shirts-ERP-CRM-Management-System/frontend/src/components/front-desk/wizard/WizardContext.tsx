'use client'

import React, {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useRef,
  useState,
} from 'react'
import { v4 as uuidv4 } from 'uuid'
import { useAuthStore } from '@/lib/auth/store'
import { apiGet, apiMutate, generateIdempotencyKey } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { buildOrderPayload } from './payload'
import type { Customer } from '@/lib/api/schemas/customers'
import {
  type MainOrderMeta,
  type PaymentDraft,
  type SubOrderDraft,
  type WizardSnapshot,
  type WizardStep,
  WIZARD_STEPS,
} from './types'
import { completedCount } from './validation'
import { orderTotals } from './pricing'
import { clearDraft, loadDraft, saveDraft } from './draftStorage'

/** A sub-order with no user-entered data — safe to drop when reducing count. */
function isBlankSub(s: SubOrderDraft): boolean {
  return !s.measurementVersionId && !s.fabricId && !s.styleId && !s.fitId && !s.notes?.trim()
}

function blankSubOrder(): SubOrderDraft {
  return {
    tempId: uuidv4(),
    itemId: null,
    measurementVersionId: null,
    measurementLabel: null,
    measurementStatus: null,
    fabricId: null,
    fabricLabel: null,
    styleId: null,
    styleLabel: null,
    fitId: null,
    fitLabel: null,
    quantity: 1,
    notes: '',
    basePrice: 0,
    discountAmount: 0,
    gstRate: 0,
    pdfStatus: 'pending',
    documentId: null,
    pdfUrl: null,
    printStatus: 'pending',
    productionStatus: 'draft',
  }
}

function todayIso(): string {
  // Avoid Date in module scope; computed lazily inside the client only.
  return new Date().toISOString().slice(0, 10)
}

function defaultMeta(): MainOrderMeta {
  // orderDate is left empty here and filled with "today" in a mount effect so
  // the SSR render path never calls new Date() (avoids any hydration mismatch).
  return {
    source: 'walk_in',
    orderDate: '',
    deliveryDate: '',
    deliveryMode: 'pickup',
    notes: '',
    totalShirts: 1,
  }
}

function defaultPayment(): PaymentDraft {
  return { advancePaid: 0, method: null, reference: '' }
}

/** Map a wizard snapshot → the server draft create/update body (Phase 6B). */
function draftBody(snap: WizardSnapshot): Record<string, unknown> {
  return {
    customer_id: snap.customer?.id ?? null,
    family_member_id: snap.memberId ?? null,
    order_id: snap.orderId ?? null,
    title: snap.customer?.name ?? null,
    current_step: snap.activeStep,
    completed_count: completedCount(snap.subOrders),
    total_items: snap.subOrders.length,
    draft_payload: snap,
  }
}

interface WizardState {
  activeStep: WizardStep
  // The persisted order id, set once the order is created (entering Print Center).
  orderId: number | null
  // The server draft id (Phase 6B) once this wizard is persisted server-side.
  draftId: number | null
  customer: Customer | null
  memberId: number | null
  memberLabel: string
  meta: MainOrderMeta
  subOrders: SubOrderDraft[]
  payment: PaymentDraft
}

function initialState(): WizardState {
  return {
    activeStep: 'customer',
    orderId: null,
    draftId: null,
    customer: null,
    memberId: null,
    memberLabel: '',
    meta: defaultMeta(),
    subOrders: [blankSubOrder()],
    payment: defaultPayment(),
  }
}

interface WizardContextValue extends WizardState {
  // navigation
  activeIndex: number
  maxReachedIndex: number
  goTo: (step: WizardStep) => void
  next: () => void
  back: () => void
  // mutators
  setCustomer: (c: Customer | null) => void
  setMember: (id: number | null, label: string) => void
  patchMeta: (patch: Partial<MainOrderMeta>) => void
  setTotalShirts: (n: number) => void
  addSubOrder: () => void
  duplicateSubOrder: (tempId: string) => void
  removeSubOrder: (tempId: string) => void
  patchSubOrder: (tempId: string, patch: Partial<SubOrderDraft>) => void
  patchPayment: (patch: Partial<PaymentDraft>) => void
  // Phase 2 — order persistence (created when entering the Print Center)
  orderId: number | null
  locked: boolean // true once the order is created → sub-order details are fixed
  creating: boolean
  createError: string | null
  isConfirmed: boolean // true once the order is confirmed → show success, hide nav
  ensureOrderCreated: () => Promise<boolean>
  // draft
  draftId: number | null
  savedAt: string | null
  saveNow: () => void
  pauseDraft: () => Promise<void>
  discardDraft: () => void
  /** Blank the wizard for a fresh order (clears local autosave + current draft id). */
  resetWizard: (keepLocal?: boolean) => void
  /** Clear the saved draft after a confirmed order, keeping the view intact. */
  finalizeConfirmed: () => void
  // derived
  progressDone: number
  progressTotal: number
  grandTotal: number // order grand total (rupees), from per-shirt pricing
  balance: number
}

const Ctx = createContext<WizardContextValue | null>(null)

const MAX_SHIRTS = 50

export function FrontDeskWizardProvider({
  children,
  resumeDraftId = null,
}: {
  children: React.ReactNode
  /** Phase 6B — resume this server draft instead of starting fresh. */
  resumeDraftId?: number | null
}) {
  const userId = useAuthStore((s) => s.user?.id)

  const [state, setState] = useState<WizardState>(initialState)
  const [savedAt, setSavedAt] = useState<string | null>(null)
  const [maxReachedIndex, setMaxReachedIndex] = useState(0)
  const [creating, setCreating] = useState(false)
  const [createError, setCreateError] = useState<string | null>(null)
  const [isConfirmed, setIsConfirmed] = useState(false)
  const hydrated = useRef(false)
  const dirty = useRef(false)
  // Set once the order is confirmed → stop autosave/warn without resetting the
  // visible state, so the success screen can render.
  const confirmed = useRef(false)
  // Stable idempotency key so a retried/double-fired create can't duplicate.
  const orderIdemKey = useRef(generateIdempotencyKey())
  // The server draft id — mirrors state.draftId for use inside async callbacks.
  const draftIdRef = useRef<number | null>(null)
  // Bumped on every reset. An in-flight autosave captures the generation and is
  // dropped if it resolves after a reset, so it can never re-attach the old draft.
  const genRef = useRef(0)

  const hydrateFrom = useCallback((draft: WizardSnapshot, draftId: number | null) => {
    draftIdRef.current = draftId
    setState({
      activeStep: draft.activeStep,
      orderId: draft.orderId ?? null,
      draftId,
      customer: draft.customer,
      memberId: draft.memberId,
      memberLabel: draft.memberLabel,
      meta: { ...defaultMeta(), ...draft.meta },
      subOrders: draft.subOrders?.length ? draft.subOrders : [blankSubOrder()],
      payment: { ...defaultPayment(), ...draft.payment },
    })
    setSavedAt(draft.savedAt)
    setMaxReachedIndex(WIZARD_STEPS.indexOf(draft.activeStep))
  }, [])

  // Mount behaviour (client only → no SSR mismatch):
  //   • Resume (explicit ?draft=id) → load ONLY that server draft.
  //   • New Order (no id) → ALWAYS blank. We never auto-hydrate the localStorage
  //     autosave, so a just-paused draft can't reappear. A legacy local draft
  //     that was never synced is migrated to the server ONCE (so it surfaces in
  //     the drafts list) but is not loaded into this fresh form.
  useEffect(() => {
    if (hydrated.current) return
    hydrated.current = true

    void (async () => {
      if (resumeDraftId) {
        try {
          const env = await apiGet<{ id: number; draft_payload: WizardSnapshot }>(ENDPOINTS.frontDeskDraft(resumeDraftId))
          hydrateFrom(env.data.draft_payload, env.data.id)
        } catch {
          // Requested draft unavailable → show a blank form, never the wrong
          // (localStorage) one.
          setState((p) => ({ ...p, meta: { ...p.meta, orderDate: todayIso() } }))
        }
        return
      }

      // New Order — migrate a legacy un-synced local draft once, then start blank.
      const local = loadDraft(userId)
      if (local?.customer && !local.draftId) {
        try {
          await apiMutate('post', ENDPOINTS.frontDeskDrafts, draftBody(local))
        } catch {
          /* leave the local copy in place as a fallback */
        }
      }
      clearDraft(userId) // stale autosave can never repopulate a New Order
      setState((p) => ({ ...p, meta: { ...p.meta, orderDate: todayIso() } }))
    })()
  }, [userId, resumeDraftId, hydrateFrom])

  const snapshot = useCallback(
    (s: WizardState): WizardSnapshot => ({
      version: 1,
      activeStep: s.activeStep,
      orderId: s.orderId,
      draftId: draftIdRef.current ?? s.draftId,
      customer: s.customer,
      memberId: s.memberId,
      memberLabel: s.memberLabel,
      meta: s.meta,
      subOrders: s.subOrders,
      payment: s.payment,
      savedAt: new Date().toISOString(),
    }),
    [],
  )

  // Best-effort server sync (Phase 6B). Creates the draft on first save and
  // PATCHes thereafter. Failures are swallowed — the synchronous localStorage
  // write in persist() remains the safety net.
  const syncServer = useCallback(
    async (snap: WizardSnapshot, gen: number) => {
      if (!snap.customer) return
      try {
        // Decide PATCH-vs-POST on the id CAPTURED in the snapshot, not the live
        // ref. A stale autosave that fires after a reset still knows its own
        // draft id, so it PATCHes that draft instead of POSTing a duplicate.
        if (snap.draftId) {
          await apiMutate('patch', ENDPOINTS.frontDeskDraft(snap.draftId), draftBody(snap))
        } else {
          const env = await apiMutate<{ id: number }>('post', ENDPOINTS.frontDeskDrafts, draftBody(snap))
          // A reset happened while this POST was in flight → do NOT re-attach the
          // freshly-created draft to the now-blank wizard.
          if (gen !== genRef.current) return
          draftIdRef.current = env.data.id
          setState((p) => ({ ...p, draftId: env.data.id }))
          clearDraft(userId) // server is now the source of truth
        }
      } catch {
        /* offline / server error → localStorage fallback already written */
      }
    },
    [userId],
  )

  const persist = useCallback(
    (s: WizardState) => {
      const snap = snapshot(s)
      saveDraft(userId, snap)
      setSavedAt(snap.savedAt)
      dirty.current = false
      void syncServer(snap, genRef.current)
    },
    [snapshot, syncServer, userId],
  )

  // Debounced autosave whenever state changes after hydration. A draft is only
  // worth saving once a customer is selected — this prevents a "phantom draft"
  // being written (and nagging the dashboard) just by opening the wizard.
  useEffect(() => {
    if (!hydrated.current || !state.customer || confirmed.current) return
    dirty.current = true
    const gen = genRef.current
    const t = setTimeout(() => {
      // A reset (pause/discard/confirm) bumped the generation → drop this stale
      // autosave so it can't re-create or re-attach the old draft.
      if (gen !== genRef.current) return
      persist(state)
    }, 700)
    return () => clearTimeout(t)
  }, [state, persist])

  // Warn before leaving with unsaved work (browser-level: refresh / close).
  // TODO(phase-2): also intercept in-app route changes for a softer prompt.
  useEffect(() => {
    function onBeforeUnload(e: BeforeUnloadEvent) {
      if (dirty.current && !confirmed.current) {
        e.preventDefault()
        e.returnValue = ''
      }
    }
    window.addEventListener('beforeunload', onBeforeUnload)
    return () => window.removeEventListener('beforeunload', onBeforeUnload)
  }, [])

  const activeIndex = WIZARD_STEPS.indexOf(state.activeStep)

  const goTo = useCallback((step: WizardStep) => {
    setState((p) => ({ ...p, activeStep: step }))
    setMaxReachedIndex((m) => Math.max(m, WIZARD_STEPS.indexOf(step)))
  }, [])

  const next = useCallback(() => {
    setState((p) => {
      const i = WIZARD_STEPS.indexOf(p.activeStep)
      const ni = Math.min(i + 1, WIZARD_STEPS.length - 1)
      setMaxReachedIndex((m) => Math.max(m, ni))
      return { ...p, activeStep: WIZARD_STEPS[ni] }
    })
  }, [])

  const back = useCallback(() => {
    setState((p) => {
      const i = WIZARD_STEPS.indexOf(p.activeStep)
      return { ...p, activeStep: WIZARD_STEPS[Math.max(i - 1, 0)] }
    })
  }, [])

  const setCustomer = useCallback((c: Customer | null) => {
    setState((p) => ({
      ...p,
      customer: c,
      // Reset member to "Self" for the new customer.
      memberId: null,
      memberLabel: c ? c.name.split(' ')[0] : '',
    }))
  }, [])

  const setMember = useCallback((id: number | null, label: string) => {
    setState((p) => ({ ...p, memberId: id, memberLabel: label }))
  }, [])

  const patchMeta = useCallback((patch: Partial<MainOrderMeta>) => {
    setState((p) => ({ ...p, meta: { ...p.meta, ...patch } }))
  }, [])

  const setTotalShirts = useCallback((nRaw: number) => {
    const n = Math.max(1, Math.min(MAX_SHIRTS, Math.floor(nRaw) || 1))
    setState((p) => {
      const cur = p.subOrders
      if (n > cur.length) {
        const extra = Array.from({ length: n - cur.length }, () => blankSubOrder())
        return { ...p, meta: { ...p.meta, totalShirts: n }, subOrders: [...cur, ...extra] }
      }
      if (n < cur.length) {
        // Remove only trailing BLANK cards — never silently delete entered data.
        const next = [...cur]
        while (next.length > n && next.length > 1 && isBlankSub(next[next.length - 1])) {
          next.pop()
        }
        return { ...p, meta: { ...p.meta, totalShirts: next.length }, subOrders: next }
      }
      return { ...p, meta: { ...p.meta, totalShirts: n } }
    })
  }, [])

  const addSubOrder = useCallback(() => {
    setState((p) => {
      const subOrders = [...p.subOrders, blankSubOrder()]
      return { ...p, subOrders, meta: { ...p.meta, totalShirts: subOrders.length } }
    })
  }, [])

  const duplicateSubOrder = useCallback((tempId: string) => {
    setState((p) => {
      const src = p.subOrders.find((s) => s.tempId === tempId)
      if (!src) return p
      const clone: SubOrderDraft = { ...src, tempId: uuidv4() }
      const idx = p.subOrders.findIndex((s) => s.tempId === tempId)
      const subOrders = [...p.subOrders]
      subOrders.splice(idx + 1, 0, clone)
      return { ...p, subOrders, meta: { ...p.meta, totalShirts: subOrders.length } }
    })
  }, [])

  const removeSubOrder = useCallback((tempId: string) => {
    setState((p) => {
      if (p.subOrders.length <= 1) return p // keep at least one
      const subOrders = p.subOrders.filter((s) => s.tempId !== tempId)
      return { ...p, subOrders, meta: { ...p.meta, totalShirts: subOrders.length } }
    })
  }, [])

  const patchSubOrder = useCallback((tempId: string, patch: Partial<SubOrderDraft>) => {
    setState((p) => ({
      ...p,
      subOrders: p.subOrders.map((s) => (s.tempId === tempId ? { ...s, ...patch } : s)),
    }))
  }, [])

  const patchPayment = useCallback((patch: Partial<PaymentDraft>) => {
    setState((p) => ({ ...p, payment: { ...p.payment, ...patch } }))
  }, [])

  const saveNow = useCallback(() => {
    if (!state.customer) return // nothing meaningful to persist yet
    persist(state)
  }, [persist, state])

  /**
   * Reset the wizard to a blank, fresh order: clears the local autosave, the
   * current draft id, all dirty/confirmed flags, and bumps the generation so any
   * in-flight autosave is ignored. `keepLocal` retains the localStorage copy (used
   * when a Save & Pause could not reach the server — the work isn't lost).
   */
  const resetWizard = useCallback((keepLocal = false) => {
    genRef.current += 1
    if (!keepLocal) clearDraft(userId)
    draftIdRef.current = null
    confirmed.current = false
    dirty.current = false
    orderIdemKey.current = generateIdempotencyKey()
    setState({ ...initialState(), meta: { ...defaultMeta(), orderDate: todayIso() } })
    setSavedAt(null)
    setMaxReachedIndex(0)
    setCreateError(null)
    setIsConfirmed(false)
  }, [userId])

  /**
   * Save & Pause — persist the latest payload to the server, mark it paused, then
   * clear the active wizard so the next "New Order" starts blank. On a network
   * failure the work is kept locally (and migrates to the server on next mount).
   */
  const pauseDraft = useCallback(async () => {
    if (!state.customer) return
    const snap = snapshot(state)
    const id = draftIdRef.current ?? snap.draftId
    try {
      if (id) {
        await apiMutate('patch', ENDPOINTS.frontDeskDraft(id), { ...draftBody(snap), status: 'paused' })
      } else {
        // create() always starts active → create, then flip to paused.
        const env = await apiMutate<{ id: number }>('post', ENDPOINTS.frontDeskDrafts, draftBody(snap))
        await apiMutate('patch', ENDPOINTS.frontDeskDraft(env.data.id), { status: 'paused' })
      }
      resetWizard()
    } catch {
      saveDraft(userId, snap)
      resetWizard(true)
    }
  }, [snapshot, state, userId, resetWizard])

  /**
   * Persist the order so box/PDF/print can operate on real sub-orders. Called
   * when the user enters the Print Center. Idempotent: returns true if the order
   * already exists, and the stable idempotency key guards a double-fire.
   */
  const ensureOrderCreated = useCallback(async (): Promise<boolean> => {
    if (state.orderId) return true
    if (!state.customer) {
      setCreateError('Select a customer before continuing.')
      return false
    }
    setCreating(true)
    setCreateError(null)
    try {
      const payload = buildOrderPayload(state.customer, state.memberLabel, state.meta, state.subOrders)
      const res = await apiMutate<{ id: number; items?: Array<{ id: number }> }>(
        'post',
        ENDPOINTS.orders,
        payload,
        orderIdemKey.current,
      )
      const items = res.data.items ?? []
      setState((p) => ({
        ...p,
        orderId: res.data.id,
        // Items are returned in creation order → map 1:1 with sub-order cards.
        subOrders: p.subOrders.map((s, i) => ({ ...s, itemId: items[i]?.id ?? s.itemId })),
      }))
      return true
    } catch (err: unknown) {
      setCreateError((err as { message?: string })?.message ?? 'Failed to create the order.')
      return false
    } finally {
      setCreating(false)
    }
  }, [state.orderId, state.customer, state.memberLabel, state.meta, state.subOrders])

  const discardDraft = useCallback(() => {
    const id = draftIdRef.current
    if (id) void apiMutate('delete', ENDPOINTS.frontDeskDraft(id)).catch(() => {})
    resetWizard()
  }, [resetWizard])

  const finalizeConfirmed = useCallback(() => {
    confirmed.current = true
    dirty.current = false
    genRef.current += 1 // drop any in-flight autosave so it can't touch the converting draft
    setIsConfirmed(true)
    clearDraft(userId) // remove the local draft so a confirmed order can't be resumed
    const id = draftIdRef.current
    if (id) void apiMutate('post', ENDPOINTS.frontDeskDraftConvert(id), {}).catch(() => {})
  }, [userId])

  const progressDone = useMemo(() => completedCount(state.subOrders), [state.subOrders])
  const progressTotal = state.subOrders.length
  const grandTotal = useMemo(() => orderTotals(state.subOrders).grandPaise / 100, [state.subOrders])
  const balance = Math.max(0, grandTotal - state.payment.advancePaid)

  const value: WizardContextValue = {
    ...state,
    activeIndex,
    maxReachedIndex,
    goTo,
    next,
    back,
    setCustomer,
    setMember,
    patchMeta,
    setTotalShirts,
    addSubOrder,
    duplicateSubOrder,
    removeSubOrder,
    patchSubOrder,
    patchPayment,
    locked: state.orderId !== null,
    creating,
    createError,
    isConfirmed,
    ensureOrderCreated,
    savedAt,
    saveNow,
    pauseDraft,
    discardDraft,
    resetWizard,
    finalizeConfirmed,
    progressDone,
    progressTotal,
    grandTotal,
    balance,
  }

  return <Ctx.Provider value={value}>{children}</Ctx.Provider>
}

export function useWizard(): WizardContextValue {
  const ctx = useContext(Ctx)
  if (!ctx) throw new Error('useWizard must be used within FrontDeskWizardProvider')
  return ctx
}
