'use client'

import { useState } from 'react'
import { toast } from 'sonner'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { apiMutate, generateIdempotencyKey } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { queryKeys } from '@/lib/query/keys'
import { useProductionBoard } from '@/lib/api/hooks/useProduction'
import { usePermission, ROLES } from '@/lib/auth/permissions'
import { useAuthStore } from '@/lib/auth/store'
import { PageHeader } from '@/components/ui/page-header'
import { ProductionBadge } from '@/components/ui/status-badge'
import { ConfirmDialog } from '@/components/ui/confirm-dialog'
import { DrawerPanel } from '@/components/ui/drawer-panel'
import { FormField } from '@/components/ui/form-field'
import { TableSkeleton } from '@/components/ui/loading-skeleton'
import { EmptyState } from '@/components/ui/empty-state'
import type { ProductionItem } from '@/lib/api/schemas/production'

export default function TailoringPage() {
  const user = useAuthStore((s) => s.user)
  const { is } = usePermission()
  const isTailor = is(ROLES.TAILOR)
  const isSupervisor = is(ROLES.ADMIN) || is(ROLES.OWNER)

  const { data: board, isLoading } = useProductionBoard()
  const qc = useQueryClient()

  const [assignTarget, setAssignTarget] = useState<ProductionItem | null>(null)
  const [tailorId, setTailorId] = useState('')
  const [submitting, setSubmitting] = useState(false)

  const allItems: ProductionItem[] = Object.values(board?.columns ?? {}).flat()
  const assignableItems = allItems.filter((i) =>
    i.production_state === 'FabricAllocated' && !i.assigned_tailor_name,
  )
  const myItems = isTailor
    ? allItems.filter((i) => i.assigned_tailor_id === user?.id)
    : allItems.filter((i) => ['Tailoring', 'KajaButton', 'Finishing'].includes(i.production_state ?? ''))

  async function handleAssign() {
    if (!assignTarget || !tailorId) return
    setSubmitting(true)
    try {
      await apiMutate(
        'post',
        ENDPOINTS.tailoringAssignments,
        { production_item_id: assignTarget.id, tailor_id: parseInt(tailorId) },
        generateIdempotencyKey(),
      )
      qc.invalidateQueries({ queryKey: queryKeys.productionBoard() })
      setAssignTarget(null)
      setTailorId('')
      toast.success('Tailor assigned')
    } catch (err: unknown) {
      toast.error((err as { message?: string })?.message ?? 'Failed')
    } finally {
      setSubmitting(false)
    }
  }

  if (isLoading) return <TableSkeleton rows={5} cols={4} />

  return (
    <div className="space-y-6">
      <PageHeader
        title={isTailor ? 'My Work' : 'Tailor Assignment'}
        subtitle={isTailor ? `${myItems.length} assigned items` : `${assignableItems.length} items need assignment`}
      />

      {isSupervisor && assignableItems.length > 0 && (
        <div className="space-y-3">
          <p className="text-sm font-semibold text-[var(--color-text-primary)]">Needs Assignment</p>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
            {assignableItems.map((item) => (
              <div key={item.id} className="rounded-xl border border-[var(--color-border)] bg-white p-3 flex items-center justify-between gap-3">
                <div>
                  <p className="text-xs font-mono font-semibold text-[var(--color-brand)]">{item.order_number}</p>
                  <p className="text-sm text-[var(--color-text-primary)]">{item.customer_name} — {item.garment_type}</p>
                </div>
                <button
                  onClick={() => setAssignTarget(item)}
                  className="px-3 py-1.5 text-xs font-medium bg-[var(--color-brand)] text-white rounded-lg hover:bg-[var(--color-brand-dark)] transition-colors"
                >
                  Assign
                </button>
              </div>
            ))}
          </div>
        </div>
      )}

      <div className="space-y-3">
        <p className="text-sm font-semibold text-[var(--color-text-primary)]">
          {isTailor ? 'My Items' : 'In Tailoring'}
        </p>
        {myItems.length === 0 ? (
          <EmptyState title="No items" description="Nothing assigned right now" />
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
            {myItems.map((item) => (
              <div key={item.id} className="rounded-xl border border-[var(--color-border)] bg-white p-3">
                <div className="flex items-center justify-between gap-2 mb-2">
                  <p className="text-xs font-mono font-semibold text-[var(--color-brand)]">{item.order_number}</p>
                  <ProductionBadge state={item.production_state ?? 'Tailoring'} />
                </div>
                <p className="text-sm font-medium text-[var(--color-text-primary)]">{item.customer_name}</p>
                <p className="text-xs text-[var(--color-text-muted)]">{item.garment_type}</p>
                {item.rework_count && item.rework_count > 0 && (
                  <p className="mt-2 text-xs text-[var(--color-brand)] font-medium">
                    Returned from QC — check rework notes
                  </p>
                )}
              </div>
            ))}
          </div>
        )}
      </div>

      <DrawerPanel open={assignTarget !== null} onClose={() => { setAssignTarget(null); setTailorId('') }} title="Assign Tailor" size="sm">
        <div className="space-y-4 p-4">
          <p className="text-sm text-[var(--color-text-secondary)]">
            Assigning: {assignTarget?.order_number} — {assignTarget?.customer_name}
          </p>
          <FormField label="Tailor User ID" required>
            <input
              type="number"
              value={tailorId}
              onChange={(e) => setTailorId(e.target.value)}
              placeholder="Enter tailor user ID"
              className="w-full h-9 px-3 text-sm border border-[var(--color-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)]"
            />
          </FormField>
          <div className="flex gap-2">
            <button
              onClick={handleAssign}
              disabled={!tailorId || submitting}
              className="flex-1 py-2 bg-[var(--color-brand)] text-white text-sm font-medium rounded-lg hover:bg-[var(--color-brand-dark)] disabled:opacity-50 transition-colors"
            >
              {submitting ? 'Assigning…' : 'Assign'}
            </button>
            <button
              onClick={() => { setAssignTarget(null); setTailorId('') }}
              className="px-4 py-2 border border-[var(--color-border)] text-sm rounded-lg text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] transition-colors"
            >
              Cancel
            </button>
          </div>
        </div>
      </DrawerPanel>
    </div>
  )
}
