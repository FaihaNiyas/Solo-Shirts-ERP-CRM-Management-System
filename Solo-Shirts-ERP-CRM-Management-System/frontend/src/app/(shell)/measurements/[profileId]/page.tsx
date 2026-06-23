'use client'

import { useState, use, useCallback, useEffect, useRef } from 'react'
import { toast } from 'sonner'
import { motion, AnimatePresence, useReducedMotion } from 'framer-motion'
import { CheckCircle2, ChevronDown, Shirt, Scissors, StickyNote, Save } from 'lucide-react'
import {
  useMeasurementVersions,
  useCreateMeasurementVersion,
} from '@/lib/api/hooks/useMeasurements'
import type { MeasurementVersion } from '@/lib/api/schemas/measurements'
import { MeasurementGuideAnimator } from '@/components/measurements/MeasurementGuideAnimator'
import { usePreferences } from '@/lib/preferences/PreferencesContext'
import { cn } from '@/lib/utils'

// ─── Field definitions ────────────────────────────────────────────────────────

const SHIRT_FIELDS: { key: string; label: string }[] = [
  { key: 'chest',        label: 'Chest' },
  { key: 'waist',        label: 'Waist' },
  { key: 'hip',          label: 'Hip' },
  { key: 'shoulder',     label: 'Shoulder' },
  { key: 'sleeve_length',label: 'Sleeve Length' },
  { key: 'shirt_length', label: 'Shirt Length' },
  { key: 'collar',       label: 'Collar' },
  { key: 'cuff',         label: 'Cuff' },
  { key: 'arm_round',    label: 'Arm Round' },
  { key: 'neck',         label: 'Neck' },
  { key: 'front_chest',  label: 'Front Chest' },
  { key: 'cross_back',   label: 'Cross Back' },
  { key: 'dart',         label: 'Dart' },
  { key: 'bicep',        label: 'Bicep' },
  { key: 'wrist',        label: 'Wrist' },
]

const PANT_FIELDS: { key: string; label: string }[] = [
  { key: 'pant_waist',   label: 'Waist' },
  { key: 'pant_hip',     label: 'Hip' },
  { key: 'thigh',        label: 'Thigh' },
  { key: 'knee',         label: 'Knee' },
  { key: 'bottom',       label: 'Bottom' },
  { key: 'pant_length',  label: 'Length' },
  { key: 'in_seam',      label: 'In-Seam' },
  { key: 'out_seam',     label: 'Out-Seam' },
  { key: 'crotch',       label: 'Crotch' },
  { key: 'fly',          label: 'Fly' },
]

const ALL_FIELDS = [...SHIRT_FIELDS, ...PANT_FIELDS]

// Pant form keys are prefixed (pant_waist / pant_hip / pant_length) while the
// stored pant_data uses bare keys (waist / hip / length). Read both so prefill
// is robust whichever way the backend stored them.
const PANT_DATA_KEY: Record<string, string> = {
  pant_waist: 'waist',
  pant_hip: 'hip',
  pant_length: 'length',
}

/** Build the initial `values` map from a saved version's shirt_data / pant_data. */
function seedFromVersion(v: MeasurementVersion): Record<string, string> {
  const out: Record<string, string> = {}
  const shirt = (v.shirt_data ?? {}) as Record<string, unknown>
  for (const f of SHIRT_FIELDS) {
    const raw = shirt[f.key]
    if (typeof raw === 'number' && raw > 0) out[f.key] = String(raw)
  }
  const pant = (v.pant_data ?? {}) as Record<string, unknown>
  for (const f of PANT_FIELDS) {
    const raw = pant[f.key] ?? pant[PANT_DATA_KEY[f.key] ?? f.key]
    if (typeof raw === 'number' && raw > 0) out[f.key] = String(raw)
  }
  return out
}

// ─── Completion ring ──────────────────────────────────────────────────────────

