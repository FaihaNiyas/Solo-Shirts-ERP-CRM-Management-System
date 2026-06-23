<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\Modules\Customer\Models\Customer;
use App\Modules\Customer\Policies\CustomerPolicy;
use App\Modules\Delivery\Listeners\OnDeliveredOrCancelledReleaseSlot;
use App\Modules\Delivery\Listeners\OnReadyForDeliveryAssignSlot;
use App\Modules\Delivery\Models\Delivery;
use App\Modules\Delivery\Models\RackSlot;
use App\Modules\Delivery\Policies\DeliveryPolicy;
use App\Modules\Delivery\Policies\RackPolicy;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Policies\FinancePolicy;
use App\Modules\Identity\Models\Branch;
use App\Modules\Identity\Policies\BranchPolicy;
use App\Modules\Identity\Policies\UserPolicy;
use App\Modules\Inventory\Contracts\StockLedgerInterface;
use App\Modules\Inventory\Models\DamageReport;
use App\Modules\Inventory\Models\FabricRoll;
use App\Modules\Inventory\Policies\DamageReportPolicy;
use App\Modules\Inventory\Policies\InventoryPolicy;
use App\Modules\Inventory\Services\StockLedgerService;
use App\Modules\Measurement\Models\MeasurementProfile;
use App\Modules\Measurement\Models\MeasurementVersion;
use App\Modules\Measurement\Policies\MeasurementPolicy;
use App\Modules\Order\Models\AlterationRequest;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Order\Policies\AlterationPolicy;
use App\Modules\Order\Policies\OrderPolicy;
use App\Modules\Printing\Models\Document;
use App\Modules\Printing\Policies\DocumentPolicy;
use App\Modules\Production\Events\OrderItemStateChanged;
use App\Modules\Production\Events\ProductionIssueReported;
use App\Modules\Production\Listeners\AppendActivityLog;
use App\Modules\Production\Listeners\NotifyOnReadyForDelivery;
use App\Modules\Production\Listeners\RecomputeOrderDerivedStatus;
use App\Modules\Production\Listeners\SendIssueNotification;
use App\Modules\Production\Listeners\SendProductionNotifications;
use App\Modules\Production\Models\CutBundle;
use App\Modules\Production\Models\DefectCategory;
use App\Modules\Production\Models\TailorAssignment;
use App\Modules\Production\Policies\CutBundlePolicy;
use App\Modules\Production\Policies\DefectCategoryPolicy;
use App\Modules\Production\Policies\ProductionPolicy;
use App\Modules\Production\Policies\TailorAssignmentPolicy;
use App\Modules\Reporting\Models\ReportJob;
use App\Modules\Reporting\Policies\ReportingPolicy;
use App\Modules\Shared\Policies\AuditPolicy;
use App\Modules\Shared\Services\BranchContext;
use App\Modules\Shared\Services\LogNotificationDispatcher;
use App\Modules\Shared\Services\NotificationDispatcher;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\Models\Activity;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // One BranchContext per request lifecycle.
        $this->app->scoped(BranchContext::class);

        // Fabric stock ledger seam (Phase 8 impl; Phase 11 will own it).
        $this->app->bind(StockLedgerInterface::class, StockLedgerService::class);

        // Outbound notification seam (Phase 14 OTP dispatch; Phase 17 adds real
        // SMS/WhatsApp gateways). Default logs the intent without secrets.
        $this->app->bind(NotificationDispatcher::class, LogNotificationDispatcher::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // utf8mb4 indexes exceed MySQL's key-length limit at the default 255;
        // cap indexed string columns at 191 chars.
        Schema::defaultStringLength(191);

        $this->loadModuleMigrations();

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Branch::class, BranchPolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(MeasurementProfile::class, MeasurementPolicy::class);
        Gate::policy(MeasurementVersion::class, MeasurementPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(AlterationRequest::class, AlterationPolicy::class);
        Gate::policy(OrderItem::class, ProductionPolicy::class);
        Gate::policy(CutBundle::class, CutBundlePolicy::class);
        Gate::policy(TailorAssignment::class, TailorAssignmentPolicy::class);
        Gate::policy(DefectCategory::class, DefectCategoryPolicy::class);
        Gate::policy(FabricRoll::class, InventoryPolicy::class);
        Gate::policy(DamageReport::class, DamageReportPolicy::class);
        Gate::policy(RackSlot::class, RackPolicy::class);
        Gate::policy(Delivery::class, DeliveryPolicy::class);
        Gate::policy(Invoice::class, FinancePolicy::class);
        Gate::policy(Document::class, DocumentPolicy::class);
        Gate::policy(ReportJob::class, ReportingPolicy::class);
        Gate::policy(Activity::class, AuditPolicy::class);

        // The Owner role bypasses every permission/policy check.
        Gate::before(function (Authorizable $user): ?bool {
            return method_exists($user, 'hasRole') && $user->hasRole('Owner') ? true : null;
        });

        // Phase 7 production transitions fan out to audit, cache invalidation and
        // (eventually) customer notifications.
        Event::listen(OrderItemStateChanged::class, AppendActivityLog::class);
        Event::listen(OrderItemStateChanged::class, RecomputeOrderDerivedStatus::class);
        Event::listen(OrderItemStateChanged::class, NotifyOnReadyForDelivery::class);

        // Kanban Phase F: in-app production notifications to section supervisors.
        Event::listen(OrderItemStateChanged::class, SendProductionNotifications::class);
        Event::listen(ProductionIssueReported::class, SendIssueNotification::class);

        // Phase 13: rack slots auto-assign on ready-for-delivery, free on delivered/cancelled.
        Event::listen(OrderItemStateChanged::class, OnReadyForDeliveryAssignSlot::class);
        Event::listen(OrderItemStateChanged::class, OnDeliveredOrCancelledReleaseSlot::class);
    }

    /**
     * Each module owns its migrations under app/Modules/{Module}/Database/Migrations.
     * Register every such directory so `migrate` discovers them without a per-module
     * service provider.
     */
    private function loadModuleMigrations(): void
    {
        $paths = glob(app_path('Modules/*/Database/Migrations'));

        if ($paths !== false && $paths !== []) {
            $this->loadMigrationsFrom($paths);
        }
    }
}
