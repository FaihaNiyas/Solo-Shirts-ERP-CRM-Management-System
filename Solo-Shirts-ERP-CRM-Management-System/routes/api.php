<?php

declare(strict_types=1);

use App\Modules\Customer\Http\Controllers\Api\V1\CustomerController;
use App\Modules\Customer\Http\Controllers\Api\V1\FamilyMemberController;
use App\Modules\Delivery\Http\Controllers\Api\V1\DeliveryAttemptController;
use App\Modules\Delivery\Http\Controllers\Api\V1\DeliveryConfirmationController;
use App\Modules\Delivery\Http\Controllers\Api\V1\DeliveryController;
use App\Modules\Delivery\Http\Controllers\Api\V1\RackAssignmentController;
use App\Modules\Delivery\Http\Controllers\Api\V1\RackSlotController;
use App\Modules\Finance\Http\Controllers\Api\V1\CreditNoteController;
use App\Modules\Finance\Http\Controllers\Api\V1\FinanceDashboardController;
use App\Modules\Finance\Http\Controllers\Api\V1\InvoiceController;
use App\Modules\Finance\Http\Controllers\Api\V1\PaymentController;
use App\Modules\Identity\Http\Controllers\Api\V1\AuthController;
use App\Modules\Identity\Http\Controllers\Api\V1\BranchController;
use App\Modules\Identity\Http\Controllers\Api\V1\PermissionController;
use App\Modules\Identity\Http\Controllers\Api\V1\RoleController;
use App\Modules\Identity\Http\Controllers\Api\V1\TwoFactorController;
use App\Modules\Identity\Http\Controllers\Api\V1\UserController;
use App\Modules\Identity\Http\Middleware\ResolveBranchContext;
use App\Modules\Inventory\Http\Controllers\Api\V1\DamageReportApprovalController;
use App\Modules\Inventory\Http\Controllers\Api\V1\DamageReportController;
use App\Modules\Inventory\Http\Controllers\Api\V1\DamageReportPhotoController;
use App\Modules\Inventory\Http\Controllers\Api\V1\FabricRollController;
use App\Modules\Inventory\Http\Controllers\Api\V1\FabricTypeController;
use App\Modules\Inventory\Http\Controllers\Api\V1\LowStockController;
use App\Modules\Inventory\Http\Controllers\Api\V1\MovementController;
use App\Modules\Inventory\Http\Controllers\Api\V1\PurchaseOrderController;
use App\Modules\Inventory\Http\Controllers\Api\V1\SupplierController;
use App\Modules\Measurement\Http\Controllers\Api\V1\MeasurementApprovalController;
use App\Modules\Measurement\Http\Controllers\Api\V1\MeasurementProfileController;
use App\Modules\Measurement\Http\Controllers\Api\V1\MeasurementVersionController;
use App\Modules\Order\Http\Controllers\Api\V1\AlterationController;
use App\Modules\Order\Http\Controllers\Api\V1\FrontDeskDashboardController;
use App\Modules\Order\Http\Controllers\Api\V1\FrontDeskDraftController;
use App\Modules\Order\Http\Controllers\Api\V1\FrontDeskLookupController;
use App\Modules\Order\Http\Controllers\Api\V1\HandoverController;
use App\Modules\Order\Http\Controllers\Api\V1\ItemJobCardController;
use App\Modules\Order\Http\Controllers\Api\V1\ItemPaymentSummaryController;
use App\Modules\Order\Http\Controllers\Api\V1\JobCardController;
use App\Modules\Order\Http\Controllers\Api\V1\OrderController;
use App\Modules\Order\Http\Controllers\Api\V1\OrderItemBoxController;
use App\Modules\Order\Http\Controllers\Api\V1\OrderItemController;
use App\Modules\Order\Http\Controllers\Api\V1\OrderNotificationController;
use App\Modules\Order\Http\Controllers\Api\V1\OrderPaymentController;
use App\Modules\Order\Http\Controllers\Api\V1\PickupBatchController;
use App\Modules\Printing\Http\Controllers\Api\V1\DocumentController;
use App\Modules\Production\Http\Controllers\Api\V1\BundleController;
use App\Modules\Production\Http\Controllers\Api\V1\CuttingActionController;
use App\Modules\Production\Http\Controllers\Api\V1\CuttingQueueController;
use App\Modules\Production\Http\Controllers\Api\V1\DefectCategoryController;
use App\Modules\Production\Http\Controllers\Api\V1\FabricAllocationController;
use App\Modules\Production\Http\Controllers\Api\V1\KanbanBoardController;
use App\Modules\Production\Http\Controllers\Api\V1\ProductionClothDamageController;
use App\Modules\Production\Http\Controllers\Api\V1\ProductionDashboardController;
use App\Modules\Production\Http\Controllers\Api\V1\ProductionFabricController;
use App\Modules\Production\Http\Controllers\Api\V1\ProductionHoldController;
use App\Modules\Production\Http\Controllers\Api\V1\ProductionIssueController;
use App\Modules\Production\Http\Controllers\Api\V1\ProductionItemController;
use App\Modules\Production\Http\Controllers\Api\V1\ProductionOrderSummaryController;
use App\Modules\Production\Http\Controllers\Api\V1\ProductionNotificationController;
use App\Modules\Production\Http\Controllers\Api\V1\ProductionPackingController;
use App\Modules\Production\Http\Controllers\Api\V1\ProductionQcController;
use App\Modules\Production\Http\Controllers\Api\V1\ProductionTransitionController;
use App\Modules\Production\Http\Controllers\Api\V1\QcInspectionController;
use App\Modules\Production\Http\Controllers\Api\V1\QcPhotoController;
use App\Modules\Production\Http\Controllers\Api\V1\StageSupervisorController;
use App\Modules\Production\Http\Controllers\Api\V1\ReworkOverrideController;
use App\Modules\Production\Http\Controllers\Api\V1\TailorAssignmentController;
use App\Modules\Production\Http\Controllers\Api\V1\TailorPerformanceController;
use App\Modules\Reporting\Http\Controllers\Api\V1\DashboardController;
use App\Modules\Reporting\Http\Controllers\Api\V1\ManagementReportController;
use App\Modules\Reporting\Http\Controllers\Api\V1\NotificationController;
use App\Modules\Reporting\Http\Controllers\Api\V1\ReportController;
use App\Modules\Reporting\Http\Controllers\Api\V1\ReportJobController;
use App\Modules\Shared\Http\Controllers\Api\V1\AuditController;
use App\Modules\Shared\Http\Controllers\Api\V1\HealthController;
use App\Modules\Shared\Http\Controllers\Api\V1\SearchController;
use App\Modules\Shared\Http\Controllers\Api\V1\SmokeController;
use Illuminate\Support\Facades\Route;

