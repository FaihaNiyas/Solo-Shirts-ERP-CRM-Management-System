export interface ApiEnvelope<T = unknown> {
  success: boolean
  message: string
  data: T
  request_id: string
}

export interface ApiError {
  message: string
  code: string
  request_id: string
  errors?: Record<string, string[]>
}

export type RequestId = string

export interface PaginatedData<T> {
  data: T[]
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number
    to: number
  }
  links: {
    first: string | null
    last: string | null
    prev: string | null
    next: string | null
  }
  // Top-level aliases for convenience (matches some backend response shapes)
  total?: number
  per_page?: number
  current_page?: number
}

export interface Branch {
  id: number
  name: string
  code: string
  address?: string
  is_active: boolean
}

export interface User {
  id: number
  name: string
  email: string
  roles: string[]
  permissions: string[]
  branch_id: number | null
  branch: Branch | null
  two_factor_enabled: boolean
}

export interface AuthSession {
  user: User
  token: string
  expires_at: string
}
