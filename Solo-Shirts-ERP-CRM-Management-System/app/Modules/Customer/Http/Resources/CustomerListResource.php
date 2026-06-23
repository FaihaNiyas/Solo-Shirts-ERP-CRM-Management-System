<?php

declare(strict_types=1);

namespace App\Modules\Customer\Http\Resources;

use App\Modules\Customer\Models\Customer;
use App\Modules\Shared\Http\Resources\BaseResource;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

/**
 * Lighter customer representation for list endpoints.
 *
 * @mixin Customer
 */
final class CustomerListResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_code' => $this->customer_code,
            'name' => $this->name,
            // Full, decrypted phone for desk-facing roles who must contact the
            // customer (Owner / Admin / Front Desk); others get phone_last4 only.
            'phone' => $this->canSeeFullPhone($request->user()) ? $this->phone : null,
            'phone_last4' => $this->phone_last4,
            'last_measurement_at' => null, // populated in Phase 5
        ];
    }

    private function canSeeFullPhone(?Authenticatable $user): bool
    {
        return $user !== null
            && method_exists($user, 'hasAnyRole')
            && $user->hasAnyRole(['Owner', 'Admin', 'Front Desk']);
    }
}
