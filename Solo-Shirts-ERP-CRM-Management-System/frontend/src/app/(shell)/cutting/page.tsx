'use client'

import { useMemo, useState } from 'react'
import { toast } from 'sonner'
import { Scissors } from 'lucide-react'
import {
  useCuttingQueue,
  useAllocatableRolls,
  useAllocateFabric,
  useReleaseFabric,
  useStartCutting,
  useCompleteCutting,
  type CuttingQueueItem,
  type CompleteCuttingBundleInput,
} from '@/lib/api/hooks/useCutting'
import { PageHeader } from '@/components/ui/page-header'
import { DrawerPanel } from '@/components/ui/drawer-panel'
import { ErrorState } from '@/components/ui/error-state'
import { EmptyState } from '@/components/ui/empty-state'
import { TableSkeleton } from '@/components/ui/loading-skeleton'
import { FormField } from '@/components/ui/form-field'
import { RequireRole } from '@/components/shell/RequireRole'
import { ROLES } from '@/lib/auth/permissions'
import type { ApiError } from '@/lib/api/types'

const STATE_LABEL: Record<string, string> = {
  draft: 'Awaiting fabric',
  fabric_allocated: 'Fabric reserved',
  cutting: 'Cutting',
}

function asApiError(err: unknown): ApiError {
  const e = err as Partial<ApiError> | undefined
  return {
    message: e?.message ?? 'Something went wrong',
    code: e?.code ?? 'UNKNOWN_ERROR',
    request_id: e?.request_id ?? '',
  }
}

function toastError(err: unknown, fallback: string) {
  const e = asApiError(err)
  toast.error(e.message || fallback, {
    description: e.request_id ? `req: ${e.request_id.slice(0, 12)}…` : undefined,
  })
}

// FE-011: gate the route to roles that own cutting; backend still enforces 403.
export default function CuttingQueuePage() {
  return (
    <RequireRole roles={[ROLES.OWNER, ROLES.ADMIN, ROLES.CUTTER, ROLES.PRODUCTION]}>
      <CuttingQueueContent />
    </RequireRole>
  )
}

