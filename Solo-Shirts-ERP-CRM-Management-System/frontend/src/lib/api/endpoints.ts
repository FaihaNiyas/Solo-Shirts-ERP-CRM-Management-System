// All API endpoint strings — never hardcode these in components.
const V1 = '/api/v1'

export const ENDPOINTS = {
  // Auth
  auth: {
    login:          `${V1}/auth/login`,
    logout:         `${V1}/auth/logout`,
    refresh:        `${V1}/auth/refresh`,
    me:             `${V1}/auth/me`,
    '2faEnable':    `${V1}/auth/2fa/enable`,
    '2faConfirm':   `${V1}/auth/2fa/confirm`,
    '2faDisable':   `${V1}/auth/2fa/disable`,
    switchBranch:   `${V1}/auth/switch-branch`,
    updateProfile:  `${V1}/auth/me`,
    changePassword: `${V1}/auth/change-password`,
  },

  // Branches & users
  branches:           `${V1}/branches`,
  branchesActiveList: `${V1}/branches/active-list`,
  branch:            (id: number) => `${V1}/branches/${id}`,
  branchActivate:    (id: number) => `${V1}/branches/${id}/activate`,
  branchDeactivate:  (id: number) => `${V1}/branches/${id}/deactivate`,
  users:            `${V1}/users`,
  user:            (id: number) => `${V1}/users/${id}`,
  userAssignRole:  (id: number) => `${V1}/users/${id}/assign-role`,
  userActivate:    (id: number) => `${V1}/users/${id}/activate`,
  userDeactivate:  (id: number) => `${V1}/users/${id}/deactivate`,

  // Roles & permissions (RBAC management)
  roles:            `${V1}/roles`,
  role:            (id: number) => `${V1}/roles/${id}`,
  permissions:      `${V1}/permissions`,
  permission:      (id: number) => `${V1}/permissions/${id}`,

  // Customers
  customers:        `${V1}/customers`,
  customer:        (id: number) => `${V1}/customers/${id}`,
  customerOrders:  (id: number) => `${V1}/customers/${id}/orders`,
  customerDocuments: (id: number) => `${V1}/customers/${id}/documents`,
  customerBalance: (id: number) => `${V1}/customers/${id}/balance`,
  customerTimeline:(id: number) => `${V1}/customers/${id}/timeline`,
  familyMembers:   (cid: number) => `${V1}/customers/${cid}/family-members`,
  familyMember:    (cid: number, fid: number) => `${V1}/customers/${cid}/family-members/${fid}`,

  // Measurements
  measurements:    (cid: number) => `${V1}/customers/${cid}/measurements`,
  measurementProfile:   (profileId: number) => `${V1}/measurements/profiles/${profileId}`,
  measurementVersions:  (profileId: number) => `${V1}/measurements/profiles/${profileId}/versions`,
  measurementVersion:   (versionId: number) => `${V1}/measurements/versions/${versionId}`,
  approveMeasurement:   (versionId: number) => `${V1}/measurements/versions/${versionId}/approve`,
  rejectMeasurement:    (versionId: number) => `${V1}/measurements/versions/${versionId}/reject`,
  pendingApprovals:     `${V1}/measurements/pending-approval`,

  // Orders
  orders:          `${V1}/orders`,
  order:          (id: number) => `${V1}/orders/${id}`,
  orderDocuments: (id: number) => `${V1}/orders/${id}/documents`,
  orderTimeline:  (id: number) => `${V1}/orders/${id}/timeline`,
  orderItems:     (id: number) => `${V1}/orders/${id}/items`,
  orderItem:      (id: number, itemId: number) => `${V1}/orders/${id}/items/${itemId}`,
  cancelOrder:    (id: number) => `${V1}/orders/${id}/cancel`,
  confirmOrder:   (id: number) => `${V1}/orders/${id}/confirm`,
  // Phase 3B-1 — order-scoped balance collection (GET history + POST collect)
  orderPayments:  (id: number) => `${V1}/orders/${id}/payments`,
  // Phase 1 — per-item payment summary (item balance + advance share)
  itemPaymentSummary: (orderId: number, itemId: number) => `${V1}/orders/${orderId}/items/${itemId}/payment-summary`,
  // Phase 2 — selected-item pickup batches (pay-now)
  pickupBatches:       (orderId: number) => `${V1}/orders/${orderId}/pickup-batches`,
  pickupBatch:         (orderId: number, batchId: number) => `${V1}/orders/${orderId}/pickup-batches/${batchId}`,
  pickupBatchPayment:  (orderId: number, batchId: number) => `${V1}/orders/${orderId}/pickup-batches/${batchId}/payments`,
  pickupBatchHandover: (orderId: number, batchId: number) => `${V1}/orders/${orderId}/pickup-batches/${batchId}/handover`,
  pickupBatchReceipt:  (orderId: number, batchId: number) => `${V1}/orders/${orderId}/pickup-batches/${batchId}/receipt`,
  // Phase 3B-2 — Front Desk read-only lookup + ready-rack search
  orderLookup:    `${V1}/orders/lookup`,
  rackSearch:     `${V1}/rack/search`,
  // Phase 6A — Front Desk dashboard aggregation
  frontDeskDashboard: `${V1}/front-desk/dashboard`,
  // Phase 6B — server-side Front Desk order drafts
  frontDeskDrafts: `${V1}/front-desk/drafts`,
  frontDeskDraft: (id: number) => `${V1}/front-desk/drafts/${id}`,
  frontDeskDraftConvert: (id: number) => `${V1}/front-desk/drafts/${id}/convert`,
  // Phase 3B-3 — Front Desk pickup handover
  handoverEligibility: (id: number) => `${V1}/orders/${id}/handover-eligibility`,
  handover:            (id: number) => `${V1}/orders/${id}/handover`,
  // Phase 4 — Front Desk WhatsApp notifications
  orderNotifications:    (id: number) => `${V1}/orders/${id}/notifications`,
  notificationPreview:   (id: number) => `${V1}/orders/${id}/notifications/preview`,
  sendWhatsapp:          (id: number) => `${V1}/orders/${id}/notifications/whatsapp`,
  // Phase 5 — Customer alteration after delivery (list + create share one URL).
  // Distinct from internal QC rework: a Front-Desk intake against a delivered item.
  alterations:     `${V1}/alterations`,
  alteration:     (id: number) => `${V1}/alterations/${id}`,
  alterationStatus:(id: number) => `${V1}/alterations/${id}/status`,
  jobCardPdf:     (id: number) => `${V1}/orders/${id}/job-card`,
  // Phase 2 — Front Desk production box + per-sub-order PDF/print
  itemJobCard:    (id: number, itemId: number) => `${V1}/orders/${id}/items/${itemId}/job-card`,
  itemPrintLog:   (id: number, itemId: number) => `${V1}/orders/${id}/items/${itemId}/print-log`,

  // Production
  productionBoard:     `${V1}/production/board`,
  productionOrderSummary: (orderId: number) => `${V1}/production/orders/${orderId}/summary`,
  productionDashboard: `${V1}/production/dashboard`,
  productionItems:     `${V1}/production/items`,
  productionCodeSearch: `${V1}/production/search-code`,
  productionItem:     (id: number) => `${V1}/production/items/${id}`,
  transitionItem:     (id: number) => `${V1}/production/items/${id}/transition`,
  productionHistory:  (id: number) => `${V1}/production/items/${id}/history`,
  // Kanban Phase B — issues (text-only) + on-hold overlay.
  productionIssues:   (id: number) => `${V1}/production/items/${id}/issues`,
  resolveIssue:       (issueId: number) => `${V1}/production/issues/${issueId}/resolve`,
  holdItem:           (id: number) => `${V1}/production/items/${id}/hold`,
  resumeItem:         (id: number) => `${V1}/production/items/${id}/resume`,
  // Kanban Phase C — section-supervisor assignment + "my section" lookup.
  mySections:          `${V1}/production/my-sections`,
  stageSupervisors:    `${V1}/production/stage-supervisors`,
  stageSupervisor:    (id: number) => `${V1}/production/stage-supervisors/${id}`,
  // Kanban Phase F — in-app production notification feed.
  productionNotifications: `${V1}/production/notifications`,
  readNotification:    (id: number) => `${V1}/production/notifications/${id}/read`,
  readAllNotifications: `${V1}/production/notifications/read-all`,
  // Phase 7B — fabric allocation + cloth damage on the production workbench.
  productionFabric:        (id: number) => `${V1}/production/items/${id}/fabric-allocation`,
  productionConsumeFabric: (id: number) => `${V1}/production/items/${id}/fabric-allocation/consume`,
  productionClothDamage:   (id: number) => `${V1}/production/items/${id}/cloth-damage`,
  clothDamage:         `${V1}/cloth-damage`,
  // Phase 7C — QC pass/fail + rework closure on the production workbench.
  productionQc:        (id: number) => `${V1}/production/items/${id}/qc`,
  qcPass:              (id: number) => `${V1}/production/items/${id}/qc/pass`,
  qcFail:              (id: number) => `${V1}/production/items/${id}/qc/fail`,
  // Phase 7D — final packing + ready rack.
  productionPacking:   (id: number) => `${V1}/production/items/${id}/packing`,
  packingChecklist:    (id: number) => `${V1}/production/items/${id}/packing-checklist`,
  markPacked:          (id: number) => `${V1}/production/items/${id}/mark-packed`,
  packingSlip:         (id: number) => `${V1}/production/items/${id}/packing-slip`,

  // Cutting
  cuttingQueue:        `${V1}/cutting/queue`,
  allocateFabric:     (id: number) => `${V1}/cutting/items/${id}/allocate-fabric`,
  releaseFabric:      (id: number) => `${V1}/cutting/items/${id}/release-fabric`,
  startCutting:       (id: number) => `${V1}/cutting/items/${id}/start-cutting`,
  completeCutting:    (id: number) => `${V1}/cutting/items/${id}/complete-cutting`,
  cuttingBundle:      (id: number) => `${V1}/cutting/bundles/${id}`,

  // Tailoring
  tailoringAssignments:  `${V1}/tailoring/assignments`,
  tailoringAssignment:   (id: number) => `${V1}/tailoring/assignments/${id}`,
  startTailoring:        (id: number) => `${V1}/tailoring/assignments/${id}/start`,
  completeTailoring:     (id: number) => `${V1}/tailoring/assignments/${id}/complete`,
  reassignTailoring:     (id: number) => `${V1}/tailoring/assignments/${id}/reassign`,
  tailorPerformance:     (tailorId: number) => `${V1}/tailoring/performance/${tailorId}`,

  // QC
  inspectItem:     (id: number) => `${V1}/qc/items/${id}/inspect`,
  qcPhotos:        `${V1}/qc/photos`,
  qcHistory:      (id: number) => `${V1}/qc/items/${id}/history`,
  reworkOverride: (id: number) => `${V1}/qc/items/${id}/rework-override`,
  defectCategories:`${V1}/qc/defects/categories`,

  // Inventory
  fabricRolls:     `${V1}/inventory/fabric-rolls`,
  fabricRoll:     (id: number) => `${V1}/inventory/fabric-rolls/${id}`,
  fabricRollLedger: (id: number) => `${V1}/inventory/fabric-rolls/${id}/ledger`,
  fabricRollThreshold: (id: number) => `${V1}/inventory/fabric-rolls/${id}/threshold`,
  adjustFabric:   (id: number) => `${V1}/inventory/fabric-rolls/${id}/adjust`,
  inventoryMovements: `${V1}/inventory/movements`,
  lowStock:        `${V1}/inventory/low-stock`,
  fabricTypes:     `${V1}/inventory/fabric-types`,
  fabricType:     (id: number) => `${V1}/inventory/fabric-types/${id}`,
  suppliers:       `${V1}/inventory/suppliers`,
  supplier:       (id: number) => `${V1}/inventory/suppliers/${id}`,
  purchaseOrders:  `${V1}/inventory/purchase-orders`,
  purchaseOrder:  (id: number) => `${V1}/inventory/purchase-orders/${id}`,
  placePO:        (id: number) => `${V1}/inventory/purchase-orders/${id}/place`,
  cancelPO:       (id: number) => `${V1}/inventory/purchase-orders/${id}/cancel`,
  receivePO:      (id: number) => `${V1}/inventory/purchase-orders/${id}/receive`,

  // Damage reports
  damageReports:   `${V1}/damage-reports`,
  damageReport:   (id: number) => `${V1}/damage-reports/${id}`,
  damagePhotos:    `${V1}/damage-reports/photos`,
  approveDamage:  (id: number) => `${V1}/damage-reports/${id}/approve`,
  rejectDamage:   (id: number) => `${V1}/damage-reports/${id}/reject`,

  // Rack
  rackSlots:       `${V1}/rack/slots`,
  rackSlot:       (id: number) => `${V1}/rack/slots/${id}`,
  assignRack:     (itemId: number) => `${V1}/rack/items/${itemId}/assign`,
  releaseRack:    (itemId: number) => `${V1}/rack/items/${itemId}/release`,
  currentSlot:    (itemId: number) => `${V1}/rack/items/${itemId}/current-slot`,

  // Deliveries
  deliveries:      `${V1}/deliveries`,
  delivery:       (id: number) => `${V1}/deliveries/${id}`,
  dispatchDelivery:(id: number) => `${V1}/deliveries/${id}/dispatch`,
  confirmDelivery: (id: number) => `${V1}/deliveries/${id}/confirm`,
  attemptDelivery: (id: number) => `${V1}/deliveries/${id}/attempt`,
  cancelDelivery:  (id: number) => `${V1}/deliveries/${id}/cancel`,

  // Finance
  invoices:        `${V1}/finance/invoices`,
  invoice:        (id: number) => `${V1}/finance/invoices/${id}`,
  invoicePdf:     (id: number) => `${V1}/finance/invoices/${id}/pdf`,
  creditNote:     (id: number) => `${V1}/finance/invoices/${id}/credit-note`,
  payments:        `${V1}/finance/payments`,
  outstandingBalance: (orderId: number) => `${V1}/finance/orders/${orderId}/outstanding-balance`,
  financeOutstanding: `${V1}/finance/outstanding`,
  financeDashboard:`${V1}/finance/dashboard/summary`,

  search:          `${V1}/search`,

  // Documents
  documents:        `${V1}/documents`,
  documentDownload:(id: number) => `${V1}/documents/${id}/download`,
  regenerateDoc:   `${V1}/documents/regenerate`,

  // Reports
  reports:         `${V1}/reports`,
  runReport:       `${V1}/reports/run`,
  reportJob:      (id: number | string) => `${V1}/reports/jobs/${id}`,
  reportDownload: (id: number | string) => `${V1}/reports/jobs/${id}/download`,
  // Phase 9 — management reports (read-only aggregates)
  reportDashboard:        `${V1}/reports/dashboard`,
  reportOrdersDaily:      `${V1}/reports/orders/daily`,
  reportPaymentsPending:  `${V1}/reports/payments/pending`,
  reportProductionStages: `${V1}/reports/production/stages`,
  reportDamage:           `${V1}/reports/damage`,
  reportSalesGst:         `${V1}/reports/sales-gst`,
  reportInventoryStock:   `${V1}/reports/inventory/stock`,
  reportPurchases:        `${V1}/reports/purchases`,

  // Notifications
  notifications:   `${V1}/notifications`,

  // Audit
  auditActivities: `${V1}/audit/activities`,
  auditTransitions:(orderItemId: number) => `${V1}/audit/transitions/${orderItemId}`,
} as const
