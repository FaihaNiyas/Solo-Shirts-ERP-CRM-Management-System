'use client'

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { queryKeys } from '@/lib/query/keys'
import { apiGet, apiMutate, parseApiData } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { MeasurementVersionSchema, type MeasurementProfile, type MeasurementVersion, type PendingApproval } from '@/lib/api/schemas/measurements'

export function useMeasurements(customerId: number) {
  return useQuery({
    queryKey: queryKeys.measurements(customerId),
    queryFn: () => apiGet<MeasurementProfile[]>(ENDPOINTS.measurements(customerId)),
    select: (res) => res.data,
    enabled: customerId > 0,
  })
}

export function useMeasurementVersions(profileId: number) {
  return useQuery({
    queryKey: queryKeys.measurementVersions(profileId),
    queryFn: () => apiGet<MeasurementVersion[]>(ENDPOINTS.measurementVersions(profileId)),
    select: (res) => res.data,
    enabled: profileId > 0,
  })
}

export function useMeasurementVersion(versionId: number) {
  return useQuery({
    queryKey: queryKeys.measurementVersion(versionId),
    queryFn: async () => {
      const env = await apiGet<MeasurementVersion>(ENDPOINTS.measurementVersion(versionId))
      parseApiData(env, MeasurementVersionSchema) // FE-008/FE-025
      return env
    },
    select: (res) => res.data,
    enabled: versionId > 0,
  })
}

export function usePendingApprovals(enabled = true) {
  return useQuery({
    queryKey: queryKeys.pendingApprovals(),
    queryFn: () => apiGet<PendingApproval[]>(ENDPOINTS.pendingApprovals),
    select: (res) => res.data,
    enabled, // only fetch for users who can approve (else it 403s)
  })
}

export function useCreateMeasurementProfile(customerId: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: Record<string, unknown>) =>
      apiMutate<MeasurementProfile>('post', ENDPOINTS.measurements(customerId), data).then((r) => r.data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: queryKeys.measurements(customerId) })
    },
  })
}

export function useUpdateMeasurementProfile(customerId: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ profileId, data }: { profileId: number; data: { name?: string; is_default?: boolean } }) =>
      apiMutate<MeasurementProfile>('patch', ENDPOINTS.measurementProfile(profileId), data).then((r) => r.data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: queryKeys.measurements(customerId) })
    },
  })
}

export function useDeleteMeasurementProfile(customerId: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (profileId: number) =>
      apiMutate('delete', ENDPOINTS.measurementProfile(profileId)),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: queryKeys.measurements(customerId) })
    },
  })
}

export function useCreateMeasurementVersion(profileId: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: Record<string, unknown>) =>
      apiMutate<MeasurementVersion>('post', ENDPOINTS.measurementVersions(profileId), data)
        .then((env) => { parseApiData(env, MeasurementVersionSchema); return env }), // FE-008/FE-025
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: queryKeys.measurementVersions(profileId) })
      qc.invalidateQueries({ queryKey: queryKeys.pendingApprovals() })
    },
  })
}

export function useApproveMeasurement(versionId: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (note?: string) =>
      apiMutate<MeasurementVersion>('post', ENDPOINTS.approveMeasurement(versionId), { note }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: queryKeys.pendingApprovals() })
      qc.invalidateQueries({ queryKey: queryKeys.measurementVersion(versionId) })
    },
  })
}

export function useRejectMeasurement(versionId: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (note: string) =>
      apiMutate<MeasurementVersion>('post', ENDPOINTS.rejectMeasurement(versionId), { note }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: queryKeys.pendingApprovals() })
      qc.invalidateQueries({ queryKey: queryKeys.measurementVersion(versionId) })
    },
  })
}
