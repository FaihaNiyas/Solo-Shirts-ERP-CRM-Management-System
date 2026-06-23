<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Policies;

use App\Models\User;

/**
 * Reporting/dashboard/notification authorization. Registered on ReportJob and
 * used class-level. Owner bypasses via Gate::before; branch isolation via
 * BelongsToBranch.
 */
final class ReportingPolicy
{
    public function viewDashboard(User $actor): bool
    {
        return $actor->hasPermissionTo('dashboard.view');
    }

    public function runReports(User $actor): bool
    {
        return $actor->hasPermissionTo('reports.run');
    }

    public function viewReports(User $actor): bool
    {
        return $actor->hasPermissionTo('reports.view');
    }

    public function viewNotifications(User $actor): bool
    {
        return $actor->hasPermissionTo('notifications.view');
    }
}
