'use client'

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { queryKeys } from '@/lib/query/keys'
import { invalidateOrderCaches } from '@/lib/query/invalidateOrders'
import { apiGet, apiMutate, parseApiData } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { useStableIdempotencyKey } from '@/lib/api/idempotency'
import { RawProductionItemSchema, type ProductionItem, type Board, type ProductionIssue } from '@/lib/api/schemas/production'
import type { TransitionHistoryItem } from '@/lib/api/schemas/audit'

// Backend uses snake_case states; frontend uses PascalCase
const SNAKE_TO_PASCAL: Record<string, string> = {
  draft: 'Draft',
  fabric_allocated: 'FabricAllocated',
  cutting: 'Cutting',
  tailoring: 'Tailoring',
  kaja_button: 'KajaButton',
  finishing: 'Finishing',
  qc: 'QC',
  rework: 'Rework',
  packing: 'Packing',
  ready_for_delivery: 'ReadyForDelivery',
  delivered: 'Delivered',
  cancelled: 'Cancelled',
}

function toPascalState(s: string): string {
  return SNAKE_TO_PASCAL[s] ?? s
}

// Reverse map: the board/cards work in PascalCase, but the backend transition
// endpoint validates the snake_case state (OrderItem::WORKFLOW_STATES). Convert
// back on send. Idempotent — an already-snake value passes through unchanged.
const PASCAL_TO_SNAKE: Record<string, string> = Object.fromEntries(
  Object.entries(SNAKE_TO_PASCAL).map(([snake, pascal]) => [pascal, snake]),
)

function toSnakeState(s: string): string {
  return PASCAL_TO_SNAKE[s] ?? s
}

// FE-025: single source of truth for the raw→FE production-item mapping
// (snake_case state/product_type → PascalCase production_state/garment_type).
// Used by both the board and the item-detail hooks so the transform is centralized.
function transformProductionItem(it: Record<string, unknown>): ProductionItem {
  return {
    ...it,
    production_state: toPascalState((it.state as string) ?? ''),
    garment_type: it.product_type,
    allowed_transitions: ((it.allowed_transitions as string[]) ?? []).map(toPascalState),
  } as unknown as ProductionItem
}

function selectBoard(res: { data: Board }) {
  const board = res.data
  if (board && Array.isArray((board as { columns: unknown }).columns)) {
    type RawCol = { state: string; items: Array<Record<string, unknown>> }
    const cols = (board as unknown as { columns: RawCol[] }).columns
    const columnsObj: Record<string, ProductionItem[]> = {}
    for (const col of cols) {
      columnsObj[toPascalState(col.state)] = (col.items ?? []).map(transformProductionItem)
    }
    return { ...board, columns: columnsObj }
  }
  return board
}

// Board filters (Phase E). All optional; empty values are dropped before the call.
export interface BoardFilters {
  search?: string
  priority?: string
  supervisor_id?: number
  stage?: string
  date_from?: string
  date_to?: string
  delayed?: boolean
  rework?: boolean
  ready?: boolean
}

function boardParams(mine: boolean, filters: BoardFilters): Record<string, string | number> {
  const params: Record<string, string | number> = {}
  if (mine) params.mine = 1
  for (const [k, v] of Object.entries(filters)) {
    if (v === undefined || v === null || v === '' || v === false) continue
    params[k] = v === true ? 1 : v
  }
  return params
}

// polling: true only on the live Production page; dashboard passes false.
// mine: scope the board to the caller's supervised sections (Phase C).
// filters: search / priority / delayed / rework / ready / date range (Phase E).
export function useProductionBoard(
  { polling = false, mine = false, filters = {} }: { polling?: boolean; mine?: boolean; filters?: BoardFilters } = {},
) {
  return useQuery({
    queryKey: [...queryKeys.productionBoard(), { mine, filters }],
    queryFn: () => apiGet<Board>(ENDPOINTS.productionBoard, boardParams(mine, filters)),
    select: selectBoard,
    refetchInterval: polling ? 30_000 : false,
    staleTime: polling ? 30_000 : 3 * 60 * 1000,
  })
}

// --- Order thread: sibling sub-orders of a Main Order --------------------

export interface OrderSummaryItem {
  id: number
  item_code: string | null
  product_type: string | null
  state: string
  state_label: string | null
  is_ready: boolean
  is_delivered: boolean
  is_cancelled: boolean
  is_on_hold: boolean
  is_overdue: boolean
}

