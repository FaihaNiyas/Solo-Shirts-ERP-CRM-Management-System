'use client'

import { useQuery } from '@tanstack/react-query'
import { format } from 'date-fns'
import { FileText, Download } from 'lucide-react'
import { apiGet } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'

interface OrderDocument {
  id: number
  kind: string
  reference_type: string
  reference_id: number
  reference_label: string | null
  order_item_id: number | null
  size_bytes: number
  generated_at: string | null
  download_url: string
}

const KIND_LABELS: Record<string, string> = {
  gst_invoice: 'GST Invoice',
  job_card: 'Job Card',
  measurement_card: 'Measurement Card',
  packing_slip: 'Packing Slip',
  delivery_receipt: 'Delivery Receipt',
  pickup_receipt: 'Pickup Receipt',
  report: 'Report',
}

function sizeLabel(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${Math.round(bytes / 1024)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

/** Every PDF generated for the order (invoices, job cards, receipts), newest first. */
export function OrderDocuments({ orderId }: { orderId: number }) {
  const { data: docs, isLoading } = useQuery({
    queryKey: ['orders', orderId, 'documents'],
    queryFn: () => apiGet<OrderDocument[]>(ENDPOINTS.orderDocuments(orderId)),
    select: (res) => res.data,
    enabled: orderId > 0,
  })

  if (isLoading) return null
  if (!docs || docs.length === 0) return null

  return (
    <section className="rounded-xl border border-[var(--color-border)] bg-white p-4 space-y-3">
      <h2 className="text-base font-semibold text-[var(--color-text-primary)]">Documents</h2>
      <div className="space-y-1.5">
        {docs.map((d) => (
          <div
            key={d.id}
            className="flex flex-wrap items-center gap-x-3 gap-y-1 rounded-lg border border-[var(--color-border)] px-3 py-2.5"
          >
            <FileText size={15} strokeWidth={1.75} className="text-[var(--color-text-muted)]" />
            <span className="text-sm font-medium text-[var(--color-text-primary)]">
              {KIND_LABELS[d.kind] ?? d.kind}
            </span>
            {d.reference_label && (
              <span className="ss-mono rounded bg-[var(--color-surface-alt)] px-1.5 py-0.5 text-[11px] font-semibold text-[var(--color-text-secondary)]">
                {d.reference_label}
              </span>
            )}
            <span className="text-xs text-[var(--color-text-muted)]">
              {sizeLabel(d.size_bytes)}
              {d.generated_at ? ` · ${format(new Date(d.generated_at), 'dd MMM yyyy, HH:mm')}` : ''}
            </span>
            <a
              href={d.download_url}
              target="_blank"
              rel="noopener noreferrer"
              className="ml-auto inline-flex items-center gap-1 rounded border border-[var(--color-border-mid)] px-2 py-1 text-xs font-medium text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)]"
            >
              <Download size={12} strokeWidth={1.75} /> Download
            </a>
          </div>
        ))}
      </div>
    </section>
  )
}
