'use client'

import { useEffect, useRef, useState } from 'react'
import { useRouter } from 'next/navigation'
import { toast } from 'sonner'
import { ArrowLeft, ArrowRight, PauseCircle, ShieldAlert, Trash2 } from 'lucide-react'
import { usePermission } from '@/lib/auth/permissions'
import { ShortcutKey } from '@/components/shortcuts/ShortcutKey'
import { isTypingTarget, useShortcutsEnabled } from '@/lib/shortcuts/useKeyboardShortcuts'
import { apiMutate } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { ConfirmDialog } from '@/components/ui/confirm-dialog'
import { PageHeader } from '@/components/ui/page-header'
import { WizardStepper } from './WizardStepper'
import { DraftStatusIndicator } from './DraftStatusIndicator'
import { PaymentSummaryBar } from './PaymentSummaryBar'
import { WIZARD_STEPS } from './types'
import { completedCount } from './validation'
import { useWizard } from './WizardContext'
import { StepCustomer } from './steps/StepCustomer'
import { StepMember } from './steps/StepMember'
import { StepMainOrder } from './steps/StepMainOrder'
import { StepSubOrders } from './steps/StepSubOrders'
import { StepPrintCenter } from './steps/StepPrintCenter'
import { StepPayment } from './steps/StepPayment'
import { StepReview } from './steps/StepReview'

export function FrontDeskWizard() {
  const router = useRouter()
  const { can } = usePermission()
  const {
    activeStep,
    activeIndex,
    maxReachedIndex,
    goTo,
    next,
    back,
    customer,
    meta,
    subOrders,
    locked,
    orderId,
    isConfirmed,
    pauseDraft,
    discardDraft,
  } = useWizard()

  const [cancelOpen, setCancelOpen] = useState(false)
  const [cancelling, setCancelling] = useState(false)
  const [pausing, setPausing] = useState(false)
  const shortcutsEnabled = useShortcutsEnabled()

  if (!can('orders.create')) {
    return (
      <div className="flex flex-col items-center justify-center py-20 text-center">
        <ShieldAlert size={28} strokeWidth={1.5} className="text-[var(--color-warning)] mb-2" />
        <p className="text-sm font-medium text-[var(--color-text-primary)]">
          You don&apos;t have permission to create orders.
        </p>
        <p className="text-xs text-[var(--color-text-muted)]">Required permission: orders.create</p>
      </div>
    )
  }

  // Per-step gating for the "Next" button.
  const allSubsComplete = subOrders.length > 0 && completedCount(subOrders) === subOrders.length
  const deliveryBeforeOrder =
    !!meta.orderDate && !!meta.deliveryDate && meta.deliveryDate < meta.orderDate
  const nextBlocked =
    (activeStep === 'customer' && !customer) ||
    (activeStep === 'main' && (!meta.deliveryDate || meta.totalShirts < 1 || deliveryBeforeOrder)) ||
    // Can't create the order (next step) until every shirt is complete.
    (activeStep === 'subOrders' && !allSubsComplete)
  const isReview = activeStep === 'review'

  // Once the order exists, the steps before the Print Center are locked.
  const floorIndex = locked ? WIZARD_STEPS.indexOf('print') : 0

  async function saveAndPause() {
    if (pausing) return
    setPausing(true)
    try {
      // Saves the draft (paused) AND blanks the wizard, so the next "New Order"
      // starts clean — the paused draft lives in Saved Drafts until resumed.
      await pauseDraft()
      toast.success('Draft saved — resume it any time from Saved Drafts.')
      router.push('/front-desk/drafts')
    } finally {
      setPausing(false)
    }
  }

  async function cancelIntakeOrder() {
    if (!orderId) {
      discardDraft()
      router.push('/front-desk')
      return
    }
    setCancelling(true)
    try {
      // Cancels the intake order and releases its production boxes back to the pool.
      await apiMutate('post', ENDPOINTS.cancelOrder(orderId), { reason: 'Cancelled at Front Desk intake' })
      discardDraft()
      toast.success('Intake order cancelled — production boxes released.')
      router.push('/front-desk')
    } catch (err: unknown) {
      const e = err as { message?: string }
      toast.error(e?.message ?? 'Could not cancel the order.')
    } finally {
      setCancelling(false)
      setCancelOpen(false)
    }
  }

  return (
    <div className="flex flex-col gap-4">
      {/* Wizard-only shortcuts: Ctrl+S = Save & Pause, Ctrl+Enter = Next. */}
      <WizardKeyboardShortcuts
        onSave={saveAndPause}
        onNext={next}
        canSave={!!customer && !pausing && !isConfirmed}
        canNext={!isReview && !nextBlocked && !isConfirmed}
      />

      <PageHeader title="Front Desk · New Order" />

      <div className="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-[var(--color-border)] bg-white px-4 py-3">
        <WizardStepper activeIndex={activeIndex} maxReachedIndex={maxReachedIndex} minIndex={floorIndex} onStepClick={goTo} />
        <DraftStatusIndicator />
      </div>

      {/* Lifecycle banner — the backend order exists but is not yet in production. */}
      {locked && !isConfirmed && (
        <div className="flex flex-wrap items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-xs text-amber-800">
          <span className="rounded-full bg-amber-100 px-2 py-0.5 font-semibold">Intake Preparation</span>
          <span>
            Backend order created for box/PDF preparation. It is <strong>not</strong> in production yet — it is
            saved and resumable, and becomes <strong>Order Received</strong> only on final Confirm.
          </span>
        </div>
      )}

      {/* Active step */}
      <div className="min-h-[360px]">
        {activeStep === 'customer' && <StepCustomer />}
        {activeStep === 'member' && <StepMember />}
        {activeStep === 'main' && <StepMainOrder />}
        {activeStep === 'subOrders' && <StepSubOrders />}
        {activeStep === 'print' && <StepPrintCenter />}
        {activeStep === 'payment' && <StepPayment />}
        {activeStep === 'review' && <StepReview />}
      </div>

      {/* Sticky payment + footer nav — hidden once the order is confirmed. */}
      {!isConfirmed && (
      <div className="sticky bottom-0 z-10 space-y-2 bg-[var(--color-bg)] pt-2 pb-3">
        <PaymentSummaryBar />
        <div className="flex items-center gap-2 rounded-xl border border-[var(--color-border)] bg-white px-4 py-3">
          <button
            type="button"
            onClick={back}
            disabled={activeIndex <= floorIndex}
            className="inline-flex items-center gap-1.5 rounded-lg border border-[var(--color-border-mid)] px-4 h-10 text-sm font-medium text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
          >
            <ArrowLeft size={15} strokeWidth={1.75} /> Back
          </button>

          <button
            type="button"
            onClick={saveAndPause}
            disabled={!customer || pausing}
            title={customer ? undefined : 'Select a customer first'}
            className="inline-flex items-center gap-1.5 rounded-lg border border-[var(--color-border-mid)] px-4 h-10 text-sm font-medium text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
          >
            <PauseCircle size={15} strokeWidth={1.75} /> {pausing ? 'Saving…' : 'Save Draft & Pause'}
            <ShortcutKey keys={['Ctrl', 'S']} muted={!shortcutsEnabled} className="ml-1" />
          </button>

          {locked && (
            <button
              type="button"
              onClick={() => setCancelOpen(true)}
              className="inline-flex items-center gap-1.5 rounded-lg border border-red-200 px-4 h-10 text-sm font-medium text-[var(--color-danger)] hover:bg-red-50 transition-colors"
            >
              <Trash2 size={15} strokeWidth={1.75} /> Cancel Order
            </button>
          )}

          {!isReview && (
            <button
              type="button"
              onClick={next}
              disabled={nextBlocked}
              className="ml-auto inline-flex items-center gap-1.5 rounded-lg bg-[var(--color-brand)] px-5 h-10 text-sm font-semibold text-white hover:bg-[var(--color-brand-dark)] disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
            >
              Next <ArrowRight size={15} strokeWidth={2} />
              <ShortcutKey keys={['Ctrl', 'Enter']} muted={!shortcutsEnabled} className="ml-1 [&_kbd]:bg-white/15 [&_kbd]:border-white/30 [&_kbd]:text-white" />
            </button>
          )}
        </div>
      </div>
      )}

      <ConfirmDialog
        open={cancelOpen}
        onClose={() => setCancelOpen(false)}
        onConfirm={cancelIntakeOrder}
        title="Cancel this order?"
        description="The intake order will be cancelled and its production boxes released. This cannot be undone."
        variant="danger"
        confirmLabel="Cancel Order"
        cancelLabel="Keep Editing"
        loading={cancelling}
      />
    </div>
  )
}

