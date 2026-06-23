'use client'

import axios, { AxiosError, type AxiosRequestConfig } from 'axios'
import type { ZodType } from 'zod'
import type { ApiEnvelope, ApiError } from './types'
import { ENDPOINTS } from './endpoints'
import { getToken, clearSession } from '@/lib/auth/session'

const BASE_URL = process.env.NEXT_PUBLIC_API_URL ?? 'http://localhost:8000'

export const apiClient = axios.create({
  baseURL: BASE_URL,
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
  withCredentials: false,
})

// ---- Request interceptor ----
apiClient.interceptors.request.use((config) => {
  const token = getToken()
  if (token) {
    config.headers['Authorization'] = `Bearer ${token}`
  }
  // FE-018: branch context is carried by the Sanctum token's active_branch_id
  // (set via POST /auth/switch-branch); the backend resolves branch from the
  // token, not from a header — so no X-Branch-Id is sent.
  return config
})

let isRefreshing = false
let pendingQueue: Array<{
  resolve: (value: string) => void
  reject: (reason?: unknown) => void
}> = []

function processQueue(error: Error | null, token: string | null = null) {
  pendingQueue.forEach(({ resolve, reject }) => {
    if (error) reject(error)
    else resolve(token!)
  })
  pendingQueue = []
}

// ---- Response interceptor — 401 → silent refresh ----
apiClient.interceptors.response.use(
  (response) => response,
  async (error: AxiosError) => {
    const originalRequest = error.config as AxiosRequestConfig & { _retry?: boolean }

    // A 401 from the auth endpoints themselves (bad credentials, an expired
    // refresh token) must surface to the caller — never kick off a refresh,
    // which would recurse and hang the request.
    const url = originalRequest.url ?? ''
    const isAuthEndpoint =
      url.includes('/auth/login') || url.includes('/auth/refresh') || url.includes('/auth/logout')

    if (error.response?.status === 401 && !originalRequest._retry && !isAuthEndpoint) {
      if (isRefreshing) {
        return new Promise((resolve, reject) => {
          pendingQueue.push({ resolve, reject })
        }).then((token) => {
          if (originalRequest.headers) {
            originalRequest.headers['Authorization'] = `Bearer ${token}`
          }
          return apiClient(originalRequest)
        })
      }

      originalRequest._retry = true
      isRefreshing = true

      try {
        const { data } = await apiClient.post<ApiEnvelope<{ token: string }>>(
          ENDPOINTS.auth.refresh,
        )
        const newToken = data.data.token
        if (typeof window !== 'undefined') {
          sessionStorage.setItem('ss_token', newToken)
        }
        processQueue(null, newToken)
        if (originalRequest.headers) {
          originalRequest.headers['Authorization'] = `Bearer ${newToken}`
        }
        return apiClient(originalRequest)
      } catch (refreshError) {
        processQueue(refreshError as Error, null)
        clearSession()
        if (typeof window !== 'undefined') {
          window.location.href = '/login'
        }
        return Promise.reject(refreshError)
      } finally {
        isRefreshing = false
      }
    }

    return Promise.reject(normalizeError(error))
  },
)

// ---- Normalize every API error to a consistent shape ----
export function normalizeError(error: AxiosError): ApiError {
  const data = error.response?.data as Record<string, unknown> | undefined
  // FE-024: fall back to the X-Request-Id header when the body has none
  // (e.g. a 500 HTML page, a gateway error, or a network failure).
  const headerRequestId = error.response?.headers?.['x-request-id'] as string | undefined
  return {
    message: (data?.message as string) ?? error.message ?? 'An error occurred',
    code: (data?.code as string) ?? 'UNKNOWN_ERROR',
    request_id: (data?.request_id as string) ?? headerRequestId ?? '',
    errors: (data?.errors as Record<string, string[]>) ?? undefined,
  }
}

// ---- FE-024: a 2xx response carrying { success:false } is NOT success ----
// The backend always returns the standard envelope; if `success` is false (or
// the envelope is malformed) we reject it as an ApiError so the UI never treats
// it as valid data.
function ensureSuccess<T>(envelope: ApiEnvelope<T>): ApiEnvelope<T> {
  if (!envelope || typeof envelope.success !== 'boolean' || envelope.success === false) {
    const e = envelope as Partial<ApiEnvelope<T>> & { code?: string; errors?: Record<string, string[]> }
    const err: ApiError = {
      message: e?.message ?? 'The request did not succeed.',
      code: e?.code ?? 'API_ERROR',
      request_id: e?.request_id ?? '',
      errors: e?.errors,
    }
    throw err
  }
  return envelope
}

// ---- FE-008: validate envelope.data against a Zod schema ----
// A malformed/contract-drifted response becomes a controlled FRONTEND_SCHEMA_MISMATCH
// ApiError (shown in the ErrorDrawer with request_id) rather than a silent bad-data
// bug or blank crash. Returns the parsed (typed) data.
export function parseApiData<T>(envelope: ApiEnvelope<unknown>, schema: ZodType<T>): T {
  const result = schema.safeParse(envelope.data)
  if (!result.success) {
    if (process.env.NODE_ENV !== 'production') {
      // eslint-disable-next-line no-console
      console.warn('[FRONTEND_SCHEMA_MISMATCH]', result.error.issues)
    }
    const err: ApiError = {
      message: 'Frontend schema mismatch. Backend contract may have changed.',
      code: 'FRONTEND_SCHEMA_MISMATCH',
      request_id: envelope.request_id ?? '',
    }
    throw err
  }
  return result.data
}

// ---- Typed GET helper ----
export async function apiGet<T>(
  url: string,
  params?: Record<string, unknown>,
): Promise<ApiEnvelope<T>> {
  const { data } = await apiClient.get<ApiEnvelope<T>>(url, { params })
  return ensureSuccess(data)
}

// ---- Typed mutation helper (POST/PUT/PATCH/DELETE) ----
export async function apiMutate<T>(
  method: 'post' | 'put' | 'patch' | 'delete',
  url: string,
  body?: unknown,
  idempotencyKey?: string,
): Promise<ApiEnvelope<T>> {
  const key = idempotencyKey ?? crypto.randomUUID()
  const { data } = await apiClient.request<ApiEnvelope<T>>({
    method,
    url,
    data: body,
    headers: { 'Idempotency-Key': key },
  })
  return ensureSuccess(data)
}

export function generateIdempotencyKey(): string {
  return typeof crypto !== 'undefined' ? crypto.randomUUID() : Math.random().toString(36).slice(2)
}

// Convenience wrappers — thin aliases over apiMutate
export async function apiPost<T>(
  url: string,
  body?: unknown,
  config?: { headers?: Record<string, string> },
): Promise<ApiEnvelope<T>> {
  const key = config?.headers?.['Idempotency-Key'] ?? crypto.randomUUID()
  const { data } = await apiClient.request<ApiEnvelope<T>>({
    method: 'post',
    url,
    data: body,
    headers: { 'Idempotency-Key': key, ...(config?.headers ?? {}) },
  })
  return ensureSuccess(data)
}

export async function apiPut<T>(
  url: string,
  body?: unknown,
  config?: { headers?: Record<string, string> },
): Promise<ApiEnvelope<T>> {
  const key = config?.headers?.['Idempotency-Key'] ?? crypto.randomUUID()
  const { data } = await apiClient.request<ApiEnvelope<T>>({
    method: 'put',
    url,
    data: body,
    headers: { 'Idempotency-Key': key, ...(config?.headers ?? {}) },
  })
  return ensureSuccess(data)
}
