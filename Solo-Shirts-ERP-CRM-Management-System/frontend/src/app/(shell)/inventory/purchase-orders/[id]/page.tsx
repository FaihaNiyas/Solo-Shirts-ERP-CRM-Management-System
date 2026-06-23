'use client'

import { use, useMemo } from 'react'
import Link from 'next/link'
import { ArrowLeft } from 'lucide-react'
import { usePurchaseOrder, useFabricTypes } from '@/lib/api/hooks/useInventory'
import { PageHeader } from '@/components/ui/page-header'
import { InfoGrid } from '@/components/ui/info-grid'
import { StatusBadge } from '@/components/ui/status-badge'
import { TableSkeleton } from '@/components/ui/loading-skeleton'
import { formatINR } from '@/lib/utils'

export default function PurchaseOrderDetailPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = use(params)
  const poId = parseInt(id)
  const { data: po, isLoading } = usePurchaseOrder(poId)
  const { data: types } = useFabricTypes()

  const typeName = useMemo(() => {
    const map = new Map<number, string>()
    for (const t of types ?? []) map.set(t.id, t.name)
    return map
  }, [types])

  if (isLoading) return <TableSkeleton rows={4} cols={4} />
  if (!po) return <p className="text-sm text-[var(--color-text-muted)]">Purchase order not found</p>

  return (
    <div className="space-y-6">
      <Link href="/inventory/purchase-orders" className="inline-flex items-center gap-1.5 text-sm text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)]">
        <ArrowLeft size={15} strokeWidth={1.75} /> Purchase orders
      </Link>

      <PageHeader
        title={po.po_code ?? `PO #${poId}`}
        subtitle={po.supplier_name ?? undefined}
        actions={<StatusBadge status={po.status ?? 'draft'} />}
      />

      <InfoGrid
        items={[
          { label: 'Supplier', value: po.supplier_name ?? '—' },
          { label: 'Status', value: <StatusBadge status={po.status ?? 'draft'} /> },
          { label: 'Total', value: formatINR((po.total_paise ?? 0) / 100) },
          { label: 'Placed', value: po.placed_at ?? '—' },
          { label: 'Notes', value: po.notes ?? '—' },
        ]}
      />

      <section className="rounded-xl border border-[var(--color-border)] overflow-hidden">
        <div className="px-4 py-3 bg-[var(--color-surface-alt)] border-b border-[var(--color-border)]">
          <p className="text-xs font-semibold text-[var(--color-text-muted)] uppercase tracking-wide">Line items</p>
        </div>
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-[var(--color-border)] text-left text-[11px] uppercase tracking-wide text-[var(--color-text-muted)]">
              <th className="px-4 py-2 font-medium">Fabric</th>
              <th className="px-4 py-2 font-medium">Colour</th>
              <th className="px-4 py-2 font-medium text-right">Ordered</th>
              <th className="px-4 py-2 font-medium text-right">Received</th>
              <th className="px-4 py-2 font-medium text-right">₹ / m</th>
            </tr>
          </thead>
          <tbody>
            {(po.items ?? []).map((it) => (
              <tr key={it.id} className="border-b border-[var(--color-border)] last:border-0">
                <td className="px-4 py-2">{typeName.get(it.fabric_type_id) ?? `Type ${it.fabric_type_id}`}</td>
                <td className="px-4 py-2 text-[var(--color-text-secondary)]">{it.colour ?? '—'}</td>
                <td className="px-4 py-2 text-right font-mono">{it.quantity_metres}m</td>
                <td className="px-4 py-2 text-right font-mono text-green-700">{it.received_metres ?? '0.00'}m</td>
                <td className="px-4 py-2 text-right font-mono">{formatINR((it.unit_price_paise ?? 0) / 100)}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </section>

      <p className="text-xs text-[var(--color-text-muted)]">Receiving a line creates a fabric roll and an inward stock movement. Manage actions (place / receive / cancel) from the purchase-orders list.</p>
    </div>
  )
}
