<?php

declare(strict_types=1);

namespace App\Modules\Finance\Policies;

use App\Models\User;

/**
 * Finance authorization. Registered on Invoice and used class-level. Only
 * Owner/Admin/Accountant hold the finance permissions; Owner additionally
 * bypasses via Gate::before. Branch isolation is enforced by BelongsToBranch.
 */
final class FinancePolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermissionTo('finance.view');
    }

    public function view(User $actor): bool
    {
        return $actor->hasPermissionTo('finance.view');
    }

    /**
     * Download an invoice PDF. Narrower than view: the payment-collecting desk
     * (Front Desk) holds this to hand a paying customer their invoice, without
     * the broader finance.view access to the rest of the module.
     */
    public function downloadInvoice(User $actor): bool
    {
        return $actor->hasPermissionTo('finance.invoice.download');
    }

    public function create(User $actor): bool
    {
        return $actor->hasPermissionTo('finance.invoice.create');
    }

    public function recordPayment(User $actor): bool
    {
        return $actor->hasPermissionTo('finance.payment.record');
    }

    public function issueCreditNote(User $actor): bool
    {
        return $actor->hasPermissionTo('finance.credit_note.issue');
    }

    public function viewDashboard(User $actor): bool
    {
        return $actor->hasPermissionTo('finance.dashboard.view');
    }
}