// AssignRequestId + ForceJsonResponse are applied globally to the 'api' group
// (see bootstrap/app.php), so every route below already carries a request id.

Route::prefix('v1')
    ->middleware(['throttle:60,1'])
    ->group(function () {
        Route::get('health', HealthController::class)->name('health');
    });

Route::prefix('v1')->group(function () {
    // Public auth entry point.
    Route::post('auth/login', [AuthController::class, 'login']);

    Route::middleware(['auth:sanctum', ResolveBranchContext::class])->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::post('auth/refresh', [AuthController::class, 'refresh']);
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/switch-branch', [AuthController::class, 'switchBranch']);

        Route::post('auth/2fa/enable', [TwoFactorController::class, 'enable']);
        Route::post('auth/2fa/confirm', [TwoFactorController::class, 'confirm']);
        Route::post('auth/2fa/disable', [TwoFactorController::class, 'disable']);

        Route::get('branches', [BranchController::class, 'index']);
        Route::get('branches/active-list', [BranchController::class, 'activeList']);
        Route::post('branches', [BranchController::class, 'store']);
        Route::put('branches/{branch}', [BranchController::class, 'update']);
        Route::post('branches/{branch}/activate', [BranchController::class, 'activate']);
        Route::post('branches/{branch}/deactivate', [BranchController::class, 'deactivate']);

        Route::get('users', [UserController::class, 'index']);
        Route::get('users/{user}', [UserController::class, 'show']);
        Route::post('users', [UserController::class, 'store']);
        Route::put('users/{user}', [UserController::class, 'update']);
        Route::delete('users/{user}', [UserController::class, 'destroy']);
        Route::post('users/{user}/assign-role', [UserController::class, 'assignRole']);
        Route::post('users/{user}/deactivate', [UserController::class, 'deactivate']);
        Route::post('users/{user}/activate', [UserController::class, 'activate']);

        // Roles & permissions (RBAC management — Owner/Admin)
        Route::get('roles', [RoleController::class, 'index']);
        Route::post('roles', [RoleController::class, 'store']);
        Route::get('roles/{role}', [RoleController::class, 'show']);
        Route::put('roles/{role}', [RoleController::class, 'update']);
        Route::delete('roles/{role}', [RoleController::class, 'destroy']);
        Route::get('permissions', [PermissionController::class, 'index']);
        Route::post('permissions', [PermissionController::class, 'store']);
        Route::put('permissions/{permission}', [PermissionController::class, 'update']);
        Route::delete('permissions/{permission}', [PermissionController::class, 'destroy']);

        // Customers
        Route::get('customers', [CustomerController::class, 'index']);
        Route::post('customers', [CustomerController::class, 'store']);
        Route::get('customers/{customer}', [CustomerController::class, 'show']);
        Route::put('customers/{customer}', [CustomerController::class, 'update']);
        Route::delete('customers/{customer}', [CustomerController::class, 'destroy']);

        // Customer 360 sub-resources (read-only feeds for the detail view).
        Route::get('customers/{customer}/orders', [CustomerController::class, 'orders']);
        Route::get('customers/{customer}/documents', [CustomerController::class, 'documents']);
        Route::get('customers/{customer}/balance', [CustomerController::class, 'balance']);
        Route::get('customers/{customer}/timeline', [CustomerController::class, 'timeline']);

        // Family members (scoped to their customer)
        Route::scopeBindings()->group(function () {
            Route::get('customers/{customer}/family-members', [FamilyMemberController::class, 'index']);
            Route::post('customers/{customer}/family-members', [FamilyMemberController::class, 'store']);
            Route::put('customers/{customer}/family-members/{familyMember}', [FamilyMemberController::class, 'update']);
            Route::delete('customers/{customer}/family-members/{familyMember}', [FamilyMemberController::class, 'destroy']);
        });

        // Measurements
        Route::get('customers/{customer}/measurements', [MeasurementProfileController::class, 'index']);
        Route::post('customers/{customer}/measurements', [MeasurementProfileController::class, 'store']);
        Route::patch('measurements/profiles/{profile}', [MeasurementProfileController::class, 'update']);
        Route::delete('measurements/profiles/{profile}', [MeasurementProfileController::class, 'destroy']);

        Route::get('measurements/pending-approval', [MeasurementVersionController::class, 'pendingApproval']);
        Route::get('measurements/profiles/{profile}/versions', [MeasurementVersionController::class, 'index']);
        Route::post('measurements/profiles/{profile}/versions', [MeasurementVersionController::class, 'store']);
        Route::get('measurements/versions/{version}', [MeasurementVersionController::class, 'show']);
        Route::post('measurements/versions/{version}/approve', [MeasurementApprovalController::class, 'approve']);
        Route::post('measurements/versions/{version}/reject', [MeasurementApprovalController::class, 'reject']);

        // Orders
        Route::get('orders', [OrderController::class, 'index']);
        // Phase 3B-2 — Front Desk read-only lookup. MUST precede orders/{order}
        // so "lookup" isn't bound as an {order}.
        Route::get('orders/lookup', [FrontDeskLookupController::class, 'orders']);
        Route::get('rack/search', [FrontDeskLookupController::class, 'rack']);
        // Phase 6A — Front Desk dashboard aggregation (branch-scoped operational counts).
        Route::get('front-desk/dashboard', [FrontDeskDashboardController::class, 'show']);
        // Phase 6B — server-side Front Desk order drafts (multi-draft, resumable).
        Route::get('front-desk/drafts', [FrontDeskDraftController::class, 'index']);
        Route::post('front-desk/drafts', [FrontDeskDraftController::class, 'store']);
        Route::get('front-desk/drafts/{draft}', [FrontDeskDraftController::class, 'show']);
        Route::patch('front-desk/drafts/{draft}', [FrontDeskDraftController::class, 'update']);
        Route::delete('front-desk/drafts/{draft}', [FrontDeskDraftController::class, 'destroy']);
        Route::post('front-desk/drafts/{draft}/convert', [FrontDeskDraftController::class, 'convert']);
        Route::post('orders', [OrderController::class, 'store'])->middleware('idempotent');
        Route::get('orders/{order}', [OrderController::class, 'show']);
        Route::get('orders/{order}/documents', [OrderController::class, 'documents']);
        Route::get('orders/{order}/timeline', [OrderController::class, 'timeline']);
        Route::put('orders/{order}', [OrderController::class, 'update']);
        Route::post('orders/{order}/cancel', [OrderController::class, 'cancel']);
        Route::post('orders/{order}/confirm', [OrderController::class, 'confirm']);
        // Phase 3B-1 — Front Desk order balance collection (narrow, order-scoped).
        Route::get('orders/{order}/payments', [OrderPaymentController::class, 'index']);
        Route::post('orders/{order}/payments', [OrderPaymentController::class, 'store']);
        // Phase 3B-3 — Front Desk pickup handover with balance gate.
        Route::get('orders/{order}/handover-eligibility', [HandoverController::class, 'eligibility']);
        Route::post('orders/{order}/handover', [HandoverController::class, 'store']);
        // Phase 2 — selected-item pickup batches (pay-now). Batch is scoped to its
        // order; create is idempotent so a double-submit can't mint two batches.
        Route::scopeBindings()->group(function () {
            Route::get('orders/{order}/pickup-batches', [PickupBatchController::class, 'index']);
            Route::post('orders/{order}/pickup-batches', [PickupBatchController::class, 'store'])->middleware('idempotent');
            Route::get('orders/{order}/pickup-batches/{pickupBatch}', [PickupBatchController::class, 'show']);
            Route::post('orders/{order}/pickup-batches/{pickupBatch}/payments', [PickupBatchController::class, 'payment']);
            Route::post('orders/{order}/pickup-batches/{pickupBatch}/handover', [PickupBatchController::class, 'handover']);
            Route::get('orders/{order}/pickup-batches/{pickupBatch}/receipt', [PickupBatchController::class, 'receipt']);
        });
        // Phase 4 — Front Desk WhatsApp notifications (manual trigger + log).
        Route::get('orders/{order}/notifications', [OrderNotificationController::class, 'index']);
        Route::get('orders/{order}/notifications/preview', [OrderNotificationController::class, 'preview']);
        Route::post('orders/{order}/notifications/whatsapp', [OrderNotificationController::class, 'store']);
        Route::get('orders/{order}/job-card', [JobCardController::class, 'show']);

        // Phase 5 — Customer post-delivery alteration intake (separate from QC rework).
        // No 'idempotent' middleware: like qc/photos this route accepts a file upload,
        // which the idempotency hasher cannot serialise.
        Route::get('alterations', [AlterationController::class, 'index']);
        Route::post('alterations', [AlterationController::class, 'store']);
        Route::get('alterations/{alteration}', [AlterationController::class, 'show']);
        // Phase 5B — alteration status workflow (approve/start/ready/deliver/cancel).
        Route::patch('alterations/{alteration}/status', [AlterationController::class, 'updateStatus']);

        Route::scopeBindings()->group(function () {
            // Adding an item mints a new order_item with no natural dedup key, so a
            // double-submit would create a duplicate line — it is made idempotent
            // (QA-002). Update/delete target an existing item by id and are safe.
            Route::post('orders/{order}/items', [OrderItemController::class, 'store'])->middleware('idempotent');
            Route::put('orders/{order}/items/{item}', [OrderItemController::class, 'update']);
            Route::delete('orders/{order}/items/{item}', [OrderItemController::class, 'destroy']);

            // Phase 1 — per-item payment summary (item balance + advance share).
            Route::get('orders/{order}/items/{item}/payment-summary', [ItemPaymentSummaryController::class, 'show']);

            // Phase 2 — Front Desk per-sub-order PDF/print + print logging.
            Route::get('orders/{order}/items/{item}/job-card', [ItemJobCardController::class, 'show']);
            Route::post('orders/{order}/items/{item}/print-log', [OrderItemBoxController::class, 'printLog']);
        });

        // Production workflow (Phase 7). Transitions are idempotent and audited.
        Route::get('production/board', [KanbanBoardController::class, 'index']);
        // Kanban Phase D — live production dashboard (manager-facing). Literal path
        // before the production/items/{item} catch-alls.
        Route::get('production/dashboard', [ProductionDashboardController::class, 'summary']);
        // Read-only "order thread": all sub-orders of a Main Order with their stages.
        Route::get('production/orders/{order}/summary', [ProductionOrderSummaryController::class, 'show']);
        // Phase 7A — flat shop-floor queue (literal paths before {item}).
        Route::get('production/items', [ProductionItemController::class, 'index']);
        Route::get('production/search-code', [ProductionItemController::class, 'searchByCode']);
        Route::get('production/items/{item}', [ProductionItemController::class, 'show']);
        Route::get('production/items/{item}/history', [ProductionItemController::class, 'history']);
        Route::post('production/items/{item}/transition', [ProductionTransitionController::class, 'store'])
            ->middleware('idempotent');

        // Phase 7B — fabric allocation + cloth damage on the production workbench
        // (per sub-order). Reserve is idempotent. Front Desk has no fabric/damage
        // permissions, so these never render for them.
        Route::get('production/items/{item}/fabric-allocation', [ProductionFabricController::class, 'show']);
        Route::post('production/items/{item}/fabric-allocation', [ProductionFabricController::class, 'store'])
            ->middleware('idempotent');
        Route::patch('production/items/{item}/fabric-allocation/consume', [ProductionFabricController::class, 'consume']);
        Route::get('production/items/{item}/cloth-damage', [ProductionClothDamageController::class, 'index']);
        Route::post('production/items/{item}/cloth-damage', [ProductionClothDamageController::class, 'store']);

        // Phase 7C — QC pass/fail + rework closure on the production workbench.
        // Internal production rework only — never a customer alteration. Front Desk
        // lacks qc.inspect, so pass/fail 403 for them.
        Route::get('production/items/{item}/qc', [ProductionQcController::class, 'show']);
        Route::post('production/items/{item}/qc/pass', [ProductionQcController::class, 'pass']);
        Route::post('production/items/{item}/qc/fail', [ProductionQcController::class, 'fail']);

        // Phase 7D — final packing checklist + mark-packed + per-item packing slip.
        // Mark-packed promotes to ready-for-delivery (auto-assigns a rack slot); it
        // never marks delivered and never touches the balance. Front Desk cannot pack.
        Route::get('production/items/{item}/packing', [ProductionPackingController::class, 'show']);
        Route::match(['post', 'patch'], 'production/items/{item}/packing-checklist', [ProductionPackingController::class, 'saveChecklist']);
        Route::post('production/items/{item}/mark-packed', [ProductionPackingController::class, 'markPacked']);
        Route::get('production/items/{item}/packing-slip', [ProductionPackingController::class, 'slip']);

        // Kanban Phase B — production issues (text-only) + on-hold overlay. None of
        // these move the item's production state; that stays owned by the machine.
        Route::get('production/items/{item}/issues', [ProductionIssueController::class, 'index']);
        Route::post('production/items/{item}/issues', [ProductionIssueController::class, 'store']);
        Route::post('production/issues/{issue}/resolve', [ProductionIssueController::class, 'resolve']);
        Route::post('production/items/{item}/hold', [ProductionHoldController::class, 'hold']);
        Route::post('production/items/{item}/resume', [ProductionHoldController::class, 'resume']);

        // Kanban Phase F — the caller's in-app production notification feed.
        Route::get('production/notifications', [ProductionNotificationController::class, 'index']);
        Route::post('production/notifications/read-all', [ProductionNotificationController::class, 'readAll']);
        Route::post('production/notifications/{notification}/read', [ProductionNotificationController::class, 'read']);

        // Kanban Phase C — section-supervisor assignment + "my section" lookup.
        // Literal paths, declared before the {item} catch-alls above are not needed
        // (distinct prefixes), but kept grouped for clarity.
        Route::get('production/my-sections', [StageSupervisorController::class, 'mySections']);
        Route::get('production/stage-supervisors', [StageSupervisorController::class, 'index']);
        Route::post('production/stage-supervisors', [StageSupervisorController::class, 'store']);
        Route::delete('production/stage-supervisors/{supervisor}', [StageSupervisorController::class, 'destroy']);

        // Cutting & fabric allocation (Phase 8). allocate-fabric is idempotent.
        Route::get('cutting/queue', [CuttingQueueController::class, 'index']);
        Route::post('cutting/items/{item}/allocate-fabric', [FabricAllocationController::class, 'store'])
            ->middleware('idempotent');
        Route::post('cutting/items/{item}/release-fabric', [FabricAllocationController::class, 'release']);
        Route::post('cutting/items/{item}/start-cutting', [CuttingActionController::class, 'start']);
        Route::post('cutting/items/{item}/complete-cutting', [CuttingActionController::class, 'complete']);
        Route::get('cutting/bundles/{bundle}', [BundleController::class, 'show']);

        // Tailoring assignment (Phase 9).
        Route::get('tailoring/assignments', [TailorAssignmentController::class, 'index']);
        Route::post('tailoring/assignments', [TailorAssignmentController::class, 'store']);
        Route::post('tailoring/assignments/{assignment}/start', [TailorAssignmentController::class, 'start']);
        Route::post('tailoring/assignments/{assignment}/complete', [TailorAssignmentController::class, 'complete']);
        Route::post('tailoring/assignments/{assignment}/reassign', [TailorAssignmentController::class, 'reassign']);
        Route::get('tailoring/performance/{tailor}', [TailorPerformanceController::class, 'show']);

        // Finishing / QC / rework (Phase 10).
        Route::post('qc/photos', [QcPhotoController::class, 'store']);
        Route::get('qc/defects/categories', [DefectCategoryController::class, 'index']);
        Route::post('qc/defects/categories', [DefectCategoryController::class, 'store']);
        Route::get('qc/defects/analytics', [DefectCategoryController::class, 'analytics']);
        Route::post('qc/items/{item}/inspect', [QcInspectionController::class, 'inspect']);
        Route::get('qc/items/{item}/history', [QcInspectionController::class, 'history']);
        Route::post('qc/items/{item}/rework-override', [ReworkOverrideController::class, 'store']);

        // Inventory (Phase 11): fabric rolls, types, suppliers, purchase orders.
        Route::get('inventory/fabric-rolls', [FabricRollController::class, 'index']);
        Route::post('inventory/fabric-rolls', [FabricRollController::class, 'store']);
        Route::get('inventory/fabric-rolls/{fabricRoll}', [FabricRollController::class, 'show']);
        Route::get('inventory/fabric-rolls/{fabricRoll}/ledger', [FabricRollController::class, 'ledger']);
        Route::patch('inventory/fabric-rolls/{fabricRoll}/threshold', [FabricRollController::class, 'threshold']);
        Route::post('inventory/fabric-rolls/{fabricRoll}/adjust', [FabricRollController::class, 'adjust']);
        Route::get('inventory/low-stock', [LowStockController::class, 'index']);
        Route::get('inventory/movements', [MovementController::class, 'index']);

        Route::get('inventory/fabric-types', [FabricTypeController::class, 'index']);
        Route::post('inventory/fabric-types', [FabricTypeController::class, 'store']);
        Route::put('inventory/fabric-types/{fabricType}', [FabricTypeController::class, 'update']);

        Route::get('inventory/suppliers', [SupplierController::class, 'index']);
        Route::post('inventory/suppliers', [SupplierController::class, 'store']);
        Route::match(['put', 'patch'], 'inventory/suppliers/{supplier}', [SupplierController::class, 'update']);

        Route::get('inventory/purchase-orders', [PurchaseOrderController::class, 'index']);
        Route::get('inventory/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show']);
        Route::post('inventory/purchase-orders', [PurchaseOrderController::class, 'store']);
        Route::post('inventory/purchase-orders/{purchaseOrder}/place', [PurchaseOrderController::class, 'place']);
        Route::post('inventory/purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel']);
        Route::post('inventory/purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive']);

        // Cloth damage & write-off (Phase 12). approve is idempotent + owner-grade.
        Route::post('damage-reports/photos', [DamageReportPhotoController::class, 'store']);
        Route::get('damage-reports', [DamageReportController::class, 'index']);
        // Phase 7B alias — production-facing cloth damage list (same branch-scoped feed).
        Route::get('cloth-damage', [DamageReportController::class, 'index']);
        Route::post('damage-reports', [DamageReportController::class, 'store']);
        Route::get('damage-reports/{damageReport}', [DamageReportController::class, 'show']);
        Route::post('damage-reports/{damageReport}/approve', [DamageReportApprovalController::class, 'approve'])
            ->middleware('idempotent');
        Route::post('damage-reports/{damageReport}/reject', [DamageReportApprovalController::class, 'reject']);

        // Ready-for-delivery rack slots (Phase 13).
        Route::get('rack/slots', [RackSlotController::class, 'index']);
        Route::post('rack/slots', [RackSlotController::class, 'store']);
        Route::put('rack/slots/{rackSlot}', [RackSlotController::class, 'update']);
        Route::post('rack/items/{item}/assign', [RackAssignmentController::class, 'assign']);
        Route::post('rack/items/{item}/release', [RackAssignmentController::class, 'release']);
        Route::get('rack/items/{item}/current-slot', [RackAssignmentController::class, 'currentSlot']);

        // Delivery management (Phase 14). Confirm is idempotent so a replayed
        // Idempotency-Key returns the prior result rather than re-confirming.
        Route::get('deliveries', [DeliveryController::class, 'index']);
        Route::post('deliveries', [DeliveryController::class, 'store']);
        Route::post('deliveries/{delivery}/dispatch', [DeliveryController::class, 'dispatchDelivery']);
        Route::post('deliveries/{delivery}/confirm', [DeliveryConfirmationController::class, 'confirm'])
            ->middleware('idempotent');
        Route::post('deliveries/{delivery}/attempt', [DeliveryAttemptController::class, 'store']);
        Route::post('deliveries/{delivery}/cancel', [DeliveryController::class, 'cancel']);

        // Finance: invoices, payments, credit notes (Phase 15). Owner/Admin/
        // Accountant only — gated by the FinancePolicy on Invoice. Payments are
        // idempotent via a required Idempotency-Key (payments.idempotency_key).
        Route::get('finance/invoices', [InvoiceController::class, 'index']);
        // Invoice + credit-note creation mint gap-free financial numbers, so a
        // retry/double-submit must replay rather than issue a second document
        // (QA-002). Idempotency-Key is required and enforced by the middleware.
        Route::post('finance/invoices', [InvoiceController::class, 'store'])->middleware('idempotent');
        Route::get('finance/invoices/{invoice}', [InvoiceController::class, 'show']);
        Route::get('finance/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf']);
        Route::post('finance/invoices/{invoice}/credit-note', [CreditNoteController::class, 'store'])->middleware('idempotent');
        Route::get('finance/payments', [PaymentController::class, 'index']);
        Route::post('finance/payments', [PaymentController::class, 'store']);
        Route::get('finance/orders/{order}/outstanding-balance', [InvoiceController::class, 'orderOutstanding']);
        Route::get('finance/outstanding', [InvoiceController::class, 'outstanding']);
        Route::get('finance/dashboard/summary', [FinanceDashboardController::class, 'summary']);

        // Printing / documents (Phase 16). Heavy PDFs queue; downloads are signed-URL
        // only (the download route itself is public + signature-protected below).
        Route::get('documents', [DocumentController::class, 'index']);
        Route::post('documents/regenerate', [DocumentController::class, 'regenerate']);

        // Reporting / dashboard / notifications (Phase 17). Dashboard reads
        // rollups (cached 60s); reports run on the queue and produce documents.
        Route::get('dashboard/summary', [DashboardController::class, 'summary']);
        Route::get('reports', [ReportController::class, 'index']);
        Route::post('reports/run', [ReportController::class, 'run']);
        Route::get('reports/jobs/{reportJob}', [ReportJobController::class, 'show']);
        Route::get('reports/jobs/{reportJob}/download', [ReportJobController::class, 'download']);
        Route::get('notifications', [NotificationController::class, 'index']);

        // Phase 9 — read-only management reports (gated reports.view; branch-scoped).
        Route::get('reports/dashboard', [ManagementReportController::class, 'dashboard']);
        Route::get('reports/orders/daily', [ManagementReportController::class, 'ordersDaily']);
        Route::get('reports/payments/pending', [ManagementReportController::class, 'paymentsPending']);
        Route::get('reports/production/stages', [ManagementReportController::class, 'productionStages']);
        Route::get('reports/damage', [ManagementReportController::class, 'damage']);
        Route::get('reports/sales-gst', [ManagementReportController::class, 'salesGst']);
        Route::get('reports/inventory/stock', [ManagementReportController::class, 'inventoryStock']);
        Route::get('reports/purchases', [ManagementReportController::class, 'purchases']);

        // Audit trail (Phase 18). Owner/Admin read the activity log; supervisors
        // may additionally read an item's production transition history.
        Route::get('audit/activities', [AuditController::class, 'activities']);
        Route::get('audit/transitions/{item}', [AuditController::class, 'transitions']);

        // Global (Ctrl+K) quick search across customers, orders and invoices.
        // Results are permission-filtered per entity and branch-scoped.
        Route::get('search', SearchController::class);
    });

    // Defect photos are retrieved via a temporary signed URL only — public but
    // signature-protected, so the raw storage path is never exposed.
    Route::get('qc/photos/{photo}/download', [QcPhotoController::class, 'download'])
        ->name('qc.photos.download')
        ->middleware('signed');

    Route::get('damage-reports/photos/{photo}/download', [DamageReportPhotoController::class, 'download'])
        ->name('damage-reports.photos.download')
        ->middleware('signed');

    // Rendered PDFs are fetched only via a temporary signed URL — public but
    // signature-protected, so the raw storage path is never exposed.
    Route::get('documents/{document}/download', [DocumentController::class, 'download'])
        ->name('documents.download')
        ->middleware('signed');

    // Phase 5 — Alteration intake photos via temporary signed URL only (public but
    // signature-protected, so the raw storage path is never exposed).
    Route::get('alterations/{alteration}/photo', [AlterationController::class, 'photo'])
        ->name('alterations.photo')
        ->middleware('signed');
});

// Smoke endpoints that exercise the Phase 2 base classes / idempotency. Only
// registered outside production so they never ship as live routes.
if (app()->environment(['local', 'testing'])) {
    Route::prefix('v1/_smoke')->group(function () {
        Route::post('idempotent', [SmokeController::class, 'store'])->middleware('idempotent');
        Route::post('validate', [SmokeController::class, 'validateInput']);
        Route::get('domain-exception', [SmokeController::class, 'domainError']);
    });
}
