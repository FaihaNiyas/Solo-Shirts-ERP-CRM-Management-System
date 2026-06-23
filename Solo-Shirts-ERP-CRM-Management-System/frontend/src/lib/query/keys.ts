// TanStack Query key factory — all keys derive from this object.
// Invalidating a parent key invalidates all children.

export const queryKeys = {
  // Auth
  me: () => ['me'] as const,

  // Branches
  branches: () => ['branches'] as const,
  branch: (id: number) => ['branches', id] as const,

  // Users
  users: (filters?: Record<string, unknown>) => ['users', filters ?? {}] as const,
  user: (id: number) => ['users', id] as const,

  // Roles & permissions (RBAC management)
  roles: () => ['roles'] as const,
  role: (id: number) => ['roles', id] as const,
  permissionsList: () => ['permissions'] as const,

  // Customers
  customers: (filters?: Record<string, unknown>) => ['customers', filters ?? {}] as const,
  customer: (id: number) => ['customers', id] as const,
  familyMembers: (cid: number) => ['customers', cid, 'family-members'] as const,

  // Measurements
  measurements: (cid: number) => ['customers', cid, 'measurements'] as const,
  measurementVersions: (profileId: number) => ['measurements', 'profiles', profileId, 'versions'] as const,
  measurementVersion: (versionId: number) => ['measurements', 'versions', versionId] as const, // FE-021
  pendingApprovals: () => ['measurements', 'pending-approval'] as const,

  // Orders
  orders: (filters?: Record<string, unknown>) => ['orders', filters ?? {}] as const,
  order: (id: number) => ['orders', id] as const,
  orderItems: (id: number) => ['orders', id, 'items'] as const,
  // Phase 1/2 — per-item payment summary + pickup batches
  itemPaymentSummary: (orderId: number, itemId: number) => ['orders', orderId, 'items', itemId, 'payment-summary'] as const,
  pickupBatches: (orderId: number) => ['orders', orderId, 'pickup-batches'] as const,
  pickupBatch: (orderId: number, batchId: number) => ['orders', orderId, 'pickup-batches', batchId] as const,

  // Production
  productionBoard: () => ['production', 'board'] as const,
  productionOrderSummary: (orderId: number) => ['production', 'orders', orderId, 'summary'] as const,
  productionDashboard: () => ['production', 'dashboard'] as const,
  productionItem: (id: number) => ['production', 'items', id] as const,
  productionHistory: (id: number) => ['production', 'items', id, 'history'] as const,
  productionIssues: (id: number) => ['production', 'items', id, 'issues'] as const,
  stageSupervisors: () => ['production', 'stage-supervisors'] as const,
  mySections: () => ['production', 'my-sections'] as const,
  productionNotifications: () => ['production', 'notifications'] as const,

  // Cutting
  cuttingQueue: () => ['cutting', 'queue'] as const,
  cuttingBundle: (id: number) => ['cutting', 'bundles', id] as const,

  // Tailoring
  tailoringAssignments: (filters?: Record<string, unknown>) => ['tailoring', 'assignments', filters ?? {}] as const,

  // QC
  qcHistory: (id: number) => ['qc', 'items', id, 'history'] as const,
  defectCategories: () => ['qc', 'defect-categories'] as const,

  // Inventory
  fabricRolls: (filters?: Record<string, unknown>) => ['inventory', 'fabric-rolls', filters ?? {}] as const,
  fabricRoll: (id: number) => ['inventory', 'fabric-rolls', id] as const,
  inventoryMovements: (filters?: Record<string, unknown>) => ['inventory', 'movements', filters ?? {}] as const,
  lowStock: () => ['inventory', 'low-stock'] as const,
  fabricTypes: () => ['inventory', 'fabric-types'] as const,
  suppliers: () => ['inventory', 'suppliers'] as const,
  purchaseOrders: (filters?: Record<string, unknown>) => ['inventory', 'purchase-orders', filters ?? {}] as const,

  // Damage
  damageReports: (filters?: Record<string, unknown>) => ['damage-reports', filters ?? {}] as const,
  damageReport: (id: number) => ['damage-reports', id] as const,

  // Rack
  rackSlots: () => ['rack', 'slots'] as const,

  // Deliveries
  deliveries: (filters?: Record<string, unknown>) => ['deliveries', filters ?? {}] as const,
  delivery: (id: number) => ['deliveries', id] as const,

  // Finance
  invoices: (filters?: Record<string, unknown>) => ['finance', 'invoices', filters ?? {}] as const,
  invoice: (id: number) => ['finance', 'invoices', id] as const,
  payments: (filters?: Record<string, unknown>) => ['finance', 'payments', filters ?? {}] as const,
  financeDashboard: () => ['finance', 'dashboard'] as const,
  outstandingBalance: (orderId: number) => ['finance', 'orders', orderId, 'outstanding-balance'] as const,

  // Order payments & handover (counter workflow).
  // NOTE: these mirror the tuples in useOrderPayments/useHandover so a single
  // invalidation from any hook keeps the payment, handover-gate and dashboards in sync.
  orderPayments: (orderId: number) => ['order-payments', orderId] as const,
  handoverEligibility: (orderId: number) => ['handover-eligibility', orderId] as const,

  // Front Desk dashboard (counter summary + quick lists)
  frontDeskDashboard: () => ['front-desk-dashboard'] as const,

  // Front Desk order lookup & ready-rack search. The bare-prefix variants are
  // used to invalidate every cached query term at once after a balance payment,
  // so a paid order stops showing "Balance Pending" without a manual refresh.
  orderLookup: (q: string) => ['order-lookup', q] as const,
  orderLookups: () => ['order-lookup'] as const,
  rackSearch: (q: string) => ['rack-search', q] as const,
  rackSearches: () => ['rack-search'] as const,

  // Finance outstanding-balance list (customer-level rollup page)
  financeOutstanding: () => ['finance', 'outstanding'] as const,

  // Notifications
  notifications: () => ['notifications'] as const,

  // Reports
  reports: () => ['reports'] as const,
  reportJob: (id: string) => ['reports', 'jobs', id] as const,

  // Audit
  auditActivities: (filters?: Record<string, unknown>) => ['audit', 'activities', filters ?? {}] as const,
  auditTransitions: (orderItemId: number) => ['audit', 'transitions', orderItemId] as const,
} as const
