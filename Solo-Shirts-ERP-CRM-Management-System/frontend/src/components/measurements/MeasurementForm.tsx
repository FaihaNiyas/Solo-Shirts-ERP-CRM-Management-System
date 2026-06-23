'use client'

import { useMemo, useState } from 'react'
import { Loader2, Shirt as ShirtIcon } from 'lucide-react'
import { toast } from 'sonner'
import { useCreateMeasurementProfile } from '@/lib/api/hooks/useMeasurements'
import {
  MEASUREMENT_GUIDE,
  dataKeyFor,
  productTypeApiValue,
  type MeasurementGuideField,
  type MeasurementProductType,
} from '@/lib/measurements/measurementGuide'
import { MeasurementVisualGuide } from './MeasurementVisualGuide'

const FIT_PRESETS = ['Regular Fit', 'Slim Fit', 'Loose Fit', 'Custom'] as const

interface CreatedVersion {
  versionId: number
  label: string
  productType: MeasurementProductType
}

/**
 * Visual, bilingual measurement form. Left = input fields; right = a live
 * diagram that highlights the body region of the focused field. Saving creates
 * a new measurement profile + first version (immediately usable — no approval).
 */
export function MeasurementForm({
  customerId,
  familyMemberId = null,
  defaultProductType = 'shirt',
  onCreated,
  onCancel,
}: {
  customerId: number
  familyMemberId?: number | null
  defaultProductType?: MeasurementProductType
  onCreated: (created: CreatedVersion) => void
  onCancel?: () => void
}) {
  const [productType, setProductType] = useState<MeasurementProductType>(defaultProductType)
  const [profileName, setProfileName] = useState<string>('Regular Fit')
  const [values, setValues] = useState<Record<string, string>>({})
  const [notes, setNotes] = useState('')
  const [activeKey, setActiveKey] = useState<string | null>(null)

  const create = useCreateMeasurementProfile(customerId)
  const fields = MEASUREMENT_GUIDE[productType]
  const activeField = useMemo<MeasurementGuideField | null>(
    () => fields.find((f) => f.key === activeKey) ?? null,
    [fields, activeKey],
  )

  const requiredKeys = useMemo(() => fields.filter((f) => f.required).map((f) => f.key), [fields])
  const missingRequired = requiredKeys.filter((k) => !values[k] || values[k].trim() === '')
  const canSave = profileName.trim().length > 0 && missingRequired.length === 0 && !create.isPending

  function switchType(t: MeasurementProductType) {
    setProductType(t)
    setValues({})
    setActiveKey(null)
  }

  function setVal(key: string, raw: string) {
    setValues((v) => ({ ...v, [key]: raw }))
  }

  async function handleSave() {
    const data: Record<string, number> = {}
    for (const f of fields) {
      const raw = values[f.key]
      if (raw !== undefined && raw.trim() !== '' && !Number.isNaN(Number(raw))) {
        data[f.key] = Number(raw)
      }
    }

    const payload: Record<string, unknown> = {
      name: profileName.trim(),
      type: productTypeApiValue(productType),
      family_member_id: familyMemberId,
      [dataKeyFor(productType)]: { ...data, note_1: notes.trim() || undefined },
    }

    try {
      const profile = await create.mutateAsync(payload)
      const current = (profile as { current_version?: { id?: number; version_number?: number } | null }).current_version
      if (!current?.id) {
        toast.error('Measurement saved but could not be selected — pick it from the list.')
        return
      }
      onCreated({
        versionId: current.id,
        label: `${profileName.trim()} · v${current.version_number ?? 1}`,
        productType,
      })
      toast.success('Measurement saved')
    } catch (err: unknown) {
      const e = err as { message?: string; errors?: Record<string, string[]> }
      const first = e?.errors ? Object.values(e.errors)[0]?.[0] : undefined
      toast.error(first ?? e?.message ?? 'Could not save measurement')
    }
  }

  return (
    <div className="space-y-4">
      {/* Product type */}
      <div className="flex gap-2">
        {(['shirt', 'trouser'] as MeasurementProductType[]).map((t) => (
          <button
            key={t}
            type="button"
            onClick={() => switchType(t)}
            className={
              productType === t
                ? 'flex-1 inline-flex items-center justify-center gap-1.5 h-10 rounded-lg bg-[var(--color-brand)] text-sm font-medium text-white'
                : 'flex-1 inline-flex items-center justify-center gap-1.5 h-10 rounded-lg border border-[var(--color-border-mid)] text-sm font-medium text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)]'
            }
          >
            <ShirtIcon size={15} strokeWidth={1.75} /> {t === 'trouser' ? 'Trouser / Pant' : 'Shirt'}
          </button>
        ))}
      </div>

      {/* Profile / fit name */}
      <div>
        <label className="block text-sm font-medium text-[var(--color-text-primary)] mb-1.5">
          Measurement profile <span className="text-[var(--color-text-muted)] font-normal">/ அளவு வகை</span>
        </label>
        <div className="flex flex-wrap gap-1.5 mb-2">
          {FIT_PRESETS.map((p) => (
            <button
              key={p}
              type="button"
              onClick={() => setProfileName(p === 'Custom' ? '' : p)}
              className={
                profileName === p || (p === 'Custom' && !FIT_PRESETS.includes(profileName as typeof FIT_PRESETS[number]))
                  ? 'rounded-full bg-[var(--color-brand)] px-3 h-8 text-xs font-medium text-white'
                  : 'rounded-full border border-[var(--color-border-mid)] px-3 h-8 text-xs font-medium text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)]'
              }
            >
              {p}
            </button>
          ))}
        </div>
        <input
          value={profileName}
          onChange={(e) => setProfileName(e.target.value)}
          placeholder="e.g. Regular Fit"
          className="w-full h-10 px-3 text-sm border border-[var(--color-border-mid)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)]"
        />
      </div>

      {/* Left fields · right visual guide */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div className="space-y-2.5">
          {fields.map((f) => (
            <label key={f.key} className="block">
              <span className="block text-xs font-medium text-[var(--color-text-primary)]">
                {f.label} <span className="text-[var(--color-text-muted)] font-normal">/ {f.label_ta}</span>
                {f.required && <span className="ml-0.5 text-[var(--color-danger)]">*</span>}
              </span>
              <div className="mt-1 flex items-center gap-2">
                <input
                  type="number"
                  inputMode="decimal"
                  min={0}
                  max={100}
                  step="0.25"
                  value={values[f.key] ?? ''}
                  onFocus={() => setActiveKey(f.key)}
                  onChange={(e) => setVal(f.key, e.target.value)}
                  className={
                    'w-full h-9 px-3 text-sm rounded-lg border bg-white focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)] ' +
                    (activeKey === f.key ? 'border-[var(--color-brand)]' : 'border-[var(--color-border-mid)]')
                  }
                />
                <span className="text-xs text-[var(--color-text-muted)] w-7 shrink-0">{f.unit}</span>
              </div>
            </label>
          ))}

          <label className="block pt-1">
            <span className="block text-xs font-medium text-[var(--color-text-primary)]">
              Notes <span className="text-[var(--color-text-muted)] font-normal">/ குறிப்பு</span>
            </span>
            <textarea
              value={notes}
              onChange={(e) => setNotes(e.target.value)}
              rows={2}
              maxLength={500}
              className="mt-1 w-full px-3 py-2 text-sm border border-[var(--color-border-mid)] rounded-lg resize-none focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)]"
            />
          </label>
        </div>

        <div className="md:sticky md:top-2 self-start">
          <MeasurementVisualGuide productType={productType} activeField={activeField} />
        </div>
      </div>

      {missingRequired.length > 0 && (
        <p className="text-xs text-[var(--color-warning)]">
          Required: {fields.filter((f) => missingRequired.includes(f.key)).map((f) => f.label).join(', ')}.
        </p>
      )}

      <div className="flex justify-end gap-2">
        {onCancel && (
          <button
            type="button"
            onClick={onCancel}
            className="px-4 h-10 rounded-lg border border-[var(--color-border-mid)] text-sm font-medium text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)]"
          >
            Cancel
          </button>
        )}
        <button
          type="button"
          onClick={handleSave}
          disabled={!canSave}
          className="inline-flex items-center gap-1.5 px-5 h-10 rounded-lg bg-[var(--color-brand)] text-sm font-semibold text-white hover:bg-[var(--color-brand-dark)] disabled:opacity-40 disabled:cursor-not-allowed"
        >
          {create.isPending && <Loader2 size={15} className="animate-spin" />}
          Save measurement
        </button>
      </div>
    </div>
  )
}
