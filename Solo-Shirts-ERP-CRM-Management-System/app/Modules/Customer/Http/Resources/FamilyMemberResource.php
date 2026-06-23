<?php

declare(strict_types=1);

namespace App\Modules\Customer\Http\Resources;

use App\Modules\Customer\Models\FamilyMember;
use App\Modules\Shared\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @mixin FamilyMember
 */
final class FamilyMemberResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_id' => $this->customer_id,
            'name' => $this->name,
            'relation' => $this->relation,
            'dob' => $this->dob?->toDateString(),
            'gender' => $this->gender,
            'notes' => $this->notes,
        ];
    }
}