export interface OrderProductionSummary {
  order_id: number
  order_code: string | null
  customer_name: string | null
  expected_delivery_date: string | null
  progress: {
    aggregate_status: string
    aggregate_status_label: string
    summary_label: string
    progress: Record<string, number>
  }
  items: OrderSummaryItem[]
}

// Read-only "order thread" for the board's sibling strip. Disabled until a
// drawer actually opens (orderId > 0) so we never fetch on every card render.
export function useOrderProductionSummary(orderId: number, enabled = true) {
  return useQuery({
    queryKey: queryKeys.productionOrderSummary(orderId),
    queryFn: () => apiGet<OrderProductionSummary>(ENDPOINTS.productionOrderSummary(orderId)),
    select: (res) => res.data,
    enabled: enabled && orderId > 0,
    staleTime: 30_000,
  })
}

// --- Kanban Phase D: live production dashboard ---------------------------

export interface ProductionDashboard {
  total_active: number
  by_stage: Record<string, number>
  delayed: number
  urgent: number
  on_hold: number
  in_rework: number
  pending_qc: number
  ready_for_delivery: number
  completed_today: number
  avg_hours_in_stage: Record<string, number>
  bottleneck_stage: { stage: string; avg_hours: number } | null
}

export function useProductionDashboard({ polling = false }: { polling?: boolean } = {}) {
  return useQuery({
    queryKey: queryKeys.productionDashboard(),
    queryFn: () => apiGet<ProductionDashboard>(ENDPOINTS.productionDashboard),
    select: (res) => res.data,
    refetchInterval: polling ? 30_000 : false,
  })
}

export function useProductionItem(id: number) {
  return useQuery({
    queryKey: queryKeys.productionItem(id),
    queryFn: async () => {
      const env = await apiGet<ProductionItem>(ENDPOINTS.productionItem(id))
      parseApiData(env, RawProductionItemSchema) // FE-008/FE-025: validate the raw item
      return env
    },
    // FE-025: apply the same transform as the board so the detail reads
    // garment_type/production_state instead of the previously-blank drift.
    select: (res) => transformProductionItem(res.data as unknown as Record<string, unknown>),
    enabled: id > 0,
  })
}

export function useProductionHistory(id: number) {
  return useQuery({
    queryKey: queryKeys.productionHistory(id),
    queryFn: () => apiGet<TransitionHistoryItem[]>(ENDPOINTS.productionHistory(id)),
    select: (res) => res.data,
    enabled: id > 0,
  })
}

export interface TransitionPayload {
  to: string
  notes?: string
  completed_qty?: number
  rejected_qty?: number
  attachment_path?: string
  // Pickup box / shelf entered when staging an item for delivery.
  delivery_box_code?: string
}

export function useTransitionItem(itemId: number) {
  const qc = useQueryClient()
  const idem = useStableIdempotencyKey() // FE-007: stable key across double-submit
  return useMutation({
    mutationFn: ({ to, ...rest }: TransitionPayload) =>
      apiMutate<ProductionItem>(
        'post',
        ENDPOINTS.transitionItem(itemId),
        { to: toSnakeState(to), ...rest },
        idem.current,
      ),
    onSuccess: () => {
      idem.reset()
      qc.invalidateQueries({ queryKey: queryKeys.productionBoard() })
      qc.invalidateQueries({ queryKey: queryKeys.productionItem(itemId) })
      qc.invalidateQueries({ queryKey: queryKeys.productionHistory(itemId) })
      qc.invalidateQueries({ queryKey: queryKeys.auditTransitions(itemId) }) // FE-014
      // A stage move recomputes the parent order's aggregate progress, so the
      // order list/detail (which read order.progress) must refetch too —
      // otherwise the status only updates after a manual page refresh.
      invalidateOrderCaches(qc)
    },
  })
}

// --- Kanban Phase B: issues & on-hold -------------------------------------

// Shared invalidation for any issue/hold mutation: the board (badges), the item
// detail, and the item's issue list all reflect the change.
function useInvalidateItem(itemId: number) {
  const qc = useQueryClient()
  return () => {
    qc.invalidateQueries({ queryKey: queryKeys.productionBoard() })
    qc.invalidateQueries({ queryKey: queryKeys.productionItem(itemId) })
    qc.invalidateQueries({ queryKey: queryKeys.productionIssues(itemId) })
    // Hold/resume flips the item's on-hold state, which the order summary shows.
    invalidateOrderCaches(qc)
  }
}

