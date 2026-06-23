'use client'

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { queryKeys } from '@/lib/query/keys'
import { apiGet, apiMutate, parseApiData } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { useStableIdempotencyKey } from '@/lib/api/idempotency'
import {
  InvoiceSchema, PaymentSchema, CreditNoteSchema, FinanceSummarySchema,
  type Invoice, type Payment, type CreditNote, type FinanceSummary,
} from '@/lib/api/schemas/finance'
import type { PaginatedData } from '@/lib/api/types'

interface InvoiceFilters {
  status?: string | string[]
  from?: string
  to?: string
  customer?: string
  page?: number
  per_page?: number
  [key: string]: unknown
}

export function useInvoices(filters: InvoiceFilters = {}) {
  return useQuery({
    queryKey: queryKeys.invoices(filters),
    queryFn: () => apiGet<PaginatedData<Invoice>>(ENDPOINTS.invoices, filters),
    select: (res) => res.data,
  })
}

export function useInvoice(id: number) {
  return useQuery({
    queryKey: queryKeys.invoice(id),
    queryFn: async () => {
      const env = await apiGet<Invoice>(ENDPOINTS.invoice(id))
      parseApiData(env, InvoiceSchema) // FE-008/FE-025
      return env
    },
    select: (res) => res.data,
    enabled: id > 0,
  })
}

export function usePaymentsByInvoice(invoiceId: number) {
  return useQuery({
    queryKey: queryKeys.payments({ invoice_id: invoiceId }),
    queryFn: () => apiGet<Payment[]>(ENDPOINTS.payments, { invoice_id: invoiceId }),
    select: (res) => res.data,
    enabled: invoiceId > 0,
  })
}

export function useFinanceDashboard() {
  return useQuery({
    queryKey: queryKeys.financeDashboard(),
    queryFn: async () => {
      const env = await apiGet<FinanceSummary>(ENDPOINTS.financeDashboard)
      parseApiData(env, FinanceSummarySchema) // FE-008/FE-025
      return env
    },
    select: (res) => res.data,
    staleTime: 2 * 60 * 1000,
  })
}

export function useOutstandingBalance(orderId: number) {
  return useQuery({
    queryKey: queryKeys.outstandingBalance(orderId),
    queryFn: () => apiGet<{ balance: number }>(ENDPOINTS.outstandingBalance(orderId)),
    select: (res) => res.data,
    enabled: orderId > 0,
  })
}

export function useCreateInvoice() {
  const qc = useQueryClient()
  const idem = useStableIdempotencyKey() // FE-007: stable key (no duplicate invoices)
  return useMutation({
    mutationFn: async (data: { order_id: number }) => {
      const env = await apiMutate<Invoice>('post', ENDPOINTS.invoices, data, idem.current)
      parseApiData(env, InvoiceSchema) // FE-008/FE-025
      return env
    },
    onSuccess: () => {
      idem.reset()
      qc.invalidateQueries({ queryKey: queryKeys.invoices() })
      qc.invalidateQueries({ queryKey: queryKeys.financeDashboard() }) // FE-014
    },
  })
}

export function useRecordPayment() {
  const qc = useQueryClient()
  const idem = useStableIdempotencyKey() // FE-007: stable key (no duplicate payments)
  return useMutation({
    mutationFn: (data: {
      invoice_id: number
      amount: number
      method: string
      reference?: string
      notes?: string
    }) => apiMutate<Payment>('post', ENDPOINTS.payments, data, idem.current)
      .then((env) => { parseApiData(env, PaymentSchema); return env }), // FE-008/FE-025
    onSuccess: (_, vars) => {
      idem.reset()
      qc.invalidateQueries({ queryKey: queryKeys.invoice(vars.invoice_id) })
      qc.invalidateQueries({ queryKey: queryKeys.payments({ invoice_id: vars.invoice_id }) })
      qc.invalidateQueries({ queryKey: queryKeys.financeDashboard() })
    },
  })
}

export function useIssueCreditNote(invoiceId: number) {
  const qc = useQueryClient()
  const idem = useStableIdempotencyKey() // FE-007: stable key (no duplicate credit notes)
  return useMutation({
    mutationFn: async (data: { reason: string; amount: number }) => {
      const env = await apiMutate<CreditNote>('post', ENDPOINTS.creditNote(invoiceId), data, idem.current)
      parseApiData(env, CreditNoteSchema) // FE-008/FE-025
      return env
    },
    onSuccess: () => {
      idem.reset()
      qc.invalidateQueries({ queryKey: queryKeys.invoice(invoiceId) })
      qc.invalidateQueries({ queryKey: queryKeys.invoices() })
      qc.invalidateQueries({ queryKey: queryKeys.financeDashboard() }) // FE-014
    },
  })
}