function CompletionRing({ filled, total }: { filled: number; total: number }) {
  const pct = total === 0 ? 0 : filled / total
  const r = 22
  const circ = 2 * Math.PI * r
  const dash = circ * pct
  const prefersReduced = useReducedMotion()

  return (
    <div className="relative flex items-center justify-center" style={{ width: 56, height: 56 }}>
      <svg width="56" height="56" viewBox="0 0 56 56" className="-rotate-90">
        <circle cx="28" cy="28" r={r} fill="none" stroke="var(--color-border-soft)" strokeWidth="3" />
        <motion.circle
          cx="28" cy="28" r={r}
          fill="none"
          stroke="var(--color-brand)"
          strokeWidth="3"
          strokeLinecap="round"
          strokeDasharray={circ}
          animate={{ strokeDashoffset: circ - dash }}
          transition={{ duration: prefersReduced ? 0 : 0.5, ease: 'easeOut' }}
        />
      </svg>
      <span className="absolute text-[11px] font-bold text-[var(--color-brand)] tabular-nums">
        {filled}/{total}
      </span>
    </div>
  )
}

// ─── Floating-label input ─────────────────────────────────────────────────────

function MeasurementInput({
  field, label, value, unit, onChange, onFocus, onBlur, index,
}: {
  field: string; label: string; value: string; unit: string
  onChange: (v: string) => void; onFocus: () => void; onBlur: () => void; index: number
}) {
  const [focused, setFocused] = useState(false)
  const prefersReduced = useReducedMotion()
  const hasValue = value !== '' && value !== '0' && value !== '0.0'
  const lifted = focused || hasValue

  return (
    <motion.div
      initial={{ opacity: 0, y: 12 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: prefersReduced ? 0 : 0.22, delay: prefersReduced ? 0 : index * 0.03 }}
      className="relative"
    >
      <div
        className={cn(
          'relative rounded-xl border transition-all duration-200',
          focused
            ? 'border-[var(--color-brand)] shadow-[0_0_0_3px_var(--color-brand-light)]'
            : hasValue
              ? 'border-[var(--color-brand)] border-opacity-40 bg-[var(--color-brand-light)]'
              : 'border-[var(--color-border)] bg-white hover:border-[var(--color-border-mid)]',
        )}
      >
        {/* floating label */}
        <motion.label
          animate={{
            top: lifted ? 6 : '50%',
            y: lifted ? 0 : '-50%',
            fontSize: lifted ? '10px' : '13px',
            color: focused ? 'var(--color-brand)' : lifted ? 'var(--color-text-muted)' : 'var(--color-text-muted)',
          }}
          transition={{ duration: prefersReduced ? 0 : 0.15 }}
          className="absolute left-3 pointer-events-none font-medium leading-none"
          style={{ top: lifted ? 6 : '50%', fontSize: lifted ? 10 : 13 }}
        >
          {label}
        </motion.label>

        <div className="flex items-end pt-5 pb-2 px-3 gap-2">
          <input
            type="number"
            step="0.1"
            min="0"
            value={value}
            onChange={(e) => onChange(e.target.value)}
            onFocus={() => { setFocused(true); onFocus() }}
            onBlur={() => { setFocused(false); onBlur() }}
            className="flex-1 w-0 min-w-0 text-sm font-mono font-semibold bg-transparent focus:outline-none text-[var(--color-text-primary)] text-right"
            placeholder="0.0"
          />
          <span className="text-[11px] font-medium text-[var(--color-text-muted)] shrink-0 pb-px">{unit}</span>
        </div>

        {/* filled indicator dot */}
        {hasValue && (
          <motion.span
            initial={{ scale: 0 }}
            animate={{ scale: 1 }}
            className="absolute top-2 right-2 w-1.5 h-1.5 rounded-full bg-[var(--color-brand)]"
          />
        )}
      </div>
    </motion.div>
  )
}

// ─── Collapsible section ──────────────────────────────────────────────────────

