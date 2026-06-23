'use client'

import { useState } from 'react'
import { ChevronDown, Plus, Ruler } from 'lucide-react'
import { format } from 'date-fns'
import { useMeasurements, useMeasurementVersions } from '@/lib/api/hooks/useMeasurements'
import { DrawerPanel } from '@/components/ui/drawer-panel'
import { EmptyState } from '@/components/ui/empty-state'
import { MeasurementForm } from '@/components/measurements/MeasurementForm'
import { productTypeApiValue, type MeasurementProductType } from '@/lib/measurements/measurementGuide'
import { cn } from '@/lib/utils'
import type { MeasurementProfile } from '@/lib/api/schemas/measurements'

interface MeasurementPickerModalProps {
  open: boolean
  onClose: () => void
  customerId: number
  productType?: MeasurementProductType
  selectedVersionId: number | null
  onPick: (versionId: number, label: string, status: string) => void
}

function profileLabel(p: MeasurementProfile): string {
  return p.label ?? p.name ?? 'Measurements'
}

function ProfileBlock({
  profile,
  selectedVersionId,
  onPick,
}: {
  profile: MeasurementProfile
  selectedVersionId: number | null
  onPick: (versionId: number, label: string, status: string) => void
}) {
  const [open, setOpen] = useState(false)
  const { data: versions = [], isLoading } = useMeasurementVersions(open ? profile.id : 0)

  return (
    <div className="border border-[var(--color-border)] rounded-xl overflow-hidden">
      <button
        type="button"
        className="w-full flex items-center justify-between p-3 text-left hover:bg-[var(--color-surface-alt)] transition-colors"
        onClick={() => setOpen((v) => !v)}
      >
        <p className="text-sm font-medium text-[var(--color-text-primary)]">{profileLabel(profile)}</p>
        <ChevronDown
          size={15}
          strokeWidth={1.75}
          className={cn('text-[var(--color-text-muted)] transition-transform', open && 'rotate-180')}
        />
      </button>

      {open && (
        <div className="space-y-1.5 p-3 border-t border-[var(--color-border)] bg-[var(--color-surface-alt)]">
          {isLoading && <p className="text-xs text-[var(--color-text-muted)]">Loading versions…</p>}
          {!isLoading && versions.length === 0 && (
            <p className="text-xs text-[var(--color-text-muted)]">No versions yet</p>
          )}
          {versions.map((v) => {
            const date = v.created_at ? format(new Date(v.created_at), 'dd MMM yyyy') : null
            // Name + date — no internal version numbers in front of the user.
            const label = date ? `${profileLabel(profile)} · ${date}` : profileLabel(profile)
            return (
              <button
                key={v.id}
                type="button"
                onClick={() => onPick(v.id, label, v.status ?? 'usable')}
                className={cn(
                  'w-full flex items-center gap-2.5 p-2.5 rounded-lg border text-left transition-colors',
                  selectedVersionId === v.id
                    ? 'border-[var(--color-brand)] bg-[var(--color-brand-light)]'
                    : 'border-[var(--color-border)] bg-white hover:border-[var(--color-brand)]',
                )}
              >
                <Ruler size={13} strokeWidth={1.75} className="shrink-0 text-[var(--color-text-muted)]" />
                <span className="flex-1 text-sm font-medium text-[var(--color-text-primary)]">
                  {profileLabel(profile)}
                </span>
                <span className="text-xs text-[var(--color-text-muted)]">{date ?? 'Saved'}</span>
                {selectedVersionId === v.id && (
                  <span className="rounded-full bg-[var(--color-brand)] px-2 py-0.5 text-[10px] font-medium text-white">Selected</span>
                )}
              </button>
            )
          })}
        </div>
      )}
    </div>
  )
}

export function MeasurementPickerModal({
  open,
  onClose,
  customerId,
  productType = 'shirt',
  selectedVersionId,
  onPick,
}: MeasurementPickerModalProps) {
  const { data: profiles = [], isLoading } = useMeasurements(open ? customerId : 0)
  const [creating, setCreating] = useState(false)

  // Only show profiles usable for this sub-order's product type (or 'both').
  const apiType = productTypeApiValue(productType)
  const visible = profiles.filter((p) => !p.type || p.type === apiType || p.type === 'both')

  function handlePick(versionId: number, label: string, status: string) {
    onPick(versionId, label, status)
    onClose()
  }

  return (
    <DrawerPanel
      open={open}
      onClose={() => {
        setCreating(false)
        onClose()
      }}
      title={creating ? 'New measurement' : 'Select measurement'}
      size={creating ? 'xl' : 'md'}
    >
      {creating ? (
        <MeasurementForm
          customerId={customerId}
          defaultProductType={productType}
          onCancel={() => setCreating(false)}
          onCreated={({ versionId, label }) => {
            setCreating(false)
            handlePick(versionId, label, 'usable')
          }}
        />
      ) : (
        <div className="space-y-2">
          <button
            type="button"
            onClick={() => setCreating(true)}
            className="w-full inline-flex items-center justify-center gap-2 rounded-lg border border-dashed border-[var(--color-brand)] py-2.5 text-sm font-medium text-[var(--color-brand)] hover:bg-[var(--color-brand-light)] transition-colors"
          >
            <Plus size={15} strokeWidth={2} /> New measurement (with visual guide)
          </button>

          {isLoading && <p className="text-sm text-[var(--color-text-muted)]">Loading…</p>}
          {!isLoading && visible.length === 0 && (
            <EmptyState
              title="No measurements yet"
              description="Create one with the visual guide above — it's usable immediately, no approval needed."
            />
          )}
          {visible.map((p) => (
            <ProfileBlock key={p.id} profile={p} selectedVersionId={selectedVersionId} onPick={handlePick} />
          ))}

          {!isLoading && visible.length > 0 && (
            <p className="flex items-center gap-1.5 pt-1 text-[11px] text-[var(--color-text-muted)]">
              <Ruler size={11} strokeWidth={1.75} /> Measurements are usable immediately — no approval required.
            </p>
          )}
        </div>
      )}
    </DrawerPanel>
  )
}
