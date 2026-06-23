'use client'

import { useState } from 'react'
import { toast } from 'sonner'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { apiGet, apiMutate, generateIdempotencyKey } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { queryKeys } from '@/lib/query/keys'
import { useProductionBoard } from '@/lib/api/hooks/useProduction'
import { usePermission } from '@/lib/auth/permissions'
import { PageHeader } from '@/components/ui/page-header'
import { DrawerPanel } from '@/components/ui/drawer-panel'
import { FormField } from '@/components/ui/form-field'
import { ProductionBadge } from '@/components/ui/status-badge'
import { TableSkeleton } from '@/components/ui/loading-skeleton'
import { EmptyState } from '@/components/ui/empty-state'
import type { ProductionItem } from '@/lib/api/schemas/production'

interface DefectCategory { id: number; name: string; severity?: string }

export default function QCPage() {
  const { data: board, isLoading } = useProductionBoard()
  const qcItems: ProductionItem[] = board?.columns?.['QC'] ?? []

  const { data: defectCats = [] } = useQuery<DefectCategory[]>({
    queryKey: queryKeys.defectCategories(),
    queryFn: () => apiGet<DefectCategory[]>(ENDPOINTS.defectCategories).then((r) => r.data),
  })

  const [inspecting, setInspecting] = useState<ProductionItem | null>(null)
  const [disposition, setDisposition] = useState<'pass' | 'rework' | 'reject'>('pass')
  const [selectedDefects, setSelectedDefects] = useState<number[]>([])
  const [notes, setNotes] = useState('')
  const [submitting, setSubmitting] = useState(false)

  const { can } = usePermission()
  const qc = useQueryClient()

  async function handleInspect() {
    if (!inspecting) return
    setSubmitting(true)
    try {
      await apiMutate(
        'post',
        ENDPOINTS.inspectItem(inspecting.id),
        { disposition, defect_codes: selectedDefects, notes },
        generateIdempotencyKey(),
      )
      qc.invalidateQueries({ queryKey: queryKeys.productionBoard() })
      setInspecting(null)
      setSelectedDefects([])
      setNotes('')
      setDisposition('pass')
      toast.success(disposition === 'pass' ? 'QC passed!' : `Sent to ${disposition}`)
    } catch (err: unknown) {
      toast.error((err as { message?: string })?.message ?? 'Inspection failed')
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title="Quality Control"
        subtitle={`${qcItems.length} items pending`}
      />

      {isLoading && <TableSkeleton rows={4} cols={4} />}

      {!isLoading && qcItems.length === 0 && (
        <EmptyState title="No items in QC" description="All items have been inspected" />
      )}

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        {qcItems.map((item) => (
          <div
            key={item.id}
            className="rounded-xl border border-[var(--color-border)] bg-white p-4 space-y-3"
          >
            <div className="flex items-start justify-between">
              <div>
                <p className="text-xs font-mono font-semibold text-[var(--color-brand)]">
                  {item.order_number ?? `#${item.id}`}
                </p>
                <p className="text-sm font-medium text-[var(--color-text-primary)]">
                  {item.customer_name ?? '—'}
                </p>
                <p className="text-xs text-[var(--color-text-muted)]">{item.garment_type}</p>
              </div>
              {(item.rework_count ?? 0) > 0 && (
                <span className="px-2 py-0.5 text-xs font-medium bg-[var(--color-brand-light)] text-[var(--color-brand)] rounded-full">
                  Rework #{item.rework_count}
                </span>
              )}
            </div>
            <button
              onClick={() => setInspecting(item)}
              className="w-full py-2 bg-[var(--color-brand)] text-white text-sm font-medium rounded-lg hover:bg-[var(--color-brand-dark)] transition-colors"
            >
              Start Inspection
            </button>
          </div>
        ))}
      </div>

      <DrawerPanel
        open={inspecting !== null}
        onClose={() => { setInspecting(null); setDisposition('pass'); setSelectedDefects([]); setNotes('') }}
        title="QC Inspection"
        size="lg"
      >
        {inspecting && (
          <div className="space-y-5 p-4">
            <div className="flex items-center gap-3 p-3 rounded-lg bg-[var(--color-surface-alt)]">
              <div>
                <p className="text-sm font-semibold text-[var(--color-text-primary)]">
                  {inspecting.order_number} — {inspecting.customer_name}
                </p>
                <p className="text-xs text-[var(--color-text-muted)]">{inspecting.garment_type}</p>
              </div>
              <ProductionBadge state={inspecting.production_state ?? 'QC'} />
            </div>

            {defectCats.length > 0 && (
              <FormField label="Defects Found">
                <div className="flex flex-wrap gap-2">
                  {defectCats.map((d) => (
                    <label key={d.id} className="flex items-center gap-1.5 cursor-pointer">
                      <input
                        type="checkbox"
                        checked={selectedDefects.includes(d.id)}
                        onChange={(e) =>
                          setSelectedDefects((prev) =>
                            e.target.checked ? [...prev, d.id] : prev.filter((x) => x !== d.id),
                          )
                        }
                        className="accent-[var(--color-brand)]"
                      />
                      <span className="text-sm">{d.name}</span>
                      {d.severity && (
                        <span className="text-xs text-[var(--color-text-muted)]">({d.severity})</span>
                      )}
                    </label>
                  ))}
                </div>
              </FormField>
            )}

            <FormField label="Disposition" required>
              <div className="flex gap-2">
                {(['pass', 'rework', 'reject'] as const).map((d) => (
                  <button
                    key={d}
                    onClick={() => setDisposition(d)}
                    className={`flex-1 py-2 text-sm font-medium rounded-lg border-2 capitalize transition-colors ${
                      disposition === d
                        ? d === 'pass' ? 'border-green-500 bg-green-50 text-green-700'
                          : d === 'rework' ? 'border-[var(--color-brand)] bg-[var(--color-brand-light)] text-[var(--color-brand)]'
                          : 'border-red-400 bg-red-50 text-red-600'
                        : 'border-[var(--color-border)] text-[var(--color-text-secondary)]'
                    }`}
                  >
                    {d}
                  </button>
                ))}
              </div>
            </FormField>

            <FormField label="Notes" required={disposition !== 'pass'}>
              <textarea
                value={notes}
                onChange={(e) => setNotes(e.target.value)}
                rows={3}
                className="w-full px-3 py-2 text-sm border border-[var(--color-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)] resize-none"
                placeholder={disposition !== 'pass' ? 'Required for rework/reject' : 'Optional notes'}
              />
            </FormField>

            <div className="flex gap-2">
              <button
                onClick={handleInspect}
                disabled={submitting || (disposition !== 'pass' && !notes.trim())}
                className="flex-1 py-2.5 bg-[var(--color-brand)] text-white text-sm font-semibold rounded-xl hover:bg-[var(--color-brand-dark)] disabled:opacity-50 transition-colors"
              >
                {submitting ? 'Submitting…' : 'Submit Inspection'}
              </button>
              <button
                onClick={() => { setInspecting(null); setDisposition('pass'); setSelectedDefects([]); setNotes('') }}
                className="px-4 py-2 border border-[var(--color-border)] text-sm rounded-xl text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] transition-colors"
              >
                Cancel
              </button>
            </div>
          </div>
        )}
      </DrawerPanel>
    </div>
  )
}
