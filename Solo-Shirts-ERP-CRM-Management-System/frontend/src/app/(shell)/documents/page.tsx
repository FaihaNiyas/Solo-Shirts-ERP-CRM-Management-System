'use client'

import { useQuery } from '@tanstack/react-query'
import { apiGet } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { PageHeader } from '@/components/ui/page-header'
import { TableSkeleton } from '@/components/ui/loading-skeleton'
import { EmptyState } from '@/components/ui/empty-state'
import { Download, FileText, type LucideIcon } from 'lucide-react'
import { format } from 'date-fns'

interface Document {
  id: number
  kind?: string
  reference_type?: string
  reference_id?: number
  generated_at?: string
  download_url?: string
}

export default function DocumentsPage() {
  const { data: docs = [], isLoading } = useQuery<Document[]>({
    queryKey: ['documents'],
    queryFn: () => apiGet<Document[]>(ENDPOINTS.documents).then((r) => r.data),
  })

  return (
    <div className="space-y-6">
      <PageHeader title="Documents" subtitle="Download invoices, job cards and reports" />

      {isLoading && <TableSkeleton rows={5} cols={4} />}

      {!isLoading && docs.length === 0 && (
        <EmptyState
          title="No documents"
          description="Generated documents will appear here"
          icon={FileText as LucideIcon}
        />
      )}

      {!isLoading && docs.length > 0 && (
        <div className="rounded-xl border border-[var(--color-border)] overflow-hidden">
          <div className="divide-y divide-[var(--color-border)] bg-white">
            {docs.map((doc) => (
              <div key={doc.id} className="flex items-center gap-4 px-4 py-3">
                <FileText size={18} strokeWidth={1.75} className="text-[var(--color-text-muted)] shrink-0" />
                <div className="flex-1 min-w-0">
                  <p className="text-sm font-medium text-[var(--color-text-primary)] capitalize">
                    {doc.kind?.replace(/_/g, ' ') ?? 'Document'}
                  </p>
                  {doc.reference_id && (
                    <p className="text-xs font-mono text-[var(--color-text-muted)]">
                      Ref: #{doc.reference_id}
                    </p>
                  )}
                </div>
                <span className="text-xs text-[var(--color-text-muted)]">
                  {doc.generated_at ? format(new Date(doc.generated_at), 'dd MMM yyyy') : '—'}
                </span>
                <a
                  href={doc.download_url}
                  target="_blank"
                  rel="noopener noreferrer"
                  className={`flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium border rounded-lg transition-colors ${
                    doc.download_url
                      ? 'border-[var(--color-brand)] text-[var(--color-brand)] hover:bg-[var(--color-brand-light)]'
                      : 'border-[var(--color-border)] text-[var(--color-text-muted)] pointer-events-none opacity-50'
                  }`}
                >
                  <Download size={13} strokeWidth={1.75} /> Download
                </a>
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  )
}
