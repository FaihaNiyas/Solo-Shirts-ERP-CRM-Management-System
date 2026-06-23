'use client'

import { useState } from 'react'
import { format } from 'date-fns'
import { AlertTriangle, Scissors, Plus, PackageCheck } from 'lucide-react'
import { toast } from 'sonner'
import { ModalDialog } from '@/components/ui/modal-dialog'
import { FormField } from '@/components/ui/form-field'
import { cn } from '@/lib/utils'
import { usePermission } from '@/lib/auth/permissions'
import { useAllocatableRolls } from '@/lib/api/hooks/useCutting'
import {
  useAllocateProductionFabric,
  useConsumeProductionFabric,
  useReportItemDamage,
} from '@/lib/api/hooks/useProductionFabric'
import type { ProductionWorkbench } from '@/lib/api/hooks/useProductionQueue'

const STAGE_LABELS: Record<string, string> = {
  receiving: 'Receiving',
  cutting: 'Cutting',
  tailoring: 'Tailoring',
  qc: 'QC',
  ironing: 'Ironing',
  packing: 'Packing',
}

const DAMAGE_TYPE_LABELS: Record<string, string> = {
  tear: 'Tear',
  stain: 'Stain',
  color_bleed: 'Colour bleed',
  mis_cut: 'Mis-cut',
  machine_oil: 'Machine oil',
  other: 'Other',
}

const inputCls =
  'w-full h-10 px-3 text-sm border border-[var(--color-border-mid)] rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)]'

const STATUS_BADGE: Record<string, string> = {
  reserved: 'bg-blue-50 text-blue-700',
  consumed: 'bg-green-50 text-green-700',
  pending: 'bg-amber-50 text-amber-800',
  approved: 'bg-green-50 text-green-700',
  rejected: 'bg-red-50 text-red-700',
}

function apiError(err: unknown, fallback: string): string {
  const e = err as { message?: string; errors?: Record<string, string[]> }
  const field = e?.errors ? Object.values(e.errors)[0]?.[0] : undefined
  return field ?? e?.message ?? fallback
}

/**
 * Phase 7B — fabric allocation + cloth damage on the production workbench. The
 * action buttons are gated on fabric.allocate / damage_reports.create, so Front
 * Desk (which has neither) only ever sees the read-only status.
 */
export function FabricDamagePanels({ item }: { item: ProductionWorkbench }) {
  const { can } = usePermission()
  const canAllocate = can('fabric.allocate')
  const canReportDamage = can('damage_reports.create')

  const [modal, setModal] = useState<'allocate' | 'consume' | 'damage' | null>(null)

  const allocation = item.fabric_allocation
  const isReserved = allocation?.status === 'reserved'

  return (
    <>
      <section className="rounded-xl border border-[var(--color-border)] bg-white p-4 space-y-3">
        <div className="flex items-center justify-between">
          <h2 className="flex items-center gap-2 text-sm font-semibold text-[var(--color-text-primary)]">
            <Scissors size={15} strokeWidth={1.75} className="text-[var(--color-brand)]" /> Fabric allocation
          </h2>
          <div className="flex items-center gap-2">
            {canAllocate && !allocation && (
              <button type="button" onClick={() => setModal('allocate')} className="inline-flex items-center gap-1.5 rounded-lg bg-[var(--color-brand)] px-3 h-8 text-xs font-medium text-white hover:bg-[var(--color-brand-dark)]">
                <Plus size={13} strokeWidth={2} /> Allocate fabric
              </button>
            )}
            {canAllocate && isReserved && (
              <button type="button" onClick={() => setModal('consume')} className="inline-flex items-center gap-1.5 rounded-lg border border-[var(--color-border-mid)] px-3 h-8 text-xs font-medium text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)]">
                <PackageCheck size={13} strokeWidth={1.75} /> Mark consumed
              </button>
            )}
          </div>
        </div>

        {!allocation ? (
          <p className="text-xs text-[var(--color-text-muted)]">No fabric reserved for this sub-order yet.</p>
        ) : (
          <div className="grid grid-cols-2 sm:grid-cols-4 gap-2">
            <Cell label="Status">
              <span className={cn('rounded-full px-2 py-0.5 text-[11px] font-medium', STATUS_BADGE[allocation.status] ?? 'bg-gray-100 text-gray-600')}>
                {allocation.status}
              </span>
            </Cell>
            <Cell label="Roll"><span className="ss-mono">{allocation.roll?.roll_code ?? '—'}</span></Cell>
            <Cell label="Reserved (m)">{allocation.reserved_metres}</Cell>
            <Cell label="Consumed (m)">{allocation.consumed_metres ?? '—'}</Cell>
          </div>
        )}
      </section>

      <section className="rounded-xl border border-[var(--color-border)] bg-white p-4 space-y-3">
        <div className="flex items-center justify-between">
          <h2 className="flex items-center gap-2 text-sm font-semibold text-[var(--color-text-primary)]">
            <AlertTriangle size={15} strokeWidth={1.75} className="text-[var(--color-warning)]" /> Cloth damage / waste
          </h2>
          {canReportDamage && (
            <button
              type="button"
              onClick={() => setModal('damage')}
              disabled={!allocation}
              title={!allocation ? 'Allocate fabric before reporting damage' : undefined}
              className="inline-flex items-center gap-1.5 rounded-lg border border-amber-200 px-3 h-8 text-xs font-medium text-[var(--color-warning)] hover:bg-amber-50 disabled:opacity-40 disabled:cursor-not-allowed"
            >
              <Plus size={13} strokeWidth={2} /> Report damage
            </button>
          )}
        </div>

        <div className="grid grid-cols-3 gap-2">
          <Cell label="Reports">{item.cloth_damage.count}</Cell>
          <Cell label="Lost (m)">{item.cloth_damage.total_metres}</Cell>
          <Cell label="Pending">{item.cloth_damage.pending_count}</Cell>
        </div>

        {item.cloth_damage.recent.length > 0 && (
          <ul className="divide-y divide-[var(--color-border)] border-t border-[var(--color-border)]">
            {item.cloth_damage.recent.map((d) => (
              <li key={d.id} className="flex items-center justify-between py-2 text-sm">
                <span className="text-[var(--color-text-secondary)]">
                  {STAGE_LABELS[d.stage] ?? d.stage} · {DAMAGE_TYPE_LABELS[d.damage_type] ?? d.damage_type}
                  <span className="ml-2 text-xs text-[var(--color-text-muted)]">
                    {d.reported_at ? format(new Date(d.reported_at), 'dd MMM') : ''}
                  </span>
                </span>
                <span className="flex items-center gap-2">
                  <span className="tabular-nums text-[var(--color-text-primary)]">{d.quantity_lost_metres} m</span>
                  <span className={cn('rounded-full px-2 py-0.5 text-[11px] font-medium', STATUS_BADGE[d.status] ?? 'bg-gray-100 text-gray-600')}>
                    {d.status}
                  </span>
                </span>
              </li>
            ))}
          </ul>
        )}
        <p className="text-[11px] text-[var(--color-text-muted)]">Damage is reviewed and written off by the owner — reporting here never changes stock.</p>
      </section>

      {modal === 'allocate' && <AllocateModal itemId={item.id} onClose={() => setModal(null)} />}
      {modal === 'consume' && allocation && (
        <ConsumeModal itemId={item.id} reserved={allocation.reserved_metres} onClose={() => setModal(null)} />
      )}
      {modal === 'damage' && (
        <DamageModal itemId={item.id} currentStage={item.current_stage} onClose={() => setModal(null)} />
      )}
    </>
  )
}

