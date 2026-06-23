<?php

declare(strict_types=1);

namespace App\Modules\Customer\Services;

use App\Modules\Customer\Models\Customer;
use App\Modules\Customer\Models\FamilyMember;

final class FamilyMemberService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Customer $customer, array $data): FamilyMember
    {
        return $customer->familyMembers()->create([
            'name' => $data['name'],
            'relation' => $data['relation'] ?? null,
            'dob' => $data['dob'] ?? null,
            'gender' => $data['gender'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(FamilyMember $member, array $data): FamilyMember
    {
        $member->fill([
            'name' => $data['name'] ?? $member->name,
            'relation' => $data['relation'] ?? $member->relation,
            'dob' => $data['dob'] ?? $member->dob,
            'gender' => $data['gender'] ?? $member->gender,
            'notes' => $data['notes'] ?? $member->notes,
        ])->save();

        return $member;
    }

    public function delete(FamilyMember $member): void
    {
        $member->delete();
    }
}
