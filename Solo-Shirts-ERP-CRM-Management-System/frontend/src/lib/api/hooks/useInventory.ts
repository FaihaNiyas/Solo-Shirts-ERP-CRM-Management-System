'use client'

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { queryKeys } from '@/lib/query/keys'
import { apiGet, apiMutate, parseApiData } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { FabricRollSchema, type FabricRoll, type InventoryMovement, type FabricType, type Supplier, type PurchaseOrder } from '@/lib/api/schemas/inventory'
import type { PaginatedData } from '@/lib/api/types'

interface FabricRollFilters {
  fabric_type_id?: number
  status?: string
  low_stock?: boolean
  search?: string
  page?: number
  per_page?: number
  [key: string]: unknown
}

export function useFabricRolls(filters: FabricRollFilters = {}) {
  return useQuery({
    queryKey: queryKeys.fabricRolls(filters),
    queryFn: () => apiGet<PaginatedData<FabricRoll>>(ENDPOINTS.fabricRolls, filters),
    select: (res) => res.data,
  })
}

export function useFabricRoll(id: number) {
  return useQuery({
    queryKey: queryKeys.fabricRoll(id),
    queryFn: async () => {
      const env = await apiGet<FabricRoll>(ENDPOINTS.fabricRoll(id))
      parseApiData(env, FabricRollSchema) // FE-008/FE-025
      return env
    },
    select: (res) => res.data,
    enabled: id > 0,
  })
}

export function useInventoryMovements(rollId: number) {
  return useQuery({
    queryKey: queryKeys.inventoryMovements({ roll_id: rollId }),
    queryFn: () => apiGet<InventoryMovement[]>(ENDPOINTS.inventoryMovements, { roll_id: rollId }),
    select: (res) => res.data,
    enabled: rollId > 0,
  })
}

// Phase 8A — per-roll ledger (breakdown + movement history) and reorder threshold.

export interface RollBreakdown {
  remaining_metres: string
  available_metres: string
  reserved_metres: string
  consumed_metres: string
  damaged_metres: string
}

export interface RollLedger {
  roll: FabricRoll
  breakdown: RollBreakdown
  movements: InventoryMovement[]
}

export function useFabricRollLedger(rollId: number) {
  return useQuery({
    queryKey: [...queryKeys.fabricRoll(rollId), 'ledger'],
    queryFn: () => apiGet<RollLedger>(ENDPOINTS.fabricRollLedger(rollId)),
    select: (res) => res.data,
    enabled: rollId > 0,
  })
}

export function useSetRollThreshold(id: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (low_stock_threshold_metres: number | null) =>
      apiMutate<FabricRoll>('patch', ENDPOINTS.fabricRollThreshold(id), { low_stock_threshold_metres }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: queryKeys.fabricRoll(id) })
      qc.invalidateQueries({ queryKey: queryKeys.fabricRolls() })
      qc.invalidateQueries({ queryKey: queryKeys.lowStock() })
    },
  })
}

export function useLowStock() {
  return useQuery({
    queryKey: queryKeys.lowStock(),
    queryFn: () => apiGet<FabricRoll[]>(ENDPOINTS.lowStock),
    select: (res) => res.data,
  })
}

export function useFabricTypes() {
  return useQuery({
    queryKey: queryKeys.fabricTypes(),
    queryFn: () => apiGet<FabricType[]>(ENDPOINTS.fabricTypes),
    select: (res) => res.data,
    staleTime: 10 * 60 * 1000,  // fabric types change very rarely
    gcTime: 30 * 60 * 1000,
  })
}

export function useSuppliers() {
  return useQuery({
    queryKey: queryKeys.suppliers(),
    queryFn: () => apiGet<PaginatedData<Supplier>>(ENDPOINTS.suppliers),
    select: (res) => res.data,
    staleTime: 10 * 60 * 1000,  // supplier list changes rarely
    gcTime: 30 * 60 * 1000,
  })
}

export function usePurchaseOrders(filters: Record<string, unknown> = {}) {
  return useQuery({
    queryKey: queryKeys.purchaseOrders(filters),
    queryFn: () => apiGet<PaginatedData<PurchaseOrder>>(ENDPOINTS.purchaseOrders, filters),
    select: (res) => res.data,
  })
}

export function usePurchaseOrder(id: number) {
  return useQuery({
    queryKey: [...queryKeys.purchaseOrders({}), id],
    queryFn: () => apiGet<PurchaseOrder>(ENDPOINTS.purchaseOrder(id)),
    select: (res) => res.data,
    enabled: id > 0,
  })
}

export function useCreateFabricRoll() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: Partial<FabricRoll>) =>
      apiMutate<FabricRoll>('post', ENDPOINTS.fabricRolls, data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: queryKeys.fabricRolls() })
      qc.invalidateQueries({ queryKey: queryKeys.lowStock() })
    },
  })
}

export function useAdjustFabricRoll(id: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: { type: 'adjust_in' | 'adjust_out'; metres: number; reason: string }) =>
      apiMutate<FabricRoll>('post', ENDPOINTS.adjustFabric(id), data)
        .then((env) => { parseApiData(env, FabricRollSchema); return env }), // FE-008/FE-025
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: queryKeys.fabricRoll(id) })
      qc.invalidateQueries({ queryKey: queryKeys.fabricRolls() })
      qc.invalidateQueries({ queryKey: queryKeys.inventoryMovements({ roll_id: id }) })
    },
  })
}

export function useCreateSupplier() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: Partial<Supplier>) =>
      apiMutate<Supplier>('post', ENDPOINTS.suppliers, data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: queryKeys.suppliers() })
    },
  })
}

export function useUpdateSupplier(id: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: Partial<Supplier>) =>
      apiMutate<Supplier>('put', ENDPOINTS.supplier(id), data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: queryKeys.suppliers() })
    },
  })
}

export interface CreatePoInput {
  supplier_id: number
  notes?: string
  items: { fabric_type_id: number; colour?: string; quantity_metres: number; unit_price_paise: number }[]
}

export function useCreatePO() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: CreatePoInput) => apiMutate<PurchaseOrder>('post', ENDPOINTS.purchaseOrders, data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: queryKeys.purchaseOrders() })
    },
  })
}

export function usePlacePO(id: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: () => apiMutate('post', ENDPOINTS.placePO(id), {}),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: queryKeys.purchaseOrders() })
    },
  })
}

export interface ReceivePoInput {
  notes?: string
  lines: { purchase_order_item_id: number; metres: number; rack_location?: string }[]
}

export function useReceivePO(id: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: ReceivePoInput) => apiMutate('post', ENDPOINTS.receivePO(id), data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: queryKeys.purchaseOrders() })
      qc.invalidateQueries({ queryKey: queryKeys.fabricRolls() })
      qc.invalidateQueries({ queryKey: queryKeys.lowStock() })
    },
  })
}

export function useCancelPO(id: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: { reason?: string }) => apiMutate('post', ENDPOINTS.cancelPO(id), data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: queryKeys.purchaseOrders() })
    },
  })
}
