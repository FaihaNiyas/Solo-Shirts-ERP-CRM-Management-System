'use client'

import { use, useState } from 'react'
import { format } from 'date-fns'
import { AlertTriangle } from 'lucide-react'
import { toast } from 'sonner'
import { useFabricRoll, useInventoryMovements, useAdjustFabricRoll, useSetRollThreshold } from '@/lib/api/hooks/useInventory'
import { PageHeader } from '@/components/ui/page-header'
import { InfoGrid } from '@/components/ui/info-grid'
import { StatusBadge } from '@/components/ui/status-badge'
import { DrawerPanel } from '@/components/ui/drawer-panel'
import { FormField } from '@/components/ui/form-field'
import { TableSkeleton } from '@/components/ui/loading-skeleton'
import { usePermission } from '@/lib/auth/permissions'
import { cn } from '@/lib/utils'

function m(v: number | string | undefined | null): string {
  return v === undefined || v === null ? '—' : `${v}m`
}

const DIRECTION_STYLE: Record<string, string> = {
  in: 'text-green-700',
  out: 'text-red-600',
  hold: 'text-[var(--color-brand)]',
}

export default function FabricRollDetailPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = use(params)
  const rollId = parseInt(id)
  const { can } = usePermission()
  const canAdjust = can('inventory.fabric_rolls.adjust')

  const { data: roll, isLoading } = useFabricRoll(rollId)
  const { data: movements = [] } = useInventoryMovements(rollId)
  const adjustMutation = useAdjustFabricRoll(rollId)
  const thresholdMutation = useSetRollThreshold(rollId)

  const [showAdjust, setShowAdjust] = useState(false)
  const [adjustForm, setAdjustForm] = useState({ type: 'adjust_in' as 'adjust_in' | 'adjust_out', metres: '', reason: '' })
  const [threshold, setThreshold] = useState('')

  async function handleAdjust() {
    try {
      await adjustMutation.mutateAsync({ type: adjustForm.type, metres: parseFloat(adjustForm.metres), reason: adjustForm.reason })
      setShowAdjust(false)
      setAdjustForm({ type: 'adjust_in', metres: '', reason: '' })
      toast.success('Fabric roll adjusted')
    } catch (err: unknown) {
      toast.error((err as { message?: string })?.message ?? 'Failed')
    }
  }

  async function saveThreshold(value: number | null) {
    try {
      await thresholdMutation.mutateAsync(value)
      setThreshold('')
      toast.success(value === null ? 'Threshold cleared' : 'Low-stock threshold set')
    } catch (err: unknown) {
      toast.error((err as { message?: string })?.message ?? 'Failed')
    }
  }

  if (isLoading) return <TableSkeleton rows={4} cols={4} />
  if (!roll) return <p className="text-sm text-[var(--color-text-muted)]">Roll not found</p>

  return (
    <div className="space-y-6">
      <PageHeader
        title={roll.roll_code ?? `Roll #${rollId}`}
        actions={
          <div className="flex items-center gap-2">
            {roll.low_stock && (
              <span className="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-[var(--color-warning)]">
                <AlertTriangle size={13} strokeWidth={1.75} /> Low stock
              </span>
            )}
            {canAdjust && (
              <button onClick={() => setShowAdjust(true)} className="px-4 py-2 text-sm border border-[var(--color-border)] rounded-lg text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] transition-colors">
                Adjust
              </button>
            )}
          </div>
        }
      />

      {/* Stock breakdown */}
      <div className="grid grid-cols-2 sm:grid-cols-5 gap-3">
        <StockCard label="Remaining" value={m(roll.remaining_metres)} />
        <StockCard label="Reserved" value={m(roll.reserved_metres)} tone="brand" />
        <StockCard label="Consumed" value={m(roll.consumed_metres)} tone="muted" />
        <StockCard label="Damaged" value={m(roll.damaged_metres)} tone="danger" />
        <StockCard label="Available" value={m(roll.available_metres)} tone="success" />
      </div>

      <InfoGrid
        items={[
          { label: 'Roll Code', value: <span className="font-mono">{roll.roll_code ?? '—'}</span> },
          { label: 'Colour', value: roll.colour ?? '—' },
          { label: 'Received', value: m(roll.received_length_metres) },
          { label: 'Rack location', value: roll.rack_location ?? '—' },
          { label: 'Reorder threshold', value: roll.low_stock_threshold_metres ? m(roll.low_stock_threshold_metres) : 'Not set' },
          { label: 'Status', value: <StatusBadge status={roll.status ?? 'active'} /> },
        ]}
      />

      {/* Per-roll reorder threshold */}
      {canAdjust && (
        <section className="rounded-xl border border-[var(--color-border)] bg-white p-4">
          <h2 className="text-sm font-semibold text-[var(--color-text-primary)] mb-1">Low-stock threshold</h2>
          <p className="text-xs text-[var(--color-text-muted)] mb-3">Flag this roll when its remaining metres fall below the reorder level.</p>
          <div className="flex items-end gap-2">
            <FormField label="Threshold (m)">
              <input
                type="number" min={0} step="0.01"
                value={threshold}
                onChange={(e) => setThreshold(e.target.value)}
                placeholder={roll.low_stock_threshold_metres ? String(roll.low_stock_threshold_metres) : 'e.g. 10'}
                className="w-40 h-9 px-3 text-sm font-mono border border-[var(--color-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)]"
              />
            </FormField>
            <button
              onClick={() => saveThreshold(parseFloat(threshold))}
              disabled={!threshold || thresholdMutation.isPending}
              className="h-9 rounded-lg bg-[var(--color-brand)] px-4 text-sm font-semibold text-white hover:bg-[var(--color-brand-dark)] disabled:opacity-40"
            >
              Save
            </button>
            {roll.low_stock_threshold_metres && (
              <button
                onClick={() => saveThreshold(null)}
                disabled={thresholdMutation.isPending}
                className="h-9 rounded-lg border border-[var(--color-border-mid)] px-4 text-sm font-medium text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] disabled:opacity-40"
              >
                Clear
              </button>
            )}
          </div>
        </section>
      )}

      {/* Stock ledger */}
      <section className="rounded-xl border border-[var(--color-border)] overflow-hidden">
        <div className="px-4 py-3 bg-[var(--color-surface-alt)] border-b border-[var(--color-border)]">
          <p className="text-xs font-semibold text-[var(--color-text-muted)] uppercase tracking-wide">Stock ledger</p>
        </div>
        {movements.length === 0 ? (
          <p className="px-4 py-6 text-center text-sm text-[var(--color-text-muted)]">No movements yet.</p>
        ) : (
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-[var(--color-border)] text-left text-[11px] uppercase tracking-wide text-[var(--color-text-muted)]">
                <th className="px-4 py-2 font-medium">Date</th>
                <th className="px-4 py-2 font-medium">Type</th>
                <th className="px-4 py-2 font-medium text-right">Metres</th>
                <th className="px-4 py-2 font-medium">Reason</th>
              </tr>
            </thead>
            <tbody>
              {movements.map((mv) => (
                <tr key={mv.id} className="border-b border-[var(--color-border)] last:border-0">
                  <td className="px-4 py-2 text-[var(--color-text-muted)]">{mv.occurred_at ? format(new Date(mv.occurred_at), 'dd MMM yyyy, HH:mm') : '—'}</td>
                  <td className="px-4 py-2">
                    <span className={cn('font-medium', DIRECTION_STYLE[mv.direction ?? 'hold'] ?? '')}>{(mv.type ?? '').replace(/_/g, ' ')}</span>
                  </td>
                  <td className={cn('px-4 py-2 text-right font-mono', DIRECTION_STYLE[mv.direction ?? 'hold'] ?? '')}>
                    {mv.direction === 'in' ? '+' : mv.direction === 'out' ? '−' : ''}{mv.metres ?? 0}m
                  </td>
                  <td className="px-4 py-2 text-[var(--color-text-secondary)]">{mv.reason ?? '—'}</td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </section>

      <DrawerPanel open={showAdjust} onClose={() => setShowAdjust(false)} title="Adjust Fabric Roll" size="sm">
        <div className="space-y-4 p-4">
          <FormField label="Type">
            <select
              value={adjustForm.type}
              onChange={(e) => setAdjustForm((p) => ({ ...p, type: e.target.value as 'adjust_in' | 'adjust_out' }))}
              className="w-full h-9 px-3 text-sm border border-[var(--color-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)] bg-white"
            >
              <option value="adjust_in">Adjust In (+)</option>
              <option value="adjust_out">Adjust Out (−)</option>
            </select>
          </FormField>
          <FormField label="Metres" required>
            <input
              type="number" step="0.1" min="0"
              value={adjustForm.metres}
              onChange={(e) => setAdjustForm((p) => ({ ...p, metres: e.target.value }))}
              className="w-full h-9 px-3 text-sm font-mono border border-[var(--color-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)]"
              placeholder="0.0"
            />
          </FormField>
          <FormField label="Reason" required>
            <textarea
              value={adjustForm.reason}
              onChange={(e) => setAdjustForm((p) => ({ ...p, reason: e.target.value }))}
              rows={2}
              className="w-full px-3 py-2 text-sm border border-[var(--color-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)] resize-none"
              placeholder="Reason for adjustment (min 10 chars for adjust-out)"
            />
          </FormField>
          <div className="flex gap-2">
            <button
              onClick={handleAdjust}
              disabled={!adjustForm.metres || !adjustForm.reason || adjustMutation.isPending}
              className="flex-1 py-2 bg-[var(--color-brand)] text-white text-sm font-medium rounded-lg hover:bg-[var(--color-brand-dark)] disabled:opacity-50 transition-colors"
            >
              {adjustMutation.isPending ? 'Adjusting…' : 'Confirm Adjustment'}
            </button>
            <button onClick={() => setShowAdjust(false)} className="px-4 py-2 border border-[var(--color-border)] text-sm rounded-lg text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] transition-colors">
              Cancel
            </button>
          </div>
        </div>
      </DrawerPanel>
    </div>
  )
}

function StockCard({ label, value, tone = 'default' }: { label: string; value: string; tone?: 'default' | 'brand' | 'success' | 'danger' | 'muted' }) {
  const styles: Record<string, string> = {
    default: 'border-[var(--color-border)] bg-white text-[var(--color-text-primary)]',
    brand: 'border-[var(--color-brand)] bg-[var(--color-brand-light)] text-[var(--color-brand)]',
    success: 'border-green-200 bg-green-50 text-green-700',
    danger: 'border-red-200 bg-red-50 text-red-600',
    muted: 'border-[var(--color-border)] bg-[var(--color-surface-alt)] text-[var(--color-text-secondary)]',
  }
  return (
    <div className={cn('rounded-xl border p-3', styles[tone])}>
      <p className="text-[11px] uppercase tracking-wide opacity-80 mb-1">{label}</p>
      <p className="text-xl font-mono font-semibold">{value}</p>
    </div>
  )
}
