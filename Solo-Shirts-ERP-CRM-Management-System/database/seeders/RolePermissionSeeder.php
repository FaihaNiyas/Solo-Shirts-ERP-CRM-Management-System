<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds the role set and the Identity-domain permission matrix. Later phases
 * extend $permissions / $matrix with their own module permissions.
 *
 * Note: the Master Prompt lists "Owner/Admin" as one bucket, but the system
 * requires them distinct — only Owner bypasses branch isolation. So 14 roles.
 * Owner is granted everything via a Gate::before in AuthServiceProvider.
 */
final class RolePermissionSeeder extends Seeder
{
    /**
     * @var list<string>
     */
    public const ROLES = [
        'Owner',
        'Admin',
        'Front Desk',
        'Measurement Staff',
        'Production Supervisor',
        'Cutting Master',
        'Tailor',
        'Kaja Button',
        'QC Supervisor',
        'Ironing Master',
        'Re-Worker',
        'Inventory Manager',
        'Accountant',
        'Delivery Staff',
    ];

    /**
     * Identity-domain permissions. Each later phase appends its own.
     *
     * @var list<string>
     */
    public const PERMISSIONS = [
        'users.view',
        'users.create',
        'users.update',
        'users.delete',
        'roles.assign',
        'branches.view',
        'branches.create',
        'branches.update',
        // Phase 4 — Customers
        'customers.view',
        'customers.create',
        'customers.update',
        'customers.delete',
        'family_members.manage',
        // Phase 5 — Measurements
        'measurements.view',
        'measurements.create',
        'measurements.approve',
        'measurements.reject',
        // Phase 6 — Orders
        'orders.view',
        'orders.create',
        'orders.update',
        'orders.cancel',
        'orders.print_job_card',
        // Phase 7 — Production workflow
        'production.view',
        'production.transition.fabric_allocated',
        'production.transition.cutting',
        'production.transition.tailoring',
        'production.transition.kaja',
        'production.transition.finishing',
        'production.transition.qc',
        'production.transition.rework',
        'production.transition.packing',
        'production.transition.ready_for_delivery',
        'production.transition.delivered',
        'production.transition.cancel',
        'production.rework.override',
        'production.packing.manage',
        // Kanban Phase B — production issues & on-hold
        'production.issue.report',
        'production.issue.resolve',
        'production.hold.manage',
        // Kanban Phase C — section-supervisor assignment
        'production.supervisor.assign',
        // Kanban Phase D — live production dashboard
        'production.dashboard.view',
        // Phase 8 — Cutting & fabric allocation
        'fabric.allocate',
        'fabric.release',
        'fabric.over_consume',
        'cutting.start',
        'cutting.complete',
        'bundles.view',
        // Phase 9 — Tailoring assignment
        'tailoring.assign',
        'tailoring.start',
        'tailoring.complete',
        'tailoring.reassign',
        'tailoring.performance.view',
        // Phase 10 — Finishing / QC / rework
        'qc.inspect',
        'qc.override',
        'qc.defect_categories.manage',
        // Phase 11 — Inventory
        'inventory.view',
        'inventory.fabric_rolls.create',
        'inventory.fabric_rolls.adjust',
        'inventory.fabric_rolls.adjust_out_approve',
        'inventory.suppliers.manage',
        'inventory.purchase_orders.create',
        'inventory.purchase_orders.place',
        'inventory.purchase_orders.receive',
        'inventory.low_stock.view',
        // Phase 12 — Cloth damage & write-off
        'damage_reports.create',
        'damage_reports.view',
        'damage_reports.approve',
        'damage_reports.reject',
        // Phase 13 — Ready-for-delivery rack
        'rack.view',
        'rack.slots.manage',
        'rack.assign',
        'rack.release',
        // Phase 14 — Delivery management
        'deliveries.view',
        'deliveries.create',
        'deliveries.dispatch',
        'deliveries.confirm',
        'deliveries.attempt',
        'deliveries.cancel',
        // Phase 15 — Finance (Owner/Admin/Accountant only)
        'finance.view',
        'finance.invoice.create',
        'finance.payment.record',
        'finance.credit_note.issue',
        'finance.dashboard.view',
        // Narrow invoice-PDF download — held by the payment-collecting desk
        // (Front Desk) in addition to the full-finance roles, so it can hand a
        // paying customer their invoice without exposing the finance module.
        'finance.invoice.download',
        // Phase 16 — Printing
        'documents.view',
        'documents.regenerate',
        // Phase 17 — Reporting / dashboard / notifications
        'dashboard.view',
        'reports.run',
        'reports.view',
        'notifications.view',
        // Phase 18 — Audit
        'audit.view',
        'audit.transitions.view',
        // Phase 19 — RBAC management (role & permission CRUD)
        'roles.view',
        'roles.manage',
        'permissions.view',
        'permissions.manage',
        // Phase 20 — Front Desk production box & placement
        'boxes.assign',
        'boxes.mark_placed',
        // Phase 3B-1 — Front Desk order balance collection (narrow; NOT broad finance)
        'orders.collect_payment',
        // Phase 3B-2 — Front Desk read-only order/ready-rack lookup
        'orders.lookup',
        // Phase 3B-3 — Front Desk pickup handover (+ manager balance override)
        'orders.handover',
        'orders.handover.override_balance',
        // Phase 4 — Front Desk WhatsApp notifications (narrow; NOT bulk/marketing)
        'orders.notifications.send',
        'orders.notifications.view',
        // Phase 5 — Customer post-delivery alteration intake (narrow; NOT QC rework)
        'alterations.view',
        'alterations.create',
        // Phase 5B — alteration status workflow. 'update' drives approve/start/
        // ready/cancel; 'deliver' is the narrow ready->delivered handover step.
        'alterations.update',
        'alterations.deliver',
    ];

