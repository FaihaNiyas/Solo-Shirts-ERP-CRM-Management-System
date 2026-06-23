'use client'

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { queryKeys } from '@/lib/query/keys'
import { apiGet, apiMutate } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { useStableIdempotencyKey } from '@/lib/api/idempotency'

// RBAC management — role & permission CRUD (Owner/Admin). Backend enforces the
// `roles.manage` / `permissions.manage` permissions and protects system rows.

export interface Role {
  id: number
  name: string
  is_system: boolean
  permissions: string[]
  users_count: number
  created_at?: string
}

export interface Permission {
  id: number
  name: string
  group: string
  is_system: boolean
  roles_count: number
  created_at?: string
}

// ─── Roles ───────────────────────────────────────────────────────────────
export function useRoles() {
  return useQuery({
    queryKey: queryKeys.roles(),
    queryFn: () => apiGet<Role[]>(ENDPOINTS.roles),
    select: (res) => res.data,
  })
}

export function useCreateRole() {
  const qc = useQueryClient()
  const idem = useStableIdempotencyKey()
  return useMutation({
    mutationFn: (body: { name: string; permissions: string[] }) =>
      apiMutate<Role>('post', ENDPOINTS.roles, body, idem.current),
    onSuccess: () => {
      idem.reset()
      qc.invalidateQueries({ queryKey: queryKeys.roles() })
    },
  })
}

export function useUpdateRole(id: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (body: { name?: string; permissions?: string[] }) =>
      apiMutate<Role>('put', ENDPOINTS.role(id), body),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: queryKeys.roles() })
    },
  })
}

export function useDeleteRole() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => apiMutate<null>('delete', ENDPOINTS.role(id)),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: queryKeys.roles() })
    },
  })
}

// ─── Permissions ─────────────────────────────────────────────────────────
export function usePermissionsList() {
  return useQuery({
    queryKey: queryKeys.permissionsList(),
    queryFn: () => apiGet<Permission[]>(ENDPOINTS.permissions),
    select: (res) => res.data,
  })
}

export function useCreatePermission() {
  const qc = useQueryClient()
  const idem = useStableIdempotencyKey()
  return useMutation({
    mutationFn: (body: { name: string }) =>
      apiMutate<Permission>('post', ENDPOINTS.permissions, body, idem.current),
    onSuccess: () => {
      idem.reset()
      qc.invalidateQueries({ queryKey: queryKeys.permissionsList() })
    },
  })
}

export function useUpdatePermission(id: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (body: { name: string }) =>
      apiMutate<Permission>('put', ENDPOINTS.permission(id), body),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: queryKeys.permissionsList() })
    },
  })
}

export function useDeletePermission() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => apiMutate<null>('delete', ENDPOINTS.permission(id)),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: queryKeys.permissionsList() })
    },
  })
}
