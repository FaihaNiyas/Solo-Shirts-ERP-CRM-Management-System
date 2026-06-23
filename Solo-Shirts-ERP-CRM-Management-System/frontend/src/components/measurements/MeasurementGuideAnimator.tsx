'use client'

import { useReducedMotion, motion, AnimatePresence } from 'framer-motion'

const FIELD_GUIDE: Record<string, { region: string; hint: string }> = {
  chest:        { region: 'torso-upper',      hint: 'Around the fullest part of the chest, just under the arms' },
  waist:        { region: 'torso-mid',        hint: 'Around the natural waistline, narrowest part of the torso' },
  hip:          { region: 'torso-lower',      hint: 'Around the fullest part of the hips' },
  shoulder:     { region: 'shoulder',         hint: 'From shoulder seam to shoulder seam across the back' },
  sleeve_length:{ region: 'arm',              hint: 'From shoulder seam down to the wrist' },
  shirt_length: { region: 'torso',            hint: 'From highest shoulder point to desired hem length' },
  collar:       { region: 'neck',             hint: 'Around the base of the neck where the collar sits' },
  cuff:         { region: 'wrist',            hint: 'Around the wrist where the cuff will sit' },
  arm_round:    { region: 'arm-mid',          hint: 'Around the fullest part of the upper arm' },
  neck:         { region: 'neck',             hint: 'Around the neck, 2 fingers above the collar bone' },
  front_chest:  { region: 'torso-upper',      hint: 'Front chest width, armhole to armhole' },
  cross_back:   { region: 'torso-upper-back', hint: 'Back width, armhole to armhole' },
  bicep:        { region: 'arm-upper',        hint: 'Around the fullest part of the bicep' },
  wrist:        { region: 'wrist',            hint: 'Around the wrist bone' },
  dart:         { region: 'torso-mid',        hint: 'Dart placement for shaped fit' },
  pant_waist:   { region: 'waist-lower',      hint: 'Around the waist where the pant sits' },
  pant_hip:     { region: 'hip',              hint: 'Around the fullest part of the hips' },
  thigh:        { region: 'thigh',            hint: 'Around the fullest part of the thigh' },
  knee:         { region: 'knee',             hint: 'Around the knee' },
  bottom:       { region: 'ankle',            hint: 'Around the bottom opening of the pant leg' },
  pant_length:  { region: 'leg',              hint: 'From waist to ankle (outseam)' },
  in_seam:      { region: 'inseam',           hint: 'From crotch to ankle (inner leg)' },
  out_seam:     { region: 'leg',              hint: 'From waist to ankle (outer leg)' },
  crotch:       { region: 'crotch',           hint: 'Crotch depth from waist to seat' },
  fly:          { region: 'waist-lower',      hint: 'Fly length from waist to crotch front' },
}

// cx, cy = center, rx = x-radius, ry = y-radius of ellipse highlight
const REGION_HIGHLIGHTS: Record<string, { cx: number; cy: number; rx: number; ry: number }> = {
  neck:               { cx: 100, cy: 52,  rx: 14, ry: 14 },
  shoulder:           { cx: 100, cy: 76,  rx: 46, ry: 14 },
  'torso-upper':      { cx: 100, cy: 105, rx: 38, ry: 22 },
  'torso-upper-back': { cx: 100, cy: 105, rx: 38, ry: 22 },
  torso:              { cx: 100, cy: 150, rx: 38, ry: 65 },
  'torso-mid':        { cx: 100, cy: 155, rx: 32, ry: 20 },
  'torso-lower':      { cx: 100, cy: 185, rx: 40, ry: 22 },
  'waist-lower':      { cx: 100, cy: 210, rx: 34, ry: 14 },
  arm:                { cx: 40,  cy: 150, rx: 14, ry: 55 },
  'arm-upper':        { cx: 40,  cy: 110, rx: 14, ry: 28 },
  'arm-mid':          { cx: 38,  cy: 140, rx: 14, ry: 22 },
  wrist:              { cx: 34,  cy: 195, rx: 11, ry: 12 },
  hip:                { cx: 100, cy: 235, rx: 42, ry: 22 },
  thigh:              { cx: 80,  cy: 285, rx: 18, ry: 30 },
  knee:               { cx: 78,  cy: 335, rx: 14, ry: 16 },
  ankle:              { cx: 76,  cy: 390, rx: 12, ry: 14 },
  leg:                { cx: 80,  cy: 330, rx: 14, ry: 70 },
  inseam:             { cx: 88,  cy: 330, rx: 10, ry: 65 },
  crotch:             { cx: 100, cy: 248, rx: 20, ry: 14 },
}