    /**
     * Reporting permissions, granted to management roles (Admin, Accountant,
     * Production Supervisor).
     *
     * @var list<string>
     */
    private const ALL_REPORTING = [
        'dashboard.view',
        'reports.run',
        'reports.view',
        'notifications.view',
    ];

    /**
     * Document permissions, granted to the desk-facing roles that print
     * (Admin, Front Desk, Production Supervisor).
     *
     * @var list<string>
     */
    private const ALL_PRINTING = [
        'documents.view',
        'documents.regenerate',
    ];

    /**
     * The full finance permission set, granted only to Admin and Accountant
     * (Owner bypasses via Gate::before).
     *
     * @var list<string>
     */
    private const ALL_FINANCE = [
        'finance.view',
        'finance.invoice.create',
        'finance.payment.record',
        'finance.credit_note.issue',
        'finance.dashboard.view',
        'finance.invoice.download',
    ];

    /**
     * The full delivery-management permission set, granted wholesale to roles
     * that own the delivery desk (Admin, Production Supervisor, Delivery Staff).
     *
     * @var list<string>
     */
    private const ALL_DELIVERIES = [
        'deliveries.view',
        'deliveries.create',
        'deliveries.dispatch',
        'deliveries.confirm',
        'deliveries.attempt',
        'deliveries.cancel',
    ];

    /**
     * Inventory permissions an Inventory Manager holds (everything bar the
     * owner-only adjust-out approval).
     *
     * @var list<string>
     */
    private const INVENTORY_MANAGER = [
        'inventory.view',
        'inventory.fabric_rolls.create',
        'inventory.fabric_rolls.adjust',
        'inventory.suppliers.manage',
        'inventory.purchase_orders.create',
        'inventory.purchase_orders.place',
        'inventory.purchase_orders.receive',
        'inventory.low_stock.view',
    ];

