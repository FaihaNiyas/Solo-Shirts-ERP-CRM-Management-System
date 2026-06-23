'use client'

import { memo, useState } from 'react'
import { Ruler, Copy, Trash2, FileText, Printer, Lock, Check, AlertTriangle } from 'lucide-react'
import { cn } from '@/lib/utils'
import { FABRIC_OPTIONS, STYLE_OPTIONS, FIT_OPTIONS } from './catalog'
import type { CatalogOption, SubOrderDraft } from './types'
import { isSubOrderComplete } from './validation'
import { MeasurementPickerModal } from './MeasurementPickerModal'

interface SubOrderCardProps {
  index: number
  sub: SubOrderDraft
  memberLabel: string
  customerId: number
  canRemove: boolean
  /** Once the order is created, sub-order details are fixed (edit in earlier steps). */
  locked: boolean
  // Stable callbacks from the wizard (take the card's tempId) so this card can be
  // memoized — editing one card no longer re-renders its siblings.
  onPatch: (tempId: string, patch: Partial<SubOrderDraft>) => void
  onDuplicate: (tempId: string) => void
  onRemove: (tempId: string) => void
}

export const SubOrderCard = memo(function SubOrderCard({
  index,
  sub,
  memberLabel,
  customerId,
  canRemove,
  locked,
  onPatch,
  onDuplicate,
  onRemove,
}: SubOrderCardProps) {
  const [pickMeasurement, setPickMeasurement] = useState(false)
  const code = String(index + 1).padStart(2, '0')
  const complete = isSubOrderComplete(sub)
  const productType: 'shirt' | 'trouser' = sub.productType ?? 'shirt'

  function pickCatalog(
    options: CatalogOption[],
    id: string,
    idKey: 'fabricId' | 'styleId' | 'fitId',
    labelKey: 'fabricLabel' | 'styleLabel' | 'fitLabel',
  ) {
    const opt = options.find((o) => o.id === id) ?? null
    onPatch(sub.tempId, { [idKey]: opt?.id ?? null, [labelKey]: opt?.label ?? null } as Partial<SubOrderDraft>)
  }

  return (
    <div
      className={cn(
        'rounded-2xl border bg-white p-4',
        complete ? 'border-[var(--color-border)]' : 'border-[var(--color-border)]',
      )}
    >
      {/* Header */}
      <div className="flex items-start justify-between gap-3 mb-3">
        <div className="min-w-0">
          <p className="ss-mono text-sm font-semibold text-[var(--color-text-primary)]">Sub-order {code}</p>
          <p className="text-xs text-[var(--color-text-muted)] truncate">
            {memberLabel || 'Self'} · 1 {productType === 'trouser' ? 'trouser' : 'shirt'}
          </p>
        </div>
        {complete ? (
          <span className="inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-0.5 text-[11px] font-medium text-green-700 shrink-0">
            <Check size={11} strokeWidth={2.5} /> Ready
          </span>
        ) : (
          <span className="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-medium text-amber-800 shrink-0">
            <AlertTriangle size={11} strokeWidth={2} /> Incomplete
          </span>
        )}
      </div>

      {/* Chips */}
      <div className="flex flex-wrap gap-1.5 mb-3">
        <Chip label="Measurement" value={sub.measurementLabel} ok={Boolean(sub.measurementVersionId)} />
        <Chip label="Fabric" value={sub.fabricLabel} ok={Boolean(sub.fabricId)} />
        <Chip label="Style" value={sub.styleLabel} ok={Boolean(sub.styleId)} />
        <Chip label="Fit" value={sub.fitLabel} ok={Boolean(sub.fitId)} />
      </div>

      {/* Product type */}
      <div className="mb-3 flex gap-2">
        {(['shirt', 'trouser'] as const).map((t) => (
          <button
            key={t}
            type="button"
            disabled={locked}
            onClick={() => onPatch(sub.tempId, { productType: t, measurementVersionId: null, measurementLabel: null, measurementStatus: null })}
            className={cn(
              'inline-flex items-center justify-center h-8 px-3 rounded-lg text-xs font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed',
              productType === t
                ? 'bg-[var(--color-brand)] text-white'
                : 'border border-[var(--color-border-mid)] text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)]',
            )}
          >
            {t === 'trouser' ? 'Trouser' : 'Shirt'}
          </button>
        ))}
      </div>

      {/* Measurement */}
      <div className="mb-3">
        <button
          type="button"
          onClick={() => setPickMeasurement(true)}
          disabled={locked}
          className="inline-flex items-center gap-2 rounded-lg border border-[var(--color-border-mid)] px-3 h-9 text-sm text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >
          <Ruler size={15} strokeWidth={1.75} />
          {sub.measurementVersionId ? 'Change Measurement' : 'Select Measurement'}
        </button>
      </div>

      {/* Catalog selectors */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-2 mb-3">
        <CatalogSelect
          label="Fabric"
          value={sub.fabricId}
          options={FABRIC_OPTIONS}
          disabled={locked}
          onChange={(id) => pickCatalog(FABRIC_OPTIONS, id, 'fabricId', 'fabricLabel')}
        />
        <CatalogSelect
          label="Style"
          value={sub.styleId}
          options={STYLE_OPTIONS}
          disabled={locked}
          onChange={(id) => pickCatalog(STYLE_OPTIONS, id, 'styleId', 'styleLabel')}
        />
        <CatalogSelect
          label="Fit"
          value={sub.fitId}
          options={FIT_OPTIONS}
          disabled={locked}
          onChange={(id) => pickCatalog(FIT_OPTIONS, id, 'fitId', 'fitLabel')}
        />
      </div>

      {/* Notes */}
      <input
        value={sub.notes ?? ''}
        onChange={(e) => onPatch(sub.tempId, { notes: e.target.value })}
        disabled={locked}
        placeholder="Special notes for this shirt (optional)"
        className="w-full h-9 px-3 mb-3 text-sm border border-[var(--color-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)] disabled:bg-[var(--color-surface-alt)] disabled:cursor-not-allowed"
      />

      {/* Phase 2 state chips (managed in the Print step) */}
      <div className="flex flex-wrap items-center gap-1.5 mb-3">
        <StateChip icon={FileText} label={sub.pdfStatus === 'generated' ? 'PDF ready' : 'PDF pending'} ok={sub.pdfStatus === 'generated'} />
        <StateChip icon={Printer} label={sub.printStatus === 'printed' ? 'Printed' : 'Not printed'} ok={sub.printStatus === 'printed'} />
      </div>

      {/* Actions */}
      <div className="flex flex-wrap items-center gap-2 border-t border-[var(--color-border)] pt-3">
        {locked ? (
          <span className="inline-flex items-center gap-1.5 text-xs text-[var(--color-text-muted)]">
            <Lock size={12} strokeWidth={1.75} /> Order created — manage print in the Print step.
          </span>
        ) : (
          <>
            <button
              type="button"
              onClick={() => onDuplicate(sub.tempId)}
              className="inline-flex items-center gap-1.5 rounded-lg border border-[var(--color-border-mid)] px-3 h-8 text-xs font-medium text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] transition-colors"
            >
              <Copy size={13} strokeWidth={1.75} /> Duplicate
            </button>
            <button
              type="button"
              onClick={() => onRemove(sub.tempId)}
              disabled={!canRemove}
              title={canRemove ? 'Remove this shirt' : 'An order needs at least one shirt'}
              className="ml-auto inline-flex items-center gap-1.5 rounded-lg px-3 h-8 text-xs font-medium text-[var(--color-danger)] hover:bg-red-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
            >
              <Trash2 size={13} strokeWidth={1.75} /> Remove
            </button>
          </>
        )}
      </div>

      <MeasurementPickerModal
        open={pickMeasurement}
        onClose={() => setPickMeasurement(false)}
        customerId={customerId}
        productType={productType}
        selectedVersionId={sub.measurementVersionId}
        onPick={(versionId, label, status) =>
          onPatch(sub.tempId, { measurementVersionId: versionId, measurementLabel: label, measurementStatus: status })
        }
      />
    </div>
  )
})

function Chip({ label, value, ok }: { label: string; value: string | null; ok: boolean }) {
  return (
    <span
      className={cn(
        'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-medium max-w-[200px]',
        ok ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800',
      )}
    >
      <span className="shrink-0">{label}:</span>
      <span className="truncate">{ok && value ? value : 'Select'}</span>
    </span>
  )
}

function CatalogSelect({
  label,
  value,
  options,
  disabled,
  onChange,
}: {
  label: string
  value: string | null
  options: CatalogOption[]
  disabled?: boolean
  onChange: (id: string) => void
}) {
  return (
    <label className="block">
      <span className="sr-only">{label}</span>
      <select
        value={value ?? ''}
        disabled={disabled}
        onChange={(e) => onChange(e.target.value)}
        className={cn(
          'w-full h-9 px-3 text-sm rounded-lg border bg-white focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)]',
          'disabled:bg-[var(--color-surface-alt)] disabled:cursor-not-allowed',
          value ? 'border-[var(--color-border)]' : 'border-amber-300 text-[var(--color-text-muted)]',
        )}
      >
        <option value="" disabled>
          {label}…
        </option>
        {options.map((o) => (
          <option key={o.id} value={o.id}>
            {o.label}
          </option>
        ))}
      </select>
    </label>
  )
}

function StateChip({ icon: Icon, label, ok }: { icon: React.ElementType; label: string; ok: boolean }) {
  return (
    <span
      className={cn(
        'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-medium',
        ok ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500',
      )}
    >
      <Icon size={11} strokeWidth={1.75} />
      {label}
    </span>
  )
}
