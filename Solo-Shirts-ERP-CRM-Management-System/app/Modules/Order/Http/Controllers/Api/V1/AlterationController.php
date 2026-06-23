<?php

declare(strict_types=1);

namespace App\Modules\Order\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Modules\Order\Http\Requests\CreateAlterationRequest;
use App\Modules\Order\Http\Requests\UpdateAlterationStatusRequest;
use App\Modules\Order\Models\AlterationRequest;
use App\Modules\Order\Models\AlterationStatusLog;
use App\Modules\Order\Services\AlterationService;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Customer post-delivery alteration intake (Front Desk). Read/create only here —
 * the alteration production workflow (status transitions) is a later phase.
 */
final class AlterationController extends BaseApiController
{
    public function __construct(private readonly AlterationService $alterations) {}

    public function store(CreateAlterationRequest $request): JsonResponse
    {
        $this->authorize('create', AlterationRequest::class);

        /** @var User $actor */
        $actor = $request->user();

        $data = $request->validated();
        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('alterations');
        }

        $alteration = $this->alterations->create($data, $actor)->load('order', 'orderItem', 'customer');

        return $this->respond([
            'alteration_id' => $alteration->id,
            'status' => $alteration->status,
            'original_order_code' => $alteration->order?->order_code,
            'original_item_code' => $alteration->orderItem?->item_code,
            'customer_name' => $alteration->customer?->name,
        ], 'Alteration request created', 201);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AlterationRequest::class);

        $query = AlterationRequest::query()
            ->with(['order:id,order_code', 'orderItem:id,item_code', 'customer:id,name,phone,phone_last4'])
            ->latest('id');

        if ($request->filled('status')) {
            $query->where('status', (string) $request->string('status'));
        }
        if ($request->filled('priority')) {
            $query->where('priority', (string) $request->string('priority'));
        }
        if ($request->filled('order_id')) {
            $query->where('original_order_id', $request->integer('order_id'));
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->date('to'));
        }
        $this->applySearch($query, trim((string) $request->query('q', '')));

        $rows = $query->limit(50)->get();

        $showPhone = $this->canSeeFullPhone($request->user());

        return $this->respond($rows->map(fn (AlterationRequest $a): array => $this->summary($a, false, $showPhone))->all());
    }

    public function show(Request $request, AlterationRequest $alteration): JsonResponse
    {
        $this->authorize('view', $alteration);

        $alteration->load([
            'order:id,order_code',
            'orderItem',
            'customer:id,name,phone,phone_last4',
            'requester:id,name',
            'statusLogs.changedBy:id,name',
        ]);

        $user = $request->user();
        $allowedNext = $this->allowedNextStatusesFor($alteration, $user);

        $payload = $this->summary($alteration, true, $this->canSeeFullPhone($user)) + [
            'allowed_next_statuses' => $allowedNext,
            'can_update_status' => $allowedNext !== [],
            'completed_at' => $alteration->completed_at?->toIso8601String(),
            'cancelled_at' => $alteration->cancelled_at?->toIso8601String(),
            'status_logs' => $alteration->statusLogs->map(fn (AlterationStatusLog $l): array => [
                'id' => $l->id,
                'previous_status' => $l->previous_status,
                'new_status' => $l->new_status,
                'changed_by' => $l->changedBy?->name,
                'notes' => $l->notes,
                'created_at' => $l->created_at?->toIso8601String(),
            ])->all(),
        ];

        return $this->respond($payload);
    }

    public function updateStatus(UpdateAlterationStatusRequest $request, AlterationRequest $alteration): JsonResponse
    {
        $to = (string) $request->validated()['status'];

        // Marking an alteration delivered is the Front-Desk handover step
        // (alterations.deliver); every other transition is workflow management
        // (alterations.update). Authorising on the target status keeps the
        // Front-Desk capability to exactly "ready -> delivered".
        $this->authorize($to === AlterationRequest::STATUS_DELIVERED ? 'deliver' : 'update', $alteration);

        /** @var User $actor */
        $actor = $request->user();
        $previous = $alteration->status;

        $updated = $this->alterations->updateStatus($alteration, $to, $request->input('notes'), $actor);

        return $this->respond([
            'alteration_id' => $updated->id,
            'status' => $updated->status,
            'previous_status' => $previous,
            'updated_by' => $actor->name,
            'updated_at' => $updated->updated_at?->toIso8601String(),
        ], 'Alteration status updated');
    }

    /** Public but signature-protected photo download (no raw path exposed). */
    public function photo(AlterationRequest $alteration): StreamedResponse
    {
        abort_if($alteration->photo_path === null, 404);
        abort_unless(Storage::exists($alteration->photo_path), 404);

        return Storage::response($alteration->photo_path);
    }

    private function applySearch(Builder $query, string $q): void
    {
        if ($q === '') {
            return;
        }

        $digits = (string) preg_replace('/\D/', '', $q);

        $query->where(function (Builder $sub) use ($q, $digits): void {
            $sub->whereHas('order', fn (Builder $o): Builder => $o->where('order_code', 'like', "%{$q}%"))
                ->orWhereHas('orderItem', fn (Builder $i): Builder => $i->where('item_code', 'like', "%{$q}%"));

            if (strlen($digits) >= 4) {
                $last4 = substr($digits, -4);
                $sub->orWhereHas('customer', fn (Builder $c): Builder => $c->where('phone_last4', $last4));
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(AlterationRequest $a, bool $full, bool $showPhone = false): array
    {
        $base = [
            'id' => $a->id,
            'status' => $a->status,
            'priority' => $a->priority,
            'issue_type' => $a->issue_type,
            'customer_name' => $a->customer?->name,
            // Full, decrypted phone only for staff allowed to contact the customer
            // (Front Desk / Admin / Owner); everyone else gets phone_masked only.
            'phone' => $showPhone ? $a->customer?->phone : null,
            'phone_masked' => $this->mask($a->customer?->phone_last4),
            'order_code' => $a->order?->order_code,
            'item_code' => $a->orderItem?->item_code,
            'charge_required' => $a->charge_required,
            'estimated_charge' => $a->estimated_charge_paise !== null ? $a->estimated_charge_paise / 100 : null,
            'created_at' => $a->created_at?->toIso8601String(),
            'photo_url' => $this->photoUrl($a),
        ];

        if (!$full) {
            $base['issue_preview'] = Str::limit($a->issue_description, 80);

            return $base;
        }

        $design = is_array($a->orderItem?->design_notes) ? $a->orderItem->design_notes : [];

        return $base + [
            'issue_description' => $a->issue_description,
            'created_by' => $a->requester?->name,
            'product_type' => $a->orderItem?->product_type,
            'fabric' => $design['fabric'] ?? $a->orderItem?->fabric_preference_text,
            'style' => $design['style'] ?? null,
            'fit' => $design['fit'] ?? null,
        ];
    }

    private function photoUrl(AlterationRequest $a): ?string
    {
        if ($a->photo_path === null) {
            return null;
        }

        return URL::temporarySignedRoute('alterations.photo', now()->addMinutes(10), ['alteration' => $a->id]);
    }

    private function mask(?string $last4): ?string
    {
        return $last4 !== null && $last4 !== '' ? '****' . $last4 : null;
    }

    /**
     * Front-desk-facing roles may see the full customer phone so they can call or
     * search for the customer. Any other role that gains alterations.view later
     * still gets the masked phone only — full phone is opt-in by role.
     */
    private function canSeeFullPhone(?User $user): bool
    {
        return $user !== null && $user->hasAnyRole(['Owner', 'Admin', 'Front Desk']);
    }

    /**
     * The workflow-allowed next statuses, filtered to those the user may actually
     * perform: 'delivered' needs alterations.deliver (or update), everything else
     * needs alterations.update. Empty when the request is final or the user has no
     * update rights — the UI then renders no action buttons.
     *
     * @return list<string>
     */
    private function allowedNextStatusesFor(AlterationRequest $alteration, ?User $user): array
    {
        if ($user === null) {
            return [];
        }

        $canUpdate = $user->can('alterations.update');
        $canDeliver = $user->can('alterations.deliver');

        return array_values(array_filter(
            $alteration->allowedNextStatuses(),
            fn (string $status): bool => $status === AlterationRequest::STATUS_DELIVERED
                ? ($canUpdate || $canDeliver)
                : $canUpdate,
        ));
    }
}