    /**
     * Every production transition permission, granted wholesale to roles that
     * own the full workflow (Admin, Production Supervisor).
     *
     * @var list<string>
     */
    private const ALL_PRODUCTION = [
        'production.view',
        'production.transition.fabric_allocated',
        'production.transition.cutting',
        'production.transition.tailoring',
        'production.transition.kaja',
        'production.transition.finishing',
        'production.transition.qc',
        'production.transition.rework',
        'production.transition.packing',
        'production.transition.ready_for_delivery',
        'production.transition.delivered',
        'production.transition.cancel',
        'production.rework.override',
        'production.packing.manage',
        // Kanban Phase B — managers own issue resolution + holds; reporting is broad.
        'production.issue.report',
        'production.issue.resolve',
        'production.hold.manage',
        // Kanban Phase C — managers assign section supervisors.
        'production.supervisor.assign',
        // Kanban Phase D — managers view the live production dashboard.
        'production.dashboard.view',
    ];

    /**
     * Role => permissions granted (Owner omitted: granted everything via Gate).
     *
     * @var array<string, list<string>>
     */
    public const MATRIX = [
        'Admin' => [
            'users.view', 'users.create', 'users.update', 'users.delete',
            'roles.assign', 'branches.view',
            'customers.view', 'customers.create', 'customers.update', 'customers.delete',
            'family_members.manage',
            'measurements.view', 'measurements.create', 'measurements.approve', 'measurements.reject',
            'orders.view', 'orders.create', 'orders.update', 'orders.cancel', 'orders.print_job_card',
            'boxes.assign', 'boxes.mark_placed', 'orders.collect_payment', 'orders.lookup',
            'orders.handover', 'orders.handover.override_balance',
            'orders.notifications.send', 'orders.notifications.view',
            'alterations.view', 'alterations.create', 'alterations.update', 'alterations.deliver',
            ...self::ALL_PRODUCTION,
            'fabric.allocate', 'fabric.release', 'fabric.over_consume',
            'cutting.start', 'cutting.complete', 'bundles.view',
            'tailoring.assign', 'tailoring.start', 'tailoring.complete',
            'tailoring.reassign', 'tailoring.performance.view',
            'qc.inspect', 'qc.override', 'qc.defect_categories.manage',
            ...self::INVENTORY_MANAGER,
            'inventory.fabric_rolls.adjust_out_approve',
            'damage_reports.create', 'damage_reports.view',
            'damage_reports.approve', 'damage_reports.reject',
            'rack.view', 'rack.slots.manage', 'rack.assign', 'rack.release',
            ...self::ALL_DELIVERIES,
            ...self::ALL_FINANCE,
            ...self::ALL_PRINTING,
            ...self::ALL_REPORTING,
            'audit.view', 'audit.transitions.view',
            'roles.view', 'roles.manage', 'permissions.view', 'permissions.manage',
        ],
        'Accountant' => [
            'customers.view',
            'orders.view',
            ...self::ALL_FINANCE,
            ...self::ALL_REPORTING,
        ],
        'Front Desk' => [
            'customers.view', 'customers.create', 'customers.update',
            'family_members.manage',
            // Front Desk creates/edits measurements and uses them immediately —
            // no approval needed. It still does NOT hold measurements.approve.
            'measurements.view', 'measurements.create',
            'orders.view', 'orders.create', 'orders.update', 'orders.cancel', 'orders.print_job_card',
            'boxes.assign', 'boxes.mark_placed', 'orders.collect_payment', 'orders.lookup',
            'orders.handover',
            // Front Desk collects payment, so it can also hand the customer their
            // invoice PDF — without the rest of the finance module.
            'finance.invoice.download',
            'orders.notifications.send', 'orders.notifications.view',
            // Front Desk: create + view + mark-delivered only (their handover step).
            // It does NOT hold alterations.update, so it cannot approve/start/ready/cancel.
            'alterations.view', 'alterations.create', 'alterations.deliver',
            // Read-only visibility of deliveries so the handover desk can see them.
            'deliveries.view',
            // Single-operator mode: Front Desk also runs the production board and can
            // make every stage move. Manager-only actions (assigning section
            // supervisors, the dashboard, raising/holding issues) stay out of scope.
            'production.view',
            'production.transition.fabric_allocated',
            'production.transition.cutting',
            'production.transition.tailoring',
            'production.transition.kaja',
            'production.transition.finishing',
            'production.transition.qc',
            'production.transition.rework',
            'production.transition.packing',
            'production.transition.ready_for_delivery',
            'production.transition.delivered',
            'production.transition.cancel',
            ...self::ALL_PRINTING,
        ],
        'Measurement Staff' => [
            'customers.view',
            'measurements.view', 'measurements.create',
        ],
        'QC Supervisor' => [
            'measurements.view', 'measurements.approve', 'measurements.reject',
            'production.view',
            'production.transition.qc', 'production.transition.rework',
            'production.transition.packing', 'production.transition.cancel',
            'production.rework.override',
            'qc.inspect', 'qc.override', 'qc.defect_categories.manage',
            // Phase 7B — report cloth damage found during QC (approval stays owner-grade).
            'damage_reports.create', 'damage_reports.view',
            // Phase 7D — QC hands the passed garment to packing.
            'production.packing.manage',
            // Kanban Phase B — QC raises and closes quality issues.
            'production.issue.report', 'production.issue.resolve', 'production.hold.manage',
        ],
        'Production Supervisor' => [
            'measurements.view', 'measurements.approve', 'measurements.reject',
            ...self::ALL_PRODUCTION,
            'fabric.allocate', 'fabric.release', 'fabric.over_consume',
            'cutting.start', 'cutting.complete', 'bundles.view',
            'tailoring.assign', 'tailoring.start', 'tailoring.complete',
            'tailoring.reassign', 'tailoring.performance.view',
            'qc.inspect', 'qc.override',
            'damage_reports.create', 'damage_reports.view',
            'rack.view', 'rack.slots.manage', 'rack.assign', 'rack.release',
            // Production Supervisor runs the customer-alteration workflow.
            'alterations.view', 'alterations.update', 'alterations.deliver',
            ...self::ALL_DELIVERIES,
            ...self::ALL_PRINTING,
            ...self::ALL_REPORTING,
            'audit.transitions.view',
        ],
        'Cutting Master' => [
            'production.view',
            'production.transition.fabric_allocated', 'production.transition.cutting',
            'fabric.allocate', 'fabric.release',
            'cutting.start', 'cutting.complete', 'bundles.view',
            // Phase 7B — report cloth damage during cutting (mis-cut, tear, …).
            'damage_reports.create', 'damage_reports.view',
            'production.issue.report',
        ],
        'Inventory Manager' => [
            'production.view',
            'fabric.allocate', 'fabric.release', 'fabric.over_consume', 'bundles.view',
            ...self::INVENTORY_MANAGER,
            'damage_reports.create', 'damage_reports.view',
            'production.issue.report',
        ],
        'Tailor' => [
            'production.view', 'production.transition.tailoring', 'bundles.view',
            'tailoring.start', 'tailoring.complete',
            // Phase 7B — report cloth damage during tailoring.
            'damage_reports.create', 'damage_reports.view',
            'production.issue.report',
        ],
        'Kaja Button' => [
            'production.view', 'production.transition.kaja',
            'production.issue.report',
        ],
        'Ironing Master' => [
            'production.view', 'production.transition.finishing',
            // Phase 7B — report cloth damage during finishing/ironing.
            'damage_reports.create', 'damage_reports.view',
            // Phase 7D — finishing/ironing hands the garment to final packing.
            'production.packing.manage',
            'production.issue.report',
        ],
        'Re-Worker' => [
            'production.view', 'production.transition.rework',
            'production.issue.report',
        ],
        'Delivery Staff' => [
            'production.view',
            'production.transition.ready_for_delivery', 'production.transition.delivered',
            'rack.view', 'rack.assign', 'rack.release',
            ...self::ALL_DELIVERIES,
        ],
    ];

    public function run(): void
    {
        // Operate in the global (null) team context so roles are shared across
        // branches; data isolation is enforced by branch_id, not by team scope.
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        DB::transaction(function (): void {
            foreach (self::PERMISSIONS as $permission) {
                Permission::findOrCreate($permission, 'web');
            }

            foreach (self::ROLES as $roleName) {
                $role = Role::findOrCreate($roleName, 'web');

                if (isset(self::MATRIX[$roleName])) {
                    $role->syncPermissions(self::MATRIX[$roleName]);
                }
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
