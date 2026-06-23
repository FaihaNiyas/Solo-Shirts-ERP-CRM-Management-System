'use client'

import { useState } from 'react'
import { format } from 'date-fns'
import { Box, FileText, Info, MapPin, Package, PackageCheck } from 'lucide-react'
import { toast } from 'sonner'
import { apiGet } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { cn } from '@/lib/utils'
import { usePermission } from '@/lib/auth/permissions'
import { useSavePackingChecklist, useMarkPacked, type PackingChecklistInput } from '@/lib/api/hooks/useProductionPacking'
import type { ProductionWorkbench } from '@/lib/api/hooks/useProductionQueue'

const CHECK_FIELDS = [
  { key: 'checked_measurement_card', label: 'Measurement card enclosed' },
  { key: 'checked_buttons', label: 'Buttons / accessories' },
  { key: 'checked_ironing', label: 'Ironing done' },
  { key: 'checked_folded', label: 'Folded' },
  { key: 'checked_packing_cover', label: 'Packing cover' },
  { key: 'checked_label', label: 'Label attached' },
] as const

type CheckKey = (typeof CHECK_FIELDS)[number]['key']

function apiError(err: unknown, fallback: string): string {
  const e = err as { message?: string; errors?: Record<string, string[]> }
  const field = e?.errors ? Object.values(e.errors)[0]?.[0] : undefined
  return field ?? e?.message ?? fallback
}

/**
 * Phase 7D — final packing on the production workbench. Checklist + Mark Packed
 * are gated on production.packing.manage (Front Desk cannot pack — they own
 * handover). Marking packed promotes the item to the ready rack; it never marks
 * delivered and never touches the balance.
 */
