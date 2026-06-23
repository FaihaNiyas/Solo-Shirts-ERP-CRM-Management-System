'use client'

import { useMemo, useState } from 'react'
import { useRouter } from 'next/navigation'
import { Plus, Trash2 } from 'lucide-react'
import { toast } from 'sonner'
import type { ColumnDef } from '@tanstack/react-table'
import {
  usePurchaseOrders, usePurchaseOrder, useCreatePO, usePlacePO, useReceivePO, useCancelPO, useSuppliers, useFabricTypes,
} from '@/lib/api/hooks/useInventory'
import { PageHeader } from '@/components/ui/page-header'
import { DataTable } from '@/components/ui/data-table'
import { StatusBadge } from '@/components/ui/status-badge'
import { ConfirmDialog } from '@/components/ui/confirm-dialog'
import { DrawerPanel } from '@/components/ui/drawer-panel'
import { FormField } from '@/components/ui/form-field'
import { TableSkeleton } from '@/components/ui/loading-skeleton'
import { formatINR } from '@/lib/utils'
import { usePermission } from '@/lib/auth/permissions'
import { format } from 'date-fns'
import type { PurchaseOrder } from '@/lib/api/schemas/inventory'

const inputCls = 'w-full h-9 px-3 text-sm border border-[var(--color-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)]'

export default function PurchaseOrdersPage() {
  const { can } = usePermission()
  const canCreate = can('inventory.purchase_orders.create')
  const canPlace = can('inventory.purchase_orders.place')
  const canReceive = can('inventory.purchase_orders.receive')
  const router = useRouter()
  const [page, setPage] = useState(0)
  const { data, isLoading } = usePurchaseOrders({ page: page + 1, per_page: 20 })

  const [showCreate, setShowCreate] = useState(false)
  const [receiveId, setReceiveId] = useState<number | null>(null)
  const [confirmAction, setConfirmAction] = useState<{ type: 'place' | 'cancel'; id: number } | null>(null)

  const placeMutation = usePlacePO(confirmAction?.id ?? 0)
  const cancelMutation = useCancelPO(confirmAction?.id ?? 0)

  const pos = data?.data ?? []
  const pageCount = data ? Math.ceil((data.meta?.total ?? data.total ?? 0) / (data.meta?.per_page ?? data.per_page ?? 20)) : 0

  const columns: ColumnDef<PurchaseOrder, unknown>[] = [
    { accessorKey: 'po_code', header: 'PO #', cell: ({ row }) => <span className="font-mono text-xs font-semibold">{row.original.po_code ?? `#${row.original.id}`}</span> },
    { accessorKey: 'supplier_name', header: 'Supplier', cell: ({ row }) => <span className="text-sm">{row.original.supplier_name ?? '—'}</span> },
    { id: 'status', header: 'Status', cell: ({ row }) => <StatusBadge status={row.original.status ?? 'draft'} /> },
    { id: 'total', header: 'Total', cell: ({ row }) => <span className="font-mono text-sm">{formatINR((row.original.total_paise ?? 0) / 100)}</span> },
    { id: 'placed', header: 'Placed', cell: ({ row }) => <span className="text-sm text-[var(--color-text-muted)]">{row.original.placed_at ? format(new Date(row.original.placed_at), 'dd MMM yyyy') : '—'}</span> },
    {
      id: 'actions',
      header: 'Actions',
      cell: ({ row }) => {
        const s = row.original.status
        return (
          <div className="flex gap-1.5" onClick={(e) => e.stopPropagation()}>
            {s === 'draft' && canPlace && (
              <button onClick={() => setConfirmAction({ type: 'place', id: row.original.id })} className="px-2 py-1 text-xs border border-[var(--color-brand)] text-[var(--color-brand)] rounded hover:bg-[var(--color-brand-light)] transition-colors">Place</button>
            )}
            {(s === 'placed' || s === 'partial_received') && canReceive && (
              <button onClick={() => setReceiveId(row.original.id)} className="px-2 py-1 text-xs border border-green-500 text-green-600 rounded hover:bg-green-50 transition-colors">Receive</button>
            )}
            {['draft', 'placed'].includes(s ?? '') && canPlace && (
              <button onClick={() => setConfirmAction({ type: 'cancel', id: row.original.id })} className="px-2 py-1 text-xs border border-red-300 text-red-500 rounded hover:bg-red-50 transition-colors">Cancel</button>
            )}
          </div>
        )
      },
    },
  ]

  async function handleConfirm() {
    if (!confirmAction) return
    try {
      if (confirmAction.type === 'place') await placeMutation.mutateAsync()
      else await cancelMutation.mutateAsync({})
      toast.success(`Purchase order ${confirmAction.type === 'place' ? 'placed' : 'cancelled'}`)
      setConfirmAction(null)
    } catch (err: unknown) {
      toast.error((err as { message?: string })?.message ?? 'Failed')
    }
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title="Purchase Orders"
        subtitle="Order fabric · receive into stock"
        actions={canCreate && (
          <button onClick={() => setShowCreate(true)} className="inline-flex items-center gap-1.5 px-4 py-2 bg-[var(--color-brand)] text-white text-sm font-medium rounded-xl hover:bg-[var(--color-brand-dark)] transition-colors">
            <Plus size={15} strokeWidth={2} /> New PO
          </button>
        )}
      />

      {isLoading ? <TableSkeleton rows={6} cols={6} /> : (
        <DataTable data={pos} columns={columns} pageCount={pageCount} pageIndex={page} onPageChange={setPage} onRowClick={(row) => router.push(`/inventory/purchase-orders/${row.id}`)} />
      )}

      <ConfirmDialog
        open={confirmAction !== null}
        onClose={() => setConfirmAction(null)}
        onConfirm={handleConfirm}
        title={confirmAction?.type === 'cancel' ? 'Cancel purchase order' : 'Place purchase order'}
        description={confirmAction?.type === 'cancel' ? 'This cancels the order. It cannot be cancelled once received.' : 'Mark this order as placed with the supplier.'}
        variant={confirmAction?.type === 'cancel' ? 'danger' : 'info'}
        loading={placeMutation.isPending || cancelMutation.isPending}
      />

      {showCreate && <CreatePoDrawer onClose={() => setShowCreate(false)} />}
      {receiveId !== null && <ReceivePoDrawer poId={receiveId} onClose={() => setReceiveId(null)} />}
    </div>
  )
}