function Cell({ label, children }: { label: string; children: React.ReactNode }) {
  return (
    <div className="rounded-lg border border-[var(--color-border)] px-3 py-2">
      <p className="text-[11px] uppercase tracking-wide text-[var(--color-text-muted)]">{label}</p>
      <p className="mt-0.5 text-sm font-semibold text-[var(--color-text-primary)]">{children}</p>
    </div>
  )
}

// ── Modals ────────────────────────────────────────────────────────────────

function AllocateModal({ itemId, onClose }: { itemId: number; onClose: () => void }) {
  const { data: rolls = [], isLoading } = useAllocatableRolls()
  const allocate = useAllocateProductionFabric(itemId)
  const [rollId, setRollId] = useState<number | null>(null)
  const [metres, setMetres] = useState('')

  const selected = rolls.find((r) => r.id === rollId)
  const metresNum = parseFloat(metres) || 0
  const tooMuch = selected ? metresNum > selected.available_metres : false
  const valid = rollId !== null && metresNum > 0 && !tooMuch

  async function submit() {
    if (!valid || rollId === null) return
    try {
      await allocate.mutateAsync({ roll_id: rollId, metres: metresNum })
      toast.success('Fabric reserved.')
      onClose()
    } catch (err) {
      toast.error(apiError(err, 'Could not reserve fabric.'))
    }
  }

  return (
    <ModalDialog
      open
      onClose={onClose}
      title="Allocate fabric"
      description="Reserve metres from a roll for this sub-order."
      footer={
        <>
          <button onClick={onClose} className="rounded-lg border border-[var(--color-border-mid)] px-4 h-9 text-sm font-medium text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)]">Cancel</button>
          <button onClick={submit} disabled={!valid || allocate.isPending} className="rounded-lg bg-[var(--color-brand)] px-4 h-9 text-sm font-semibold text-white hover:bg-[var(--color-brand-dark)] disabled:opacity-40 disabled:cursor-not-allowed">
            {allocate.isPending ? 'Reserving…' : 'Reserve'}
          </button>
        </>
      }
    >
      <div className="space-y-4">
        <FormField label="Fabric roll">
          <select value={rollId ?? ''} onChange={(e) => setRollId(e.target.value ? Number(e.target.value) : null)} className={inputCls} disabled={isLoading}>
            <option value="">{isLoading ? 'Loading rolls…' : 'Select a roll…'}</option>
            {rolls.map((r) => (
              <option key={r.id} value={r.id}>
                {r.roll_code} — {r.available_metres}m available{r.colour ? ` · ${r.colour}` : ''}
              </option>
            ))}
          </select>
        </FormField>
        <FormField label="Metres to reserve" error={tooMuch ? `Only ${selected?.available_metres}m available on this roll.` : undefined}>
          <input type="number" min={0} step="0.01" value={metres} onChange={(e) => setMetres(e.target.value)} className={inputCls} placeholder="0.00" />
        </FormField>
      </div>
    </ModalDialog>
  )
}