function Section({
  title, icon: Icon, open, onToggle, filled, total, children,
}: {
  title: string
  icon: React.ElementType
  open: boolean
  onToggle: () => void
  filled: number
  total: number
  children: React.ReactNode
}) {
  const prefersReduced = useReducedMotion()

  return (
    <div className="rounded-2xl border border-[var(--color-border)] overflow-hidden shadow-[var(--shadow-sm)]">
      <button
        type="button"
        onClick={onToggle}
        className={cn(
          'flex items-center gap-3 w-full px-5 py-4 text-left transition-colors',
          open ? 'bg-[var(--color-surface-alt)]' : 'bg-white hover:bg-[var(--color-surface-alt)]',
        )}
      >
        <span className={cn(
          'flex items-center justify-center w-8 h-8 rounded-xl transition-colors',
          open ? 'bg-[var(--color-brand)] text-white' : 'bg-[var(--color-border-soft)] text-[var(--color-text-muted)]',
        )}>
          <Icon size={15} strokeWidth={2} />
        </span>
        <span className="flex-1 text-sm font-semibold text-[var(--color-text-primary)]">{title}</span>

        {/* filled count chip */}
        {filled > 0 && (
          <motion.span
            initial={{ scale: 0.7, opacity: 0 }}
            animate={{ scale: 1, opacity: 1 }}
            className="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-[var(--color-brand)] text-white tabular-nums"
          >
            {filled}/{total}
          </motion.span>
        )}

        <ChevronDown
          size={16}
          strokeWidth={1.75}
          className={cn(
            'text-[var(--color-text-muted)] transition-transform duration-200 ml-1',
            open && 'rotate-180',
          )}
        />
      </button>

      <AnimatePresence initial={false}>
        {open && (
          <motion.div
            initial={{ height: 0, opacity: 0 }}
            animate={{ height: 'auto', opacity: 1 }}
            exit={{ height: 0, opacity: 0 }}
            transition={{ duration: prefersReduced ? 0 : 0.22, ease: 'easeInOut' }}
            className="overflow-hidden"
          >
            <div className="border-t border-[var(--color-border-soft)] bg-white p-5">
              {children}
            </div>
          </motion.div>
        )}
      </AnimatePresence>
    </div>
  )
}

// ─── Page ─────────────────────────────────────────────────────────────────────

