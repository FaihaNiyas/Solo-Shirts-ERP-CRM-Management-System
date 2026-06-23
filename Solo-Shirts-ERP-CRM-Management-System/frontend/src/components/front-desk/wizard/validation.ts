// Completeness + confirm-gate rules for the Front Desk wizard.

import type { Customer } from '@/lib/api/schemas/customers'
import type { MainOrderMeta, SubOrderDraft } from './types'

/**
 * A sub-order is complete when it has a selected measurement plus fabric, style
 * and fit. Measurements are usable immediately — there is no approval gate.
 */
export function isSubOrderComplete(s: SubOrderDraft): boolean {
  return Boolean(
    s.measurementVersionId &&
      s.fabricId &&
      s.styleId &&
      s.fitId,
  )
}

export function completedCount(subs: SubOrderDraft[]): number {
  return subs.reduce((n, s) => (isSubOrderComplete(s) ? n + 1 : n), 0)
}

/** Which fields are still missing on a shirt — used for inline warnings. */
export function missingFields(s: SubOrderDraft): string[] {
  const missing: string[] = []
  if (!s.measurementVersionId) missing.push('measurement')
  if (!s.fabricId) missing.push('fabric')
  if (!s.styleId) missing.push('style')
  if (!s.fitId) missing.push('fit')
  return missing
}

export interface ConfirmContext {
  customer: Customer | null
  meta: MainOrderMeta
  subOrders: SubOrderDraft[]
}

/**
 * Returns a list of blocking issues. Empty list = order may be confirmed.
 */
export function confirmIssues({ customer, meta, subOrders }: ConfirmContext): string[] {
  const issues: string[] = []
  if (!customer) issues.push('Select a customer.')
  if (!meta.deliveryDate) issues.push('Enter a delivery date.')
  if (subOrders.length === 0) issues.push('Add at least one shirt (sub-order).')

  subOrders.forEach((s, i) => {
    const code = String(i + 1).padStart(2, '0')
    const miss = missingFields(s)
    if (miss.length > 0) {
      issues.push(`Shirt ${code} needs: ${miss.join(', ')}.`)
    }
  })

  return issues
}

/** A shirt's job-card PDF is generated (Phase 2 blocking gate). */
export function pdfReady(s: SubOrderDraft): boolean {
  return s.pdfStatus === 'generated'
}

/**
 * Full confirm gate including Phase 2: measurement/fabric/style/fit AND a
 * generated job-card PDF per shirt. Empty list = confirmable.
 */
export function confirmBlockers(ctx: ConfirmContext): string[] {
  const issues = confirmIssues(ctx)

  ctx.subOrders.forEach((s, i) => {
    const code = String(i + 1).padStart(2, '0')
    if (s.pdfStatus !== 'generated') issues.push(`Shirt ${code} needs its PDF generated.`)
  })

  return issues
}

/** Non-blocking reminders shown before confirm: unprinted shirts. */
export function confirmWarnings(subs: SubOrderDraft[]): string[] {
  const warnings: string[] = []
  const notPrinted = subs.filter((s) => s.printStatus !== 'printed').length
  if (notPrinted > 0) warnings.push(`${notPrinted} of ${subs.length} PDFs are not marked printed.`)
  return warnings
}
