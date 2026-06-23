'use client'

import { useState } from 'react'
import { format } from 'date-fns'
import { AlertTriangle, CheckCircle2, ClipboardCheck, RotateCcw, XCircle } from 'lucide-react'
import { toast } from 'sonner'
import { ModalDialog } from '@/components/ui/modal-dialog'
import { FormField } from '@/components/ui/form-field'
import { cn } from '@/lib/utils'
import { usePermission } from '@/lib/auth/permissions'
import { productionStateLabel } from '@/lib/api/hooks/useFrontDeskLookup'
import { useQcPass, useQcFail } from '@/lib/api/hooks/useProductionQc'
import type { ProductionWorkbench, QcInspectionView } from '@/lib/api/hooks/useProductionQueue'

const FAILURE_REASON_LABELS: Record<string, string> = {
  measurement_mismatch: 'Measurement mismatch',
  stitching_issue: 'Stitching issue',
  fabric_damage: 'Fabric damage',
  stain: 'Stain',
  button_issue: 'Button issue',
  finishing_issue: 'Finishing issue',
  wrong_style: 'Wrong style',
  other: 'Other',
}

const REWORK_TARGETS = ['cutting', 'tailoring', 'kaja_button', 'finishing']

const inputCls =
  'w-full h-10 px-3 text-sm border border-[var(--color-border-mid)] rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)]'

function apiError(err: unknown, fallback: string): string {
  const e = err as { message?: string; errors?: Record<string, string[]> }
  const field = e?.errors ? Object.values(e.errors)[0]?.[0] : undefined
  return field ?? e?.message ?? fallback
}

function failureLabel(reason: string | null): string {
  return reason ? (FAILURE_REASON_LABELS[reason] ?? reason) : '—'
}

/**
 * Phase 7C — Quality Control on the production workbench. Pass/Fail are gated on
 * qc.inspect, so Front Desk never sees them. A QC fail starts INTERNAL production
 * rework (before delivery) — deliberately worded and styled apart from a customer
 * alteration (which is a separate, post-delivery flow and never appears here).
 */
export function QcPanel({ item }: { item: ProductionWorkbench }) {
  const { can } = usePermission()
  const canInspect = can('qc.inspect')
  const qc = item.qc
  const [modal, setModal] = useState<'pass' | 'fail' | null>(null)

  return (
    <>
      {qc.in_rework && qc.rework && (
        <div className="rounded-xl border border-orange-300 bg-orange-50 px-4 py-3">
          <div className="flex items-center gap-2 text-sm font-semibold text-orange-800">
            <RotateCcw size={15} strokeWidth={1.75} /> Internal QC Rework
          </div>
          <p className="mt-1 text-xs text-orange-700">
            This is production rework before delivery. It does <strong>not</strong> create a customer alteration.
          </p>
          <div className="mt-2 grid grid-cols-2 sm:grid-cols-3 gap-2">
            <MiniCell label="Reason">{failureLabel(qc.rework.failure_reason)}</MiniCell>
            <MiniCell label="Send back to">{qc.rework.target_stage ? productionStateLabel(qc.rework.target_stage) : '—'}</MiniCell>
            {qc.rework.notes && <MiniCell label="QC notes">{qc.rework.notes}</MiniCell>}
          </div>
          {qc.rework.failure_reason === 'fabric_damage' && (
            <p className="mt-2 text-[11px] text-orange-700">Related fabric damage is shown in the Cloth damage panel above.</p>
          )}
        </div>
      )}

      <section className="rounded-xl border border-[var(--color-border)] bg-white p-4 space-y-3">
        <div className="flex items-center justify-between">
          <h2 className="flex items-center gap-2 text-sm font-semibold text-[var(--color-text-primary)]">
            <ClipboardCheck size={15} strokeWidth={1.75} className="text-[var(--color-brand)]" /> Quality control
          </h2>
          {canInspect && qc.can_inspect && (
            <div className="flex items-center gap-2">
              <button type="button" onClick={() => setModal('pass')} className="inline-flex items-center gap-1.5 rounded-lg bg-[var(--color-success)] px-3 h-8 text-xs font-medium text-white hover:opacity-90">
                <CheckCircle2 size={13} strokeWidth={2} /> Pass QC
              </button>
              <button type="button" onClick={() => setModal('fail')} className="inline-flex items-center gap-1.5 rounded-lg border border-red-200 px-3 h-8 text-xs font-medium text-[var(--color-danger)] hover:bg-red-50">
                <XCircle size={13} strokeWidth={2} /> Fail QC
              </button>
            </div>
          )}
        </div>

        <div className="flex items-center gap-2 text-sm">
          <span className="text-[var(--color-text-muted)]">Latest result:</span>
          {qc.latest ? <ResultChip result={qc.latest.result} /> : <span className="text-[var(--color-text-muted)]">Not inspected yet</span>}
          {qc.attempts > 0 && <span className="text-xs text-[var(--color-text-muted)]">· {qc.attempts} attempt{qc.attempts > 1 ? 's' : ''}</span>}
        </div>

        {qc.history.length > 0 && (
          <ol className="divide-y divide-[var(--color-border)] border-t border-[var(--color-border)]">
            {qc.history.map((h) => <HistoryRow key={h.id} h={h} />)}
          </ol>
        )}

        <p className="text-[11px] text-[var(--color-text-muted)]">
          QC failures route to internal production rework only — separate from post-delivery customer alterations.
        </p>
      </section>

      {modal === 'pass' && <PassModal itemId={item.id} onClose={() => setModal(null)} />}
      {modal === 'fail' && <FailModal itemId={item.id} currentStage={item.current_stage} onClose={() => setModal(null)} />}
    </>
  )
}

