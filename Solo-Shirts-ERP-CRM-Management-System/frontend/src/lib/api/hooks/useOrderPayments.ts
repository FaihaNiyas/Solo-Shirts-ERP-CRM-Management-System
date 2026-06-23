'use client'

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { apiGet, apiMutate } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { useStableIdempotencyKey } from '@/lib/api/idempotency'
import { queryKeys } from '@/lib/query/keys'

export interface OrderInvoiceSummary {
  id: number
  invoice_number: string
  total_amount: number
  paid_amount: number
  balance_amount: number
  status: string
}

export interface OrderPaymentRow {
  id: number
  paid_at: string | null
  amount: number
  method: string
  reference: string | null
  recorded_by: string | null
}

export interface OrderPaymentsData {
  order_id: number
  lifecycle_status: string
  invoice: OrderInvoiceSummary | null
  payments: OrderPaymentRow[]
}

export interface RecordOrderPaymentInput {
  amount: number
  method: string
  reference?: string
  note?: string
}

export interface RecordOrderPaymentResult {
  invoice: OrderInvoiceSummary
  payment: {
    id: number
    amount: number
    method: string
    reference: string | null
    status: string
    receipt_url: string | null
  }
}

export function orderPaymentsKey(orderId: number) {
  return queryKeys.orderPayments(orderId)
}

export function useOrderPayments(orderId: number) {
  return useQuery({
    queryKey: orderPaymentsKey(orderId),
    queryFn: () => apiGet<OrderPaymentsData>(ENDPOINTS.orderPayments(orderId)),
    select: (res) => res.data,
    enabled: orderId > 0,
  })
}

export function useRecordOrderPayment(orderId: number) {
  const qc = useQueryClient()
  const idem = useStableIdempotencyKey() // stable across retries → backend dedupes; reset after success

  const mutation = useMutation({
    mutationFn: (data: RecordOrderPaymentInput) =>
      apiMutate<RecordOrderPaymentResult>('post', ENDPOINTS.orderPayments(orderId), data, idem.current).then(
        (r) => r.data,
      ),
    onSuccess: () => {
      // New idempotency key for the NEXT logical payment (a second installment).
      // We intentionally do NOT reset on error: a network failure that actually
      // succeeded server-side must replay the SAME key on retry (no double charge).
      idem.reset()
      // A balance payment changes more than the payment list: it moves the
      // handover gate (can_handover / balance_amount), the order's own balance,
      // delivery eligibility (the gate now applies to delivery too), and every
      // list/dashboard/search that surfaces outstanding amounts. Refresh them all
      // so the UI never shows a stale "balance pending" after collection.
      qc.invalidateQueries({ queryKey: orderPaymentsKey(orderId) })
      qc.invalidateQueries({ queryKey: queryKeys.handoverEligibility(orderId) })
      qc.invalidateQueries({ queryKey: queryKeys.order(orderId) })
      qc.invalidateQueries({ queryKey: queryKeys.orders() })
      qc.invalidateQueries({ queryKey: queryKeys.outstandingBalance(orderId) })
      qc.invalidateQueries({ queryKey: queryKeys.financeDashboard() })
      qc.invalidateQueries({ queryKey: queryKeys.frontDeskDashboard() })
      qc.invalidateQueries({ queryKey: queryKeys.orderLookups() })
      qc.invalidateQueries({ queryKey: queryKeys.rackSearches() })
      qc.invalidateQueries({ queryKey: queryKeys.financeOutstanding() })
      qc.invalidateQueries({ queryKey: queryKeys.deliveries() })
    },
  })

  // Exposed so the form can mint a fresh key when the user edits the amount,
  // method, or reference — those describe a DIFFERENT payment, so they must not
  // reuse (and thus idempotently replay) the prior attempt's key.
  return Object.assign(mutation, { resetIdempotencyKey: idem.reset })
}