function ConsumeModal({ itemId, reserved, onClose }: { itemId: number; reserved: string; onClose: () => void }) {
  const consume = useConsumeProductionFabric(itemId)
  const reservedNum = parseFloat(reserved) || 0
  const [actual, setActual] = useState(reserved)

  const actualNum = parseFloat(actual)
  const tooMuch = !Number.isNaN(actualNum) && actualNum > reservedNum

  async function submit() {
    if (tooMuch) return
    try {
      await consume.mutateAsync(Number.isNaN(actualNum) ? {} : { actual_metres: actualNum })
      toast.success('Fabric marked consumed.')
      onClose()
    } catch (err) {
      toast.error(apiError(err, 'Could not mark consumed.'))
    }
  }

  return (
    <ModalDialog
      open
      onClose={onClose}
      title="Mark fabric consumed"
      description={`Record actual usage. ${reserved}m is reserved; any unused tail is released back to stock.`}
      footer={
        <>
          <button onClick={onClose} className="rounded-lg border border-[var(--color-border-mid)] px-4 h-9 text-sm font-medium text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)]">Cancel</button>
          <button onClick={submit} disabled={tooMuch || consume.isPending} className="rounded-lg bg-[var(--color-brand)] px-4 h-9 text-sm font-semibold text-white hover:bg-[var(--color-brand-dark)] disabled:opacity-40 disabled:cursor-not-allowed">
            {consume.isPending ? 'Saving…' : 'Mark consumed'}
          </button>
        </>
      }
    >
      <FormField label="Actual metres used" error={tooMuch ? `Cannot exceed the ${reserved}m reserved.` : undefined}>
        <input type="number" min={0} step="0.01" max={reservedNum} value={actual} onChange={(e) => setActual(e.target.value)} className={inputCls} />
      </FormField>
    </ModalDialog>
  )
}

function DamageModal({ itemId, currentStage, onClose }: { itemId: number; currentStage: string; onClose: () => void }) {
  const report = useReportItemDamage(itemId)
  const defaultStage = STAGE_LABELS[currentStage] ? currentStage : 'cutting'
  const [stage, setStage] = useState(defaultStage)
  const [type, setType] = useState('tear')
  const [other, setOther] = useState('')
  const [metres, setMetres] = useState('')
  const [action, setAction] = useState('')

  const metresNum = parseFloat(metres) || 0
  const needsOther = type === 'other'
  const valid = metresNum > 0 && (!needsOther || other.trim().length > 0)

  async function submit() {
    if (!valid) return
    try {
      await report.mutateAsync({
        stage,
        damage_type: type,
        damage_type_other: needsOther ? other.trim() : undefined,
        quantity_lost_metres: metresNum,
        action_taken: action.trim() || undefined,
      })
      toast.success('Cloth damage reported.')
      onClose()
    } catch (err) {
      toast.error(apiError(err, 'Could not report damage.'))
    }
  }

  return (
    <ModalDialog
      open
      onClose={onClose}
      title="Report cloth damage"
      description="Records fabric lost during production for owner review. This does not change stock."
      footer={
        <>
          <button onClick={onClose} className="rounded-lg border border-[var(--color-border-mid)] px-4 h-9 text-sm font-medium text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)]">Cancel</button>
          <button onClick={submit} disabled={!valid || report.isPending} className="rounded-lg bg-[var(--color-warning)] px-4 h-9 text-sm font-semibold text-white hover:opacity-90 disabled:opacity-40 disabled:cursor-not-allowed">
            {report.isPending ? 'Saving…' : 'Report damage'}
          </button>
        </>
      }
    >
      <div className="space-y-4">
        <div className="grid grid-cols-2 gap-3">
          <FormField label="Stage">
            <select value={stage} onChange={(e) => setStage(e.target.value)} className={inputCls}>
              {Object.entries(STAGE_LABELS).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
            </select>
          </FormField>
          <FormField label="Damage type">
            <select value={type} onChange={(e) => setType(e.target.value)} className={inputCls}>
              {Object.entries(DAMAGE_TYPE_LABELS).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
            </select>
          </FormField>
        </div>
        {needsOther && (
          <FormField label="Describe the damage">
            <input value={other} onChange={(e) => setOther(e.target.value)} className={inputCls} maxLength={100} placeholder="e.g. burn mark" />
          </FormField>
        )}
        <FormField label="Metres lost">
          <input type="number" min={0} step="0.01" value={metres} onChange={(e) => setMetres(e.target.value)} className={inputCls} placeholder="0.00" />
        </FormField>
        <FormField label="Action taken (optional)">
          <input value={action} onChange={(e) => setAction(e.target.value)} className={inputCls} maxLength={255} placeholder="e.g. segregated for re-cut" />
        </FormField>
      </div>
    </ModalDialog>
  )
}
