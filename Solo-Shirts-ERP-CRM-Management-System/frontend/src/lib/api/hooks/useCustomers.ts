'use client'

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { queryKeys } from '@/lib/query/keys'
import { apiGet, apiMutate, parseApiData } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { CustomerSchema, type Customer, type FamilyMember } from '@/lib/api/schemas/customers'
import type { PaginatedData } from '@/lib/api/types'

interface CustomerFilters {
  search?: string
  page?: number
  per_page?: number
  [key: string]: unknown
}

export function useCustomers(filters: CustomerFilters = {}) {
  return useQuery({
    queryKey: queryKeys.customers(filters),
    queryFn: () => apiGet<PaginatedData<Customer>>(ENDPOINTS.customers, filters),
    select: (res) => res.data,
  })
}

export function useCustomer(id: number) {
  return useQuery({
    queryKey: queryKeys.customer(id),
    queryFn: async () => {
      const env = await apiGet<Customer>(ENDPOINTS.customer(id))
      parseApiData(env, CustomerSchema) // FE-008/FE-025
      return env
    },
    select: (res) => res.data,
    enabled: id > 0,
  })
}

export function useFamilyMembers(customerId: number) {
  return useQuery({
    queryKey: queryKeys.familyMembers(customerId),
    queryFn: () => apiGet<FamilyMember[]>(ENDPOINTS.familyMembers(customerId)),
    select: (res) => res.data,
    enabled: customerId > 0,
  })
}

export function useCreateCustomer() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: Partial<Customer>) =>
      apiMutate<Customer>('post', ENDPOINTS.customers, data)
        .then((env) => { parseApiData(env, CustomerSchema); return env }), // FE-008/FE-025
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: queryKeys.customers() })
    },
  })
}

export function useUpdateCustomer(id: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: Partial<Customer>) =>
      apiMutate<Customer>('put', ENDPOINTS.customer(id), data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: queryKeys.customer(id) })
      qc.invalidateQueries({ queryKey: queryKeys.customers() })
    },
  })
}

export function useCreateFamilyMember(customerId: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: Partial<FamilyMember>) =>
      apiMutate<FamilyMember>('post', ENDPOINTS.familyMembers(customerId), data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: queryKeys.familyMembers(customerId) })
    },
  })
}
