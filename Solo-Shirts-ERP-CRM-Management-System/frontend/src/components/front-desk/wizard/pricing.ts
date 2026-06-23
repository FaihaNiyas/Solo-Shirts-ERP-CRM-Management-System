// Per-shirt pricing maths, mirrored from the server (all in integer paise so the
// displayed grand total matches the invoice the backend will compute). The
// backend remains the source of truth — this is for live UI feedback only.

import type { SubOrderDraft } from './types'

const GST_RATES = [0, 5, 12, 18] as const

export function rupeesToPaise(rupees: number): number {
  return Math.round((Number(rupees) || 0) * 100)
}

export interface LineTotals {
  grossPaise: number // base price
  discountPaise: number // capped at gross
  taxablePaise: number // gross - discount
  gstPaise: number
  totalPaise: number // taxable + gst
}

export function lineTotals(s: SubOrderDraft): LineTotals {
  const gross = rupeesToPaise(s.basePrice)
  const discount = Math.min(rupeesToPaise(s.discountAmount), gross)
  const taxable = gross - discount
  const rate = GST_RATES.includes(s.gstRate as 0 | 5 | 12 | 18) ? s.gstRate : 0
  const gst = rate > 0 ? Math.round((taxable * rate) / 100) : 0
  return { grossPaise: gross, discountPaise: discount, taxablePaise: taxable, gstPaise: gst, totalPaise: taxable + gst }
}

export interface OrderTotals {
  grossPaise: number
  discountPaise: number
  taxablePaise: number
  gstPaise: number
  grandPaise: number
}

export function orderTotals(subs: SubOrderDraft[]): OrderTotals {
  return subs.reduce<OrderTotals>(
    (acc, s) => {
      const l = lineTotals(s)
      return {
        grossPaise: acc.grossPaise + l.grossPaise,
        discountPaise: acc.discountPaise + l.discountPaise,
        taxablePaise: acc.taxablePaise + l.taxablePaise,
        gstPaise: acc.gstPaise + l.gstPaise,
        grandPaise: acc.grandPaise + l.totalPaise,
      }
    },
    { grossPaise: 0, discountPaise: 0, taxablePaise: 0, gstPaise: 0, grandPaise: 0 },
  )
}

/** A line whose discount exceeds its base price is invalid. */
export function lineDiscountExceeds(s: SubOrderDraft): boolean {
  return rupeesToPaise(s.discountAmount) > rupeesToPaise(s.basePrice)
}

export const GST_RATE_OPTIONS = GST_RATES