export function PackingPanel({ item }: { item: ProductionWorkbench }) {
  const { can } = usePermission()
  const canPack = can('production.packing.manage')
  const p = item.packing

  const save = useSavePackingChecklist(item.id)
  const markPacked = useMarkPacked(item.id)

  const initial = CHECK_FIELDS.reduce((acc, f) => {
    acc[f.key] = Boolean(p.checklist?.[f.key])
    return acc
  }, {} as Record<CheckKey, boolean>)
  const [checks, setChecks] = useState<Record<CheckKey, boolean>>(initial)
  const [notes, setNotes] = useState(p.checklist?.notes ?? '')

  const editable = canPack && p.is_packing
  const allChecked = CHECK_FIELDS.every((f) => checks[f.key])

  async function handleSave() {
    try {
      await save.mutateAsync({ ...checks, notes } as PackingChecklistInput)
      toast.success('Packing checklist saved.')
    } catch (err) {
      toast.error(apiError(err, 'Could not save the checklist.'))
    }
  }

  async function handleMarkPacked() {
    try {
      await save.mutateAsync({ ...checks, notes } as PackingChecklistInput)
      await markPacked.mutateAsync()
      toast.success('Packed and moved to the ready rack.')
    } catch (err) {
      toast.error(apiError(err, 'Could not mark packed.'))
    }
  }

  const busy = save.isPending || markPacked.isPending

  return (
    <section className="rounded-xl border border-[var(--color-border)] bg-white p-4 space-y-3">
      <div className="flex items-center justify-between">
        <h2 className="flex items-center gap-2 text-sm font-semibold text-[var(--color-text-primary)]">
          <Package size={15} strokeWidth={1.75} className="text-[var(--color-brand)]" /> Final packing
        </h2>
        {p.rack_slot && (
          <span className="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700">
            <MapPin size={12} strokeWidth={1.75} /> Ready Rack {p.rack_slot.slot_code}
          </span>
        )}
      </div>

      {/* Concept separation — Production Box vs Ready Rack. */}
      <div className="grid grid-cols-2 gap-2 text-[11px]">
        <div className="flex items-start gap-1.5 rounded-lg bg-[var(--color-surface-alt)] px-2.5 py-2 text-[var(--color-text-muted)]">
          <Box size={13} strokeWidth={1.75} className="mt-0.5 shrink-0" />
          <span><strong>Production Box</strong> — material / job-card box{item.production_box_code ? `: ${item.production_box_code}` : ''}</span>
        </div>
        <div className="flex items-start gap-1.5 rounded-lg bg-[var(--color-surface-alt)] px-2.5 py-2 text-[var(--color-text-muted)]">
          <MapPin size={13} strokeWidth={1.75} className="mt-0.5 shrink-0" />
          <span><strong>Ready Rack</strong> — finished packed item pickup location{p.rack_slot ? `: ${p.rack_slot.slot_code}` : ''}</span>
        </div>
      </div>

      {!p.is_packing && !p.is_ready && !p.is_delivered ? (
        <p className="text-xs text-[var(--color-text-muted)]">Packing becomes available once the item passes QC.</p>
      ) : (
        <>
          <ul className="space-y-1.5">
            {CHECK_FIELDS.map((f) => (
              <li key={f.key}>
                <label className={cn('flex items-center gap-2 text-sm', editable ? 'cursor-pointer' : 'cursor-default')}>
                  <input
                    type="checkbox"
                    checked={p.is_packing ? checks[f.key] : Boolean(p.checklist?.[f.key])}
                    disabled={!editable || busy}
                    onChange={(e) => setChecks((c) => ({ ...c, [f.key]: e.target.checked }))}
                    className="h-4 w-4 rounded border-[var(--color-border-mid)] text-[var(--color-brand)] focus:ring-[var(--color-brand)]"
                  />
                  <span className="text-[var(--color-text-secondary)]">{f.label}</span>
                </label>
              </li>
            ))}
          </ul>

          {editable && (
            <div className="flex items-center gap-2 pt-1">
              <button type="button" onClick={handleSave} disabled={busy} className="rounded-lg border border-[var(--color-border-mid)] px-3 h-9 text-sm font-medium text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] disabled:opacity-40">
                Save checklist
              </button>
              <button type="button" onClick={handleMarkPacked} disabled={busy || !allChecked} title={!allChecked ? 'Tick every checklist item first' : undefined} className="inline-flex items-center gap-1.5 rounded-lg bg-[var(--color-brand)] px-3 h-9 text-sm font-semibold text-white hover:bg-[var(--color-brand-dark)] disabled:opacity-40 disabled:cursor-not-allowed">
                <PackageCheck size={14} strokeWidth={2} /> Mark Packed &amp; send to rack
              </button>
            </div>
          )}

          {(p.is_ready || p.is_delivered) && p.checklist?.packed_at && (
            <p className="text-xs text-[var(--color-text-muted)]">
              Packed{p.checklist.packed_by_name ? ` by ${p.checklist.packed_by_name}` : ''} on {format(new Date(p.checklist.packed_at), 'dd MMM yyyy, HH:mm')}.
            </p>
          )}

          <button type="button" onClick={() => openPackingSlip(item.id)} className="inline-flex items-center gap-1.5 rounded-lg border border-[var(--color-border-mid)] px-3 h-8 text-xs font-medium text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)]">
            <FileText size={13} strokeWidth={1.75} /> Packing slip
          </button>
        </>
      )}

      <div className="flex items-start gap-2 rounded-lg bg-blue-50 border border-blue-100 px-3 py-2 text-[11px] text-blue-700">
        <Info size={13} strokeWidth={1.75} className="mt-0.5 shrink-0" />
        Balance is checked during handover. Packing does not mark the item delivered.
      </div>
    </section>
  )
}

async function openPackingSlip(itemId: number) {
  try {
    const res = await apiGet<{ download_url?: string }>(ENDPOINTS.packingSlip(itemId))
    const url = res.data?.download_url
    if (url) window.open(url, '_blank')
    else toast.error('Packing slip not available yet.')
  } catch {
    toast.error('Could not open the packing slip.')
  }
}
