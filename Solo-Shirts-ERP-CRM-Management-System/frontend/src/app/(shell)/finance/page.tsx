'use client'

import Link from 'next/link'
import { FileText, PieChart, AlertCircle } from 'lucide-react'
import { useFinanceDashboard } from '@/lib/api/hooks/useFinance'
import { PageHeader } from '@/components/ui/page-header'
import { formatINR } from '@/lib/utils'

const SECTIONS = [
  {
    href: '/finance/invoices',
    icon: FileText,
    label: 'Invoices',
    description: 'Generate invoices, record payments, and issue credit notes.',
  },
  {
    href: '/finance/gst',
    icon: PieChart,
    label: 'GST Report',
    description: 'Quarter-wise CGST / SGST / IGST breakdown for filing.',
  },
  {
    href: '/finance/outstanding',
    icon: AlertCircle,
    label: 'Outstanding',
    description: 'Customers with unpaid balances, sorted by amount due.',
  },
]

export default function FinancePage() {
  const { data: summary } = useFinanceDashboard()

  return (
    <div className="space-y-6">
      <PageHeader title="Finance" subtitle="Invoices, GST, and outstanding balances" />

      {summary && (
        <div className="grid grid-cols-3 gap-4">
          <div className="rounded-xl border border-[var(--color-border)] bg-white p-4">
            <p className="text-xs text-[var(--color-text-muted)] mb-1">Total Revenue</p>
            <p className="text-xl font-semibold text-[var(--color-text-primary)] tabular-nums">
              {formatINR(summary.total_revenue ?? 0)}
            </p>
          </div>
          <div className="rounded-xl border border-[var(--color-border)] bg-white p-4">
            <p className="text-xs text-[var(--color-text-muted)] mb-1">Outstanding</p>
            <p className="text-xl font-semibold text-red-600 tabular-nums">
              {formatINR(summary.outstanding_amount ?? 0)}
            </p>
          </div>
          <div className="rounded-xl border border-[var(--color-border)] bg-white p-4">
            <p className="text-xs text-[var(--color-text-muted)] mb-1">Completed Today</p>
            <p className="text-xl font-semibold text-green-600 tabular-nums">
              {summary.completed_today ?? 0}
            </p>
          </div>
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