interface Props {
  activeField: string | null
  showGuide?: boolean
}

export function MeasurementGuideAnimator({ activeField, showGuide = true }: Props) {
  const prefersReduced = useReducedMotion()
  if (!showGuide) return null

  const guide = activeField ? FIELD_GUIDE[activeField] : null
  const region = guide ? REGION_HIGHLIGHTS[guide.region] : null

  return (
    <div className="flex flex-col items-center gap-4 select-none">
      <div className="relative">
        <svg
          viewBox="0 0 200 420"
          width="148"
          fill="none"
          strokeLinecap="round"
          strokeLinejoin="round"
        >
          {/* ── Body fill ── */}
          <defs>
            <linearGradient id="bodyGrad" x1="0" y1="0" x2="1" y2="1">
              <stop offset="0%" stopColor="var(--color-surface-alt)" />
              <stop offset="100%" stopColor="var(--color-border-soft)" />
            </linearGradient>
            <filter id="glow">
              <feGaussianBlur stdDeviation="3" result="coloredBlur" />
              <feMerge>
                <feMergeNode in="coloredBlur" />
                <feMergeNode in="SourceGraphic" />
              </feMerge>
            </filter>
          </defs>

          {/* Head */}
          <ellipse cx="100" cy="30" rx="22" ry="26"
            fill="url(#bodyGrad)"
            stroke="var(--color-border-mid)" strokeWidth="1.5" />

          {/* Neck */}
          <path d="M91 54 L89 70 M109 54 L111 70"
            stroke="var(--color-border-mid)" strokeWidth="1.5" />

          {/* Shoulders */}
          <path d="M89 70 Q66 68 48 86"
            stroke="var(--color-border-mid)" strokeWidth="1.5" />
          <path d="M111 70 Q134 68 152 86"
            stroke="var(--color-border-mid)" strokeWidth="1.5" />

          {/* Left arm */}
          <path d="M48 86 L36 145 L30 202"
            stroke="var(--color-border-mid)" strokeWidth="1.5" />
          {/* Right arm */}
          <path d="M152 86 L164 145 L170 202"
            stroke="var(--color-border-mid)" strokeWidth="1.5" />

          {/* Torso outline */}
          <path d="M48 86 L52 205 L60 246 L140 246 L148 205 L152 86"
            fill="url(#bodyGrad)"
            stroke="var(--color-border-mid)" strokeWidth="1.5" />

          {/* Waist in-curve */}
          <path d="M55 155 Q100 144 145 155"
            stroke="var(--color-border)" strokeWidth="1" strokeDasharray="4 3" opacity="0.6" />

          {/* Chest line */}
          <path d="M54 110 Q100 102 146 110"
            stroke="var(--color-border)" strokeWidth="1" strokeDasharray="4 3" opacity="0.4" />

          {/* Hip line */}
          <path d="M52 200 Q100 208 148 200"
            stroke="var(--color-border)" strokeWidth="1" strokeDasharray="4 3" opacity="0.4" />

          {/* Left leg */}
          <path d="M60 246 L56 346 L50 406"
            stroke="var(--color-border-mid)" strokeWidth="1.5" />
          {/* Right leg */}
          <path d="M140 246 L144 346 L150 406"
            stroke="var(--color-border-mid)" strokeWidth="1.5" />

          {/* Inner leg seam */}
          <path d="M100 246 L94 346 L88 406"
            stroke="var(--color-border)" strokeWidth="1" opacity="0.4" />
          <path d="M100 246 L106 346 L112 406"
            stroke="var(--color-border)" strokeWidth="1" opacity="0.4" />

          {/* Knee lines */}
          <path d="M52 338 Q64 334 76 338" stroke="var(--color-border)" strokeWidth="1" opacity="0.5" />
          <path d="M124 338 Q136 334 148 338" stroke="var(--color-border)" strokeWidth="1" opacity="0.5" />

          {/* ── Active region highlight ── */}
          <AnimatePresence>
            {region && (
              <>
                {/* outer glow ring */}
                <motion.ellipse
                  key={`glow-${activeField}`}
                  cx={region.cx}
                  cy={region.cy}
                  rx={region.rx + 6}
                  ry={region.ry + 6}
                  fill="none"
                  stroke="var(--color-brand)"
                  strokeWidth={1}
                  initial={{ opacity: 0, scaleX: 0.7, scaleY: 0.7 }}
                  animate={{ opacity: [0, 0.4, 0.15], scaleX: 1, scaleY: 1 }}
                  exit={{ opacity: 0 }}
                  transition={{ duration: prefersReduced ? 0 : 0.4, ease: 'easeOut' }}
                  style={{ filter: 'url(#glow)', transformOrigin: `${region.cx}px ${region.cy}px` }}
                />
                {/* solid fill */}
                <motion.ellipse
                  key={`fill-${activeField}`}
                  cx={region.cx}
                  cy={region.cy}
                  rx={region.rx}
                  ry={region.ry}
                  fill="var(--color-brand)"
                  stroke="var(--color-brand)"
                  strokeWidth={1.5}
                  initial={{ opacity: 0, scaleX: 0.5, scaleY: 0.5 }}
                  animate={{ opacity: 0.22, scaleX: 1, scaleY: 1 }}
                  exit={{ opacity: 0, scaleX: 0.6, scaleY: 0.6 }}
                  transition={{ duration: prefersReduced ? 0 : 0.25, ease: 'backOut' }}
                  style={{ transformOrigin: `${region.cx}px ${region.cy}px` }}
                />
                {/* pulsing ring */}
                {!prefersReduced && (
                  <motion.ellipse
                    key={`pulse-${activeField}`}
                    cx={region.cx}
                    cy={region.cy}
                    rx={region.rx}
                    ry={region.ry}
                    fill="none"
                    stroke="var(--color-brand)"
                    strokeWidth={1.5}
                    animate={{ opacity: [0.6, 0], scaleX: [1, 1.5], scaleY: [1, 1.5] }}
                    transition={{ duration: 1.2, repeat: Infinity, ease: 'easeOut' }}
                    style={{ transformOrigin: `${region.cx}px ${region.cy}px` }}
                  />
                )}
              </>
            )}
          </AnimatePresence>
        </svg>
      </div>

      {/* Hint text */}
      <div className="min-h-[52px] flex items-start justify-center px-2">
        <AnimatePresence mode="wait">
          {guide ? (
            <motion.div
              key={activeField}
              initial={{ opacity: 0, y: 6 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -4 }}
              transition={{ duration: prefersReduced ? 0 : 0.18 }}
              className="text-center"
            >
              <p className="text-[11px] font-semibold text-[var(--color-brand)] capitalize tracking-wide mb-1">
                {activeField?.replace(/_/g, ' ')}
              </p>
              <p className="text-[10px] text-[var(--color-text-muted)] leading-relaxed">
                {guide.hint}
              </p>
            </motion.div>
          ) : (
            <motion.p
              key="idle"
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              className="text-[10px] text-[var(--color-text-muted)] text-center"
            >
              Focus any field to see the guide
            </motion.p>
          )}
        </AnimatePresence>
      </div>
    </div>
  )
}