function ResultChip({ result }: { result: 'passed' | 'failed' }) {
  return (
    <span className={cn('rounded-full px-2 py-0.5 text-[11px] font-medium', result === 'passed' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700')}>
      {result === 'passed' ? 'Passed' : 'Failed'}
    </span>
  )
}

function HistoryRow({ h }: { h: QcInspectionView }) {
  return (
    <li className="flex items-center justify-between py-2 text-sm">
      <span className="min-w-0 text-[var(--color-text-secondary)]">
        <span className="ss-mono text-xs text-[var(--color-text-muted)]">#{h.attempt_number}</span>{' '}
        {h.result === 'failed' ? failureLabel(h.failure_reason) : 'Pass'}
        {h.rework_target_stage && <span className="text-xs text-[var(--color-text-muted)]"> → {productionStateLabel(h.rework_target_stage)}</span>}
        {h.notes && <span className="ml-1 text-xs text-[var(--color-text-muted)]">· {h.notes}</span>}
      </span>
      <span className="flex shrink-0 items-center gap-2">
        <ResultChip result={h.result} />
        <span className="text-xs text-[var(--color-text-muted)]">{h.inspected_at ? format(new Date(h.inspected_at), 'dd MMM') : ''}</span>
      </span>
    </li>
  )
}

function MiniCell({ label, children }: { label: string; children: React.ReactNode }) {
  return (
    <div>
      <p className="text-[10px] uppercase tracking-wide text-orange-700/70">{label}</p>
      <p className="text-xs font-medium text-orange-900">{children}</p>
    </div>
  )
}

// ── Modals ────────────────────────────────────────────────────────────────

function PassModal({ itemId, onClose }: { itemId: number; onClose: () => void }) {
  const pass = useQcPass(itemId)
  const [notes, setNotes] = useState('')

  async function submit() {
    try {
      await pass.mutateAsync(notes.trim() ? { notes: notes.trim() } : {})
      toast.success('QC passed — item moved to packing.')
      onClose()
    } catch (err) {
      toast.error(apiError(err, 'Could not pass QC.'))
    }
  }

  return (
    <ModalDialog
      open
      onClose={onClose}
      title="Pass QC"
      description="Confirm this item passed inspection. It will move to packing."
      footer={
        <>
          <button onClick={onClose} className="rounded-lg border border-[var(--color-border-mid)] px-4 h-9 text-sm font-medium text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)]">Cancel</button>
          <button onClick={submit} disabled={pass.isPending} className="rounded-lg bg-[var(--color-success)] px-4 h-9 text-sm font-semibold text-white hover:opacity-90 disabled:opacity-40">
            {pass.isPending ? 'Saving…' : 'Pass QC'}
          </button>
        </>
      }
    >
      <FormField label="Notes (optional)">
        <input value={notes} onChange={(e) => setNotes(e.target.value)} className={inputCls} maxLength={2000} placeholder="e.g. clean finish" />
      </FormField>
    </ModalDialog>
  )
}

function FailModal({ itemId, currentStage, onClose }: { itemId: number; currentStage: string; onClose: () => void }) {
  const fail = useQcFail(itemId)
  const [reason, setReason] = useState('stitching_issue')
  const [target, setTarget] = useState('tailoring')
  const [notes, setNotes] = useState('')

  async function submit() {
    try {
      await fail.mutateAsync({
        failure_reason: reason,
        rework_target_stage: target,
        notes: notes.trim() || undefined,
      })
      toast.success('QC failed — item sent to internal rework.')
      onClose()
    } catch (err) {
      toast.error(apiError(err, 'Could not fail QC.'))
    }
  }

  return (
    <ModalDialog
      open
      onClose={onClose}
      title="Fail QC"
      description="Sends the item to internal production rework — not a customer alteration."
      footer={
        <>
          <button onClick={onClose} className="rounded-lg border border-[var(--color-border-mid)] px-4 h-9 text-sm font-medium text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)]">Cancel</button>
          <button onClick={submit} disabled={fail.isPending} className="rounded-lg bg-[var(--color-danger)] px-4 h-9 text-sm font-semibold text-white hover:opacity-90 disabled:opacity-40">
            {fail.isPending ? 'Saving…' : 'Fail & send to rework'}
          </button>
        </>
      }
    >
      <div className="space-y-4">
        <div className="rounded-lg bg-orange-50 border border-orange-200 px-3 py-2 text-[11px] text-orange-700">
          This is internal production rework before delivery. It does not create a customer alteration.
        </div>
        {currentStage !== 'qc' && (
          <p className="text-xs text-[var(--color-warning)]">This item is not currently in QC.</p>
        )}
        <div className="grid grid-cols-2 gap-3">
          <FormField label="Failure reason">
            <select value={reason} onChange={(e) => setReason(e.target.value)} className={inputCls}>
              {Object.entries(FAILURE_REASON_LABELS).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
            </select>
          </FormField>
          <FormField label="Send back to">
            <select value={target} onChange={(e) => setTarget(e.target.value)} className={inputCls}>
              {REWORK_TARGETS.map((s) => <option key={s} value={s}>{productionStateLabel(s)}</option>)}
            </select>
          </FormField>
        </div>
        <FormField label="Notes (optional)">
          <input value={notes} onChange={(e) => setNotes(e.target.value)} className={inputCls} maxLength={2000} placeholder="e.g. sleeve seam not straight" />
        </FormField>
      </div>
    </ModalDialog>
  )
}
