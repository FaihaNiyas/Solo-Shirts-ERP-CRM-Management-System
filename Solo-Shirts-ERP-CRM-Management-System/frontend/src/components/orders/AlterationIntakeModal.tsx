'use client'

import { useEffect, useState } from 'react'
import { Scissors, AlertTriangle, Info, Loader2, Upload, X } from 'lucide-react'
import { toast } from 'sonner'
import { DrawerPanel } from '@/components/ui/drawer-panel'
import { FormField, Input, Textarea } from '@/components/ui/form-field'
import {
  ALTERATION_ISSUE_LABELS,
  ALTERATION_ISSUE_TYPES,
  useCreateAlteration,
  type AlterationIssueType,
  type AlterationPriority,
} from '@/lib/api/hooks/useAlterations'

export interface AlterationIntakeItem {
  id: number
  item_code: string
  product_type?: string | null
}

const MAX_PHOTO_BYTES = 5 * 1024 * 1024

export function AlterationIntakeModal({
  open,
  onClose,
  items,
  onCreated,
}: {
  open: boolean
  onClose: () => void
  items: AlterationIntakeItem[]
  onCreated?: () => void
}) {
  const create = useCreateAlteration()

  const [itemId, setItemId] = useState<number | null>(items[0]?.id ?? null)
  const [issueType, setIssueType] = useState<AlterationIssueType>('fitting_issue')
  const [description, setDescription] = useState('')
  const [priority, setPriority] = useState<AlterationPriority>('normal')
  const [chargeRequired, setChargeRequired] = useState(false)
  const [charge, setCharge] = useState('')
  const [photo, setPhoto] = useState<File | null>(null)
  const [photoError, setPhotoError] = useState<string | null>(null)

  // Reset the form each time the drawer opens.
  useEffect(() => {
    if (open) {
      setItemId(items[0]?.id ?? null)
      setIssueType('fitting_issue')
      setDescription('')
      setPriority('normal')
      setChargeRequired(false)
      setCharge('')
      setPhoto(null)
      setPhotoError(null)
    }
  }, [open, items])

  function onPickPhoto(file: File | null) {
    setPhotoError(null)
    if (!file) {
      setPhoto(null)
      return
    }
    if (!/\.(jpe?g|png|webp)$/i.test(file.name)) {
      setPhotoError('Only JPG, PNG, or WEBP images are allowed.')
      return
    }
    if (file.size > MAX_PHOTO_BYTES) {
      setPhotoError('Image must be 5 MB or smaller.')
      return
    }
    setPhoto(file)
  }

  const canSubmit =
    itemId !== null && description.trim().length > 0 && !photoError && !create.isPending

  async function handleSubmit() {
    if (itemId === null) return
    try {
      const res = await create.mutateAsync({
        original_order_item_id: itemId,
        issue_type: issueType,
        issue_description: description.trim(),
        priority,
        charge_required: chargeRequired,
        estimated_charge: chargeRequired && charge !== '' ? Number(charge) : null,
        photo,
      })
      toast.success(`Alteration logged for ${res.original_item_code ?? 'sub-order'}`)
      onCreated?.()
      onClose()
    } catch (err: unknown) {
      const e = err as { message?: string; errors?: Record<string, string[]> }
      const first = e?.errors ? Object.values(e.errors)[0]?.[0] : undefined
      toast.error(first ?? e?.message ?? 'Failed to create alteration')
    }
  }

  return (
    <DrawerPanel
      open={open}
      onClose={onClose}
      title="Customer Alteration After Delivery"
      description="Log a fitting or stitching correction for a delivered shirt."
      size="md"
    >
      <div className="space-y-4">
        {/* Context banner — this is NOT internal QC rework. */}
        <div className="flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
          <Info size={14} strokeWidth={1.75} className="mt-0.5 shrink-0" />
          <span>
            This is a <strong>customer alteration after delivery</strong> — separate from internal QC
            rework. It does not change the original order, invoice, or production stage. Estimated
            charge only; payment is handled separately.
          </span>
        </div>

        {items.length === 0 ? (
          <div className="flex items-center gap-2 rounded-lg border border-[var(--color-border-mid)] bg-[var(--color-surface-alt)] px-3 py-3 text-sm text-[var(--color-text-muted)]">
            <AlertTriangle size={15} strokeWidth={1.75} />
            No delivered sub-orders are eligible for alteration yet.
          </div>
        ) : (
          <>
            <FormField label="Delivered sub-order" required>
              <select
                value={itemId ?? ''}
                onChange={(e) => setItemId(Number(e.target.value))}
                className="w-full h-10 px-3 text-sm border border-[var(--color-border-mid)] rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)]"
              >
                {items.map((it) => (
                  <option key={it.id} value={it.id}>
                    {it.item_code}
                    {it.product_type ? ` · ${it.product_type}` : ''}
                  </option>
                ))}
              </select>
            </FormField>

            <FormField label="Issue type" required>
              <select
                value={issueType}
                onChange={(e) => setIssueType(e.target.value as AlterationIssueType)}
                className="w-full h-10 px-3 text-sm border border-[var(--color-border-mid)] rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)]"
              >
                {ALTERATION_ISSUE_TYPES.map((t) => (
                  <option key={t} value={t}>
                    {ALTERATION_ISSUE_LABELS[t]}
                  </option>
                ))}
              </select>
            </FormField>

            <FormField label="What needs altering?" required hint="Describe the fit/stitch problem the customer reported.">
              <Textarea
                value={description}
                onChange={(e) => setDescription(e.target.value)}
                rows={4}
                maxLength={1000}
                placeholder="e.g. Right sleeve is 1 cm too long; waist a little loose."
              />
            </FormField>

            <FormField label="Priority">
              <div className="flex gap-2">
                {(['normal', 'urgent'] as AlterationPriority[]).map((p) => (
                  <button
                    key={p}
                    type="button"
                    onClick={() => setPriority(p)}
                    className={
                      priority === p
                        ? 'flex-1 h-10 rounded-lg text-sm font-medium capitalize bg-[var(--color-brand)] text-white'
                        : 'flex-1 h-10 rounded-lg text-sm font-medium capitalize border border-[var(--color-border-mid)] text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)]'
                    }
                  >
                    {p}
                  </button>
                ))}
              </div>
            </FormField>

            <div className="rounded-lg border border-[var(--color-border)] px-3 py-3 space-y-3">
              <label className="flex items-center gap-2 text-sm text-[var(--color-text-primary)]">
                <input
                  type="checkbox"
                  checked={chargeRequired}
                  onChange={(e) => setChargeRequired(e.target.checked)}
                  className="h-4 w-4 rounded border-[var(--color-border-mid)] accent-[var(--color-brand)]"
                />
                This alteration will be charged
              </label>
              {chargeRequired && (
                <FormField label="Estimated charge (₹)" hint="Estimate only — no payment is collected now.">
                  <Input
                    type="number"
                    min={0}
                    step="1"
                    value={charge}
                    onChange={(e) => setCharge(e.target.value)}
                    placeholder="0"
                  />
                </FormField>
              )}
            </div>

            <FormField label="Reference photo (optional)" error={photoError ?? undefined}>
              {photo ? (
                <div className="flex items-center gap-2 rounded-lg border border-[var(--color-border-mid)] px-3 py-2 text-sm">
                  <span className="truncate text-[var(--color-text-secondary)]">{photo.name}</span>
                  <button
                    type="button"
                    onClick={() => setPhoto(null)}
                    className="ml-auto text-[var(--color-text-muted)] hover:text-[var(--color-danger)]"
                    aria-label="Remove photo"
                  >
                    <X size={15} strokeWidth={1.75} />
                  </button>
                </div>
              ) : (
                <label className="flex cursor-pointer items-center gap-2 rounded-lg border border-dashed border-[var(--color-border-mid)] px-3 py-2.5 text-sm text-[var(--color-text-muted)] hover:bg-[var(--color-surface-alt)]">
                  <Upload size={15} strokeWidth={1.75} />
                  Attach a JPG/PNG/WEBP (max 5 MB)
                  <input
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    className="hidden"
                    onChange={(e) => onPickPhoto(e.target.files?.[0] ?? null)}
                  />
                </label>
              )}
            </FormField>

            <div className="flex justify-end gap-2 pt-1">
              <button
                type="button"
                onClick={onClose}
                className="px-4 h-10 rounded-lg border border-[var(--color-border-mid)] text-sm font-medium text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] transition-colors"
              >
                Cancel
              </button>
              <button
                type="button"
                onClick={handleSubmit}
                disabled={!canSubmit}
                className="inline-flex items-center gap-1.5 px-5 h-10 rounded-lg bg-[var(--color-brand)] text-sm font-semibold text-white hover:bg-[var(--color-brand-dark)] disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
              >
                {create.isPending ? <Loader2 size={15} className="animate-spin" /> : <Scissors size={15} strokeWidth={1.75} />}
                Create Alteration
              </button>
            </div>
          </>
        )}
      </div>
    </DrawerPanel>
  )
}
