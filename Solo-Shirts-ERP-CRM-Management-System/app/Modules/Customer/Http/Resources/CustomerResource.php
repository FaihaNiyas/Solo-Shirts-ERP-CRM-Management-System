<?php

declare(strict_types=1);

namespace App\Modules\Customer\Http\Resources;

use App\Modules\Customer\Models\Customer;
use App\Modules\Shared\Http\Resources\BaseResource;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

/**
 * @mixin Customer
 */
final class CustomerResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'branch_id' => $this->branch_id,
            'customer_code' => $this->customer_code,
            'name' => $this->name,
            // Full, decrypted phone for desk-facing roles who must contact the
            // customer (Owner / Admin / Front Desk); others get phone_masked only.
            'phone' => $this->when($this->canSeeFullPhone($request->user()), fn () => $this->phone),
            'phone_masked' => $this->maskPhone($this->phone),
            'address' => $this->address,
            'preferred_fabric_id' => $this->preferred_fabric_id,
            'special_notes' => $this->special_notes,
            'last_measurement_at' => null, // populated in Phase 5
            'family_members' => FamilyMemberResource::collection($this->whenLoaded('familyMembers')),
            'created_at' => $this->date($this->created_at),
        ];
    }

    private function canSeeFullPhone(?Authenticatable $user): bool
    {
        return $user !== null
            && method_exists($user, 'hasAnyRole')
            && $user->hasAnyRole(['Owner', 'Admin', 'Front Desk']);
    }

    private function maskPhone(?string $phone): ?string
    {
        if ($phone === null || $phone === '') {
            return null;
        }

        $visible = 4;
        $masked = max(0, strlen($phone) - $visible);

        return str_repeat('*', $masked) . substr($phone, -$visible);
    }
}
