'use client'

import { useQuery } from '@tanstack/react-query'
import { apiGet } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { queryKeys } from '@/lib/query/keys'

export interface DashboardListBase {
  order_id?: number
  order_code?: string | null
  customer_name: string | null
  phone: string | null
  phone_masked: string | null
}

export interface DueTodayRow extends DashboardListBase {
  delivery_date: string | null
  status: string
  balance_amount: number
}

export interface ReadyPickupRow extends DashboardListBase {
  rack_slots: string[]
  balance_amount: number
  payment_status: 'balance_pending' | 'paid'
}

export interface PendingBalanceRow extends DashboardListBase {
  balance_amount: number
  last_payment_date: string | null
}

export interface ActiveAlterationRow {
  alteration_id: number
  customer_name: string | null
  phone: string | null
  phone_masked: string | null
  order_code: string | null
  item_code: string | null
  status: string
  priority: string
}

export interface FrontDeskDashboardData {
  today: {
    new_orders_count: number
    confirmed_orders_count: number
    intake_preparation_count: number
    due_today_count: number
    overdue_count: number
  }
  pickup: {
    ready_for_pickup_count: number
    ready_with_balance_pending_count: number
    ready_fully_paid_count: number
  }
  payments: {
    pending_balance_orders_count: number
    pending_balance_amount: number
    payments_collected_today: number
  }
  alterations: {
    active_count: number
    ready_count: number
    intake_count: number
  }
  notifications: {
    whatsapp_failed_count: number
    whatsapp_simulated_today_count: number
  }
  quick_lists: {
    due_today: DueTodayRow[]
    ready_for_pickup: ReadyPickupRow[]
    pending_balance: PendingBalanceRow[]
    active_alterations: ActiveAlterationRow[]
  }
}

export function useFrontDeskDashboard() {
  return useQuery({
    queryKey: queryKeys.frontDeskDashboard(),
    queryFn: () => apiGet<FrontDeskDashboardData>(ENDPOINTS.frontDeskDashboard),
    select: (res) => res.data,
    staleTime: 30_000,
  })
}
