'use client'

import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { apiGet } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { PageHeader } from '@/components/ui/page-header'
import { CurrencyDisplay } from '@/components/ui/currency-display'
import { TableSkeleton } from '@/components/ui/loading-skeleton'

const QUARTERS = [
  { value: 'Q1', label: 'Q1 (Apr–Jun)' },
  { value: 'Q2', label: 'Q2 (Jul–Sep)' },
  { value: 'Q3', label: 'Q3 (Oct–Dec)' },
  { value: 'Q4', label: 'Q4 (Jan–Mar)' },
]

interface GSTSummary {
  total_taxable: number
  cgst_collected: number
  sgst_collected: number
  igst_collected: number
  total_gst: number
  breakdown?: Array<{
    rate: string
    taxable: number
    cgst: number
    sgst: number
    igst: number
    invoice_count: number
  }>
}

function getFiscalYear() {
  const now = new Date()
  const year = now.getMonth() >= 3 ? now.getFullYear() : now.getFullYear() - 1
  return { year, label: `FY ${year}-${String(year + 1).slice(-2)}` }
}

export default function GSTPage() {
  const [quarter, setQuarter] = useState('Q1')
  const { year, label: fiscalLabel } = getFiscalYear()

  const { data: summary, isLoading } = useQuery<GSTSummary>({
    queryKey: ['finance', 'gst', year, quarter],
    queryFn: () =>
      apiGet<GSTSummary>(ENDPOINTS.reports, { type: 'gst_summary', quarter, fiscal_year: year }).then(
        (r) => r.data as GSTSummary,
      ),
  })

  const metrics = [
    { label: 'Total Taxable', value: summary?.total_taxable ?? 0 },
    { label: 'CGST Collected', value: summary?.cgst_collected ?? 0 },
    { label: 'SGST Collected', value: summary?.sgst_collected ?? 0 },
    { label: 'IGST Collected', value: summary?.igst_collected ?? 0 },
    { label: 'Total GST', value: summary?.total_gst ?? 0 },
  ]

  return (
    <div className="space-y-6">
      <PageHeader title="GST Summary" subtitle={fiscalLabel} />

      <div className="flex gap-2">
        {QUARTERS.map((q) => (
          <button
            key={q.value}
            onClick={() => setQuarter(q.value)}
            className={`px-4 py-2 text-sm font-medium rounded-lg border transition-colors ${
              quarter === q.value
                ? 'border-[var(--color-brand)] bg-[var(--color-brand-light)] text-[var(--color-brand)]'
                : 'border-[var(--color-border)] text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)]'
            }`}
          >
            {q.label}
          </button>
        ))}
      </div>

      {isLoading ? (
        <div className="grid grid-cols-2 md:grid-cols-5 gap-4">
          {Array.from({ length: 5 }).map((_, i) => (
            <div key={i} className="h-24 rounded-xl border border-[var(--color-border)] animate-pulse bg-[var(--color-border)]" />
          ))}
        </div>
      ) : (
        <div className="grid grid-cols-2 md:grid-cols-5 gap-4">
          {metrics.map(({ label, value }) => (
            <div key={label} className="rounded-xl border border-[var(--color-border)] bg-white p-4">
              <p className="text-xs text-[var(--color-text-muted)] mb-1">{label}</p>
              <CurrencyDisplay amount={value} className="text-lg font-semibold" />
            </div>
          ))}
        </div>
      )}

      {summary?.breakdown && (
        <div className="rounded-xl border border-[var(--color-border)] overflow-hidden">
          <div className="px-4 py-3 bg-[var(--color-surface-alt)] border-b border-[var(--color-border)]">
            <p className="text-xs font-semibold text-[var(--color-text-muted)] uppercase tracking-wide">
              Rate-wise Breakdown
            </p>
          </div>
          <table className="w-full text-sm">
            <thead className="bg-[var(--color-surface-alt)]">
              <tr>
                {['GST Rate', 'Taxable', 'CGST', 'SGST', 'IGST', 'Invoices'].map((h) => (
                  <th key={h} className="px-4 py-2 text-left text-xs font-semibold text-[var(--color-text-muted)]">
                    {h}
                  </th>
                ))}
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-[var(--color-border)]">
              {summary.breakdown.map((row) => (
                <tr key={row.rate}>
                  <td className="px-4 py-3 font-semibold">{row.rate}%</td>
                  <td className="px-4 py-3"><CurrencyDisplay amount={row.taxable} /></td>
                  <td className="px-4 py-3"><CurrencyDisplay amount={row.cgst} /></td>
                  <td className="px-4 py-3"><CurrencyDisplay amount={row.sgst} /></td>
                  <td className="px-4 py-3"><CurrencyDisplay amount={row.igst} /></td>
                  <td className="px-4 py-3 text-[var(--color-text-muted)]">{row.invoice_count}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  )
}