// ── Create PO ───────────────────────────────────────────────────────────────

interface LineForm { fabric_type_id: string; colour: string; quantity_metres: string; unit_price_rupees: string }
const emptyLine: LineForm = { fabric_type_id: '', colour: '', quantity_metres: '', unit_price_rupees: '' }

function CreatePoDrawer({ onClose }: { onClose: () => void }) {
  const { data: suppliers } = useSuppliers()
  const { data: types } = useFabricTypes()
  const create = useCreatePO()
  const [supplierId, setSupplierId] = useState('')
  const [notes, setNotes] = useState('')
  const [lines, setLines] = useState<LineForm[]>([{ ...emptyLine }])

  const total = useMemo(() => lines.reduce((sum, l) => sum + (parseFloat(l.quantity_metres) || 0) * (parseFloat(l.unit_price_rupees) || 0), 0), [lines])
  const valid = supplierId !== '' && lines.length > 0 && lines.every((l) => l.fabric_type_id && parseFloat(l.quantity_metres) > 0 && l.unit_price_rupees !== '')

  function setLine(i: number, patch: Partial<LineForm>) {
    setLines((ls) => ls.map((l, idx) => (idx === i ? { ...l, ...patch } : l)))
  }

  async function submit() {
    if (!valid) return
    try {
      await create.mutateAsync({
        supplier_id: Number(supplierId),
        notes: notes.trim() || undefined,
        items: lines.map((l) => ({
          fabric_type_id: Number(l.fabric_type_id),
          colour: l.colour.trim() || undefined,
          quantity_metres: parseFloat(l.quantity_metres),
          unit_price_paise: Math.round(parseFloat(l.unit_price_rupees) * 100),
        })),
      })
      toast.success('Purchase order drafted')
      onClose()
    } catch (err: unknown) {
      toast.error((err as { message?: string })?.message ?? 'Failed')
    }
  }

  return (
    <DrawerPanel open onClose={onClose} title="New Purchase Order" size="lg">
      <div className="space-y-4 p-4">
        <FormField label="Supplier" required>
          <select value={supplierId} onChange={(e) => setSupplierId(e.target.value)} className={inputCls}>
            <option value="">Select supplier…</option>
            {(suppliers?.data ?? []).map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
          </select>
        </FormField>

        <div>
          <div className="flex items-center justify-between mb-2">
            <span className="text-sm font-medium text-[var(--color-text-primary)]">Line items</span>
            <button type="button" onClick={() => setLines((ls) => [...ls, { ...emptyLine }])} className="inline-flex items-center gap-1 text-xs text-[var(--color-brand)] hover:underline"><Plus size={12} /> Add line</button>
          </div>
          <div className="space-y-2">
            {lines.map((l, i) => (
              <div key={i} className="grid grid-cols-12 gap-2 items-end rounded-lg border border-[var(--color-border)] p-2">
                <div className="col-span-4">
                  <span className="block text-[10px] uppercase text-[var(--color-text-muted)] mb-1">Fabric</span>
                  <select value={l.fabric_type_id} onChange={(e) => setLine(i, { fabric_type_id: e.target.value })} className={inputCls}>
                    <option value="">Type…</option>
                    {(types ?? []).map((t) => <option key={t.id} value={t.id}>{t.name}</option>)}
                  </select>
                </div>
                <div className="col-span-2">
                  <span className="block text-[10px] uppercase text-[var(--color-text-muted)] mb-1">Colour</span>
                  <input value={l.colour} onChange={(e) => setLine(i, { colour: e.target.value })} className={inputCls} />
                </div>
                <div className="col-span-2">
                  <span className="block text-[10px] uppercase text-[var(--color-text-muted)] mb-1">Metres</span>
                  <input type="number" min={0} step="0.01" value={l.quantity_metres} onChange={(e) => setLine(i, { quantity_metres: e.target.value })} className={inputCls} />
                </div>
                <div className="col-span-3">
                  <span className="block text-[10px] uppercase text-[var(--color-text-muted)] mb-1">₹ / metre</span>
                  <input type="number" min={0} step="0.01" value={l.unit_price_rupees} onChange={(e) => setLine(i, { unit_price_rupees: e.target.value })} className={inputCls} />
                </div>
                <div className="col-span-1 flex justify-center pb-1.5">
                  {lines.length > 1 && (
                    <button type="button" onClick={() => setLines((ls) => ls.filter((_, idx) => idx !== i))} className="text-[var(--color-text-muted)] hover:text-red-500"><Trash2 size={15} /></button>
                  )}
                </div>
              </div>
            ))}
          </div>
        </div>

        <FormField label="Notes">
          <input value={notes} onChange={(e) => setNotes(e.target.value)} className={inputCls} placeholder="Optional" />
        </FormField>

        <div className="flex items-center justify-between rounded-lg bg-[var(--color-surface-alt)] px-4 py-3">
          <span className="text-sm text-[var(--color-text-secondary)]">Estimated total</span>
          <span className="text-lg font-semibold tabular-nums">{formatINR(total)}</span>
        </div>

        <div className="flex gap-2">
          <button onClick={submit} disabled={!valid || create.isPending} className="flex-1 py-2 bg-[var(--color-brand)] text-white text-sm font-medium rounded-lg hover:bg-[var(--color-brand-dark)] disabled:opacity-50 transition-colors">
            {create.isPending ? 'Saving…' : 'Create draft PO'}
          </button>
          <button onClick={onClose} className="px-4 py-2 border border-[var(--color-border)] text-sm rounded-lg text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] transition-colors">Cancel</button>
        </div>
      </div>
    </DrawerPanel>
  )
}

// ── Receive PO (line-by-line into stock) ─────────────────────────────────────

function ReceivePoDrawer({ poId, onClose }: { poId: number; onClose: () => void }) {
  const { data: po, isLoading } = usePurchaseOrder(poId)
  const receive = useReceivePO(poId)
  const [lines, setLines] = useState<Record<number, { metres: string; rack: string }>>({})

  function lineState(itemId: number, ordered: number, received: number) {
    return lines[itemId] ?? { metres: String(Math.max(0, ordered - received)), rack: '' }
  }

  async function submit() {
    if (!po?.items) return
    const payload = po.items
      .map((it) => {
        const st = lines[it.id]
        const metres = st ? parseFloat(st.metres) : Math.max(0, (parseFloat(String(it.quantity_metres)) || 0) - (parseFloat(String(it.received_metres ?? 0)) || 0))
        return { purchase_order_item_id: it.id, metres, rack_location: (st?.rack || undefined) }
      })
      .filter((l) => l.metres > 0)

    if (payload.length === 0) { toast.error('Enter metres to receive.'); return }

    try {
      await receive.mutateAsync({ lines: payload })
      toast.success('Goods received — rolls added to stock')
      onClose()
    } catch (err: unknown) {
      toast.error((err as { message?: string })?.message ?? 'Failed')
    }
  }

  return (
    <DrawerPanel open onClose={onClose} title={`Receive ${po?.po_code ?? 'PO'}`} size="lg">
      <div className="space-y-4 p-4">
        <p className="text-xs text-[var(--color-text-muted)]">Each received line creates a fabric roll and an inward stock movement. Receiving more than ordered needs approval.</p>
        {isLoading || !po?.items ? (
          <TableSkeleton rows={3} cols={3} />
        ) : (
          <div className="space-y-2">
            {po.items.map((it) => {
              const ordered = parseFloat(String(it.quantity_metres)) || 0
              const received = parseFloat(String(it.received_metres ?? 0)) || 0
              const st = lineState(it.id, ordered, received)
              return (
                <div key={it.id} className="grid grid-cols-12 gap-2 items-end rounded-lg border border-[var(--color-border)] p-2">
                  <div className="col-span-5">
                    <span className="block text-[10px] uppercase text-[var(--color-text-muted)] mb-1">Line</span>
                    <p className="text-sm">{it.colour ?? 'Fabric'} · ordered {ordered}m{received > 0 ? ` · received ${received}m` : ''}</p>
                  </div>
                  <div className="col-span-3">
                    <span className="block text-[10px] uppercase text-[var(--color-text-muted)] mb-1">Receive (m)</span>
                    <input type="number" min={0} step="0.01" value={st.metres} onChange={(e) => setLines((p) => ({ ...p, [it.id]: { ...st, metres: e.target.value } }))} className={inputCls} />
                  </div>
                  <div className="col-span-4">
                    <span className="block text-[10px] uppercase text-[var(--color-text-muted)] mb-1">Rack location</span>
                    <input value={st.rack} onChange={(e) => setLines((p) => ({ ...p, [it.id]: { ...st, rack: e.target.value } }))} className={inputCls} placeholder="Optional" />
                  </div>
                </div>
              )
            })}
          </div>
        )}
        <div className="flex gap-2">
          <button onClick={submit} disabled={receive.isPending} className="flex-1 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 disabled:opacity-50 transition-colors">
            {receive.isPending ? 'Receiving…' : 'Receive into stock'}
          </button>
          <button onClick={onClose} className="px-4 py-2 border border-[var(--color-border)] text-sm rounded-lg text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] transition-colors">Cancel</button>
        </div>
      </div>
    </DrawerPanel>
  )
}
