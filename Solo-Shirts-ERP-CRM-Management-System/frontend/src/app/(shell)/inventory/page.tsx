'use client'

import Link from 'next/link'
import { Layers, Users, ShoppingCart, AlertTriangle } from 'lucide-react'
import { useQuery } from '@tanstack/react-query'
import { apiGet } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { queryKeys } from '@/lib/query/keys'
import { PageHeader } from '@/components/ui/page-header'
import type { FabricRoll } from '@/lib/api/schemas/inventory'

const SECTIONS = [
  {
    href: '/inventory/fabric-rolls',
    icon: Layers,
    label: 'Fabric Rolls',
    description: 'Track remaining, reserved, and available metres per roll.',
  },
  {
    href: '/inventory/suppliers',
    icon: Users,
    label: 'Suppliers',
    description: 'Manage supplier profiles and contact information.',
  },
  {
    href: '/inventory/purchase-orders',
    icon: ShoppingCart,
    label: 'Purchase Orders',
    description: 'Raise, place, and receive POs for fabric replenishment.',
  },
]

export default function InventoryPage() {
  const { data: lowStockRolls } = useQuery({
    queryKey: queryKeys.lowStock(),
    queryFn: () => apiGet<FabricRoll[]>(ENDPOINTS.lowStock),
    select: (res) => res.data,
  })

  const lowStockCount = lowStockRolls?.length ?? 0

  return (
    <div className="space-y-6">
      <PageHeader title="Inventory" subtitle="Fabric rolls, suppliers, and purchase orders" />

      {lowStockCount > 0 && (
        <div className="flex items-center gap-3 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-sm text-red-700">
          <AlertTriangle size={16} strokeWidth={1.75} className="shrink-0" />
          <span>
            <strong>{lowStockCount}</strong> fabric roll{lowStockCount !== 1 ? 's' : ''} are below the minimum stock threshold.
          </span>
          <Link href="/inventory/fabric-rolls?low_stock=true" className="ml-auto text-xs font-medium underline hover:no-underline shrink-0">
            View
          </Link>
        </div>
      )}

      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        {SECTIONS.map(({ href, icon: Icon, label, description }) => (
          <Link
            key={href}
            href={href}
            className="flex flex-col gap-3 p-5 rounded-xl border border-[var(--color-border)] bg-white hover:border-[var(--color-brand)] hover:shadow-[var(--shadow-sm)] transition-all group"
          >
            <span className="flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--color-brand-light)] text-[var(--color-brand)] group-hover:bg-[var(--color-brand)] group-hover:text-white transition-colors">
              <Icon size={20} strokeWidth={1.75} />
            </span>
            <div>
              <p className="text-sm font-semibold text-[var(--color-text-primary)]">{label}</p>
              <p className="mt-0.5 text-xs text-[var(--color-text-muted)] leading-relaxed">{description}</p>
            </div>
          </Link>
        ))}
      </div>
    </div>
  )
}