export default function MeasurementProfilePage({ params }: { params: Promise<{ profileId: string }> }) {
  const { profileId } = use(params)
  const id = parseInt(profileId)
  const { preferences } = usePreferences()
  const unit = preferences.measurementUnit

  const { data: versions = [] } = useMeasurementVersions(id)
  const createVersion = useCreateMeasurementVersion(id)

  const [activeField, setActiveField] = useState<string | null>(null)
  const [values, setValues] = useState<Record<string, string>>({})
  const [notes, setNotes] = useState('')
  const [submitting, setSubmitting] = useState(false)
  const [shirtOpen, setShirtOpen] = useState(true)
  const [pantOpen, setPantOpen] = useState(false)
  const [saved, setSaved] = useState(false)

  const latestVersion = versions[0]

  // Prefill the form from the latest version ONCE (the banner promises this).
  // Append-only flow: after a save the form is intentionally blanked, so we never
  // re-seed; and we never clobber values the user has already started entering.
  const seeded = useRef(false)
  useEffect(() => {
    if (seeded.current || !latestVersion) return
    seeded.current = true
    setValues((cur) => (Object.keys(cur).length > 0 ? cur : seedFromVersion(latestVersion)))
  }, [latestVersion])

  const setValue = useCallback((field: string, val: string) => {
    setValues((p) => ({ ...p, [field]: val }))
  }, [])

  const filledCount = Object.values(values).filter(v => v && parseFloat(v) > 0).length
  const shirtFilled = SHIRT_FIELDS.filter(f => values[f.key] && parseFloat(values[f.key]) > 0).length
  const pantFilled  = PANT_FIELDS.filter(f => values[f.key] && parseFloat(values[f.key]) > 0).length

  async function handleSave() {
    setSubmitting(true)
    try {
      const payload: Record<string, unknown> = {}
      for (const [k, v] of Object.entries(values)) {
        if (v && parseFloat(v) > 0) payload[k] = parseFloat(v)
      }
      payload.notes = notes
      payload.unit = unit
      await createVersion.mutateAsync(payload)
      setSaved(true)
      toast.success('New measurement version saved')
      setTimeout(() => setSaved(false), 2000)
      setValues({})
      setNotes('')
    } catch (err: unknown) {
      toast.error((err as { message?: string })?.message ?? 'Failed to save')
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <div className="flex gap-6 h-[calc(100vh-5rem)]">

      {/* ── Left: form ── */}
      <div className="flex-1 flex flex-col gap-5 overflow-y-auto pb-8 pr-1">

        {/* Header */}
        <div className="flex items-start justify-between gap-4 pt-1">
          <div>
            <h1 className="text-2xl font-bold tracking-tight text-[var(--color-text-primary)]">
              Measurement Entry
            </h1>
            <p className="text-sm text-[var(--color-text-muted)] mt-0.5">
              Creating a new version — append only
            </p>
          </div>
          <CompletionRing filled={filledCount} total={ALL_FIELDS.length} />
        </div>

        {/* Pre-fill notice */}
        {latestVersion && (
          <motion.div
            initial={{ opacity: 0, y: -8 }}
            animate={{ opacity: 1, y: 0 }}
            className="flex items-center gap-2.5 px-4 py-3 rounded-xl bg-amber-50 border border-amber-200 text-sm text-amber-800"
          >
            <CheckCircle2 size={15} className="shrink-0 text-amber-600" />
            Pre-filling from latest approved version. Any change creates a new version.
          </motion.div>
        )}

        {/* Shirt section */}
        <Section
          title="Shirt Measurements"
          icon={Shirt}
          open={shirtOpen}
          onToggle={() => setShirtOpen(v => !v)}
          filled={shirtFilled}
          total={SHIRT_FIELDS.length}
        >
          <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
            {SHIRT_FIELDS.map((f, i) => (
              <MeasurementInput
                key={f.key}
                field={f.key}
                label={f.label}
                value={values[f.key] ?? ''}
                unit={unit}
                index={i}
                onChange={(v) => setValue(f.key, v)}
                onFocus={() => setActiveField(f.key)}
                onBlur={() => setActiveField(null)}
              />
            ))}
          </div>
        </Section>

        {/* Pant section */}
        <Section
          title="Pant Measurements"
          icon={Scissors}
          open={pantOpen}
          onToggle={() => setPantOpen(v => !v)}
          filled={pantFilled}
          total={PANT_FIELDS.length}
        >
          <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
            {PANT_FIELDS.map((f, i) => (
              <MeasurementInput
                key={f.key}
                field={f.key}
                label={f.label}
                value={values[f.key] ?? ''}
                unit={unit}
                index={i}
                onChange={(v) => setValue(f.key, v)}
                onFocus={() => setActiveField(f.key)}
                onBlur={() => setActiveField(null)}
              />
            ))}
          </div>
        </Section>

        {/* Notes section */}
        <Section
          title="Notes"
          icon={StickyNote}
          open={true}
          onToggle={() => {}}
          filled={notes.length > 0 ? 1 : 0}
          total={0}
        >
          <textarea
            value={notes}
            onChange={(e) => setNotes(e.target.value)}
            rows={3}
            className="w-full px-3 py-2.5 text-sm border border-[var(--color-border)] rounded-xl focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)] resize-none transition-shadow"
            placeholder="Any special fitting notes, customer preferences…"
          />
        </Section>

        {/* Save button */}
        <div className="flex items-center gap-3">
          <motion.button
            onClick={handleSave}
            disabled={submitting || filledCount === 0}
            whileTap={{ scale: 0.97 }}
            whileHover={{ scale: filledCount > 0 ? 1.02 : 1 }}
            className={cn(
              'relative flex items-center gap-2 px-6 py-3 rounded-xl text-sm font-semibold text-white',
              'transition-all duration-200 disabled:opacity-40 disabled:cursor-not-allowed overflow-hidden',
              saved
                ? 'bg-green-600'
                : 'bg-[var(--color-brand)] hover:bg-[var(--color-brand-dark)] shadow-[0_2px_12px_var(--color-brand-light)]',
            )}
          >
            {/* shimmer when ready */}
            {filledCount > 0 && !submitting && !saved && (
              <motion.span
                className="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -skew-x-12"
                animate={{ x: ['-150%', '150%'] }}
                transition={{ duration: 2.5, repeat: Infinity, ease: 'linear', repeatDelay: 1 }}
              />
            )}
            <AnimatePresence mode="wait">
              {saved ? (
                <motion.span key="done" initial={{ scale: 0 }} animate={{ scale: 1 }} className="flex items-center gap-2">
                  <CheckCircle2 size={15} /> Saved!
                </motion.span>
              ) : submitting ? (
                <motion.span key="saving" className="flex items-center gap-2">
                  <motion.span animate={{ rotate: 360 }} transition={{ duration: 0.8, repeat: Infinity, ease: 'linear' }}>
                    <Save size={15} />
                  </motion.span>
                  Saving…
                </motion.span>
              ) : (
                <motion.span key="idle" className="flex items-center gap-2">
                  <Save size={15} /> Save as New Version
                </motion.span>
              )}
            </AnimatePresence>
          </motion.button>

          {filledCount > 0 && (
            <motion.p
              initial={{ opacity: 0, x: -8 }}
              animate={{ opacity: 1, x: 0 }}
              className="text-xs text-[var(--color-text-muted)]"
            >
              {filledCount} field{filledCount !== 1 ? 's' : ''} filled
            </motion.p>
          )}
        </div>
      </div>

      {/* ── Right: body guide ── */}
      <div className="w-48 shrink-0 hidden lg:flex flex-col items-center pt-6 gap-2">
        <div className="sticky top-6 flex flex-col items-center gap-3 w-full">
          {/* active field label chip */}
          <div className="h-7 flex items-center">
            <AnimatePresence mode="wait">
              {activeField ? (
                <motion.span
                  key={activeField}
                  initial={{ opacity: 0, scale: 0.8 }}
                  animate={{ opacity: 1, scale: 1 }}
                  exit={{ opacity: 0, scale: 0.8 }}
                  className="px-3 py-1 rounded-full text-[11px] font-semibold bg-[var(--color-brand)] text-white capitalize"
                >
                  {activeField.replace(/_/g, ' ')}
                </motion.span>
              ) : (
                <motion.span
                  key="none"
                  initial={{ opacity: 0 }}
                  animate={{ opacity: 1 }}
                  className="text-[10px] text-[var(--color-text-muted)]"
                >
                  Body Guide
                </motion.span>
              )}
            </AnimatePresence>
          </div>

          <MeasurementGuideAnimator activeField={activeField} />

          {/* mini progress bar */}
          <div className="w-full px-2 mt-2">
            <div className="flex justify-between text-[9px] text-[var(--color-text-muted)] mb-1">
              <span>Completion</span>
              <span>{Math.round((filledCount / ALL_FIELDS.length) * 100)}%</span>
            </div>
            <div className="h-1.5 rounded-full bg-[var(--color-border-soft)] overflow-hidden">
              <motion.div
                className="h-full rounded-full bg-[var(--color-brand)]"
                animate={{ width: `${(filledCount / ALL_FIELDS.length) * 100}%` }}
                transition={{ duration: 0.4, ease: 'easeOut' }}
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}