export function useItemIssues(itemId: number, enabled = true) {
  return useQuery({
    queryKey: queryKeys.productionIssues(itemId),
    queryFn: () => apiGet<ProductionIssue[]>(ENDPOINTS.productionIssues(itemId)),
    select: (res) => res.data,
    enabled: enabled && itemId > 0,
  })
}

export function useReportIssue(itemId: number) {
  const invalidate = useInvalidateItem(itemId)
  return useMutation({
    mutationFn: (body: { issue_type: string; description: string }) =>
      apiMutate<ProductionIssue>('post', ENDPOINTS.productionIssues(itemId), body),
    onSuccess: invalidate,
  })
}

export function useResolveIssue(itemId: number) {
  const invalidate = useInvalidateItem(itemId)
  return useMutation({
    mutationFn: ({ issueId, notes }: { issueId: number; notes?: string }) =>
      apiMutate<ProductionIssue>('post', ENDPOINTS.resolveIssue(issueId), { notes }),
    onSuccess: invalidate,
  })
}

export function useHoldItem(itemId: number) {
  const invalidate = useInvalidateItem(itemId)
  return useMutation({
    mutationFn: (body: { reason: string }) =>
      apiMutate<ProductionItem>('post', ENDPOINTS.holdItem(itemId), body),
    onSuccess: invalidate,
  })
}

export function useResumeItem(itemId: number) {
  const invalidate = useInvalidateItem(itemId)
  return useMutation({
    mutationFn: () => apiMutate<ProductionItem>('post', ENDPOINTS.resumeItem(itemId)),
    onSuccess: invalidate,
  })
}

// --- Kanban Phase C: section-supervisor assignment -----------------------

export interface StageSupervisor {
  id: number
  branch_id: number
  user_id: number
  user_name?: string | null
  stage: string
  created_at?: string | null
}

export function useStageSupervisors() {
  return useQuery({
    queryKey: queryKeys.stageSupervisors(),
    queryFn: () => apiGet<StageSupervisor[]>(ENDPOINTS.stageSupervisors),
    select: (res) => res.data,
  })
}

// The stages the current user supervises — drives the "my section" board toggle.
export function useMySections() {
  return useQuery({
    queryKey: queryKeys.mySections(),
    queryFn: () => apiGet<{ stages: string[] }>(ENDPOINTS.mySections),
    select: (res) => res.data.stages,
  })
}

export function useAssignSupervisor() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (body: { user_id: number; stage: string }) =>
      apiMutate<StageSupervisor>('post', ENDPOINTS.stageSupervisors, body),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: queryKeys.stageSupervisors() })
      qc.invalidateQueries({ queryKey: queryKeys.productionBoard() })
    },
  })
}

export function useUnassignSupervisor() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => apiMutate('delete', ENDPOINTS.stageSupervisor(id)),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: queryKeys.stageSupervisors() })
      qc.invalidateQueries({ queryKey: queryKeys.productionBoard() })
    },
  })
}

// --- Kanban Phase F: in-app production notifications ----------------------

export interface ProductionNotificationItem {
  id: number
  type: string
  title: string
  body?: string | null
  order_item_id?: number | null
  is_read: boolean
  read_at?: string | null
  created_at: string
}

export interface ProductionNotificationFeed {
  unread_count: number
  items: ProductionNotificationItem[]
}

export function useProductionNotifications({ polling = false }: { polling?: boolean } = {}) {
  return useQuery({
    queryKey: queryKeys.productionNotifications(),
    queryFn: () => apiGet<ProductionNotificationFeed>(ENDPOINTS.productionNotifications),
    select: (res) => res.data,
    refetchInterval: polling ? 30_000 : false,
  })
}

export function useMarkNotificationRead() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => apiMutate('post', ENDPOINTS.readNotification(id)),
    onSuccess: () => qc.invalidateQueries({ queryKey: queryKeys.productionNotifications() }),
  })
}

export function useMarkAllNotificationsRead() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: () => apiMutate('post', ENDPOINTS.readAllNotifications),
    onSuccess: () => qc.invalidateQueries({ queryKey: queryKeys.productionNotifications() }),
  })
}