function CuttingQueueContent() {
  const { data: items, isLoading, isError, error, refetch } = useCuttingQueue()
  const [allocateItem, setAllocateItem] = useState<CuttingQueueItem | null>(null)
  const [completeItem, setCompleteItem] = useState<CuttingQueueItem | null>(null)

  const count = items?.length ?? 0

  return (
    <div className="space-y-6">
      <PageHeader title="Cutting Queue" subtitle={isLoading ? 'Loading…' : `${count} items`} />

      {isLoading ? (
        <TableSkeleton rows={6} cols={6} />
      ) : isError ? (
        <ErrorState
          message={asApiError(error).message}
          requestId={asApiError(error).request_id}
          onRetry={() => refetch()}
        />
      ) : count === 0 ? (
        <EmptyState icon={Scissors} title="No items in the cutting queue" description="Items awaiting fabric, reserved, or being cut will appear here." />
      ) : (
        <div className="overflow-x-auto rounded-xl border border-[var(--color-border)] bg-white">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-[var(--color-border)] text-left text-xs text-[var(--color-text-muted)]">
                <th className="px-4 py-2.5 font-medium">Item</th>
                <th className="px-4 py-2.5 font-medium">Garment</th>
                <th className="px-4 py-2.5 font-medium">Qty</th>
                <th className="px-4 py-2.5 font-medium">Fabric preference</th>
                <th className="px-4 py-2.5 font-medium">State</th>
                <th className="px-4 py-2.5 font-medium text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              {(items ?? []).map((item) => (
                <tr key={item.id} className="border-b border-[var(--color-border-soft)] last:border-0">
                  <td className="px-4 py-2.5 font-mono text-xs font-semibold text-[var(--color-brand)]">
                    {item.item_code}
                  </td>
                  <td className="px-4 py-2.5 capitalize">{item.product_type}</td>
                  <td className="px-4 py-2.5">{item.quantity}</td>
                  <td className="px-4 py-2.5 text-[var(--color-text-secondary)]">
                    {item.fabric_preference_text ?? '—'}
                  </td>
                  <td className="px-4 py-2.5">
                    <span className="inline-flex items-center rounded-full bg-[var(--color-surface-alt)] px-2 py-0.5 text-xs font-medium text-[var(--color-text-secondary)]">
                      {STATE_LABEL[item.state] ?? item.state}
                    </span>
                  </td>
                  <td className="px-4 py-2.5">
                    <RowActions
                      item={item}
                      onAllocate={() => setAllocateItem(item)}
                      onComplete={() => setCompleteItem(item)}
                    />
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {allocateItem && (
        <AllocateDrawer item={allocateItem} onClose={() => setAllocateItem(null)} />
      )}
      {completeItem && (
        <CompleteDrawer item={completeItem} onClose={() => setCompleteItem(null)} />
      )}
    </div>
  )
}

/** State-driven action buttons. Start/Release mutate inline; Allocate/Complete open drawers. */
function RowActions({
  item,
  onAllocate,
  onComplete,
}: {
  item: CuttingQueueItem
  onAllocate: () => void
  onComplete: () => void
}) {
  const release = useReleaseFabric(item.id)
  const start = useStartCutting(item.id)

  const btn =
    'px-2.5 py-1 text-xs font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed'
  const primary = `${btn} bg-[var(--color-brand)] text-white hover:bg-[var(--color-brand-dark)]`
  const outline = `${btn} border border-[var(--color-brand)] text-[var(--color-brand)] hover:bg-[var(--color-brand-light)]`
  const ghost = `${btn} border border-[var(--color-border)] text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)]`

  return (
    <div className="flex justify-end gap-1.5">
      {item.state === 'draft' && (
        <button className={primary} onClick={onAllocate}>
          Allocate Fabric
        </button>
      )}
      {item.state === 'fabric_allocated' && (
        <>
          <button
            className={ghost}
            disabled={release.isPending}
            onClick={() =>
              release.mutate(
                { reason: 'Released from cutting queue' },
                {
                  onSuccess: () => toast.success('Reservation released'),
                  onError: (e) => toastError(e, 'Release failed'),
                },
              )
            }
          >
            {release.isPending ? 'Releasing…' : 'Release'}
          </button>
          <button
            className={primary}
            disabled={start.isPending}
            onClick={() =>
              start.mutate(undefined, {
                onSuccess: () => toast.success('Cutting started'),
                onError: (e) => toastError(e, 'Start cutting failed'),
              })
            }
          >
            {start.isPending ? 'Starting…' : 'Start Cutting'}
          </button>
        </>
      )}
      {item.state === 'cutting' && (
        <button className={outline} onClick={onComplete}>
          Complete Cutting
        </button>
      )}
    </div>
  )
}

/** Reserve fabric: pick a roll, enter metres; shows remaining/reserved/available. */
function AllocateDrawer({ item, onClose }: { item: CuttingQueueItem; onClose: () => void }) {
  const { data: rolls, isLoading } = useAllocatableRolls()
  const allocate = useAllocateFabric(item.id)
  const [rollId, setRollId] = useState<number | ''>('')
  const [metres, setMetres] = useState('')

  const selected = useMemo(() => rolls?.find((r) => r.id === rollId), [rolls, rollId])
  const reserved = selected ? Math.max(0, selected.remaining_metres - selected.available_metres) : 0
  const metresNum = Number(metres)
  const valid = rollId !== '' && metresNum > 0

  function submit() {
    if (!valid) return
    allocate.mutate(
      { roll_id: Number(rollId), metres: metresNum },
      {
        onSuccess: () => {
          toast.success('Fabric reserved')
          onClose()
        },
        onError: (e) => toastError(e, 'Allocation failed'),
      },
    )
  }

  return (
    <DrawerPanel
      open
      onClose={onClose}
      title="Allocate Fabric"
      description={`${item.item_code} · ${item.product_type}`}
      footer={
        <div className="flex justify-end gap-2">
          <button
            onClick={onClose}
            className="px-4 py-2 text-sm rounded-lg border border-[var(--color-border)] text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)]"
          >
            Cancel
          </button>
          <button
            onClick={submit}
            disabled={!valid || allocate.isPending}
            className="px-4 py-2 text-sm rounded-lg bg-[var(--color-brand)] text-white font-medium hover:bg-[var(--color-brand-dark)] disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {allocate.isPending ? 'Reserving…' : 'Reserve Fabric'}
          </button>
        </div>
      }
    >
      <div className="space-y-4">
        <FormField label="Fabric roll" required>
          <select
            value={rollId}
            onChange={(e) => setRollId(e.target.value === '' ? '' : Number(e.target.value))}
            disabled={isLoading}
            className="w-full h-9 px-3 text-sm border border-[var(--color-border)] rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)]"
          >
            <option value="">{isLoading ? 'Loading rolls…' : 'Select a roll'}</option>
            {(rolls ?? []).map((r) => (
              <option key={r.id} value={r.id}>
                {r.roll_code}{r.colour ? ` · ${r.colour}` : ''} — {r.available_metres}m available
              </option>
            ))}
          </select>
        </FormField>

        {selected && (
          <div className="grid grid-cols-3 gap-2 rounded-lg bg-[var(--color-surface-alt)] p-3 text-center">
            <Stat label="Remaining" value={`${selected.remaining_metres}m`} />
            <Stat label="Reserved" value={`${reserved}m`} />
            <Stat label="Available" value={`${selected.available_metres}m`} />
          </div>
        )}

        <FormField label="Metres to reserve" required>
          <input
            type="number"
            min={0.01}
            step={0.01}
            value={metres}
            onChange={(e) => setMetres(e.target.value)}
            placeholder="e.g. 3.5"
            className="w-full h-9 px-3 text-sm border border-[var(--color-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)]"
          />
        </FormField>
        {selected && metresNum > selected.available_metres && (
          <p className="text-xs text-[var(--color-danger)]">
            Only {selected.available_metres}m available on this roll.
          </p>
        )}
      </div>
    </DrawerPanel>
  )
}

/** Complete cutting: enter actual metres used + one or more cut bundles. */
function CompleteDrawer({ item, onClose }: { item: CuttingQueueItem; onClose: () => void }) {
  const complete = useCompleteCutting(item.id)
  const [actual, setActual] = useState('')
  const [bundles, setBundles] = useState<CompleteCuttingBundleInput[]>([{ pieces: item.quantity || 1 }])

  const actualNum = Number(actual)
  const validBundles = bundles.every((b) => b.pieces > 0)
  const valid = actualNum > 0 && bundles.length > 0 && validBundles

  function updateBundle(i: number, patch: Partial<CompleteCuttingBundleInput>) {
    setBundles((bs) => bs.map((b, idx) => (idx === i ? { ...b, ...patch } : b)))
  }

  function submit() {
    if (!valid) return
    complete.mutate(
      { actual_metres: actualNum, bundles },
      {
        onSuccess: () => {
          toast.success('Cutting completed — bundles created')
          onClose()
        },
        onError: (e) => toastError(e, 'Complete cutting failed'),
      },
    )
  }

  return (
    <DrawerPanel
      open
      onClose={onClose}
      title="Complete Cutting"
      description={`${item.item_code} · ${item.product_type}`}
      footer={
        <div className="flex justify-end gap-2">
          <button
            onClick={onClose}
            className="px-4 py-2 text-sm rounded-lg border border-[var(--color-border)] text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)]"
          >
            Cancel
          </button>
          <button
            onClick={submit}
            disabled={!valid || complete.isPending}
            className="px-4 py-2 text-sm rounded-lg bg-[var(--color-brand)] text-white font-medium hover:bg-[var(--color-brand-dark)] disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {complete.isPending ? 'Completing…' : 'Consume & Create Bundles'}
          </button>
        </div>
      }
    >
      <div className="space-y-4">
        <FormField label="Actual metres used" required>
          <input
            type="number"
            min={0.01}
            step={0.01}
            value={actual}
            onChange={(e) => setActual(e.target.value)}
            placeholder="e.g. 3.2"
            className="w-full h-9 px-3 text-sm border border-[var(--color-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)]"
          />
        </FormField>

        <div className="space-y-2">
          <div className="flex items-center justify-between">
            <span className="text-sm font-medium text-[var(--color-text-primary)]">Bundles</span>
            <button
              type="button"
              onClick={() => setBundles((bs) => [...bs, { pieces: 1 }])}
              className="text-xs text-[var(--color-brand)] hover:underline"
            >
              + Add bundle
            </button>
          </div>
          {bundles.map((b, i) => (
            <div key={i} className="flex gap-2 items-end">
              <div className="w-24">
                <label className="block text-xs text-[var(--color-text-muted)] mb-1">Pieces</label>
                <input
                  type="number"
                  min={1}
                  value={b.pieces}
                  onChange={(e) => updateBundle(i, { pieces: Number(e.target.value) })}
                  className="w-full h-9 px-3 text-sm border border-[var(--color-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)]"
                />
              </div>
              <div className="flex-1">
                <label className="block text-xs text-[var(--color-text-muted)] mb-1">Notes</label>
                <input
                  type="text"
                  value={b.notes ?? ''}
                  onChange={(e) => updateBundle(i, { notes: e.target.value })}
                  placeholder="optional"
                  className="w-full h-9 px-3 text-sm border border-[var(--color-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)]"
                />
              </div>
              {bundles.length > 1 && (
                <button
                  type="button"
                  onClick={() => setBundles((bs) => bs.filter((_, idx) => idx !== i))}
                  className="h-9 px-2 text-xs text-[var(--color-danger)] hover:underline"
                >
                  Remove
                </button>
              )}
            </div>
          ))}
        </div>
      </div>
    </DrawerPanel>
  )
}

function Stat({ label, value }: { label: string; value: string }) {
  return (
    <div>
      <p className="text-xs text-[var(--color-text-muted)]">{label}</p>
      <p className="text-sm font-semibold text-[var(--color-text-primary)]">{value}</p>
    </div>
  )
}