/**
 * Wizard-only keyboard combos. Rendered inside the wizard (so it only runs when
 * the user may create orders). Ctrl/Cmd+S → Save & Pause, Ctrl/Cmd+Enter → Next.
 * Disabled while typing and a no-op unless shortcuts are enabled. We only swallow
 * the browser default (e.g. Save Page) when the action is actually valid.
 */
function WizardKeyboardShortcuts({
  onSave,
  onNext,
  canSave,
  canNext,
}: {
  onSave: () => void
  onNext: () => void
  canSave: boolean
  canNext: boolean
}) {
  const enabled = useShortcutsEnabled()
  const ref = useRef({ onSave, onNext, canSave, canNext })
  ref.current = { onSave, onNext, canSave, canNext }

  useEffect(() => {
    if (!enabled) return

    function onKeyDown(e: KeyboardEvent): void {
      if (e.defaultPrevented || isTypingTarget(e.target)) return
      if (!(e.ctrlKey || e.metaKey) || e.altKey || e.shiftKey) return

      if (e.key === 's' || e.key === 'S') {
        if (!ref.current.canSave) return // not valid → let the browser handle it
        e.preventDefault()
        ref.current.onSave()
      } else if (e.key === 'Enter') {
        if (!ref.current.canNext) return // final review / blocked → do nothing
        e.preventDefault()
        ref.current.onNext()
      }
    }

    window.addEventListener('keydown', onKeyDown)
    return () => window.removeEventListener('keydown', onKeyDown)
  }, [enabled])

  return null
}
