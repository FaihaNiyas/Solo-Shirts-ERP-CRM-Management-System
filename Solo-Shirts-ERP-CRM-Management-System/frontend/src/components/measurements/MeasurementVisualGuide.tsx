'use client'

import { memo } from 'react'
import { Ruler } from 'lucide-react'
import type { MeasurementGuideField, MeasurementProductType } from '@/lib/measurements/measurementGuide'

const BRAND = 'var(--color-brand)'
const FAINT = '#D1D5DB'

/** Highlight overlay for a shirt region. Returns null for unknown keys. */
function shirtHighlight(key: string | null) {
  switch (key) {
    case 'neck':
      return <ellipse cx="110" cy="46" rx="15" ry="8" />
    case 'shoulder':
      return <line x1="80" y1="50" x2="140" y2="50" />
    case 'chest':
      return <line x1="82" y1="82" x2="138" y2="82" />
    case 'waist':
      return <line x1="86" y1="132" x2="134" y2="132" />
    case 'hip':
      return <line x1="84" y1="195" x2="136" y2="195" />
    case 'length':
      return <line x1="110" y1="52" x2="110" y2="206" />
    case 'sleeve':
      return <line x1="138" y1="52" x2="160" y2="116" />
    case 'armhole':
      return <ellipse cx="140" cy="64" rx="10" ry="13" />
    case 'bicep':
      return <line x1="142" y1="80" x2="162" y2="92" />
    case 'cuff':
      return <ellipse cx="158" cy="116" rx="8" ry="5" />
    case 'front':
      return <line x1="110" y1="58" x2="110" y2="120" />
    case 'back':
      return <line x1="86" y1="60" x2="134" y2="60" />
    default:
      return null
  }
}

function trouserHighlight(key: string | null) {
  switch (key) {
    case 'waist':
      return <line x1="78" y1="48" x2="142" y2="48" />
    case 'hip':
      return <line x1="74" y1="74" x2="146" y2="74" />
    case 'thigh':
      return <line x1="80" y1="104" x2="106" y2="104" />
    case 'knee':
      return <line x1="84" y1="172" x2="106" y2="172" />
    case 'bottom':
      return <line x1="84" y1="246" x2="104" y2="246" />
    case 'length':
      return <line x1="78" y1="56" x2="82" y2="250" />
    case 'inseam':
      return <line x1="110" y1="122" x2="92" y2="250" />
    case 'rise':
      return <line x1="110" y1="48" x2="110" y2="122" />
    default:
      return null
  }
}

function ShirtDiagram({ activeKey }: { activeKey: string | null }) {
  return (
    <svg viewBox="0 0 220 250" className="w-full max-w-[210px] h-auto" role="img" aria-label="Shirt measurement diagram">
      {/* Base silhouette — smooth collared shirt with set-in sleeves */}
      <g fill="#FFFFFF" stroke={FAINT} strokeWidth="2.5" strokeLinejoin="round" strokeLinecap="round">
        {/* body + sleeves as one continuous outline */}
        <path d="M90,44
                 C90,56 130,56 130,44
                 L150,52
                 L176,72 C179,75 179,80 175,82 L156,98 L150,84
                 L150,206 C150,210 147,212 143,212 L77,212 C73,212 70,210 70,206 L70,84
                 L64,98 L45,82 C41,80 41,75 44,72 L70,52 Z" />
        {/* collar */}
        <path d="M90,44 L102,62 L110,52 L118,62 L130,44" fill="none" stroke={FAINT} strokeWidth="2" />
        {/* placket */}
        <line x1="110" y1="52" x2="110" y2="206" stroke={FAINT} strokeWidth="1.5" />
      </g>
      {/* Active highlight */}
      <g
        stroke={BRAND}
        strokeWidth="3.5"
        strokeLinecap="round"
        fill="none"
        className="animate-pulse"
        style={{ filter: 'drop-shadow(0 0 3px rgba(217,119,6,0.45))' }}
      >
        {shirtHighlight(activeKey)}
      </g>
    </svg>
  )
}

function TrouserDiagram({ activeKey }: { activeKey: string | null }) {
  return (
    <svg viewBox="0 0 220 270" className="w-full max-w-[170px] h-auto" role="img" aria-label="Trouser measurement diagram">
      <g fill="#FFFFFF" stroke={FAINT} strokeWidth="2.5" strokeLinejoin="round" strokeLinecap="round">
        {/* waistband */}
        <path d="M74,40 L146,40 C148,40 150,42 150,44 L150,56 L70,56 L70,44 C70,42 72,40 74,40 Z" />
        {/* legs with a tapered fit + centre crotch notch */}
        <path d="M70,56 L150,56
                 L146,150 L138,252 C138,254 136,256 134,256 L120,256 C118,256 116,254 116,252 L110,128
                 L104,252 C104,254 102,256 100,256 L86,256 C84,256 82,254 82,252 L74,150 Z" />
        {/* centre + pocket hints */}
        <line x1="110" y1="56" x2="110" y2="128" stroke={FAINT} strokeWidth="1.5" />
        <path d="M82,62 C92,70 100,70 104,64" fill="none" stroke={FAINT} strokeWidth="1.5" />
        <path d="M138,62 C128,70 120,70 116,64" fill="none" stroke={FAINT} strokeWidth="1.5" />
      </g>
      <g
        stroke={BRAND}
        strokeWidth="3.5"
        strokeLinecap="round"
        fill="none"
        className="animate-pulse"
        style={{ filter: 'drop-shadow(0 0 3px rgba(217,119,6,0.45))' }}
      >
        {trouserHighlight(activeKey)}
      </g>
    </svg>
  )
}

/**
 * Visual measurement guide — shows the product diagram with the active field's
 * body region highlighted, plus a bilingual label and a short how-to-measure
 * instruction. Works entirely from inline SVG (no external/copyrighted images).
 */
export const MeasurementVisualGuide = memo(function MeasurementVisualGuide({
  productType,
  activeField,
}: {
  productType: MeasurementProductType
  activeField: MeasurementGuideField | null
}) {
  const activeKey = activeField?.diagram_key ?? null

  return (
    <div className="rounded-2xl border border-[var(--color-border)] bg-white p-4 flex flex-col items-center">
      <div className="flex min-h-[230px] items-center justify-center">
        {productType === 'trouser' ? <TrouserDiagram activeKey={activeKey} /> : <ShirtDiagram activeKey={activeKey} />}
      </div>

      <div className="mt-3 w-full rounded-xl bg-[var(--color-surface-alt)] px-3 py-3 text-center">
        {activeField ? (
          <>
            <p className="text-sm font-semibold text-[var(--color-text-primary)]">
              {activeField.label}
              <span className="ml-1 text-[var(--color-text-secondary)]">/ {activeField.label_ta}</span>
            </p>
            <p className="mt-1 text-xs font-medium text-[var(--color-brand-dark)]">{activeField.guide_title}</p>
            <p className="mt-0.5 text-xs text-[var(--color-text-secondary)]">{activeField.guide_text}</p>
            <p className="mt-1 text-[11px] text-[var(--color-text-muted)]">Measured in inches ({activeField.unit})</p>
          </>
        ) : (
          <p className="inline-flex items-center gap-1.5 text-xs text-[var(--color-text-muted)]">
            <Ruler size={13} strokeWidth={1.75} />
            Tap a measurement field to see where and how to measure it.
          </p>
        )}
      </div>
    </div>
  )
})
