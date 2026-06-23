'use client'

import { useQuery } from '@tanstack/react-query'
import { ModalDialog } from '@/components/ui/modal-dialog'
import { apiGet } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'

interface MeasurementVersion {
  id: number
  version_number: number
  status: string
  shirt_data: Record<string, unknown> | null
  pant_data: Record<string, unknown> | null
}

interface Props {
  versionId: number
  itemCode: string
  open: boolean
  onClose: () => void
}

function humanize(key: string): string {
  return key.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())
}

/** Read-only measurement snapshot (chest/waist/length …) used to make a shirt. */
export function MeasurementViewModal({ versionId, itemCode, open, onClose }: Props) {
  const { data: version, isLoading } = useQuery({
    queryKey: ['measurement-version', versionId],
    queryFn: () => apiGet<MeasurementVersion>(ENDPOINTS.measurementVersion(versionId)),
    select: (res) => res.data,
    enabled: open && versionId > 0,
  })

  return (
    <ModalDialog open={open} onClose={onClose} title={`Measurements — ${itemCode}`}>
      {isLoading ? (
        <p className="py-6 text-center text-sm text-[var(--color-text-muted)]">Loading…</p>
      ) : !version ? (
        <p className="py-6 text-center text-sm text-[var(--color-text-muted)]">No measurement on file for this item.</p>
      ) : (
        <div className="space-y-4">
          <p className="text-xs text-[var(--color-text-muted)]">
            Version v{version.version_number} · {version.status}
          </p>
          <MeasureGroup title="Shirt" data={version.shirt_data} />
          <MeasureGroup title="Pant" data={version.pant_data} />
        </div>
      )}
    </ModalDialog>
  )
}

function MeasureGroup({ title, data }: { title: string; data: Record<string, unknown> | null }) {
  const entries = Object.entries(data ?? {}).filter(([, v]) => v !== null && v !== '' && typeof v !== 'object')
  if (entries.length === 0) return null
  return (
    <div>
      <p className="mb-1.5 text-xs font-semibold uppercase tracking-wide text-[var(--color-text-muted)]">{title}</p>
      <dl className="grid grid-cols-2 gap-x-4 gap-y-1.5 sm:grid-cols-3">
        {entries.map(([key, value]) => (
          <div key={key} className="rounded-lg border border-[var(--color-border)] px-2.5 py-1.5">
            <dt className="text-[11px] text-[var(--color-text-muted)]">{humanize(key)}</dt>
            <dd className="text-sm font-semibold text-[var(--color-text-primary)]">{String(value)}</dd>
          </div>
        ))}
      </dl>
    </div>
  )
}
